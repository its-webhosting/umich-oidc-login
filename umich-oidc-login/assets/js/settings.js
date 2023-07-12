/**
 * UMich OIDC Login settings page helper functions.
 *
 * @copyright  2023 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

var umichOidcSettings =  {

	/** Check to see if the contents of the restrict_site form field are valid.
	 *
	 * @param array list value/label pairs the user selected:
	 *     [ { value: 123, label: 'option 1' }, { value: 456, label: 'option 2' } ]
	 * @return string empty string if values are OK, or an error message if they are invalid.
	 */
	validateRestrictSite: function (values) {
		if ( values.length === 0)  {
			return 'Must have at least one group';
		}
		if ( values.length > 1 ) {
			if ( values.find(({ value }) => value === '_everyone_') ) {
				return '"( Everyone )" cannot be used together with other groups.';
			}
			if ( values.find(({ value }) => value === '_logged_in_') ) {
				return '"( Logged-in Users )" cannot be used together with other groups.';
			}
		}
		return '';
	}

};

