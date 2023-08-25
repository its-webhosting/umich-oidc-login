<?php
/**
 * OIDC authenticated user.
 *
 * This implements An OIDC user, as opposed to a WordPress user account.  The
 * two accounts may or may not be linked.
 *
 * @package    UMich_OIDC_Login\Core
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

namespace UMich_OIDC_Login\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function UMich_OIDC_Login\Core\log_message;

/**
 * OIDC authenticated user.
 *
 * The user may be not logged in, logged in via OIDC, logged in via
 * WordPress, or logged in with linked WordPress/OIDC accounts.
 *
 * @package    UMich_OIDC_Login\Core
 */
class OIDC_User {

	/**
	 * Context for this WordPress request / this run of the plugin.
	 *
	 * @var      object    $ctx    Context passed to us by our creator.
	 */
	private $ctx;

	/**
	 * Whether this object has been initialized.
	 *
	 * @var      bool    $initialized    Whether this object has been initialized.
	 */
	private $initialized = false;

	/**
	 * The current WordPress user.
	 *
	 * @var      WP_User    $wp_user    The current WordPress user.
	 */
	public $wp_user = null;

	/**
	 * The authenticated user's OIDC id token.
	 *
	 * @var      Object    $id_token    The OIDC id token for the currently authenticated user.  null if the user is not authenticated.
	 */
	private $id_token = null;

	/**
	 * The authenticated user's OIDC userinfo.
	 *
	 * @var      Object    $userinfo    The OIDC userinfo for the currently authenticated user.  null if the user is not authenticated.
	 */
	private $userinfo = null;

	/**
	 * Session state.
	 *
	 * @var      string    $session_state  "none" (user has not logged in), "valid" (user is currently logged in), "expired" (user was logged in but the session expired).
	 */
	private $session_state = 'none';

	/**
	 * Create and initialize the OIDC User object.
	 *
	 * @param object $ctx Context for this WordPress request / this run of the plugin.
	 *
	 * @return void
	 */
	public function __construct( $ctx ) {
		$this->ctx = $ctx;
	}

	/**
	 * Create and initialize the University of Michigan user object.
	 *
	 * @return void
	 */
	public function init() {

		$ctx = $this->ctx;

		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		$session             = $ctx->session;
		$session_state       = $session->get( 'state', 'none' );
		$this->session_state = $session_state;

		$id_token = $session->get( 'id_token', null );
		$userinfo = $session->get( 'userinfo', null );

		if ( ! \is_object( $id_token ) ||
			! \property_exists( $id_token, 'iat' ) ||
			! \is_object( $userinfo ) ) {
			// User is not logged in.
			if ( 'expired' === $session_state && ! \wp_doing_ajax() ) {
				// An AJAX request will not clear an expired session, but a regular page load will.
				log_message( 'user init: page load, clearing expired session' );

				$this->session_state = 'none';
				$session->set( 'state', 'none' );
				$session->close();
			}
			return;
		}

		$options        = $ctx->options;
		$session_length = $options['session_length'];
		if ( \time() > ( ( (int) $id_token->iat ) + (int) $session_length ) ) {
			log_message( "user init: OIDC session time ({$session_length} seconds) expired, logging user out" );
			$ctx->oidc->logout();
			$this->wp_user       = null;
			$this->id_token      = null;
			$this->userinfo      = null;
			$this->session_state = 'expired';
			$session->set( 'state', 'expired' );
			$session->close();
			return;
		}

		if ( ! \array_key_exists( 'claim_for_username', $options ) ) {
			log_message( 'ERROR: plugin option claim_for_username not set' );
			$this->session_state = 'none';
			$session->set( 'state', 'none' );
			$session->close();
			return;
		}
		$username_claim = $options['claim_for_username'];
		if ( ! \property_exists( $userinfo, $username_claim ) ) {
			log_message( 'ERROR: user seemingly logged in, but can\'t find username information in userinfo' );
			$this->session_state = 'none';
			$session->set( 'state', 'none' );
			$session->close();
			return;
		}

		$this->id_token = $id_token;
		$this->userinfo = $userinfo;
	}

	/**
	 * Get a piece of userinfo for an authenticated user.
	 *
	 * @param string $key What userinfo to get.  This key will be mapped trough the plugin claim_for_ options unless it starts with "userinfo:".
	 * @param mixed  $default_value What to return if the requested userinfo is not found.
	 *
	 * @return mixed
	 */
	public function get_userinfo( $key, $default_value = '' ) {

		if ( ! $this->initialized ) {
			$this->init();
		}

		/*
		 * translate type to OIDC claim name
		 * - using "userinfo:abc" will get "abc" from userinfo
		 * - using "abc" will get the plugin userinfo if a mapping for abc exists, otherwise will look in userinfo:abc
		 */
		if ( \str_starts_with( $key, 'userinfo:' ) ) {
			// bypass plugin-to-claim mapping.
			$key = \substr( $key, \strlen( 'userinfo:' ) );
		} else {
			// do plugin-to-claim mapping.
			$options = $this->ctx->options;
			if ( \array_key_exists( 'claim_for_' . $key, $options ) ) {
				$key = $options[ 'claim_for_' . $key ];
			}
		}

		log_message( "looking up userinfo {$key}" );
		if ( '' === $key || ! \is_object( $this->userinfo ) || ! \property_exists( $this->userinfo, $key ) ) {
			return $default_value;
		}
		return $this->userinfo->$key;
	}

	/**
	 * Is the user logged in?
	 *
	 * @return bool
	 */
	public function logged_in() {

		if ( ! $this->initialized ) {
			$this->init();
		}

		if ( \is_object( $this->userinfo ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Session state.
	 *
	 * @return string
	 */
	public function session_state() {
		if ( ! $this->initialized ) {
			$this->init();
		}
		return $this->session_state;
	}

	/**
	 * Returm the list of groups the user is in (is a member of).
	 *
	 * @return array
	 */
	public function groups() {

		if ( ! $this->initialized ) {
			$this->init();
		}

		$groups = $this->get_userinfo( 'groups', null );
		if ( \is_null( $groups ) ) {
			log_message( 'groups is null' );
			return array();
		}
		if ( ! \is_array( $groups ) ) {
			log_message( 'groups is not array' );
			return array();
		}

		return $groups;
	}

	/**
	 * If an OIDC user is logged in, add headers to prevent the page from
	 * being cached.
	 *
	 * @param array $headers  HTTP headers generated by WordPress.
	 *
	 * @returns array $headers
	 */
	public function fix_headers( $headers ) {

		if ( ! $this->initialized ) {
			$this->init();
		}

		if ( $this->logged_in() ) {
			$headers = \array_merge( $headers, \wp_get_nocache_headers() );
		}

		return $headers;
	}

	/**
	 * Pantheon has a mandatory caching module that overrides the
	 * cache-control header set by other plugins.  Undo what Pantheon
	 * did and re-do our own cache-control headers.
	 *
	 * @returns void
	 */
	public function pantheon_headers() {

		if ( ! $this->initialized ) {
			$this->init();
		}

		if ( ! $this->logged_in() || \headers_sent() ) {
			return;
		}

		$headers = \wp_get_nocache_headers();
		foreach ( (array) $headers as $name => $field_value ) {
			if ( 0 === \strcasecmp( $name, 'cache-control' ) ) {
				\header_remove( 'cache-control' );
				\header( "{$name}: {$field_value}" );
			}
		}
	}
}
