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

/**
 * Register WP API Rest controller to extend the WordPress API and store options from optionskit.
 */
class WPROK_Rest_Server extends \WP_REST_Controller {

	/**
	 * OptionsKit panel object.
	 *
	 * @var object
	 */
	protected $panel;

	/**
	 * OptionsKit Namespace
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * OptionsKit API Version
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Store errors here.
	 *
	 * @var object
	 */
	protected $errors;

	/**
	 * Get controller started.
	 *
	 * @param object $panel OptionsKit panel object.
	 */
	public function __construct( $panel ) {

		$this->version   = 'v1';
		$this->panel     = $panel;
		$this->namespace = 'wprok/' . $this->panel->func . '/' . $this->version;

		// Create a new instance of WP_Error.
		$this->errors = new \WP_Error();

		add_filter( $this->panel->func . '_settings_sanitize_text', array( $this, 'sanitize_text_field' ), 3, 10 );
		add_filter( $this->panel->func . '_settings_sanitize_textarea', array( $this, 'sanitize_textarea_field' ), 3, 10 );
		add_filter( $this->panel->func . '_settings_sanitize_radio', array( $this, 'sanitize_text_field' ), 3, 10 );
		add_filter( $this->panel->func . '_settings_sanitize_select', array( $this, 'sanitize_text_field' ), 3, 10 );
		add_filter( $this->panel->func . '_settings_sanitize_checkbox', array( $this, 'sanitize_checkbox_field' ), 3, 10 );
		add_filter( $this->panel->func . '_settings_sanitize_multiselect', array( $this, 'sanitize_multiple_field' ), 3, 10 );
		add_filter( $this->panel->func . '_settings_sanitize_multicheckbox', array( $this, 'sanitize_multiple_field' ), 3, 10 );
		add_filter( $this->panel->func . '_settings_sanitize_file', array( $this, 'sanitize_file_field' ), 3, 10 );
	}

	/**
	 * Register new routes for the options kit panel.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/records',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save_options' ),
					'permission_callback' => array( $this, 'get_options_permission' ),
				),
			)
		);
	}

	/**
	 * Detect if the user can submit options.
	 *
	 * @return bool|\WP_Error
	 */
	public function get_options_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'rest_forbidden', 'WPOK: Permission Denied.', array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Sanitize the text field.
	 *
	 * @param string $input   The input to sanitize.
	 * @param object $errors  WP_Error object for errors that are found.
	 * @param array  $setting The wp-react-optionskit setting array for this field.
	 * @return string
	 */
	public function sanitize_text_field( $input, $errors, $setting ) {
		return trim( wp_strip_all_tags( $input, true ) );
	}

	/**
	 * Sanitize textarea field.
	 *
	 * @param string $input   The input to sanitize.
	 * @param object $errors  WP_Error object for errors that are found.
	 * @param array  $setting The wp-react-optionskit setting array for this field.
	 * @return string
	 */
	public function sanitize_textarea_field( $input, $errors, $setting ) {
		return stripslashes( wp_kses_post( $input ) );
	}

	/**
	 * Sanitize multiselect and multicheck field.
	 *
	 * @param string $input   The input to sanitize.
	 * @param object $errors  WP_Error object for errors that are found.
	 * @param array  $setting The wp-react-optionskit setting array for this field.
	 * @return array
	 */
	public function sanitize_multiple_field( $input, $errors, $setting ) {

		$new_input = array();

		if ( is_array( $input ) && ! empty( $input ) ) {
			foreach ( $input as $key => $value ) {
				$new_input[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		if ( ! empty( $input ) && ! is_array( $input ) ) {
			$input = explode( ',', $input );
			foreach ( $input as $key => $value ) {
				$new_input[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		return $new_input;
	}

	/**
	 * Sanitize urls for the file field.
	 *
	 * @param string $input   The input to sanitize.
	 * @param object $errors  WP_Error object for errors that are found.
	 * @param array  $setting The wp-react-optionskit setting array for this field.
	 * @return string
	 */
	public function sanitize_file_field( $input, $errors, $setting ) {
		return esc_url( $input );
	}

	/**
	 * Sanitize the checkbox field.
	 *
	 * @param string $input   The input to sanitize.
	 * @param object $errors  WP_Error object for errors that are found.
	 * @param array  $setting The wp-react-optionskit setting array for this field.
	 * @return bool
	 */
	public function sanitize_checkbox_field( $input, $errors, $setting ) {

		$pass = false;

		if ( 'true' === $input ) {
			$pass = true;
		}

		return $pass;
	}

	/**
	 * Save options to the database. Sanitize them first.
	 *
	 * @param \WP_REST_Request $request The REST request containing options to save.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_options( \WP_REST_Request $request ) {

		if ( ! wp_verify_nonce( $request['verifynonce'], 'wprok_verifynonce' ) ) {
			return false;
		}

		$registered_settings = $this->panel->settings;
		$settings_received   = $request->get_params();
		$data_to_save        = array();

		if ( is_array( $registered_settings ) && ! empty( $registered_settings ) ) {
			foreach ( $registered_settings as $setting_section ) {
				foreach ( $setting_section as $setting ) {
					// Skip if no setting type.
					if ( ! $setting['type'] ) {
						continue;
					}

					// Skip if the ID doesn't exist in the data received.
					if ( ! array_key_exists( $setting['id'], $settings_received ) ) {
						continue;
					}

					// Sanitize the input.
					$setting_type = $setting['type'];
					$output       = apply_filters( $this->panel->func . '_settings_sanitize_' . $setting_type, $settings_received[ $setting['id'] ], $this->errors, $setting );
					$output       = apply_filters( $this->panel->func . '_settings_sanitize_' . $setting['id'], $output, $this->errors, $setting );

					if ( 'checkbox' === $setting_type && false === $output ) {
						continue;
					}

					// Add the option to the list of ones that we need to save.
					if ( ! empty( $output ) && ! is_wp_error( $output ) ) {
						$data_to_save[ $setting['id'] ] = $output;
					}
				}
			}
		}

		if ( ! empty( $this->errors->get_error_codes() ) ) {
			return new \WP_REST_Response( $this->errors, 422 );
		}

		update_option( $this->panel->func . '_settings', apply_filters( $this->panel->func . '_save_options', $data_to_save ) );

		$response = array(
			'options'  => $data_to_save,
			'notices'  => apply_filters( $this->panel->func . '_notices', $this->panel->notices ),
			'settings' => $this->panel->get_registered_settings(),  // recalculate with newly saved options, $this->panel->settings contains the old settings.
		);
		return rest_ensure_response( $response );
	}
}
