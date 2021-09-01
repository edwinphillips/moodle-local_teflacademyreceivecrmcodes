<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local
 * @subpackage teflacademyreceivecrmcodes
 * @author     Ed Phillips <ed@theteflacademy.com>
 * @copyright  The TEFL Academy 2021 <https://www.theteflacademy.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/import_form.php');

$context = context_system::instance();
require_capability('local/teflacademyreceivecrmcodes:importcrmcodes', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/teflacademyreceivecrmcodes/import.php');

$page_head_title = get_string('importbrightofficereportfile', 'local_teflacademyreceivecrmcodes');
$PAGE->set_title($page_head_title);
$PAGE->set_heading($page_head_title);

$user_context = context_user::instance($USER->id);

echo $OUTPUT->header();

$file_picker_options = array(
    'accepted_types' => array('.csv'),
    'maxbytes'       => 51200
);

$mform = new local_teflacademyreceivecrmcodes_import_form($PAGE->url->out(), array('options' => $file_picker_options));

// Form processing.
if ($mform->is_cancelled()) {
    // @todo - process cancellation request.

} else if ($formdata = $mform->get_data()) {
    // Process validated data.

    // Leave the file in the user's draft area since we will not keep it after processing.
    $area_files = get_file_storage()->get_area_files($user_context->id, 'user', 'draft', $formdata->filepicker, null, false);

    // Import the report file.
    $results = import_brightoffice_report_file(array_shift($area_files));

    // Display the import results.
    echo $results;

    // Clean up the file area.
    get_file_storage()->delete_area_files($user_context->id, 'user', 'draft', $formdata->filepicker);

} else {

    // Display the form.
    $mform->display();
}

echo $OUTPUT->footer();