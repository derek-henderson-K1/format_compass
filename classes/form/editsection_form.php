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
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page
}

/**
 * Original class defined in course\editsection_form.php.
 */
class editsection_form extends \editsection_form {

    public function definition() {
        global $OUTPUT, $PAGE;
        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $sectioninfo = $this->_customdata['cs'];

        $courseformat = course_get_format($course->id);
        $courseformatoptions = $courseformat->get_format_options();
        $enableassessmentstab = $courseformatoptions['enableassessmentstab'];
        $assessmentscutoff = $courseformatoptions['assessmentscutoff'];

        // Add the tab param to redirect the user to the right tab
        // after saving the section. The redirect is done in
        // course\format\compass\editsection.php.
        $sectionnumber = $sectioninfo->section;
        if ($sectionnumber > 0) {
            $tabreturn = 'lessons';
            if ($sectionnumber >= $assessmentscutoff && $enableassessmentstab) {
                $tabreturn = 'assessments';
            }
            $mform->addElement('hidden', 'tab', $tabreturn);
            $mform->setType('tab', PARAM_RAW);
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'generalhdr', get_string('general'));

        // Section group settings should be hidden if both the following conditions are true
        // - Assessments tab is enabled
        // -  Section is after the cutoff
        if (!($enableassessmentstab && \format_compass\local\section_options::is_assessment($sectionnumber))) {
            $mform->addElement('advcheckbox', 'sectiongroup', get_string('sectiongroup', 'format_compass'), get_string('sectiongroup_label', 'format_compass'), array('id' => 'id_sectiongroup'));
            $mform->setDefault('sectiongroup', false);
            $mform->addHelpButton('sectiongroup', 'sectiongroup', 'format_compass');
            $mform->setType('sectiongrouptext', PARAM_BOOL);

            $mform->addElement('text', 'sectiongrouptext', get_string('sectiongrouptext', 'format_compass'), '');
            $mform->setDefault('sectiongrouptext', 'Section group');
            $mform->setType('sectiongrouptext', PARAM_TEXT);

            $mform->hideif('sectiongrouptext', 'sectiongroup', 'notchecked');
        }

        $defaultcustom = $mform->addElement('defaultcustom', 'name', get_string('sectionname'), [
            'defaultvalue' => $this->_customdata['defaultsectionname'],
            'customvalue' => $sectioninfo->name,
        ], ['size' => 30, 'maxlength' => 255, 'tag' => 'id_sectionname']);
        $mform->setDefault('name', false);
        $mform->addGroupRule('name', array('name' => array(array(get_string('maximumchars', '', 255), 'maxlength', 255))));
        $checkboxname = $defaultcustom->_elements[0]->_attributes["name"];
        $textboxname = $defaultcustom->_elements[1]->_attributes["name"];

        $sectionnametypes = array(
            'sectionname_freetype' => get_string('sectionname_freetype', 'format_compass'),
            'sectionname_singleterm_singlenumber' => get_string('sectionname_singleterm_singlenumber', 'format_compass'),
            'sectionname_singleterm_doublenumber' => get_string('sectionname_singleterm_doublenumber', 'format_compass'),
            'sectionname_doubleterm_doublenumber' => get_string('sectionname_doubleterm_doublenumber', 'format_compass')
        );

        $mform->addElement('select', 'sectionname_type', get_string('sectionname_type', 'format_compass'), $sectionnametypes, array('id' => 'id_sectionname_type', 'class' => 'select_name_type_option'));
        $mform->addHelpButton('sectionname_type', 'sectionname_type', 'format_compass');
        $mform->setType('sectionname_type', PARAM_TEXT);

        $sectionnameterms = array(
            'sectionname_lesson' => get_string('sectionname_lesson', 'format_compass'),
            'sectionname_unit' => get_string('sectionname_unit', 'format_compass'),
            'sectionname_module' => get_string('sectionname_module', 'format_compass'),
            'sectionname_section' => get_string('sectionname_section', 'format_compass'),
            'sectionname_topic' => get_string('sectionname_topic', 'format_compass')
        );

        // Generate the section number range.
        for ($i = 1; $i <= 20; ++$i) {
            $sectionnamenumbers[$i] = $i;
        }

        $selectarray = array();
        $selectarray[] = & $mform->createElement('select', 'sectionname_first_term', get_string('sectionname_first_term', 'format_compass'), $sectionnameterms, array('id' => 'id_sectionname_first_term', 'class' => 'select_name_type_option'));
        $selectarray[] = & $mform->createElement('select', 'sectionname_first_number', get_string('sectionname_first_number', 'format_compass'), $sectionnamenumbers, array('id' => 'id_sectionname_first_number', 'class' => 'select_name_type_option'));
        $selectarray[] = & $mform->createElement('select', 'sectionname_second_term', get_string('sectionname_second_term', 'format_compass'), $sectionnameterms, array('id' => 'id_sectionname_second_term', 'class' => 'select_name_type_option'));
        $selectarray[] = & $mform->createElement('select', 'sectionname_second_number', get_string('sectionname_second_number', 'format_compass'), $sectionnamenumbers, array('id' => 'id_sectionname_second_number', 'class' => 'select_name_type_option'));
        $mform->addGroup($selectarray, 'selectArray', '', array(' '), false);

        // Additional fields that course format has defined.
        $formatoptions = $courseformat->section_format_options(true);
        if (!empty($formatoptions)) {
            $elements = $courseformat->create_edit_form_elements($mform, true);
        }

        $summary = $mform->createElement('editor', 'summary_editor', get_string('summary'), null, $this->_customdata['editoroptions']);
        $mform->addElement($summary);

        $mform->addHelpButton('summary_editor', 'summary');
        $mform->setType('summary_editor', PARAM_RAW);

        $mform->hideif('selectArray', 'name[' . $checkboxname .']', 'notchecked');
        $mform->hideif('selectArray', 'sectionname_type', 'eq', 'sectionname_freetype');

        $mform->hideif('sectionname_second_term', 'sectionname_type', 'neq', 'sectionname_doubleterm_doublenumber');
        $mform->hideif('sectionname_second_number', 'sectionname_type', 'eq', 'sectionname_singleterm_singlenumber');

        $mform->hideif('sectionname_type', 'name[' . $checkboxname .']', 'notchecked');
        $mform->disabledif('name[' . $textboxname .']', 'sectionname_type', 'neq', 'sectionname_freetype');

        $mform->hideif('subname', 'hidesubnamefield', 'eq', 1);

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
                $staticgroup[] = & $mform->createElement('static', 'weighttype_preview_value', get_string('weighttype_preview', 'format_compass'), 'N/A', array('class' => 'weighttype_preview_value', 'id' => 'id_weighttype_preview_value'));
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
                $context = \context_course::instance($course->id);
                $coursesettings = strtolower(get_string('coursesettings', 'format_compass'));
                if (has_capability('moodle/course:update', $context)) {
                    $url = new \moodle_url('/course/edit.php', ['id' => $course->id]);
                    $replacelink = '<a target=_blank href=' . $url . '>' . $coursesettings . '</a>';
                    $hidemessage = get_string('sectionweighthiddenmessage', 'format_compass', $replacelink);
                } else {
                    $hidemessage = get_string('sectionweighthiddenmessage', 'format_compass', $coursesettings);
                }

                $mform->addElement('html', $OUTPUT->notification($hidemessage, \core\output\notification::NOTIFY_INFO, false));
            }
        }

        $so = \format_compass\local\section_options::get_section_options($course->id, $sectioninfo->id);
        if ($so !== false) {
            foreach ($so as $field => $value) {
                $mform->setDefault($field, $value);
            }
        }

        $PAGE->requires->js_call_amd('format_compass/local/editsection_form', 'editsection_form_helper');
        $mform->_registerCancelButton('cancel');
        $mform->validate();
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
