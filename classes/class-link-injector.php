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

				// Loop through all the instances of the keyword in the node. This is needed because a paragraph can have multiple instances of the keyword. `htmlspecialchars()` added because "&" in content threw errors.
				$replaced = preg_replace_callback( "/\b({$keywords})\b/i", function( $matches ) use ( $url, &$count ) {
					// Check if we're over the limit.
					if ( $this->limit && $count > $this->limit ) {
						// Return the original matched string without replacement.
						return $matches[0];
					} else {
						$count++;
						// Return the new link.
						return sprintf('<a href="%s">%s</a>', esc_url( $url ), htmlspecialchars( $matches[1] ) );
					}
				}, htmlspecialchars( $node->nodeValue ) );

				// Replace.
				$fragment = $dom->createDocumentFragment();
				$fragment->appendXml( $replaced );
				$node->parentNode->replaceChild( $fragment, $node );
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
