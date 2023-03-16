<?php

/**
 * Gets a single option value by key.
 *
 * @since 1.1.0
 *
 * @param string $key
 * @param mixed  $default
 *
 * @return mixed
 */
function maili_get_option( $key, $default = null ) {
	$options = maili_get_options();
	return isset( $options[ $key ] ) ? $options[ $key ] : $default;
}

/**
 * Gets all options.
 *
 * @since 1.1.0
 *
 * @return array
 */
function maili_get_options() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	// Get all options, with defaults.
	$cache = (array) get_option( 'mai_link_injector', maili_get_options_defaults() );

	return $cache;
}

/**
 * Gets a single option default value by key.
 *
 * @since 1.1.0
 *
 * @param string $key
 * @param mixed  $default
 *
 * @return mixed
 */
function maili_get_option_default( $key, $default = null ) {
	$options = maili_get_options_defaults();
	return isset( $options[ $key ] ) ? $options[ $key ] : $default;
}


/**
 * Gets default options.
 *
 * @since 1.1.0
 *
 * @return array
 */
function maili_get_options_defaults() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache = [
		'singles' => [ 'post' ],
		'limit'   => 0,
		'links'   => [],
	];

	return $cache;
}