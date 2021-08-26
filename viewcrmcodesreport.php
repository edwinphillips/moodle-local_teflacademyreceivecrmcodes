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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/dataformatlib.php');
require_once($CFG->dirroot . '/local/teflacademyreceivecrmcodes/lib.php');

$context = context_system::instance();
require_capability('local/teflacademyreceivecrmcodes:viewcrmcodesreport', $context);

$PAGE->set_context($context);
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->set_title(format_string($SITE->fullname) . ': ' . get_string('pluginname', 'local_teflacademyreceivecrmcodes'));
$PAGE->set_url('/local/teflacademyreceivecrmcodes/viewcrmcodesreport.php');

$download   = optional_param('download', 0, PARAM_INT);
$sort       = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir        = optional_param('dir', 'ASC', PARAM_ALPHA);
$page       = optional_param('page', 0, PARAM_INT);
$perpage    = optional_param('perpage', 30, PARAM_INT);
$dataformat = optional_param('dataformat', '', PARAM_ALPHA);

// Validate $sort and $dir params against a specific whitelist of valid values.
if (!in_array($sort, array('name', 'firstname', 'lastname', 'crmdelegatecode', 'coursename', 'crmcoursecode'))) {
    $sort = 'name';
}
if (!in_array($dir, array('ASC', 'DESC'))) {
    $dir = 'ASC';
}

$site = get_site();

// Create the user filter form.
$filtering = new local_teflacademyreceivecrmcodes_crmcodesreport_user_filtering();

// Carry on with the user listing.
$context = context_system::instance();

// Extra columns containing the extra user fields.
$extracolumns = array('crmdelegatecode', 'coursename', 'crmcoursecode');

// Get all user name fields as an array.
$allusernamefields = get_all_user_name_fields(false, null, null, null, true);

$columns = array_merge($allusernamefields, $extracolumns);

foreach ($columns as $column) {
    switch ($column) {
        case 'crmdelegatecode':
            $string['crmdelegatecode'] = get_string('crmdelegatecode', 'local_teflacademyreceivecrmcodes');
            break;
        case 'coursename':
            $string['coursename'] = get_string('coursename', 'local_teflacademyreceivecrmcodes');
            break;
        case 'crmcoursecode':
            $string['crmcoursecode'] = get_string('crmcoursecode', 'local_teflacademyreceivecrmcodes');
            break;
        default:
            $string[$column] = get_user_field_name($column);
    }
    if ($sort != $column) {
        $columnicon = '';
        $columndir = 'ASC';
    } else {
        $columndir = $dir == 'ASC' ? 'DESC' : 'ASC';
        $columnicon = ($dir == 'ASC') ? 'sort_asc' : 'sort_desc';
        $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)),
                'core', array('class' => 'iconsort'));
    }

    $$column = "<a href=\"viewcrmcodesreport.php?sort={$column}&amp;dir={$columndir}\">{$string[$column]}</a>{$columnicon}";
}

// We need to check that alternativefullnameformat is not set to '' or language.
// We don't need to check the fullnamedisplay setting here as the fullname function call further down has
// the override parameter set to true.
$fullnamesetting = $CFG->alternativefullnameformat;

// If we are using language or it is empty, then retrieve the default user names of just 'firstname' and 'lastname'.
if ($fullnamesetting == 'language' || empty($fullnamesetting)) {

    // Set $a variables to return 'firstname' and 'lastname'.
    $a = new stdClass();
    $a->firstname = 'firstname';
    $a->lastname = 'lastname';

    // Getting the fullname display will ensure that the order in the language file is maintained.
    $fullnamesetting = get_string('fullnamedisplay', null, $a);
}

// Order in string will ensure that the name columns are in the correct order.
$usernames = order_in_string($allusernamefields, $fullnamesetting);
$fullnamedisplay = array();
foreach ($usernames as $name) {

    // Use the link from $$column for sorting on the user's name.
    $fullnamedisplay[] = ${$name};
}

// All of the names are in one column. Put them into a string and separate them with a /.
$fullnamedisplay = implode(' / ', $fullnamedisplay);

// If $sort = name then it is the default for the setting and we should use the first name to sort by.
if ($sort == 'name') {
    // Use the first item in the array.
    $sort = reset($usernames);
}

if ($dataformat) {

    $columns = array(
        'Firstname',
        'Surname',
        'CRM Delegate Code',
        'Course Name',
        'CRM Course Code'
    );

    $filename = 'CRM_Codes_' . time();

    list($extrasql, $params) = $filtering->get_sql_filter();

    $sort = $_SESSION['tacrmcodessort'];
    $dir = $_SESSION['tacrmcodesdir'];

    $users = local_teflacademyreceivecrmcodes_get_crm_code_users_download($sort, $dir, $extrasql, $params);

    $downloadusers = new ArrayObject($users);
    $iterator = $downloadusers->getIterator();
    \core\dataformat::download_data($filename, $dataformat, $columns, $iterator);
    exit;
} else {

    list($extrasql, $params) = $filtering->get_sql_filter();

    $_SESSION['tacrmcodessort'] = $sort;
    $_SESSION['tacrmcodesdir'] = $dir;

    $users = local_teflacademyreceivecrmcodes_get_crm_codes_users_listing($sort, $dir,
            $page * $perpage, $perpage, $extrasql, $params, $context);
    $usercount = local_teflacademyreceivecrmcodes_count_crm_codes_users();
    $usersearchcount = local_teflacademyreceivecrmcodes_count_crm_codes_users($extrasql, $params);

    echo $OUTPUT->header();

    if ($extrasql !== '') {
        echo $OUTPUT->heading("{$usersearchcount} / {$usercount} " . get_string('crmcoderecords', 'local_teflacademyreceivecrmcodes'));
        $usercount = $usersearchcount;
    } else {
        echo $OUTPUT->heading("{$usercount} " . get_string('crmcoderecords', 'local_teflacademyreceivecrmcodes'));
    }

    echo $OUTPUT->download_dataformat_selector(get_string('downloaddatatableas', 'local_teflacademyreceivecrmcodes'), 'viewcrmcodesreport.php');

    $baseurl = new moodle_url('/local/teflacademyreceivecrmcodes/viewcrmcodesreport.php',
            array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);

    flush();

    if (!$users) {
        $match = array();
        echo $OUTPUT->heading(get_string('nocrmcoderecordsfound', 'local_teflacademyreceivecrmcodes'));
        $table = null;
    } else {
        $table = new html_table();
        $table->head = array ();
        $table->colclasses = array();
        $table->head[] = $fullnamedisplay;
        $table->attributes['class'] = 'admintable generaltable';
        foreach ($extracolumns as $field) {
            $table->head[] = ${$field};
        }
        $table->colclasses[] = 'centeralign';
        $table->colclasses[] = 'centeralign';
        $table->id = "users";
        foreach ($users as $user) {
            $buttons = array();
            $fullname = fullname($user, true);
            $row = array ();
            $row[] = "<a href=\"../../user/view.php?id={$user->id}&amp;course={$site->id}\">{$fullname}</a>";
            foreach ($extracolumns as $field) {
                $row[] = $user->{$field};
            }
            $table->data[] = $row;
        }
    }

    // Add filters.
    $filtering->display_add();
    $filtering->display_active();

    if (!empty($table)) {
        echo html_writer::start_tag('div', array('class' => 'no-overflow'));
        echo html_writer::table($table);
        echo html_writer::end_tag('div');
        echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
    }

    echo $OUTPUT->footer();
}