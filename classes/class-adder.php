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
			'singles'   => (array) maili_get_option( 'singles' ),
			'limit_max' => (int) maili_get_option( 'limit_max' ),
			'limit_el'  => (int) maili_get_option( 'limit_el' ),
			'limit'     => (int) maili_get_option( 'limit' ),
			'links'     => (array) maili_get_option( 'links' ),
		];

		// Allow filtering of these values, prior to injecting.
		$options = apply_filters( 'mai_link_injector', $options );

		// Sanitize.
		$options['singles']   = array_map( 'sanitize_key', $options['singles'] );
		$options['limit_max'] = absint( $options['limit_max'] );
		$options['limit_el']  = absint( $options['limit_el'] );
		$options['limit']     = absint( $options['limit'] );
		$options['links']     = (array) $options['links']; // Sanitized in `Mai_Link_Injector` class.

		// Bail if we don't have singles and links.
		if ( ! ( $options['singles'] && $options['links'] ) ) {
			return;
		}

		// Bail if we're not on a single post type.
		if ( ! is_singular( $options['singles'] ) ) {
			return;
		}

		// Inject links.
		$class = new Mai_Link_Injector( $options['links'] );
		$class->set_limit_max( $options['limit_max'] );
		$class->set_limit_el( $options['limit_el'] );
		$class->set_limit( $options['limit'] );
		$class->run();
	}
}