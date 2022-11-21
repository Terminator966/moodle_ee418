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
 * File containing the course class.
 *
 * @package    tool_bulkenrollment
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Course class.
 *
 * @package    tool_bulkenrollment
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_bulkenrollment_course {

    /** @var array final import data. */
    protected $data = array();

    /** @var array course import options. */
    protected $options = array();

    /** @var int constant value of self::DO_*, what to do with that course */
    protected $do;

    /** @var bool set to true once we have prepared the course */
    protected $prepared = false;

    /** @var bool set to true once we have started the process of the course */
    protected $processstarted = false;

    /** @var array course import data. */
    protected $rawdata = array();

    /** @var array errors. */
    protected $statuses = array();

    /** @var array fields allowed as enrollment data. */
    static protected $validfields = array('sname', 'id', 'role'); //what must go in the dtb

    /** @var array fields required on course creation. */
    static protected $mandatoryfields = array('sname', 'id', 'role');

    /**
     * Constructor
     *

     * @param array $rawdata raw course data.
     * @param array $options options for the construct
]
     */
    public function __construct($rawdata, $options) { //may not use options but must have added
        $this->rawdata = $rawdata;
        $this->options = $options;
    }

    /**
     * Log an error
     *
     * @param string $code error code.
     * @param lang_string $message error message.
     * @return void
     */
    protected function error($code, lang_string $message) {
        if (array_key_exists($code, $this->errors)) {
            throw new coding_exception('Error code already defined');
        }
        $this->errors[$code] = $message;
    }

    /**
     * Return whether the course exists or not.
     *
     * @param string $shortname the shortname to use to check if the course exists. Falls back on $this->shortname if empty.
     * @return bool
     */
    protected function exists($shortname = null) {
        global $DB;
        if (is_null($shortname)) {
            $shortname = $this->shortname;
        }
        if (!empty($shortname) || is_numeric($shortname)) {
            return $DB->record_exists('course', array('shortname' => $shortname));
        }
        return false;
    }

    /**
     * Return the data that will be used upon saving.
     *
     * @return null|array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Return the errors found during preparation.
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Return array of valid fields for default values
     *
     * @return array
     */
    protected function get_valid_fields() {
        return self::$validfields;
    }


    /**
     * Return the errors found during preparation.
     *
     * @return array
     */
    public function get_statuses() {
        return $this->statuses;
    }

    /**
     * Validates and prepares the data.
     *
     * @return bool false is any error occured.
     */
    public function prepare() { //convert rawdata to enrollment object
        global $DB, $SITE, $CFG;

        $this->prepared = true;


        // Basic data.
        $enrollmentdata = array();
        foreach ($this->rawdata as $field => $value) {
            if (!in_array($field, self::$validfields)) {
                continue;
            }
            $enrollmentdata[$field] = $value;
        }

        // Mandatory fields upon creation.
        $errors = array();
        foreach (self::$mandatoryfields as $field) {
            if ((!isset($coursedata[$field]) || $coursedata[$field] === '') &&
                (!isset($this->defaults[$field]) || $this->defaults[$field] === '')) {
                $errors[] = $field;
            }
        }
        if (!empty($errors)) {
            $this->error('missingmandatoryfields', new lang_string('missingmandatoryfields', 'tool_bulkenrollment',
                implode(', ', $errors)));
            return false;
        }


        // Resolve the category, and fail if not found.
        $errors = array();
        $userid = tool_bulkenrollment_helper::resolve_user($this->rawdata['sname'], $errors); //does course exist (check id?)
        $courseobject = tool_bulkenrollment_helper::resolve_course($this->rawdata['id'], $errors);
        $roleid = tool_bulkenrollment_helper::resolve_role($this->rawdata['role'], $errors);
        if (empty($errors)) {
            $enrollmentdata['userid'] = $userid;
            $enrollmentdata['courseid'] = $courseobject->id;
            $enrollmentdata['roleid'] = $roleid;
        } else {
            foreach ($errors as $key => $message) { //dispay errors (check error strigs)
                $this->error($key, $message);
            }
            return false;
        }

        // Saving data.
        $this->data = $enrollmentdata;
        $instance = tool_bulkenrollment_helper::get_enrolment_instance($courseobject);
        $userenrolment = D
        return true;
    }

    /**
     * Proceed with the import of the course.
     *
     * @return void
     */
    public function proceed() {
        global $CFG, $USER;

        if (!$this->prepared) {
            throw new coding_exception('The course has not been prepared.');
        } else if ($this->has_errors()) {
            throw new moodle_exception('Cannot proceed, errors were detected.');
        } else if ($this->processstarted) {
            throw new coding_exception('The process has already been started.');
        }
        $this->processstarted = true;

        $course = create_course((object) $this->data);
        $this->id = $course->id;
        $this->status('coursecreated', new lang_string('coursecreated', 'tool_bulkenrollment'));

        // Mark context as dirty.
        $context = context_course::instance($course->id);
        $context->mark_dirty();
    }

    /**
     * Log a status
     *
     * @param string $code status code.
     * @param lang_string $message status message.
     * @return void
     */
    protected function status($code, lang_string $message) {
        if (array_key_exists($code, $this->statuses)) {
            throw new coding_exception('Status code already defined');
        }
        $this->statuses[$code] = $message;
    }
}
