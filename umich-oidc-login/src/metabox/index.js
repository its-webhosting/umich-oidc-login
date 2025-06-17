/**
 * UMich OIDC Login access restriction metabox for pages and posts.
 *
 * Works in both Gutenberg and the Classic Editor.
 *
 * @copyright  2023 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

import { createRoot, render, createElement, useState, useEffect, useRef, useMemo } from '@wordpress/element';
import {BaseControl, Icon, Notice, Spinner} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import * as icons from "@wordpress/icons";

import Select from 'react-select';
import debounce from "lodash.debounce";
import isEqual from "react-fast-compare";


const UmichOidcAccess = () => {
	const settings = window.umichOidcMetabox;
	const selectRef = useRef(null);
	const [ accessValue, setAccessValue ] = useState( settings.selectedGroups );
	const [ lastValue, setLastValue ] = useState( settings.selectedGroups );
	const [ error, setError ] = useState( '' );
	const [ isAutosaving, setIsAutosaving] = useState( false );
	const [ submitCount, setSubmitCount ] = useState( 0 );
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
			whiteSpace: 'normal'
		} ),
	};

	function isValid( v ) {
		if ( v.length === 0 ) {
			setError( 'Must have at least one group' );
			return false;
		}
		if ( v.length > 1 ) {
			if ( v.find( ( { value } ) => value === '_everyone_' ) ) {
				setError('"( Everyone )" cannot be used together with other groups.' );
				return false;
			}
			if ( v.find( ( { value } ) => value === '_logged_in_' ) ) {
				setError('"( Logged-in Users )" cannot be used together with other groups.' );
				return false;
			}
		}
		// Check each value against settings.availableGroups.
		for ( const item of v ) {
			if ( ! settings.availableGroups.find( ( g ) => g.value === item.value ) ) {
				setError( `Invalid group: ${ item.value }` );
				return false;
			}
		}
		setError( '' );
		return true;
	}

	function checkValue( v ) {
		if ( ! isValid( v ) ) {
			v = settings.selectedGroups;
		}
		setAccessValue( v );
	}

	async function doAutoSave( value ) {
		setIsAutosaving( true );
		setSubmitCount( submitCount + 1 );
		apiFetch( {
			path: `/wp/v2/posts/${ settings.postId }`,
			method: 'POST',
			data: { meta: { '_umich_oidc_access': value.map( ( x ) => x.value ).join( ',' ) } },
		} ).then( ( res ) => {
			// If returned value is not the same as value, use the returned value instead for both accessValue and lastValue.
			let newValue = res.meta?._umich_oidc_access;
			if ( newValue ) {
				newValue = newValue.split( ',' ).map( ( x ) => {
					return { value: x, label: settings.availableGroups.find( ( g ) => g.value === x )?.label || x };
				} );
			   if ( ! isEqual( newValue, value ) ) {
				  console.log( 'Autosave returned different value', newValue );
				  value = newValue;
				  setError( 'Failed to save changes (server returned a different value).' );
				  setAccessValue( value );
				  selectRef.current?.setValue( value );
			   }
			}
			setLastValue( value );
		} ).catch( ( err ) => {
			console.error( 'Autosave failed', err );
			status = err.data?.status;
			if ( status === 401 || status === 403 ) {
				// Session timed out or nonce expired. Force reauthentication.
				setError('Session expired. Please log in again.');
			} else if ( err.message ) {
				setError( err.message );
			} else {
				setError( 'Unknown error' );
			}
			setAccessValue( lastValue );
		} ).finally( () => {
			setIsAutosaving( false );
		});
	}

	function AutoSaveFields() {

		const useDebounce = (callback) => {

			// From https://www.developerway.com/posts/debouncing-in-react

			const ref = useRef();

			useEffect( () => {
				ref.current = callback;
			}, [ callback ]);

			const debouncedCallback = useMemo( () => {
				const func = () => {
					ref.current?.();
				};

				return debounce( func, 1500 );
			}, []);

			return debouncedCallback;
		};

		const debouncedSubmit = useDebounce(async () => {
			if (  ! isAutosaving && ! isEqual( accessValue, lastValue ) && error === '' ) {
				await doAutoSave( accessValue );
			}
		} );

		useEffect( () => {
			if ( ! isAutosaving && ! isEqual(accessValue, lastValue) && error === '' ) {
				debouncedSubmit();
			}
		}, [ debouncedSubmit, accessValue, lastValue, isAutosaving, error ] );

		let pendingChanges = ! isEqual( accessValue, lastValue )
		let autosaveStatus = 'Settings status unknown';
		let icon = icons.help;
		let autosaveIcon = null;
		if ( ! pendingChanges && submitCount === 0 ) {
			autosaveStatus = 'No changes made.';
			icon = icons.border;
		} else if ( isAutosaving ) {
			autosaveStatus = 'Saving changes...';
			autosaveIcon = <Spinner className='optionskit-autosave-status-icon' />;
		} else if ( pendingChanges ) {
			autosaveStatus = 'Unsaved changes.';
			icon = icons.plusCircle;
		} else if ( error !== '' ) {
			autosaveStatus = 'Changes not saved due to errors.';
			icon = icons.info; // icons.error requires a later version of @wordpress/icons than 10.8.2.
		} else {
			autosaveStatus = 'Changes saved.';
			icon = icons.published;
		}
		if ( autosaveIcon === null ) {
			autosaveIcon = <Icon icon={icon} style={{verticalAlign: 'middle'}}/>;
		}

		return (
			<>
				<div className='optionskit-autosave-status'>
					{ autosaveIcon } { autosaveStatus }
				</div>
			</>
		);
	}

	return (
		<>
			<BaseControl
				id="umich-oidc-metabox-multiselect"
				help={ helpText }
				label={ labelText }
				className="components-select-control optionskit-multiselect-field"
				__nextHasNoMarginBottom
			>
				<Select
					ref={ selectRef }
					defaultValue={ accessValue }
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
			{ error ? (
				<Notice status="error" isDismissible={ false }>
					<b>{ error }</b>
				</Notice>
			) : null }
			{ settings.autosave ? (
				<AutoSaveFields />
			) : null }
			<input
				type="hidden"
				name="_umich_oidc_access"
				id="_umich_oidc_access"
				value={ accessValue.map( ( x ) => x.value ).join( ',' ) }
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
