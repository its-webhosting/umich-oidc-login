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
		'id'   => 'link_accounts',
		'name' => 'Use OIDC for WordPress Logins',
		'desc' => '
Users who have WordPress accounts log in to them using OIDC rather than their WordPress password.
<p><b>IMPORTANT:</b> Before turning this on,</p>
<ul style="list-style-type: disc;">
<li>Make sure OIDC authentication is working correctly for OIDC / non-WordPress users. If it is not, turning on this setting will make it impossible for WordPress users to log in.</li>
<li>Make sure the username for each user\'s WordPress account is the same as their OIDC username. WordPress accounts with usernames that are different from the person\'s OIDC username will either not be accessible at all, or will be accessible by a different person than intended.</li>
</ul>
<p>If you lock yourself out of your WordPress account by turning this on, you can run the following commands to turn it off again and set a password (if you don\'t know it) so you can log in again directly through WordPress.  Copy and paste these commands into a note so you will have them if you need them (they are also available in the documentation for this plugin).</p>
<pre>
wp option patch delete umich_oidc_settings link_accounts
wp user reset-password YOUR_USERNAME
</pre>
		',
		'type' => 'checkbox',
	),
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
		'std'     => 'setting',
	),
	array(
		'id'   => 'login_return_url',
		'name' => 'Login Destination URL',
		'desc' => 'URL to send users to after they successfullly log in. This is only used if the Post-Login Action is set to "URL" above.  Can be a full URL (starting with "https://") or a site URL path (such as "/some/page").',
		'type' => 'text',
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
		'std'     => 'smart',
	),
	array(
		'id'   => 'logout_return_url',
		'name' => 'Logout Destination URL',
		'desc' => 'URL to send users to after they log out. If blank, the user will be sent back to the site\'s main page. If this site is not accessible by everyone, it is a good idea to put the URL for a public web page (one that is accessible by everyone without login) here. Can be a full URL (starting with "https://") or a site URL path (such as "/some/page").',
		'type' => 'text',
	),
	array(
		'id'   => 'available_groups',
		'name' => 'Groups for Authorization',
		'desc' => 'One or more group names to use for authorization, separated by commas. The name of a group can contain spaces.<p><b style="background-color: #FFFFCC;">IMPORTANT NOTE: only the <i>official</i> name of the group will work.  The "also known as" names for the group will not work.  University of Michigan users can find the official name for a group on the group\'s MCommunity page, in large type at the top of the main section.</b></p><p>After entering or changing these group(s), you must click the "Save Changes" button in order to make these groups available in the fields below.</p>',
		'type' => 'text',
	),
	array(
		'id'       => 'restrict_site',
		'name'     => 'Who can access this site?',
		'desc'     => 'Allow only members of these groups (plus administrators) to access this site.',
		'type'     => 'multiselect',
		'multiple' => true,
		'labels'   => array( 'placeholder' => 'Select one or more groups' ),
		'options'  => $this->available_groups(),
		'std'      => '_everyone_',
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
		'std'     => '86400',
	),
);
