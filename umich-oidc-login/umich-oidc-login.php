<?php
/**
 * UMich OIDC Login
 *
 * @package           UMich_OIDC_Login
 * @copyright         2022 Regents of the University of Michigan
 * @license           https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 *
 * @wordpress-plugin
 * Plugin Name:       UMich OIDC Login
 * Plugin URI:        https://github.com/its-webhosting/umich-oidc-login/
 * Description:       Restrict access to the whole site or only certain parts based on OpenID Connect (OIDC) login and group membership information.
 * Version:           1.2.0
 * Author:            Regents of the University of Michigan
 * Requires at least: 6.0.0
 * Requires PHP:      7.3
 * Tested up to:      6.3.1
 * Author URI:        https://umich.edu/
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       umich-oidc-login
 * Domain Path:       /languages
 */

/*
 * This plugin was written from an example plugin by Robert Morsali,
 * published under GPLv2 or later. See
 *
 * https://codeamp.com/using-php-namespaces-in-wordpress-plugins-creating-a
n-autoloader/
 * https://github.com/rmorse/speed-up
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only use this file if there is no conflict.
if ( ! function_exists( 'umich_oidc_login_run' ) ) {

	define( 'UMICH_OIDC_LOGIN_VERSION', '1.2.0' ); // X.Y.Z-a.
	define( 'UMICH_OIDC_LOGIN_VERSION_INT', 1020000 ); // XXYYZZaa.

	define( 'UMICH_OIDC_LOGIN_BASE_NAME', plugin_basename( __FILE__ ) );
	define( 'UMICH_OIDC_LOGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'UMICH_OIDC_LOGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

	require_once UMICH_OIDC_LOGIN_DIR . 'includes/core/polyfill.php';

	require_once UMICH_OIDC_LOGIN_DIR . 'build/vendor/scoper-autoload.php';
	require_once UMICH_OIDC_LOGIN_DIR . 'includes/core/autoload.php';
	require_once UMICH_OIDC_LOGIN_DIR . 'includes/core/functions.php';

	/**
	 * Glue function to call the code that performs custom actions
	 * during plugin activation.
	 *
	 * @return   void
	 */
	function umich_oidc_login_activate() {
		\UMich_OIDC_Login\Core\Setup::activate();
	}
	register_activation_hook( __FILE__, 'umich_oidc_login_activate' );

	/**
	 * Glue function to call the code that performs custom actions
	 * during plugin deactivation.
	 *
	 * @return   void
	 */
	function umich_oidc_login_deactivate() {
		\UMich_OIDC_Login\Core\Setup::deactivate();
	}
	register_deactivation_hook( __FILE__, 'umich_oidc_login_deactivate' );

	/**
	 * Initialize plugin functionality.
	 *
	 * @return   void
	 */
	function umich_oidc_login_run() {
		$plugin = new \UMich_OIDC_Login\Run();
	}
	add_action( 'init', 'umich_oidc_login_run' );

}
