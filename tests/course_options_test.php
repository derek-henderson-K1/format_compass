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

use format_compass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Course options related unit tests.
 *
 * @package    format_compass
 * @copyright  2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_options_test extends \advanced_testcase {

    /**
     * Test for course_options::save_options(array $data) to ensure that the course options are saved.
     * @covers ::save_options
     * @return void
     */
    public function test_course_options() {
        global $DB;
        $this->resetAfterTest(true);

        // Declare different formats to use for the test (topics and compass).
        $topicscourseformat = 'topics';
        $compasscourseformat = 'compass';
        // Create the course.
        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $params = [];
        $course = $compassgenerator->create_course($params);
        $data = new \stdClass();
        // Change the course format to topics course format.
        $data->id = $course->id;
        $data->format = $topicscourseformat;
        update_course($data);
        $course = $DB->get_record('course', ['id' => $course->id]);
        // Retrieve the options from course_format_options table and assert if it's deleted.
        $lessontabformat = $DB->record_exists('course_format_options',
        ['courseid' => $course->id, 'name' => 'termforlessonstab']);
        $enableassessmentformat = $DB->record_exists('course_format_options',
        ['courseid' => $course->id, 'name' => 'enableassessmentstab']);
        $assessmenttabformat = $DB->record_exists('course_format_options',
        ['courseid' => $course->id, 'name' => 'termforassessmentstab']);
        $assessmentscutoffformat = $DB->record_exists('course_format_options',
        ['courseid' => $course->id, 'name' => 'assessmentscutoff']);
        $this->assertFalse($lessontabformat);
        $this->assertFalse($enableassessmentformat);
        $this->assertFalse($assessmenttabformat);
        $this->assertFalse($assessmentscutoffformat);

        // Assert that the options are still in format_compass_options table.
        $compassoptions = $DB->get_field(
            'format_compass_options',
            'value',
            ['courseid' => $course->id, 'name' => 'courseelements']
        );

        $decodedoptions = json_decode($compassoptions);
        // Change the course format back to compass course format.
        $course->format = $compasscourseformat;
        update_course($course);
        // Get the compass course format object (defined in lib.php).
        $compassformat = course_get_format($course);
        // Update the course options with a new value and assert the new
        // values are set in the course format.
        $newformatoptions['id'] = $course->id;
        $newformatoptions['termforlessonstab'] = 'ecf_lessonstabname_lessons';
        $newformatoptions['enableassessmentstab'] = '1';
        $newformatoptions['termforassessmentstab'] = 'ecf_assessmentstabname_gradedactivities';
        $newformatoptions['assessmentscutoff'] = '1';
        $compassformat->update_course_format_options($newformatoptions);
        // Assert the course options are still in course_format_options table.
        $courseformatoptions = $compassformat->get_format_options();
        unset($newformatoptions['id']);
        foreach ($newformatoptions as $newformatoption => $value) {
            $this->assertEquals(
                $courseformatoptions[$newformatoption],
                $newformatoptions[$newformatoption]
            );
        }

        // Assert that the options are still in format_compass_options table.
        $compassoptions = $DB->get_field(
            'format_compass_options',
            'value',
            ['courseid' => $course->id, 'name' => 'courseelements']
        );
        $decodedoptions = json_decode($compassoptions);
        foreach ($newformatoptions as $newformatoption => $value) {
            $this->assertEquals(
                $decodedoptions->$newformatoption,
                $newformatoptions[$newformatoption]
            );
        }
    }
}
