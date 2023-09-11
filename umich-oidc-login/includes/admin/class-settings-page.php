<?php
/**
 * Admin dashboard settings page.
 *
 * @package    UMich_OIDC_Login\Admin
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

namespace UMich_OIDC_Login\Admin;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function UMich_OIDC_Login\Core\log_message;

/**
 * Admin dashboard settings page
 *
 * @package    UMich_OIDC_Login\Admin
 */
class Settings_Page {

	/**
	 * Context for this WordPress request / this run of the plugin.
	 *
	 * @var      object    $ctx    Context passed to us by our creator.
	 */
	private $ctx;

	/**
	 * Holds the options panel controller.
	 *
	 * @var object $panel
	 */
	protected $panel;

	/**
	 * Settings page object.
	 *
	 * @param object $ctx Context for this WordPress request / this run of the plugin.
	 */
	public function __construct( $ctx ) {

		$this->ctx = $ctx;

		$this->panel = new \UMich_OIDC_Login\Admin\WP_React_OptionsKit\React_OptionsKit( 'umich_oidc' );
		$this->panel->set_page_title( 'UMich OIDC Login Settings' );

		// Setup the options panel menu.
		\add_filter( 'umich_oidc_menu', array( $this, 'setup_menu' ) );
		\add_filter( 'umich_oidc_notices', array( $this, 'notices' ) );
		\add_filter( 'umich_oidc_labels', array( $this, 'labels' ) );
		\add_filter( 'umich_oidc_save_options', array( $this, 'save_options' ) );

		// Register settings tabs.
		\add_filter( 'umich_oidc_settings_tabs', array( $this, 'register_settings_tabs' ) );
		\add_filter( 'umich_oidc_registered_settings_sections', array( $this, 'register_settings_subsections' ) );

		// Register settings fields for the options panel.
		\add_filter( 'umich_oidc_registered_settings', array( $this, 'register_settings' ) );

		\add_filter( 'umich_oidc_settings_sanitize_provider_url', array( $this, 'sanitize_provider_url' ), 3, 10 );
		\add_filter( 'umich_oidc_settings_sanitize_scopes', array( $this, 'sanitize_scopes' ), 3, 10 );
		\add_filter( 'umich_oidc_settings_sanitize_login_return_url', array( $this, 'sanitize_url' ), 3, 10 );
		\add_filter( 'umich_oidc_settings_sanitize_logout_return_url', array( $this, 'sanitize_url' ), 3, 10 );
		\add_filter( 'umich_oidc_settings_sanitize_available_groups', array( $this, 'sanitize_available_groups' ), 3, 10 );
		\add_filter( 'umich_oidc_settings_sanitize_restrict_site', array( $this, 'sanitize_group_choices' ), 3, 10 );

		\wp_enqueue_script(
			'umich-oidc-settings',
			UMICH_OIDC_LOGIN_DIR_URL . '/assets/js/settings.js',
			array(),
			UMICH_OIDC_LOGIN_VERSION_INT,
			true
		);
	}

	/**
	 * Setup the menu for the options panel.
	 *
	 * @param array $menu original settings of the menu.
	 *
	 * @return array
	 */
	public function setup_menu( $menu ) {
		return array(
			'parent'     => 'options-general.php',
			'page_title' => 'UMich OIDC Login',
			'menu_title' => 'UMich OIDC Login',
			'capability' => 'manage_options',
		);
	}

	/**
	 * Setup notices for the options panel.
	 *
	 * @param array $notices original notices for the options panel.
	 *
	 * @return array
	 */
	public function notices( $notices ) {
		$notices = array();  // start over.

		$options          = $this->ctx->options;
		$missing_options  = '';
		$separator        = ' ';
		$required_options = array(
			'provider_url'  => 'Identity Provider URL',
			'client_id'     => 'Client ID',
			'client_secret' => 'Client Secret',
		);
		foreach ( \array_keys( $required_options ) as $opt ) {
			if ( ! \array_key_exists( $opt, $options ) || '' === $options[ $opt ] ) {
				$opt_name         = $required_options[ $opt ];
				$missing_options .= "{$separator}<a href='#oidc/{$opt}'>{$opt_name}</a>";
				$separator        = ', ';
			}
		}

		if ( '' !== $missing_options ) {
			$notices[] = array(
				'id'      => 'missing-options',
				'status'  => 'warning',
				'content' => "UMich OIDC Login requires the following options to be set before users will be able to log in via OIDC: {$missing_options}",
			);
		}

		if ( ! \class_exists( 'Pantheon_Sessions' ) ) {
			$notices[] = array(
				'id'      => 'pantheon-plugin',
				'status'  => 'warning',
				'content' => 'UMich OIDC Login strongly recommends using the <a href="https://wordpress.org/plugins/wp-native-php-sessions/" target="_blank">WordPress Native PHP Sessions</a> plugin to prevent conflicts with other WordPress plugins that also use PHP sessions, and to ensure correct operation when the site has multiple web servers.',
			);
		}
		return $notices;
	}

	/**
	 * Setup labels for the options panel.
	 *
	 * @param array $labels default labels for the options panel.
	 *
	 * @return array
	 */
	public function labels( $labels ) {

		$labels['success'] = 'Settings successfully saved.  Clear any WordPress and web hosting caches to ensure new access restrictions fully take effect.';
		return $labels;
	}

	/**
	 * Modify options before saving changes.
	 *
	 * @param array $options new options to save.
	 *
	 * @return array
	 */
	public function save_options( $options ) {
		$this->ctx->options = $options; // save the new data so the notices filter can use them.
		return $options;
	}

	/**
	 * Register settings tabs for the options panel.
	 *
	 * @param array $tabs Default tabs.
	 *
	 * @return array
	 */
	public function register_settings_tabs( $tabs ) {
		$tabs = array(
			'general'    => 'General',
			'oidc'       => 'OIDC',
			'shortcodes' => 'Shortcodes',
		);
		return $tabs;
	}

	/**
	 * Register subsections for the option tabs.
	 *
	 * @param array $sections Default sections.
	 *
	 * @return array
	 */
	public function register_settings_subsections( $sections ) {
		return $sections;
	}

	/**
	 * Return list of groups for restricting access to a post.
	 *
	 * @param int $id ID of the post to return the access list for.
	 *
	 * @return array
	 */
	public function post_access_groups( $id ) {

		$access = \get_post_meta( $id, '_umich_oidc_access', true );
		log_message( "access list for post {$id}: \"{$access}\"" );

		// If $access is empty, explode() will return an array with
		// one element that is an empty string.
		if ( '' === $access ) {
			return array();
		}
		$access = \array_map( '\trim', \explode( ',', $access ) );

		return $access;
	}

	/**
	 * Return list of available groups for use in a multiselect field.
	 *
	 * @return array
	 */
	public function available_groups() {

		$groups = array(
			array(
				'value' => '_everyone_',
				'label' => '( Everyone )',
			),
			array(
				'value' => '_logged_in_',
				'label' => '( Logged-in Users )',
			),
		);

		$options = $this->ctx->options;
		if ( ! \array_key_exists( 'available_groups', $options ) || ! \is_string( $options['available_groups'] ) ) {
			return $groups;
		}
		$available = \array_map( '\trim', \explode( ',', $options['available_groups'] ) );
		foreach ( $available as $a ) {
			if ( '' !== $a ) {
				$groups[] = array(
					'value' => $a,
					'label' => $a,
				);
			}
		}

		return $groups;
	}

	/**
	 * Register settings for the plugin.
	 *
	 * @param array $settings Default settings.
	 *
	 * @return array
	 */
	public function register_settings( $settings ) {

		$option_defaults = $this->ctx->option_defaults;

		// Use require rather than require_once to ensure new values are calculated each time.
		require 'settings-tab-general.php';    // sets $settings_tab_general.
		require 'settings-tab-oidc.php';       // sets $settings_tab_oidc.
		require 'settings-tab-shortcodes.php'; // sets $settings_tab_shortcodes.

		$settings = array(
			'general'    => $settings_tab_general,
			'oidc'       => $settings_tab_oidc,
			'shortcodes' => $settings_tab_shortcodes,
		);

		return $settings;
	}


	/**
	 * Sanitize the IdP URL
	 *
	 * @param string $input URL to sanitize.
	 * @param object $errors WP_Error object for errors that are found.
	 * @param array  $setting The wp-optionskit setting array for the URL field.
	 * @return string
	 */
	public function sanitize_provider_url( $input, $errors, $setting ) {

		if ( ! \is_string( $input ) ) {
			$errors->add( 'provider_url', 'Internal error (not a string)' );
			return '';
		}

		$input = \trim( $input );
		if ( '' === $input ) {
			// Allow people to save other settings before saving
			// the provider URL.
			return '';
		}
		$url = \esc_url_raw( $input, array( 'https' ) );
		if ( '' === $url ) {
			$errors->add( 'provider_url', 'Must be a URL starting with https://' );
			return '';
		}
		$url     = \rtrim( $url, '/' );
		$request = \wp_safe_remote_get( $url . '/.well-known/openid-configuration' );
		if ( \is_wp_error( $request ) ) {
			$msg = 'Does not appear to be an OpenID Identity Provider: unable to retrieve ' . \esc_url( $url ) . '/.well-known/openid-configuration';
			$errors->add( 'provider_url', $msg );
			log_message( $msg );
			log_message( $request );
			return '';
		}
		$body = \wp_remote_retrieve_body( $request );
		$json = \json_decode( $body );
		if ( JSON_ERROR_NONE !== \json_last_error() ) {
			$errors->add( 'provider_url', 'Does not appear to be an OpenID Identity Provider: ' . \esc_url( $url ) . '/.well-known/openid-configuration is not a valid JSON document.' );
			return '';
		}
		return $url;
	}

	/**
	 * Sanitize the list of scopes
	 *
	 * @param string $input Space separated list of scopes to sanitize.
	 * @param object $errors WP_Error object for errors that are found.
	 * @param array  $setting The wp-optionskit setting array for the scopes field.
	 * @return string
	 */
	public function sanitize_scopes( $input, $errors, $setting ) {

		if ( ! \is_string( $input ) ) {
			$errors->add( 'scopes', 'Internal error (not a string)' );
			return '';
		}

		$input  = \trim( $input );
		$scopes = \explode( ' ', $input );

		// The openid scope needs to be present
		// https://openid.net/specs/openid-connect-core-1_0.html#AuthRequest .
		if ( ! \in_array( 'openid', $scopes, true ) ) {
			$errors->add( 'scopes', 'The scope "openid" is required.' );
			return '';
		}

		// Legal scopes match
		// 1*( %x21 / %x23-5B / %x5D-7E )
		// which is any printable ASCII character except spaces,
		// double quote, or backslash.
		// https://www.rfc-editor.org/rfc/rfc6749.html#section-3.3 .
		foreach ( $scopes as $s ) {
			if ( 1 !== \preg_match( '/^[\x21\x23-\x5b\x5d-\x7e]+$/', $s ) ) {
				$errors->add( 'scopes', 'Bad character in scope name: \'' . $s . '\'' );
				return '';

			}
		}

		return $input;
	}

	/**
	 * Sanitize the list of available groups.
	 *
	 * @param string $input comma-separated list of groups to sanitize.
	 * @param object $errors WP_Errror object for errors that are found.
	 * @param array  $setting The wp-optionskit setting array for the available_groups field.
	 * @return string
	 */
	public function sanitize_available_groups( $input, $errors, $setting ) {

		$field_id = $setting['id'];

		if ( ! \is_string( $input ) ) {
			$errors->add( $field_id, 'Internal error (not a string)' );
			return '';
		}

		$input = \trim( $input );
		if ( '' === $input ) {
			return '';
		}

		// Single quotes are legal in group names, unescape them.
		$input = str_replace( "\\'", "'", $input );

		log_message( "available groups: |$input|" );
		return $input;
	}

	/**
	 * Sanitize the list of selected groups.
	 *
	 * @param string $input Array of group names to sanitize.
	 * @param object $errors WP_Error object for errors that are found.
	 * @param array  $setting The wp-optionskit setting array for the groups field.
	 * @return array
	 */
	public function sanitize_group_choices( $input, $errors, $setting ) {

		log_message( 'group choices: ' );
		log_message( $input );
		$field_id = $setting['id'];
		if ( ! \is_array( $input ) ) {
			$errors->add( $field_id, 'Internal error (not an array)' );
			return array();
		}

		$n = \count( $input );
		if ( 0 === $n ) {
			$errors->add( $field_id, 'Must select at least one group.' );
			return array();
		}
		if ( $n > 1 ) {
			if ( \in_array( '_everyone_', $input, true ) ) {
				$errors->add( $field_id, '"( Everyone )" cannot be used together with other groups.' );
				return array();
			}
			if ( \in_array( '_logged_in_', $input, true ) ) {
				$errors->add( $field_id, '"( Logged-in Users )" cannot be used together with other groups.' );
				return array();
			}
		}

		$values = array_map(
			function ( $v ) {
				return $v['value'];
			},
			$setting['options']
		);
		foreach ( $input as $group ) {
			if ( ! \in_array( $group, $values, true ) ) {
				$errors->add( $field_id, 'Unknown group: ' . esc_html( $group ) );
				return array();
			}
		}

		return $input;
	}

	/**
	 * Sanitize a URL
	 *
	 * @param string $input URL to sanitize.
	 * @param object $errors WP_Error object for errors that are found.
	 * @param array  $setting The wp-optionskit setting array for the URL field.
	 * @return string
	 */
	public function sanitize_url( $input, $errors, $setting ) {

		$field_id = $setting['id'];

		if ( ! \is_string( $input ) ) {
			$errors->add( $field_id, 'Internal error (not a string)' );
			return '';
		}

		$input = \trim( $input );
		if ( '' === $input ) {
			return '';
		}

		$url = \esc_url_raw( $input, array( 'https' ) );
		if ( '' === $url ) {
			$errors->add( $field_id, 'Must be a URL starting with "https://" or "/"' );
			return '';
		}
		return $input;
	}
}
