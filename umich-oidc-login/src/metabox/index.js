/**
 * UMich OIDC Login access restriction metabox for pages and posts.
 *
 * Works in both Gutenberg and the Classic Editor.
 *
 * @copyright  2023 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

const { createRoot, render, createElement, useState } = wp.element;
const { BaseControl, Notice } = wp.components;

import Select from 'react-select';

const UmichOidcAccess = () => {
	const settings = window.umichOidcMetabox;
	const [ accessValue, setAccessValue ] = useState(
		settings.selectedGroups.map( ( x ) => x.value ).join( ',' )
	);
	const [ message, setMessage ] = useState( '' );
	const labelText = `Who can access this ${ settings.postType }?`;
	const helpText = `Allow only members of these groups (plus administrators) to visit this ${ settings.postType }.`;
	const styles = {
		control: ( base ) => ( {
			...base,
			minHeight: 34,
		} ),
		Input: ( base ) => ( {
			...base,
			minHeight: 32,
		} ),
		dropdownIndicator: ( base ) => ( {
			...base,
			paddingTop: 0,
			paddingBottom: 0,
		} ),
		clearIndicator: ( base ) => ( {
			...base,
			paddingTop: 0,
			paddingBottom: 0,
		} ),
		multiValue: ( base ) => ( {
			...base,
			backgroundColor: '#b2d4ff',
		} ),
		multiValueLabel: ( base ) => ( {
			...base,
			color: '#fff',
			backgroundColor: '#007cba',
		} ),
	};

	function isValid( v ) {
		if ( v.length === 0 ) {
			setMessage( 'Must have at least one group' );
			return false;
		}
		if ( v.length > 1 ) {
			if ( v.find( ( { value } ) => value === '_everyone_' ) ) {
				setMessage(
					'"( Everyone )" cannot be used together with other groups.'
				);
				return false;
			}
			if ( v.find( ( { value } ) => value === '_logged_in_' ) ) {
				setMessage(
					'"( Logged-in Users )" cannot be used together with other groups.'
				);
				return false;
			}
		}
		setMessage( '' );
		return true;
	}

	function checkValue( v ) {
		if ( ! isValid( v ) ) {
			v = settings.selectedGroups;
		}
		setAccessValue( v.map( ( x ) => x.value ).join( ',' ) );
	}

	return (
		<>
			<BaseControl
				id="umich-oidc-metabox-multiselect"
				help={ helpText }
				label={ labelText }
				className="components-select-control optionskit-multiselect-field"
			>
				<Select
					defaultValue={ settings.selectedGroups }
					isMulti
					name="_umich_oidc_access_select"
					placeholder="Select one or more groups..."
					options={ settings.availableGroups }
					onChange={ checkValue }
					styles={ styles }
					className="basic-multi-select"
					classNamePrefix="select"
				/>
			</BaseControl>
			{ message ? (
				<Notice status="error" isDismissible={ false }>
					<b>{ message }</b>
				</Notice>
			) : null }
			<input
				type="hidden"
				name="_umich_oidc_access"
				id="_umich_oidc_access"
				value={ accessValue }
			/>
		</>
	);
};

const domElement = document.getElementById( 'umich-oidc-metabox' );
const uiElement = createElement( UmichOidcAccess );

if ( createRoot ) {
	createRoot( domElement ).render( uiElement ); // React 18 and later
} else {
	render( uiElement, domElement ); // React 17 and before
}
