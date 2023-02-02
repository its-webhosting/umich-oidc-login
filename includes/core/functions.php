<?php
/**
 * Plugin-level functions that don't fit into any class.
 *
 * @package    UMich_OIDC_Login
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

namespace UMich_OIDC_Login\Core;

/**
 * Write a diagnostic message to the WP_DEBUG log.
 *
 * @param string $message The message to log.
 *
 * @returns void
 */
function log_message( $message ) {
	if ( true === WP_DEBUG ) {
		if ( \is_array( $message ) || \is_object( $message ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions
			\error_log( \print_r( $message, true ) );
		} else {
			$session_name = \substr( \session_name(), 0, 11 );
			$session_id   = \substr( \session_id(), 0, 6 );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions
			\error_log( sprintf( 'umich-oidc %11s=%6s %s', $session_name, $session_id, $message ) );
		}
	}
}

/**
 * Add or remove the wp_logout action. We have to jump thorugh some hoops to
 * ensure that WordPress can find the same the OIDC class instance every time.
 *
 * @param string $operation  'add' to add the action handler, or 'remove' to remove it.
 * @param mixed  $instance Optional instance of the UMich_Login\Core\OIDC class.  Needed the first time this function is called.
 *
 * @returns void
 */
function patch_wp_logout_action( $operation, $instance = null ) {

	static $saved_instance = null;

	if ( ! \is_null( $instance ) ) {
		$saved_instance = $instance;
	}

	if ( ! \is_object( $saved_instance ) || 'UMich_OIDC_Login\Core\OIDC' !== get_class( $saved_instance ) ) {
		log_message( 'ERROR: patch_wp_logout_action() has bad saved instance, plugin logic error' );
		return;
	}

	if ( 'remove' === $operation ) {
		\remove_action( 'wp_logout', array( $saved_instance, 'logout' ) );
		return;
	}
	\add_action( 'wp_logout', array( $saved_instance, 'logout' ) );
}
