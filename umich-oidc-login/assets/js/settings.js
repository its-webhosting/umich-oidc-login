/**
 * UMich OIDC Login settings page helper functions.
 *
 * @copyright  2023 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

var umichOidcSettings =  {

	/** Check to see if the contents of the restrict_site form field are valid.
	 *
	 * @param array list values the user selected:
	 *     [ '_everyone_', 'Example Group' ]
	 * @return undefined|string undefined if values are OK, or a string containing an error message if they are invalid.
	 */
	validateRestrictSite: function (values) {
		if ( values.length === 0 )  {
			return 'Must have at least one group';
		}
		if ( values.length > 1 ) {
			if ( values.includes( '_everyone_' ) ) {
				return '"( Everyone )" cannot be used together with other groups.';
			}
			if ( values.includes( '_logged_in_' ) ) {
				return '"( Logged-in Users )" cannot be used together with other groups.';
			}
		}
		return undefined;
	}

};

