/**
 * Field components for the WP React OptionsKit frontend app.
 *
 * @copyright  2023 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

import { useFormikContext, useField, ErrorMessage } from 'formik';
import { Container, Row, Col } from 'react-grid-system';
import {
	//__experimentalInputControl as InputControl,
	BaseControl,
	TextControl,
	SelectControl,
	RadioControl,
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

function OptionsKitTextInput( { description, ...props } ) {
	/*
	 * As of 2023-02-19 with @wordpress/components 23.4.0
	 * There are some InputControl problems, perhaps becauase it is experimental
	 *   - a wrapping <div> is required to avoid element overlap problems
	 *   - help (in fact, the whole BaseComponent) does not get rendered
			<div>
			<InputControl
				help={ parse( description ) }
				{ ...field }
				{ ...props }
				onChange={ (value, e) => { formik.handleChange( e.event.nativeEvent ) } }
				onBlur={ (e) => { formik.handleBlur( e.nativeEvent ) } }
			/>
			</div>
	 */
	const [ field ] = useField( props );
	const formik = useFormikContext();
	return (
		<>
			<TextControl
				help={ parse( description ) }
				{ ...field }
				{ ...props }
				onChange={ ( v ) => {
					formik.setFieldValue( props.id, v );
				} }
			/>
			<ErrorMessage name={ props.name }>
				{ ( msg ) => (
					<Notice status="error" isDismissible={ false }>
						{ msg }
					</Notice>
				) }
			</ErrorMessage>
		</>
	);
}

function OptionsKitSelectInput( { description, options, ...props } ) {
	const [ field ] = useField( props );
	const opts = Object.keys( options ).map( ( item ) => ( {
		label: options[ item ],
		value: item,
	} ) );
	const formik = useFormikContext();
	return (
		<>
			<SelectControl
				options={ opts }
				help={ parse( description ) }
				{ ...field }
				{ ...props }
				onChange={ ( value, e ) => {
					formik.handleChange( e.event.nativeEvent );
				} }
				onBlur={ ( e ) => {
					formik.handleBlur( e.nativeEvent );
				} }
			/>
			<ErrorMessage name={ props.name }>
				{ ( msg ) => (
					<Notice status="error" isDismissible={ false }>
						{ msg }
					</Notice>
				) }
			</ErrorMessage>
		</>
	);
}

function OptionsKitMultiSelectInput( {
	description,
	options,
	labels,
	customValidate,
	...props
} ) {
	const [ field ] = useField( props );
	const value = options.filter( ( v ) => {
		return field.value.indexOf( v.value ) >= 0;
	} );
	const formik = useFormikContext();
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

	return (
		<>
			<BaseControl
				id={ props.id }
				help={ parse( description ) }
				className="components-select-control optionskit-multiselect-field"
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
						// setFieldError workaround, see https://github.com/jaredpalmer/formik/issues/1278
						formik.setFieldValue( props.id, result, false );
						formik.setFieldTouched( props.id, true, false );
						formik.setFieldError(
							props.id,
							executeFunctionByName( customValidate, window, v )
						);
					} }
					onBlur={ formik.onBlur }
				/>
			</BaseControl>
			<ErrorMessage name={ props.name } id={ props.id }>
				{ ( msg ) => (
					<Notice status="error" isDismissible={ false }>
						{ msg }
					</Notice>
				) }
			</ErrorMessage>
		</>
	);
}

function OptionsKitRadioInput( { description, options, ...props } ) {
	const opts = Object.keys( options ).map( ( item ) => ( {
		label: options[ item ],
		value: item,
	} ) );
	const formik = useFormikContext();
	// don't pass { ...field } since it contains `value` which will prevent
	// RadioControl from updating.
	return (
		<>
			<RadioControl
				options={ opts }
				help={ parse( description ) }
				{ ...props }
				onChange={ ( v ) => {
					formik.setFieldValue( props.id, v );
				} }
				selected={ formik.values[ props.id ] }
			/>
			<ErrorMessage name={ props.name }>
				{ ( msg ) => (
					<Notice status="error" isDismissible={ false }>
						{ msg }
					</Notice>
				) }
			</ErrorMessage>
		</>
	);
}

function SettingsField( { setting } ) {
	switch ( setting.type ) {
		case 'text':
			return (
				<>
					<OptionsKitTextInput
						id={ setting.id }
						name={ setting.id }
						description={ setting.desc }
					/>
				</>
			);

		case 'select':
			return (
				<>
					<OptionsKitSelectInput
						id={ setting.id }
						name={ setting.id }
						description={ setting.desc }
						options={ setting.options }
					/>
				</>
			);

		case 'multiselect':
			return (
				<>
					<OptionsKitMultiSelectInput
						id={ setting.id }
						name={ setting.id }
						description={ setting.desc }
						options={ setting.options }
						labels={ setting.labels }
						customValidate={ setting.validate }
					/>
				</>
			);

		case 'radio':
			return (
				<>
					<OptionsKitRadioInput
						id={ setting.id }
						name={ setting.id }
						description={ setting.desc }
						options={ setting.options }
					/>
				</>
			);

		case 'html':
			return <div id={ setting.id }>{ parse( setting.html ) }</div>;

		default:
			return <span>{ setting.type } field not implemented</span>;
	}
}

function TabFields( { tabName } ) {
	const fieldSettings = window.optionsKitSettings.settings[ tabName ];
	const settings = fieldSettings?.map( ( setting ) => (
		<Row key={ setting.id } className="optionskit-field-row">
			<Col lg={ 2 } className="optionskit-field-label">
				<label htmlFor={ setting.id }>{ setting.name }</label>
			</Col>
			<Col lg={ 10 } className="optionskit-field">
				<SettingsField setting={ setting } />
			</Col>
		</Row>
	) );

	return (
		<div className="optionskit-form-wrapper">
			<Container fluid className="form-table">
				{ settings }
			</Container>
		</div>
	);
}

export default TabFields;
