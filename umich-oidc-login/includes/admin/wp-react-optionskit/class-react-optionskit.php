<?php
/**
 * WP React OptionsKit.
 *
 * Based on WP React OptionKit version 1.1.2 as of February 22, 2023.
 * Copyright (c) 2018 Alessandro Tesoro
 * https://github.com/WPUserManager/wp-optionskit
 *
 * WP OptionsKit. is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP OptionsKit. is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * @author     Alessandro Tesoro, Regents of the University of Michigan
 * @version    0.8.0
 * @copyright  (c) 2018 Alessandro Tesoro
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 * @package    wp-optionskit
 */

namespace UMich_OIDC_Login\Admin\WP_React_OptionsKit;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Automatically create WordPress settings pages using React.
 *
 * @package    UMich_OIDC_Login\Admin\WP_React_OptionsKit
 */
class React_OptionsKit {
	/**
	 * Version of the class. This is the WP React OptionsKit version.
	 * WP React OptionsKit version 0.8.0 was based on WP OptionsKit
	 * version 1.1.2.
	 *
	 * @var string
	 */
	public $version = '0.8.0';

	/**
	 * The slug of the options panel.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * The slug for the function names of this panel.
	 *
	 * @var string
	 */
	public $func;

	/**
	 * The title of the page.
	 *
	 * @var string
	 */
	private $page_title;

	/**
	 * Logo to be displayed near the title.
	 *
	 * @var string
	 */
	private $image;

	/**
	 * Actions links for the options panel header.
	 *
	 * @var array
	 */
	private $action_buttons = array();

	/**
	 * Notices to display.
	 *
	 * @var array
	 */
	public $notices = array();

	/**
	 * Holds the settings for this panel.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Get things started.
	 *
	 * @param boolean $slug Unique identifier for this options page.
	 */
	public function __construct( $slug = false ) {

		if ( ! $slug ) {
			return;
		}

		$this->slug = $slug;
		$this->func = str_replace( '-', '_', $slug );

		$GLOBALS[ $this->func . '_options' ] = get_option( $this->func . '_settings', true );

		$this->hooks();
	}

	/**
	 * Set the title for the page.
	 *
	 * @param string $page_title Title of the page.
	 * @return void
	 */
	public function set_page_title( $page_title = '' ) {
		$this->page_title = $page_title;
	}

	/**
	 * Add action button to the header.
	 *
	 * @param array $args Arguments for the action button.
	 * @return void
	 */
	public function add_action_button( $args ) {

		$defaults = array(
			'title' => '',
			'url'   => '',
		);

		$this->action_buttons[] = wp_parse_args( $args, $defaults );
	}

	/**
	 * Set an image for the options panel title.
	 *
	 * @param string $url URL of the image.
	 * @return void
	 */
	public function add_image( $url ) {
		$this->image = esc_url( $url );
	}

	/**
	 * Add a notice to the settings page.
	 *
	 * @param string $id Unique ID for the notice. May be used in an HTML attribute.
	 * @param string $status Type of notice to display.  One of "info", "warning", "success", "error".
	 * @param string $content HTML text to display in the notice.
	 * @return void
	 */
	public function add_notice( $id, $status, $content ) {
		$new_notices = array();
		foreach ( $this->notices as $n ) {
			if ( $n['id'] !== $id ) {
				$new_notices[] = $n;
			}
		}
		$new_notices[] = array(
			'id'      => $id,
			'status'  => $status,
			'content' => $content,
		);
		$this->notices = $new_notices;
	}

	/**
	 * Hook into WordPress and run things.
	 *
	 * @return void
	 */
	private function hooks() {

		add_action( 'admin_menu', array( $this, 'add_settings_page' ), apply_filters( $this->func . '_admin_menu_priority', 10 ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 100 );
		add_action( 'rest_api_init', array( $this, 'register_rest_controller' ) );
	}

	/**
	 * Register the WP API controller for the options panel.
	 *
	 * @return void
	 */
	public function register_rest_controller() {
		require_once plugin_dir_path( __FILE__ ) . '/class-wprok-rest-server.php';
		$this->settings = $this->get_registered_settings();
		$controller     = new \UMich_OIDC_Login\Admin\WP_React_OptionsKit\WPROK_Rest_Server( $this );
		$controller->register_routes();
	}

	/**
	 * Return the rest url for the options panel.
	 *
	 * @return string
	 */
	private function get_rest_url() {
		return get_rest_url( null, '/wprok/' . $this->func . '/v1/' );
	}

	/**
	 * Retrieve labels.
	 *
	 * @return array
	 */
	private function get_labels() {

		$defaults = array(
			'save'         => 'Save Changes',
			'success'      => 'Settings successfully saved.',
			'upload'       => 'Select file',
			'upload-title' => 'Insert file',
			'error'        => 'The changes were not saved. Please check the following fields for more info:',
		);

		return apply_filters( $this->func . '_labels', $defaults );
	}

	/**
	 * Add settings page to the WordPress menu.
	 *
	 * @return void
	 */
	public function add_settings_page() {

		$menu = apply_filters(
			$this->func . '_menu',
			array(
				'parent'     => 'options-general.php',
				'page_title' => 'Settings Panel',
				'menu_title' => 'Settings Panel',
				'capability' => 'manage_options',
			)
		);

		$page = add_submenu_page(
			$menu['parent'],
			$menu['page_title'],
			$menu['menu_title'],
			$menu['capability'],
			$this->slug . '-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Determine whether we're on an options page generated by WPOK.
	 *
	 * @return boolean
	 */
	private function is_options_page() {

		$is_page = false;
		$screen  = get_current_screen();
		$check   = $this->slug . '-settings';

		if ( preg_match( "/{$check}/", $screen->base ) ) {
			$is_page = true;
		}

		return $is_page;
	}

	/**
	 * Add a new class to the body tag.
	 * The class will be used to adjust the layout.
	 *
	 * @param string $classes HTML classes to add.
	 *
	 * @return string
	 */
	public function admin_body_class( $classes ) {
		$screen = get_current_screen();
		$check  = $this->slug . '-settings';

		if ( preg_match( "/{$check}/", $screen->base ) ) {
			$classes .= ' optionskit-panel-page';
		}

		return $classes;
	}

	/**
	 * Load require styles and scripts for the options panel.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		$path = plugin_dir_url( __FILE__ );

		if ( $this->is_options_page() ) {

			$asset_file = include plugin_dir_path( __FILE__ ) . '/build/index.asset.php';
			foreach ( $asset_file['dependencies'] as $style ) {
				wp_enqueue_style( $style );
			}
			wp_register_script(
				$this->func . '_opk',
				$path . '/build/index.js',
				$asset_file['dependencies'],
				$asset_file['version'],
				true,
			);
			wp_enqueue_script( $this->func . '_opk' );
			wp_register_style(
				$this->func . '_opk',
				$path . '/build/index.css',
				null,
				$asset_file['version'],
			);
			wp_enqueue_style( $this->func . '_opk' );

			$options_panel_settings = array(
				'rest_url'    => esc_url( $this->get_rest_url() ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'verifynonce' => wp_create_nonce( 'wprok_verifynonce' ),
				'page_title'  => esc_html( $this->page_title ),
				'logo'        => $this->image,
				'buttons'     => $this->action_buttons,
				'labels'      => $this->get_labels(),
				'notices'     => apply_filters( $this->func . '_notices', $this->notices ),
				'tabs'        => $this->get_settings_tabs(),
				'sections'    => $this->get_registered_settings_sections(),
				'settings'    => $this->get_registered_settings(),
				'options'     => $this->get_options(),
			);
			wp_enqueue_media();
			wp_add_inline_script( $this->func . '_opk', 'window.optionsKitSettings = ' . wp_json_encode( $options_panel_settings ), 'before' );
		}
	}

	/**
	 * Retrieve the default tab.
	 * The default tab, will be the first available tab.
	 *
	 * @return string
	 */
	private function get_default_tab() {

		$default = '';
		$tabs    = $this->get_settings_tabs();

		if ( is_array( $tabs ) ) {
			$default = key( $tabs );
		}

		return $default;
	}

	/**
	 * Retrieve the settings tabs.
	 *
	 * @return array
	 */
	private function get_settings_tabs() {
		return apply_filters( $this->func . '_settings_tabs', array() );
	}

	/**
	 * Retrieve sections for the currently selected tab.
	 *
	 * @param mixed $tab Tab to get settings for.
	 * @return mixed
	 */
	private function get_settings_tab_sections( $tab = false ) {

		$tabs     = false;
		$sections = $this->get_registered_settings_sections();

		if ( $tab && ! empty( $sections[ $tab ] ) ) {
			$tabs = $sections[ $tab ];
		} elseif ( $tab ) {
			$tabs = false;
		}

		return $tabs;
	}

	/**
	 * Retrieve the registered sections.
	 *
	 * @return array
	 */
	private function get_registered_settings_sections() {

		$sections = apply_filters( $this->func . '_registered_settings_sections', array() );

		return $sections;
	}

	/**
	 * Retrieve the settings for this options panel.
	 *
	 * @return array
	 */
	public function get_registered_settings() {
		return apply_filters( $this->func . '_registered_settings', array() );
	}

	/**
	 * Get a specific option of this panel from the database.
	 *
	 * @param string  $key           Name of option to get.
	 * @param boolean $default_value Value to return if option is not found.
	 * @return mixed
	 */
	private function get_option( $key = '', $default_value = false ) {
		$option_key = $this->func . '_options';
		$options    = $GLOBALS[ $option_key ];

		$value = ! empty( $options[ $key ] ) ? $options[ $key ] : $default_value;
		$value = apply_filters( $this->func . '_get_option', $value, $key, $default_value );

		return apply_filters( $this->func . '_get_option_' . $key, $value, $key, $default_value );
	}

	/**
	 * Retrieve stored options from WordPress and populate the model into React.
	 *
	 * @return array
	 */
	private function get_options() {

		$settings = array();

		// First retrieve all the registered settings.
		$registered_settings = $this->get_registered_settings();

		// Loop through each available setting, and setup the setting into the array.
		foreach ( $registered_settings as $setting_section ) {
			foreach ( $setting_section as $setting ) {
				if ( 'html' !== $setting['type'] ) {
					// Don't include HTML settings since they don't have values.
					$default                    = isset( $setting['std'] ) ? $setting['std'] : '';
					$settings[ $setting['id'] ] = $this->get_option( $setting['id'], $default );
				}
			}
		}

		// Check if the option for this panel exists into the database.
		// If not, create an empty one.
		// If option exists, merge with available settings.
		if ( ! get_option( $this->func . '_settings' ) ) {
			update_option( $this->func . '_settings', $settings );
		} else {
			$settings = array_merge( $settings, get_option( $this->func . '_settings' ) );
		}

		return apply_filters( $this->func . '_get_settings', $settings );
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		?>
<noscript>You need to enable JavaScript to view this page.</noscript>
<div id="optionskit-screen"></div>
		<?php
	}
}
