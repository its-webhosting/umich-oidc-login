<?php
/**
 * UMich OIDC settings page - General tab
 *
 * This file is required via includes/admin/class-settings.php
 *
 * @package    UMich_OIDC_Login\Admin
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

namespace UMich_OIDC_Login\Admin;

$settings_tab_general = array(
	array(
		'id'      => 'use_oidc_for_wp_users',
		'name'    => 'Use OIDC for WordPress Users',
		'desc'    => '',
		'type'    => 'radio',
		'options' => array(
			'no'       => 'NO: Require WordPress users to use their WordPress password, even if they are already logged in to the website using OIDC.',
			'optional' => 'OPTIONAL: Allow people to log in to the WordPress dashboard using either OIDC or their WordPress password.  Using OIDC will log them in to both the website and the WordPress dashboard, while using their WordPress password will log them into WordPress but not log them in to the website.  WARNING: you need to make sure the OIDC user and the WordPress user are always the same person! Don\'t create a WordPress user using a different person\'s OIDC username.',
			'yes'      => 'YES: Require WordPress users to use OIDC to log in to the WordPress dashboard.  This will also log them in to the website.',
		),
		'std'     => $option_defaults['use_oidc_for_wp_users'],
	),
	array(
		'id'   => 'use_oidc_for_wp_users_description',
		'name' => '',
		'type' => 'html',
		'html' => '
<div style="font-size: smaller">
<b>IMPORTANT:</b> Before setting this to YES,<br />
<ul style="list-style-type: disc; margin: 2px 1em;">
<li>Make sure you can log in to the website using OIDC. Otherwise, you will lock yourself out of your WordPress dashboard.</li>
<li>Make sure the username for each WordPress user is the same as their OIDC username. WordPress users with usernames that are different from the person\'s OIDC username will either not be accessible at all, or will be accessible by a different person than you intend.</li>
</ul>
<p>If you lock yourself out of your WordPress dashboard, you can run the following <a href="https://wp-cli.org">WP-CLI</a> commands to re-enable WordPress user passwords.  Copy and paste these commands into a note so you will have them if you need them (they are also available in the <a href="https://wordpress.org/plugins/umich-oidc-login/#faq">plugin FAQ</a>).</p>
<pre>
# Re-enable WordPress passwords:
wp option patch delete umich_oidc_settings use_oidc_for_wp_users
# Set a new WordPress password if you forgot it:
wp user update YOUR-WORDPRESS-USERNAME --user_pass="PUT-YOUR-NEW-PASSWORD-HERE"
</pre>
</div>
		',
	),
	defined('UMICH_OIDC_BETA_ENABLE_WP_ACCOUNTS') && UMICH_OIDC_BETA_ENABLE_WP_ACCOUNTS ? array(
		'id'    => 'create_wp_user_for_oidc_user_beta',
		'name'  => 'Automatically create WordPress user accounts',
		'label' => 'Create a WordPress user with role "Subscriber" for people who log in using OIDC',
		'desc'  => '
If enabled, people who log in via OIDC will have a WordPress user account automatically created for them if they don\'t
already have one. The new accounts will be assigned the "Subscriber" role, and their passwords will be randomized and discarded.
<br /><br />
This setting requires that "Use OIDC for WordPress Users" be set to "OPTIONAL" or "YES" above. The WordPress password
or role can be changed after the account is created, and those changes will persist.
<br /><br />
<b></b>NOTE:</b> This is a beta feature available only available to sites that specifically enable it in
<code>wp-config.php</code>. The production version of this feature will be considerably more flexible.
',
		'type'  => 'toggle',
		'std'   => $option_defaults['create_wp_user_for_oidc_user_beta'],
	) : array('id' => 'create_wp_user_for_oidc_user_beta', 'name' => '', 'type' => 'html', 'html' => ''),
	array(
		'id'      => 'login_action',
		'name'    => 'Post-Login Action',
		'desc'    => 'Where to send people after they successfully log in, if not otherwise specified by the logout button/link/URL.',
		'type'    => 'select',
		'options' => array(
			'setting' => 'URL: Login Destination URL if set below, or page they were on when they logged in',
			'here'    => 'Here: Same page they were on when they logged in',
			'home'    => 'Home: Site home page',
		),
		'std'     => $option_defaults['login_action'],
	),
	array(
		'id'       => 'login_return_url',
		'name'     => 'Login Destination URL',
		'desc'     => 'URL to send users to after they successfully log in. This is only used if the Post-Login Action is set to "URL" above.  Can be a full URL (starting with "https://") or a site URL path (such as "/some/page").',
		'type'     => 'text',
		'validate' => 'umichOidcSettings.validateUrlOrPath',
	),
	array(
		'id'      => 'logout_action',
		'name'    => 'Post-Logout Action',
		'desc'    => 'Where to send people after they successfully log out, if not otherwise specified by the logout button/link/URL.<br /><b>NOTE:</b> "Home" and "Here" will log the user in again if the pages are not publicly accessible.',
		'type'    => 'select',
		'options' => array(
			'smart'   => 'Smart: Same page they logged out from if public, or logout URL if set below, or site home',
			'setting' => 'URL: Logout Destination URL if set below, or site home',
			'home'    => 'Home: Site home page',
			'here'    => 'Here: Same page they were on when they logged out',
		),
		'std'     => $option_defaults['logout_action'],
	),
	array(
		'id'       => 'logout_return_url',
		'name'     => 'Logout Destination URL',
		'desc'     => 'URL to send users to after they log out. If blank, the user will be sent back to the site\'s main page. If this site is not accessible by everyone, it is a good idea to put the URL for a public web page (one that is accessible by everyone without login) here. Can be a full URL (starting with "https://") or a site URL path (such as "/some/page").',
		'type'     => 'text',
		'validate' => 'umichOidcSettings.validateUrlOrPath',
	),
	array(
		'id'       => 'restrict_site',
		'name'     => 'Who can access this site?',
		'desc'     => 'Allow only members of these groups (plus administrators) to access this site. Which groups show up here is determined by the <a href="#oidc/available_groups">Groups for Authorization</a> setting.',
		'type'     => 'multiselect',
		'labels'   => array( 'placeholder' => 'Select one or more groups...' ),
		'options'  => $this->available_groups(),
		'validate' => 'umichOidcSettings.validateRestrictSite',
		'std'      => $option_defaults['restrict_site'],
	),
	array(
		'id'      => 'session_length',
		'name'    => 'Session Length',
		'desc'    => 'How long OIDC and WordPress login sessions last before the user needs to log in again.',
		'type'    => 'select',
		'options' => array(
			'28800' => '8 hours',
			'43200' => '12 hours',
			'86400' => '24 hours',
		),
		'std'     => $option_defaults['session_length'],
	),
	array(
		'id'    => 'autosave',
		'name'  => 'Autosave plugin settings',
		'label' => 'Autosave',
		'desc'  => '
If enabled, changes to UMich OIDC Login plugin settings will be saved automatically as soon as they are made.
This applies both to <code>Settings &mdash;&gt; UMich OIDC Login</code> pages as well as when creating or editing pages
and posts.
<br /><br />
If disabled, you will need to click the "Save Changes" button on the plugin settings page or the "Save" button
for a page or post in order to save UMich OIDC Login changes you have made.
',
		'type'  => 'toggle',
		'std'   => $option_defaults['autosave'],
	),
	array(
		'id'    => 'test_prereleases',
		'name'  => 'Upgrading',
		'label' => 'Test pre-releases',
		'desc'  => '
Enable this option to opt-in to testing new beta and release candidate versions of the UMich OIDC Login plugin ahead
of official releases.  By turning this setting on for development, testing, or staging websites, you can help ensure any
problems get addressed before production (live) websites get the new version of this plugin.  Pre-releases are usually
available for <i>one week</i> in advance of official releases unless a problem is found.  You can get notified when new
releases and pre-releases are available by going to the <a href="https://github.com/its-webhosting/umich-oidc-login/">
UMich OIDC Login GitHub repository</a>, logging in, and turning on Watch -> Custom -> Releases.
<br /><br />
<b>NOTE:</b> If you manually install a pre-release version of the plugin, this setting will get turned on automatically
so you will also get future pre-releases.  If that\'s not what you want, turn this setting off after manually
installing the pre-release.
',
		'type'  => 'toggle',
		'std'   => $option_defaults['test_prereleases'],
	),
);
