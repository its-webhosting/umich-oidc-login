<?php
/**
 * UMich OIDC settings page - Logs tab
 *
 * This file is required via includes/admin/class-settings.php
 *
 * @package    UMich_OIDC_Login\Admin
 * @copyright  2025 Regents of the University of Michigan
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
 */

namespace UMich_OIDC_Login\Admin;

$settings_tab_logs = array(
	array(
		'id'   => 'logs_table',
		'name' => 'Logs Blurb',
		'type' => 'table-dynamic-fullwidth',
		'html' => "
<div class='optionskit-field-help' id='logs-table' >
The logs table will go here.
</div>
		",
	),
	array(
		'id'   => 'logs_blurb',
		'name' => 'Logs Blurb',
		'type' => 'html',
		'html' => "
<div class='optionskit-field-help' id='logs-blurb' >
This is a placeholder blurb.
</div>
		",
	),
);
