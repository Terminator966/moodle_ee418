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
 * A moodleform to allow the editing of a calculated grade item
 *
 * @package   core_grades
 * @copyright 2007 Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once $CFG->libdir.'/formslib.php';

class edit_calculation_form extends moodleform {
    public $available;
    public $noidnumbers;
    public $selection;

    const SUM_SELECTION = 1;
    const SUM_WEIGHTED_SELECTION = 3;
    const AVG_SELECTION = 2;
    const AVG_WEIGHTED_SELECTION = 4;

    function definition() {
        global $COURSE;

        $mform =& $this->_form;

        $itemid = $this->_customdata['itemid'];

        $this->available = grade_item::fetch_all(array('courseid'=>$COURSE->id));
        $grading_choices = array();
//        $this->grading_choices = $grading_choices;
        $this->grading_choice = grade_item::fetch_all(array('selection'=>$grading_choices));
        $this->noidnumbers = array();
        $this->sum = 0;
        $this->weight = 1;

        // All items that have no idnumbers are added to a separate section of the form (hidden by default),
        // enabling the user to assign idnumbers to these grade_items.
        foreach ($this->available as $item) {
            if (empty($item->idnumber)) {
                $this->noidnumbers[$item->id] = $item;
                unset($this->available[$item->id]);
            }
            if ($item->id == $itemid) { // Do not include the current grade_item in the available section
                unset($this->available[$item->id]);
            }
//
//            $sum += $item;
//            $weightedSum += ($weightedSum*$item);
        }

        $this->sum = "sum";
        $this->weightedSum = "avg";
//        $this->avg = $this->sum/count($this->available);
//        $this->weightedAvg = $this->weightedSum/count($this->available); //Does this have to be in string form?

/// visible elements
        $grading_choices = array(
            self::SUM_SELECTION => get_string('sum', 'grades'),
            self::AVG_SELECTION => get_string('avg', 'grades'),
            self::SUM_WEIGHTED_SELECTION => get_string('sum_weighted', 'grades'),
            self::AVG_WEIGHTED_SELECTION => get_string('avg_weighted', 'grades')
        );

        $this->gc = $grading_choices;

        $mform->addElement('select', 'options[mode]', get_string('CGF_preselect', 'grades'), $grading_choices);
//        $mform->addHelpButton('options[mode]', 'mode', 'grades');

        $mform->addElement('header', 'general', get_string('gradeitem', 'grades'));
        $mform->addElement('static', 'itemname', get_string('itemname', 'grades'));
//        $mform->addElement('select', 'itemname', get_string('itemname', 'grades'));
        $mform->addElement('textarea', 'calculation', get_string('calculation', 'grades'), 'cols="60" rows="5"');
        $mform->addHelpButton('calculation', 'calculation', 'grades');
        $mform->setForceLtr('calculation');

/// hidden params
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid', 0);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'section', 0);
        $mform->setType('section', PARAM_ALPHA);
        $mform->setDefault('section', 'calculation');

/// add return tracking info
        $gpr = $this->_customdata['gpr'];

        $gpr->add_mform_elements($mform);

        $this->add_action_buttons();
    }

    function definition_after_data() {
        global $CFG, $COURSE;

        $mform =& $this->_form;
    }

    public function prepare_grading(array $gc){
        $this->gc = $gc;

        if($this->gc['mode'] == self::SUM_SELECTION){
            return $this->sum;
        }
        if($this->gc['mode'] == self::AVG_SELECTION){
            return $this->avg;
        }
        if($this->gc['mode'] == self::SUM_WEIGHTED_SELECTION){
            return $this->weightedSum;
        }
        if($this->gc['mode'] == self::AVG_WEIGHTED_SELECTION){
            return $this->weightedAvg;
        }
        return 0;
    }

/// perform extra validation before submission
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $mform =& $this->_form;

        // check the calculation formula
        if ($data['calculation'] != '') {
            $grade_item = grade_item::fetch(array('id'=>$data['id'], 'courseid'=>$data['courseid']));
            $calculation = calc_formula::unlocalize(stripslashes($data['calculation']));
            $result = $grade_item->validate_formula($calculation);
            if ($result !== true) {
                $errors['calculation'] = $result;
            }
        }

        return $errors;
    }

}

