# Roadmap

This is a list of the features and functionality we would like to add in the future.  None of this represents a commitment, it's just a scratchpad for ideas.

Things we think we might get to sooner should be moved higher up in this list.

To work on something,
* [create an issue in GitHub](https://github.com/its-webhosting/umich-oidc-login/issues)
* copy the item from thist list into the issue
* assign the issue to yourself
* move the item to the top of the list in this document and add a link to the issue
* commit the roadmap change to the `main` branch
* remove the item from the roadmap once it is in a production release of the plugin.

## Items

### Add a "Copy" (to clipboard) button to the `Redirect URI` field
* Priority: Low
* Size: Small
* Reason: Convenience

### Make `Client Secret` field a password field with a "Show" button
* Priority: Low
* Size: Small
* Reason: Security

### Settings page tab for logs
* Priority: Medium
* Size: Medium
* Reason: Allow plugin users to troubleshoot problems without having to ask developers.
* Details:
  * Add a tab to the settings page for logs.
  * Display logs in DataTables table for easy sorting and filtering, opening/closing rows for more/less detail.
  * Denials should include: IP, username, session ID, page, access required, actual access.
  * Button to download logs.
  * Button to clear logs.
  * Allow user to choose log level: logging disabled, denials only, errors, info, debug. (Each log level encompasses all higher levels.)
  * What is the most performant way to store and retrieve logs?  Database?  File?
  * One HTTP request can result in hundreds of log lines at the debugging log level.  What is the best way to handle these?  Write them out one at a time, or all at once at the end of the request?
  * How long should logs be kept?  And/or how many logs should be kept?

### Automated testing
* Priority: High
* Size: Large
* Reason: Plugin is too complex with too many edge cases to test manually; high risk of problems slipping through.
* Details:
  * Add Keycloak server to the Docker Compose setup.
  * Make sure Keycloak can assets a groups claim via OIDC.
  * Modify plugin to fully support Keycloak.
  * Automate the tests currently listed in `docs/testing.md`.
    * End-to-end tests should be the main tests.
    * Unit tests as appropriate.
    * Regression tests as appropriate.
    * Do we want to reuse the one of the two testing frameworks WordPress/Gutenberg uses (below) or make our own technology choices?
      * Gutenberg: https://developer.wordpress.org/block-editor/explanations/architecture/automated-testing/
      * WordPress: https://github.com/wordpress/wordpress-develop/
  * Create documentation on how a website administrator can set up and use Keycloak with a production website, and link to that from the plugin README.

### In-plugin support for a beta update channel with automatic updates
* Priority: Low
* Size: Small
* Reason: Better testing of beta releases and fewer problems in production releases by letting website administrators to opt in to automatically get beta releases.  This will be particularly valuable in advance of landing major new features, such as WordPress Multisite support.
* Details:
  * Research and test how it will work.
    * WordPress Plugin Directory guidelines prohibit getting the beta updates from our own server; they have to come from the directory.
      * We should be able to tag a beta release in SVN but _not_ update the `Stable tag` field in `README.md`.
      * How will the plugin know that a beta release is available?
        * BuddyPress and WooCommerce supposedly pull their beta versions from wordpress.org, check their code to see how they do this.
    * Add setting to enable beta updates.
    * Add documentation on packaging/publishing beta releases.

### Multisite support for plugin
* Priority: High
* Size: Large
* Reason: Critical need from large University of Michigan units (LSA, Engineering) that heavily use WordPress Multisite.
* Details:
  * Ensure works for both subdomain and subdirectory Multisite installations.
  * WP Native Sessions plugin
    * Does it work as a network-activated plugin?
    * If the UMich OIDC Login plugin is network-activated, warn if the sessions plugin is also not network-activated.
    * Make sure site admins cannot see sessions of users for other site.  Generate a site-specific cookie prefix if needed.
  * Add wp-react-optionskit support for a Multisite network settings page.
  * Figure out how network settings will interact with site settings.
  * Ensure works on: Pantheon, WP Engine, AFS Web Hosting

### Automatically create WordPress accounts for OIDC users who are members of certain groups.
* Priority: High
* Size: Medium
* Reason: Critical need from large University of Michigan units (LSA, Engineering).
* Dependencies: Multisite support (would need significant modification if implemented before Multisite support)
* Details:
  * Needs to be disabled/greyed out if the `Use OIDC for WordPress Users` setting is set to `NO`.
  * The special group `( Logged-in Users )` includes all OIDC users.
  * Allow user to map each WordPress role onto zero or more OIDC groups.  If a group is listed for a role, members of that group will have a WordPress account with that role created for them when they log in via OIDC.  In the example below, members of the groups `unit-website-owners` and `unit-directors` get WordPress administrator accounts created for them automatically when they log in; members of `unit-faculty` get WordPress author accounts, and everyone else in the world gets Subscriber accounts.
    * Administrator: unit-website-owners, unit-directors
    * Editor:
    * Author: unit-faculty
    * Contributor:
    * Subscriber: `( Logged-in Users )`
  * A single group can be used for only one WordPress role.  Adding the same group to multiple roles should be rejected (changes not saved).
  * Auto-created WordPress accounts should be marked with a user term or other indicator that it was auto-created.
  * Provide instructions on how to list auto-created accounts and how to unmark them so they won't be managed by the setting below.
  * Per-role setting for what should happen if a user with an auto-created WordPress account logs in but is not a member of any group for that role.  Options:
    * Leave the user's account associated with the old role.
    * Change the user's role to a specific role (specify)
    * Delete the user's WordPress account.

### Access control for static assets and media files
* Priority: High
* Size: Large
* Reason: Critical need for many websites
* Details:
  * Add access restriction controls (required OIDC groups membership) to Media Library interface.
  * Have a private directory for files that are not managed through the Media Library
    * Allow website administrators to specify different access restrictions for different path prefixes/subdirectories.
      * Example:
        * `/private/` - only members of group A
        * `/private/reports/` - only members of group B
        * `/private/committees/` - only members of groups A or B
  * Protected files need to be opened and streamed to the browser by PHP only after checking access requirements, rather than being served by the web server.
  * Ensure works with WP Engine
  * Ensure works with Pantheon
    * Pantheon does not allow .htaccess files.
    * Files that must not be served can only reside under `wp-content/uploads/private`, see https://pantheon.io/docs/guides/secure-development/private-paths
    * Also see https://pantheon.io/docs/caching-advanced-topics, "Pantheon strips cookies from requests made to public files served from sites/default/files in Drupal and wp-content/uploads in WordPress. This allows the Global CDN to cache the response."
  * Other plugins to refer to for ideas:
    * Good: https://wordpress.org/plugins/private-media/
    * https://wordpress.org/plugins/prevent-file-access/
    * https://buddydev.com/mediapress/settings-options/general-settings/#privacy

### Ensure the plugin works with Solr search
* Priority: Medium
* Size: Medium
* Reason: Solr search is an add-on for Pantheon-hosted websites.
* Details:
  * Test with the Solr plugin Pantheon uses.
  * Test with the top Solr/Lucene plugins in the WordPress plugins directory.
  * Make sure that content on protected posts/pages only shows up in search results if the user searching has permission to view those posts/pages.
  * Make sure content in access-restricting shortcodes only shows up in search results in the user searching has the necessary permissions to view that content.

### Ensure the plugin works with Divi
* Priority: Medium
* Size: Medium
* Reason: many websites use Divi
* Details:
  * Make sure the page/post restriction metabox works.

### Ensure the plugin works with Elementor
* Priority: Medium
* Size: Medium
* Reason: many websites use Elementor
* Details:
  * Make sure the page/post restriction metabox works.

### Hooks to allow customization
* Priority: Low
* Size: Large
* Reason: Allow website administrators to customize the behavior of the plugin; best practice.
* Details:
  * Include filter hooks and action hooks for other plugins/themes to use
	  * for all shortcodes
	  * for each main event or phase (login, log out, create account, link account, ...)
      * for each significant function (determining log-in URL, log-out URL, ...)
  * Document hooks in `docs/hooks.md` and link from the plugin README.

### Publish wp-react-optionskit as its own composer package
* Priority: Low
* Size: Medium
* Reason: Cleans up the plugin code, allows other plugins to use and contribute to wp-react-optionskit
* Details:
  * Create wp-react-optionskit repo
  * Create wp-react-optionskit documentation
    * Include code for an example plugin
  * Add support for features in wp-optionskit that are missing in wp-react-optionskit:
    * file upload
    * subsections?
  * Create wp-react-optionskit package
  * Switch UMich OIDC Login plugin to use wp-react-optionskit package as a composer dependency

### Sessions tab
* Priority: Low
* Size: Medium
* Reason: Nice to have
* Details:
  * Add a "Sessions" tab to the settings page allowing admins to see sessions and also log people out.

### Improve `Use OIDC for WP logins` setting UI
* Priority: Low
* Size: Small
* Reason: De-clutter the UI, make it easier to understand
* Details:
  * move the recovery commands to a popup that shows when you click a "Help! I locked myself out" link.
  * move the warnings to a popup that appears when you click "Yes" (or maybe "Save").  Two checkboxes, when both are checked an enable button becomes active that will actually save the change.

### Custom 401 and 403 pages
* Priority: Low
* Size: Small
* Reason: Better user experience
* Details:
  * Setting for URL to send user to (instead of the default plugin error) when a user needs to authenticate.
  * Setting for URL to send user to (instead of the default plugin error) when a user is denied access.

### Restrict access by tag
* Priority: Low
* Size: Medium
* Reason: Completeness
* Details:
  * For each tag, allow website administrator to specify which groups are allowed to view content with that tag.

### Restrict access by category
* Priority: Low
* Size: Medium
* Reason: Completeness
* Details:
	* For each category, allow website administrator to specify which groups are allowed to view content in that category.

### Restrict access by URL path
* Priority: Low
* Size: Medium
* Reason: Completeness
* Details:
  * Allow administrator to specify a list of URL paths
  * For each URL path, restrict access at and below that path to the groups the administrator specifies.
  * More specific (longer) paths take precedence over less specific (shorter) paths; restrictions are not applied hierarchically.

### Guest user support
* Priority: Low
* Size: Large
* Reason: Completeness
* Details:
  * Support U-M Friend accounts as both users and group members.
  * Support U-M Social login accounts as both users and group members.
  * Add new special group, `( Logged-in Guests )`
  * Add new special group, `( Logged-in Non-Guests )`
  * The existing special group `( Logged-in Users )` will encompass both guests and non-guests.

### Add support for refresh tokens
* Priority: Low
* Size: Medium
* Reason: React to authorization changes more quickly
* Details:
  * pick up authorization changes within 10 minutes (at U-M), rather than WP session expiration time which could be 1 day.
  * At U-M, IdP config for client needs to be changed to allow refresh_token grant (currently on per-client basis, and not supported via the OIDC provisioning API).
	* See the 8/3/2020 11:09 am TDx comment at https://teamdynamix.umich.edu/TDNext/Apps/31/Tickets/TicketDet.aspx?TicketID=51207

### Support for using Google OIDC as an identity provider
* Priority: Low
* Size: Medium
* Reason: Would be a nice option for non-UM organizations.
* Details:
  * Need to figure out how groups could work.
