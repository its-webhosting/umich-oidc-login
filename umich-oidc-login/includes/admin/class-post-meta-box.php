<?php
/**
 * Post Meta Box for restricting access to pages and posts.
 *
 * This is not a Gutenberg sidebar plugin because we also need the functionality
 * in the Classic Editor.
 *
 * @package    UMich_OIDC_Login\Admin
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

namespace UMich_OIDC_Login\Admin;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function UMich_OIDC_Login\Core\log_message;

/**
 * Post Meta Box for restricting access to pages and posts.
 *
 * @package    UMich_OIDC_Login\Admin
 */
class Post_Meta_Box {

	/**
	 * Context for this WordPress request / this run of the plugin.
	 *
	 * @var      object    $ctx    Context passed to us by our creator.
	 */
	private $ctx;

	/**
	 * Create and initialize the Restrict_Access object.
	 *
	 * @param  object $ctx  Context for this WordPress request / this run of the plugin.
	 * @return void
	 */
	public function __construct( $ctx ) {
		$this->ctx = $ctx;
	}

	/**
	 * Load the CSS for meta boxes.
	 *
	 * @param  string $hook Admin page that is being rendered.
	 * @return void
	 */
	public function admin_scripts( $hook ) {
		if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
			$asset_file = include UMICH_OIDC_LOGIN_DIR . '/build/metabox/index.asset.php';
			foreach ( $asset_file['dependencies'] as $style ) {
				\wp_enqueue_style( $style );
			}
			\wp_register_script(
				'umich-oidc-metabox',
				UMICH_OIDC_LOGIN_DIR_URL . '/build/metabox/index.js',
				$asset_file['dependencies'],
				$asset_file['version'],
				true,
			);
			\wp_enqueue_script( 'umich-oidc-metabox' );
		}
	}

	/**
	 * Access meta box callback.
	 *
	 * @param    object $post  The post.
	 * @return   void
	 */
	public function access_meta_callback( $post ) {

		$ctx = $this->ctx;

		\wp_nonce_field( 'umich_oidc_access_meta', 'umich_oidc_meta_nonce' );
		$post_type = \get_post_type( $post->ID );
		$access    = $ctx->settings_page->post_access_groups( $post->ID );

		$selected = array();
		foreach ( $access as $group ) {
			if ( '_everyone_' === $group ) {
				$selected[] = array(
					'value' => '_everyone_',
					'label' => '( Everyone )',
				);
			} elseif ( '_logged_in_' === $group ) {
				$selected[] = array(
					'value' => '_logged_in_',
					'label' => '( Logged-in Users )',
				);
			} else {
				$selected[] = array(
					'value' => $group,
					'label' => $group,
				);
			}
		}

		$settings      = array(
			'postType'        => \esc_html( $post_type ),
			'availableGroups' => $ctx->settings_page->available_groups(),
			'selectedGroups'  => $selected,
		);
		$settings_json = \wp_json_encode( $settings );
		log_message( "UMich OIDC access meta box settings: $settings_json" );

		?>
		<script type="text/javascript">
			window.umichOidcMetabox =
				<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- it's JSON we just generated that should already have proper escaping.
					echo $settings_json;
				?>
		</script>
		<div id="umich-oidc-metabox"></div>
		<?php
	}

	/**
	 * Create the Access meta box.
	 *
	 * @return void
	 */
	public function access_meta_box() {

		\add_meta_box( 'umich_oidc_access_meta', 'UMich OIDC access', array( $this, 'access_meta_callback' ), null, 'side', 'high' );
	}

	/**
	 * Determine if the user can update the meta box value.
	 * This is called by the WP_REST_Meta_Fields API.
	 *
	 * See the hook definitions at https://developer.wordpress.org/reference/functions/map_meta_cap/
	 * and the invocation in wp-includes/capabilities.php
	 *
	 * @param bool   $allowed   Whether the user can add the object meta. Default false.
	 * @param string $meta_key  The meta key.
	 * @param int    $object_id Object ID.
	 *  param int       $user_id   User ID.
	 *  param string    $cap       Capability name.
	 *  param string[]  $caps      Array of the user's capabilities.
	 *
	 * @return bool     $allowed   Whether the user can add the object meta
	 */
	public function access_meta_auth( $allowed, $meta_key, $object_id /*, $user_id, $cap, $caps */ ) {

		if ( '_umich_oidc_access' !== $meta_key ) {
			log_message( "WARNING: access_meta_auth called with wrong meta_key: $meta_key" );
			return $allowed;
		}

		$parent_id = \wp_is_post_revision( $object_id );
		$id        = $parent_id ? $parent_id : $object_id;

		$post_type = \get_post_type( $id );

		log_message( "access_meta_auth called for $post_type $id (orig: $object_id)" );

		if ( 'page' === $post_type && ! \current_user_can( 'edit_page', $id ) ) {
			log_message( 'denied: not allowed to edit_page' );
			return false;
		}
		if ( ! \current_user_can( 'edit_post', $id ) ) {
			log_message( 'denied: not allowed to edit_post' );
			return false;
		}

		return true;
	}

	/**
	 * Sanitize the meta box access list.
	 * This is called by the WP_REST_Meta_Fields API.
	 *
	 * See the hook definitions at https://developer.wordpress.org/reference/functions/map_meta_cap/
	 * and the invocation in ./wp-includes/meta.php
	 *
	 * @param mixed  $meta_value Metadata value to sanitize.
	 * @param string $meta_key Metadata key.
	 *  param string $object_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
	 *                               or any other object type with an associated meta table.
	 *
	 * @return string     Sanitized metadata value.
	 */
	public function access_meta_sanitize( $meta_value, $meta_key /*, $object_type */ ) {

		if ( '_umich_oidc_access' !== $meta_key ) {
			log_message( "WARNING: access_meta_sanitize called with wrong meta_key: $meta_key" );
			return $meta_value;
		}

		$meta_value = \sanitize_text_field( \wp_unslash( $meta_value ) );
		$groups     = ( '' !== $meta_value ) ? \array_map( '\trim', \explode( ',', $meta_value ) ) : array();

		/*
		 * If a special group ( _everyone_ or _logged_in_ ) is
		 * used in conjunction with other groups, drop the special
		 * group from the list, leaving only the more restrictive
		 * permissions. We filter twice so _logged_in_ will have
		 * priority over _everyone_.
		 */
		if ( \count( $groups ) > 1 ) {
			$groups = \array_filter(
				$groups,
				function ( $g ) {
					return '_everyone_' !== $g;
				}
			);
		}
		if ( \count( $groups ) > 1 ) {
			$groups = \array_filter(
				$groups,
				function ( $g ) {
					return '_logged_in' !== $g;
				}
			);
		}

		return \implode( ',', $groups );
	}

	/**
	 * Save the Access meta box value.
	 *
	 * @param int      $post_id ID of the post, page, or other thing being saved.
	 * @param \WP_Post $post Post object.
	 *
	 * @return void
	 */
	public function access_meta_save( $post_id, $post ) {

		// Skip calls to save_post() that are not for us.
		if ( ! isset( $_REQUEST['umich_oidc_meta_nonce'] ) ) {
			return;
		}
		log_message( "access_meta_save called for $post_id" );

		if ( ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_REQUEST['umich_oidc_meta_nonce'] ) ), 'umich_oidc_access_meta' ) ) {
			log_message( "ERROR: bad nonce when saving post $post_id" );
			return;
		}

		if ( \is_multisite() && \ms_is_switched() ) {
			log_message( "skipping meta box save for post $post_id: multisite switch_to_blog() is active" );
			return;
		}

		if ( ! $this->access_meta_auth( false, '_umich_oidc_access', $post_id ) ) {
			log_message( "ERROR: access denied when saving post $post_id" );
			return;
		}

		$parent_id = \wp_is_post_revision( $post );
		$id        = $parent_id ? $parent_id : $post_id;

		$access = '';
		if ( isset( $_REQUEST['_umich_oidc_access'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- this call IS to sanitize it.
			$access = $this->access_meta_sanitize( $_REQUEST['_umich_oidc_access'], '_umich_oidc_access' );
		}

		log_message( "saving access for post $id: $access" );
		$result = \update_post_meta( $id, '_umich_oidc_access', $access );

		log_message( "update_metadata result: $result" );
	}
}
