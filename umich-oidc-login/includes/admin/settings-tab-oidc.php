<?php
/**
 * UMich OIDC settings page - OIDC tab
 *
 * This file is required via includes/admin/class-settings.php
 *
 * @package    UMich_OIDC_Login\Admin
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

namespace UMich_OIDC_Login\Admin;

$redirect_url = \esc_attr( \admin_url( 'admin-ajax.php?action=openid-connect-authorize' ) );

$settings_tab_oidc = array(
	array(
		'id'   => 'redirect_uri',
		'name' => 'Redirect URI',
		'type' => 'html',
		'html' => "
<input type='text' name='redirect-uri-placeholder' id='redirect-uri-placeholder' class='optionskit-field-input' aria-details='redirect-uri-placeholder-help' value='$redirect_url' style='width: 100%;' readonly>
<div class='optionskit-field-help' id='redirect-uri-placeholder-help' style='font-size: smaller; margin-top: 0.5em;'>
You may need to provide this information to your OIDC Identity Provider (IdP) in order to register the website as an OIDC client / Relying Party (RP) and obtain a Client ID and Client Secret to use below. It is not currently posssible to customize this value.
</div>
		",
	),
	array(
		'id'   => 'provider_url',
		'name' => 'Identity Provider URL',
		'desc' => 'Base URL of the OIDC Identity Provider (IdP) that users will use to log in to WordPress.<br /><b>Example:</b> <code>https://sign-in.example.com</code>',
		'type' => 'text',
	),
	array(
		'id'   => 'client_id',
		'name' => 'Client ID',
		'desc' => 'Tells the IdP which website this is. Paste the OIDC Client ID here that you receied when you registered this website with the IdP.',
		'type' => 'text',
	),
	array(
		'id'   => 'client_secret',
		'name' => 'Client Secret',
		'desc' => 'Proves the identity of this website to the IdP. Keep this secret, it is a password. Paste the OIDC Client Secret here that you receied when you registered this website with the IdP.',
		'type' => 'text',
	),
	array(
		'id'   => 'available_groups',
		'name' => 'Groups for Authorization',
		'desc' => 'One or more group names to use for authorization, separated by commas. The name of a group can contain spaces.  These group names are only used as a list of choices to select from in access restriction menus; ensure they match the groups that the Identity Provider sends to the website.<br /><br /><b style="background-color: #FFFFCC;">IMPORTANT NOTE: only the <i>official</i> name of the group will work.  The "also known as" names for the group will not work.  University of Michigan users can find the official name for a group on the group\'s MCommunity page, in large type at the top of the main section.</b><br /><br />After entering or changing these group(s), you must click the "Save Changes" button to make the groups available in other fields.',
		'type' => 'text',
	),
	array(
		'id'      => 'client_auth_method',
		'name'    => 'Client Authentication Method',
		'desc'    => 'How this WordPress OIDC client will authenticate to the OIDC IdP. If unsure, select <code>client_secret_post</code>.',
		'type'    => 'select',
		'options' => array(
			'client_secret_post'  => 'client_secret_post',
			'client_secret_basic' => 'client_secret_basic',
		),
		'std'     => $option_defaults['client_auth_method'],
	),
	array(
		'id'   => 'scopes',
		'name' => 'Scopes',
		'desc' => 'Space-separated list of the types of information to request from the IdP for each user that logs in.',
		'type' => 'text',
		'std'  => $option_defaults['scopes'],
	),
	array(
		'id'   => 'redirect_uri_description',
		'name' => '',
		'type' => 'html',
		'html' => "
<div style='font-size: smaller'>
<b>Example:</b> <code>openid email profile edumember</code><br />
<ul style='list-style-type: disc;'>
		<li><code>openid</code> - required for WordPress to authenticate users via OIDC, provides the user\'s username</li>
		<li><code>email</code> - gets the user\'s email address</li>
		<li><code>profile</code> - gets the user\'s full name (display name), given name, and family name</li>
		<li><code>edumember</code> - needed for WordPress to receive group membership information from Shibboleth</li></ul>
</div>
		",
	),
	array(
		'id'   => 'claim_mappings_subsection',
		'name' => 'OIDC CLAIM MAPPINGS',
		'type' => 'html',
		'html' => '<hr style="border: 2px solid #cccccc;" /><p>Specify the name of the OIDC claim that the IdP uses for each piece of information.  The name on the left can also be used in UMich OIDC shortcodes to retrieve that information.</p><p><b>Example:</b> If the <code>username</code> field below is set to <code>preferred_name</code> then the shortcode <code>[umich_oidc_user_info type="username"]</code> will be replaced with the value that the IdP returned in its <code>preferred_username</code> claim for the currently authenticated user.</p>',
	),
	array(
		'id'   => 'claim_for_username',
		'name' => 'username',
		'desc' => 'Name of OIDC userinfo claim for the user\'s login id.<br /><b>Example:</b><code>preferred_username</code> or <code>sub</code>',
		'type' => 'text',
		'std'  => $option_defaults['claim_for_username'],
	),
	array(
		'id'   => 'claim_for_email',
		'name' => 'email',
		'desc' => 'Name of OIDC userinfo claim for the user\'s email address.<br /><b>Example:</b><code>email</code>',
		'type' => 'text',
		'std'  => $option_defaults['claim_for_email'],
	),
	array(
		'id'   => 'claim_for_full_name',
		'name' => 'full_name',
		'desc' => 'Name of OIDC userinfo claim for the user\'s full name, display name, or preferred name.<br /><b>Example:</b><code>name</code>',
		'type' => 'text',
		'std'  => $option_defaults['claim_for_full_name'],
	),
	array(
		'id'   => 'claim_for_given_name',
		'name' => 'given_name',
		'desc' => 'Name of OIDC userinfo claim for the user\'s given or first name.<br /><b>Example:</b><code>given_name</code>',
		'type' => 'text',
		'std'  => $option_defaults['claim_for_given_name'],
	),
	array(
		'id'   => 'claim_for_family_name',
		'name' => 'family_name',
		'desc' => 'Name of OIDC userinfo claim for the user\'s family name or surname.<br /><b>Example:</b><code>family_name</code>',
		'type' => 'text',
		'std'  => $option_defaults['claim_for_family_name'],
	),
	array(
		'id'   => 'claim_for_groups',
		'name' => 'groups',
		'desc' => 'Name of OIDC userinfo claim for the groups the user belongs to.<br /><b>Example:</b><code>edumember_ismemberof</code>',
		'type' => 'text',
		'std'  => $option_defaults['claim_for_groups'],
	),
);
