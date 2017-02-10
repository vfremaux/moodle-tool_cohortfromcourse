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
 * This script handles the report generation in batch task for a single group. 
 * It will produce a group Excel worksheet report that is pushed immediately to output
 * for downloading by a batch agent. No file is stored into the system.
 * groupid must be provided.
 * This script should be sheduled in a CURL call stack or a multi_CURL parallel call.
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/admin/tool/cohortfromcourse/lib.php');
require_once($CFG->dirroot.'/admin/tool/cohortfromcourse/export_forms.php');

// page parameters
$id = required_param('id', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('coursemisconf');
}

$url = new moodle_url('/admin/tool/cohortfromcourse/update.php', array('id' => $id));

// Security.

require_login($course);
$context = context_system::instance();
require_capability('tool/cohortfromcourse:import', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('pluginname', 'tool_cohortfromcourse'));

// Header
$mform = new Confirm_Form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', array('id' => $id)));
}

if ($data = $mform->get_data()) {

    if ($oldrec = $DB->get_record('cohort', array('name' => $data->name))) {
        // We test it again in case cohort has disapeared in the meanwhile.
        tool_cohortfromcourse_save($oldrec, $data);
    } else {
        // And thus we create it back.
        tool_cohortfromcourse_save(null, $data);
    }
    redirect(new moodle_url('/course/view.php', array('id' => $id)));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('exporttocohort', 'tool_cohortfromcourse'));
$mform->display();
echo $OUTPUT->footer();