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
		log_message( "UMich OIDC access meta box settings: {$settings_json}" );

		?>
		<script>
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

		$ctx = $this->ctx;

		\add_meta_box( 'umich_oidc_access_meta', 'UMich OIDC access', array( $this, 'access_meta_callback' ), array( 'post', 'page' ), 'side', 'high' );
	}

	/**
	 * Create the Access meta box.
	 *
	 * @param int $post_id ID of the post being saved.
	 *
	 * @return void
	 */
	public function access_meta_box_save( $post_id ) {

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_REQUEST['umich_oidc_meta_nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_REQUEST['umich_oidc_meta_nonce'] ) ), 'umich_oidc_access_meta' ) ) {
			log_message( 'ERROR: bad nonce when saving post' );
			return;
		}

		$access = '';
		if ( isset( $_REQUEST['_umich_oidc_access'] ) ) {
			$access = \sanitize_text_field( \wp_unslash( $_REQUEST['_umich_oidc_access'] ) );
		}

		/*
		 * If a special group ( _everyone_ or _logged_in_ ) is
		 * used in conjunction with other groups, drop the special
		 * group from the list, leaving only the more restrictive
		 * permissions.
		 */
		$access = ( '' !== $access ) ? \array_map( '\trim', \explode( ',', $access ) ) : array();
		if ( \count( $access ) > 1 ) {
			$access = \array_filter(
				$access,
				function ( $g ) {
					return '_everyone_' !== $g;
				}
			);
		}
		if ( \count( $access ) > 1 ) {
			$access = \array_filter(
				$access,
				function ( $g ) {
					return '_logged_in' !== $g;
				}
			);
		}

		\update_post_meta( $post_id, '_umich_oidc_access', \implode( ',', $access ) );
	}
}
