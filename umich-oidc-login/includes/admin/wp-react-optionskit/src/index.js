/**
 * Frontend app for WP React OptionsKit.
 *
 * @copyright  2023 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

import { render } from '@wordpress/element';
import './index.scss';
import Panel from './Panel';

window.addEventListener(
	'load',
	() => {
		render( <Panel />, document.querySelector( '#optionskit-screen' ) );
	},
	false
);
