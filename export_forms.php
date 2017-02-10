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
 * Cohort importer from course enrolled users
 *
 * @package    tool_cohortfromcourse
 * @version    moodle 2.x
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class Export_To_Cohort_Form extends moodleform {

    public function definition() {
        global $DB, $COURSE;

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $roles = $DB->get_records_menu('role', array(), 'id, shortname');
        $fixedroles = role_fix_names($roles);
        $select = $mform->addElement('select', 'roles', get_string('rolestoexport', 'tool_cohortfromcourse'), $fixedroles);
        $select->setMultiple(true);

        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_CLEANHTML);

        $mform->addElement('text', 'idnumber', get_string('idnumber'));
        $mform->setType('idnumber', PARAM_TEXT);

        $mform->addElement('textarea', 'description', get_string('description'));
        $mform->setType('description', PARAM_CLEANHTML);

        $context = context_course::instance($COURSE->id);
        $contextoptions = array(context_system::instance()->id => get_string('system', 'tool_cohortfromcourse'), $context->id => get_string('course'));
        $select = $mform->addElement('select', 'contextid', get_string('rolestoexport', 'tool_cohortfromcourse'), $contextoptions);

        $this->add_action_buttons(true);
    }

    public function validation($data, $files = null) {
        global $DB;

        $errors = null;

        if (empty($data['name'])) {
            $errors['name'] = get_string('erroremptyname', 'tool_cohortfromcourse');
        }

        if (!empty($data['idnumber'])) {
            $oldrec = $DB->get_record('cohort', array('name' => $data['name']));
            if ($oldrec) {
                $oldrecwithidnumber = $DB->get_record('cohort', array('idnumber' => $data['idnumber']));
                if ($oldrecwithidnumber) {
                    if ($oldrec->id != $oldrecwithidnumber) {
                        $errors['idnumber'] = get_string('erroridnumbercollision', 'tool_cohortfromcourse');
                    }
                }
            }
        }

        return $errors;
    }
}

class Confirm_Form extends moodleform {

    function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'name');
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('hidden', 'description');
        $mform->setType('description', PARAM_CLEANHTML);
        $mform->addElement('hidden', 'idnumber');
        $mform->setType('idnumber', PARAM_INT);
        $mform->addElement('hidden', 'roles');
        $mform->setType('roles', PARAM_INT);
        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);

        $this->add_action_buttons(true, get_string('confirm'));
    }
}