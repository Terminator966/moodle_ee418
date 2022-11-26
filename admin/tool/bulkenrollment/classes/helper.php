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
 * File containing the helper class.
 *
 * @package    tool_bulkenrollment
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/cache/lib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Class containing a set of helpers.
 *
 * @package    tool_bulkenrollment
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_bulkenrollment_helper {


    public static function get_enrolment_instance($courseobject) {
        global $DB;

        $instance = $DB->get_record('enrol', array('courseid' => $courseobject, 'enrol' => 'manual'));
        if (!empty($instance)) {
            $plugin = enrol_get_plugin('manual');
            $enrolid = $plugin->add_instance($courseobject);

            $instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'manual'));
        }
        return $instance;
    }

    public static function resolve_user($data, &$errors = array()) {
        $userid = null;
        global $DB;

        $user = $DB->get_record('user', array('username' => $data));

        if (!empty($user) && !empty($user->id)) {
            $userid = $user->id;
        } else {
            $errors['couldnotresolveusernamebyid'] =
                new lang_string('couldnotresolveusernamebyid', 'tool_bulkenrollment');
        }

        return $userid;
    }

    public static function resolve_role($data, &$errors = array()) { //modify to work for other ways (TODO)
        $userrole = null;
        global $DB;

        $role = $DB->get_record('role', array('shortname' => $data));

        if (!empty($role) && !empty($role->id)) {
            $userrole = $role->id;
        } else {
            $errors['couldnotresolverolebyid'] =
                new lang_string('couldnotresolverolebyid', 'tool_bulkenrollment');
        }

        return $userrole; //id of role
    }

    public static function resolve_course($data, &$errors = array()) {
        $usercourseid = null;
        global $DB;

        $courseid = $DB->get_record('course', array('idnumber' => $data));

        if (!empty($courseid) && !empty($courseid->id)) {
            $usercourseid = $courseid;
        } else {
            $errors['couldnotresolvecoursebyid'] =
                new lang_string('couldnotresolvecoursebyid', 'tool_bulkenrollment');
        }

        return $usercourseid; //id of course
    }
}