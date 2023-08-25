<?php
/**
 * The main class used to run the plugin.
 *
 * @package    UMich_OIDC_Login
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

namespace UMich_OIDC_Login;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function UMich_OIDC_Login\Core\log_message;

/**
 * The main class used to run the plugin.
 *
 * @package    UMich_OIDC_Login\Core
 */
class Run {

	/**
	 * Plugin options array.
	 *
	 * We get this once centrally in the Run class to reduce the amount
	 * of error checking needed elsewhere in the code.
	 *
	 * @var      array    $options    Options for the UMich OIDC Login plugin.
	 */
	public $options;

	/**
	 * Default values for plugin options.  Used on the plugin settings page.
	 *
	 * Don't set a default for any of the following options, we want the
	 * authentication to fail if any of these are not present:
	 *   'provider_url', 'client_id', 'client_secret'.
	 *
	 * @var      array    $option_defaults    Option default values.
	 */
	public $option_defaults = array(
		'claim_for_email'       => 'email',
		'claim_for_family_name' => 'family_name',
		'claim_for_full_name'   => 'name',
		'claim_for_given_name'  => 'given_name',
		'claim_for_groups'      => 'edumember_ismemberof',
		'claim_for_username'    => 'preferred_username',
		'client_auth_method'    => 'client_secret_post',
		'login_action'          => 'setting',
		'login_return_url'      => '',
		'logout_action'         => 'smart',
		'logout_return_url'     => '',
		'restrict_site'         => array( '_everyone_' ),
		'session_length'        => 86400,
		'scopes'                => 'openid email profile edumember',
		'use_oidc_for_wp_users' => 'no',
	);

	/**
	 * Plugin internal options array.
	 *
	 * This array stores state that the plugin needs across multiple
	 * sessions.
	 *
	 * We get this once centrally in the Run class so we can check it
	 * and set it to an empty array if \get_option returns a non-array.
	 *
	 * @var      array    $internals    Internal options for the UMich OIDC Login plugin.
	 */
	public $internals;

	/**
	 * Session object.
	 *
	 * @var      object    $session    Session object.
	 */
	public $session;

	/**
	 * OIDC_User object.
	 *
	 * @var      object    $oidc_user    OIDC_User object.
	 */
	public $oidc_user;

	/**
	 * OIDC object.
	 *
	 * @var      object    $oidc    OIDC object.
	 */
	public $oidc;

	/**
	 * Auth_Check object.
	 *
	 * @var      object    $auth_check    Auth_Check object.
	 */
	public $auth_check;

	/**
	 * Shortcodes object.
	 *
	 * @var      object    $shotcodes    Shortcodes object.
	 */
	public $shortcodes;

	/**
	 * Restrict_Access object.
	 *
	 * @var      object    $restrict_access    Restrict_Access object.
	 */
	public $restrict_access;

	/**
	 * Restrict_Access object.
	 *
	 * @var      object    $post_meta_box    Post_Meta_Box object.
	 */
	public $post_meta_box;

	/**
	 * Settings_Page object.
	 *
	 * @var      object    $settings_page    Settings_Page object.
	 */
	public $settings_page;

	/**
	 * Whether the WordPress request we are processing is viewable by
	 * everyone / the public (as opposed to being limited to logged-in
	 * users / members of groups).
	 *
	 * @var      bool    $public_resource    Whether the resource is public.
	 */
	public $public_resource = true;


	/**
	 * Check whether the plugin has been upgraded, and if so take care
	 * of any database changes or other housekeeping that the upgrade
	 * requires.
	 *
	 * @return void
	 */
	private function do_plugin_upgrade_tasks() {

		if ( $this->internals['plugin_version'] >= UMICH_OIDC_LOGIN_VERSION_INT ) {
			return;
		}

		log_message( '***** UMich OIDC Login plugin upgrade from version ' . $this->internals['plugin_version'] . ' to ' . UMICH_OIDC_LOGIN_VERSION_INT . ' *****' );

		/*
		 * Version 1.0.0 -> 1.1.0:
		 * use_oidc_for_wp_users (no|optional|yes) replaces link_accounts (false|true).
		 */
		if ( \array_key_exists( 'link_accounts', $this->options ) ) {
			$link_accounts = (bool) $this->options['link_accounts'];
			if ( $link_accounts ) {
				$this->options['use_oidc_for_wp_users'] = 'yes';
			}
			unset( $this->options['link_accounts'] );
			\update_option( 'umich_oidc_settings', $this->options );
		}
		$this->internals['plugin_version'] = UMICH_OIDC_LOGIN_VERSION_INT;
		\update_option( 'umich_oidc_internals', $this->internals );
	}


	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function __construct() {
		/*
		 * \get_option() can return false if the option does not exist,
		 * or it can return any other type if the plugin options got
		 * corrupted (for example, if the website owner ran
		 * "wp option update").  We retrieve the option and check the
		 * return value centrally to simplify the code in all the
		 * other classes.
		 */
		$options = \get_option( 'umich_oidc_settings' );
		if ( ! \is_array( $options ) ) {
			log_message( 'WARNING: plugin options not an array' );
			$options = array();
		}
		$this->options = \array_merge( $this->option_defaults, $options );

		$internal_defaults = array(
			'plugin_version' => 0,
		);

		$internals = \get_option( 'umich_oidc_internals' );
		if ( ! \is_array( $internals ) ) {
			$internals = array();
		}
		$this->internals = \array_merge( $internal_defaults, $internals );

		$this->do_plugin_upgrade_tasks();

		$this->session = new \UMich_OIDC_Login\Core\PHP_Session( $this );
		$this->session->init();
		\add_filter( 'wp_headers', array( $this->session, 'fix_headers' ) );

		$this->oidc_user = new \UMich_OIDC_Login\Core\OIDC_User( $this );
		\add_filter( 'wp_headers', array( $this->oidc_user, 'fix_headers' ) );
		if ( isset( $_ENV['PANTHEON_ENVIRONMENT'] ) ) {
			\add_action( 'send_headers', array( $this->oidc_user, 'pantheon_headers' ), 10000 );
		}

		$this->oidc = new \UMich_OIDC_Login\Core\OIDC( $this );
		if ( \is_admin() ) {

			// Login handler.
			//
			// Use the same action name (openid-connect-authorize) as the Daggerhart OpenID Connect Generic
			// plugin so either that plugin or this plugin can be used without changing the authorized
			// redirect URL list on the IdP.
			\add_action( 'wp_ajax_openid-connect-authorize', array( $this->oidc, 'login' ) );
			\add_action( 'wp_ajax_nopriv_openid-connect-authorize', array( $this->oidc, 'login' ) );

			// Logout handler.
			\add_action( 'wp_ajax_umich-oidc-logout', array( $this->oidc, 'logout_and_redirect' ) );
			\add_action( 'wp_ajax_nopriv_umich-oidc-logout', array( $this->oidc, 'logout_and_redirect' ) );

		}

		\add_filter( 'allowed_redirect_hosts', array( $this->oidc, 'allowed_redirect_hosts' ) );
		\add_filter( 'login_url', array( $this->oidc, 'login_url' ), 20, 3 );
		\add_filter( 'logout_url', array( $this->oidc, 'logout_url' ), 20, 2 );
		\add_action( 'login_init', array( $this->oidc, 'init_wp_login' ), 20, 2 );
		\UMich_OIDC_Login\Core\patch_wp_logout_action( 'add', $this->oidc );

		// Detect session expiration and prompt the user to log back in.
		$this->auth_check = new \UMich_OIDC_Login\Core\Auth_Check( $this );
		\add_filter( 'wp_auth_check_same_domain', array( $this->auth_check, 'auth_check_same_domain' ) );
		\add_action( 'wp_enqueue_scripts', array( $this->auth_check, 'auth_check_load' ) );
		\add_filter( 'heartbeat_send', array( $this->auth_check, 'oidc_auth_check' ), 20, 1 );
		\add_filter( 'heartbeat_nopriv_send', array( $this->auth_check, 'oidc_auth_check' ), 20, 1 );
		\add_filter( 'auth_cookie_expiration', array( $this->auth_check, 'session_length' ) );

		$this->shortcodes = new \UMich_OIDC_Login\Site\Shortcodes( $this );
		\add_shortcode( 'umich_oidc_link', array( $this->shortcodes, 'link' ) );
		\add_shortcode( 'umich_oidc_button', array( $this->shortcodes, 'button' ) );
		\add_shortcode( 'umich_oidc_logged_in', array( $this->shortcodes, 'logged_in' ) );
		\add_shortcode( 'umich_oidc_not_logged_in', array( $this->shortcodes, 'logged_in' ) );
		\add_shortcode( 'umich_oidc_member', array( $this->shortcodes, 'member' ) );
		\add_shortcode( 'umich_oidc_not_member', array( $this->shortcodes, 'member' ) );
		\add_shortcode( 'umich_oidc_url', array( $this->shortcodes, 'url' ) );
		\add_shortcode( 'umich_oidc_userinfo', array( $this->shortcodes, 'userinfo' ) );  // canonical name.
		\add_shortcode( 'umich_oidc_user_info', array( $this->shortcodes, 'userinfo' ) );  // alias for umich_oidc_userinfo.
		\add_filter( 'login_message', array( $this->shortcodes, 'login_form' ) );

		$this->restrict_access = new \UMich_OIDC_Login\Site\Restrict_Access( $this );
		\add_action( 'template_redirect', array( $this->restrict_access, 'restrict_site' ), 0 );
		\add_action( 'template_redirect', array( $this->restrict_access, 'restrict_site_single_post' ), 1 );
		\add_action( 'get_the_excerpt', array( $this->restrict_access, 'get_the_excerpt' ), 0 );
		\add_filter( 'the_content', array( $this->restrict_access, 'the_content' ), 10000 );
		\add_filter( 'the_posts', array( $this->restrict_access, 'restrict_list' ), 10000 );
		\add_filter( 'get_pages', array( $this->restrict_access, 'restrict_list' ), 10000 );
		\add_filter( 'the_content_feed', array( $this->restrict_access, 'restrict_feed' ), 10000 );
		\add_filter( 'the_excerpt_rss', array( $this->restrict_access, 'restrict_feed' ), 10000 );
		\add_filter( 'comment_text_rss', array( $this->restrict_access, 'restrict_feed' ), 10000 );
		$post_types = \get_post_types();
		foreach ( $post_types as $type ) {
			\add_filter( 'rest_prepare_' . $type, array( $this->restrict_access, 'rest_prepare_post' ), 10000, 3 );
		}
		\add_filter( 'rest_prepare_comment', array( $this->restrict_access, 'rest_prepare_comment' ), 10000, 3 );
		\add_filter( 'rest_prepare_revision', array( $this->restrict_access, 'rest_prepare_revision' ), 10000, 3 );
		\add_filter( 'found_posts', array( $this->restrict_access, 'found_posts' ), 10000, 2 );
		\add_filter( 'xmlrpc_prepare_post', array( $this->restrict_access, 'xmlrpc_prepare_post' ), 0, 3 );
		\add_filter( 'xmlrpc_prepare_page', array( $this->restrict_access, 'xmlrpc_prepare_post' ), 0, 3 );
		\add_filter( 'xmlrpc_prepare_comment', array( $this->restrict_access, 'xmlrpc_prepare_comment' ), 0, 2 );
		\add_action( 'xmlrpc_call', array( $this->restrict_access, 'xmlrpc_call' ), 0, 3 );

		// Metabox to restrict access to pages and posts.
		// Works in both Gutenberg and the Classic Editor.
		$this->post_meta_box = new \UMich_OIDC_Login\Admin\Post_Meta_Box( $this );
		\add_filter( 'add_meta_boxes', array( $this->post_meta_box, 'access_meta_box' ) );
		\add_action( 'admin_enqueue_scripts', array( $this->post_meta_box, 'admin_scripts' ) );
		\add_filter( 'save_post', array( $this->post_meta_box, 'access_meta_box_save' ) );

		\register_post_meta(
			'',
			'_umich_oidc_access',
			array(
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			)
		);

		$this->settings_page = new \UMich_OIDC_Login\Admin\Settings_Page( $this );
	}
}
