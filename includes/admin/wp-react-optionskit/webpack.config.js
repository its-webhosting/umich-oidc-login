/**
 * Webpack configuration for WP React OptionsKit.
 *
 * @copyright  2023 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

const defaults = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaults,
	externals: {
		react: 'React',
		'react-dom': 'ReactDOM',
	},
};
