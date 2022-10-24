<?php
/**
 * Admin dashboard settings page.
 *
 * @package    UMich_OIDC_Login\Admin
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 * @since      1.0.0
 */

namespace UMich_OIDC_Login\Admin;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function UMich_OIDC_Login\Core\log_message as log_message;

/**
 * Admin dashboard settings page
 *
 * @package    UMich_OIDC_Login\Admin
 * @since      1.0.0
 */
class Settings_Page {

	/**
	 * Context for this WordPress request / this run of the plugin.
	 *
	 * @var      object    $ctx    Context passed to us by our creator.
	 *
	 * @since    1.0.0
	 */
	private $ctx;

	/**
	 * Holds the options panel controller.
	 *
	 * @var object $panel
	 *
	 * @since    1.0.0
	 */
	protected $panel;

	/**
	 * Settings page object.
	 *
	 * @param object $ctx Context for this WordPress request / this run of the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $ctx ) {

		$this->ctx = $ctx;

		$this->panel = new \UMich_OIDC\Vendor\TDP\OptionsKit( 'umich_oidc' );
		$this->panel->set_page_title( 'UMich OIDC Login Settings' );

		// Setup the options panel menu.
		\add_filter( 'umich_oidc_menu', array( $this, 'setup_menu' ) );

		// Register settings tabs.
		\add_filter( 'umich_oidc_settings_tabs', array( $this, 'register_settings_tabs' ) );
		\add_filter( 'umich_oidc_registered_settings_sections', array( $this, 'register_settings_subsections' ) );

		// Register settings fields for the options panel.
		\add_filter( 'umich_oidc_registered_settings', array( $this, 'register_settings' ) );

		\add_filter( 'umich_oidc_settings_sanitize_provider_url', array( $this, 'sanitize_provider_url' ), 3, 10 );
		\add_filter( 'umich_oidc_settings_sanitize_scopes', array( $this, 'sanitize_scopes' ), 3, 10 );
		\add_filter( 'umich_oidc_settings_sanitize_login_return_url', array( $this, 'sanitize_url' ), 3, 10 );
		\add_filter( 'umich_oidc_settings_sanitize_logout_return_url', array( $this, 'sanitize_url' ), 3, 10 );
		\add_filter( 'umich_oidc_settings_sanitize_restrict_site', array( $this, 'sanitize_group_choices' ), 3, 10 );

		\add_action( 'admin_notices', array( $this, 'admin_notices' ) );

	}

	/**
	 * Setup the menu for the options panel.
	 *
	 * @param array $menu original settings of the menu.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function setup_menu( $menu ) {
		return array(
			'parent'     => 'options-general.php',
			'page_title' => 'UMich OIDC Login',
			'menu_title' => 'UMich OIDC Login',
			'capability' => 'manage_options',
		);
	}

	/**
	 * Register settings tabs for the options panel.
	 *
	 * @param array $tabs Default tabs.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function register_settings_tabs( $tabs ) {
		$tabs = array(
			'general'    => 'General',
			'oidc'       => 'OIDC',
			'shortcodes' => 'Shortcodes',
		);
		return $tabs;
	}

	/**
	 * Register subsections for the option tabs.
	 *
	 * @param array $sections Default sections.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function register_settings_subsections( $sections ) {
		return $sections;
	}

	/**
	 * Return list of groups for restricting access to a post.
	 *
	 * @param int $id ID of the post to return the access list for.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function post_access_groups( $id ) {

		$access = \get_post_meta( $id, '_umich_oidc_access', true );
		log_message( "access list for post {$id}: \"{$access}\"" );

		// If $access is empty, explode() will return an array with
		// one element that is an empty string.
		if ( '' === $access ) {
			return array();
		}
		$access = \array_map( '\trim', \explode( ',', $access ) );

		return $access;

	}

	/**
	 * Return list of available groups for use in a multiselect field.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function available_groups() {

		$groups = array(
			array(
				'value' => '_everyone_',
				'label' => '( Everyone )',
			),
			array(
				'value' => '_logged_in_',
				'label' => '( Logged-in Users )',
			),
		);

		$options = $this->ctx->options;
		if ( ! \array_key_exists( 'available_groups', $options ) || ! \is_string( $options['available_groups'] ) ) {
			return $groups;
		}
		$available = \array_map( '\trim', \explode( ',', $options['available_groups'] ) );
		foreach ( $available as $a ) {
			if ( '' !== $a ) {
				$v        = \esc_attr( $a );
				$groups[] = array(
					'value' => $v,
					'label' => $v,
				);
			}
		}

		return $groups;

	}

	/**
	 * Register settings for the plugin.
	 *
	 * @param array $settings Default settings.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function register_settings( $settings ) {

		$redirect_url = \esc_attr( \admin_url( 'admin-ajax.php?action=openid-connect-authorize' ) );

		$loginlogout_type_attribute_description = <<<'END'
<b>type</b> - <i>Optional, defaults to "login-logout".</i> What the shortcode should do.  Can be any of the following:
<table style="margin-left: 1em;">
<tr><th style="padding: 4px;">login</th><td style="padding: 4px;">Log the user in.</td></tr>
<tr><th style="padding: 4px;">logout</th><td style="padding: 4px;">Log the user out.<p><b>Caution:</b> if the <code>return</code> parameter takes the user to a page that is not public and auto-login is enabled, the user may be automatically logged in again, defeating the purpose of the logout.</p></td></tr>
<tr><th style="padding: 4px;">login-logout</th><td style="padding: 4px;">If the user is not logged in, log them in.  If the user is logged in, log them out.</td></tr>
</table>
END;

		$return_attribute_description = <<<'END'
<b>return</b> - <i>Optional. Defaults to "setting" for login URLs, "smart" for logout URLs.</i> Where to send the user after they successfully login or logout.  Can be any of the following:
<table style="margin-left: 1em;">
<tr><th style="padding: 4px;">smart</th><td style="padding: 4px;"><i>Can only be used for logout.</i> Send the user back to the page the shortcode is on, if that page is public (accessible by everyone without logging in). Otherwise, send the user to the <a href="#/general">Logout Destination URL</a>, if one has been set.  Otherwise, send the user to the site's main page.</td></tr>
<tr><th style="padding: 4px;">setting</th><td style="padding: 4px;">Send the user to the <a href="#/general">Login Destination URL</a> (when logging in) or <a href="#/general">Logout Destination URL</a> (when logging out), if the appropriate one has been set.  Otherwise, send the user to the site's main page.</td></tr>
<tr><th style="padding: 4px;">here</th><td style="padding: 4px;">Send the user back to the page the shortcode was on.</td></tr>
<tr><th style="padding: 4px;">home</th><td style="padding: 4px;">Send the user to the site's main page.</td></tr>
<tr><th style="padding: 4px;"><i>URL</i></th><td style="padding: 4px;">Send the user to the specified URL within the site.  Can be either a full URL within the site (starting with "https://"), or a URL path on the site (starting with "/"). If <code>return</code> starts with anything else, the user will be sent to the site's main page. <b>NOTE:</b> <i>Due to security constraints, only URLs for the site will work.  URLs for things outside the site will generate an error.</i></td></tr>
</table>
END;

		$settings = array(
			// General tab settings.
			'general'    => array(
				array(
					'id'   => 'link_accounts',
					'name' => 'Use OIDC for WordPress Logins',
					'desc' => <<<'END'
Users who have WordPress accounts log in to them using OIDC rather than their WordPress password.
<p><b>IMPORTANT:</b> Before turning this on,</p>
<ul style="list-style-type: disc;">
<li>Make sure OIDC authentication is working correctly for OIDC / non-WordPress users. If it is not, turning on this setting will make it impossible for WordPress users to log in.</li>
<li>Make sure the username for each user's WordPress account is the same as their OIDC username. WordPress accounts with usernames that are different from the person\'s OIDC username will either not be accessible at all, or will be accessible by a different person than intended.</li>
</ul>
<p>If you lock yourself out of your WordPress account by turning this on, you can run the following commands to turn it off again and set a password (if you don't know it) so you can log in again directly through WordPress.  Copy and paste these commands into a note so you will have them if you need them (they are also available in the documentation for this plugin).</p>
<pre>
wp option patch delete umich_oidc_settings link_accounts
wp user reset-password YOUR_USERNAME
</pre>
END
					,
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
			),
			// OIDC tab settings.
			'oidc'       => array(
				array(
					'id'   => 'redirect_uri',
					'name' => 'Redirect URI',
					'type' => 'html',
					'html' => <<<"END"
<input type="text" name="redirect-uri-placeholder" id="redirect-url-placeholder" class="opk-field regular-text" value="$redirect_url" style="width: 100%;" readonly>
<br />
<p>You may need to provide this information to your OIDC Identity Provider (IdP) in order to register the website as an OIDC client / Relying Party (RP) and obtain a Client ID and Client Secret to use below. It is not currently posssible to customize this value.</p>
END
					,
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
					'id'      => 'client_auth_method',
					'name'    => 'Client Authentication Method',
					'desc'    => 'How this WordPress OIDC client will authenticate to the OIDC IdP. If unsure, select <code>client_secret_post</code>.',
					'type'    => 'select',
					'options' => array(
						'client_secret_post'  => 'client_secret_post',
						'client_secret_basic' => 'client_secret_basic',
					),
					'std'     => 'client_secret_post',
				),
				array(
					'id'   => 'scopes',
					'name' => 'Scopes',
					'desc' => 'Space-separated list of the types of information to request from the IdP for each user that logs in.<br /><b>Example:</b> <code>openid email profile edumember</code><br><ul style="list-style-type: disc;">
					<li><code>openid</code> - required for WordPress to authenticate users via OIDC, provides the user\'s username</li>
					<li><code>email</code> - gets the user\'s email address</li>
					<li><code>profile</code> - gets the user\'s full name (display name), given name, and family name</li>
					<li><code>edumember</code> - needed for WordPress to receive group membership information from Shibboleth</li></ul>',
					'type' => 'text',
					'std'  => 'openid email profile edumember',
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
					'std'  => 'preferred_username',
				),
				array(
					'id'   => 'claim_for_email',
					'name' => 'email',
					'desc' => 'Name of OIDC userinfo claim for the user\'s email address.<br /><b>Example:</b><code>email</code>',
					'type' => 'text',
					'std'  => 'email',
				),
				array(
					'id'   => 'claim_for_full_name',
					'name' => 'full_name',
					'desc' => 'Name of OIDC userinfo claim for the user\'s full name, display name, or preferred name.<br /><b>Example:</b><code>name</code>',
					'type' => 'text',
					'std'  => 'name',
				),
				array(
					'id'   => 'claim_for_given_name',
					'name' => 'given_name',
					'desc' => 'Name of OIDC userinfo claim for the user\'s given or first name.<br /><b>Example:</b><code>given_name</code>',
					'type' => 'text',
					'std'  => 'given_name',
				),
				array(
					'id'   => 'claim_for_family_name',
					'name' => 'family_name',
					'desc' => 'Name of OIDC userinfo claim for the user\'s family name or surname.<br /><b>Example:</b><code>family_name</code>',
					'type' => 'text',
					'std'  => 'family_name',
				),
				array(
					'id'   => 'claim_for_groups',
					'name' => 'groups',
					'desc' => 'Name of OIDC userinfo claim for the groups the user belongs to.<br /><b>Example:</b><code>edumember_ismemberof</code>',
					'type' => 'text',
					'std'  => 'edumember_ismemberof',
				),
			),
			'shortcodes' => array(
				array(
					'id'   => 'shortcodes_subsection',
					'name' => 'SHORTCODES',
					'type' => 'html',
					'html' => <<<'END'
<p>The shortcodes below can be used in your content, widgets, and themes to control what shows up for different users.</p>
<table style="margin-left: 1em;">
<tr><th style="padding: 4px;"><a onclick="document.getElementById('umich_oidc_button').scrollIntoView(); return false;"><code>umich_oidc_button</code></a></th><td style="padding: 4px;">Generate a login or logout button.</td></tr>
<tr><th style="padding: 4px;"><a onclick="document.getElementById('umich_oidc_link').scrollIntoView(); return false;"><code>umich_oidc_link</code></a></th><td style="padding: 4px;">Generate a login or logout link.</td></tr>
<tr><th style="padding: 4px;"><a onclick="document.getElementById('umich_oidc_logged_in').scrollIntoView(); return false;"><code>umich_oidc_logged_in</code></a></th><td style="padding: 4px;">Show content only if the visitor is logged in.</td></tr>
<tr><th style="padding: 4px;"><a onclick="document.getElementById('umich_oidc_member').scrollIntoView(); return false;"><code>umich_oidc_member</code></a></th><td style="padding: 4px;">Show content only if the visitor is a member of one or more groups.</td></tr>
<tr><th style="padding: 4px;"><a onclick="document.getElementById('umich_oidc_not_logged_in').scrollIntoView(); return false;"><code>umich_oidc_not_logged_in</code></a></th><td style="padding: 4px;">Show content only if the visitor is NOT logged in.</td></tr>
<tr><th style="padding: 4px;"><a onclick="document.getElementById('umich_oidc_not_member').scrollIntoView(); return false;"><code>umich_oidc_not_member</code></a></th><td style="padding: 4px;">Show content only if the visitor NOT a member of any of the specified groups.</td></tr>
<tr><th style="padding: 4px;"><a onclick="document.getElementById('umich_oidc_url').scrollIntoView(); return false;"><code>umich_oidc_url</code></a></th><td style="padding: 4px;">Generate a login or logout URL.</td></tr>
<tr><th style="padding: 4px;"><a onclick="document.getElementById('umich_oidc_userinfo').scrollIntoView(); return false;"><code>umich_oidc_userinfo</code></a></th><td style="padding: 4px;">Display information about the currently-logged-in OIDC user.</td></tr>
</table>
END
					,
				),
				array(
					'id'   => 'shortcodes_button',
					'name' => 'umich_oidc_button',
					'type' => 'html',
					'html' => <<<END
<div id="umich_oidc_button"><code>[umich_oidc_button type="login" text="Log in" return="here" <i>HTML_ATTRIBUTES</i>]</code></div>
<p>Generates a login or logout button.</p>
<p><b>Example:</b> <code>[umich_oidc_button type="login"]</code> will be replaced by a button that users can use to log in.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li>{$return_attribute_description}</li>
<li><b>text</b> - <i>Optional, defaults to "Log in" for login, or "Log out" for logout.</i> Controls the text that the button displays.</li>
<li><b>text_login</b> - <i>Optional, defaults "Log in".</i> Controls the text that the <code>type="login-logout"</code> link displays for logins.</li>
<li><b>text_logout</b> - <i>Optional, defaults "Log out".</i> Controls the text that the <code>type="login-logout"</code> logout link displays for logouts.</li>
<li>{$loginlogout_type_attribute_description}</li>
<li><b><i>HTML_ATTRIBUTES</i></b> - <i>Optional.</i> Any HTML attributes to add to the button.  List multiple HTML attributes as separate shortcode parameters.</li>
</ul>
END
					,
				),
				array(
					'id'   => 'shortcodes_link',
					'name' => 'umich_oidc_link',
					'type' => 'html',
					'html' => <<<END
<div id="umich_oidc_link"><code>[umich_oidc_link type="login" text="Log in" return="here" <i>HTML_ATTRIBUTES</i>]</code></div>
<p>Generate a login or logout link.</p>
<p><b>Example:</b> <code>[umich_oidc_link type="login"]</code> will be replaced by a link that users can use to log in.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li>{$return_attribute_description}</li>
<li><b>text</b> - <i>Optional, defaults to "Log in" for login, or "Log out" for logout.</i> Controls the text that the link displays.</li>
<li><b>text_login</b> - <i>Optional, defaults "Log in".</i> Controls the text that the <code>type="login-logout"</code> login link displays for logins.</li>
<li><b>text_logout</b> - <i>Optional, defaults "Log out".</i> Controls the text that the <code>type="login-logout"</code> logout link displays for logouts.</li>
<li>{$loginlogout_type_attribute_description}</li>
<li><b><i>HTML_ATTRIBUTES</i></b> - <i>Optional.</i> Any HTML attributes to add to the link.  List multiple HTML attributes as separate shortcode parameters.</li>
</ul>
END
					,
				),
				array(
					'id'   => 'shortcodes_logged_in',
					'name' => 'umich_oidc_logged_in',
					'type' => 'html',
					'html' => <<<'END'
<div id="umich_oidc_logged_in"><code>[umich_oidc_logged_in flow="paragraph"] <i>CONTENT</i> [/umich_oidc_logged_in]</code></div>
<p>Display content only if the user is logged in via OIDC and/or WordPress.</p>
<p><b>Example:</b> <code>[umich_oidc_logged_in]Welcome, authenticated user![/umich_oidc_logged_in]</code> will be replaced by nothing if the visitor is not logged in.  If the visitor is logged in, "Welcome, authenticated user!" will be displayed.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li><b>flow</b> - <i>Optional, defaults to "paragraph".</i> "paragraph" will display the content as it's own paragraph(s), while "inline" will display the content in the middle of the current paragraph.</li>
</ul>
END
					,
				),
				array(
					'id'   => 'shortcodes_member',
					'name' => 'umich_oidc_member',
					'type' => 'html',
					'html' => <<<'END'
<div id="umich_oidc_member"><code>[umich_oidc_member groups="<i>GROUP A, GROUP B, GROUP C</i>" flow="paragraph"] <i>CONTENT</i> [/umich_oidc_member]</code></div>
<p>Display content only if the user is logged in via OIDC and is a member of at least one of the specified groups, or is a WordPress administrator (WordPress administrators are considered members of all groups).</p>
<p><b>Example:</b> <code>[umich_oidc_member groups="diag-squirrel-fans"]Be ready to take photos![/umich_oidc_member]</code> will be replaced by nothing if the visitor is not logged in via OIDC or is not a member of the group diag-squirrel-fans.  If the visitor is logged and a member of the group, "Be ready to take photos!" will be displayed.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li><b>flow</b> - <i>Optional, defaults to "paragraph".</i> "paragraph" will display the content as it's own paragraph(s), while "inline" will display the content in the middle of the current paragraph.</li>
<li><b>groups</b> - <i>Required.</i> One or more group names separated by commas.<p><b style="background-color: #FFFFCC;">IMPORTANT NOTE: only the <i>official</i> name of the group will work.  The "also known as" names for the group will not work.  University of Michgan users can find the official name for a group on the group's MCommunity page, in large type at the top of the main section.</b></p>Keep in mind that the name of a group can contain spaces. If you accidentally type <code>group=</code> instead of <code>groups=</code>, it will still work, but if both are present only <code>groups</code> will be used.</li>
</ul>
END
					,
				),
				array(
					'id'   => 'shortcodes_not_logged_in',
					'name' => 'umich_oidc_not_logged_in',
					'type' => 'html',
					'html' => <<<'END'
<div id="umich_oidc_not_logged_in"><code>[umich_oidc_not_logged_in flow="paragraph"] <i>CONTENT</i> [/umich_oidc_not_logged_in]</code></div>
<p>Display content only if the user is NOT logged in via either OIDC or WordPress.</p>
<p><b>Example:</b> <code>[umich_oidc_not_logged_in]Log in to see restricted content.[/umich_oidc_not_logged_in]</code> will be replaced "Log in to see restricted content." if the visitor is not logged in. If the visitor is logged in, the shortcode wil be replaced by nothing.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li><b>flow</b> - <i>Optional, defaults to "paragraph".</i> "paragraph" will display the content as it's own paragraph(s), while "inline" will display the content in the middle of the current paragraph.</li>
</ul>
END
					,
				),
				array(
					'id'   => 'shortcodes_not_member',
					'name' => 'umich_oidc_not_member',
					'type' => 'html',
					'html' => <<<'END'
<div id="umich_oidc_not_member"><code>[umich_oidc_not_member groups="<i>GROUP A, GROUP B, GROUP C</i>" flow="paragraph"] <i>CONTENT</i> [/umich_oidc_not_member]</code></div>
<p>Display content only if the user is logged in via OIDC and is NOT a member of any of the specified groups.  This content will never be displayed for WordPress administrators, since they are considered to be members of all groups even if not logged in via OIDC.</p>
<p><b>Example:</b> <code>[umich_oidc_not_member groups="diag-squirrel-fans"]Consider joining Squirrel Club![/umich_oidc_member]</code> will be replaced by nothing if the visitor is not logged in via OIDC or is member of the group diag-squirrel-fans.  If the visitor is logged and a member of the group, nothing will be displayed.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li><b>flow</b> - <i>Optional, defaults to "paragraph".</i> "paragraph" will display the content as it's own paragraph(s), while "inline" will display the content in the middle of the current paragraph.</li>
<li><b>groups</b> - <i>Required.</i> One or more group names separated by commas.<p><b style="background-color: #FFFFCC;">IMPORTANT NOTE: only the <i>official</i> name of the group will work.  The "also known as" names for the group will not work.  University of Michigan users can find the official name for a group on the group's MCommunity page, in large type at the top of the main section.</b></p>Keep in mind that the name of a group can contain spaces. If you accidentally type <code>group=</code> instead of <code>groups=</code>, it will still work, but if both are present only <code>groups</code> will be used.</li>
</ul>
END
					,
				),
				array(
					'id'   => 'shortcodes_url',
					'name' => 'umich_oidc_url',
					'type' => 'html',
					'html' => <<<END
<div id="umich_oidc_url"><code>[umich_oidc_url type="login" return="here"]</code></div>
<p>Generate a URL of the specified type.  The URL will not be clickable unless you put it in a link or button.</p>
<p><b>Example:</b> <code>[umich_oidc_userinfo type="login"]</code> will be replaced with a URL that can be used to log the user in.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li>{$loginlogout_type_attribute_description}</li>
<li>
{$return_attribute_description}
<p><b>NOTE:</b> These URLs cannot be copied and used outside of the site.  If you want to have a link elsewhere that will log someone in and then take them to specific content, restrict access to the content and then share the direct link to the protected content.</p>
</li>
</ul>
END
					,
				),
				array(
					'id'   => 'shortcodes_userinfo',
					'name' => 'umich_oidc_userinfo',
					'type' => 'html',
					'html' => <<<'END'
<div id="umich_oidc_userinfo"><code>[umich_oidc_userinfo type="<i>WHAT</i>" default="" unprintable="???" separator=", " dictionary="="]</code></div>
<p>Display information about the user who is currently logged in via OIDC.</p>
<p><b>Example:</b> <code>[umich_oidc_userinfo type="given_name"]</code> will be replaced by the user's first name, if they are logged in, and will be replaced with nothing if they are not logged in.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li><b>type</b> - <i>Required.</i> What information to display.  Can be any of the following:
<table style="margin-left: 1em;">
<tr><th style="padding: 4px;">username</th><td style="padding: 4px;">login name</td></tr>
<tr><th style="padding: 4px;">email</th><td style="padding: 4px;">email address</td></tr>
<tr><th style="padding: 4px;">full_name</th><td style="padding: 4px;">full name, display name, or preferred name</td></tr>
<tr><th style="padding: 4px;">given_name</th><td style="padding: 4px;">first or given name</td></tr>
<tr><th style="padding: 4px;">family_name</th><td style="padding: 4px;">surname or family name</td></tr>
<tr><th style="padding: 4px;">groups</th><td style="padding: 4px;">list of groups the user is a member of</td></tr>
</table>
In addition, you can specify the name of any claim in the OIDC userinfo token.  If an OIDC claim conflicts with one of the names above which may be mapped to a different OIDC claim, you can override the mapping by using the syntax <code>type="userinfo:<i>CLAIM</i>"</code>
</li>
<li><b>default</b> - <i>Optional, defaults to "".</i> What to display if the information specified by <code>type</code> is not present, either because the user is not logged in or for some other reason.</li>
<li><b>unprintable</b> - <i>Optional, defaults to "???".</i> What to display if the information specified by <code>type</code> is present but can't be displayed as text.</li>
<li><b>separator</b> - <i>Optional, defaults to ", ".</i> What to use in between items if the information specified by <code>type</code> is a list of items.</li>
<li><b>dictionary</b> - <i>Optional, defaults to "=".</i> What to do if the information specified by <code>type</code> is a dictionary (list of key/value pairs).
<table style="margin-left: 1em;">
<tr><th style="padding: 4px;">keys</th><td style="padding: 4px;">display only the keys</td></tr>
<tr><th style="padding: 4px;">values</th><td style="padding: 4px;">display only the values</td></tr>
<tr><th style="padding: 4px;"><i>STRING</i></th><td style="padding: 4px;">display a list of all keys and values with <i>STRING</i> in between each of them. <b>Example (using the defaults):</b><br /><code>key1=value1, key2=value2, key3=value3</code></td></tr>
</table>
</li>
</ul>
END
					,
				),
			),
		);

		return $settings;
	}


	/**
	 * Sanitize the IdP URL
	 *
	 * @param string $input URL to sanitize.
	 * @param object $errors WP_Errror object for errors that are found.
	 * @param array  $setting The wp-optionskit setting array for the URL field.
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function sanitize_provider_url( $input, $errors, $setting ) {

		if ( ! \is_string( $input ) ) {
			$errors->add( 'provider_url', 'Internal error (not a string)' );
			return '';
		}

		$input = \trim( $input );
		$url   = \esc_url_raw( $input, array( 'https' ) );
		if ( '' === $url ) {
			$errors->add( 'provider_url', 'Must be a URL starting with https://' );
			return '';
		}
		$url     = \rtrim( $url, '/' );
		$request = \wp_safe_remote_get( $url . '/.well-known/openid-configuration' );
		if ( \is_wp_error( $request ) ) {
			$errors->add( 'provider_url', 'Does not appear to be an OpenID Identity Provider: unable to retrieve ' . \esc_url( $url ) . '/.well-known/openid-configuration' );
			return '';
		}
		$body = \wp_remote_retrieve_body( $request );
		$json = \json_decode( $body );
		if ( JSON_ERROR_NONE !== \json_last_error() ) {
			$errors->add( 'provider_url', 'Does not appear to be an OpenID Identity Provider: ' . \esc_url( $url ) . '/.well-known/openid-configuration is not a valid JSON document.' );
			return '';
		}
		return $url;
	}

	/**
	 * Sanitize the list of scopes
	 *
	 * @param string $input Space separated list of scopes to sanitize.
	 * @param object $errors WP_Errror object for errors that are found.
	 * @param array  $setting The wp-optionskit setting array for the scopes field.
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function sanitize_scopes( $input, $errors, $setting ) {

		if ( ! \is_string( $input ) ) {
			$errors->add( 'scopes', 'Internal error (not a string)' );
			return '';
		}

		$input  = \trim( $input );
		$scopes = \explode( ' ', $input );

		// The openid scope needs to be present
		// https://openid.net/specs/openid-connect-core-1_0.html#AuthRequest .
		if ( ! \in_array( 'openid', $scopes, true ) ) {
			$errors->add( 'scopes', 'The scope "openid" is required.' );
			return '';
		}

		// Legal scopes match
		// 1*( %x21 / %x23-5B / %x5D-7E )
		// which is any printable ASCII character except spaces,
		// double quote, or backslash.
		// https://www.rfc-editor.org/rfc/rfc6749.html#section-3.3 .
		foreach ( $scopes as $s ) {
			if ( 1 !== \preg_match( '/^[\x21\x23-\x5b\x5d-\x7e]+$/', $s ) ) {
				$errors->add( 'scopes', 'Bad character in scope name: \'' . $s . '\'' );
				return '';

			}
		}

		return $input;
	}

	/**
	 * Sanitize the list of available groups
	 *
	 * @param string $input Space separated list of groups to sanitize.
	 * @param object $errors WP_Errror object for errors that are found.
	 * @param array  $setting The wp-optionskit setting array for the groups field.
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function sanitize_group_choices( $input, $errors, $setting ) {

		log_message( 'group choices: ' );
		log_message( $input );
		$field_id = $setting['id'];
		if ( ! \is_array( $input ) ) {
			$errors->add( $field_id, 'Internal error (not an array)' );
			return array();
		}

		$n = \count( $input );
		if ( 0 === $n ) {
			$errors->add( $field_id, 'Must select at least one group.' );
			return array();
		}
		if ( $n > 1 ) {
			if ( \in_array( '_everyone_', $input, true ) ) {
				$errors->add( $field_id, '"( Everyone )" cannot be used together with other groups.' );
				return array();
			}
			if ( \in_array( '_logged_in_', $input, true ) ) {
				$errors->add( $field_id, '"( Logged-in Users )" cannot be used together with other groups.' );
				return array();
			}
		}

		$values = array_map(
			function( $v ) {
				return $v['value'];
			},
			$setting['options']
		);
		foreach ( $input as $group ) {
			if ( ! \in_array( $group, $values, true ) ) {
				$errors->add( $field_id, 'Unknown group: ' . esc_html( $group ) );
				return array();
			}
		}

		return $input;
	}

	/**
	 * Sanitize a URL
	 *
	 * @param string $input URL to sanitize.
	 * @param object $errors WP_Errror object for errors that are found.
	 * @param array  $setting The wp-optionskit setting array for the URL field.
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function sanitize_url( $input, $errors, $setting ) {

		$field_id = $setting['id'];

		if ( ! \is_string( $input ) ) {
			$errors->add( $field_id, 'Internal error (not a string)' );
			return '';
		}

		$input = \trim( $input );
		if ( '' === $input ) {
			return;
		}

		$url = \esc_url_raw( $input, array( 'https' ) );
		if ( '' === $url ) {
			$errors->add( $field_id, 'Must be a URL starting with "https://" or "/"' );
			return '';
		}
		return $input;

	}

	/**
	 * Display admin notices about configuration problems.
	 *
	 * Called by the admin_notices action.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {

		// Only display notices on our own plugin settings pages.
		global $plugin_page;
		if ( 'umich_oidc-settings' !== $plugin_page ) {
			return;
		}

		if ( ! \class_exists( 'Pantheon_Sessions' ) ) {

			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php echo "UMich OIDC Login strongly recommends using the <a href='https://wordpress.org/plugins/wp-native-php-sessions/' target='_blank'>WordPress Native PHP Sessions</a> plugin to prevent conflicts with other WordPress plugins that also use PHP sessions and to ensure correct operation when the site has multiple web servers."; ?></p>
			</div>
			<?php

		}

	}

}
