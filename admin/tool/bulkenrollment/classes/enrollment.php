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

        // Resolve the category, and fail if not found.
        $errors = array();
        $catid = tool_bulkenrollment_helper::resolve_category($this->rawdata, $errors); //does course exist (check id?)
        if (empty($errors)) {
            $coursedata['category'] = $catid;
        } else {
            foreach ($errors as $key => $message) {
                $this->error($key, $message);
            }
            return false;
        }

        // Ensure we don't overflow the maximum length of the fullname field.
        if (!empty($coursedata['fullname']) && core_text::strlen($coursedata['fullname']) > 254) {
            $this->error('invalidfullnametoolong', new lang_string('invalidfullnametoolong', 'tool_bulkenrollment', 254));
            return false;
        }

        // If the course does not exist, or will be forced created.
        if (!$exists || $mode === tool_bulkenrollment_processor::MODE_CREATE_ALL) {

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
        }

        // Should the course be renamed?
        if (!empty($this->options['rename']) || is_numeric($this->options['rename'])) {
            if (!$this->can_update()) {
                $this->error('canonlyrenameinupdatemode', new lang_string('canonlyrenameinupdatemode', 'tool_bulkenrollment'));
                return false;
            } else if (!$exists) {
                $this->error('cannotrenamecoursenotexist', new lang_string('cannotrenamecoursenotexist', 'tool_bulkenrollment'));
                return false;
            } else if (!$this->can_rename()) {
                $this->error('courserenamingnotallowed', new lang_string('courserenamingnotallowed', 'tool_bulkenrollment'));
                return false;
            } else if ($this->options['rename'] !== clean_param($this->options['rename'], PARAM_TEXT)) {
                $this->error('invalidshortname', new lang_string('invalidshortname', 'tool_bulkenrollment'));
                return false;
            } else if ($this->exists($this->options['rename'])) {
                $this->error('cannotrenameshortnamealreadyinuse',
                    new lang_string('cannotrenameshortnamealreadyinuse', 'tool_bulkenrollment'));
                return false;
            } else if (isset($coursedata['idnumber']) &&
                    $DB->count_records_select('course', 'idnumber = :idn AND shortname != :sn',
                    array('idn' => $coursedata['idnumber'], 'sn' => $this->shortname)) > 0) {
                $this->error('cannotrenameidnumberconflict', new lang_string('cannotrenameidnumberconflict', 'tool_bulkenrollment'));
                return false;
            }
            $coursedata['shortname'] = $this->options['rename'];
            $this->status('courserenamed', new lang_string('courserenamed', 'tool_bulkenrollment',
                array('from' => $this->shortname, 'to' => $coursedata['shortname'])));
        }

        // Should we generate a shortname?
        if (empty($this->shortname) && !is_numeric($this->shortname)) {
            if (empty($this->importoptions['shortnametemplate'])) {
                $this->error('missingshortnamenotemplate', new lang_string('missingshortnamenotemplate', 'tool_bulkenrollment'));
                return false;
            } else if (!$this->can_only_create()) {
                $this->error('cannotgenerateshortnameupdatemode',
                    new lang_string('cannotgenerateshortnameupdatemode', 'tool_bulkenrollment'));
                return false;
            } else {
                $newshortname = tool_bulkenrollment_helper::generate_shortname($coursedata,
                    $this->importoptions['shortnametemplate']);
                if (is_null($newshortname)) {
                    $this->error('generatedshortnameinvalid', new lang_string('generatedshortnameinvalid', 'tool_bulkenrollment'));
                    return false;
                } else if ($this->exists($newshortname)) {
                    if ($mode === tool_bulkenrollment_processor::MODE_CREATE_NEW) {
                        $this->error('generatedshortnamealreadyinuse',
                            new lang_string('generatedshortnamealreadyinuse', 'tool_bulkenrollment'));
                        return false;
                    }
                    $exists = true;
                }
                $this->status('courseshortnamegenerated', new lang_string('courseshortnamegenerated', 'tool_bulkenrollment',
                    $newshortname));
                $this->shortname = $newshortname;
            }
        }

        // If exists, but we only want to create courses, increment the shortname.
        if ($exists && $mode === tool_bulkenrollment_processor::MODE_CREATE_ALL) {
            $original = $this->shortname;
            $this->shortname = tool_bulkenrollment_helper::increment_shortname($this->shortname);
            $exists = false;
            if ($this->shortname != $original) {
                $this->status('courseshortnameincremented', new lang_string('courseshortnameincremented', 'tool_bulkenrollment',
                    array('from' => $original, 'to' => $this->shortname)));
                if (isset($coursedata['idnumber'])) {
                    $originalidn = $coursedata['idnumber'];
                    $coursedata['idnumber'] = tool_bulkenrollment_helper::increment_idnumber($coursedata['idnumber']);
                    if ($originalidn != $coursedata['idnumber']) {
                        $this->status('courseidnumberincremented', new lang_string('courseidnumberincremented', 'tool_bulkenrollment',
                            array('from' => $originalidn, 'to' => $coursedata['idnumber'])));
                    }
                }
            }
        }

        // If the course does not exist, ensure that the ID number is not taken.
        if (!$exists && isset($coursedata['idnumber'])) {
            if ($DB->count_records_select('course', 'idnumber = :idn', array('idn' => $coursedata['idnumber'])) > 0) {
                $this->error('idnumberalreadyinuse', new lang_string('idnumberalreadyinuse', 'tool_bulkenrollment'));
                return false;
            }
        }

        // Course start date.
        if (!empty($coursedata['startdate'])) {
            $coursedata['startdate'] = strtotime($coursedata['startdate']);
        }

        // Course end date.
        if (!empty($coursedata['enddate'])) {
            $coursedata['enddate'] = strtotime($coursedata['enddate']);
        }

        // If lang is specified, check the user is allowed to set that field.
        if (!empty($coursedata['lang'])) {
            if ($exists) {
                $courseid = $DB->get_field('course', 'id', ['shortname' => $this->shortname]);
                if (!has_capability('moodle/course:setforcedlanguage', context_course::instance($courseid))) {
                    $this->error('cannotforcelang', new lang_string('cannotforcelang', 'tool_bulkenrollment'));
                    return false;
                }
            } else {
                $catcontext = context_coursecat::instance($coursedata['category']);
                if (!guess_if_creator_will_have_course_capability('moodle/course:setforcedlanguage', $catcontext)) {
                    $this->error('cannotforcelang', new lang_string('cannotforcelang', 'tool_bulkenrollment'));
                    return false;
                }
            }
        }

        // Ultimate check mode vs. existence.
        switch ($mode) {
            case tool_bulkenrollment_processor::MODE_CREATE_NEW:
            case tool_bulkenrollment_processor::MODE_CREATE_ALL:
                if ($exists) {
                    $this->error('courseexistsanduploadnotallowed',
                        new lang_string('courseexistsanduploadnotallowed', 'tool_bulkenrollment'));
                    return false;
                }
                break;
            case tool_bulkenrollment_processor::MODE_UPDATE_ONLY:
                if (!$exists) {
                    $this->error('coursedoesnotexistandcreatenotallowed',
                        new lang_string('coursedoesnotexistandcreatenotallowed', 'tool_bulkenrollment'));
                    return false;
                }
                // No break!
            case tool_bulkenrollment_processor::MODE_CREATE_OR_UPDATE:
                if ($exists) {
                    if ($updatemode === tool_bulkenrollment_processor::UPDATE_NOTHING) {
                        $this->error('updatemodedoessettonothing',
                            new lang_string('updatemodedoessettonothing', 'tool_bulkenrollment'));
                        return false;
                    }
                }
                break;
            default:
                // O_o Huh?! This should really never happen here!
                $this->error('unknownimportmode', new lang_string('unknownimportmode', 'tool_bulkenrollment'));
                return false;
        }

        // Get final data.
        if ($exists) {
            $missingonly = ($updatemode === tool_bulkenrollment_processor::UPDATE_MISSING_WITH_DATA_OR_DEFAUTLS);
            $coursedata = $this->get_final_update_data($coursedata, $usedefaults, $missingonly);

            // Make sure we are not trying to mess with the front page, though we should never get here!
            if ($coursedata['id'] == $SITE->id) {
                $this->error('cannotupdatefrontpage', new lang_string('cannotupdatefrontpage', 'tool_bulkenrollment'));
                return false;
            }

            $this->do = self::DO_UPDATE;
        } else {
            $coursedata = $this->get_final_create_data($coursedata);
            $this->do = self::DO_CREATE;
        }

        // Validate course start and end dates.
        if ($exists) {
            // We also check existing start and end dates if we are updating an existing course.
            $existingdata = $DB->get_record('course', array('shortname' => $this->shortname));
            if (empty($coursedata['startdate'])) {
                $coursedata['startdate'] = $existingdata->startdate;
            }
            if (empty($coursedata['enddate'])) {
                $coursedata['enddate'] = $existingdata->enddate;
            }
        }
        if ($errorcode = course_validate_dates($coursedata)) {
            $this->error($errorcode, new lang_string($errorcode, 'error'));
            return false;
        }

        // Add role renaming.
        $errors = array();
        $rolenames = tool_bulkenrollment_helper::get_role_names($this->rawdata, $errors);
        if (!empty($errors)) {
            foreach ($errors as $key => $message) {
                $this->error($key, $message);
            }
            return false;
        }
        foreach ($rolenames as $rolekey => $rolename) {
            $coursedata[$rolekey] = $rolename;
        }

        // Custom fields. If the course already exists and mode isn't set to force creation, we can use its context.
        if ($exists && $mode !== tool_bulkenrollment_processor::MODE_CREATE_ALL) {
            $context = context_course::instance($coursedata['id']);
        } else {
            // The category ID is taken from the defaults if it exists, otherwise from course data.
            $context = context_coursecat::instance($this->defaults['category'] ?? $coursedata['category']);
        }
        $customfielddata = tool_bulkenrollment_helper::get_custom_course_field_data($this->rawdata, $this->defaults, $context,
            $errors);
        if (!empty($errors)) {
            foreach ($errors as $key => $message) {
                $this->error($key, $message);
            }

            return false;
        }

        foreach ($customfielddata as $name => $value) {
            $coursedata[$name] = $value;
        }

        // Some validation.
        if (!empty($coursedata['format']) && !in_array($coursedata['format'], tool_bulkenrollment_helper::get_course_formats())) {
            $this->error('invalidcourseformat', new lang_string('invalidcourseformat', 'tool_bulkenrollment'));
            return false;
        }

        // Add data for course format options.
        if (isset($coursedata['format']) || $exists) {
            if (isset($coursedata['format'])) {
                $courseformat = course_get_format((object)['format' => $coursedata['format']]);
            } else {
                $courseformat = course_get_format($existingdata);
            }
            $coursedata += $courseformat->validate_course_format_options($this->rawdata);
        }

        // Special case, 'numsections' is not a course format option any more but still should apply from the template course,
        // if any, and otherwise from defaults.
        if (!$exists || !array_key_exists('numsections', $coursedata)) {
            if (isset($this->rawdata['numsections']) && is_numeric($this->rawdata['numsections'])) {
                $coursedata['numsections'] = (int)$this->rawdata['numsections'];
            } else if (isset($this->options['templatecourse'])) {
                $numsections = tool_bulkenrollment_helper::get_coursesection_count($this->options['templatecourse']);
                if ($numsections != 0) {
                    $coursedata['numsections'] = $numsections;
                } else {
                    $coursedata['numsections'] = get_config('moodlecourse', 'numsections');
                }
            } else {
                $coursedata['numsections'] = get_config('moodlecourse', 'numsections');
            }
        }

        // Visibility can only be 0 or 1.
        if (!empty($coursedata['visible']) AND !($coursedata['visible'] == 0 OR $coursedata['visible'] == 1)) {
            $this->error('invalidvisibilitymode', new lang_string('invalidvisibilitymode', 'tool_bulkenrollment'));
            return false;
        }

        // Ensure that user is allowed to configure course content download and the field contains a valid value.
        if (isset($coursedata['downloadcontent'])) {
            if (!$CFG->downloadcoursecontentallowed ||
                    !has_capability('moodle/course:configuredownloadcontent', $context)) {

                $this->error('downloadcontentnotallowed', new lang_string('downloadcontentnotallowed', 'tool_bulkenrollment'));
                return false;
            }

            $downloadcontentvalues = [
                DOWNLOAD_COURSE_CONTENT_DISABLED,
                DOWNLOAD_COURSE_CONTENT_ENABLED,
                DOWNLOAD_COURSE_CONTENT_SITE_DEFAULT,
            ];
            if (!in_array($coursedata['downloadcontent'], $downloadcontentvalues)) {
                $this->error('invaliddownloadcontent', new lang_string('invaliddownloadcontent', 'tool_bulkenrollment'));
                return false;
            }
        }

        // Saving data.
        $this->data = $coursedata;

        // Get enrolment data. Where the course already exists, we can also perform validation.
        $this->enrolmentdata = tool_bulkenrollment_helper::get_enrolment_data($this->rawdata);
        $courseid = $coursedata['id'] ?? 0;
        $errors = $this->validate_enrolment_data($courseid, $this->enrolmentdata);

        if (!empty($errors)) {
            foreach ($errors as $key => $message) {
                $this->error($key, $message);
            }

            return false;
        }

        if (isset($this->rawdata['tags']) && strval($this->rawdata['tags']) !== '') {
            $this->data['tags'] = preg_split('/\s*,\s*/', trim($this->rawdata['tags']), -1, PREG_SPLIT_NO_EMPTY);
        }

        // Restore data.
        // TODO Speed up things by not really extracting the backup just yet, but checking that
        // the backup file or shortname passed are valid. Extraction should happen in proceed().
        $this->restoredata = $this->get_restore_content_dir();
        if ($this->restoredata === false) {
            return false;
        }

        // We can only reset courses when allowed and we are updating the course.
        if ($this->importoptions['reset'] || $this->options['reset']) {
            if ($this->do !== self::DO_UPDATE) {
                $this->error('canonlyresetcourseinupdatemode',
                    new lang_string('canonlyresetcourseinupdatemode', 'tool_bulkenrollment'));
                return false;
            } else if (!$this->can_reset()) {
                $this->error('courseresetnotallowed', new lang_string('courseresetnotallowed', 'tool_bulkenrollment'));
                return false;
            }
        }

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
