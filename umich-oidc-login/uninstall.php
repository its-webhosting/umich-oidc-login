<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    UMich_OIDC_Login\Admin
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 *
 * The initial version of this code came from Better WordPress Plugin
 * Boilerplate, maintained by TukuToi and published under GPLv2 or later.
 *
 * @see https://github.com/TukuToi/better-wp-plugin-boilerplate
 *
 * We are not using an uninstall hook because WordPress perfoms bad when using it.
 * Even if below issue is "fixed", it did not resolve the perfomance issue.
 *
 * @see https://core.trac.wordpress.org/ticket/31792
 *
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - Check if the $_REQUEST['plugin'] content actually is umich-oidc-login/umich-oidc-login.php
 * - Check if the $_REQUEST['action'] content actually is delete-plugin
 * - Run a check_ajax_referer check to make sure it goes through authentication
 * - Run a current_user_can check to make sure current user can delete a plugin
 *
 * @todo Consider multisite. Once for a single site in the network, once sitewide.
 */

/**
 * Perform Uninstall Actions.
 *
 * If uninstall not called from WordPress,
 * If no uninstall action,
 * If not this plugin,
 * If no caps,
 * then exit.
 *
 * @return void
 */
function umich_oidc_login_uninstall() {

	if ( ! defined( 'WP_UNINSTALL_PLUGIN' )
		|| empty( $_REQUEST )
		|| ! isset( $_REQUEST['plugin'] )
		|| ! isset( $_REQUEST['action'] )
		|| 'umich-oidc-login/umich-oidc-login.php' !== $_REQUEST['plugin']
		|| 'delete-plugin' !== $_REQUEST['action']
		|| ! check_ajax_referer( 'updates', '_ajax_nonce' )
		|| ! current_user_can( 'activate_plugins' )
	) {

		exit;

	}

	/**
	 * It is now safe to perform your uninstall actions here.
	 *
	 * @see https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/#method-2-uninstall-php
	 */
}

umich_oidc_login_uninstall();
