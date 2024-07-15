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
use context_system;

/**
 * Class to manage the course options.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_options extends \format_compass\local\options {

    /** @var array Default options for colours in course options. */
    public const defaultvalues = array (
        'custom_accent_1_dark' => '#E97D11',
        'custom_accent_1_light' => '#FFDFD1',
        'custom_accent_2_dark' => '#3F4197',
        'custom_accent_2_light' => '#C9DCF3',
    );

    /** @var array List of course options using file managers. */
    public const courseoptionsfilemanager = array (
        'bannerimage', 'bannermobile', 'verticalimage'
    );

    /** @var array List of course options using editors. */
    public const courseoptionstexteditor = array (
        'textareaabove', 'textareabelow'
    );

    /**
     * Get the combined array of course option files
     * @return array The combined array of all course option files.
     */
    public static function get_courseoptionsallfiles():array {
        return array_merge(self::courseoptionsfilemanager, self::courseoptionstexteditor);
    }

    /**
     * Prepare the editors content for editing.
     *
     * @param array|stdClass $formatoptions The course format options.
     * @param null|context_course $context The course context object.
     *
     * @return $formatoptions The course options with formated editor.
     */
    public static function prep_editor_editing($formatoptions, $context = null) {
        global $COURSE;

        $options = \format_compass\local\options::get_filemanager_options();
        // Use the course context if the context is not passed in argument.
        // Also set the course context in the editor $options array.
        if (is_null($context)) {
            $context = $options['context'] = \context_course::instance($COURSE->id);
        } else {
            $options['context'] = $context;
        }

        // Make sure the $formatoptions is an object
        // to be compatible with file_prepare_standard_editor().
        if (!is_object($formatoptions)) {
            $formatoptions = (object)$formatoptions;
        }

        foreach (self::courseoptionstexteditor as $editor) {

            $fieldvalue = $formatoptions->$editor['default'];

            // To be optimized.
            // Manipulate the data structure of the course format options
            // object to be compatible with file_prepare_standard_editor().
            $formatoptions->{$editor . 'format'} = FORMAT_HTML;
            $formatoptions->{$editor} = $fieldvalue;

            // Create the _editor key so Moodle can use it to
            // write the media in the draft file area,
            // generate the itemid and the editor format.
            $formatoptions->{$editor . '_editor'} = '';

            $formatoptions = file_prepare_standard_editor(
                $formatoptions, $editor, $options, $context, 'format_compass', $editor, 0
            );
        }

        return $formatoptions;
    }

    /**
     * Generate HTML to display editor content in a browser.
     *
     * @param stdClass $course The course the editor belongs to.
     * @param string $editor The editor text to format.
     * @param string $filearea The editor's file area.
     *
     * @return string HTML to output.
     */
    public static function format_editor_text(stdClass $course, string $editor, string $filearea): string {

        $context = \context_course::instance($course->id);

        $formatededitor = file_rewrite_pluginfile_urls(
            $editor, 'pluginfile.php', $context->id, 'format_compass', $filearea, 0
        );

        $editoroptions = \format_compass\local\options::get_editor_options();
        // TODO : Use the editor original format instead of hard-coding it.
        return format_text($formatededitor, FORMAT_HTML, $editoroptions);
    }

    /**
     * Initialize the course format options (CFO) when creating a course or setting the compass format for the first time.
     *
     * This method is created to initialize the hidden CFO since they
     * have no GUI to be set by the user.
     *
     * @param array $data The course creation data that was submitted by the user
     *
     * @return array $data The course creation data with init options
     */
    public static function initialize_course_format_options($data): array {
        static $initialized = false;

        if ($initialized === false) {
            $options = \format_compass\local\options::get_default_options();
            foreach ($options['courseelements'] as $optionname => $optionvalue) {

                // Skip if the option doesn't exists.
                if (!isset($data[$optionname])) {
                    continue;
                }

                // If the option needs to be initialized it will
                // be declared with undefined as it's default
                // value in lib.php.
                if ($data[$optionname] == 'undefined') {
                    $functionname = "initialize_{$optionname}";
                    if (method_exists(__CLASS__ , $functionname)) {
                        $data[$optionname] = self::$functionname($data);
                    }
                }
            }
        }
        $initialized = true;
        return $data;
    }

    /**
     * Initialize the assessments cutoff value with a default value of number of section.
     *
     * @param array $data The course creation data that was submitted by the user
     *
     * @return int The default value of assessments cutoff during init
     */
    public static function initialize_assessmentscutoff($data) {
        if ($data['assessmentscutoff'] == 'undefined') {
            if (isset($data["numsections"])) {
                // This is a brand new course being created, so we have the $data["numsections"] value.
                return $data["numsections"] + 1;
            } else {
                // This is an existing course being modified, so we do not have the $data["numsections"] value
                // instead we get the section count from count($format->get_sections()).
                $format = course_get_format($data['id']);
                return count($format->get_sections());
            }
        }
        return $data['assessmentscutoff'];
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
        global $DB;

        // Get the default course options with default value.
        $defaultcourseoptions = \format_compass\local\options::get_default_options();

        // Get the last saved options value.
        $oldcourseoptions = $DB->get_record(
            'format_compass_options',
            ['courseid' => $course->id, 'name' => 'courseelements']
        );

        // If there is values (new course has none).
        if ($oldcourseoptions !== false) {

            // Populate the options with the last inputed values.
            $savedcourseoptions = json_decode($oldcourseoptions->value);
            foreach ($defaultcourseoptions['courseelements'] as $coname => $covalue) {
                if (isset($courseformatoptions[$coname]) &&  isset($savedcourseoptions->$coname)) {
                    $courseformatoptions[$coname]['default'] = $savedcourseoptions->$coname;
                }
            }
        }

        return $courseformatoptions;
    }

    /**
     * Save course additional options.
     *
     * @param array $data The submited course options.
     *
     * @return void
     */
    public static function save_options(array $data): void {
        global $DB;

        // Get the default course options.
        $courseoptions = \format_compass\local\options::get_default_options();

        $currentcourseoptions = $DB->get_field(
            'format_compass_options',
            'value',
            ['courseid' => $data['id'], 'name' => 'courseelements']
        );

        if ($currentcourseoptions !== false) {
            $co = json_decode($currentcourseoptions);
        } else {
            $co = new \stdClass();
        }

        // Update only the options passed in $data.
        foreach ($data as $optname => $optvalue) {
            // Make sure the option exists in the course options list.
            if (array_key_exists($optname, $courseoptions['courseelements'])) {
                $co->$optname = $optvalue;
                // In case of editor, save the format.
                if (array_key_exists($optname . 'format', $data)) {
                    $co->{$optname . 'format'} = $data[$optname . 'format'];
                }
            }
        }

        $data = (array)$data;

        \format_compass\local\options::save_option(0, $data['id'], 'courseelements', json_encode($co));
    }

    /**
     * Create course CSS file.
     *
     * @param array $data The submited course options.
     *
     * @return void
     */
    public static function generate_cssfile(array $data): void {
        $fs = get_file_storage();

        // Context has to be context_system because the course may not exist yet.
        $context = context_system::instance();

        // Prepare file record object.
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'format_compass',
            'filearea' => 'styles',
            'itemid' => $context->id,
            'filepath' => '/css/',
            'filename' => 'course' . $data['id'] . '.css',
        ];

        // Get file from the file system.
        $file = $fs->get_file(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        );

        // Delete the old file if it exist.
        if ($file) {
            $file->delete();
        }

        // Course options that need to be included in the generated stylesheet.
        $courseoptions = [
            'custom_accent_1_dark',
            'custom_accent_1_light',
            'custom_accent_2_dark',
            'custom_accent_2_light'
        ];

        $css = ':root{';
        $colouroptions = new \format_compass\local\colour_options($data['id']);
        $css .= $colouroptions->get_all_scss_variables();

        foreach ($courseoptions as $courseoption) {
            if (!empty($data[$courseoption])) {
                $courseoptionvalue = $data[$courseoption];
            } else {
                $courseoptionvalue = self::defaultvalues[$courseoption];
            }

            $css .= sprintf('--%s:%s;', $courseoption, $courseoptionvalue);
        }

        $courseimagefile = self::createcourseimage($data['id']);
        if (!is_null($courseimagefile)) {
            $css .= '--custom_home_banner:url('.$courseimagefile.');';
        }
        $css .= '}';

        // Create the css file.
        $fs->create_file_from_string($fileinfo, $css);
    }


    /**
     * Create course image.
     *
     * @param int $courseid  the id of the course.
     *
     * @return string $courseimage the generated link to the courseimage.
     */
    public static function createcourseimage($courseid) {
        global $OUTPUT;
        $courseimage = \cache::make('core', 'course_image')->get($courseid);
        if (is_null($courseimage)) {
            // If no course image stored use a generated one as a placeholder.
             $courseimage = $OUTPUT->get_generated_image_for_id($courseid);
        }
        return $courseimage;
    }

}
