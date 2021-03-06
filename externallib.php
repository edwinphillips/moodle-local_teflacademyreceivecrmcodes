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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

define('LOCAL_TEFLACADEMYRECEIVECRMCODES_STUDENT_SHORTNAME', 'student');

class local_teflacademyreceivecrmcodes_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function process_teflacademy_request_parameters() {

        return new external_function_parameters(
            array(
                'email'           => new external_value(PARAM_TEXT),
                'courseidnumber'  => new external_value(PARAM_TEXT),
                'crmdelegatecode' => new external_value(PARAM_TEXT),
                'crmcoursecode'   => new external_value(PARAM_TEXT),
            )
        );
    }

    /**
     * Processes website receive CRM codes request.
     *
     * @param text $email
     * @param text $courseidnumber
     * @param text $crmdelegatecode
     * @param text $crmcoursecode
     *
     * @return bool success or failure
     */
    public static function process_teflacademy_request($email, $courseidnumber, $crmdelegatecode, $crmcoursecode) {
        global $DB;

        $params = self::validate_parameters(
            self::process_teflacademy_request_parameters(),
            array(
                'email'           => $email,
                'courseidnumber'  => $courseidnumber,
                'crmdelegatecode' => $crmdelegatecode,
                'crmcoursecode'   => $crmcoursecode,
            )
        );

        if (!has_capability('local/teflacademyreceivecrmcodes:processrequest', context_system::instance())) {
            return false;
        }

        // Identify the user enrolment record.
        $sql = "SELECT ue.id, c.id AS courseid, u.id AS userid
                  FROM {user_enrolments} ue
                  JOIN {user} u ON u.id = ue.userid
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                  JOIN {role} r ON r.id = e.roleid
                 WHERE u.email = ?
                   AND c.idnumber = ?
                   AND r.shortname = ?";
        $params = array($email, $courseidnumber, LOCAL_TEFLACADEMYRECEIVECRMCODES_STUDENT_SHORTNAME);

        if ($userenrolment = $DB->get_record_sql($sql, $params)) {

            // Record CRM codes.
            $record = new stdClass();
            $record->userenrolmentid = $userenrolment->id;
            $record->crmdelegatecode = $crmdelegatecode;
            $record->crmcoursecode   = $crmcoursecode;

            if ($DB->insert_record('local_teflacademycrmcodes', $record)) {
                // Send the enrolment updated event so that listeners in local_teflacademywebservices plugin
                // will send extra data about the enrolment.
                $event = \core\event\user_enrolment_updated::create(
                    array(
                        'objectid' => $userenrolment->id,
                        'courseid' => $userenrolment->courseid,
                        'context' => context_course::instance($userenrolment->courseid),
                        'relateduserid' => $userenrolment->userid,
                        'other' => array('enrol' => 'manual')
                    )
                );
                $event->trigger();

                return true;
            } else {
                return false;
            }
        } else {

            return false;
        }
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function process_teflacademy_request_returns() {

        return new external_value(PARAM_BOOL);
    }
}