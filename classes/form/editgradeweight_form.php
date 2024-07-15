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
 * compass form for editing course section.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace format_compass\form;

use context;
use moodle_exception;
use moodle_url;
use context_course;
use stdClass;
use core_form\dynamic_form;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page
}

class editgradeweight_form extends dynamic_form {
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
        $data->name = $this->optional_param('name', null, PARAM_TEXT);
        $update = course_update_section($courseid, $section, $data);
        $previewvalue = '';
        $so = \format_compass\local\section_options::get_section_options($courseid,  $section->id);
        if (isset($so->type_of_grade_weighting) && $so->type_of_grade_weighting == 'weighttype_weighted') {
            // Weighted.
            if ($so->weighttype_singlemultiple == 'weighttype_singlemultiple_multiplier') {
                $previewvalue = $this->CalculateMultiplierPreviewValue($so);
            } else if ($so->weighttype_singlemultiple == 'weighttype_singlemultiple_csv') {
                $previewvalue = $this->CalculateCSVPreviewValue($so);
            }
        }

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
        $data->title = $this->optional_param('name', null, PARAM_TEXT);
        $data->preview = $this->optional_param('num', null, PARAM_INT);
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
     * Calculate CSVPreviewValue from csv string on change function
     */
    public function CalculateCSVPreviewValue($so) {
        $totalweight = 0;

        $csvvalues = explode(',', str_replace(' ', '', $so->weighttype_multiplier_csvvalue));
        $i = 0;
        foreach ($csvvalues as $value) {
            $totalweight += $value;
            if ($i < sizeof($csvvalues)-1) {
                $breakdown = $breakdown . $value . '+';
            } else {
                $breakdown = $breakdown . $value;
            }
            $i = $i + 1;
        }
        if ($totalweight) {
            $totalweight = strval(round($totalweight,2)) . "%";
            $breakdown = "("  . $breakdown . ")";
            $fullstring = $totalweight . " "  . $breakdown;
        } else {
            $fullstring = "N/A";
        }
        return array($fullstring, $totalweight, $breakdown);
    }

    /**
     * Calculate MultiplierPreviewValue from weightValue and multiplier on change function
     */
    public function CalculateMultiplierPreviewValue($so) {
        $weighttype_multiplier_weightvalue = $so->weighttype_multiplier_weightvalue;
        $weighttype_multiplier_multipliervalue = $so->weighttype_multiplier_multipliervalue;

        if ($weighttype_multiplier_weightvalue && $weighttype_multiplier_multipliervalue) {
            $totalweight = $weighttype_multiplier_weightvalue * $weighttype_multiplier_multipliervalue;
            if ($totalweight) {
                $totalValue = strval(round($totalweight,2)) . "%";
                $breakdown = "(" . $weighttype_multiplier_multipliervalue . " x " .  $weighttype_multiplier_weightvalue . "%)";
                $fullstring = $totalValue . ' ' . $breakdown;
            } else {
                $fullstring= "N/A";
            }
        } else {
           $fullstring= "N/A";
        }
        return array($fullstring, $totalValue, $breakdown);
        //return $totalValue;
    }

    public function definition() {
        global $OUTPUT, $PAGE;
        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $sectioninfo = $this->_customdata['cs'];

        if (!isset($course)) {
            $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        } else {
            $courseid = $course->id;
        }

        if ($sectioninfo == null) {
            $sectionnum = new stdClass();
            $sectionnum->section = $this->optional_param('num', 0, PARAM_INT);;
            $sectioninfo = get_fast_modinfo($courseid)->get_section_info($sectionnum->section);
        }

        // Hidden elements like courseid and num need to be added here
        // otherwise some arguments will be missing when submitting the form.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'num');
        $mform->setType('num', PARAM_INT);

        $mform->addElement('hidden', 'name');
        $mform->setType('name', PARAM_TEXT);

        $courseformat = course_get_format($courseid);
        $courseformatoptions = $courseformat->get_format_options();
        $enableassessmentstab = $courseformatoptions['enableassessmentstab'];
        $assessmentscutoff = $courseformatoptions['assessmentscutoff'];

        $previewvalue = "N/A";
        $so = \format_compass\local\section_options::get_section_options($courseid,  $sectioninfo->id);
        if (isset($so->type_of_grade_weighting) && $so->type_of_grade_weighting == 'weighttype_weighted') {
            // Weighted.
            if ($so->weighttype_singlemultiple == 'weighttype_singlemultiple_single') {

            } else if ($so->weighttype_singlemultiple == 'weighttype_singlemultiple_multiplier') {
                $previewvalue = $this->CalculateMultiplierPreviewValue($so);
            } else if ($so->weighttype_singlemultiple == 'weighttype_singlemultiple_csv') {
                $previewvalue = $this->CalculateCSVPreviewValue($so);
            }
        }

        // Add the tab param to redirect the user to the right tab
        // after saving the section. The redirect is done in
        // course\format\compass\editsection.php.
        $sectionnumber = $sectioninfo->section;

        // Grade weighting conditions.
        if (\format_compass\local\section_options::is_assessment($sectionnumber)) {
            $mform->addElement('header', 'sectionassessmentsheader', get_string('sectionassessmentsheader', 'format_compass'));

            if ($courseformatoptions['displaysectionweights'] == "display_weights") {
                $gradeweightingtypes = array(
                    "weighttype_ungraded" => get_string('type_of_grade_weighting_ungraded', 'format_compass'),
                    "weighttype_weighted" => get_string('type_of_grade_weighting_weighted', 'format_compass'),
                    "weighttype_bonus" => get_string('type_of_grade_weighting_bonus', 'format_compass')
                );

                $mform->addElement('select', 'type_of_grade_weighting', get_string('type_of_grade_weighting', 'format_compass'), $gradeweightingtypes);
                $mform->addHelpButton('type_of_grade_weighting', 'type_of_grade_weighting', 'format_compass');
                $mform->setDefault('type_of_grade_weighting', 'weighttype_ungraded');
                $mform->setType('type_of_grade_weighting', PARAM_TEXT);

                $mform->addElement('float', 'weighttype_bonus_value', get_string('type_of_grade_weighting_bonus', 'format_compass'), '');
                $mform->addHelpButton('weighttype_bonus_value', 'weighttype_bonus_value', 'format_compass');
                $mform->setDefault('weighttype_bonus_value', 5);
                $mform->setType('weighttype_bonus_value', PARAM_TEXT);

                $weighttypesinglemultiple = array(
                    "weighttype_singlemultiple_single" => get_string('weighttype_singlemultiple_single', 'format_compass'),
                    "weighttype_singlemultiple_multiplier" => get_string('weighttype_singlemultiple_multiplier', 'format_compass'),
                    "weighttype_singlemultiple_csv" => get_string('weighttype_singlemultiple_csv', 'format_compass')
                );
                $mform->addElement('select', 'weighttype_singlemultiple', get_string('weighttype_singlemultiple', 'format_compass'), $weighttypesinglemultiple);
                $mform->addHelpButton('weighttype_singlemultiple', 'weighttype_singlemultiple', 'format_compass');
                $mform->setDefault('weighttype_singlemultiple', 'weighttype_singlemultiple_single');
                $mform->setType('weighttype_singlemultiple', PARAM_TEXT);

                $mform->addElement('float', 'weighttype_singlevalue_weightvalue', get_string('weighttype_singlevalue_weightvalue', 'format_compass'), '');
                $mform->addHelpButton('weighttype_singlevalue_weightvalue', 'weighttype_singlevalue_weightvalue', 'format_compass');
                $mform->setDefault('weighttype_singlevalue_weightvalue', 5);
                $mform->setType('weighttype_singlevalue_weightvalue', PARAM_TEXT);

                $mform->addElement('float', 'weighttype_multiplier_weightvalue', get_string('weighttype_multiplier_weightvalue', 'format_compass'), '');
                $mform->addHelpButton('weighttype_multiplier_weightvalue', 'weighttype_multiplier_weightvalue', 'format_compass');
                $mform->setDefault('weighttype_multiplier_weightvalue', '5');
                $mform->setType('weighttype_multiplier_weightvalue', PARAM_TEXT);

                $mform->addElement('float', 'weighttype_multiplier_multipliervalue', get_string('weighttype_multiplier_multipliervalue', 'format_compass'), '');
                $mform->addHelpButton('weighttype_multiplier_multipliervalue', 'weighttype_multiplier_multipliervalue', 'format_compass');
                $mform->setDefault('weighttype_multiplier_multipliervalue', '2');
                $mform->setType('weighttype_multiplier_multipliervalue', PARAM_TEXT);

                $mform->addElement('text', 'weighttype_multiplier_csvvalue', get_string('weighttype_multiplier_csvvalue', 'format_compass'), '');
                $mform->addHelpButton('weighttype_multiplier_csvvalue', 'weighttype_multiplier_csvvalue', 'format_compass');
                $mform->setDefault('weighttype_multiplier_csvvalue', '5,5');
                $mform->setType('weighttype_multiplier_csvvalue', PARAM_TEXT);

                $this->set_id_to_gradeweight_controls($mform, 'type_of_grade_weighting');
                $this->set_id_to_gradeweight_controls($mform, 'weighttype_singlemultiple');
                $this->set_id_to_gradeweight_controls($mform, 'weighttype_multiplier_weightvalue');
                $this->set_id_to_gradeweight_controls($mform, 'weighttype_multiplier_multipliervalue');
                $this->set_id_to_gradeweight_controls($mform, 'weighttype_multiplier_csvvalue');
                $this->set_class_to_multiplier_gradeweight_controls($mform, 'weighttype_multiplier_weightvalue');
                $this->set_class_to_multiplier_gradeweight_controls($mform, 'weighttype_multiplier_multipliervalue');


                $staticgroup = [];
                $staticgroup[] = & $mform->createElement('static', 'weighttype_preview_value', get_string('weighttype_preview', 'format_compass'), $previewvalue[0], array('class' => 'weighttype_preview_value', 'id' => 'id_weighttype_preview_value'));
                $mform->addGroup($staticgroup, 'staticgroup', get_string('weighttype_preview', 'format_compass'), array('class' => 'staticgroup', 'id' => 'id_staticgroup'), true);

                $mform->hideif('weighttype_bonus_value', 'type_of_grade_weighting', 'neq', 'weighttype_bonus');

                $mform->hideif('weighttype_singlemultiple', 'type_of_grade_weighting', 'neq', 'weighttype_weighted');

                $mform->hideif('weighttype_singlevalue_weightvalue', 'type_of_grade_weighting', 'neq', 'weighttype_weighted');
                $mform->hideif('weighttype_singlevalue_weightvalue', 'weighttype_singlemultiple', 'neq', 'weighttype_singlemultiple_single');

                $mform->hideif('weighttype_multiplier_weightvalue', 'type_of_grade_weighting', 'neq', 'weighttype_weighted');
                $mform->hideif('weighttype_multiplier_multipliervalue', 'type_of_grade_weighting', 'neq', 'weighttype_weighted');

                $mform->hideif('weighttype_multiplier_weightvalue', 'weighttype_singlemultiple', 'neq', 'weighttype_singlemultiple_multiplier');
                $mform->hideif('weighttype_multiplier_multipliervalue', 'weighttype_singlemultiple', 'neq', 'weighttype_singlemultiple_multiplier');

                $mform->hideif('weighttype_multiplier_csvvalue', 'type_of_grade_weighting', 'neq', 'weighttype_weighted');
                $mform->hideif('weighttype_multiplier_csvvalue', 'weighttype_singlemultiple', 'neq', 'weighttype_singlemultiple_csv');

                $mform->hideif('staticgroup', 'type_of_grade_weighting', 'neq', 'weighttype_weighted');
                $mform->hideif('staticgroup', 'weighttype_singlemultiple', 'eq', 'weighttype_singlemultiple_single');

                $mform->addRule('weighttype_bonus_value', get_string('weighttype_bonus_value_help', 'format_compass'), 'numeric', '', 'client', false, false);
                $mform->addRule('weighttype_bonus_value', get_string('weighttype_bonus_value_help', 'format_compass'), 'required', '', 'client', false, false);
                $mform->addRule('weighttype_bonus_value', get_string('weighttype_bonus_value_help', 'format_compass'), 'regex', '/^(?:100(?:\.[0]?(0))?|[1-9][0-9]?(?:\.[0-9]?[0-9])?|0?\.(?!0+$)\d{1,2})$/', 'client', false, false);

                $mform->addRule('weighttype_singlevalue_weightvalue', get_string('weighttype_singlevalue_weightvalue_help', 'format_compass'), 'numeric', '', 'client', false, false);
                $mform->addRule('weighttype_singlevalue_weightvalue', get_string('weighttype_singlevalue_weightvalue_help', 'format_compass'), 'required', '', 'client', false, false);
                $mform->addRule('weighttype_singlevalue_weightvalue', get_string('weighttype_singlevalue_weightvalue_help', 'format_compass'), 'regex', '/^(?:100(?:\.[0]?(0))?|[1-9][0-9]?(?:\.[0-9]?[0-9])?|0?\.(?!0+$)\d{1,2})$/', 'client', false, false);

                $mform->addRule('weighttype_multiplier_weightvalue', get_string('weighttype_multiplier_weightvalue_help', 'format_compass'), 'numeric', '', 'client', false, false);
                $mform->addRule('weighttype_multiplier_weightvalue', get_string('weighttype_multiplier_weightvalue_help', 'format_compass'), 'required', '', 'client', false, false);
                $mform->addRule('weighttype_multiplier_weightvalue', get_string('weighttype_multiplier_weightvalue_help', 'format_compass'), 'regex', '/^(?:100(?:\.[0]?(0))?|[1-9][0-9]?(?:\.[0-9]?[0-9])?|0?\.(?!0+$)\d{1,2})$/', 'client', false, false);

                $mform->addRule('weighttype_multiplier_multipliervalue', get_string('weighttype_multiplier_multipliervalue_help', 'format_compass'), 'numeric', '', 'client', false, false);
                $mform->addRule('weighttype_multiplier_multipliervalue', get_string('weighttype_multiplier_multipliervalue_help', 'format_compass'), 'required', '', 'client', false, false);
                $mform->addRule('weighttype_multiplier_multipliervalue', get_string('weighttype_multiplier_multipliervalue_help', 'format_compass'), 'regex', '/^(?:100|([1-9][0-9])|[2-9]|0(?![0-1]+$)\d{1})$/', 'client', false, false);

                $mform->addRule('weighttype_multiplier_csvvalue', get_string('weighttype_multiplier_csvvalue_help', 'format_compass'), 'required', '', 'client', false, false);
                $mform->addRule('weighttype_multiplier_csvvalue', get_string('weighttype_multiplier_csvvalue_help', 'format_compass'), 'regex', '/^(?:100(?:\.[0]?(0))?|[1-9][0-9]?(?:\.[0-9]?[0-9])?|(0?\.(?!0+$)\d{1,2}))((,)[ ]?(?:100(\.[0]?(0))?|[1-9][0-9]?(?:\.[0-9]?[0-9])?|(0?\.(?!0+$)\d{1,2}))){1,9}$/', 'client', false, false);
            } else {

                $context = \context_course::instance($courseid);
                $course_settings = strtolower(get_string('coursesettings', 'format_compass'));
                if (has_capability('moodle/course:update', $context)) {
                    $url = new \moodle_url('/course/edit.php', ['id' => $courseid]);
                    $replace_link = '<a target=_blank href='. $url .'>' . $course_settings .'</a>';
                    $hide_message = get_string('sectionweighthiddenmessage', 'format_compass', $replace_link);
                } else {
                    $hide_message = get_string('sectionweighthiddenmessage', 'format_compass', $course_settings);
                }

                $mform->addElement('html', $OUTPUT->notification($hide_message, \core\output\notification::NOTIFY_INFO, false));
            }
        }

        $so = \format_compass\local\section_options::get_section_options($courseid, $sectioninfo->id);
        if ($so !== false) {
            foreach ($so as $field => $value) {
                $mform->setDefault($field, $value);
            }
        }
    }

    /**
     * This function sets program defined ID to form controls instead of the ID set by moodle, to avoid any future discrepencies
     * This ID will be used in the jquery for query selector
     */
    public function set_id_to_gradeweight_controls($mform, $controlname) {
        $attributearray = $mform->getElement($controlname)->getAttributes();
        $attributearray['id'] = 'id_' . $controlname;
        $mform->getElement($controlname)->setAttributes($attributearray);
    }

    /**
     * This function sets program defined class to form controls
     * This class will be used in the jquery for query selector
     */
    public function set_class_to_multiplier_gradeweight_controls($mform, $controlname) {
        $attributearray = $mform->getElement($controlname)->getAttributes();
        $attributearray['class'] = 'class_weighttype_multiplier';
        $mform->getElement($controlname)->setAttributes($attributearray);
    }

    /**
     * This function is called after the form values have been posted.
     * In this function, we remove the validate conditions for form controls that are hidden.
     * Since, these controls are hidden and not submitted, there is no need to validate these controls
     */
    public function definition_after_data() {
        global $CFG, $DB;
        parent::definition_after_data();
        $mform = $this->_form;
        $requiredarray = $mform->_required;
        foreach ($requiredarray as $key => $required) {
            $submittedvalues = $mform->_submitValues;
            if (count($submittedvalues) !== 0) {
                if (!isset($submittedvalues[$required])) {
                    unset($requiredarray[$key]);
                }
            }
        }
        $mform->_required = $requiredarray;
    }
}