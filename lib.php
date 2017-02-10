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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * Cohort importer from course enrolled users
 *
 * @package    tool_cohortfromcourse
 * @version    moodle 2.x
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function tool_cohortfromcourse_extend_course_navigation_hook() {
    global $COURSE, $PAGE;

    $context = context_course::instance($COURSE->id);
    if (has_capability('tool/cohortfromcourse:import', $context) && $COURSE->id > SITEID) {
        $url = new moodle_url('/admin/tool/cohortfromcourse/index.php', array('id' => $COURSE->id));
        $PAGE->settingsnav->find('users', navigation_node::TYPE_CONTAINER)->add(get_string('exporttocohort', 'tool_cohortfromcourse'), $url, navigation_node::TYPE_SETTING, null, 'exporttocohort', new pix_icon('i/enrolusers', ''));
    }
}

function tool_cohortfromcourse_save($oldrec, $data) {
    global $DB;

    $roles = $data->roles;

    if (is_null($oldrec)) {
        unset($data->id);
        unset($data->roles);
        $cohortid = cohort_add_cohort($data);
    } else {
        $oldrec->timemodified = time();
        $oldrec->description = $data->description;
        $oldrec->idnumber = $data->idnumber;
        cohort_update_cohort($data);
        $cohortid = $oldrec->id;
    }

    // Now update members
    list($insql, $inparams) = $DB->get_in_or_equal($roles);
    $sql = '
        SELECT DISTINCT
            ue.userid
        FROM
            {user_enrolments} ue,
            {enrol} e,
            {context} ctx,
            {role_assignments} ra
        WHERE
            ue.userid = ra.userid AND
            ue.enrolid = e.id AND
            ctx.instanceid = e.courseid AND
            ctx.contextlevel = 50 AND
            ra.contextid = ctx.id AND
            e.status = 0 AND
            ue.status = 0 AND
            ra.roleid '.$insql;
    $members = $DB->get_records_sql($sql, $inparams);

    // Scan and register old members.
    $oldmemberkeys = array();
    $oldmemberreg = array();
    if ($oldmembers = $DB->get_records('cohort_members', array('cohortid' => $cohortid))) {
        foreach($oldmembers as $om) {
            $oldmemberkeys[] = $om->userid;
            $oldmemberreg[$om->userid] = true;
        }
    }

    if ($members) {
        // Add missing members and unregister them from old members.
        foreach ($members as $mid) {
            if (!in_array($mid, $oldmemberkeys)) {
                cohort_add_member($cohortid, $mid);
                unset($oldmemberreg[$mid]);
            }
        }
    }

    // Remove outgoing users.
    if (!empty($oldmemberreg)) {
        foreach (array_keys($oldmemberreg) as $omid) {
            cohort_remove_member($cohortid, $omid);
        }
    }
}