/**
 * Frontend app for WP React OptionsKit.
 *
 * @copyright  2023 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

import { createRoot, render, createElement } from '@wordpress/element';
import './index.scss';
import Panel from './Panel';

const domElement = document.getElementById( 'optionskit-screen' );
const uiElement = createElement( Panel );

if ( createRoot ) {
	createRoot( domElement ).render( uiElement ); // React 18 and later
} else {
	render( uiElement, domElement ); // React 17 and before
}
