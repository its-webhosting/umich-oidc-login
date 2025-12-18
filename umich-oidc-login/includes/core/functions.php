<?php
/**
 * Plugin-level functions that don't fit into any class.
 *
 * @package    UMich_OIDC_Login
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

namespace UMich_OIDC_Login\Core;

// enums require PHP 8.1.0 or later.
const LEVEL_NOTHING    = 0;
const LEVEL_ERROR      = 1;
const LEVEL_USER_EVENT = 2;  // major events (login, logout).
const LEVEL_NOTICE     = 3;  // warnings.
const LEVEL_INFO       = 4;  // details.
const LEVEL_DEBUG      = 5;

$log_level_name = array(
	LEVEL_NOTHING    => 'NOTHING',
	LEVEL_ERROR      => 'ERROR',
	LEVEL_USER_EVENT => 'EVENT',
	LEVEL_NOTICE     => 'NOTICE',
	LEVEL_INFO       => 'INFO',
	LEVEL_DEBUG      => 'DEBUG',
);


$start_timestamp = \microtime( true );
$start_time_base = \hrtime( true );
$logs            = array();
$log_level       = LEVEL_DEBUG;


/**
 * Log a message.
 *
 * Callers are encouraged to pass in a sprintf template for $message together with $params instead.  This avoids
 * the runtime cost of doing interpolation (prior to the function call) unless the message will actually get logged.
 *
 * @param int           $level      One of LEVEL_ERROR, LEVEL_USER_EVENT, LEVEL_NOTICE, LEVEL_INFO, LEVEL_DEBUG.
 * @param string|object $message    The message to log.
 * @param mixed         ...$params  Values to substitute into $message placeholders.
 *
 * @returns void
 */
function log_umich_oidc( $level, $message, ...$params ) {
	global $log_level, $logs, $start_time_base;

	if ( $level > $log_level ) {
		return;
	}

	if ( \is_array( $message ) || \is_object( $message ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions
		$message = \print_r( $message, true );
	} elseif ( count( $params ) > 0 ) {
		$message = \sprintf( $message, ...$params );
	}

	$logs[] = array(
		'time_elapsed' => \hrtime( true ) - $start_time_base,
		'level'        => $level,
		'message'      => $message,
	);
}


/**
 * Output accumulated log messages.
 *
 * @returns void
 */
function output_log_messages() {
	global $logs, $start_timestamp, $log_level_name;

	log_umich_oidc( LEVEL_DEBUG, 'shutdown' );

	// The request ID is only for people to group log entries for a single request together, so we don't need it
	// to be cryptographically secure.
	//
	// uniqid('', true) generates IDs that are very similar to one another, making them hard to visually
	// distinguish in long log entries, so we use wp_rand() instead.
	$request_id = \substr( \sprintf( '%08x', \wp_rand() ), 0, 8 );

	$session_name  = \substr( \session_name(), 0, 11 );
	$session_id    = \substr( \session_id(), 0, 6 );
	$timestamp_int = 0;
	$timestamp_str = '';

	foreach ( $logs as $log ) {
		$ts     = $start_timestamp + (float) $log['time_elapsed'] / 10e9;
		$ts_int = (int) $ts;
		if ( $ts_int !== $timestamp_int ) {
			$timestamp_int = $ts_int;
			$timestamp_str = \wp_date( 'c', $timestamp_int );
		}
		$microsec = (int) ( 100000 * ( $ts - $ts_int ) );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions
		\error_log(
			\sprintf(
				'umich-oidc %s %06d %s %11s=%6s %8s %s',
				$timestamp_str,
				$microsec,
				$request_id,
				$session_name,
				$session_id,
				( isset( $log_level_name[ $log['level'] ] ) ? $log_level_name[ $log['level'] ] : 'UNKNOWN' ),
				$log['message']
			)
		);
	}
}


/**
 * Add or remove the wp_logout action. We have to jump through some hoops to
 * ensure that WordPress can find the same the OIDC class instance every time.
 *
 * @param string      $operation  'add' to add the action handler, or 'remove' to remove it.
 * @param object|null $instance Optional instance of the UMich_Login\Core\OIDC class.  Needed the first time this function is called.
 *
 * @returns void
 */
function patch_wp_logout_action( $operation, $instance = null ) {

	static $saved_instance = null;

	if ( ! \is_null( $instance ) ) {
		$saved_instance = $instance;
	}

	if ( ! \is_object( $saved_instance ) || 'UMich_OIDC_Login\Core\OIDC' !== get_class( $saved_instance ) ) {
		log_umich_oidc( LEVEL_ERROR,'plugin logic error: patch_wp_logout_action() has bad saved instance' );
		return;
	}

	if ( 'remove' === $operation ) {
		\remove_action( 'wp_logout', array( $saved_instance, 'logout' ) );
		return;
	}
	\add_action( 'wp_logout', array( $saved_instance, 'logout' ) );
}
