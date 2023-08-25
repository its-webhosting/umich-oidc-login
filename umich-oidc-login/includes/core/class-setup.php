<?php
/**
 * Activate or deactivate the plugin
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
 * Fired during plugin activation or deactivation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    UMich_OIDC_Login\Core
 *
 * The initial version of this code came from Better WordPress Plugin
 * Boilerplate, maintained by TukuToi and published under GPLv2 or later.
 * @see https://github.com/TukuToi/better-wp-plugin-boilerplate
 */
class Setup {

	/**
	 * The $_REQUEST during plugin activation.
	 *
	 * @var      array    $request    The $_REQUEST array during plugin activation.
	 */
	private static $request = array();

	/**
	 * The $_REQUEST['plugin'] during plugin activation.
	 *
	 * @var      string    $plugin    The $_REQUEST['plugin'] value during plugin activation.
	 */
	private static $plugin = \UMICH_OIDC_LOGIN_BASE_NAME;

	/**
	 * Activate the plugin.
	 *
	 * Checks if the plugin was (safely) activated.
	 * Place to add any custom action during plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {

		if ( false === self::get_request( 'activate' )
			|| false === self::validate_request( self::$plugin, 'activate' )
			|| false === self::check_caps()
		) {
			if ( isset( $_REQUEST['plugin'] ) ) {
				if ( ! \check_admin_referer( 'activate-plugin_' . self::$request['plugin'] ) ) {
					exit;
				}
			} elseif ( isset( $_REQUEST['checked'] ) ) {
				if ( ! \check_admin_referer( 'bulk-plugins' ) ) {
					exit;
				}
			}
		}

		/**
		 * The plugin is now safely activated.
		 * Perform your activation actions here.
		 */
		log_message( '***** UMich OIDC Login plugin version ' . UMICH_OIDC_LOGIN_VERSION . ' activated *****' );
	}

	/**
	 * Deactivate the plugin.
	 *
	 * Checks if the plugin can be (safely) deactivated.
	 * Place to add any custom action during plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {

		if ( false === self::get_request( 'deactivate' )
			|| false === self::validate_request( self::$plugin, 'deactivate' )
			|| false === self::check_caps()
		) {
			if ( isset( $_REQUEST['plugin'] ) ) {
				if ( ! \check_admin_referer( 'deactivate-plugin_' . self::$request['plugin'] ) ) {
					exit;
				}
			} elseif ( isset( $_REQUEST['checked'] ) ) {
				if ( ! \check_admin_referer( 'bulk-plugins' ) ) {
					exit;
				}
			}
		}

		/**
		 * The plugin is now safely deactivated.
		 * Perform your deactivation actions here.
		 */
		log_message( '***** UMich OIDC Login plugin version ' . UMICH_OIDC_LOGIN_VERSION . ' deactivated *****' );
	}

	/**
	 * Get the request.
	 *
	 * Gets the $_REQUEST array and checks if necessary keys are set.
	 * Populates self::request with necessary and sanitized values.
	 *
	 * @param string $action The action to perform, either 'activate' or 'deactivate'.
	 * @return bool|array false or self::$request array.
	 */
	private static function get_request( $action ) {

		if ( ! empty( $_REQUEST )
			&& isset( $_REQUEST['_wpnonce'] )
			&& isset( $_REQUEST['action'] )
		) {
			if ( isset( $_REQUEST['plugin'] ) ) {
				if ( false !== \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) ), "{$action}-plugin_" . \sanitize_text_field( \wp_unslash( $_REQUEST['plugin'] ) ) ) ) {

					self::$request['plugin'] = \sanitize_text_field( \wp_unslash( $_REQUEST['plugin'] ) );
					self::$request['action'] = \sanitize_text_field( \wp_unslash( $_REQUEST['action'] ) );

					return self::$request;

				}
			} elseif ( isset( $_REQUEST['checked'] ) ) {
				if ( false !== \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-plugins' ) ) {

					self::$request['action']  = \sanitize_text_field( \wp_unslash( $_REQUEST['action'] ) );
					self::$request['plugins'] = array_map( 'sanitize_text_field', \wp_unslash( $_REQUEST['checked'] ) );

					return self::$request;

				}
			}
		} else {

			return false;
		}
	}

	/**
	 * Validate the Request data.
	 *
	 * Validates the $_REQUESTed data is matching this plugin and action.
	 *
	 * @param string $plugin The Plugin folder/name.php.
	 * @param string $action The action to perform, either 'activate' or 'deactivate'.
	 *
	 * @return bool false if either plugin or action does not match, else true.
	 */
	private static function validate_request( $plugin, $action ) {

		if ( isset( self::$request['plugin'] )
			&& $plugin === self::$request['plugin']
			&& $action === self::$request['action']
		) {

			return true;

		} elseif ( isset( self::$request['plugins'] )
			&& "{$action}-selected" === self::$request['action']
			&& in_array( $plugin, self::$request['plugins'], true )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check Capabilities.
	 *
	 * We want no one else but users with activate_plugins or above to be able to active this plugin.
	 *
	 * @return bool false if no caps, else true.
	 */
	private static function check_caps() {

		if ( \current_user_can( 'activate_plugins' ) ) {
			return true;
		}

		return false;
	}
}
