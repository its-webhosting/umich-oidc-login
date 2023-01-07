
# Test procedures

## Basic tests

* Set WordPress session length to be 5 minutes: `wp option patch update umich_oidc_settings session_length 300`
* Add user to a group used by OIDC.
* Log in to the website using OIDC, ensure user has access to a page that is only accessible by members of the OIDC group.
* Remove the user from the OIDC group.
* Wait for the session to expire.
* Click on a link to load the restricted page.  Verify that the user gets redirected to authenticate.

* Repeat the above procedure, except in the previous step reload the page rather than using a link.
* Restore session length to normal: `wp option patch update umich_oidc_settings session_length 86400`

* Set "Use OIDC for WordPress Users" to "no".
* Go to /wp-admin and make WordPress logins function correctly, that the session times out properly, and that the user can reauthenticate properly.
* Go to /wp-login.php and make WordPress logins function correctly.
* Go to main page and make sure OIDC logins function correctly, that the session times out properly, and the user gets redirected back to the main page after reauthenticating.
* Paste in the URL of a page that requires login make sure that the user gets automatically prompted to log in via OIDC, that the session times out properly, and the user gets redirected back to the protected page after reauthenticating.
* Go to a page that requires login.  Once viewing the page as a logged in user, log out and make sure that the user gets redirected back to the site main page.

* Set "Use OIDC for WordPress Users" to "optional".
* Go to /wp-admin and make sure both login options (OIDC and WP) function correctly.
* Go to /wp-login.php and make sure both login options (OIDC and WP) function correctly.
* Try as an OIDC user that does not have a corresponding WordPress account, make sure that the user gets an "Access Denied" error.
* Go to main page and make sure OIDC logins function correctly, that the session times out properly, and the user gets redirected back to the main page after reauthenticating.
* Paste in the URL of a page that requires login make sure that the user gets automatically prompted to log in via OIDC, that the session times out properly, and the user gets redirected back to the protected page after reauthenticating.
* Go to a page that requires login.  Once viewing the page as a logged in user, log out and make sure that the user gets redirected back to the site main page.

* Set "Use OIDC for WordPress Users" to "yes".
* Go to /wp-admin and verify that OIDC is used and that the user is logged in to WordPress afterward.
* Go to /wp-login.php and verify that OIDC is used and that the user is logged in to WordPress afterward (site main page).
* Log out via OIDC and verify that the user is logged out of both OIDC and their WordPress user account.
* Log out via the WordPress menu and verify that the user is logged out of both OIDC and their WordPress user account.
* Go to main page and make sure OIDC logins function correctly, that the session times out properly, and the user gets redirected back to the main page after reauthenticating.
* Paste in the URL of a page that requires login make sure that the user gets automatically prompted to log in via OIDC, that the session times out properly, and the user gets redirected back to the protected page after reauthenticating.
* Go to a page that requires login.  Once viewing the page as a logged in user, log out and make sure that the user gets redirected back to the site main page.


## REST API

* Create a WordPress user with role Editor
* Go to the plugins directory, run `git clone https://github.com/WP-API/Basic-Auth.git`
* Activate the plugin
* Make sure you have [HTTPIe](https://httpie.io/cli) or another command-line client available (`curl` can also be used).
* The below assumes that the site is restricted to logged-in users, that post 1 is restricted to a group, and that post 50 has no access control (letting the site access prevail).
```bash
export WP_USER='bjensen'
export WP_PASS='xxxx'
http https://markmont-wp-test.web.itd.umich.edu/wp-json/wp/v2/posts
http https://markmont-wp-test.web.itd.umich.edu/wp-json/wp/v2/pages
http --auth "${WP_USER}:${WP_PASS}" https://markmont-wp-test.web.itd.umich.edu/wp-json/wp/v2/posts/1  # denied
http --auth "${WP_USER}:${WP_PASS}" https://markmont-wp-test.web.itd.umich.edu/wp-json/wp/v2/posts/50/revisions/  # succeed
http --auth "${WP_USER}:${WP_PASS}" https://markmont-wp-test.web.itd.umich.edu/wp-json/wp/v2/posts/1/revisions/   # denied
http https://markmont-wp-test.web.itd.umich.edu/wp-json/wp/v2/search  # should sshow nothing (if site is restricted to logged in users)
http --auth "${WP_USER}:${WP_PASS}" https://markmont-wp-test.web.itd.umich.edu/wp-json/wp/v2/search  # should only show posts the user has permission to
http --auth "${WP_USER}:${WP_PASS}" https://markmont-wp-test.web.itd.umich.edu/wp-json/wp/v2/search?search=visible  # should not show the hello world post
```

## XMLRPC

* See https://developer.wordpress.org/apis/handbook/xml-rpc/
* XMLRPC methods we need to protect and the hook that is used to protect them:
    * wp.getPost - xmlrpc_prepare_post
    * wp.getPosts - xmlrpc_prepare_post
    * wp.getComment - xmlrpc_prepare_comment
    * wp.getComments - xmlrpc_prepare_comment
    * (obsolete) wp.getPage - xmlrpc_prepare_page
    * (obsolete) wp.getPages - xmlrpc_prepare_page
    * (obsolete) wp.getPageList - ?
    * blogger.getPost - xmlrpc_call
    * blogger.getRecentPosts - xmlrpc_call
    * metaWeblog.getPost - xmlrpc_call
    * metaWeblog.getRecentPosts - xmlrpc_call
* Same user and posts as in REST API testing above.
```bash
python3 -c 'import xmlrpc.client ; print( xmlrpc.client.ServerProxy("https://markmont-wp-test.web.itd.umich.edu/xmlrpc.php").demo.sayHello() );'
python3 -c 'import xmlrpc.client ; print( xmlrpc.client.ServerProxy("https://markmont-wp-test.web.itd.umich.edu/xmlrpc.php").wp.getOptions(0, "bjensen", "5*sInTheSky--") );'

python3 -c 'import xmlrpc.client ; print( xmlrpc.client.ServerProxy("https://markmont-wp-test.web.itd.umich.edu/xmlrpc.php").wp.getPost(0, "bjensen", "5*sInTheSky--", 50) );'  # allow
python3 -c 'import xmlrpc.client ; print( xmlrpc.client.ServerProxy("https://markmont-wp-test.web.itd.umich.edu/xmlrpc.php").wp.getPost(0, "bjensen", "5*sInTheSky--", 1) );'  # deny
python3 -c 'import xmlrpc.client ; print( xmlrpc.client.ServerProxy("https://markmont-wp-test.web.itd.umich.edu/xmlrpc.php").wp.getPosts(0, "bjensen", "5*sInTheSky--") );'  # show only permitted (yes to 50, no to 1)

python3 -c 'import xmlrpc.client ; print( xmlrpc.client.ServerProxy("https://markmont-wp-test.web.itd.umich.edu/xmlrpc.php").wp.getComment(0, "bjensen", "5*sInTheSky--", 1) );'  # deny
python3 -c 'import xmlrpc.client ; print( xmlrpc.client.ServerProxy("https://markmont-wp-test.web.itd.umich.edu/xmlrpc.php").wp.getComments(0, "bjensen", "5*sInTheSky--", ()) );'  # deny
```

