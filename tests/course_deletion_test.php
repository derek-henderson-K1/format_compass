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

/**
 * Generator tests class for format_compass.
 *
 * @package    format_compass
 * @copyright  2024 Knowledgeone inc. <https://knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class course_deletion_test extends \advanced_testcase {
    /**
     * Test for delete_course () to ensure that the course has been deleted.
     * @covers ::delete_format_data ()
     * @return void
     */
    public function test_course_deletion() {
        global $DB;
        $this->resetAfterTest(true);
        $this->resetAfterTest(true);

        // Create a course using the compass format.
        $params = [];
        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $course = $compassgenerator->create_course($params);
        // Add an activity to the course.
        $label = $this->getDataGenerator()->create_module('label', ['course' => $course->id, 'name' => 'My New Label',
        'intro' => '<p>This is the introduction to the label.</p>', 'introformat' => FORMAT_HTML]);

        // Get the course module of the created resource.
        $cm = get_coursemodule_from_instance('label', $label->id);

        // Initiate the value of the option to insert.
        $newactivityoption = new \stdClass();
        $newactivityoption->functionas = "textandmedia";

        // Call the function insert_option() to save the option for the test label in Moodle's database.
        \format_compass\local\options::save_option($cm->id, $course->id, "activityelements", json_encode($newactivityoption));

        // Assert that course is present in course_format_options table.
        $compassformat = course_get_format($course);
        $formatcourse = $compassformat->get_course();
        $this->assertEquals($formatcourse->id, $course->id);

        // Assert that the activity option is present in course_format_options table.
        $option = \format_compass\local\options::get_option($cm->id, 'activityelements');
        $decodedoption = json_decode($option);
        $this->assertEquals($decodedoption->functionas, $newactivityoption->functionas);

        // Assert that the activity option is present in format_compass_options table.
        $compassoption = $DB->get_field(
            'format_compass_options',
            'value',
            ['courseid' => $course->id, 'name' => 'activityelements']
        );
        $decodedcompassoption = json_decode($compassoption);
        $this->assertEquals($decodedcompassoption->functionas, $newactivityoption->functionas);

        // Delete the course.
        delete_course($course->id, false);

        // Assert the course, section and activity option are deleted from course_format_options table.
        $formatcourse = $DB->record_exists('course_format_options',
        ['courseid' => $course->id]);
        $formatsection = $DB->record_exists('course_format_options',
        ['courseid' => $course->id, 'name' => 'sectionelements']);
        $formatoption = $DB->record_exists('course_format_options',
        ['courseid' => $course->id, 'name' => 'activityelements']);
        $this->assertFalse($formatcourse);
        $this->assertFalse($formatsection);
        $this->assertFalse($formatoption);

        // Assert the course, section and activity option are deleted from format_compass_options table.
        $compasscourse = $DB->record_exists('format_compass_options',
        ['courseid' => $course->id]);
        $compasssection = $DB->record_exists('format_compass_options',
        ['courseid' => $course->id, 'name' => 'sectionelements']);
        $compassoption = $DB->record_exists('format_compass_options',
        ['courseid' => $course->id, 'name' => 'activityelements']);
        $this->assertFalse($compasscourse);
        $this->assertFalse($compasssection);
        $this->assertFalse($compassoption);
    }
}
