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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/user/filters/lib.php');

class local_teflacademyreceivecrmcodes_crmcodesreport_user_filtering extends user_filtering {

    /**
     * Constructor
     *
     * @param array $fieldnames array of visible user fields
     * @param string $baseurl base url used for submission/return, null if the same of current page
     * @param array $extraparams extra page parameters
     */
    public function __construct($fieldnames = null, $baseurl = null, $extraparams = null) {
        global $SESSION;

        if (!isset($SESSION->user_filtering)) {
            $SESSION->user_filtering = array();
        }

        if (empty($fieldnames)) {
            $fieldnames = array('firstname' => 0, 'lastname' => 0, 'crmdelegatecode' => 1, 'crmcoursecode' => 1, 'courseid' => 1);
        }

        $this->_fields  = array();

        foreach ($fieldnames as $fieldname => $advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
                $this->_fields[$fieldname] = $field;
            }
        }

        // First the new filter form.
        $this->_addform = new user_add_filter_form($baseurl, array('fields' => $this->_fields, 'extraparams' => $extraparams));
        if ($adddata = $this->_addform->get_data()) {
            foreach ($this->_fields as $fname => $field) {
                $data = $field->check_data($adddata);
                if ($data === false) {
                    continue; // Nothing new.
                }
                if (!array_key_exists($fname, $SESSION->user_filtering)) {
                    $SESSION->user_filtering[$fname] = array();
                }
                $SESSION->user_filtering[$fname][] = $data;
            }
            // Clear the form.
            $_POST = array();
            $this->_addform = new user_add_filter_form($baseurl, array('fields' => $this->_fields, 'extraparams' => $extraparams));
        }

        // Now the active filters.
        $this->_activeform = new user_active_filter_form($baseurl,
                array('fields' => $this->_fields, 'extraparams' => $extraparams));
        if ($adddata = $this->_activeform->get_data()) {
            if (!empty($adddata->removeall)) {
                $SESSION->user_filtering = array();

            } else if (!empty($adddata->removeselected) and !empty($adddata->filter)) {
                foreach ($adddata->filter as $fname => $instances) {
                    foreach ($instances as $i => $val) {
                        if (empty($val)) {
                            continue;
                        }
                        unset($SESSION->user_filtering[$fname][$i]);
                    }
                    if (empty($SESSION->user_filtering[$fname])) {
                        unset($SESSION->user_filtering[$fname]);
                    }
                }
            }
            // Clear+reload the form.
            $_POST = array();
            $this->_activeform = new user_active_filter_form($baseurl,
                    array('fields' => $this->_fields, 'extraparams' => $extraparams));
        }
    }

    /**
     * Creates custom filters for CRM Codes report
     *
     * @param string $fieldname
     * @param boolean $advanced
     * @return object filter
     */
    public function get_field($fieldname, $advanced) {
        global $DB;

        $courses = $DB->get_records_select_menu('course', 'visible = :visible', array('visible' => 1), 'fullname', 'id, fullname');

        switch ($fieldname) {
            case 'firstname':
                return new user_filter_text('firstname',
                        get_string('firstname'), $advanced, 'u.firstname');
            case 'lastname':
                return new user_filter_text('lastname',
                        get_string('lastname'), $advanced, 'u.lastname');
            case 'crmdelegatecode':
                return new user_filter_text('crmdelegatecode',
                        get_string('crmdelegatecode', 'local_teflacademyreceivecrmcodes'), $advanced, 'tacc.crmdelegatecode');
            case 'crmcoursecode':
                return new user_filter_text('crmcoursecode',
                        get_string('crmcoursecode', 'local_teflacademyreceivecrmcodes'), $advanced, 'tacc.crmcoursecode');
            case 'courseid':
                return new user_filter_select('courseid', get_string('coursename', 'local_teflacademyreceivecrmcodes'), $advanced, 'c.id', $courses);
            default:
                return null;
        }
    }
}

/**
 * Return filtered (if provided) list of users who have CRM Codes, except guest and deleted users.
 *
 * @param string $sort An SQL field to sort by
 * @param string $dir The sort direction ASC|DESC
 * @param int $page The page or records to return
 * @param int $recordsperpage The number of records to return per page
 * @param string $extraselect An additional SQL select statement to append to the query
 * @param array $extraparams Additional parameters to use for the above $extraselect
 * @return array Array of {@link $USER} records
 */
function local_teflacademyreceivecrmcodes_get_crm_codes_users_listing($sort='lastaccess', $dir='ASC', $page=0,
                            $recordsperpage=0, $extraselect='', array $extraparams=null, $extracontext = null) {
    global $CFG, $DB;

    $select = "u.deleted <> 1 AND u.id <> :guestid";
    $params = array('guestid' => $CFG->siteguest);

    if ($extraselect) {
        $select .= " AND {$extraselect}";
        $params = $params + (array) $extraparams;
    }

    $extrafields = get_all_user_name_fields(true, 'u');

    $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.username,
                    c.id AS courseid, c.fullname AS coursename,
                    tacc.crmdelegatecode, tacc.crmcoursecode, $extrafields
              FROM {user} u
              JOIN {user_enrolments} ue ON ue.userid = u.id
              JOIN {enrol} e ON e.id = ue.enrolid
              JOIN {course} c ON c.id = e.courseid
              JOIN {local_teflacademycrmcodes} tacc ON tacc.userenrolmentid = ue.id
             WHERE {$select}
          ORDER BY {$sort} {$dir}";

    return $DB->get_records_sql($sql, $params, $page, $recordsperpage);
}

/**
 * Return filtered (if provided) list of users who have CRM Codes, except guest and deleted users.
 *
 * @param string $sort An SQL field to sort by
 * @param string $dir The sort direction ASC|DESC
 * @param int $page The page or records to return
 * @param string $extraselect An additional SQL select statement to append to the query
 * @param array $extraparams Additional parameters to use for the above $extraselect
 * @return array Array of user records and survey information for download
 */
    function local_teflacademyreceivecrmcodes_get_crm_code_users_download($sort='firstname', $dir='ASC', $extraselect='',
                           array $extraparams=null) {
    global $CFG, $DB;

    $columnorder = array(
        'firstname',
        'surname',
        'crmdelegatecode',
        'coursename',
        'crmcoursecode'
    );

    $returndata = array();

    $select = "u.deleted <> 1 AND u.id <> :guestid";
    $params = array('guestid' => $CFG->siteguest);

    if ($extraselect) {
        $select .= " AND {$extraselect}";
        $params = $params + (array) $extraparams;
    }

    $extrafields = get_all_user_name_fields(true, 'u');

    $sql = "SELECT u.id, u.firstname, u.lastname AS surname, tacc.crmdelegatecode,
                    c.fullname AS coursename, tacc.crmcoursecode
              FROM {user} u
              JOIN {user_enrolments} ue ON ue.userid = u.id
              JOIN {enrol} e ON e.id = ue.enrolid
              JOIN {course} c ON c.id = e.courseid
              JOIN {local_teflacademycrmcodes} tacc ON tacc.userenrolmentid = ue.id
             WHERE {$select}
          ORDER BY {$sort} {$dir}";

    $records = $DB->get_records_sql($sql, $params);

    foreach ($records as $recordid => $recorddata) {
        $ordereddata = array();
        foreach ($columnorder as $column) {
            $ordereddata[$column] = $recorddata->$column;
        }
        $returndata[$recordid] = (object) $ordereddata;
    }

    return $returndata;
}

/**
 * Returns a count of CRM Code records
 *
 * @param string $extraselect A SQL snippet for extra select conditions
 * @param array $extraparams An array of parameters to use with $extraselect
 * @return int
 */
function local_teflacademyreceivecrmcodes_count_crm_codes_users($extraselect='', array $extraparams=null) {
    global $DB, $CFG;

    $select = "u.deleted <> 1 AND u.id <> :guestid";
    $params = array('guestid' => $CFG->siteguest);

    if ($extraselect) {
        $select .= " AND {$extraselect}";
        $params = $params + (array) $extraparams;
    }

    $sql = "SELECT COUNT(u.id) AS total
              FROM {user} u
              JOIN {user_enrolments} ue ON ue.userid = u.id
              JOIN {enrol} e ON e.id = ue.enrolid
              JOIN {course} c ON c.id = e.courseid
              JOIN {local_teflacademycrmcodes} tacc ON tacc.userenrolmentid = ue.id
             WHERE {$select}";

    return $DB->count_records_sql($sql, $params);
}