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

require_once($CFG->libdir.'/formslib.php');

/**
 * Form definition for the plugin
 *
 */
class local_teflacademyreceivecrmcodes_import_form extends moodleform {

    //Add elements to form
    public function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore!

        // File picker
        $this->_form->addElement('header', 'identity', get_string('importfile', 'local_teflacademyreceivecrmcodes'));

        $this->_form->addElement('filepicker', 'filepicker', null, null, $this->_customdata['options']);
        $this->_form->addHelpButton('filepicker', 'importfile', 'local_teflacademyreceivecrmcodes');
        $this->_form->addRule('filepicker', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('import', 'local_teflacademyreceivecrmcodes'));

    }

    //Custom validation should be added here
    function validation($data, $files) {

        return array();
    }
}