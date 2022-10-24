<?php
/**
 * Error information pages.
 *
 * @package    UMich_OIDC_Login\Admin
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 * @since      1.0.0
 *
 * @see
 * https://codex.wordpress.org/Integrating_WordPress_with_Your_Website
 *
 * TODO: make sure this works on a MultiSite Network, or replace with something
 * that gives a PHP file to the template_include hook similar to
 * https://plugins.trac.wordpress.org/browser/maintenance/trunk/maintenance.php
 */

define( 'WP_USE_THEMES', false );

require '../../../wp-blog-header.php';

// Workaround for 404 errors. See
// https://stackoverflow.com/questions/2810124/how-can-i-add-a-php-page-to-wordpress/39800534#39800534 .
header( 'HTTP/1.1 200 OK' );
header( 'Status: 200 OK' );

if ( PHP_SESSION_NONE === session_status() ) {
	session_start();
}

if ( array_key_exists( 'umich_oidc_error_header', $_SESSION ) ) {
	$error_header = $_SESSION['umich_oidc_error_header'];
	unset( $_SESSION['umich_oidc_error_header'] );
} else {
	$error_header = 'Unknown authentication failure';
}

if ( array_key_exists( 'umich_oidc_error_details', $_SESSION ) ) {
	$error_details = $_SESSION['umich_oidc_error_details'];
	unset( $_SESSION['umich_oidc_error_details'] );
} else {
	$error_details = '';
}

// In case this is a MultiSite Network.
if ( array_key_exists( 'umich_oidc_error_site', $_SESSION ) ) {
	$site = $_SESSION['umich_oidc_error_site'];
	unset( $_SESSION['umich_oidc_error_site'] );
} else {
	$site = site_url();
}
if ( array_key_exists( 'umich_oidc_error_home', $_SESSION ) ) {
	$home = $_SESSION['umich_oidc_error_home'];
	unset( $_SESSION['umich_oidc_error_home'] );
} else {
	$home = home_url();
}

session_write_close();

$help  = '';
$email = get_option( 'admin_email' );
if ( is_string( $email ) && '' !== $email ) {
	$email = esc_html( $email );
	$help  = "For assitance or to report a problem, contact <a href='mailto:{$email}'>{$email}.";
}

// Most of the HTML and CSS below come from viewing the page source generated by wp_die().
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width">
		<meta name='robots' content='max-image-preview:large, noindex, follow' />
	<title>WordPress &rsaquo; Error</title>
	<style type="text/css">
		html {
			background: #f1f1f1;
		}
		body {
			background: #fff;
			border: 1px solid #ccd0d4;
			color: #444;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			margin: 2em auto;
			padding: 1em 2em;
			max-width: 700px;
			-webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
			box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
		}
		h1 {
			border-bottom: 1px solid #dadada;
			clear: both;
			color: #666;
			font-size: 24px;
			margin: 30px 0 0 0;
			padding: 0;
			padding-bottom: 7px;
		}
		#error-page {
			margin-top: 50px;
		}
		#error-page p,
		#error-page .wp-die-message {
			font-size: 14px;
			line-height: 1.5;
			margin: 25px 0 20px;
		}
		#error-page code {
			font-family: Consolas, Monaco, monospace;
		}
		ul li {
			margin-bottom: 10px;
			font-size: 14px ;
		}
		a {
			color: #0073aa;
		}
		a:hover,
		a:active {
			color: #006799;
		}
		a:focus {
			color: #124964;
			-webkit-box-shadow:
				0 0 0 1px #5b9dd9,
				0 0 2px 1px rgba(30, 140, 190, 0.8);
			box-shadow:
				0 0 0 1px #5b9dd9,
				0 0 2px 1px rgba(30, 140, 190, 0.8);
			outline: none;
		}
		.button {
			background: #f3f5f6;
			border: 1px solid #016087;
			color: #016087;
			display: inline-block;
			text-decoration: none;
			font-size: 13px;
			line-height: 2;
			height: 28px;
			margin: 0;
			padding: 0 10px 1px;
			cursor: pointer;
			-webkit-border-radius: 3px;
			-webkit-appearance: none;
			border-radius: 3px;
			white-space: nowrap;
			-webkit-box-sizing: border-box;
			-moz-box-sizing:    border-box;
			box-sizing:         border-box;

			vertical-align: top;
		}

		.button.button-large {
			line-height: 2.30769231;
			min-height: 32px;
			padding: 0 12px;
		}

		.button:hover,
		.button:focus {
			background: #f1f1f1;
		}

		.button:focus {
			background: #f3f5f6;
			border-color: #007cba;
			-webkit-box-shadow: 0 0 0 1px #007cba;
			box-shadow: 0 0 0 1px #007cba;
			color: #016087;
			outline: 2px solid transparent;
			outline-offset: 0;
		}

		.button:active {
			background: #f3f5f6;
			border-color: #7e8993;
			-webkit-box-shadow: none;
			box-shadow: none;
		}

			</style>
</head>
<body id="error-page">
	<div class="wp-die-message">
		<h1><?php echo esc_html( $error_header ); ?></h1>
		<p>We're sorry for the problem.  Please try the options below. <?php echo wp_kses( $help, 'data' ); ?></p>
		<ul>
			<li><a href="<?php echo esc_url( $home ); ?>">Go to the main page</a></li>
			<li><a href="<?php echo esc_url( $site . '/wp-admin/admin-ajax.php?action=openid-connect-authorize' ); ?>">Try logging in again</a></li>
		</ul>
		<?php if ( '' !== $error_details ) : ?>
			<p>Technical details:</p>
			<code><?php echo esc_html( $error_details ); ?></code>
		<?php endif; ?>
	</div>
</body>
</html>
