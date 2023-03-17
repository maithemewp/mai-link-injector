<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class Mai_Link_Injector_Settings {

	/**
	 * Mai_Link_Injector_Settings constructor.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function __construct() {
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
		add_action( 'acf/init',                         [ $this, 'register' ] );
		add_action( 'acf/render_field/key=maili_limit', [ $this, 'admin_css' ] );
		add_filter( 'acf/load_field/key=maili_singles', [ $this, 'load_singles' ] );
		add_filter( 'acf/load_field/key=maili_limit',   [ $this, 'load_limit' ] );
		add_filter( 'acf/load_field/key=maili_links',   [ $this, 'load_links' ] );
		add_action( 'acf/save_post',                    [ $this, 'save' ], 99 );
		add_filter( 'plugin_action_links_mai-link-injector/mai-link-injector.php', [ $this, 'add_settings_link' ], 10, 4 );
	}

	/**
	 * Registers options page and field groups from settings and custom block.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	function register() {
		acf_add_options_sub_page(
			[
				'menu_title' => class_exists( 'Mai_Engine' ) ? __( 'Link Injector', 'mai-link-injector' ) : __( 'Mai Link Injector', 'mai-link-injector' ),
				'page_title' => __( 'Mai Link Injector', 'mai-link-injector' ),
				'parent'     => class_exists( 'Mai_Engine' ) ? 'mai-theme' : 'options-general.php',
				'menu_slug'  => 'mai-link-injector',
				'capability' => 'manage_options',
				'position'   => 4,
			]
		);

		acf_add_local_field_group(
			[
				'key'    => 'maili_options',
				'title'  => __( 'Mai Link Injector', 'mai-link-injector' ),
				'style'  => 'seamless',
				'fields' => [
					[
						'key'      => 'maili_message',
						'type'     => 'message',
						'message'  => '',
						'esc_html' => 0,
					],
					[
						'label'         => __( 'Post Types', 'mai-link-injector' ),
						'instructions'  => __( 'The post types to inject links on.', 'mai-link-injector' ),
						'key'           => 'maili_singles',
						'name'          => 'singles',
						'type'          => 'select',
						'choices'       => [],
						'default_value' => maili_get_option_default( 'singles' ),
						'multiple'      => 1,
						'allow_null'    => 1,
						'ui'            => 1,
						'ajax'          => 1,
					],
					[
						'label'         => __( 'Limit', 'mai-link-injector' ),
						'instructions'  => __( 'Optionally limit the amount of instances specific keywords gets linked per-page. Use 0 for no limit.', 'mai-link-injector' ),
						'key'           => 'maili_limit',
						'name'          => 'limit',
						'type'          => 'number',
						'default_value' => maili_get_option_default( 'limit' ),
					],
					[
						'label'         => __( 'Links', 'mai-link-injector' ),
						'instructions'  => __( 'Add text and link pairs for auto-linking. If there are multiple matches, the first one added here will be used. Case-insensitive', 'mai-link-injector' ),
						'key'           => 'maili_links',
						'name'          => 'links',
						'type'          => 'repeater',
						'collapsed'     => 'maili_text',
						'min'           => 0,
						'max'           => 0,
						'layout'        => 'table',
						'pagination'    => 1,
						'rows_per_page' => 25,
						'button_label'  => __( 'Add New Link', 'mai-link-injector' ),
						'sub_fields'    => [
							[
								'label'    => __( 'Text', 'mai-link-injector' ),
								'key'      => 'maili_text',
								'name'     => 'text',
								'type'     => 'text',
								'required' => 1,
								'wrapper'  => [
									'width' => '33',
								],
							],
							[
								'label'    => __( 'URL', 'mai-link-injector' ),
								'key'      => 'maili_url',
								'name'     => 'url',
								'type'     => 'url',
								'required' => 1,
								'wrapper'  => [
									'width' => '66',
								],
							],
						],
					],
				],
				'location' => [
					[
						[
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => 'mai-link-injector',
						],
					],
				],
			]
		);
	}

	/**
	 * Gets inline admin CSS.
	 *
	 * @since 1.1.0
	 *
	 * @param array $field
	 *
	 * @return void
	 */
	function admin_css( $field ) {
		?>
		<style>
		.acf-field-number input[type="number"] {
			max-width: 100px;
		}

		.acf-repeater .acf-url input[type="url"] {
			display: inline-flex;
			justify-content: flex-end;
		}

		.acf-repeater .acf-actions {
			text-align: start;
		}

		.acf-repeater .acf-actions .acf-button {
			float: none !important;
		}
		</style>
		<?php
	}

	/**
	 * Loads post types.
	 *
	 * @since 1.1.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array
	 */
	function load_singles( $field ) {
		$field['value'] = maili_get_option( 'singles' );
		$post_types     = get_post_types( [ 'public' => true ], 'objects' );

		foreach ( $post_types as $post_type ) {
			$field['choices'][ $post_type->name ] = $post_type->label;
		}

		return $field;
	}

	/**
	 * Loads limit field value from our custom option.
	 *
	 * @since 1.1.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array
	 */
	function load_limit( $field ) {
		$field['value'] = maili_get_option( 'limit' );
		return $field;
	}

	/**
	 * Loads links repeater field values from our custom option.
	 *
	 * @since 1.1.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array
	 */
	function load_links( $field ) {
		$field['value'] = [];
		$links          = maili_get_option( 'links' );

		if ( ! $links ) {
			return $field;
		}

		foreach ( $links as $text => $url ) {
			if ( ! ( $text && $url ) ) {
				continue;
			}

			$field['value'][] = [
				'maili_text' => $text,
				'maili_url'  => $url,
			];
		}

		return $field;
	}

	/**
	 * Updates and deletes options when saving the settings page.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed $post_id The post ID from ACF.
	 *
	 * @return void
	 */
	function save( $post_id ) {
		// Bail if no data.
		if ( ! isset( $_POST['acf'] ) || empty( $_POST['acf'] ) ) {
			return;
		}

		// Bail if not saving an options page.
		if ( 'options' !== $post_id ) {
			return;
		}

		// Current screen.
		$screen = get_current_screen();

		// Bail if not our options page.
		if ( ! $screen || false === strpos( $screen->id, 'mai-link-injector' ) ) {
			return;
		}

		// Set data var.
		$data  = [
			'singles' => (array) get_field( 'maili_singles', 'option' ),
			'limit'   => (int) get_field( 'maili_limit', 'option' ),
			'links'   => [],
		];

		// Get links.
		$links = (array) get_field( 'maili_links', 'option' );

		// Format links.
		foreach ( $links as $values ) {
			$text = isset( $values['text'] ) ? esc_html( $values['text'] ) : '';
			$url  = isset( $values['url'] ) ? esc_url( $values['url'] ) : '';

			if ( ! ( $text && $url ) ) {
				continue;
			}

			// Add to data to save.
			$data['links'][ $text ] = $url;
		}

		// Save new data to our field key.
		update_option( 'mai_link_injector', $data );

		// Clear repeater field.
		update_field( 'maili_links', null, $post_id );

		// To delete.
		$options = [
			'options_maili_singles',
			'options_maili_limit',
			'options_maili_links',
			'_options_maili_singles',
			'_options_maili_limit',
			'_options_maili_links',
		];

		// Delete remaining options manually.
		foreach ( $options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Return the plugin action links.  This will only be called if the plugin is active.
	 *
	 * @since 1.1.0
	 *
	 * @param array  $actions     Associative array of action names to anchor tags
	 * @param string $plugin_file Plugin file name, ie my-plugin/my-plugin.php
	 * @param array  $plugin_data Associative array of plugin data from the plugin file headers
	 * @param string $context     Plugin status context, ie 'all', 'active', 'inactive', 'recently_active'
	 *
	 * @return array associative array of plugin action links
	 */
	function add_settings_link( $actions, $plugin_file, $plugin_data, $context ) {
		if ( ! class_exists( 'acf_pro' ) ) {
			return $actions;
		}

		$actions['settings'] = $this->get_settings_link( __( 'Settings', 'mai-link-injector' ) );

		return $actions;
	}

	/**
	 * Gets settings link.
	 *
	 * @since 1.1.0
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	function get_settings_link( $text ) {
		$url  = esc_url( admin_url( sprintf( '%s.php?page=mai-link-injector', class_exists( 'Mai_Engine' ) ? 'admin' : 'options-general' ) ) );
		$link = sprintf( '<a href="%s">%s</a>', $url, $text );

		return $link;
	}
}