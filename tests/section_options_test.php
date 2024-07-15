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

namespace format_compass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Section options related unit tests.
 *
 * @package    format_compass
 * @copyright  2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_options_test extends \advanced_testcase {

    /**
     * Test for section_options
     * @covers ::section_options
     *
     * @return void
     */
    public function test_section_options() {
        global $DB;
        $this->resetAfterTest(true);
         // Declare different formats to use for the test (topics and compass).
        $topicscourseformat = 'topics';
        $compasscourseformat = 'compass';

        $params = [];
        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $course = $compassgenerator->create_course($params);

        $weightforassessment = "6";
        // Retrieve the value of the option for the weightforassessment from the database and decode it.
        $option = \format_compass\local\options::get_option($course->id, 'sectionelements');
        $decodedsectionoption = json_decode($option);
        // Compare the retrieved value of the option for the weightforassessment from database with inserted one.
        $this->assertEquals($decodedsectionoption->weightforassessment, $weightforassessment);
        // Define a new value for weight for assessment.
        $newweight = "7";
        // Use the course id in data array as course module to use the first topic of the course.
        $newdata = [
            'id' => $course->id, 'name' => 'sectionelements', 'weightforassessment' => $newweight,
        ];
        // Call the function save_options() to save the new option value for the weightforassessment in Moodle's database.
        \format_compass\local\section_options::save_options($newdata, $course->id);
        // Change the course format to 'topics'.
        $course->format = $topicscourseformat;
        update_course($course);
        // Retrieve the weight from course_format_options table and assert if it's deleted.
        $weightcourseformat = $DB->get_record('course_format_options',
        ['courseid' => $course->id, 'sectionid' => $course->id, 'name' => 'weightforassessment']);
        $this->assertNotEquals($weightcourseformat, $newweight);
        // Retrieve the weight from format_compass_options table and assert if it's not deleted.
        $weightcompassformat = \format_compass\local\options::get_option($course->id, 'sectionelements');
        $decodedweightcompassformat = json_decode($weightcompassformat);
        $this->assertEquals($decodedweightcompassformat->weightforassessment, $newweight);
        // Change the course format back to 'compass'.
        $course->format = $compasscourseformat;
        $course->termforlessonstab = "lessons";
        $course->enableassessmentstab = "0";
        $course->termforassessmentstab = "assessments";
        update_course($course);
        // Retrieve the weight from course_format_options table and assert if it's inserted.
        $weightcourseformatcompass = $DB->get_record('course_format_options',
        ['courseid' => $course->id, 'sectionid' => $course->id, 'name' => 'weightforassessment']);
        $this->assertEquals($weightcourseformatcompass->value, $newweight);
        // Retrieve the weight from compass_format_options table and assert if it's present.
        $weightcompassformatcompass = \format_compass\local\options::get_option($course->id, 'sectionelements');
        $decodedweightcompassformatcompass = json_decode($weightcompassformatcompass);
        $this->assertEquals($decodedweightcompassformatcompass->weightforassessment, $newweight);
    }
}
