<?php
/**
 * Authentication checks.
 *
 * Allows browsers to detect when a login session expires and display an
 * overlay prompting the user to log in again.
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
 * Authentication checks.
 *
 * @package    UMich_OIDC_Login\Core
 */
class Auth_Check {

	/**
	 * Context for this WordPress request / this run of the plugin.
	 *
	 * @var      object    $ctx    Context passed to us by our creator.
	 */
	private $ctx;

	/**
	 * Create and initialize the Auth Check object.
	 *
	 * @param object $ctx Context for this WordPress request / this run of the plugin.
	 *
	 * @return void
	 */
	public function __construct( $ctx ) {
		$this->ctx = $ctx;
	}

	/**
	 * Filter for wp_auth_check_same_domain.
	 *
	 * If the user is logged in via OIDC (possibly with a linked WordPress
	 * account), force the same domain check to be false.  This makes
	 * WordPress display the link to login in a new tab rather than a form
	 * with username and password fields.
	 *
	 * @param bool $same_domain Value from previous filters.
	 *
	 * @returns bool
	 */
	public function auth_check_same_domain( $same_domain ) {

		$ctx = $this->ctx;

		if ( 'none' !== $ctx->oidc_user->session_state() ) {
			// There is (or was) an OIDC session.
			return false;
		}

		return $same_domain;
	}

	/**
	 * Check for expired WordPress sessions on regular site pages
	 * (non-admin pages) and log the user in again.  WordPress provides
	 * an almost idenical function, wp_auth_check_load(), for admin pages.
	 *
	 * @return void
	 */
	public function auth_check_load() {

		$ctx = $this->ctx;

		if ( 'none' === $ctx->oidc_user->session_state() ) {
			return;
		}
		// Continue if the user is logged in or was logged in but their session expired.

		if ( defined( 'IFRAME_REQUEST' ) ) {
			return;
		}

		if ( \apply_filters( 'wp_auth_check_load', true, null ) ) {
			\wp_enqueue_style( 'wp-auth-check' );
			\wp_enqueue_script( 'wp-auth-check' );
			\add_action( 'admin_print_footer_scripts', 'wp_auth_check_html', 5 );
			\add_action( 'wp_print_footer_scripts', 'wp_auth_check_html', 5 );
		}
	}

	/**
	 * Filter for heartbeat_send() to perform our own auth check.
	 *
	 * This should be called after WordPress has called its own
	 * wp_auth_check() and set $response['wp-auth-check'] to
	 * indicate the authentication status of WordPress users.  We
	 * then overide that, if appropriate, for OIDC users.
	 *
	 * @param mixed $response Value from previous filters.
	 *
	 * @returns mixed
	 */
	public function oidc_auth_check( $response ) {

		$ctx = $this->ctx;

		if ( \array_key_exists( 'wp-auth-check', $response ) ) {
			log_message( 'oidc_auth_check: WordPress: ' . ( $response['wp-auth-check'] ? 'yes' : 'no' ) );
			if ( $response['wp-auth-check'] ) {
				return $response;
			}
		}

		$session_state = $ctx->oidc_user->session_state();
		log_message( "oidc_auth_check: OIDC: {$session_state}" );
		$response['wp-auth-check'] = ( 'valid' === $session_state );

		return $response;
	}

	/**
	 * Filter for authentication session length.
	 *
	 * @param int $length Session length in session from previous filters.
	 *
	 * @returns int
	 */
	public function session_length( $length ) {
		$new_length = (int) $this->ctx->options['session_length'];
		if ( $new_length > 0 ) {
			return $new_length;
		}
		return $length;
	}
}
