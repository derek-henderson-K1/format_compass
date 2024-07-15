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

namespace format_compass\form;

use context;
use moodle_exception;
use moodle_url;
use context_course;
use stdClass;
use core_form\dynamic_form;

/**
 * Create new snippet category form.
 *
 * @package     format_compass
 * @copyright   2023 Knowledgeone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renamesection_form extends dynamic_form {
    /**
     * Process the form submission
     *
     * @return array
     * @throws moodle_exception
     */
    public function process_dynamic_submission(): array {
        global $DB, $USER, $PAGE;

        $context = $this->get_context_for_dynamic_submission();

        $data = $this->get_data();
        // Name is always set to false when the field is disabled.
        if ($data->name == false) {
            $data->name = '';
        }
        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        $num = $this->optional_param('num', null, PARAM_INT);
        $section = get_fast_modinfo($courseid)->get_section_info($num);
        $update = course_update_section($courseid, $section, $data);

        return [
            'result' => true,
            'data' => $data,
        ];
    }

    /**
     * Get section options
     *
     */
    public function get_section_options() {
        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        $sectionid = $this->optional_param('id', null, PARAM_INT);
        $so = \format_compass\local\section_options::get_section_options($courseid, $sectionid);
        return $so;
    }

    /**
     * Get context.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        $context = \context_course::instance($courseid);
        return $context;
    }

    /**
     * Set data
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        $do = \format_compass\local\options::get_default_options();
        $so = $this->get_section_options();
        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        $sectionnum = new stdClass();
        $sectionnum->section = $this->optional_param('num', null, PARAM_INT);
        $cs = get_fast_modinfo($courseid)->get_section_info($sectionnum->section);
        $old = "";
        // Apply default course options.
        $data = (object) [];
        foreach ($do['sectionelements'] as $key => $value) {
            $data->$key = $so->$key;
        }
        // Data must be initiated here with the ajax params.
        $data->courseid = $this->optional_param('courseid', null, PARAM_INT);
        $data->id = $this->optional_param('id', null, PARAM_INT);
        $data->num = $this->optional_param('num', null, PARAM_INT);
        // Initiating data for the custom checkmark.
        if ($cs->name != "") {
            $data->name = $cs->name;
        }
        $this->set_data($data);
    }

    /**
     * Has access ?
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        if (!has_capability('moodle/course:movesections', $this->get_context_for_dynamic_submission())) {
            throw new moodle_exception('Missing permission to edit section name');
        }
    }

    /**
     * Get page URL
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        return new moodle_url('/course/view.php', ['id' => $courseid]);
    }

    /**
     * Form definition
     *
     * @return void
     */
    protected function definition() {
        global $PAGE, $FULLME;
        $fullmepageurl = new \moodle_url($FULLME);

        $mform = $this->_form;
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $sectionnum = new stdClass();
        $sectionnum->section = $this->optional_param('num', 0, PARAM_INT);;
        $courseformat = course_get_format($courseid);
        $cs = get_fast_modinfo($courseid)->get_section_info($sectionnum->section);

        // Hidden elements like courseid and num need to be added here
        // otherwise some arguments will be missing when submitting the form.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'num');
        $mform->setType('num', PARAM_INT);

        $defaultsectionname = $courseformat->get_default_section_name($sectionnum);
        $defaultcustom = $mform->addElement('defaultcustom', 'name', get_string('sectionname'), [
            'defaultvalue' => $defaultsectionname,
            'customvalue' => $cs->name,
        ], ['size' => 30, 'maxlength' => 255, 'tag' => 'id_sectionname']);
        $mform->setDefault('name', false);
        $mform->addGroupRule('name', ['name' => [[get_string('maximumchars', '', 255), 'maxlength', 255]]]);
        $checkboxname = $defaultcustom->_elements[0]->_attributes["name"];
        $textboxname = $defaultcustom->_elements[1]->_attributes["name"];

        $sectionnametypes = [
            'sectionname_freetype' => get_string('sectionname_freetype', 'format_compass'),
            'sectionname_singleterm_singlenumber' => get_string('sectionname_singleterm_singlenumber', 'format_compass'),
            'sectionname_singleterm_doublenumber' => get_string('sectionname_singleterm_doublenumber', 'format_compass'),
            'sectionname_doubleterm_doublenumber' => get_string('sectionname_doubleterm_doublenumber', 'format_compass'),
        ];

        $mform->addElement(
            'select', 'sectionname_type',
            get_string('sectionname_type', 'format_compass'),
            $sectionnametypes, ['id' => 'id_sectionname_type', 'class' => 'select_name_type_option']
        );
        $mform->addHelpButton('sectionname_type', 'sectionname_type', 'format_compass');
        $mform->setType('sectionname_type', PARAM_TEXT);

        $sectionnameterms = [
            'sectionname_lesson' => get_string('sectionname_lesson', 'format_compass'),
            'sectionname_unit' => get_string('sectionname_unit', 'format_compass'),
            'sectionname_module' => get_string('sectionname_module', 'format_compass'),
            'sectionname_section' => get_string('sectionname_section', 'format_compass'),
            'sectionname_topic' => get_string('sectionname_topic', 'format_compass'),
        ];

        // Generate the section number range.
        for ($i = 1; $i <= 20; ++$i) {
            $sectionnamenumbers[$i] = $i;
        }

        $selectarray = [];
        $selectarray[] = & $mform->createElement(
            'select', 'sectionname_first_term',
            get_string('sectionname_first_term', 'format_compass'),
            $sectionnameterms, ['id' => 'id_sectionname_first_term', 'class' => 'select_name_type_option']
        );
        $selectarray[] = & $mform->createElement(
            'select', 'sectionname_first_number',
            get_string('sectionname_first_number', 'format_compass'),
            $sectionnamenumbers, ['id' => 'id_sectionname_first_number', 'class' => 'select_name_type_option']
        );
        $selectarray[] = & $mform->createElement(
            'select', 'sectionname_second_term',
            get_string('sectionname_second_term', 'format_compass'),
            $sectionnameterms, ['id' => 'id_sectionname_second_term', 'class' => 'select_name_type_option']
        );
        $selectarray[] = & $mform->createElement(
            'select', 'sectionname_second_number',
            get_string('sectionname_second_number', 'format_compass'),
            $sectionnamenumbers, ['id' => 'id_sectionname_second_number', 'class' => 'select_name_type_option']
        );
        $mform->addGroup($selectarray, 'selectArray', '', [' '], false);

        if ($sectionnum != 0 && $this->optional_param('tab', null, PARAM_TEXT) != 'assessments') {
            $mform->addElement('text', 'subname', get_string('subname', 'format_compass'));
        }

        $mform->hideif('selectArray', 'name[' . $checkboxname .']', 'notchecked');
        $mform->hideif('selectArray', 'sectionname_type', 'eq', 'sectionname_freetype');

        $mform->hideif('sectionname_second_term', 'sectionname_type', 'neq', 'sectionname_doubleterm_doublenumber');
        $mform->hideif('sectionname_second_number', 'sectionname_type', 'eq', 'sectionname_singleterm_singlenumber');

        $mform->hideif('sectionname_type', 'name[' . $checkboxname .']', 'notchecked');
    }
}
