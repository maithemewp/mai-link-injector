<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Mai Link Injector class.
 */
class Mai_Link_Injector {
	protected $links; // Assocative array of 'the keyword string' => 'https://theurl.com'.
	protected $limit_max; // Don't inject more than this number of links. Use 0 for no limit.
	protected $limit_el; // Don't inject more than this number of links per element. Use 0 for no limit.
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
		$this->links     = $this->sanitize( $links );
		$this->limit_max = 0;
		$this->limit_el  = 0;
		$this->limit     = 0;
	}

	/**
	 * Set max number of links.
	 *
	 * @since 1.4.0
	 *
	 * @param int $limit The max number of links.
	 *
	 * @return int
	 */
	function set_limit_max( $limit ) {
		$this->limit_max = absint( $limit );
	}

	/**
	 * Set max number of links per element.
	 *
	 * @since 1.4.0
	 *
	 * @param int $limit The max number of links per element.
	 *
	 * @return int
	 */
	function set_limit_el( $limit ) {
		$this->limit_el = absint( $limit );
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
		$dom = $this->get_dom_document( $content );

		// Create xpath.
		$xpath = new DOMXPath( $dom );

		// Get current url.
		$current_url = $this->get_compare_url( home_url( add_query_arg( [] ) ) );

		// Set the number of injected links.
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
				'*[contains(concat(" ", @class, " "), " mai-link-exclude ")]',
			];

			// Filter and sanitize.
			$invalid = apply_filters( 'mai_link_injector_invalid_elements', $invalid );
			$invalid = array_map( 'sanitize_text_field', $invalid );
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
			$results = $xpath->query( $expression );

			// Bail if no results.
			if ( ! $results->length ) {
				continue;
			}

			// Set total instances.
			$instances = 0;

			// Loop through query.
			foreach ( $results as $node ) {
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

				// If we have a max and the instances plus the injected links will be over our max.
				if ( $this->limit_max && ( $instances + $injected ) > $this->limit_max ) {
					// Set limit on this keyword to the max minus the injected, if it's less than the limit.
					$limit = min( $this->limit_max - $injected, $limit );
				}

				// If we still have a limit, get indexes.
				if ( $limit ) {
					$indexes = $this->get_indexes( $instances, $limit );
				}
			}

			// Start count.
			$count = 1;

			// Loop through query.
			foreach ( $results as $index => $node ) {
				// Bail if we hit the max.
				if ( $this->limit_max && $injected >= $this->limit_max ) {
					// Break out of both loops.
					break 2;
				}

				// Bail if over limit.
				if ( $this->limit && $count > $this->limit ) {
					break;
				}

				// Skip if we have a set amount of indexes and this is not one we're replacing.
				if ( $indexes && ! isset( $indexes[ $index ] ) ) {
					continue;
				}

				// If checking element limit.
				if ( $this->limit_el ) {
					// Get parent.
					$parent = $this->get_parent( $node );

					// If we have a parent node.
					if ( $parent ) {
						// Query for links.
						// $links = $xpath->query( './/a[@class="mai-link-injected"]', $parent );
						$links = $xpath->query( './/a', $parent );

						// Skip if the parent node already has N links.
						if ( $links->length >= $this->limit_el ) {
							continue;
						}
					}
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

				/**
				 * Build the temporary dom.
				 * Special characters were causing issues with `appendXML()`.
				 *
				 * @link https://stackoverflow.com/questions/4645738/domdocument-appendxml-with-special-characters
				 * @link https://www.py4u.net/discuss/974358
				 */
				$tmp      = $this->get_dom_document( $replaced );
				$fragment = $dom->createDocumentFragment();

				// Import the nodes.
				foreach ( $tmp->childNodes as $child ) {
					$new = $dom->importNode( $child, true );
					$fragment->appendChild( $new );
				}

				// Replace the node with the fragment.
				$node->parentNode->replaceChild( $fragment, $node );

				// Increment counts.
				$count++;
				$injected++;
			}
		}

		// Get the HTML.
		$content = $this->get_dom_html( $dom );

		return $content;
	}

	/**
	 * Get the parent node.
	 * Skip <span>, <strong>, and <em>.
	 *
	 * @param object $node
	 *
	 * @return mixed
	 */
	function get_parent( $node ) {
		// If null, or a dom element that's not a span, strong, or em, return it.
		if ( is_null( $node ) || ( $node instanceof DOMElement && ! in_array( $node->tagName, [ 'span', 'strong', 'em' ] ) ) ) {
			return $node;
		}

		// Move up.
		return $this->get_parent( $node->parentNode );
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
		// Build attr.
		$attr = [
			'href'  => $url, // Already sanitized.
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
		return sprintf('<a%s>%s</a>', $attributes, $text );
	}

	/**
	 * Convert quotes to curly quotes.
	 *
	 * @since 1.4.0
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

	/**
	 * Get the DOM document.
	 *
	 * @since TBD
	 *
	 * @param string $html The HTML to load.
	 *
	 * @return DOMDocument
	 */
	function get_dom_document( $html ) {
		// Create the new document.
		$dom = new DOMDocument();

		// Modify state.
		$libxml_previous_state = libxml_use_internal_errors( true );

		// Encode.
		$html = mb_encode_numericentity( $html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8' );

		// Load the content in the document HTML.
		$dom->loadHTML( "<div>$html</div>" );

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

		return $dom;
	}

	/**
	 * Get the HTML from a DOM document.
	 *
	 * @since TBD
	 *
	 * @param DOMDocument $dom The DOM document.
	 *
	 * @return string
	 */
	function get_dom_html( $dom ) {
		// Save and decode.
		$html = $dom->saveHTML();
		$html = htmlspecialchars_decode( $html );
		$html = mb_convert_encoding( $html, 'UTF-8', 'HTML-ENTITIES' );

		return $html;
	}
}
