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
    //inserted this part to deal with resolve options

    //resolve settings for USER column
    const RESOLVE_USER_USERNAME = 1;
    const RESOLVE_USER_EMAIL = 2;

    //resolve settings for COURSE column
    const RESOLVE_COURSE_ID = 1;
    const RESOLVE_COURSE_SHORTNAME = 2;

    //resolve settings for ROLE column
    const RESOLVE_ROLE_SHORTNAME = 1;
    const RESOLVE_ROLE_ID = 2;


    public static function get_enrolment_instance($courseobject) {
        global $DB;

        $instance = $DB->get_record('enrol', array('courseid' => $courseobject->id, 'enrol' => 'manual'));
        if (empty($instance)) {
            $plugin = enrol_get_plugin('manual');
            $enrolid = $plugin->add_instance($courseobject);

            $instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'manual'));
        }
        return $instance;
    }

    public static function resolve_user($data, &$errors = array(), array $options) {
        $userid = null;
        global $DB;
        if((int) $options['resolveuserby'] == self::RESOLVE_USER_USERNAME) {
            $user = $DB->get_record('user', array('username' => $data));
        }
        else if((int) $options['resolveuserby'] == self::RESOLVE_USER_EMAIL){
            $user = $DB->get_record('user', array('email' => $data));
        }
        if (!empty($user) && !empty($user->id)) {
            $userid = $user->id;
        } else {
            $errors['couldnotresolveusernamebyid'] =
                new lang_string('couldnotresolveusernamebyid', 'tool_bulkenrollment');
        }

        return $userid;
    }

    public static function resolve_role($data, &$errors = array(), array $options) { //modify to work for other ways (TODO)
        $userrole = null;
        global $DB;
        if((int) $options['resolveroleby'] == self::RESOLVE_ROLE_SHORTNAME) {
            $role = $DB->get_record('role', array('shortname' => $data));
        }
        else if((int) $options['resolveroleby'] == self::RESOLVE_ROLE_ID){
            $role = $DB->get_record('role', array('id' => $data));
        }
        if (!empty($role) && !empty($role->id)) {
            $userrole = $role->id;
        } else {
            $errors['couldnotresolverolebyid'] =
                new lang_string('couldnotresolverolebyid', 'tool_bulkenrollment');
        }

        return $userrole; //id of role
    }

    public static function resolve_course($data, &$errors = array(), array $options) {
        global $DB;
        if((int) $options['resolveclassby'] == self::RESOLVE_COURSE_ID) {
            $course = $DB->get_record('course', array('id' => $data));
        }
        else if((int) $options['resolveclassby'] == self::RESOLVE_COURSE_SHORTNAME){
            $course = $DB->get_record('course', array('shortname' => $data));
        }
        if (empty($course)) {
            $errors['couldnotresolvecoursebyid'] =
                new lang_string('couldnotresolvecoursebyid', 'tool_bulkenrollment');
        }

        return $course; //course object
    }
}