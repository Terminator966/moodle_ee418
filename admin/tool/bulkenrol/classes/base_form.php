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
 * File containing the base import form.
 *
 * @package    tool_bulkenrol
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');

/**
 * Base import form.
 *
 * @package    tool_bulkenrol
 */
class tool_bulkenrol_base_form extends moodleform {

    /**
     * Empty definition.
     *
     * @return void
     */
    public function definition() {
    }

    /**
     * Adds the import settings part.
     *
     * @return void
     */
    public function add_import_options() {
        $mform = $this->_form;

        // Upload settings and file.
        $mform->addElement('header', 'importoptionshdr', get_string('importoptions', 'tool_bulkenrol'));
        $mform->setExpanded('importoptionshdr', true);

        $choices = array(
            tool_bulkenrol_processor::MODE_CREATE_NEW => get_string('createnew', 'tool_bulkenrol'),
            tool_bulkenrol_processor::MODE_CREATE_ALL => get_string('createall', 'tool_bulkenrol'),
            tool_bulkenrol_processor::MODE_CREATE_OR_UPDATE => get_string('createorupdate', 'tool_bulkenrol'),
            tool_bulkenrol_processor::MODE_UPDATE_ONLY => get_string('updateonly', 'tool_bulkenrol')
        );
        $mform->addElement('select', 'options[mode]', get_string('mode', 'tool_bulkenrol'), $choices);
        $mform->addHelpButton('options[mode]', 'mode', 'tool_bulkenrol');

        $choices = array(
            tool_bulkenrol_processor::UPDATE_NOTHING => get_string('nochanges', 'tool_bulkenrol'),
            tool_bulkenrol_processor::UPDATE_ALL_WITH_DATA_ONLY => get_string('updatewithdataonly', 'tool_bulkenrol'),
            tool_bulkenrol_processor::UPDATE_ALL_WITH_DATA_OR_DEFAUTLS =>
                get_string('updatewithdataordefaults', 'tool_bulkenrol'),
            tool_bulkenrol_processor::UPDATE_MISSING_WITH_DATA_OR_DEFAUTLS => get_string('updatemissing', 'tool_bulkenrol')
        );
        $mform->addElement('select', 'options[updatemode]', get_string('updatemode', 'tool_bulkenrol'), $choices);
        $mform->setDefault('options[updatemode]', tool_bulkenrol_processor::UPDATE_NOTHING);
        $mform->hideIf('options[updatemode]', 'options[mode]', 'eq', tool_bulkenrol_processor::MODE_CREATE_NEW);
        $mform->hideIf('options[updatemode]', 'options[mode]', 'eq', tool_bulkenrol_processor::MODE_CREATE_ALL);
        $mform->addHelpButton('options[updatemode]', 'updatemode', 'tool_bulkenrol');

        $mform->addElement('selectyesno', 'options[allowdeletes]', get_string('allowdeletes', 'tool_bulkenrol'));
        $mform->setDefault('options[allowdeletes]', 0);
        $mform->hideIf('options[allowdeletes]', 'options[mode]', 'eq', tool_bulkenrol_processor::MODE_CREATE_NEW);
        $mform->hideIf('options[allowdeletes]', 'options[mode]', 'eq', tool_bulkenrol_processor::MODE_CREATE_ALL);
        $mform->addHelpButton('options[allowdeletes]', 'allowdeletes', 'tool_bulkenrol');

        $mform->addElement('selectyesno', 'options[allowrenames]', get_string('allowrenames', 'tool_bulkenrol'));
        $mform->setDefault('options[allowrenames]', 0);
        $mform->hideIf('options[allowrenames]', 'options[mode]', 'eq', tool_bulkenrol_processor::MODE_CREATE_NEW);
        $mform->hideIf('options[allowrenames]', 'options[mode]', 'eq', tool_bulkenrol_processor::MODE_CREATE_ALL);
        $mform->addHelpButton('options[allowrenames]', 'allowrenames', 'tool_bulkenrol');

        $mform->addElement('selectyesno', 'options[allowresets]', get_string('allowresets', 'tool_bulkenrol'));
        $mform->setDefault('options[allowresets]', 0);
        $mform->hideIf('options[allowresets]', 'options[mode]', 'eq', tool_bulkenrol_processor::MODE_CREATE_NEW);
        $mform->hideIf('options[allowresets]', 'options[mode]', 'eq', tool_bulkenrol_processor::MODE_CREATE_ALL);
        $mform->addHelpButton('options[allowresets]', 'allowresets', 'tool_bulkenrol');
    }

}
