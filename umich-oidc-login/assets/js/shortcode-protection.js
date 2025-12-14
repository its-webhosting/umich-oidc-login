/**
 * UMich OIDC Login shortcode protection functions.
 *
 * @copyright  2024 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

const umichOidcShortcodeProtection = {
	/**
	 * Return information about the shortcode preview dialog box.
	 *
	 * @return {Object} - The dialog and shortcode elements, or null if not found.
	 */
	getDialogElements() {
		const dialog = document.getElementById( 'umich-oidc-preview-dialog' );
		if ( ! dialog ) {
			//console.log( 'ERROR: UMich OIDC Login dialog box not found' );
			return null;
		}

		const shortcodeDiv = document.getElementById(
			'umich-oidc-preview-dialog-shortcode'
		);
		if ( ! shortcodeDiv ) {
			//console.log( 'ERROR: unable to get shortcode dialog div' );
			return null;
		}

		return { dialog, shortcodeDiv };
	},

	/**
	 * Return information about a shortcode.  Also validates the shortcode ID
	 *
	 * @param {string} id - The ID of the shortcode to get information about.
	 * @return {Object} - Shortcode data object, or null if not found or not valid.
	 */
	getShortcodeData( id ) {
		if ( ! id ) {
			//console.log( 'ERROR: shortcode ID not present' );
			return null;
		}
		const shortcodeId = parseInt( id );
		if ( isNaN( shortcodeId ) || shortcodeId < 1 ) {
			//console.log( 'ERROR: shortcodeId is not a positive integer:', shortcodeId );
			return null;
		}

		const shortcodeData =
			window.umich_oidc_shortcode_preview[ shortcodeId ];
		if ( ! shortcodeData ) {
			//console.log( 'ERROR: shortcode preview data not found for shortcode number', shortcodeId );
			return null;
		}
		if ( ! shortcodeData.shortcode ) {
			//console.log( 'ERROR: shortcode preview text not found for shortcode number', shortcodeId );
			return null;
		}
		if ( ! shortcodeData.html ) {
			//console.log( 'ERROR: shortcode HTML not found for shortcode number', shortcodeId );
			return null;
		}
		shortcodeData.shortcode_id = shortcodeId;

		return shortcodeData;
	},

	/**
	 * Replace the shortcode preview element with the shortcode HTML.
	 *
	 * @param {Object} target        - The shortcode preview element to replace with the shortcode HTML.
	 * @param {Object} shortcodeData - The shortcode data object.
	 */
	replacePreview( target, shortcodeData ) {
		const htmlString = atob( shortcodeData.html );
		const range = document.createRange();
		range.selectNode( target );
		const fragment = range.createContextualFragment( htmlString );
		target.replaceWith( fragment );
	},

	/**
	 * Ask the user if they want to render the potentially malicious shortcode output.
	 *
	 * @param {Event} event - The click event to open the preview dialog.
	 * @return {boolean} - Whether to propagate the event.
	 */
	shortcodePreviewDialog( event ) {
		const dialogElements = umichOidcShortcodeProtection.getDialogElements();
		if ( ! dialogElements ) {
			return false;
		}

		const cancelButton = document.getElementById( 'umich-oidc-cancel' );
		if ( ! cancelButton ) {
			//console.log( 'ERROR: unable to find shortcode dialog cancel button:' );
			return false;
		}

		const shortcodeData = umichOidcShortcodeProtection.getShortcodeData(
			event.target.getAttribute( 'data-shortcode-id' )
		);
		if ( ! shortcodeData ) {
			return false;
		}

		// set the umich-oidc-preview-dialog-shortcode div to the unprocessed shortcode text
		dialogElements.shortcodeDiv.dataset.shortcodeId =
			shortcodeData.shortcode_id;
		dialogElements.shortcodeDiv.innerHTML = atob( shortcodeData.shortcode );
		dialogElements.dialog.showModal();
		cancelButton.focus();
		event.preventDefault();
		return false;
	},

	/**
	 * Handle a click event on the "Preview this shortcode" button.
	 *
	 *  param {Event} event - The click event on the "Preview this shortcode" button.
	 * @return {boolean} - Whether to propagate the event.
	 */
	shortcodePreviewThis( /* event */ ) {
		const dialogElements = umichOidcShortcodeProtection.getDialogElements();
		if ( ! dialogElements ) {
			return true;
		}

		const shortcodeData = umichOidcShortcodeProtection.getShortcodeData(
			dialogElements.shortcodeDiv.dataset.shortcodeId
		);
		if ( ! shortcodeData ) {
			return true;
		}
		const shortcodeId = shortcodeData.shortcode_id;

		const target = document.querySelector(
			'.umich-oidc-shortcode-preview[data-shortcode-id="' +
				shortcodeId +
				'"]'
		);
		if ( ! target ) {
			//console.log( 'ERROR: shortcode target not found for shortcode number', shortcodeId );
			return true;
		}

		umichOidcShortcodeProtection.replacePreview( target, shortcodeData );

		dialogElements.shortcodeDiv.dataset.shortcodeId = '0';

		dialogElements.dialog.close();
		return false;
	},

	/**
	 * Handle a click event on the "Preview ALL shortcodes" button.
	 *
	 *  param {Object} event - The click event on the "Preview ALL shortcodes" button.
	 * @return {boolean} - Whether to propagate the event.
	 */
	shortcodePreviewAll( /* event */ ) {
		const dialogElements = umichOidcShortcodeProtection.getDialogElements();
		if ( ! dialogElements ) {
			return true;
		}

		const shortcodePreviews = document.querySelectorAll(
			'.umich-oidc-shortcode-preview[data-shortcode-id]'
		);
		shortcodePreviews.forEach( function ( target ) {
			const shortcodeData = umichOidcShortcodeProtection.getShortcodeData(
				target.dataset.shortcodeId
			);
			if ( ! shortcodeData ) {
				return;
			}

			umichOidcShortcodeProtection.replacePreview(
				target,
				shortcodeData
			);
		} );

		dialogElements.shortcodeDiv.dataset.shortcodeId = '0';

		dialogElements.dialog.close();
		return false;
	},
};

document.addEventListener( 'DOMContentLoaded', function () {
	const shortcodePreviewLinks = document.querySelectorAll(
		'a.umich-oidc-shortcode-preview'
	);
	if ( ! shortcodePreviewLinks ) {
		return; // no shortcode previews are present on this page
	}
	shortcodePreviewLinks.forEach( function ( link ) {
		link.addEventListener(
			'click',
			umichOidcShortcodeProtection.shortcodePreviewDialog
		);
	} );

	const dialog = `
		<dialog id="umich-oidc-preview-dialog">
		  <form method="dialog">
		    <h1><span class="umich-oidc-icon">&#x1F50E;</span>&nbsp; CAUTION</h1>
			<p>
			  The UMICH OIDC Login plugin button and link shortcodes, like unflitered HTML in WordPress, can contain
			  malicious code that hijacks your user session or steals/destroys your data.  Unlike unfiltered HTML,
			  <strong>they can be inserted by any user who can edit a page, post, or theme</strong>.
			</p>
			<p>
			   If someone else inserted an OIDC button or link shortcode, verify what it will do before you execute the
			   shortcode or approve/publish the content.
			</p>
			<p>This shortcode is:</p>
			<div id="umich-oidc-preview-dialog-shortcode"class="umich-oidc-shortcode-preview" data-shortcode-id="0">
			</div>
			<div class="umich-oidc-buttons-row">
			  <button type="submit" id="umich-oidc-preview-this" name="preview-this" value="preview-this" class="um-components-button is-tertiary">
			    Execute this shortcode
			  </button>
			  <button type="submit" id="umich-oidc-preview-all" name="preview-all" value="preview-all" class="um-components-button is-destructive">
			    Execute ALL shortcodes on this page
			  </button>
		    </div>
			<button type="submit" id="umich-oidc-cancel" name="cancel" value="cancel" class="um-components-button is-primary">Cancel</button>
		  </form>
		</dialog>
	`;
	document.body.insertAdjacentHTML( 'beforeend', dialog );

	const previewThisButton = document.getElementById(
		'umich-oidc-preview-this'
	);
	const previewAllButton = document.getElementById(
		'umich-oidc-preview-all'
	);
	// The default behavior of any button is to close the dialog, which is what we want the Cancel button to do.

	if ( ! previewThisButton || ! previewAllButton ) {
		//console.log( 'ERROR: unable to set up shortcode preview event listeners:', previewThisButton, previewAllButton );
		return;
	}
	previewThisButton.addEventListener(
		'click',
		umichOidcShortcodeProtection.shortcodePreviewThis
	);
	previewAllButton.addEventListener(
		'click',
		umichOidcShortcodeProtection.shortcodePreviewAll
	);
} );
