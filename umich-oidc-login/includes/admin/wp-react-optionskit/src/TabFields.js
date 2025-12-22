/**
 * Field components for the WP React OptionsKit frontend app.
 *
 * @copyright  2023 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

import { useField } from 'formik';
import { Container, Row, Col } from 'react-grid-system';
import {
	BaseControl,
	TextControl,
	SelectControl,
	RadioControl,
	CheckboxControl,
	ToggleControl,
	Notice,
} from '@wordpress/components';
import Select from 'react-select';
import parse from 'html-react-parser';
import './TabFields.scss';

// from Jason Bunting, https://stackoverflow.com/a/359910
function executeFunctionByName( functionName, context /*, args */ ) {
	const args = Array.prototype.slice.call( arguments, 2 );
	const namespaces = functionName.split( '.' );
	const func = namespaces.pop();
	for ( let i = 0; i < namespaces.length; i++ ) {
		context = context[ namespaces[ i ] ];
	}
	return context[ func ].apply( context, args );
}

function handleValidation( validate ) {
	if ( typeof validate === 'function' ) {
		return validate;
	}
	if ( typeof validate === 'string' ) {
		return ( v ) => {
			return executeFunctionByName( validate, window, v );
		};
	}
	return undefined;
}

function OptionsKitTextInput( { description, validate, ...props } ) {
	const [ field, meta, helpers ] = useField( {
		name: props.name,
		validate: handleValidation( validate ),
	} );
	return (
		<>
			<TextControl
				help={ parse( description ) }
				{ ...field }
				{ ...props }
				onChange={ ( v ) => {
					helpers.setTouched( true, false );
					helpers.setValue( v, true );
				} }
				__nextHasNoMarginBottom
			/>
			{ meta.error && meta.touched && (
				<Notice status="error" isDismissible={ false }>
					{ meta.error }
				</Notice>
			) }
		</>
	);
}

function OptionsKitSelectInput( { description, options, validate, ...props } ) {
	const [ field, meta, helpers ] = useField( {
		name: props.name,
		validate: handleValidation( validate ),
	} );
	const opts = Object.keys( options ).map( ( item ) => ( {
		label: options[ item ],
		value: item,
	} ) );
	return (
		<>
			<SelectControl
				options={ opts }
				help={ parse( description ) }
				{ ...field }
				{ ...props }
				onChange={ ( v ) => {
					helpers.setTouched( true, false );
					helpers.setValue( v, true );
				} }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			{ meta.error && meta.touched && (
				<Notice status="error" isDismissible={ false }>
					{ meta.error }
				</Notice>
			) }
		</>
	);
}

function OptionsKitMultiSelectInput( {
	description,
	options,
	labels,
	validate,
	...props
} ) {
	const [ field, meta, helpers ] = useField( {
		name: props.name,
		validate: handleValidation( validate ),
	} );
	const value = options.filter( ( v ) => {
		return field.value.indexOf( v.value ) >= 0;
	} );
	const styles = {
		control: ( base ) => ( {
			...base,
			height: 34,
			minHeight: 34,
		} ),
		Input: ( base ) => ( {
			...base,
			height: 32,
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
	// don't pass { ...field } since it contains `value` which will prevent Select from updating.
	return (
		<>
			<BaseControl
				id={ props.id }
				help={ parse( description ) }
				className="components-select-control optionskit-multiselect-field"
				__nextHasNoMarginBottom
			>
				<Select
					isMulti
					options={ options }
					defaultValue={ value }
					placeholder={
						labels && labels.placeholder
							? labels.placeholder
							: 'Select...'
					}
					styles={ styles }
					{ ...props }
					onChange={ ( v ) => {
						const result = v.map( ( item ) => {
							return item.value;
						} );
						helpers.setTouched( true, false );
						helpers.setValue( result, true );
					} }
					onBlur={ field.onBlur }
				/>
			</BaseControl>
			{ meta.error && meta.touched && (
				<Notice status="error" isDismissible={ false }>
					{ meta.error }
				</Notice>
			) }
		</>
	);
}

function OptionsKitRadioInput( { description, options, validate, ...props } ) {
	const [ field, meta, helpers ] = useField( {
		name: props.name,
		validate: handleValidation( validate ),
	} );
	const opts = Object.keys( options ).map( ( item ) => ( {
		label: options[ item ],
		value: item,
	} ) );
	// don't pass { ...field } since it contains `value` which will prevent RadioControl from updating.
	return (
		<>
			<RadioControl
				options={ opts }
				help={ parse( description ) }
				{ ...props }
				onChange={ ( v ) => {
					helpers.setTouched( true, false );
					helpers.setValue( v, true );
				} }
				onBlur={ field.onBlur }
				selected={ field.value }
			/>
			{ meta.error && meta.touched && (
				<Notice status="error" isDismissible={ false }>
					{ meta.error }
				</Notice>
			) }
		</>
	);
}

function OptionsKitCheckboxInput( { description, validate, ...props } ) {
	const [ field, meta, helpers ] = useField( {
		name: props.name,
		validate: handleValidation( validate ),
	} );
	// don't pass { ...field } since it contains `value` which will prevent CheckboxControl from updating.
	return (
		<>
			<CheckboxControl
				help={ parse( description ) }
				{ ...props }
				onChange={ ( v ) => {
					helpers.setTouched( true, false );
					helpers.setValue( v, true );
				} }
				onBlur={ field.onBlur }
				checked={ field.value }
				__nextHasNoMarginBottom
			/>
			{ meta.error && meta.touched && (
				<Notice status="error" isDismissible={ false }>
					{ meta.error }
				</Notice>
			) }
		</>
	);
}

function OptionsKitToggleInput( { description, validate, ...props } ) {
	const [ field, meta, helpers ] = useField( {
		name: props.name,
		validate: handleValidation( validate ),
	} );
	// don't pass { ...field } since it contains `value` which will prevent ToggleControl from updating.
	return (
		<>
			<ToggleControl
				help={ parse( description ) }
				{ ...props }
				onChange={ ( v ) => {
					helpers.setTouched( true, false );
					helpers.setValue( v, true );
				} }
				onBlur={ field.onBlur }
				checked={ field.value }
				__nextHasNoMarginBottom
			/>
			{ meta.error && meta.touched && (
				<Notice status="error" isDismissible={ false }>
					{ meta.error }
				</Notice>
			) }
		</>
	);
}

function SettingsField( { setting } ) {
	let defaultValidation;
	switch ( setting.type ) {
		case 'text':
			defaultValidation = ( v ) => {
				if ( typeof v !== 'string' ) {
					return 'Internal error: invalid value (not a string).';
				}
				if ( !! setting.required && v.trim().length === 0 ) {
					return 'This field is required.';
				}
				return undefined;
			};
			return (
				<>
					<OptionsKitTextInput
						id={ setting.id }
						name={ setting.id }
						description={ setting.desc }
						validate={
							setting.validate
								? setting.validate
								: defaultValidation
						}
						__next40pxDefaultSize
					/>
				</>
			);

		case 'select':
			defaultValidation = ( v ) => {
				const valid = Object.keys( setting.options );
				if ( ! valid.includes( v ) ) {
					return 'Internal error: invalid value.';
				}
				return undefined;
			};
			return (
				<>
					<OptionsKitSelectInput
						id={ setting.id }
						name={ setting.id }
						description={ setting.desc }
						options={ setting.options }
						validate={
							setting.validate
								? setting.validate
								: defaultValidation
						}
					/>
				</>
			);

		case 'multiselect':
			defaultValidation = ( v ) => {
				const valid = setting.options.map( ( item ) => {
					return item.value;
				} );
				const invalid = v.filter( ( item ) => {
					return ! valid.includes( item );
				} );
				if ( invalid.length > 0 ) {
					return (
						'Internal error: invalid value(s): ' +
						invalid.join( ', ' )
					);
				}
				return undefined;
			};
			return (
				<>
					<OptionsKitMultiSelectInput
						id={ setting.id }
						name={ setting.id }
						description={ setting.desc }
						options={ setting.options }
						labels={ setting.labels }
						validate={
							setting.validate
								? setting.validate
								: defaultValidation
						}
					/>
				</>
			);

		case 'radio':
			defaultValidation = ( v ) => {
				const valid = Object.keys( setting.options );
				if ( ! valid.includes( v ) ) {
					return 'Internal error: invalid value.';
				}
				return undefined;
			};
			return (
				<>
					<OptionsKitRadioInput
						id={ setting.id }
						name={ setting.id }
						description={ setting.desc }
						options={ setting.options }
						validate={
							setting.validate
								? setting.validate
								: defaultValidation
						}
					/>
				</>
			);

		case 'checkbox':
			defaultValidation = ( v ) => {
				if ( typeof v !== 'boolean' ) {
					return 'Internal error: invalid value.';
				}
				return undefined;
			};
			return (
				<>
					<OptionsKitCheckboxInput
						id={ setting.id }
						name={ setting.id }
						label={ setting.label }
						description={ setting.desc }
						validate={
							setting.validate
								? setting.validate
								: defaultValidation
						}
					/>
				</>
			);

		case 'toggle':
			defaultValidation = ( v ) => {
				if ( typeof v !== 'boolean' ) {
					return 'Internal error: invalid value.';
				}
				return undefined;
			};
			return (
				<>
					<OptionsKitToggleInput
						id={ setting.id }
						name={ setting.id }
						label={ setting.label }
						description={ setting.desc }
						validate={
							setting.validate
								? setting.validate
								: defaultValidation
						}
					/>
				</>
			);

		case 'html':
			return <div id={ setting.id }>{ parse( setting.html ) }</div>;

		case 'table-dynamic-fullwidth':
			return <div id={ setting.id }>{ parse( setting.html ) }</div>;

		default:
			return <span>{ setting.type } field not implemented</span>;
	}
}

function TabFields( { tabName } ) {
	const fieldSettings = window.optionsKitSettings.settings[ tabName ];
	const settings = fieldSettings?.map( ( setting ) => {
		if ( setting.type === 'table-dynamic-fullwidth' ) {
			return (
				<Row key={ setting.id } className="optionskit-field-row">
					<Col lg={ 12 } className="optionskit-field">
						<SettingsField setting={ setting } />
					</Col>
				</Row>
			);
		}
		return (
			<Row key={ setting.id } className="optionskit-field-row">
				<Col lg={ 2 } className="optionskit-field-label">
					<label htmlFor={ setting.id }>{ setting.name }</label>
				</Col>
				<Col lg={ 10 } className="optionskit-field">
					<SettingsField setting={ setting } />
				</Col>
			</Row>
		)
	} );

	return (
		<div className="optionskit-form-wrapper">
			<Container fluid className="form-table">
				{ settings }
			</Container>
		</div>
	);
}

export default TabFields;
