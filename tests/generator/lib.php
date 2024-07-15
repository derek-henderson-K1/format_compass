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
 * format_compass data generator.
 *
 * @package    format_compass
 * @copyright  2024 Knowledgeone inc. <https://knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * format_compass generator class.
 *
 * @package    format_compass
 * @copyright  2024 Knowledgeone inc. <https://knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_compass_generator extends testing_module_generator {

    /**
     * Create a test course
     * @param array $options with keys
     *
     * @return stdClass course record
     */
    public function create_course($options) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/lib/phpunit/classes/advanced_testcase.php");
        require_once("$CFG->dirroot/course/lib.php");
        $record = [];
        if (!isset($options['fullname'])) {
            $record['fullname'] = 'Test course';
        } else {
            $record['fullname'] = $options['fullname'];
        }

        if (!isset($options['shortname'])) {
            $record['shortname'] = 'testcourse';
        } else {
            $record['shortname'] = $options['shortname'];
        }

        $record['category'] = "1";
        if (!isset($options['numsections'])) {
            $record['numsections'] = 5;
        } else {
            $record['numsections'] = $options['numsections'];
        }

        if (!isset($options['startdate'])) {
            $record['startdate'] = usergetmidnight(time());
        } else {
            $record['startdate'] = $options['startdate'];
        }

        $record['format'] = "topics";
        $course = advanced_testcase::getDataGenerator()->create_course($record);
        $data = new \stdClass();
        $data->id = $course->id;
        $data->format = 'compass';
        $compassconfigs = \format_compass::course_format_options_list(false);
        foreach ($compassconfigs as $compassconfig => $value) {
            if (isset($value['default'])) {
                $defaultvalue = $value['default'];
                if ($defaultvalue) {
                   // Ensures that nulls are not saved as default values. Can cause an issue with text editors.
                   $data->$compassconfig = $defaultvalue;
                }
            }
        }
        $data->hiddensections = '0';
        $data->coursedisplay = '1';
        $data->termforlessonstab = 'ecf_lessonstabname_lessons';
        $data->enableassessmentstab = '0';
        $data->termforassessmentstab = 'ecf_assessmentstabname_assessments';
        $data->assessmentscutoff = '0';
        update_course($data);
        // Save some section data.
        if (!isset($options['weightforassessment'])) {
            $weightforassessment = "6";
        } else {
            $weightforassessment = $options['weightforassessment'];
        }
        // Get the first section in the course.
        $cmid = $DB->get_field('course_sections', 'id', ['course' => $course->id, 'section' => 0]);
        $data = [
            'id' => $cmid, 'name' => 'sectionelements', 'weightforassessment' => $weightforassessment,
        ];
        // Call the function save_options() to save the option for the weightforassessment in Moodle's database.
        \format_compass\local\section_options::save_options($data, $course->id);
        // Reload course.
        $courserec = $DB->get_record('course', ['id' => $course->id]);
        return $courserec;
    }
}
