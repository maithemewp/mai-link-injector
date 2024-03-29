<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Mai Link Injector class.
 */
class Mai_Link_Injector {
	protected $links; // Assocative array of 'the keyword string' => 'https://theurl.com'.
	protected $limit; // Don't link more than this number of items. Use 0 for no limit.

	/**
	 * Class constructor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function __construct( array $links ) {
		$this->links = $this->sanitize( $links );
		$this->limit = 0;
	}

	/**
	 * Set link limit per item.
	 *
	 * @since 0.1.0
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
			$sanitized[ wp_kses_post( $this->strtolower( $text ) ) ] = esc_url( $url );
		}

		return array_filter( $sanitized );
	}

	/**
	 * Adds links to matching text in content.
	 *
	 * @since 0.1.0
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

		$xpath = new DOMXPath( $dom );

		foreach ( $this->links as $keywords => $url ) {
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
			foreach ( $invalid as $tag ) {
				$expression .= sprintf( ' and not(ancestor::%s)', $tag );
			}

			// Close the expression.
			$expression .= ']';

			// Run query.
			$query = $xpath->query( $expression );

			// Bail if no results.
			if ( ! $query->length ) {
				continue;
			}

			// Start count.
			$count = 1;

			// Loop through query.
			foreach ( $query as $node ) {
				// Bail if over limit.
				if ( $this->limit && $count > $this->limit ) {
					break;
				}

				/**
				 * Replace the first insance of the keyword with a link.
				 * Limited to 1 via the last param of `preg_replace_callback()`.
				 * Added `htmlspecialchars()` because `&` in content threw errors.
				 */
				$replaced = preg_replace_callback( "/\b({$keywords})\b/i", function( $matches ) use ( $url, &$count ) {
					// Bail if no matches.
					if ( ! isset( $matches[1] ) ) {
						return $matches[0];
					}

					// Set the new text.
					$text = $matches[1];

					// Build attr.
					$attr = [
						'href' => esc_url( $url ),
					];

					// If external link, add target _blank and rel noopener.
					if ( parse_url( esc_url( $url ), PHP_URL_HOST ) !== parse_url( home_url(), PHP_URL_HOST ) ) {
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

				}, htmlspecialchars( $node->nodeValue ), 1 );

				// Replace.
				$fragment = $dom->createDocumentFragment();
				$fragment->appendXml( $replaced );
				$node->parentNode->replaceChild( $fragment, $node );

				// Increment count.
				$count++;
			}
		}

		// Save and decode.
		$content = $dom->saveHTML();
		$content = mb_convert_encoding( $content, 'UTF-8', 'HTML-ENTITIES' );

		return $content;
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
