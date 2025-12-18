<?php
/**
 * Enforces access restrictions
 *
 * @package    UMich_OIDC_Login\Site
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

namespace UMich_OIDC_Login\Site;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function UMich_OIDC_Login\Core\log_umich_oidc;
use const UMich_OIDC_Login\Core\{ LEVEL_NOTHING, LEVEL_ERROR, LEVEL_USER_EVENT, LEVEL_NOTICE, LEVEL_INFO, LEVEL_DEBUG };

/**
 * Enforces access restrictions
 *
 * @package    UMich_OIDC_Login\Site
 */
class Restrict_Access {

	const ALLOWED              = 0;
	const NOT_INITIALIZED      = 1;
	const DENIED_NOT_LOGGED_IN = 2;
	const DENIED_NOT_IN_GROUPS = 3;

	/**
	 * Context for this WordPress request / this run of the plugin.
	 *
	 * @var      object    $ctx    Context passed to us by our creator.
	 */
	private $ctx;

	/**
	 * Cache result from check_site_access()
	 *
	 * @var      integer  $site_access_result  Cached result from check_site_access()
	 */
	private $site_access_result = self::NOT_INITIALIZED;

	/**
	 * Save whether access was denied due to not being logged in when checking a list of posts.
	 *
	 * @var      integer  $list_access_redirect  self::DENIED_NOT_LOGGED_IN if we should redirect.
	 */
	private $list_access_redirect = self::NOT_INITIALIZED;

	/**
	 * Create and initialize the Restrict_Access object.
	 *
	 * @param object $ctx Context for this WordPress request / this run of the plugin.
	 */
	public function __construct( $ctx ) {
		$this->ctx = $ctx;
	}

	/**
	 * Check to see if the current user has the specified access.
	 *
	 * @param array $access List of groups that can access the resource being checked.
	 *
	 * @return int  One of ALLOWED, DENIED_NOT_LOGGED_IN, DENIED_NOT_IN_GROUPS.
	 */
	public function check_access( $access ) {

		$ctx = $this->ctx;

		if ( 0 === \count( $access ) || '_everyone_' === $access[0] ) {
			return self::ALLOWED;
		}
		$ctx->public_resource = false;

		// Always allow administrators.
		// We need to check this _after_ we've set public_resource above.
		if ( \is_super_admin() ) {
			return self::ALLOWED;
		}

		$oidc_user      = $ctx->oidc_user;
		$logged_in_oidc = $oidc_user->logged_in();
		$logged_in_wp   = \is_user_logged_in();
		log_umich_oidc(
			LEVEL_INFO,
			'checking access: required=%s logged_in_oidc=%s session_state=%s logged_in_wp=%s',
			\implode( ',', $access ),
			$logged_in_oidc,
			$oidc_user->session_state(),
			$logged_in_wp
		);

		$logged_in = $logged_in_oidc || $logged_in_wp;
		if ( '_logged_in_' === $access[0] && $logged_in ) {
			return self::ALLOWED;
		}

		if ( ! $logged_in_oidc ) {
			// If the user is not logged in via OIDC, we can't check their groups.
			return self::DENIED_NOT_LOGGED_IN;
		}

		$user_groups = $oidc_user->groups();
		$matches     = \count( \array_intersect( $access, $user_groups ) );
		if ( 0 === $matches ) {
			log_umich_oidc( LEVEL_INFO, 'access denied: required=%s user=%s', \implode( ',', $access ), \implode( ',', $user_groups ) );
			return self::DENIED_NOT_IN_GROUPS;
		}

		return self::ALLOWED;
	}

	/**
	 * Check to see if the current user has the specified access.
	 *
	 * @return int  One of ALLOWED, DENIED_NOT_LOGGED_IN, DENIED_NOT_IN_GROUPS.
	 */
	public function check_site_access() {

		if ( self::NOT_INITIALIZED !== $this->site_access_result ) {
			return $this->site_access_result;
		}

		$result = $this->check_access( $this->ctx->options['restrict_site'] );

		$this->site_access_result = $result;

		return $result;
	}

	/**
	 * Redirect a user based on the reason they were denied access.
	 *
	 * @param int $type Type of denial: DENIED_NOT_LOGGED_IN, DENIED_NOT_IN_GROUPS.
	 *
	 * @return void Does not return, ends script
	 */
	public function denial_redirect( $type ) {

		$ctx     = $this->ctx;
		$oidc    = $ctx->oidc;
		$options = $ctx->options;

		if ( self::DENIED_NOT_LOGGED_IN === $type ) {
			/*
			 * Special case: Some hosting providers, especially those that use Varnish, strip cookies from
			 * requests for static assets in order to improve cache hit rates.  Avoid redirecting the user
			 * when the request is for /favicon.ico which WordPress will handle and generate if the file
			 * doesn't exist in the filesystem as that can cause an authenticated user to be continuously
			 * reauthenticated.  Instead, return a 401.
			 */
			if (
				( isset( $_SERVER['REQUEST_URI'] ) && '/favicon.ico' === $_SERVER['REQUEST_URI'] )
				|| is_favicon()
			) {
				log_umich_oidc( LEVEL_NOTICE, 'unauthenticated favicon.ico request, returning 401' );
				\wp_die( 'Authentication required', 'Authentication required', array( 'response' => 401 ) );
			}

			switch ( $options['use_oidc_for_wp_users'] ) {
				case 'yes':
					$oidc->redirect( $oidc->get_oidc_url( 'login', '' ) ); // Does not return.
					break;
				case 'optional':
					// Send user to WP login page so they can choose how to log in.
					\auth_redirect(); // Does not return.
					break;
				case 'no':
				default:
					if ( \is_admin() ) { // Admin interface.
						// Use WP login.
						\auth_redirect(); // Does not return.
					}
					$oidc->redirect( $oidc->get_oidc_url( 'login', '' ) ); // Does not return.
					break;
			}
		}

		if ( self::DENIED_NOT_IN_GROUPS === $type ) {
			// TODO: return 403 or redirect to custom 403 page.
			$back = '';
			// TODO: add a setting for the URL to send people to if they don't have access to the site.
			$main          = 'https://umich.edu/';
			$restrict_site = $options['restrict_site'];
			if ( '_everyone_' === $restrict_site[0] ) {
				$main = \home_url();
				$back = '<p><script>document.write(\'<a href="\' + document.referrer + \'">Go Back</a>\');</script></p>';
			}
			\wp_die(
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $back is a static string containing desired HTML.  There is no chance of injection attacks.
				'<h1>Permission Denied</h1><p>You do not have access to the content you requested.</p>' . $back,
				'Permission Denied',
				array(
					'response'  => 403,
					'link_text' => 'Go to the main page',
					'link_url'  => \esc_url_raw( $main ),
				)
			);
		}
	}

	/**
	 * Restrict access to site.
	 *
	 * Called by the template_redirect action.
	 *
	 * @return void
	 */
	public function restrict_site() {

		log_umich_oidc( LEVEL_DEBUG, 'restricting site' );

		// Always allow the AJAX calls for logging users in & out.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- verification not needed
		if ( defined( 'XMLRPC_REQUEST' ) ) {
			return;
		}
		if ( \wp_doing_ajax() && isset( $_GET['action'] ) &&
			( 'openid-connect-authorize' === $_GET['action'] || 'umich-oidc-logout' === $_GET['action'] )
		) {
			return;
		}

		$result = $this->check_site_access();
		if ( self::ALLOWED !== $result ) {
			$this->denial_redirect( $result );
		}

		// Return and allow access.
	}

	/**
	 * Restrict access to feeds
	 *
	 * Restrict access to feeds, including the_content_feed,
	 * the_excerpt_rss, and comment_text_rss.  Access is controlled
	 * by the "restrict_site" setting.
	 *
	 * @param string $content The original content for the feed.
	 *
	 * @return string The original content, or a "Permission denied" message.
	 */
	public function restrict_feed( $content ) {

		log_umich_oidc( LEVEL_DEBUG, 'restricting feed' );

		$result = $this->check_site_access();
		if ( self::DENIED_NOT_LOGGED_IN === $result ) {
			return 'Authentication required';
		}
		if ( self::DENIED_NOT_IN_GROUPS === $result ) {
			return 'Permission denied';
		}

		// Return and allow access.
		return $content;
	}

	/**
	 * Restrict access to site for a single post.
	 *
	 * Called by the template_redirect action -- if the current request is
	 * for a single post, we check the access here (rather than later in
	 * the_content) so that we can redirect the user instead of having to
	 * display an access-denied message within the site page.  This way,
	 * the user won't see the site header, footer, sidebar and so on.
	 *
	 * @return void
	 */
	public function restrict_site_single_post() {

		log_umich_oidc( LEVEL_DEBUG, 'restricting site single post' );

		// Always allow the AJAX calls for logging users in & out.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- verification not needed
		if ( defined( 'XMLRPC_REQUEST' ) ) {
			return;
		}
		if ( \wp_doing_ajax() && isset( $_GET['action'] ) &&
			( 'openid-connect-authorize' === $_GET['action'] || 'umich-oidc-logout' === $_GET['action'] )
		) {
			return;
		}

		if ( ! \is_singular() ) {
			log_umich_oidc( LEVEL_DEBUG, 'NOT SINGULAR' );
		}
		$post_id = \get_queried_object_id();
		log_umich_oidc( LEVEL_DEBUG, 'restrict site single post id = %s', $post_id );
		if ( $post_id <= 0 ) {
			return;
		}

		$access = $this->ctx->settings_page->post_access_groups( $post_id );
		$result = $this->check_access( $access );
		if ( self::ALLOWED !== $result ) {
			$this->denial_redirect( $result );
		}

		// Return and allow access.
	}

	/**
	 * Restrict access to page/post content
	 *
	 * @param string $excerpt The page/post excerpt.
	 *
	 * @return string the excerpt if the user has access, otherwise an "access forbidden" message
	 */
	public function get_the_excerpt( $excerpt ) {
		global $post;
		$post_id = isset( $post->ID ) ? $post->ID : 0;
		log_umich_oidc( LEVEL_DEBUG, 'restrict excerpt = %s', $post_id );
		if ( $post_id <= 0 ) {
			return '';
		}

		$ctx    = $this->ctx;
		$access = $ctx->settings_page->post_access_groups( $post_id );
		$result = $this->check_access( $access );
		if ( self::DENIED_NOT_LOGGED_IN === $result ) {
			$url = $ctx->oidc->get_oidc_url( 'login', '' );
			return "(You need to <a href='{$url}'>log in</a> to view this content.)";
		}
		if ( self::DENIED_NOT_IN_GROUPS === $result ) {
			return '(You do not have access to this content.)';
		}

		// Return and allow access.
		return $excerpt;
	}


	/**
	 * Restrict access to page/post content
	 *
	 * @param string $content The page/post content.
	 *
	 * @return string the content if the user has access, otherwise an "access forbidden" message
	 */
	public function the_content( $content ) {

		log_umich_oidc( LEVEL_DEBUG, 'restricting the_content' );
		$post = \get_post();
		if ( \is_null( $post ) || ! isset( $post->ID ) ) {
			return $content;
		}

		$ctx    = $this->ctx;
		$access = $ctx->settings_page->post_access_groups( $post->ID );
		$result = $this->check_access( $access );
		if ( self::DENIED_NOT_LOGGED_IN === $result ) {
			$url = $ctx->oidc->get_oidc_url( 'login', '' );
			return "(You need to <a href='{$url}'>log in</a> to view this content.)";
		}
		if ( self::DENIED_NOT_IN_GROUPS === $result ) {
			// TODO: let the user set this message or have nothing displayed at all by returning ''.
			return '(You do not have access to this content.)';
		}

		// Return and allow access.
		return $content;
	}

	/**
	 * Restrict access to all posts/pages in a list.
	 *
	 * Called by the following filters: the_posts, get_pages.
	 *
	 * IMPORTANT NOTE: The the_posts filter will not be called if the
	 * WP_Query arg suppress_filters is true.  This arg defaults to false
	 * for queries in general, BUT is set to true by \get_posts() !
	 * Despite that, the the_posts filter provides useful functionality,
	 * such as preventing posts the user does not have access to from
	 * showing up on the site's home page (at least for the themes
	 * WordPress ships), as well as preventing posts the user does not
	 * have access to from showing up in search results from WordPress'
	 * built-in search.
	 *
	 * @param array $posts The list of posts.
	 *
	 * @return array The filtered list of posts that the current user has access to.
	 */
	public function restrict_list( $posts ) {
		$allowed        = array();
		$redirect_count = 0;
		foreach ( $posts as $post ) {
			$access = $this->ctx->settings_page->post_access_groups( $post->ID );
			$result = $this->check_access( $access );
			switch ( $result ) {
				case self::ALLOWED:
					$allowed[] = $post;
					log_umich_oidc( LEVEL_INFO, 'restrict_list - post %s allowed', $post->ID );
					break;
				case self::DENIED_NOT_LOGGED_IN:
					log_umich_oidc( LEVEL_INFO, 'restrict_list - post %s denied: not logged in', $post->ID );
					++$redirect_count;
					break;
				case self::DENIED_NOT_IN_GROUPS:
					log_umich_oidc( LEVEL_INFO, 'restrict_list - post %s denied: not in groups', $post->ID );
					break;
				default:
					log_umich_oidc( LEVEL_ERROR, 'restrict_list - post %s denied: unexpected access check result %s', $post->ID, $result );
					break;
			}
		}
		if ( $redirect_count > 0 && count( $allowed ) === 0 && self::NOT_INITIALIZED === $this->list_access_redirect ) {
			$this->list_access_redirect = self::DENIED_NOT_LOGGED_IN;
		}
		return $allowed;
	}

	/**
	 * Intercept 404 errors and redirect the user to log in if the post
	 * actually exists but the user is not seeing it because they are
	 * not logged in.
	 *
	 * @param bool $preempt   Whether to short-circuit default header status handling. Default false.
	 *  param \WP_Query $wp_query  WordPress Query object.
	 *
	 * @return bool Whether to short-circuit default header status handling. Default false.
	 */
	public function handle_404( $preempt /*, $wp_query */ ) {
		if ( self::DENIED_NOT_LOGGED_IN === $this->list_access_redirect && ! is_favicon() ) {
			$this->denial_redirect( self::DENIED_NOT_LOGGED_IN );
		}
		return $preempt;
	}

	/**
	 * Generate a REST response for an error.
	 *
	 * @param string  $code     Error identifier for use by software.
	 * @param string  $message  Human readable message explaining the errror.
	 * @param integer $status   HTTP status code for the error.
	 *
	 * @return object The original response, if the current user has access to the post; otherwise, a permission denied response.
	 */
	private function rest_error( $code, $message, $status ) {
		return new \WP_REST_Response(
			array(
				'code'    => $code,
				'message' => $message,
				'data'    => array( 'status' => $status ),
			),
			$status,
			array()
		);
	}

	/**
	 * Perform a REST access check.
	 *
	 * @param integer $id       Post/page id to check the user's access for.
	 * @param object  $response The WP_REST_Response object.
	 *
	 * @return object The original response, if the current user has access to the post; otherwise, a permission denied response.
	 */
	private function rest_access( $id, $response ) {

		log_umich_oidc( LEVEL_DEBUG, 'restricting rest_access' );
		$result = $this->check_site_access();
		if ( self::DENIED_NOT_LOGGED_IN === $result ) {
			return $this->rest_error( 'rest_user_cannot_view', 'Authentication required', 401 );
		}
		if ( self::DENIED_NOT_IN_GROUPS === $result ) {
			return $this->rest_error( 'rest_cannot_view', 'You do not have access to this content', 403 );
		}

		if ( 0 === $id ) {
			return $response;
		}

		$access = $this->ctx->settings_page->post_access_groups( $id );
		$result = $this->check_access( $access );
		if ( self::DENIED_NOT_LOGGED_IN === $result ) {
			return $this->rest_error( 'rest_user_cannot_view', 'Authentication required', 401 );
		}
		if ( self::DENIED_NOT_IN_GROUPS === $result ) {
			return $this->rest_error( 'rest_cannot_view', 'You do not have access to this content', 403 );
		}

		return $response;
	}

	/**
	 * Restrict access to pages/posts via the REST API.
	 *
	 * @param object $response The WP_REST_Response object.
	 * @param object $post The WP_Post object.
	 *  param object $request The WP_REST_Request object.
	 *
	 * @return object The original response, if the current user has access to the post; otherwise, a permission denied response.
	 */
	public function rest_prepare_post( $response, $post ) {
		$id = isset( $post->ID ) ? $post->ID : 0;
		return $this->rest_access( $id, $response );
	}

	/**
	 * Restrict access to revisions via the REST API.
	 *
	 * @param object $response The WP_REST_Response object.
	 * @param object $post The WP_Post object.
	 *  param object $request The WP_REST_Request object.
	 *
	 * @return object The original response, if the current user has access to the post; otherwise, a permission denied response.
	 */
	public function rest_prepare_revision( $response, $post ) {
		$id = isset( $post->post_parent ) ? $post->post_parent : 0;
		return $this->rest_access( $id, $response );
	}

	/**
	 * Restrict access to comments via the REST API.
	 *
	 * @param object $response The WP_REST_Response object.
	 * @param object $comment The WP_Comment object.
	 *  param object $request The WP_REST_Request object.
	 *
	 * @return object The original response, if the current user has access to the comment's post; otherwise, a permission denied response.
	 */
	public function rest_prepare_comment( $response, $comment ) {
		$id = isset( $comment->comment_post_ID ) ? $comment->comment_post_ID : 0;
		return $this->rest_access( $id, $response );
	}

	/**
	 * Apply access control filtering to posts/pages found by queries.
	 *
	 * This is very hacky.  We're hooking the found_posts filter because
	 * as of WordPress 6.0 there is no way to filter REST search results
	 * (filters do not exist for rest_prepare_search_{post,term,post-format} )
	 *
	 * Modifying queries with field=ids affects more than just searches.
	 * If we remove this limitation, we could do access control at a very
	 * low level and anything WordPress queried that the user did not have
	 * access to would simply appear not to exist.
	 *
	 * @param integer $found_posts The number of posts found by the query.
	 * @param object  $query       The WP_Quest object.
	 *
	 * @return integer The modified number of posts after doing access control filtering.
	 */
	public function found_posts( $found_posts, $query ) {

		log_umich_oidc( LEVEL_DEBUG, 'restricting found_posts' );
		if ( 'ids' !== $query->query_vars['fields'] || empty( $query->query_vars['post_type'] ) ) {
			log_umich_oidc( LEVEL_DEBUG, 'restricting found_posts: no ids or post types' );
			return $found_posts;
		}

		$result = $this->check_site_access();
		if ( self::ALLOWED !== $result ) {
			$query->posts      = array();
			$query->post_count = 0;
			log_umich_oidc( LEVEL_DEBUG, 'restricting found_posts: site access denied' );
			return 0;
		}

		if ( null === $query->posts ) {
			log_umich_oidc( LEVEL_DEBUG, 'restricting found_posts: query posts is null' );
			return $found_posts;
		}
		if ( \is_integer( $query->posts ) ) {
			$access = $this->ctx->settings_page->post_access_groups( $query->posts );
			$result = $this->check_access( $access );
			if ( self::ALLOWED === $result ) {
				log_umich_oidc( LEVEL_DEBUG, 'restricting found_posts: integer post allowed' );
				return $found_posts;
			}
			$query->posts = null;
			log_umich_oidc( LEVEL_DEBUG, 'restricting found_posts: integer post denied' );
			return 0;
		}

		$posts = array();
		foreach ( $query->posts as $post ) {
			$access = $this->ctx->settings_page->post_access_groups( $post );
			$result = $this->check_access( $access );
			if ( self::ALLOWED === $result ) {
				$posts[] = $post;
			} else {
				--$found_posts;
			}
		}
		if ( $found_posts < 0 ) {
			$found_posts = 0;
		}
		$query->posts      = $posts;
		$query->post_count = count( $posts );
		log_umich_oidc( LEVEL_DEBUG, 'restricting found_posts: returning %s posts', $found_posts );
		return $found_posts;
	}

	/**
	 * Restrict access to posts and pages via XMLRPC.
	 *
	 * This is used for the xmlrpc_prepare_post and xmlrpc_prepare_page
	 * filters.  Note that we also do access control in the xmlrpc_call
	 * action which is called earlier and terminate the request before
	 * this filter gets called.
	 *
	 * @param array $_post  An array of modified post data.
	 * @param array $post   An array of post data.
	 *  param array $fields An array of post fields.
	 *
	 * @return array The filtered $_post
	 */
	public function xmlrpc_prepare_post( $_post, $post ) {
		log_umich_oidc( LEVEL_DEBUG, 'restricting xmlrpc_prepare_post' );

		// The page/post XMLRPC methods require login.
		$result = $this->check_site_access();
		if ( self::ALLOWED !== $result ) {
			$msg                   = 'You do not have access to this content.';
			$_post['post_title']   = $msg;
			$_post['post_excerpt'] = $msg;
			$_post['post_content'] = $msg;
			return $_post;
		}

		$access = $this->ctx->settings_page->post_access_groups( $post['ID'] );
		$result = $this->check_access( $access );
		if ( self::ALLOWED !== $result ) {
			$msg                   = 'You do not have access to this content.';
			$_post['post_title']   = $msg;
			$_post['post_excerpt'] = $msg;
			$_post['post_content'] = $msg;
			return $_post;
		}

		return $_post;
	}

	/**
	 * Return an error when a user does not have access to view a comment.
	 *
	 * @param array  $comment  An array of comment data.
	 * @param string $message  Error message.
	 *
	 * @return array A comment array with the error message and without sensitive data.
	 */
	private function xmlrpc_block_comment( $comment, $message ) {
		$_comment = array(
			'comment_id' => $comment['comment_id'],
			'parent'     => $comment['parent'],
			'content'    => $message,
			'post_id'    => $comment['post_id'],
			'post_title' => $message,
			'author'     => $message,
			'type'       => $comment['type'],
		);
		return $_comment;
	}

	/**
	 * Restrict access to comments via XMLRPC.
	 *
	 * This is used for the xmlrpc_prepare_comment filter.  Note that we
	 * also do access control in the xmlrpc_call action which is called
	 * earlier and terminate the request before this filter gets called.
	 *
	 * @param array $_comment  An array of modified comment data.
	 * @param array $comment   An array of comment data.
	 *
	 * @return array The filtered $_comment
	 */
	public function xmlrpc_prepare_comment( $_comment, $comment ) {
		log_umich_oidc( LEVEL_DEBUG, 'restricting xmlrpc_prepare_comment' );

		// The comment XMLRPC methods require login.
		$result = $this->check_site_access();
		if ( self::ALLOWED !== $result ) {
			return $this->xmlrpc_block_comment( $_comment, 'You do not have access to this content.' );
		}

		$access = $this->ctx->settings_page->post_access_groups( $comment['post_id'] );
		$result = $this->check_access( $access );
		if ( self::ALLOWED !== $result ) {
			return $this->xmlrpc_block_comment( $_comment, 'You do not have access to this content.' );
		}

		return $_comment;
	}

	/**
	 * Check access to a post or page for an XMLRPC call.
	 *
	 * @param integer $post_id  Post the user is trying to access.
	 * @param object  $server   The XML-RPC server instance.
	 *
	 * @return void
	 */
	public function xmlrpc_call_access( $post_id, $server ) {
		log_umich_oidc( LEVEL_DEBUG, 'restricting xmlrpc_call_access' );

		// Calls to $server->error() terminate the script.

		$result = $this->check_site_access();
		if ( self::DENIED_NOT_LOGGED_IN === $result ) {
			$server->error( 401, 'Authentication required' );
		}
		if ( self::DENIED_NOT_IN_GROUPS === $result ) {
			$server->error( 403, 'You do not have access to this content' );
		}

		if ( 0 === $post_id ) {
			return;
		}

		$access = $this->ctx->settings_page->post_access_groups( $post_id );
		$result = $this->check_access( $access );
		if ( self::DENIED_NOT_LOGGED_IN === $result ) {
			$server->error( 401, 'Authentication required' );
		}
		if ( self::DENIED_NOT_IN_GROUPS === $result ) {
			$server->error( 403, 'You do not have access to this content' );
		}

		// Return and allow access.
	}

	/**
	 * Restrict XMLPRC access.
	 *
	 * This is called by the xmlrpc_call action after WordPress user
	 * authentication but before any other work is done on the request.
	 * We don't have all the information we need to control access to
	 * some XMLRPC methods here, so the xmlrpc_prepare_post,
	 * xmlrpc_prepare_page, and xmlrpc_prepare_comment filters do the
	 * rest of the access control work.
	 *
	 * @param string       $name   The method name.
	 * @param array|string $args   The escaped arguments passed to the method.
	 * @param object       $server The XML-RPC server instance.
	 *
	 * @return void
	 */
	public function xmlrpc_call( $name, $args, $server ) {
		log_umich_oidc( LEVEL_DEBUG, 'xmlrpc call: %s', $name );
		log_umich_oidc( LEVEL_DEBUG, $args );

		/*
		 * We can't check the following XMLRPC methods here because
		 * they have to execute first before we know the post IDs.
		 * The xmlrpc_prepare_* filters will catch these and redact
		 * the content rather than returning an error status.
		 *
		 * wp.getPosts
		 * wp.getComments
		 */

		switch ( $name ) {
			case 'wp.getPost':
				$this->xmlrpc_call_access( (int) $args[3], $server );
				break;
			case 'wp.getPage':
			case 'blogger.getPost':
				$this->xmlrpc_call_access( (int) $args[1], $server );
				break;
			case 'metaWeblog.getPost':
				$this->xmlrpc_call_access( (int) $args[0], $server );
				break;
			case 'wp.getComment':
				$comment_id = (int) $args[3];
				$comment    = \get_comment( $comment_id );
				if ( ! $comment ) {
					$server->error( 404, 'Invalid comment ID.' );
				}
				$this->xmlrpc_call_access( $comment->comment_post_ID, $server );
				break;
			case 'wp.getComments':
				// This covers only one possible case. The other cases
				// are covered by the xmlrpc_prepare_comment filter.
				if ( isset( $struct['post_id'] ) ) {
					$post_id = absint( $struct['post_id'] );
					$this->xmlrpc_call_access( $post_id, $server );
				}
				break;
			case 'blogger.getRecentPosts':
			case 'metaWeblog.getRecentPosts':
				// Restrict this method to admins. If necessary, come back later,
				// remove the xmlrpc_call action, re-call $this->blogger_getRecentPosts,
				// re-add the xmlrpc_call action, and then filter the results
				// to perform access control.
				if ( ! \is_super_admin() ) {

					$server->error( 403, 'Restricted to administrators for now.' );
				}
				break;
		}
	}
}
