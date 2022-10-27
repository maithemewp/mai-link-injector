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

		// Load the content in the document HTML.
		$dom->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );

		// Remove <!DOCTYPE.
		$dom->removeChild( $dom->doctype );

		// Handle errors.
		libxml_clear_errors();

		// Restore.
		libxml_use_internal_errors( $libxml_previous_state );

		$xpath = new DOMXPath( $dom );

		foreach ( $this->links as $keywords => $url ) {
			$query   = sprintf( '//text()[contains(translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "%s")', $this->strtolower( $keywords )  );
			$invalid = [
				'h1',
				'h2',
				'h3',
				'h4',
				'h5',
				'h6',
				'a',
				'blockquote',
				'button',
				'input',
				'select',
				'submit',
				'textarea',
			];
			$invalid = apply_filters( 'mai_link_injector_invalid_elements', $invalid );
			$invalid = array_map( 'sanitize_key', $invalid );
			$invalid = array_unique( $invalid );

			foreach ( $invalid as $tag ) {
				$query .= sprintf( ' and not(ancestor::%s)', $tag );
			}

			$query .= ']';

			$search = $xpath->query( $query );

			if ( ! $search->length ) {
				continue;
			}

			$count = 1;

			foreach ( $search as $node ) {
				// Bail if over limit.
				if ( $this->limit && $count > $this->limit ) {
					break;
				}

				$link     = sprintf( '<a href="%s">%s</a>', esc_url( $url ), wp_kses_post( $keywords ) );
				$replaced = preg_replace( "/\b({$keywords})\b/i", sprintf( '<a href="%s">', esc_url( $url ) ) . "$1" . '</a>', $node->nodeValue );
				$fragment = $dom->createDocumentFragment();
				$fragment->appendXml( $replaced );
				$node->parentNode->replaceChild( $fragment, $node );
				$count++;
			}
		}

		// Save new HTML without html/body wrap.
		$content = substr( $dom->saveHTML(), 12, -15 );

		return $content;
	}

	/**
	 * Sanitized a string to lowercase, keeping character encoding.
	 *
	 * @param string $string The string to make lowercase.
	 *
	 * @return string
	 */
	function strtolower( $string ) {
		return mb_strtolower( (string) $string, 'UTF-8' );
	}
}
