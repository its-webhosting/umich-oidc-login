/**
 * Panel component for the WP React OptionsKit frontend app.
 *
 * @copyright  2023 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

import React from 'react'; // eslint-disable-line import/no-extraneous-dependencies
import ReactDOM from 'react-dom'; // eslint-disable-line import/no-extraneous-dependencies
import { Formik, Form, useFormikContext } from 'formik';
import debounce from 'lodash.debounce';
import isEqual from 'react-fast-compare';
import apiFetch from '@wordpress/api-fetch';
import { Button, Notice, TabPanel, Icon, Spinner } from '@wordpress/components';
import * as icons from '@wordpress/icons';
import TabFields from './TabFields';
import './Panel.scss';

const noop = () => {};

function parseLocationHash( hash ) {
	const settings = window.optionsKitSettings;
	let tabName = '';
	let fieldName = '';
	const re = /^#([^/]+)(\/([^/]+))?$/;
	const matches = re.exec( hash );
	if ( null !== matches ) {
		if ( matches[ 1 ] && matches[ 1 ] in settings.tabs ) {
			tabName = matches[ 1 ];
			// The fragment can be for something other than a field id.
			if ( undefined !== matches[ 3 ] ) {
				fieldName = matches[ 3 ];
			}
		}
	}
	return [ tabName, fieldName ];
}

function OptionsKitTabPanel() {
	const settings = window.optionsKitSettings;
	const tabs = settings.tabs;

	const [ initialTabName, initialFieldName ] = parseLocationHash(
		window.location.hash
	);

	// Track and change the active tab.
	// See https://wordpress.stackexchange.com/a/411468
	const [ tabState, setTabState ] = React.useState( {
		activeTabName: initialTabName,
		activeFieldName: initialFieldName,
		serialNumber: 0,
	} );

	function onPopstateEvent( e ) {
		let [ newTabName, newFieldName ] = parseLocationHash(
			window.location.hash
		);
		if ( e.state && 'tabName' in e.state ) {
			newTabName = e.state.tabName;
			newFieldName = '';
		}

		if ( '' !== newTabName && ! ( newTabName in settings.tabs ) ) {
			return;
		}
		ReactDOM.flushSync( () => {
			const element = document.getElementById( newFieldName );
			if ( element ) {
				element.scrollIntoView( { behavior: 'smooth' } );
			}
			setTabState( {
				...tabState,
				activeTabName: newTabName,
				activeFieldName: newFieldName,
				serialNumber: tabState.serialNumber + 1,
			} );
		} );
	}

	React.useEffect( () => {
		window.addEventListener( 'popstate', onPopstateEvent );
		return () => {
			window.removeEventListener( 'popstate', onPopstateEvent );
		};
	}, [ tabState ] );

	function onTabSelect( tabName ) {
		window.history.pushState( { tabName }, '', '#' + tabName );
	}

	const tabProps = Object.keys( tabs ).map( ( tab ) => ( {
		name: tab,
		title: tabs[ tab ],
	} ) );

	return (
		<TabPanel
			tabs={ tabProps }
			onSelect={ onTabSelect }
			initialTabName={ tabState.activeTabName }
			key={ tabState.serialNumber }
		>
			{ ( tab ) => <TabFields tabName={ tab.name } /> }
		</TabPanel>
	);
}

function submitOptionsData( values, actions ) {
	const settings = window.optionsKitSettings;
	const data = { ...values, verifynonce: settings.verifynonce };
	apiFetch( {
		url: settings.rest_url + 'records/',
		method: 'POST',
		data,
	} )
		.then( ( res ) => {
			// Update values (what was returned may be different than submitted)
			const newValues = { ...values, ...res.options };
			window.optionsKitSettings.options = newValues;
			window.optionsKitSettings.notices = res.notices;
			window.optionsKitSettings.settings = res.settings;
			actions.setValues( newValues );
			const formErrorMessage = settings.labels.success;
			actions.setFormikState( ( prevState ) => {
				return {
					...prevState,
					status: {
						...prevState.status,
						notices: res.notices,
						formErrorMessage,
						formErrorType: 'success',
						showFormError: ! settings.autosave,
					},
				};
			} );
			actions.setSubmitting( false );
			if ( newValues.hasOwnProperty( 'autosave' ) && Boolean( newValues.autosave ) !== settings.autosave ) {
				window.location.reload();
			}
		} )
		.catch( ( err ) => {
			if ( err.status === 400 || err.status === 401 || err.status === 403) {
				// Session timed out or nonce expired. Force reauthentication.
				window.location.reload();
				return;
			}
			let formErrorMessage = settings.labels.error;
			const settingInfo = {};
			for ( const tab in settings.settings ) {
				const tabSettings = settings.settings[ tab ];
				for ( const setting of tabSettings ) {
					const { id, name } = setting;
					settingInfo[ id ] = { name, tab };
				}
			}
			const errors = {};
			let separator = ' ';
			Object.keys( err.errors ).forEach( ( e ) => {
				errors[ e ] = err.errors[ e ].join( ' / ' );
				const { name, tab } = settingInfo[ e ];
				formErrorMessage += `${ separator }<a href="#${ tab }/${ e }">${ name }</a>`;
				separator = ', ';
			} );
			actions.setErrors( errors );
			actions.setFormikState( ( prevState ) => {
				return {
					...prevState,
					status: {
						...prevState.status,
						formErrorMessage,
						formErrorType: 'error',
						showFormError: true,
					},
				};
			} );
			actions.setSubmitting( false );
		} );
}

function FormSubmitButton( { label } ) {
	const formik = useFormikContext();
	function doSubmit() {
		if ( ! formik.isSubmitting ) {
			formik.handleSubmit();
		}
	}
	return (
		<Button
			variant="primary"
			onClick={ doSubmit }
			isBusy={ formik.isSubmitting }
		>
			{ label }
		</Button>
	);
}

/*
 * Adapted from https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/notice/list.tsx
 *
 * Adds:
 * - each notice can specify its own status
 * - notice content is __unstableHTML
 * - don't reverse the order of notices in the array
 */
function OptionsKitNoticeList( {
	notices,
	onRemove = noop,
	className,
	children,
} ) {
	const removeNotice = ( id ) => () => onRemove( id );

	if ( className ) {
		className += ' components-notice-list';
	} else {
		className = 'components-notice-list';
	}

	return (
		<div className={ className }>
			{ children }
			{ [ ...notices ].map( ( notice ) => {
				const { content, ...restNotice } = notice;
				return (
					<Notice
						{ ...restNotice }
						key={ notice.id }
						status={ notice.status }
						onRemove={ removeNotice( notice.id ) }
						__unstableHTML
					>
						{ notice.content }
					</Notice>
				);
			} ) }
		</div>
	);
}

function AutoSaveFields() {

	const formik = useFormikContext();
	const [ lastValues, setLastValues ] = React.useState( formik.values );

	// From https://www.developerway.com/posts/debouncing-in-react
	const useDebounce = (callback) => {
		const ref = React.useRef();

		React.useEffect( () => {
			ref.current = callback;
		}, [ callback ]);

		const debouncedCallback = React.useMemo( () => {
			const func = () => {
				ref.current?.();
			};

			return debounce( func, 1500 );
		}, []);

		return debouncedCallback;
	};

	const debouncedSubmit = useDebounce(async () => {
		if ( formik.isValid && ! formik.isValidating && ! formik.isSubmitting
			&& ! isEqual( formik.values, lastValues ) ) {
			await formik.submitForm();
			setLastValues( formik.values );
		}
	} );

	React.useEffect( () => {
			if ( formik.isValid && ! formik.isValidating && ! formik.isSubmitting
				&& ! isEqual( formik.values, lastValues ) ) {
				debouncedSubmit();
			}
		},
		[ debouncedSubmit, formik.values, formik.isValidating, formik.isValid, formik.isSubmitting ]
	);

	let autosaveStatus = 'Settings status unknown';
	let autosaveIcon = <Icon icon={ icons.help } />;
	if ( ! formik.dirty && formik.submitCount === 0 ) {
		autosaveStatus = 'No changes made.';
		autosaveIcon = <Icon icon={ icons.border } className='optionskit-autosave-status-icon' />;
	} else if ( formik.isSubmitting || formik.isValidating) {
		autosaveStatus = 'Saving changes...';
		autosaveIcon = <Spinner className='optionskit-autosave-status-icon' />;
	} else if ( formik.errors && Object.keys( formik.errors ).length > 0 ) {
		autosaveStatus = 'Changes not saved due to errors.';
		autosaveIcon = <Icon icon={ icons.info } className='optionskit-autosave-status-icon' />; // icons.error requires a later version of @wordpress/icons than 10.8.2.
	} else if ( isEqual( formik.values, lastValues ) ) {
		autosaveStatus = 'Changes saved.';
		autosaveIcon = <Icon icon={ icons.published } className='optionskit-autosave-status-icon' />;
	} else {
		autosaveStatus = 'Unsaved changes.';
		autosaveIcon = <Icon icon={ icons.plusCircle } className='optionskit-autosave-status-icon' />;
	}

	return (
		<>
			<div className='optionskit-autosave-status'>
				{ autosaveIcon } { autosaveStatus }
			</div>
		</>
	);
}

function OptionsKitNotices() {
	const settings = window.optionsKitSettings;
	const formik = useFormikContext();
	const notices = formik.status.notices;

	const removeNotice = ( id ) => {
		const newNotices = notices.filter( ( notice ) => notice.id !== id );
		formik.setStatus( { ...formik.status, notices: newNotices } );
	};

	let debugBar = false ? (
		<div>
			{ formik.isValid ? 'valid' : 'INVALID' } &nbsp;|&nbsp;
			{ formik.isValidating ? 'VALIDATING' : 'not validating' } &nbsp;|&nbsp;
			{ formik.isSubmitting ? 'SUBMITTING' : 'not submitting' } &nbsp;|&nbsp;
			{ formik.dirty ? 'DIRTY' : 'clean' } &nbsp;|&nbsp;
			{ formik.touched ? 'TOUCHED' : 'untouched' }
		</div>
	) : '';

	return (
		<>
			<OptionsKitNoticeList notices={ notices } onRemove={ removeNotice } />
			{ debugBar }
		</>

	);
}

function FormErrorNotice() {
	const formik = useFormikContext();

	const handleDismissErrorNotice = () => {
		formik.setStatus( { ...formik.status, showFormError: false } );
	};

	return (
		<>
			{ formik.status.showFormError && (
				<Notice
					id="optionskit-page-notice"
					status={ formik.status.formErrorType }
					onRemove={ handleDismissErrorNotice }
					__unstableHTML
				>
					{ formik.status.formErrorMessage }
				</Notice>
			) }
		</>
	);
}

function Panel() {
	const settings = window.optionsKitSettings;
	const logo = settings.logo ? <img src={ settings.logo } alt="" /> : '';

	// User nonce.  See https://wordpress.stackexchange.com/a/323638
	apiFetch.use( apiFetch.createNonceMiddleware( settings.nonce ) );

	let actionButtons = '';
	if ( settings.buttons.length > 0 ) {
		const buttons = settings.buttons.map( ( button ) => (
			<li key={ button.url }>
				<a href={ button.url } className="page-title-action">
					{ button.title }
				</a>
			</li>
		) );
		actionButtons = <ul className="optionskit-title-links">{ buttons }</ul>;
	}

	let saveArea;
	if ( settings.autosave ) {
		saveArea = (
			<div className="optionskit-save-area">
				<AutoSaveFields />
			</div>
		);
	} else {
		saveArea = (
			<div className="optionskit-save-area">
				<FormSubmitButton label={settings.labels.save }/>
			</div>
		);
	}

	return (
		<section id="optionskit-panel" className="optionskit-panel wrap">
			<Formik
				initialValues={ settings.options }
				initialStatus={ {
					notices: settings.notices,
					formErrorMessage: '',
					formErrorType: 'success',
					showFormError: false,
				} }
				onSubmit={ submitOptionsData }
			>
				<Form className="optionskit-form">
					<section id="optionskit-topbar">
						<h1>
							{ logo }
							{ settings.page_title }
						</h1>
						{ actionButtons }
						{ saveArea }
						<div className="optionskit-page-notice">
							<FormErrorNotice />
							<OptionsKitNotices />
						</div>
					</section>

					<div className="optionskit-navigation-wrapper">
						<div id="optionskit-navigation">
							<OptionsKitTabPanel />
						</div>
					</div>
				</Form>
			</Formik>
		</section>
	);
}

export default Panel;
