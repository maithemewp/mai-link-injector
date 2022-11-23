<?php

/**
 * Plugin Name:     Mai Link Injector
 * Plugin URI:      https://bizbudding.com/
 * Description:     A programmatic plugin to automatically link keywords to any url.
 * Version:         1.0.1
 *
 * Author:          BizBudding
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Mai_Link_Injector_Plugin Class.
 *
 * @since 0.1.0
 */
final class Mai_Link_Injector_Plugin {

	/**
	 * @var   Mai_Link_Injector_Plugin The one true Mai_Link_Injector_Plugin
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main Mai_Link_Injector_Plugin Instance.
	 *
	 * Insures that only one instance of Mai_Link_Injector_Plugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    Mai_Link_Injector_Plugin::setup_constants() Setup the constants needed.
	 * @uses    Mai_Link_Injector_Plugin::includes() Include the required files.
	 * @uses    Mai_Link_Injector_Plugin::hooks() Activate, deactivate, etc.
	 * @see     Mai_Link_Injector_Plugin()
	 * @return  object | Mai_Link_Injector_Plugin The one true Mai_Link_Injector_Plugin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup.
			self::$instance = new Mai_Link_Injector_Plugin;
			// Methods.
			self::$instance->includes();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-link-injector' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-link-injector' ), '1.0' );
	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function includes() {
		include __DIR__ . '/classes/class-link-injector.php';
	}

	/**
	 * Run the hooks.
	 *
	 * @since   0.1.0
	 * @return  void
	 */
	public function hooks() {
		add_action( 'plugins_loaded', [ $this, 'updater' ] );
	}

	/**
	 * Setup the updater.
	 *
	 * @since 0.1.0
	 *
	 * @uses https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return void
	 */
	public function updater() {
		// Bail if current user cannot manage plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Bail if plugin updater is not loaded.
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			return;
		}

		// Setup the updater.
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/maithemewp/mai-link-injector', __FILE__, 'mai-link-injector' );

		// Set the stable branch.
		$updater->setBranch( 'main' );

		// Maybe set github api token.
		if ( defined( 'MAI_GITHUB_API_TOKEN' ) ) {
			$updater->setAuthentication( MAI_GITHUB_API_TOKEN );
		}

		// Add icons for Dashboard > Updates screen.
		if ( function_exists( 'mai_get_updater_icons' ) && $icons = mai_get_updater_icons() ) {
			$updater->addResultFilter(
				function ( $info ) use ( $icons ) {
					$info->icons = $icons;
					return $info;
				}
			);
		}
	}
}

/**
 * The main function for that returns Mai_Link_Injector_Plugin
 *
 * The main function responsible for returning the one true Mai_Link_Injector_Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = Mai_Link_Injector_Plugin(); ?>
 *
 * @since 0.1.0
 *
 * @return object|Mai_Link_Injector_Plugin The one true Mai_Link_Injector_Plugin Instance.
 */
function mai_link_injector_plugin() {
	return Mai_Link_Injector_Plugin::instance();
}

// Get Mai_Link_Injector_Plugin Running.
mai_link_injector_plugin();
