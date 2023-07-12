<?php
/**
 * UMich OIDC settings page - Shortcodes tab
 *
 * This file is required via includes/admin/class-settings.php
 *
 * @package    UMich_OIDC_Login\Admin
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

namespace UMich_OIDC_Login\Admin;

$loginlogout_type_attribute_description = '
<b>type</b> - <i>Optional, defaults to "login-logout".</i> What the shortcode should do.  Can be any of the following:
<table style="margin-left: 1em;">
<tr><th style="padding: 4px;">login</th><td style="padding: 4px;">Log the user in.</td></tr>
<tr><th style="padding: 4px;">logout</th><td style="padding: 4px;">Log the user out.<p><b>Caution:</b> if the <code>return</code> parameter takes the user to a page that is not public and auto-login is enabled, the user may be automatically logged in again, defeating the purpose of the logout.</p></td></tr>
<tr><th style="padding: 4px;">login-logout</th><td style="padding: 4px;">If the user is not logged in, log them in.  If the user is logged in, log them out.</td></tr>
</table>
';

$return_attribute_description = '
<b>return</b> - <i>Optional. Defaults to "setting" for login URLs, "smart" for logout URLs.</i> Where to send the user after they successfully login or logout.  Can be any of the following:
<table style="margin-left: 1em;">
<tr><th style="padding: 4px;">smart</th><td style="padding: 4px;"><i>Can only be used for logout.</i> Send the user back to the page the shortcode is on, if that page is public (accessible by everyone without logging in). Otherwise, send the user to the <a href="#general/logout_return_url">Logout Destination URL</a>, if one has been set.  Otherwise, send the user to the site\'s main page.</td></tr>
<tr><th style="padding: 4px;">setting</th><td style="padding: 4px;">Send the user to the <a href="#general/login_return_url">Login Destination URL</a> (when logging in) or <a href="#general/logout_return_url">Logout Destination URL</a> (when logging out), if the appropriate one has been set.  Otherwise, send the user to the site\'s main page.</td></tr>
<tr><th style="padding: 4px;">here</th><td style="padding: 4px;">Send the user back to the page the shortcode was on.</td></tr>
<tr><th style="padding: 4px;">home</th><td style="padding: 4px;">Send the user to the site\'s main page.</td></tr>
<tr><th style="padding: 4px;"><i>URL</i></th><td style="padding: 4px;">Send the user to the specified URL within the site.  Can be either a full URL within the site (starting with "https://"), or a URL path on the site (starting with "/"). If <code>return</code> starts with anything else, the user will be sent to the site\'s main page. <b>NOTE:</b> <i>Due to security constraints, only URLs for the site will work.  URLs for things outside the site will generate an error.</i></td></tr>
</table>
';

$settings_tab_shortcodes = array(
	array(
		'id'   => 'shortcodes_subsection',
		'name' => 'SHORTCODES',
		'type' => 'html',
		'html' => '
The shortcodes below can be used in your content, widgets, and themes to control what shows up for different users.<br /><br />
<table style="margin-left: 1em;">
<tr><th style="padding: 4px;"><a href="#shortcodes/shortcodes_button"><code>umich_oidc_button</code></a></th><td style="padding: 4px;">Generate a login or logout button.</td></tr>
<tr><th style="padding: 4px;"><a href="#shortcodes/shortcodes_link"><code>umich_oidc_link</code></a></th><td style="padding: 4px;">Generate a login or logout link.</td></tr>
<tr><th style="padding: 4px;"><a href="#shortcodes/shortcodes_logged_in"><code>umich_oidc_logged_in</code></a></th><td style="padding: 4px;">Show content only if the visitor is logged in.</td></tr>
<tr><th style="padding: 4px;"><a href="#shortcodes/shortcodes_member"><code>umich_oidc_member</code></a></th><td style="padding: 4px;">Show content only if the visitor is a member of one or more groups.</td></tr>
<tr><th style="padding: 4px;"><a href="#shortcodes/shortcodes_not_logged_in"><code>umich_oidc_not_logged_in</code></a></th><td style="padding: 4px;">Show content only if the visitor is NOT logged in.</td></tr>
<tr><th style="padding: 4px;"><a href="#shortcodes/shortcodes_not_member"><code>umich_oidc_not_member</code></a></th><td style="padding: 4px;">Show content only if the visitor NOT a member of any of the specified groups.</td></tr>
<tr><th style="padding: 4px;"><a href="#shortcodes/shortcodes_url"><code>umich_oidc_url</code></a></th><td style="padding: 4px;">Generate a login or logout URL.</td></tr>
<tr><th style="padding: 4px;"><a href="#shortcodes/shortcodes_userinfo"><code>umich_oidc_userinfo</code></a></th><td style="padding: 4px;">Display information about the currently-logged-in OIDC user.</td></tr>
</table>
		',
	),
	array(
		'id'   => 'shortcodes_button',
		'name' => 'umich_oidc_button',
		'type' => 'html',
		'html' => "
<div id='umich_oidc_button'><code>[umich_oidc_button type=\"login\" text=\"Log in\" return=\"here\" <i>HTML_ATTRIBUTES</i>]</code></div>
<p>Generates a login or logout button.</p>
<p><b>Example:</b> <code>[umich_oidc_button type=\"login\"]</code> will be replaced by a button that users can use to log in.</p>
<p><b>Parameters:</b></p>
<ul style='list-style-type: disc; margin: 2px 1em;'>
<li>{$return_attribute_description}</li>
<li><b>text</b> - <i>Optional, defaults to \"Log in\" for login, or \"Log out\" for logout.</i> Controls the text that the button displays.</li>
<li><b>text_login</b> - <i>Optional, defaults \"Log in\".</i> Controls the text that the <code>type=\"login-logout\"</code> link displays for logins.</li>
<li><b>text_logout</b> - <i>Optional, defaults \"Log out\".</i> Controls the text that the <code>type=\"login-logout\"</code> logout link displays for logouts.</li>
<li>{$loginlogout_type_attribute_description}</li>
<li><b><i>HTML_ATTRIBUTES</i></b> - <i>Optional.</i> Any HTML attributes to add to the button.  List multiple HTML attributes as separate shortcode parameters.</li>
</ul>
		",
	),
	array(
		'id'   => 'shortcodes_link',
		'name' => 'umich_oidc_link',
		'type' => 'html',
		'html' => "
<div id='umich_oidc_link'><code>[umich_oidc_link type=\"login\" text=\"Log in\" return=\"here\" <i>HTML_ATTRIBUTES</i>]</code></div>
<p>Generate a login or logout link.</p>
<p><b>Example:</b> <code>[umich_oidc_link type=\"login\"]</code> will be replaced by a link that users can use to log in.</p>
<p><b>Parameters:</b></p>
<ul style='list-style-type: disc; margin: 2px 1em;'>
<li>{$return_attribute_description}</li>
<li><b>text</b> - <i>Optional, defaults to \"Log in\" for login, or \"Log out\" for logout.</i> Controls the text that the link displays.</li>
<li><b>text_login</b> - <i>Optional, defaults \"Log in\".</i> Controls the text that the <code>type=\"login-logout\"</code> login link displays for logins.</li>
<li><b>text_logout</b> - <i>Optional, defaults \"Log out\".</i> Controls the text that the <code>type=\"login-logout\"</code> logout link displays for logouts.</li>
<li>{$loginlogout_type_attribute_description}</li>
<li><b><i>HTML_ATTRIBUTES</i></b> - <i>Optional.</i> Any HTML attributes to add to the link.  List multiple HTML attributes as separate shortcode parameters.</li>
</ul>
		",
	),
	array(
		'id'   => 'shortcodes_logged_in',
		'name' => 'umich_oidc_logged_in',
		'type' => 'html',
		'html' => '
<div id="umich_oidc_logged_in"><code>[umich_oidc_logged_in flow="paragraph"] <i>CONTENT</i> [/umich_oidc_logged_in]</code></div>
<p>Display content only if the user is logged in via OIDC and/or WordPress.</p>
<p><b>Example:</b> <code>[umich_oidc_logged_in]Welcome, authenticated user![/umich_oidc_logged_in]</code> will be replaced by nothing if the visitor is not logged in.  If the visitor is logged in, "Welcome, authenticated user!" will be displayed.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li><b>flow</b> - <i>Optional, defaults to "paragraph".</i> "paragraph" will display the content as its own paragraph(s), while "inline" will display the content in the middle of the current paragraph.</li>
</ul>
		',
	),
	array(
		'id'   => 'shortcodes_member',
		'name' => 'umich_oidc_member',
		'type' => 'html',
		'html' => '
<div id="umich_oidc_member"><code>[umich_oidc_member groups="<i>GROUP A, GROUP B, GROUP C</i>" flow="paragraph"] <i>CONTENT</i> [/umich_oidc_member]</code></div>
<p>Display content only if the user is logged in via OIDC and is a member of at least one of the specified groups, or is a WordPress administrator (WordPress administrators are considered members of all groups).</p>
<p><b>Example:</b> <code>[umich_oidc_member groups="diag-squirrel-fans"]Be ready to take photos![/umich_oidc_member]</code> will be replaced by nothing if the visitor is not logged in via OIDC or is not a member of the group diag-squirrel-fans.  If the visitor is logged and a member of the group, "Be ready to take photos!" will be displayed.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li><b>flow</b> - <i>Optional, defaults to "paragraph".</i> "paragraph" will display the content as its own paragraph(s), while "inline" will display the content in the middle of the current paragraph.</li>
<li><b>groups</b> - <i>Required.</i> One or more group names separated by commas.<p><b style="background-color: #FFFFCC;">IMPORTANT NOTE: only the <i>official</i> name of the group will work.  The "also known as" names for the group will not work.  University of Michgan users can find the official name for a group on the group\'s MCommunity page, in large type at the top of the main section.</b></p>Keep in mind that the name of a group can contain spaces. If you accidentally type <code>group=</code> instead of <code>groups=</code>, the shortcode will still work, but if both are present only <code>groups</code> will be used.</li>
</ul>
		',
	),
	array(
		'id'   => 'shortcodes_not_logged_in',
		'name' => 'umich_oidc_not_logged_in',
		'type' => 'html',
		'html' => '
<div id="umich_oidc_not_logged_in"><code>[umich_oidc_not_logged_in flow="paragraph"] <i>CONTENT</i> [/umich_oidc_not_logged_in]</code></div>
<p>Display content only if the user is NOT logged in via either OIDC or WordPress.</p>
<p><b>Example:</b> <code>[umich_oidc_not_logged_in]Log in to see restricted content.[/umich_oidc_not_logged_in]</code> will be replaced "Log in to see restricted content." if the visitor is not logged in. If the visitor is logged in, the shortcode wil be replaced by nothing.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li><b>flow</b> - <i>Optional, defaults to "paragraph".</i> "paragraph" will display the content as its own paragraph(s), while "inline" will display the content in the middle of the current paragraph.</li>
</ul>
		',
	),
	array(
		'id'   => 'shortcodes_not_member',
		'name' => 'umich_oidc_not_member',
		'type' => 'html',
		'html' => '
<div id="umich_oidc_not_member"><code>[umich_oidc_not_member groups="<i>GROUP A, GROUP B, GROUP C</i>" flow="paragraph"] <i>CONTENT</i> [/umich_oidc_not_member]</code></div>
<p>Display content only if the user is logged in via OIDC and is NOT a member of any of the specified groups.  This content will never be displayed for WordPress administrators, since they are considered to be members of all groups even if not logged in via OIDC.</p>
<p><b>Example:</b> <code>[umich_oidc_not_member groups="diag-squirrel-fans"]Consider joining Squirrel Club![/umich_oidc_member]</code> will be replaced by nothing if the visitor is not logged in via OIDC or is member of the group diag-squirrel-fans.  If the visitor is logged and a member of the group, nothing will be displayed.</p>
<p><b>Parameters:</b></p>
<ul style="list-style-type: disc; margin: 2px 1em;">
<li><b>flow</b> - <i>Optional, defaults to "paragraph".</i> "paragraph" will display the content as its own paragraph(s), while "inline" will display the content in the middle of the current paragraph.</li>
<li><b>groups</b> - <i>Required.</i> One or more group names separated by commas.<p><b style="background-color: #FFFFCC;">IMPORTANT NOTE: only the <i>official</i> name of the group will work.  The "also known as" names for the group will not work.  University of Michigan users can find the official name for a group on the group\'s MCommunity page, in large type at the top of the main section.</b></p>Keep in mind that the name of a group can contain spaces. If you accidentally type <code>group=</code> instead of <code>groups=</code>, it will still work, but if both are present only <code>groups</code> will be used.</li>
</ul>
		',
	),
	array(
		'id'   => 'shortcodes_url',
		'name' => 'umich_oidc_url',
		'type' => 'html',
		'html' => "
<div id='umich_oidc_url'><code>[umich_oidc_url type=\"login\" return=\"here\"]</code></div>
<p>Generate a URL of the specified type.  The URL will not be clickable unless you put it in a link or button.</p>
<p><b>Example:</b> <code>[umich_oidc_userinfo type=\"login\"]</code> will be replaced with a URL that can be used to log the user in.</p>
<p><b>Parameters:</b></p>
<ul style='list-style-type: disc; margin: 2px 1em;'>
<li>{$loginlogout_type_attribute_description}</li>
<li>
{$return_attribute_description}
<p><b>NOTE:</b> These URLs cannot be copied and used outside of the site.  If you want to have a link elsewhere that will log someone in and then take them to specific content, restrict access to the content and then share the direct link to the protected content.</p>
</li>
</ul>
		",
	),
	array(
		'id'   => 'shortcodes_userinfo',
		'name' => 'umich_oidc_userinfo',
		'type' => 'html',
		'html' => '
<div id="umich_oidc_userinfo"><code>[umich_oidc_userinfo type="<i>WHAT</i>" default="" unprintable="???" separator=", " dictionary="="]</code></div>
<p>Display information about the user who is currently logged in via OIDC.  The shortcode name <code>umich_oidc_user_info</code> will also work.</p>
<p><b>Example:</b> <code>[umich_oidc_userinfo type="given_name"]</code> will be replaced by the user\'s first name, if they are logged in, and will be replaced with nothing if they are not logged in.</p>
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
<li><b>unprintable</b> - <i>Optional, defaults to "???".</i> What to display if the information specified by <code>type</code> is present but can\'t be displayed as text.</li>
<li><b>separator</b> - <i>Optional, defaults to ", ".</i> What to use in between items if the information specified by <code>type</code> is a list of items.</li>
<li><b>dictionary</b> - <i>Optional, defaults to "=".</i> What to do if the information specified by <code>type</code> is a dictionary (list of key/value pairs).
<table style="margin-left: 1em;">
<tr><th style="padding: 4px;">keys</th><td style="padding: 4px;">display only the keys</td></tr>
<tr><th style="padding: 4px;">values</th><td style="padding: 4px;">display only the values</td></tr>
<tr><th style="padding: 4px;"><i>STRING</i></th><td style="padding: 4px;">display a list of all keys and values with <i>STRING</i> in between each of them. <b>Example (using the defaults):</b><br /><code>key1=value1, key2=value2, key3=value3</code></td></tr>
</table>
</li>
</ul>
		',
	),
);
