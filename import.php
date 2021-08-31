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

$page_head_title = get_string('importbrightofficereportexportfile', 'local_teflacademyreceivecrmcodes');
$PAGE->set_title($page_head_title);
$PAGE->set_heading($page_head_title);

$file_picker_options = array(
    'accepted_types' => array('.csv'),
    'maxbytes'       => local_teflacademyreceivecrmcodes_plugin::MAXFILESIZE
);

$mform = new local_teflacademyreceivecrmcodes_import_form($PAGE->url->out(), array('options' => $file_picker_options));

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();