# UMich OIDC Login Changelog

## 1.3.0-beta4
June 27, 2025

* Changes since the private/limited release of 1.2.1-beta2:
    * Improved the fix for the security vulnerability below.
	* Fixed bug where default values for new options were sometimes not saved during plugin installation or upgrades.
	* Fixed unstyled "Log in with Single Sign On" button on the WordPress login page.
    * Allow upgrading to prerelease versions of the plugin.
	* Add autosave for settings and UMich OIDC Access metabox.
	* Miscellaneous documentation, code, and toolchain improvements.
	* Updated both README files, screenshots.
    * Added Settings and Support links for the plugin on the WordPress Plugins page.
	* Updated dependencies and tested up to WordPress 6.8.1.


* **[SECURITY] CVE-2024-11753: UMich OIDC Login <= 1.2.0 - Authenticated (Contributor+) Stored Cross-Site Scripting**
	* Fixes made:
		* New setting: Shortcodes -> Allow HTML attributes (disabled by default).
			* [BREAKING CHANGE]: If you use HTML attributes in button/link shortcodes, turn on Settings -> UMich OIDC Login -> Shortcodes -> Allow HTML attributes.
		* OIDC button and link shortcodes with attributes in unpublished pages/posts were replaced with previews with a click-through warning.
		* Earlier validation of `return` URLs in OIDC button and link shortcodes.
	* CVSS Severity Score: 6.4 (Medium)
	* CVSS Vector: CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:C/C:L/I:L/A:N
	* Reporting Organization: Wordfence
	* Vulnerability Researcher(s): [yudha](https://www.wordfence.com/threat-intel/vulnerabilities/researchers/yudha)
	* Description: Stored Cross-Site Scripting vulnerability via the `umich_oidc_button` and `umich_oidc_link` shortcodes in all versions up to, and including, 1.2.0 due to insufficient input sanitization and output escaping on user supplied attributes. This makes it possible for authenticated attackers, with contributor-level access and above, to inject arbitrary web scripts in pages that will execute whenever a user accesses an injected page.
	* Impact: This vulnerability allows an attacker to inject arbitrary JavaScript into a webpage, potentially leading to session hijacking and other security risks.
	* Acknowledgement: Thank you to yudha and Wordfence for finding, responsibly reporting, and working with the plugin authors on a fix for this vulnerability.  In particular, yudha's comprehensive and well-written report made the vulnerability, exploit, and impact simple to understand.
* Non-security bug fixes:
	* Fixed a problem where authenticated users have but later lose access to content.
	* Fixed error "Sorry, you are not allowed to edit the `_umich_oidc_access` custom field." when
		* creating new WordPress Patterns
		* saving posts with Advanced Custom Fields plugin custom post types
		* saving posts with Advanced Custom Fields plugin custom fields
	* Fixed bug preventing scripted configuration of the plugin using WP CLI (default values for options were sometimes not saved during plugin installation or upgrades).
* Features:
	* Settings are now automatically saved by default. This affects both changes to the page/post UMich OIDC Access metabox as well as the plugin settings pages. Autosave can be turned off at Settings -> UMich OIDC Login -> General -> Autosave Plugin Settings.
	* Website owners can opt-in to test prereleases of the plugin by turning on Settings -> UMich OIDC Login -> General -> Upgrading -> Test pre-releases.
* Internals:
	* Updated jumbojett/openid-connect-php to 1.0.2 from 0.9.10.
	* Updated @wordpress and other NPM dependencies to lastest versions.
	* Updated build tools and build documentation.
	* Updated and tested up to WordPress 6.8.1.


## 1.2.0
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

## 1.1.2
May 18, 2023
* Fixed a bug that prevented groups that have apostrophes / single quotes in their names from working.

## 1.1.1
January 31, 2023
* Fixed a bug with login/logout URLs being incorrect when WordPress is installed in a subdirectory.

## 1.1.0
January 8, 2023
* Completely reimplemented the feature for using OIDC to log into the WordPress dashboard.
    * Changed the setting values from no/yes to no/optional/yes. The new setting ("optional") allows users a choice of whether to log in using OIDC or their WordPress password. Choosing which way to log in looked like it was supported before when it was not, which was confusing.
    * The "no" setting previously displayed a "Login in with Single Sign On" button that would only log users into the website but not the WordPress dashboard.  This was confusing, and so the button has been removed when OIDC login for WordPress is set to "no".
    * If a user attempts to log in to the WordPress dashboard via OIDC but does not have a WordPress user account, they will now get an "Access Denied" error instead of silently being logged into the website but not logged in to WordPress.
* Fixed a bug where unauthenticated users who tried to access a restricted page/post would sometimes get a "Page Not Found" error instead of being prompted to log in.
* Fixed a bug where users were sometimes not sent to the correct page after authenticating.
* Miscellaneous cleanup and improvements.

## 1.0.0
November 2, 2022
* Initial release.

