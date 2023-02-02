<?php
/**
 * Post Meta Box for specifying restricted access to pages and posts.
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

use function UMich_OIDC_Login\Core\log_message as log_message;

/**
 * Post Meta Box for specifying restricted access to pages and posts.
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
			\wp_enqueue_script( 'umich_oidc_vue', UMICH_OIDC_LOGIN_DIR_URL . 'assets/js/vue-2.7.10.min.js', array(), UMICH_OIDC_LOGIN_VERSION, false );
			\wp_enqueue_script( 'umich_oidc_vue_multiselect', UMICH_OIDC_LOGIN_DIR_URL . 'assets/js/vue-multiselect-2.1.0.min.js', array(), UMICH_OIDC_LOGIN_VERSION, false );
			\wp_enqueue_style( 'umich_oidc_vue_multiselect_css', UMICH_OIDC_LOGIN_DIR_URL . 'assets/css/vue-multiselect-2.1.0.min.css', array(), UMICH_OIDC_LOGIN_VERSION );
			\wp_enqueue_style( 'umich_oidc_multiselect_local_css', UMICH_OIDC_LOGIN_DIR_URL . 'assets/css/metabox.css', array(), UMICH_OIDC_LOGIN_VERSION );
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
		$selected_json = \wp_json_encode( $selected );
		if ( false === $selected_json ) {
			$selected_json = '[]';
		}
		log_message( "selected groups json: {$selected_json}" );

		$available_groups      = $ctx->settings_page->available_groups();
		$available_groups_json = \wp_json_encode( $available_groups );
		if ( false === $available_groups_json ) {
			$available_groups_json = '[]';
		}

		?>
		<div id="umich-oidc-app">
			<div class="umich-oidc-wrapper">
				<label class="typo__label">Who can access this <?php echo \esc_html( $post_type ); ?>?</label>
				<multiselect v-model="value" name="_umich_oidc_access" :options="options" :multiple="true" :allow-empty=false :close-on-select="true" :clear-on-select="false" :preserve-search="true" placeholder="Select one or more groups" label="label" track-by="value" :preselect-first="false" @close="onTouch">
				</multiselect>
				<label class="typo__label form__label" id="umich-oidc-access-msg" v-show="isInvalid">{{ error_msg }}</label>
				<input type="hidden" name="_umich_oidc_access" id="_umich_oidc_access" v-bind:value="valueString" />
			</div>
		</div>

		<script>
			var umich_app = new Vue({
				el: '#umich-oidc-app',
				components: { Multiselect: window.VueMultiselect.default },
				data: function () {
					return {
						value: 
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- it's JSON we just generated that should have proper escaping
							echo $selected_json;
							?>
							,
						isTouched: false,
						error_msg: '',
						options:
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- it's JSON we just generated that should have proper escaping.
							echo $available_groups_json;
							?>
					}
				},
				computed: {
					valueString: function () {
						let valueArray = []
						this.value.forEach(function(item) {
							valueArray.push(item.value)
						})
						return valueArray.join(',');
					},
					isInvalid: function () {
						this.error_msg = ''
						if ( this.isTouched && this.value.length === 0 ) {
							this.error_msg = "Must have at least one group"
							return true
						}
						if ( this.value.length > 1 ) {
							if ( this.value.find(({ value }) => value === '_everyone_') ) {
								this.error_msg = '"( Everyone )" cannot be used together with other groups.';
								return true;
							}
							if ( this.value.find(({ value }) => value === '_logged_in') ) {
								this.error_msg = '"( Logged-in Users )" cannot be used together with other groups.';
								return true;
							}
						}
						return false
					}
				},
				methods: {
					onTouch: function () {
						this.isTouched = true
					}
				}
			})
			</script>
		<?php
	}

	/**
	 * Create the Access meta box.
	 *
	 * @return void
	 */
	public function access_meta_box() {

		$ctx = $this->ctx;

		\add_meta_box( 'umich_oidc_access_meta', 'Access', array( $this, 'access_meta_callback' ), array( 'post', 'page' ), 'side', 'high' );
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
				function( $g ) {
					return '_everyone_' !== $g;
				}
			);
		}
		if ( \count( $access ) > 1 ) {
			$access = \array_filter(
				$access,
				function( $g ) {
					return '_logged_in' !== $g;
				}
			);
		}

		\update_post_meta( $post_id, '_umich_oidc_access', \implode( ',', $access ) );
	}

}
