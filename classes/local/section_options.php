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
 * Class to manage the section options.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_options extends \format_compass\local\options {

    /**
     * Save section additional options.
     *
     * @param array $data The submited section options
     * @param int $courseid The course id
     *
     * @return void
     */
    public static function save_options(array $data, int $courseid) {
        global $DB;

        if (!isset($data['id'])) {
            // If the id is not set return.
            return false;
        }
        // Get the default course options.
        $sectionoptions = \format_compass\local\options::get_default_options();

        $currentsectionoptions = $DB->get_field(
            'format_compass_options',
            'value',
            ['courseid' => $courseid,  'cmid' => $data['id'], 'name' => 'sectionelements']
        );

        if ($currentsectionoptions !== false) {
            $so = json_decode($currentsectionoptions);
        } else {
            $so = new \stdClass();
        }

        // Update only the options passed in $data.
        foreach ($data as $optname => $optvalue) {
            // Make sure the option exists in the course options list.
            if (array_key_exists($optname, $sectionoptions['sectionelements'])) {
                $so->$optname = $optvalue;
            }
        }

        \format_compass\local\options::save_option(
            $data['id'],
            $courseid,
            'sectionelements',
            json_encode($so)
        );
    }

    /**
     * Get section options.
     *
     * @param int $courseid The course id.
     * @param int $sectionid The section id.
     *
     * @return stdclass Object containing section options.
     * @return bool False if no section options are found.
     */
    public static function get_section_options(int $courseid, int $sectionid): stdclass | bool {
        global $DB;

        $currentsectionoptions = $DB->get_field(
            'format_compass_options',
            'value',
            ['courseid' => $courseid, 'cmid' => $sectionid, 'name' => 'sectionelements']
        );

        if ($currentsectionoptions !== false) {
            $sectionoptions = json_decode($currentsectionoptions);
        } else {
            $sectionoptions = false;
        }

        return $sectionoptions;
    }

    /**
     * Generate a list with hassubname (bool) and section subname to be displayed on the section page.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param int|stdClass $course The course entry from DB
     * @return array hassubname, subname.
     */
    public static function get_subname($section, $course): array {
        return [self::hassubname($section, $course), self::subname($section, $course)];
    }

    /**
     * Does the section have a subname (true) or not (false)?
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param int|stdClass $course The course entry from DB
     * @return bool False if there's no subname.
     */
    public static function hassubname($section, $course): bool {
        $format = course_get_format($course);
        $sectionformatoptions = $format->get_format_options($section);
        // If the page is a lessons page
        // and it has a non-empty subname then return true.
        if (self::is_lesson() && !empty($sectionformatoptions["subname"])) {
            return true;
        }
        return false;
    }

    /**
     * Get the section subname to be displayed on the section page, or an empty string if there's none.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param int|stdClass $course The course entry from DB
     * @return string Subname string to output.
     */
    public static function subname($section, $course) {
        $format = course_get_format($course);
        $sectionformatoptions = $format->get_format_options($section);
        $subname = "";
        if (self::hassubname($section, $course)) {
            $subname = $sectionformatoptions["subname"];
        }
        return $subname;
    }

    /**
     * Restore section options once we switch back to format_compass.
     *
     * @param stdClass $course The course object
     *
     * @return array $courseformatoptions The list of course format options with old values set in.
     */
    public static function restore_all_section_options(stdClass $course): void {
        global $DB;

        // Get all the sections options from format_compass_options.
        $sectionsincourse = $DB->get_records(
            'format_compass_options',
            ['courseid' => $course->id, 'name' => 'sectionelements']
        );

        if (count($sectionsincourse) > 0) {

            // Restore all section options in course_format_options if they don't exist.
            foreach ($sectionsincourse as $k => $section) {
                $sectionoptions = json_decode($section->value);
                foreach ($sectionoptions as $soname => $sovalue) {

                    // Make sure the option doesn't before inserting it.
                    $currentoption = $DB->record_exists(
                        'course_format_options',
                        [
                            'courseid' => $course->id,
                            'format' => 'compass',
                            'sectionid' => $section->cmid,
                            'name' => $soname
                        ]
                    );

                    if ($currentoption === false) {
                        $option = new stdClass();
                        $option->courseid = $course->id;
                        $option->format = 'compass';
                        $option->sectionid = $section->cmid;
                        $option->name = $soname;
                        $option->value = $sovalue;
                        $option->timemodified = time();
                        $DB->insert_record('course_format_options', $option);
                    }
                }
            }
        }
    }

    /**
     * Determine if we are in an assessment section.
     *
     * @return boolean $isassessment true if we are in an assessment tab.
     */
    public static function is_assessment($sectionnumber=null) {
        global $FULLME, $PAGE;
        $fullmepageurl = new \moodle_url($FULLME);
        $isassessment = false;
        $tabname = $fullmepageurl->get_param('tab');
        if ($tabname == 'assessments') {
            $isassessment = true;
        }
        // If the tab is not an assessment check the section.
        if (!$isassessment) {
            if (is_null($sectionnumber)) {
                // Check if we are in a section within the assessments tab.
                $sectionnumber = $fullmepageurl->get_param('section');
                if (!is_null($sectionnumber)) {
                    $sectionnumber = intval($sectionnumber);
                }
            }
            if (!is_null($sectionnumber)) {
                $isassessment = self::section_in_assessments($sectionnumber);
            }
        }
        return $isassessment;
    }

    /**
     * Check if the section is in the assessments tab.
     *
     * @param int $sectionnumber The section number (not section id).
     *
     * @return bool True if the section is in the assessements tab.
     */
    public static function section_in_assessments(int $sectionnumber): bool {
        global $PAGE;
        $isassessment = false;
        $format = course_get_format($PAGE->course);
        $options = $format->get_format_options();
        $assessmentcutoff = (int) $options['assessmentscutoff'];
        if ($sectionnumber >= $assessmentcutoff) {
            $isassessment = true;
        }
        return $isassessment;
    }

    /**
     * Get the current tab.
     *
     *
     * @return string tab name.
     */
    public static function get_current_tab(): string | null {
        global $FULLME, $PAGE;

        // Set the default tab name.
        $tabname = 'coursehome';

        // Set the possible tab name to prevent rediecting
        // to an unexisting tab.
        $tabs = ['coursehome', 'lessons', 'assessments', 'calendar'];

        $fullmepageurl = new \moodle_url($FULLME);

        $paramtabname = $fullmepageurl->get_param('tab');
        if (in_array($paramtabname, $tabs)) {
            $tabname = $paramtabname;
        }

        // Check if we are in a section within the assessments tab.
        $sectionnumber = intval($fullmepageurl->get_param('section'));
        if (!is_null($sectionnumber)) {
            $isassessment = self::section_in_assessments($sectionnumber);
            if ($isassessment) {
                $tabname = 'assessments';
            }

            $islesson = self::section_in_lessons($sectionnumber);
            if ($islesson) {
                $tabname = 'lessons';
            }
        }

        return $tabname;
    }

    /**
     * Check if the section is in the lessons tab.
     *
     * @param int $sectionnumber The section number (not section id).
     *
     * @return bool True if the section is in the lessons tab.
     */
    public static function section_in_lessons(int $sectionnumber): bool {
        global $PAGE;
        $islesson = false;
        $format = course_get_format($PAGE->course);
        $options = $format->get_format_options();
        $assessmentcutoff = (int) $options['assessmentscutoff'];
        if ($sectionnumber > 0 && $sectionnumber < $assessmentcutoff) {
            $islesson = true;
        }
        return $islesson;
    }

    /**
     * Determine if we are in a lesson section.
     *
     * @return boolean $islesson true if we are in a lesson tab.
     */
    public static function is_lesson() {
        global $FULLME;
        $fullmepageurl = new \moodle_url($FULLME);
        $islesson = false;
        $tabname = $fullmepageurl->get_param('tab');
        if ($tabname == 'lessons') {
            $islesson = true;
        }
        if (!$islesson) {
            $sectionnumber = intval($fullmepageurl->get_param('section'));
            if (!is_null($sectionnumber)) {
                $islesson = self::section_in_lessons($sectionnumber);
            }
        }
        return $islesson;
    }

    /**
     * Determine if we are in a calendar.
     *
     * @return boolean $iscalendar true if we are in a calendar tab.
     */
    public static function is_calendar() {
        global $FULLME;
        $fullmepageurl = new \moodle_url($FULLME);
        $tabname = $fullmepageurl->get_param('tab');
        $iscalendar = false;
        if ($tabname == 'calendar') {
            $iscalendar = true;
        }
        return $iscalendar;
    }

    /**
     * Return the proper assessment renderer.
     *
     * @return string $renderer either allononepage or multiplepages.
     */
    public static function get_assessments_renderer($format) {
        $options = $format->get_format_options();
        $assessmenttablayout = $options['assessmentstablayout'];
        if ($assessmenttablayout == 'assessmentslayout_allonepage') {
            $renderer = 'format_compass\output\courseformat\content\assessmentsallonepage';
        } else {
            $renderer = 'format_compass\output\courseformat\content\assessmentsoneperpage';
        }
        return $renderer;
    }

    /**
     * Return the proper calendar renderer.
     *
     * @return string $renderer.
     */
    public static function get_calendar_renderer() {
        $renderer = 'format_compass\output\courseformat\content\calendar';
        return $renderer;
    }

    /**
     * Return the collapsed state of the section.
     *
     * @param section $section The section info object.
     * @param preferences $preferences An array of preferences indexed by sectionid.
     *
     * @return boolean $iscollasped true if the section is collapsed.
     */
    public static function get_collapsed_state($section, $preferences)  : bool {
        $iscollapsed = false;
        if (isset($preferences[$section->id])) {
            $sectionpreferences = $preferences[$section->id];
            if (!empty($sectionpreferences->contentcollapsed)) {
                $iscollapsed = true;
            }
        }
        return $iscollapsed;
    }
}

