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

namespace format_compass\local;

use stdClass;

/**
 * Class to manage additional format options.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class options {

    /**
     * Find the given string is JSON format or not.
     *
     * @param string $string The JSON string to test against.
     * @return bool True if it is JSON.
     */
    public static function is_json($string) {
        return (is_null(json_decode($string))) ? false : true;
    }

    /**
     * Get the module type from a form class name.
     *
     * Useful to get the module type module when the module
     *
     * @param string $classname The class name to extract the module type from.
     * @return string $modtype The module type name.
     */
    public static function get_mod_type(string $classname): string {
        $modclass = preg_match("/^mod_([a-z0-9_]{1,28})_mod_form/", $classname, $classparts);
        $modtype = $classparts[1];
        return $modtype;
    }

    /**
     * Adds the additional activity options for the label activity.
     *
     * @param MoodleQuickForm $mform The actual activity form object.
     * @param stdClass $defaultvalues The fields value.
     * @return void
     */
    public static function add_options_for_mod_label($mform, $defaultvalues) {
        throw new \coding_exception(__FUNCTION__.' is deprecated, please use activity_options::add_options_for_mod_label()');
    }

    /**
     * Get custom additional field value for the module.
     *
     * @param int $cmid Course module id.
     * @param string $name Module additional field name.
     * @return null|string Returns value of given module field.
     */
    public static function get_option(int $cmid, $name) {
        global $DB;

        $option = $DB->get_field(
            'format_compass_options',
            'value',
            ['cmid' => $cmid, 'name' => $name]
        );

        if ($option) {
            return $option;
        }

        return null;
    }

    /**
     * Get compass additional option values for the given module.
     *
     * @param int $cm Course module object.
     * @return stdclass $options List of additional field values.
     */
    public static function get_options($cm) {
        global $DB;

        // Contains the options for the given course module.
        $options = new \stdclass;

        $records = $DB->get_records('format_compass_options', ['cmid' => $cm->id]);

        if ($records) {
            foreach ($records as $field) {
                if (self::is_json($field->value)) {
                    $options->{$field->name} = json_decode($field->value, true);
                } else {
                    $options->{$field->name} = $field->value;
                }
            }
        }

        return $options;
    }

    /**
     * Save section additional options.
     *
     * @param array $data The submited section options
     * @param int $courseid The course id
     * @return void
     */
    public static function save_section_options(array $data, int $courseid) {
        throw new \coding_exception(__FUNCTION__.' is deprecated, please use section_options::save_options()');
    }

    /**
     * Save course additional options.
     *
     * @param array $data The submited course options
     * @return void
     */
    public static function save_course_options(array $data): void {
        throw new \coding_exception(__FUNCTION__.' is deprecated, please use course_options::save_options()');
    }

    /**
     * Restore section options once we switch back to format_compass.
     *
     * @param array $courseformatoptions The list of course format options
     * @param stdClass $course The course object
     *
     * @return array $courseformatoptions The list of course format options with old values set in.
     */
    public static function restore_course_options(array $courseformatoptions, stdClass $course): array {
        throw new \coding_exception(__FUNCTION__.' is deprecated, please use course_options::restore_course_options()');
    }

    /**
     * Restore section options once we switch back to format_compass.
     *
     * @param stdClass $course The course object
     *
     * @return array $courseformatoptions The list of course format options with old values set in.
     */
    public static function restore_all_section_options(stdClass $course): void {
        throw new \coding_exception(__FUNCTION__.' is deprecated, please use section_options::restore_all_section_options()');
    }

    /**
     * Insert the additional module fields data to the table.
     *
     * @param int $cmid Course module id.
     * @param int $courseid Course id.
     * @param string $name Field name.
     * @param mixed $value value of the field.
     * @return void
     */
    public static function save_option(int $cmid, int $courseid, string $name, $value): void {
        global $DB;

        // Create an object with the new option value to save
        // or the existing value to update.
        $option = new \stdClass;
        $option->cmid = $cmid;
        $option->courseid = $courseid;
        $option->name = $name;
        $option->value = $value ?: '';
        $option->timemodified = time();

        // Check if record exists.
        $optionexists = $DB->get_record(
            'format_compass_options',
            ['cmid' => $cmid, 'courseid' => $courseid, 'name' => $name]
        );

        if ($optionexists) {
            $option->id = $optionexists->id;
            $option->timecreated = $optionexists->timecreated;
            $DB->update_record('format_compass_options', $option);
        } else {
            $option->timecreated = time();
            $DB->insert_record('format_compass_options', $option);
        }
    }

    /**
     * Prepares file manager(s) by populating existing images
     * @param string|array $filemanagers filemanager (array or a single entity)
     * @param int $courseid Course id.
     * @param MoodleQuickForm $mform The actual activity form object.
     * @return void
     */
    public static function prep_filemanagers(string|array $filemanagers, int $courseid, $mform) {
        // Prepare draftarea for custom images.
        if (is_array($filemanagers)) {
            foreach ($filemanagers as $filemanager) {
                self::populate_filemanager($filemanager, $courseid, $mform);
            }
        } else {
            self::populate_filemanager($filemanagers, $courseid, $mform);
        }
    }

    /**
     * Populates a single file manager by existing image
     * @param string $filemanager filemanager
     * @param int $courseid Course id.
     * @param MoodleQuickForm $mform The actual activity form object.
     * @return void
     */
    public static function populate_filemanager(string $filemanager, int $courseid, $mform) {
        $context = \context_course::instance($courseid);
        $filemanagerdefaults = self::get_filemanager_options();

        $fieldname = $filemanager.'_filemanager';
        $draftfileid = file_get_submitted_draft_itemid($fieldname);
        file_prepare_draft_area($draftfileid, $context->id, 'format_compass', $filemanager, $filemanager, $filemanagerdefaults);
        $element = $mform->getElement($fieldname);
        $element->setValue([ 'value' => $draftfileid ]);
        $mform->setDefault($fieldname, $draftfileid);
    }

    /**
     * Get default value for the course, section and module config.
     *
     * @return array $formatoptions The list of options.
     */
    public static function get_default_options(): array {

        static $courseformatoptions;

        if (is_null($courseformatoptions)) {

            // Format options.
            $courseformatoptions['formatelements'] = (array) get_config('format_compass');

            // Course options.
            $courseformatoptions['courseelements']['termforlessonstab'] = '';
            $courseformatoptions['courseelements']['enableassessmentstab'] = '';
            $courseformatoptions['courseelements']['termforassessmentstab'] = '';
            $courseformatoptions['courseelements']['assessmentscutoff'] = '';

            // Colour options.
            $courseformatoptions['courseelements']['custom_accent_1_shading'] = '';
            $courseformatoptions['courseelements']['custom_accent_1_dark'] = '';
            $courseformatoptions['courseelements']['custom_accent_1_light'] = '';
            $courseformatoptions['courseelements']['custom_accent_2_shading'] = '';
            $courseformatoptions['courseelements']['custom_accent_2_dark'] = '';
            $courseformatoptions['courseelements']['custom_accent_2_light'] = '';

            // Course Home tab options.
            $courseformatoptions['courseelements']['bottomstripecourseimage'] = '';
            $courseformatoptions['courseelements']['activitylistlayout'] = '';

            // Colour options for sections.
            $courseformatoptions['courseelements']['bannerimage_filemanager'] = '';
            $courseformatoptions['courseelements']['bannermobile_filemanager'] = '';
            $courseformatoptions['courseelements']['lessonbannercolors'] = '';
            $courseformatoptions['courseelements']['bannerleftopacity'] = '';
            $courseformatoptions['courseelements']['bannerrightopacity'] = '';
            $courseformatoptions['courseelements']['bottomstripe'] = '';

            // Lessons Tab Options.
            $courseformatoptions['courseelements']['verticalimage_filemanager'] = '';
            $courseformatoptions['courseelements']['vertbgfilter'] = '';
            $courseformatoptions['courseelements']['vertbgfilteropacity'] = '';

             // Assessment options.
            $courseformatoptions['courseelements']['initialcollapsestate'] = '';
            $courseformatoptions['courseelements']['displaysectionweights'] = '';
            $courseformatoptions['courseelements']['displaytextareas'] = '';
            $courseformatoptions['courseelements']['textareaabove'] = '';
            $courseformatoptions['courseelements']['textareabelow'] = '';

            // Section options.
            $courseformatoptions['sectionelements']['type_of_grade_weighting'] = '';
            $courseformatoptions['sectionelements']['weighttype_bonus_value'] = '';
            $courseformatoptions['sectionelements']['weighttype_singlemultiple'] = '';
            $courseformatoptions['sectionelements']['weighttype_singlevalue_weightvalue'] = '';
            $courseformatoptions['sectionelements']['weighttype_multiplier_weightvalue'] = '';
            $courseformatoptions['sectionelements']['weighttype_multiplier_multipliervalue'] = '';
            $courseformatoptions['sectionelements']['weighttype_multiplier_csvvalue'] = '';

            $courseformatoptions['sectionelements']['subname'] = '';
            $courseformatoptions['sectionelements']['assessmentstablayout'] = '';
            $courseformatoptions['sectionelements']['sectionname_type'] = '';
            $courseformatoptions['sectionelements']['sectionname_first_term'] = '';
            $courseformatoptions['sectionelements']['sectionname_first_number'] = '';
            $courseformatoptions['sectionelements']['sectionname_second_term'] = '';
            $courseformatoptions['sectionelements']['sectionname_second_number'] = '';

            $courseformatoptions['sectionelements']['sectiongroup'] = '';
            $courseformatoptions['sectionelements']['sectiongrouptext'] = '';   
        }

        return $courseformatoptions;
    }

    /**
     * Initialize the course format options (CFO) when creating a course or setting the compass format for the first time.
     *
     * This method is created to initialize the hidden CFO since they
     * have no GUI to be set by the user.
     *
     * @param array $data The course creation data that was submitted by the user
     * @return array $data The course creation data with init options
     */
    public static function initialize_course_format_options($data): array {
        throw new \coding_exception(__FUNCTION__.' is deprecated, please use course_options::initialize_course_format_options()');
    }

    /**
     * Initialize the assessments cutoff value with a default value of number of section.
     * @param array $data The course creation data that was submitted by the user
     * @return int The default value of assessments cutoff during init
     */
    public static function initialize_assessmentscutoff($data) {
        throw new \coding_exception(__FUNCTION__.' is deprecated, please use course_options::initialize_assessmentscutoff()');
    }

    /**
     * Generate a range of values to be used in a dropdown list.
     * @param int $from starting value
     * @param int $to ending value
     * @param int $increment increment value in the loop
     * @param string $symbolvalue The symbol value
     * @return array $list The range of values
     */
    public static function generate_range(int $from, int $to, int $increment, string $symbolvalue) {
        for ($x = $from; $x <= $to; $x += $increment) {
            $list[$x.$symbolvalue] = $x.$symbolvalue;
        }
        return $list;
    }

    /**
     * Get all the editor options.
     *
     * @return array $edtoroptions The list of options.
     */
    public static function get_editor_options() {
        $editoroptions = [
            'noclean' => true,
            'overflowdiv' => true,
            'subdirs' => 0,
            'maxfiles' => -1,
        ];
        return $editoroptions;
    }

    /**
     * Get file manager options.
     *
     * @return array $edtoroptions The list of options.
     */
    public static function get_filemanager_options() {
        $filemanageroptions = [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['web_image'],
        ];
        return $filemanageroptions;
    }
}
