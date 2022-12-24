<?php
/**
 * Autoload classes while complying with the WordPress Coding Standards
 *
 * @package    UMich_OIDC_Login
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 *
 * Bassd on autoloader code designed and written by Robert Morsali and
 * published under GPLv2 or later.
 *
 * @see https://codeamp.com/using-php-namespaces-in-wordpress-plugins-creating-an-autoloader/
 * @see https://github.com/rmorse/speed-up
 */

/**
 * Autoload class files.
 *
 * @param string $class_name name of the class to autoload.
 *
 * @return void
 */
function umich_oidc_login_autoloader( $class_name ) {

	$parent_namespace  = 'UMich_OIDC_Login';
	$classes_subfolder = 'includes';

	if ( false !== strpos( $class_name, $parent_namespace ) ) {
		$classes_dir = realpath( UMICH_OIDC_LOGIN_DIR ) . DIRECTORY_SEPARATOR . $classes_subfolder . DIRECTORY_SEPARATOR;

		$project_namespace = $parent_namespace . '\\';
		$length            = strlen( $project_namespace );

		// Remove top level namespace (that is the current dir).
		$class_file = substr( $class_name, $length );
		// Swap underscores for dashes and lowercase.
		$class_file = str_replace( '_', '-', strtolower( $class_file ) );

		// Prepend `class-` to the filename (last class part).
		$class_parts                = explode( '\\', $class_file );
		$last_index                 = count( $class_parts ) - 1;
		$class_parts[ $last_index ] = 'class-' . $class_parts[ $last_index ];

		// Join everything back together and add the file extension.
		$class_file = implode( DIRECTORY_SEPARATOR, $class_parts ) . '.php';
		$location   = $classes_dir . $class_file;

		if ( ! is_file( $location ) ) {
			return;
		}

		require_once $location;
	}
}

spl_autoload_register( 'umich_oidc_login_autoloader' );
