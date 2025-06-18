=== UMich OIDC Login ===
Contributors: markmont
Tags: access-control,OIDC,content restriction,groups,login
Requires at least: 6.0.0
Tested up to: 6.3.1
Stable tag: 1.2.0
Requires PHP: 7.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Restrict access to the whole site or only certain parts based on OpenID Connect (OIDC) login and group membership information.

== Description ==

This plugin is for a very specific use case:  Your WordPress website is part of an organization that uses OpenID Connect (OIDC) for web single-sign-on as well as for group-based authorization.  In that case, this plugin will let you restrict access to parts of your WordPress website based on OIDC login and group membership information.

This plugin has been tested with:

* [Shibboleth](https://www.shibboleth.net/) OIDC using the `edumember_ismemberof` attribute for LDAP group membership.

Features:

* Allow site visitors to log in via OIDC without needing a WordPress user account.
* Optionally allow WordPress users to log in via OIDC instead of using their WordPress password.
* Optionally restrict access to the entire site to logged-in users or only members of specific groups.
* Optionally restrict access to specific pages and posts to logged-in users or only members of specific groups.
* Show parts of pages/posts/widgets only to logged-in users or members of specific groups.
* Access restrictions apply to site visitors, feeds, the REST API, and XMLRPC.
* Shortcodes (Gutenberg blocks planned for a future release)
    * `umich_oidc_button` - Generate a login or logout button.
    * `umich_oidc_link` - Generate a login or logout link.
    * `umich_oidc_logged_in` - Show content only if the visitor is logged in.
    * `umich_oidc_member` - Show content only if the visitor is a member of one or more groups.
    * `umich_oidc_not_logged_in` - Show content only if the visitor is NOT logged in.
    * `umich_oidc_not_member` - Show content only if the visitor NOT a member of the specified groups.
    * `umich_oidc_url` - Generate a login or logout URL.
    * `umich_oidc_userinfo` - Display information about the currently-logged-in OIDC user.

== Restricting private content in search results ==

You can prevent content from showing up in web search engine results by restricting access to particular pages/posts.

Search results from WordPress' built-in search will only show content that the searching user has access to.

**WARNING:** WordPress search plugins may show content that the user does not have access to, leaking private information.  Please test search plugins before enabling them.  If a search plugin provides an appropriate WordPress hook for limiting search results, contact us, and we may be able to add support for it to UMich OIDC Login.


== Installation ==

1. (Recommended but not required) Install the [WordPress Native PHP Sessions](https://wordpress.org/plugins/wp-native-php-sessions/) plugin from the WordPress.org plugin repository or by uploading the files to your web server. For details, see [How to Install a WordPress Plugin](https://www.wpbeginner.com/beginners-guide/step-by-step-guide-to-install-a-wordpress-plugin-for-beginners/). **UMich OIDC Login strongly recommends using the WordPress Native PHP Sessions plugin to prevent conflicts with other WordPress plugins that also use PHP sessions, and to ensure correct operation when the site resides on multiple web servers.**
1. Install UMich OIDC Login from the WordPress.org plugin repository or by uploading the files to your web server.
1. Activate both the WordPress Native PHP Sessions and the UMich OIDC Login plugins through the 'Plugins' menu in WordPress.
1. Under the Settings menu in WordPress, navigate to "UMich OIDC Login" and then click on the "OIDC" tab.  Make a note of the Redirect URI value for use when registering an OIDC client for your WordPress site.
1. Register an OIDC client for your WordPress site.  On the OIDC tab of the UMich OIDC Login settings page, fill in the information you got when registering your client.  At a minimum, this will be the Identity Provider URL, Client ID, and Client Secret.  Click the "Save Changes button".
1. You can now use the settings on the General tab to control access to the website, as well as login and logout behavior.  You can restrict access to individual posts and pages by editing them and changing their document settings.  You can also use shortcodes from the Shortcodes tab in your theme and/or website content.  Adding the following shortcodes to your theme will display a greeting and a login/logout button.

```
Hello, [umich_oidc_userinfo type="given_name" default="stranger"]
[umich_oidc_button]
```

== Frequently Asked Questions ==

= Why do I have to specify all groups on the settings page? =

Currently, UMich OIDC Login is designed to work with OIDC Identity Providers that restrict the groups for which membership information can be released to websites.  Only the official names of groups can be used; aliases will not work.  By entering the allowed groups on the settings page, the group names only have to be correct in a single place and access to individual pages/posts can be controlled by selecting group(s) from a dropdown list.

= Help! OIDC stopped working and now I can't log in to my WordPress dashboard! =

Use [WP CLI](https://wp-cli.org) to turn off OIDC for WordPress users:

`wp option patch delete umich_oidc_settings use_oidc_for_wp_users`

You should then be able to log in to WordPress using your WordPress username and password for your website.

Or, completely turn off the UMich OIDC Login plugin.  WARNING: deactivating the plugin will make any restricted content you have publicly viewable.

`wp plugin deactivate umich-oidc-login`

If you don't remember your WordPress user account password, you can set a new one:

`wp user update YOUR-WORDPRESS-USERNAME --user_pass="PUT-YOUR-NEW-PASSWORD-HERE"`

= How can I report an issue to the plugin developers, or help with plugin development? =

Go to the GitHub repository for this plugin at https://github.com/its-webhosting/umich-oidc-login

== Screenshots ==

1. Allows visitors to log in via OIDC without needing a WordPress user account.  WordPress can get information about logged-in visitors from the OIDC Identity Provider.
2. WordPress users can log in using either OIDC or their WordPress username and password.
3. Control what happens when visitors/users log in and log out.
4. Use group information obtained through OIDC to control access to the website.
5. Use group information obtained through OIDC to control access to individual posts and pages.
6. OIDC authentication settings.
7. Access OIDC user information from within WordPress.  Control which OIDC claims should be used for each piece of information.
8. Use shortcodes to control who sees which things within pages, posts, and themes.

== Changelog ==

= 1.2.0 =
September 11, 2023
* Completely new and improved plugin settings pages that use Gutenberg components and React instead of Vue.js.  This provides some necessary groundwork for adding future features.
    * NOTE: The "Groups for Authorization" setting is now on the OIDC tab (it used to be on the General tab).
* Completely new page/post access restriction metabox.  The new metabox uses Gutenberg components but still works in the Classic Editor.
* Now works for websites hosted on WP Engine.  In addition to WP Engine, the plugin has been tested on Pantheon, Amazon Lightsail, and University of Michigan web hosting services.  The plugin should work with websites hosted on most WordPress hosting providers; please [report](https://github.com/its-webhosting/umich-oidc-login/issues) any web hosting provider where the plugin does not work correctly.
* Bug fixes:
    * Fix a problem with the Revisions section being missing in the Gutenberg editor sidebar's Post tab.
    * Fix a problem with the plugin breaking WordPress' `/login` page.
    * Documentation said the shortcode for displaying data from OIDC user claims was `umich_oidc_userinfo`, but this didn't work since the shortcode was actually named `umich_oidc_user_info` in the plugin code.  The plugin now supports both of these names for the shortcode.
    * The README file now says that group membership for Shibboleth IdPs is specified by the `edumember_ismemberof` OIDC claim (correct) rather than `eduperson_ismemberof` (wrong).
* Internals:
    * Added [documentation for how to develop, build, and package the plugin](https://github.com/its-webhosting/umich-oidc-login/).
    * Added support for developing the plugin using Docker containers.
    * Improved compatibility with PHP 8.
    * Added testing with NGINX, and improved testing in general.  The plugin is now tested with both Apache and NGINX.
    * Updated to the latest version of all plugin dependencies.

= 1.1.2 =
May 18, 2023
* Fixed a bug that prevented groups that have apostrophes / single quotes in their names from working.

= 1.1.1 =
January 31, 2023
* Fixed a bug with login/logout URLs being incorrect when WordPress is installed in a subdirectory.

= 1.1.0 =
January 8, 2023
* Completely reimplemented the feature for using OIDC to log into the WordPress dashboard.
    * Changed the setting values from no/yes to no/optional/yes. The new setting ("optional") allows users a choice of whether to log in using OIDC or their WordPress password. Choosing which way to log in looked like it was supported before when it was not, which was confusing.
    * The "no" setting previously displayed a "Login in with Single Sign On" button that would only log users into the website but not the WordPress dashboard.  This was confusing, and so the button has been removed when OIDC login for WordPress is set to "no".
    * If a user attempts to log in to the WordPress dashboard via OIDC but does not have a WordPress user account, they will now get an "Access Denied" error instead of silently being logged into the website but not logged in to WordPress.
* Fixed a bug where unauthenticated users who tried to access a restricted page/post would sometimes get a "Page Not Found" error instead of being prompted to log in.
* Fixed a bug where users were sometimes not sent to the correct page after authenticating.
* Miscellaneous cleanup and improvements.

= 1.0.0 =
November 2, 2022
* Initial release.

== Upgrade Notice ==

= 1.2.0 =
* New and improved settings page and access restriction metabox.
* Works for websites hosted on WP Engine.
* Fixes 4 bugs.
* Updates dependencies.

= 1.1.2 =
* Fixes a bug that prevents groups that have apostrophes / single quotes in their names from working.

= 1.1.1 =
* Fixes a bug with login/logout URLs being incorrect when WordPress is installed in a subdirectory.

= 1.1.0 =
Fixes several bugs. Completely reimplements the feature for using OIDC to log into the WordPress dashboard so it makes better sense and is more flexible.

= 1.0.0 =
Initial release.


== Copyright and license information ==

Copyright (c) 2022 Regents of the University of Michigan.

This file is part of the UMich OIDC Login WordPress plugin.

UMich OIDC Login is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

UMich OIDC Login is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with UMich OIDC Login. If not, see <https://www.gnu.org/licenses/>.
