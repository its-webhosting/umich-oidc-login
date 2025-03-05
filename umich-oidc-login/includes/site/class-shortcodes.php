<?php
/**
 * UMich OIDC shortcodes.
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

use function UMich_OIDC_Login\Core\log_message;

/**
 * UMich OIDC shortcodes
 *
 * @package    UMich_OIDC_Login\Site
 */
class Shortcodes {

	/**
	 * Context for this WordPress request / this run of the plugin.
	 *
	 * @var      object    $ctx    Context passed to us by our creator.
	 */
	private $ctx;

	/**
	 * ID for the current shortcode, incremented for each additional shortcode. The value 0 is used in our JavaScript
	 * to indicate "no shortcode present".
	 *
	 * @var      integer    $shortcode_id   ID for the current shortcode, or 0 if none.
	 */
	private $shortcode_id = 1;

	/**
	 * Create and initialize the Shortcodes object.
	 *
	 * @param object $ctx Context for this WordPress request / this run of the plugin.
	 */
	public function __construct( $ctx ) {

		$this->ctx = $ctx;

		// If the user is logged in to WordPress, we may need to preview raw shortcodes.
		if ( \is_user_logged_in() ) {
			\wp_enqueue_script(
				'umich-oidc-shortcodes',
				UMICH_OIDC_LOGIN_DIR_URL . '/assets/js/shortcode-protection.js',
				array(),
				UMICH_OIDC_LOGIN_VERSION_INT,
				true
			);
			\wp_enqueue_style(
				'umich-oidc-shortcodes-styles',
				UMICH_OIDC_LOGIN_DIR_URL . '/assets/css/shortcode-protection.css',
				array(),
				UMICH_OIDC_LOGIN_VERSION_INT
			);

		}

	}

	/**
	 * Convert a variable to a string.
	 *
	 * @param mixed  $info Thing to print.
	 * @param string $unprintable What to return if the thing can't be printed.
	 * @param string $separator separator to use between array elements.
	 * @param string $dictionary what to do with associative arrays: "keys" will give keys as a list, "values" will give values as a list, anything else will be used as a separator between keys and values which are then put into a list.
	 *
	 * @return string Shortcode output - requested user info
	 *
	 * TODO: this function could be better, for example marking the start
	 * and end of arrays but only if they are nested.
	 */
	private function to_string( $info, $unprintable, $separator, $dictionary ) {

		if ( \is_string( $info ) ||
			\is_float( $info ) ||
			\is_int( $info ) ) {
			return \strval( $info );
		}

		if ( \is_bool( $info ) ) {
			return $info ? 'true' : 'false';
		}

		if ( \is_array( $info ) ) {
			if ( \array_is_list( $info ) ) {
				// Items in the list could be anything.
				$printable = array();
				foreach ( $info as $item ) {
					$printable[] = $this->to_string( $item, $unprintable, $separator, $dictionary );
				}
				return \implode( $separator, $printable );
			}
			// it's an associative array.
			if ( 'keys' === $dictionary ) {
				// keys will always be strings.
				$keys = \array_keys( $info );
				\sort( $keys );
				return \implode( $separator, $keys );
			}
			if ( 'values' === $dictionary ) {
				$keys = \array_keys( $info );
				\sort( $keys );
				$values = array();
				foreach ( $keys as $k ) {
					$values[] = $info[ $k ];
				}
				// Values could be anything.
				return $this->to_string( $values, $unprintable, $separator, $dictionary );
			}
			// we want both keys and values.
			$keys = \array_keys( $info );
			\sort( $keys );
			$printable = array();
			foreach ( $keys as $k ) {
				$printable[] = $k . $dictionary . $this->to_string( $info[ $k ], $unprintable, $separator, $dictionary );
			}
			return \implode( $separator, $printable );

		}

		if ( \is_object( $info ) && \method_exists( $info, '__toString' ) ) {
			return \strval( $info );
		}

		return $unprintable;
	}

	/**
	 * Shortcode umich_oidc_url.
	 *
	 * Return a login or logout URL.
	 *
	 * @param string $atts Shortcode attributes.
	 * @param string $content Shortcode content. Should always be empty since this shortcode should not be used as an enclosing shortcode.
	 * @param string $tag Shortcode name.
	 *
	 * @return string Shortcode output - requested URL, escaped.
	 */
	public function url( $atts = array(), $content = null, $tag = '' ) {

		$ctx = $this->ctx;

		$atts = \array_change_key_case( (array) $atts, CASE_LOWER );
		// override default attributes with user attributes.
		$atts   = \shortcode_atts(
			array(
				'type'   => 'login-logout', // what type of URL to generate.
				'return' => '',  // where to go after successful login/logout: "here" (original page), "home" for the site's main page, some URL, or '' to dyanamically determine.
			),
			$atts,
			$tag
		);
		$type   = $atts['type'];
		$return = $atts['return'];

		if ( 'login-logout' === $type ) {
			$oidc_user = $ctx->oidc_user;
			$type      = $oidc_user->logged_in() ? 'logout' : 'login';
		}

		$oidc = $ctx->oidc;
		return $oidc->get_oidc_url( $type, $return );
	}

	/**
	 * Return text for an HTML element for a button or link.
	 *
	 * @param string $element Element to create ("button" or "link").
	 * @param string $atts Shortcode attributes.
	 * @param string $content Shortcode content. Should always be empty since this shortcode should not be used as an enclosing shortcode.
	 * @param string $tag Shortcode name.
	 *
	 * @return string Shortcode output - requested HTML element.
	 */
	private function element( $element, $atts = array(), $content = null, $tag = '' ) {

		$ctx       = $this->ctx;
		$orig_atts = $atts;
		$atts      = \array_change_key_case( (array) $atts, CASE_LOWER );

		// override default attributes with user attributes.
		$atts        = \shortcode_atts(
			array(
				'type'        => 'login-logout', // what type of URL to generate.
				'return'      => '',  // where to go after successful login/logout: "here" (original page), "home" for the site's main page, some URL, or '' to dyanamically determine.
				'text'        => '_default_',  // link text.
				'text_login'  => '_default_',  // link text.
				'text_logout' => '_default_',  // link text.
			),
			$atts,
			$tag
		);
		$type        = $atts['type'];
		$return      = $atts['return'];
		$text        = $atts['text'];
		$text_login  = $atts['text_login'];
		$text_logout = $atts['text_logout'];

		if ( 'login-logout' === $type ) {
			$oidc_user = $ctx->oidc_user;
			$type      = $oidc_user->logged_in() ? 'logout' : 'login';
			if ( 'login' === $type && '_default_' !== $text_login ) {
				$text = $text_login;
			} elseif ( 'logout' === $type && '_default_' !== $text_logout ) {
				$text = $text_logout;
			}
		}

		if ( '_default_' === $text ) {
			$text = ( 'logout' === $type ) ? 'Log out' : 'Log in';
		}

		$oidc = $ctx->oidc;
		$url  = $oidc->get_oidc_url( $type, $return );

		$attributes = '';
		if ( true === $this->ctx->options['shortcode_html_attributes_allowed'] ) {
			foreach ( \array_keys( $orig_atts ) as $a ) {
				if ( ! \in_array( \strtolower( $a ), array( 'type', 'return', 'text', 'text_login', 'text_logout' ),
					true ) ) {
					$name       = \esc_html( $a );
					$value      = \esc_html( $orig_atts[ $a ] );
					$attributes .= " {$name}='{$value}'";
				}
			}
		}

		$text = \esc_html( $text );

		if ( 'a' === $element ) {
			$html = "<a href='{$url}'{$attributes}>{$text}</a>";
		} elseif ( 'button' === $element ) {
			$html = "<button onclick='window.location.href=\"{$url}\"'{$attributes}>{$text}</button>";
		} else {
			log_message( 'unrecognized element, suppressing button/link shortcode output' );
			return '';
		}

		if ( '' === $attributes ) {
			// As of UMICH OIDC Login 1.3.0, if there are no attributes, then the HTML cannot contain malicious content.
			return $html;
		}

		/* The shortcode could have malicious attributes, so only return the shortcode output if the post has
		 * been published.  This assumes the post has been reviewed for malicious content (not just OIDC button/link
		 * shortcodes, but also HTML from users with the unfiltered_html capability) before being approved and
		 * published.
		 */
		$post = \get_post();
		if ( $post && 'publish' !== \get_post_status( $post ) ) {
			$wp_user = \wp_get_current_user();
			if ( $wp_user->has_cap( 'edit_post', $post->ID ) ) {
				// The user can edit this post, so show them the raw shortcode.

				$attributes = \array_reduce(
					\array_keys( $orig_atts ),
					function ( $carry, $key ) use ( $orig_atts ) {
						$value = $orig_atts[ $key ];
						return $carry . " {$key}='{$value}'";
					},
					''
				);

				// Currently, this function only handles shortcodes without content.  This may change in the future.
				$shortcode = \esc_html( "[{$tag}{$attributes}]" );

				// Display the raw shortcode but let the user click on it to see the rendered output.
				$b64_shortcode = \base64_encode( $shortcode );
				$b64_html = \base64_encode( $html );
				$preview_html = <<<END
<a href='#'
    class='umich-oidc-shortcode-preview'
    data-shortcode-id='{$this->shortcode_id}'
    >{$shortcode}</a>
<script type="text/javascript">
    window.umich_oidc_shortcode_preview = window.umich_oidc_shortcode_preview || {};
	window.umich_oidc_shortcode_preview['{$this->shortcode_id}'] = {
		shortcode: '{$b64_shortcode}',
		html: '{$b64_html}'
	};
</script>
END;
				$this->shortcode_id++;
				return $preview_html;
			}
			# The post isn't published and the user can't edit it, so just remove the shortcode from the page.
			return '';
		}

		# The post is published, so show the shortcode output.
		return $html;
	}

	/**
	 * Shortcode umich_oidc_link.
	 *
	 * Return an HTML string for a login or logout link.
	 *
	 * @param string $atts Shortcode attributes.
	 * @param string $content Shortcode content. Should always be empty since this shortcode should not be used as an enclosing shortcode.
	 * @param string $tag Shortcode name.
	 *
	 * @return string Shortcode output - requested URL.
	 */
	public function link( $atts = array(), $content = null, $tag = '' ) {
		return $this->element( 'a', $atts, $content, $tag );
	}

	/**
	 * Shortcode umich_oidc_button.
	 *
	 * Return an HTML string for  a login or logout button.
	 *
	 * @param string $atts Shortcode attributes.
	 * @param string $content Shortcode content. Should always be empty since this shortcode should not be used as an enclosing shortcode.
	 * @param string $tag Shortcode name.
	 *
	 * @return string Shortcode output - requested URL.
	 */
	public function button( $atts = array(), $content = null, $tag = '' ) {
		return $this->element( 'button', $atts, $content, $tag );
	}

	/**
	 * Shortcode umich_oidc_userinfo (with alias umich_oidc_user_info)
	 *
	 * @param string $atts Shortcode attributes.
	 * @param string $content Shortcode content. Should always be empty since this shortcode should not be used as an enclosing shortcode.
	 * @param string $tag Shortcode name.
	 *
	 * @return string Shortcode output - requested user info, escaped for HTML.
	 */
	public function userinfo( $atts = array(), $content = null, $tag = '' ) {

		$ctx = $this->ctx;

		$atts = \array_change_key_case( (array) $atts, CASE_LOWER );
		// override default attributes with user attributes.
		$atts        = \shortcode_atts(
			array(
				'type'        => '',    // what user info to get.
				'default'     => '',    // what to return if the requested user info is not present.
				'unprintable' => '???', // what to use for things that can't be printed (objects, etc.).
				'separator'   => ', ',  // separator to use between array elements.
				'dictionary'  => '=',   // what to do with associative arrays: "keys" will give keys as a list, "values" will give values as a list, anything else will be used as a separator between keys and values which are then put into a list.
			),
			$atts,
			$tag
		);
		$type        = $atts['type'];
		$default     = $atts['default'];
		$unprintable = $atts['unprintable'];
		$separator   = $atts['separator'];
		$dictionary  = $atts['dictionary'];

		if ( '' === $type ) {
			log_message( "{$tag}: no userinfo type specified" );
			return $default;
		}

		$oidc_user = $ctx->oidc_user;
		$info      = $oidc_user->get_userinfo( $type, null );
		if ( \is_null( $info ) ) {
			log_message( "{$tag}: userinfo for {$type} is null" );
			return \esc_html( $default );
		}

		$output = $this->to_string( $info, $unprintable, $separator, $dictionary );
		return \esc_html( $output );
	}

	/**
	 * Shortcodes umich_oidc_logged_in and umich_oidc_not_logged_in
	 *
	 * Enclosing shortcodes that show content only if the visitor is /
	 * is not logged in.
	 *
	 * @param string $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag Shortcode name.
	 *
	 * @return string Shortcode output - content.
	 */
	public function logged_in( $atts = array(), $content = null, $tag = '' ) {

		$ctx = $this->ctx;

		$oidc_user      = $ctx->oidc_user;
		$user_logged_in = \is_user_logged_in() || $oidc_user->logged_in();
		if ( 'umich_oidc_logged_in' === $tag && ! $user_logged_in ) {
			return '';
		}
		if ( 'umich_oidc_not_logged_in' === $tag && $user_logged_in ) {
			return '';
		}

		$atts = \array_change_key_case( (array) $atts, CASE_LOWER );
		// override default attributes with user attributes.
		$atts = \shortcode_atts(
			array(
				'flow' => 'paragraph', // where the content should be on the page: "paragraph" for its own paragraph, "inline" for not being wrapped in any other element.
			),
			$atts,
			$tag
		);
		$flow = $atts['flow'];

		// Handle any shortcodes in the content.
		$content = \do_shortcode( $content );

		if ( 'paragraph' === $flow ) {
			$content = '<p>' . $content . '</p>';
		}

		/* Despite https://developer.wordpress.org/plugins/shortcodes/enclosing-shortcodes/#processing-enclosed-content
		 * saying "It is the responsibility of the handler function to secure the output," testing with WordPress 6.7.1
		 * shows content saved by users who do not have the unfiltered_html capability does correctly get filtered.
		 * And we want to leave the content unfiltered for users who do have that capability.
		 */

		return $content;
	}

	/**
	 * Shortcodes umich_oidc_member and umich_oidc_not_member
	 *
	 * Enclosing shortcode that shows content only if the visitor is
	 * logged in and a member of at least one of the listed groups, or
	 * is a WordPress administrator.
	 *
	 * @param string $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag Shortcode name.
	 *
	 * @return string Shortcode output - content.
	 */
	public function member( $atts = array(), $content = null, $tag = '' ) {

		$ctx = $this->ctx;

		$is_wp_admin    = \is_super_admin();
		$oidc_user      = $ctx->oidc_user;
		$logged_in_oidc = $oidc_user->logged_in();
		if ( ! $logged_in_oidc && ! $is_wp_admin ) {
			return '';
		}

		$atts = \array_change_key_case( (array) $atts, CASE_LOWER );
		// override default attributes with user attributes.
		$atts = \shortcode_atts(
			array(
				'flow'   => 'paragraph', // where the content should be on the page: "paragraph" for its own paragraph, "inline" for not being wrapped in any other element.
				'groups' => '', // which groups to check for membership (comma separated list).
				'group'  => '', // alias for "groups".
			),
			$atts,
			$tag
		);
		$flow = $atts['flow'];

		// if "groups" is empty, see if "group" was accidentally used instead.
		$groups = ( '' !== $atts['groups'] ) ? $atts['groups'] : $atts['group'];
		$groups = ( '' !== $groups ) ? \explode( ',', $groups ) : array();
		$groups = \array_map(
			function ( $g ) {
				return \trim( $g );
			},
			$groups
		);

		$user_groups = $oidc_user->groups();

		$matches = \count( \array_intersect( $groups, $user_groups ) );
		if ( $is_wp_admin ) {
			// A WordPress administrator is considered a member of all groups.
			++$matches;
		}

		if ( 'umich_oidc_member' === $tag && 0 === $matches ) {
			log_message( "{$tag}: no groups match, denying" );
			return '';
		}
		if ( 'umich_oidc_not_member' === $tag && 0 !== $matches ) {
			// all groups must not match.
			log_message( "{$tag}: at least one group matches, denying" );
			return '';
		}

		// Handle any shortcodes in the content.
		$content = \do_shortcode( $content );

		if ( 'paragraph' === $flow ) {
			$content = '<p>' . $content . '</p>';
		}

		/* Despite https://developer.wordpress.org/plugins/shortcodes/enclosing-shortcodes/#processing-enclosed-content
		 * saying "It is the responsibility of the handler function to secure the output," testing with WordPress 6.7.1
		 * shows content saved by users who do not have the unfiltered_html capability does correctly get filtered.
		 * And we want to leave the content unfiltered for users who do have that capability.
		 */

		return $content;
	}

	/**
	 * Filter for the WordPress login form login message.
	 *
	 * @param string $content Login message.
	 *
	 * @return string Modified login message.
	 */
	public function login_form( $content ) {

		if ( 'no' === $this->ctx->options['use_oidc_for_wp_users'] ) {
			return $content;
		}

		$return = '';
		if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
			$return = 'home';
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- wp-login.php does this, too.
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$return = \esc_url_raw( \wp_unslash( $_REQUEST['redirect_to'] ) );
		}

		$atts = array(
			'type'   => 'login',
			'return' => $return,
			// TODO: allow this to be changed via a plugin setting.
			'text'   => 'Log in via Single Sign On',
			'class'  => 'button button-large',
			'style'  => 'width: 100%; background: #2271b1; border-color: #2271b1; color: #fff;',
		);

		$content .= '<div style="padding: 24px; background: #fff; border: 1px solid #c3c4c7;">';
		$content .= $this->button( $atts, '', 'umich_oidc_button' );
		$content .= '</div>';
		$content .= '<div style="width: 100%; text-align: center; padding-top: 1em;">&mdash; <i>or, log in with a local WordPress account</i> &mdash;';

		return $content;
	}
}
