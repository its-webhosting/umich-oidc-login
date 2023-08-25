<?php
/**
 * PHP session management.
 *
 * @package    UMich_OIDC_Login\Core
 */

namespace UMich_OIDC_Login\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function UMich_OIDC_Login\Core\log_message;

/**
 * PHP session management.
 *
 * @package    UMich_OIDC_Login\Core
 */
class PHP_Session {

	/**
	 * Context for this WordPress request / this run of the plugin.
	 *
	 * @var      object    $ctx    Context passed to us by our creator.
	 */
	private $ctx;

	/**
	 * Create and initialize the Session object.
	 *
	 * @param object $ctx Context for this WordPress request / this run of the plugin.
	 */
	public function __construct( $ctx ) {
		$this->ctx = $ctx;
	}

	/**
	 * Start the PHP session.
	 *
	 * @return void
	 */
	public function start() {

		if ( PHP_SESSION_NONE !== \session_status() ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@ob_start();
		\session_start();
		log_message( 'session started' );

		/**
		 * Deal with duplicate cookie problem.  See
		 * https://www.php.net/manual/en/function.session-start.php#117157 .
		 */

		if ( \headers_sent() ) {
			return;
		}

		$cookies = array();
		foreach ( \headers_list() as $header ) {
			if ( \str_starts_with( $header, 'Set-Cookie:' ) ) {
				$cookies[] = $header;
			}
		}
		if ( \count( $cookies ) < 2 ) {
			return;
		}
		// Removes all cookie headers, including duplicates.
		\header_remove( 'Set-Cookie' );
		// Restore one copy of each cookie.
		foreach ( \array_unique( $cookies ) as $cookie ) {
			\header( $cookie, false );
		}
	}

	/**
	 * Start the PHP session if one exists. This is called from the Run
	 * class when the plugin is initializing, and prevents "Cannot start
	 * session when headers already sent" errors.
	 *
	 * @return void
	 */
	public function init() {
		/*
		 * Don't start a session unless one exists, otherwise
		 * we'll always be bypassing caching and performance will
		 * suffer greatly.
		 */
		if ( ! \array_key_exists( \session_name(), $_COOKIE ) ) {
			log_message( 'session init - no session cookie' );
			return;
		}

		$this->start();
		\session_write_close();
	}

	/**
	 * Close the PHP session.
	 *
	 * @return void
	 */
	public function close() {
		$this->start();
		\session_write_close();
	}

	/**
	 * Set a value in the session.
	 *
	 * All session keys are prefixed with 'umich_oidc_' to prevent
	 * conflicts with other plugins.
	 *
	 * @param string $key   Session key to set.
	 * @param string $value Value to set for the specified session key.
	 *
	 * @return void
	 */
	public function set( $key, $value ) {
		$this->start();
		$key              = 'umich_oidc_' . $key;
		$_SESSION[ $key ] = $value;
	}

	/**
	 * Get a value from the session.
	 *
	 * All session keys are prefixed with 'umich_oidc_' to prevent
	 * conflicts with other plugins.
	 *
	 * @param string $key Session key to get.
	 * @param string $default_value Optional. Session key to get. Defaults to ''.
	 *
	 * @return mixed Value for session key or $default if the key is not present in the session.
	 */
	public function get( $key, $default_value = '' ) {
		/*
		 * Don't start a session unless one exists, otherwise
		 * we'll always be bypassing caching and performance will
		 * suffer greatly.
		 */
		if ( ! \array_key_exists( \session_name(), $_COOKIE ) ) {
			log_message( "session get {$key} - no session cookie" );
			return $default_value;
		}

		$this->start();

		$key = 'umich_oidc_' . $key;
		if ( isset( $_SESSION ) && \array_key_exists( $key, $_SESSION ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- session data is created by the plugin and stored securely in the database.  Sanitization should be done at the time something is added to the session, not at the time it is retrieved.
			return $_SESSION[ $key ];
		}
		return $default_value;
	}

	/**
	 * Unset a value in the session.
	 *
	 * All session keys are prefixed with 'umich_oidc_' to prevent
	 * conflicts with other plugins.
	 *
	 * @param string $key Session key to unset/clear.
	 *
	 * @return void
	 */
	public function clear( $key ) {
		$this->start();
		$key = 'umich_oidc_' . $key;
		unset( $_SESSION[ $key ] );
	}

	/**
	 * Destroy the session, unsetting the session cookie.
	 *
	 * @return void
	 */
	public function destroy() {
		if ( \headers_sent() ) {
			// We're too late, just return.
			log_message( 'WARNING: attempted to destroy the PHP session but headers have already been sent.' );
			return;
		}

		$this->start();
		$_SESSION = array();

		// Make a list of all cookies, excluding the session cookie.
		$cookies = array();
		foreach ( \headers_list() as $h ) {
			if ( \str_starts_with( $h, 'Set-Cookie:' ) ) {
				if ( ! \str_contains( $h, \session_name() ) ) {
					$cookies[] = $h;
				}
			}
		}

		// Removes all cookie headers, including the session cookie.
		\header_remove( 'Set-Cookie' );

		// Restore one copy of each cookie, excluding the session cookie..
		foreach ( \array_unique( $cookies ) as $cookie ) {
			\header( $cookie, false );
		}

		// Unset the session cookie.
		$params = \session_get_cookie_params();
		// Note that session_get_cookie_params() uses 'lifetime' while
		// setcookie() uses 'expires'.
		\setcookie(
			\session_name(),
			'',  // cookie value.
			array(
				'expires'  => 1, // 1 second into January 1, 1970
				'path'     => $params['path'],
				'domain'   => $params['domain'],
				'secure'   => $params['secure'],
				'httponly' => $params['httponly'],
				'samesite' => $params['samesite'],
			)
		);
		\session_destroy();
		log_message( 'session cookie has been unset' );
	}

	/**
	 * Unset all values in the session.
	 *
	 * All session keys are prefixed with 'umich_oidc_' to prevent
	 * conflicts with other plugins.
	 *
	 * @return void
	 */
	public function clear_all() {

		$this->start();

		foreach ( \array_keys( $_SESSION ) as $key ) {
			if ( \str_starts_with( $key, 'umich_oidc_' ) ) {
				unset( $_SESSION[ $key ] );
			}
		}

		\session_write_close();

		// If no other plugin or package is using the session, destroy it.
		if ( 0 === \count( $_SESSION ) ) {
			$this->destroy();
		}
	}

	/**
	 * Fix session headers just before they get sent to the browser.
	 * If the session data is empty, expire the session cookie so that
	 * we do not bypass the cache and have performance suffer.
	 *
	 * @param array $headers  HTTP headers generated by WordPress.
	 *
	 * @returns array $headers
	 */
	public function fix_headers( $headers ) {

		// Do nothing if there is no session or the session contains data.
		if ( ! isset( $_SESSION ) || \count( $_SESSION ) > 0 ) {
			return $headers;
		}
		log_message( $_SESSION );

		$this->destroy();

		return $headers;
	}
}
