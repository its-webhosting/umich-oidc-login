<?php
/**
 * PHP polyfill functions.
 *
 * @package    UMich_OIDC_Login
 * @copyright  2022 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 * @since      1.0.0
 */

// Note: these functions exist in the global namespace.

if ( ! function_exists( 'array_is_list' ) ) {
	/**
	 * Polyfill for array_is_list() which is not introduced until PHP 8.1.0
	 * from https://www.php.net/manual/en/function.array-is-list.php#127044
	 *
	 * @param array $array The array to test.
	 *
	 * @returns bool
	 *
	 * @since 1.0.0
	 */
	function array_is_list( array $array ): bool {
		$i = -1;
		foreach ( $array as $k => $v ) {
			++$i;
			if ( $k !== $i ) {
				return false;
			}
		}
		return true;
	}
}

