<?php
/**
 * The main class used to run the plugin.
 *
 * @package    UMich_OIDC_Login
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 * @since      1.0.0
 */

namespace UMich_OIDC_Login;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function UMich_OIDC_Login\Core\log_message as log_message;

/**
 * The main class used to run the plugin.
 *
 * @package    UMich_OIDC_Login\Core
 * @since      1.0.0
 */
class Run {

	/**
	 * Plugin options array.
	 *
	 * We get this once centrally in the Run class so we can check it
	 * and set it to an empty array if \get_option returns a non-array.
	 *
	 * @var      array    $options    Options for the UMich OIDC Login plugin.
	 *
	 * @since    1.0.0
	 */
	public $options;

	/**
	 * Session object.
	 *
	 * @var      object    $session    Session object.
	 *
	 * @since    1.0.0
	 */
	public $session;

	/**
	 * OIDC_User object.
	 *
	 * @var      object    $oidc_user    OIDC_User object.
	 *
	 * @since    1.0.0
	 */
	public $oidc_user;

	/**
	 * OIDC object.
	 *
	 * @var      object    $oidc    OIDC object.
	 *
	 * @since    1.0.0
	 */
	public $oidc;

	/**
	 * Auth_Check object.
	 *
	 * @var      object    $auth_check    Auth_Check object.
	 *
	 * @since    1.0.0
	 */
	public $auth_check;

	/**
	 * Shortcodes object.
	 *
	 * @var      object    $shotcodes    Shortcodes object.
	 *
	 * @since    1.0.0
	 */
	public $shortcodes;

	/**
	 * Restrict_Access object.
	 *
	 * @var      object    $restrict_access    Restrict_Access object.
	 *
	 * @since    1.0.0
	 */
	public $restrict_access;

	/**
	 * Settings_Page object.
	 *
	 * @var      object    $settings_page    Settings_Page object.
	 *
	 * @since    1.0.0
	 */
	public $settings_page;

	/**
	 * Whether the WordPress request we are processing is viewable by
	 * everyone / the public (as opposed to being limited to logged-in
	 * users / members of groups).
	 *
	 * @var      bool    $public_resource    Whether the resource is public.
	 *
	 * @since    1.0.0
	 */
	public $public_resource = true;

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 *
	 * @since 1.0.0
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
		$this->options = $options;

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
		\add_filter( 'heartbeat_send', array( $this->auth_check, 'auth_check' ), 20, 1 );
		\add_filter( 'heartbeat_nopriv_send', array( $this->auth_check, 'auth_check' ), 20, 1 );
		\add_filter( 'auth_cookie_expiration', array( $this->auth_check, 'session_length' ) );

		$this->shortcodes = new \UMich_OIDC_Login\Site\Shortcodes( $this );
		\add_shortcode( 'umich_oidc_link', array( $this->shortcodes, 'link' ) );
		\add_shortcode( 'umich_oidc_button', array( $this->shortcodes, 'button' ) );
		\add_shortcode( 'umich_oidc_logged_in', array( $this->shortcodes, 'logged_in' ) );
		\add_shortcode( 'umich_oidc_not_logged_in', array( $this->shortcodes, 'logged_in' ) );
		\add_shortcode( 'umich_oidc_member', array( $this->shortcodes, 'member' ) );
		\add_shortcode( 'umich_oidc_not_member', array( $this->shortcodes, 'member' ) );
		\add_shortcode( 'umich_oidc_url', array( $this->shortcodes, 'url' ) );
		\add_shortcode( 'umich_oidc_user_info', array( $this->shortcodes, 'user_info' ) );
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

		$this->post_meta_box = new \UMich_OIDC_Login\Admin\Post_Meta_Box( $this );
		\add_filter( 'add_meta_boxes', array( $this->post_meta_box, 'access_meta_box' ) );
		\add_action( 'admin_enqueue_scripts', array( $this->post_meta_box, 'admin_scripts' ) );
		\add_filter( 'save_post', array( $this->post_meta_box, 'access_meta_box_save' ) );
		\add_action( 'xmlrpc_call', array( $this->restrict_access, 'xmlrpc_call' ), 0, 3 );

		$this->settings_page = new \UMich_OIDC_Login\Admin\Settings_Page( $this );

	}

}
