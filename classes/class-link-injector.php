<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Mai Link Injector class.
 */
class Mai_Link_Injector {
	protected $links; // Assocative array of 'the keyword string' => 'https://theurl.com'.
	protected $max; // Don't inject more than this number of links. Use 0 for no limit.
	protected $limit; // Don't link more than this number of items. Use 0 for no limit.

	/**
	 * Class constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param array $links The links to inject.
	 *
	 * @return void
	 */
	function __construct( array $links ) {
		$this->links = $this->sanitize( $links );
		$this->max   = 0;
		$this->limit = 0;
	}

	/**
	 * Set max number of links.
	 *
	 * @since TBD
	 *
	 * @param int $max The max number of links.
	 *
	 * @return int
	 */
	function set_max( $max ) {
		$this->max = absint( $max );
	}

	/**
	 * Set link limit per item.
	 *
	 * @since 0.1.0
	 *
	 * @param int $limit The link limit per item.
	 *
	 * @return int
	 */
	function set_limit( $limit ) {
		$this->limit = absint( $limit );
	}

	/**
	 * Inject links.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function run() {
		if ( ! $this->links ) {
			return;
		}

		add_filter( 'the_content', [ $this, 'add_links' ], 20 );
	}

	/**
	 * Gets sanitized links.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function sanitize( $links ) {
		$sanitized = [];

		foreach ( $links as $text => $url ) {
			// Sanitize.
			$text = sanitize_text_field( $text );
			$text = $this->convert_quotes( $text );
			$text = $this->strtolower( $text );
			$url  = esc_url( $url );

			$sanitized[ $text ] = $url;
		}

		return array_filter( $sanitized );
	}

	/**
	 * Adds links to matching text in content.
	 *
	 * @since 0.1.0
	 *
	 * @param string $content The content.
	 *
	 * @return string
	 */
	function add_links( $content ) {
		if ( ! is_main_query() ) {
			return $content;
		}

		if ( ! trim( $content ) ) {
			return $content;
		}

		// Create the new document.
		$dom = new DOMDocument();

		// Modify state.
		$libxml_previous_state = libxml_use_internal_errors( true );

		// Encode.
		$content = mb_encode_numericentity( $content, [0x80, 0x10FFFF, 0, ~0], 'UTF-8' );

		// Load the content in the document HTML.
		$dom->loadHTML( "<div>$content</div>" );

		// Handle wraps.
		$container = $dom->getElementsByTagName('div')->item(0);
		$container = $container->parentNode->removeChild( $container );

		while ( $dom->firstChild ) {
			$dom->removeChild( $dom->firstChild );
		}

		while ( $container->firstChild ) {
			$dom->appendChild( $container->firstChild );
		}

		// Handle errors.
		libxml_clear_errors();

		// Restore.
		libxml_use_internal_errors( $libxml_previous_state );

		// Create xpath.
		$xpath = new DOMXPath( $dom );

		// Get current url.
		$current_url = $this->get_compare_url( home_url( add_query_arg( [] ) ) );

		// Set number of injected links.
		$injected = 0;

		// Loop through links.
		foreach ( $this->links as $keywords => $url ) {
			// Get compare url.
			$link_url = $this->get_compare_url( $url );

			// Skip if current url is the link url.
			if ( $current_url === $link_url ) {
				continue;
			}

			// Create the expression and set invalid elements.
			$expression = sprintf( '//text()[contains(translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "%s")', $this->strtolower( $keywords )  );
			$invalid    = [
				'h1',
				'h2',
				'h3',
				'h4',
				'h5',
				'h6',
				'a',
				'blockquote',
				'button',
				'figcaption',
				'figure',
				'input',
				'select',
				'submit',
				'textarea',
			];

			// Filter and sanitize.
			$invalid = apply_filters( 'mai_link_injector_invalid_elements', $invalid );
			$invalid = array_map( 'sanitize_key', $invalid );
			$invalid = array_unique( $invalid );

			// Add invalid tags to the expression.
			if ( $invalid ) {
				$expression .= ' and not(' . implode(' | ', array_map( function( $element ) {
					return 'ancestor::' . $element;
				}, $invalid ) ) . ')';
			}

			// Close the expression.
			$expression .= ']';

			// Run query.
			$query = $xpath->query( $expression );

			// Bail if no results.
			if ( ! $query->length ) {
				continue;
			}

			// Set total instances.
			$instances = 0;

			// Loop through query.
			foreach ( $query as $node ) {
				// Convert quotes to curly.
				$node->nodeValue = $this->convert_quotes( $node->nodeValue );

				// Add out how many times the keyword appears in a node.
				$instances++;
			}

			// By default, no indexes.
			$indexes = false;

			// If we have a limit and the number of instances is over our limit.
			if ( $this->limit && $instances > $this->limit ) {
				$limit = $this->limit;

				// If we hav a max and the instances plus the injected links will be over our max.
				if ( $this->max && ( $instances + $injected ) > $this->max ) {
					// Set limit on this keyword to the max minus the injected, if it's less than the limit.
					$limit = min( $this->max - $injected, $limit );
				}

				// If we still have a limit, get indexes.
				if ( $limit ) {
					$indexes = $this->get_indexes( $instances, $limit );
				}
			}

			// Start count.
			$count = 1;

			// Loop through query.
			foreach ( $query as $index => $node ) {
				// Bail if we hit the max.
				if ( $this->max && $injected >= $this->max ) {
					// Break out of both loops.
					break 2;
				}

				// Bail if over limit.
				if ( $this->limit && $count > $this->limit ) {
					break;
				}

				// If set amount of indexes, check if we're replacing this one.
				if ( $indexes && ! isset( $indexes[ $index ] ) ) {
					// $index++;
					continue;
				}

				// Replace the first instance of the keyword.
				$replaced = preg_replace_callback( "/\b({$keywords})\b/i", function( $matches ) use ( $url ) {
					// Bail if no matches.
					if ( ! isset( $matches[1] ) ) {
						return $matches[0];
					}

					// Return the replaced string.
					return $this->get_replacement( $url, $matches[1] );

				}, $node->nodeValue, 1 );

				// Replace.
				$fragment = $dom->createDocumentFragment();
				$fragment->appendXml( $replaced );
				$node->parentNode->replaceChild( $fragment, $node );

				// Increment counts.
				$count++;
				$injected++;
			}
		}

		// Save and decode.
		$content = $dom->saveHTML();
		$content = htmlspecialchars_decode( $content );
		$content = mb_convert_encoding( $content, 'UTF-8', 'HTML-ENTITIES' );

		return $content;
	}

	/**
	 * Get the compare url.
	 *
	 * @since 1.4.0
	 *
	 * @param string $url The link URL.
	 *
	 * @return string
	 */
	function get_compare_url( $url ) {
		// Parse the url.
		$parsed = wp_parse_url( $url );

		// Bail if no host or path.
		if ( ! ( isset( $parsed['host'] ) && $parsed['host'] && isset( $parsed['path'] ) ) ) {
			return $url;
		}

		// Remove www.
		$parsed['host'] = str_replace( 'www.', '', $parsed['host'] );

		return $parsed['host'] . $parsed['path'];
	}

	/**
	 * Get indexes for the limit.
	 *
	 * @since 1.4.0
	 *
	 * @param int $instances The total number of instances.
	 * @param int $limit     The limit.
	 *
	 * @return array
	 */
	function get_indexes( $instances, $limit ) {
		$indexes = [];
		$step    = (int) floor( $instances / $limit );

		// Loop through the limit amount.
		for ( $i = 0; $i < $limit; $i++ ) {
			$indexes[] = (int) $i * $step;
		}

		return array_flip( $indexes );
	}

	/**
	 * Get the replacement string.
	 *
	 * @since 1.4.0
	 *
	 * @param string $url  The link URL.
	 * @param string $text The link text.
	 *
	 * @return string
	 */
	function get_replacement( $url, $text ) {
		// Escape.
		$url = esc_url( $url );

		// Build attr.
		$attr = [
			'href'  => $url,
			'class' => 'mai-link-injected',
		];

		// If external link, add target _blank and rel noopener.
		if ( parse_url( $url, PHP_URL_HOST ) !== parse_url( home_url(), PHP_URL_HOST ) ) {
			$attr['target'] = '_blank';
			$attr['rel']    = 'noopener';
		}

		/**
		 * Allow filtering of the link attributes.
		 *
		 * @param array  $attr The link attributes.
		 * @param string $url  The link URL.
		 * @param string $text The link text.
		 *
		 * @return array
		 */
		$attr = (array) apply_filters( 'mai_link_injector_link_attributes', $attr, $url, $text );

		// Atts string.
		$attributes = '';

		// Loop through and add attr.
		foreach ( $attr as $key => $value ) {
			$attributes .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		// Return the replaced string.
		return sprintf('<a%s>%s</a>', $attributes, htmlspecialchars( $text ) );
	}

	/**
	 * Convert quotes to curly quotes.
	 *
	 * @since TBD
	 *
	 * @param string $string The string to convert.
	 *
	 * @return string
	 */
	function convert_quotes( $string ) {
		$string = htmlspecialchars_decode( $string ); // Decode entities like single quotes to actual single quotes.
		$string = wptexturize( $string ); // Convert straight quotes to curly, this also encodes again.
		$string = html_entity_decode( $string, ENT_QUOTES, 'UTF-8' ); // Decode to curly quotes.

		return $string;
	}

	/**
	 * Sanitized a string to lowercase, keeping character encoding.
	 *
	 * @since 0.1.0
	 *
	 * @param string $string The string to make lowercase.
	 *
	 * @return string
	 */
	function strtolower( $string ) {
		return mb_strtolower( (string) $string, 'UTF-8' );
	}
}
