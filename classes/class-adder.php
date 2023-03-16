<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Mai Link Injector Adder class.
 */
class Mai_Link_Injector_Adder {
	/**
	 * Class constructor.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Runs hooks.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'wp_head', [ $this, 'inject' ] );
	}

	/**
	 * Sets up Mai Link Injector.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	function inject() {
		$options = [
			'singles' => (array) maili_get_option( 'singles' ),
			'limit'   => (int) maili_get_option( 'limit' ),
			'links'   => (array) maili_get_option( 'links' ),
		];

		// Allow filtering of these values, prior to injecting.
		$options = apply_filters( 'mai_link_injector', $options );

		// Sanitize.
		$options['singles'] = array_map( 'sanitize_key', $options['singles'] );
		$options['limit']   = absint( $options['limit'] );
		$options['links']   = $this->sanitize_links( $options['links'] );

		if ( ! ( $options['singles'] && $options['links'] ) ) {
			return;
		}

		if ( ! is_singular( $options['singles'] ) ) {
			return;
		}

		$class = new Mai_Link_Injector( $options['links'] );
		$class->set_limit( $options['limit'] );
		$class->run();
	}

	/**
	 * Gets links from settings.
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	function get_links() {
		$links = (array) maili_get_option( 'links' );
		$links = apply_filters( 'mai_link_injector_links', $links );
	}

	/**
	 * Sanitizes links.
	 *
	 * @param  array $links
	 *
	 * @return array
	 */
	function sanitize_links( array $links ) {
		$array = [];

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
}