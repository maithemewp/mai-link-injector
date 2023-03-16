<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Mai Link Injector Adder class.
 */
class Mai_Link_Injector_Adder {
	protected $typs;
	protected $limit;
	protected $links;

	/**
	 * Class constructor.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function __construct() {
		$this->types = (array) maili_get_option( 'singles' );
		$this->limit = (int) apply_filters( 'mai_link_injector_limit', (int) maili_get_option( 'limit' ) );
		$this->links = $this->get_links();

		if ( ! ( $this->types && $this->links ) ) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Gets links from settings.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	function get_links() {
		$array = [];
		$links = (array) maili_get_option( 'links' );
		$links = apply_filters( 'mai_link_injector_links', $links );

		foreach ( $links as $text => $url ) {
			$text = esc_html( $text );
			$url  = esc_url( $url );

			if ( ! ( $text && $url ) ) {
				continue;
			}

			$array[ $text ] = $url;
		}

		return $array;
	}

	/**
	 * Runs hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'wp_head', [ $this, 'inject' ] );
	}

	/**
	 * Sets up Mai Link Injector.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function inject() {
		if ( ! is_singular( $this->types ) ) {
			return;
		}

		$class = new Mai_Link_Injector( $this->links );
		$class->set_limit( $this->limit );
		$class->run();
	}
}