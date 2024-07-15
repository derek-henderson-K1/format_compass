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

class generator_test extends \advanced_testcase {
    /**
     * Test for generator_test::test_create_course to ensure that the course is created properly.
     * @covers ::create_course
     *
     * @return void
     */
    public function test_create_course() {
        global $DB;
        $this->resetAfterTest();
        $params = [];
        $startdate = 1705687152;
        // Need a default user for some functions to work properly.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $course = $compassgenerator->create_course($params);
        $format = course_get_format($course);
        $formatname = $format->get_format();

        // Check the default options. Default is 5 sections + announcements. Total of 6.
        $this->assertEquals(6, $DB->count_records('course_sections', ['course' => $course->id]));
        $this->assertEquals('compass', $formatname);

        // Create another course with some options.
        $params = ['shortname' => 'course2', 'fullname' => 'course compass', 'numsections' => '10',
                        'startdate' => $startdate, 'weightforassessment' => '15', ];
        $course2 = $compassgenerator->create_course($params);

        $this->assertEquals('course2', $course2->shortname);
        $this->assertEquals('course compass', $course2->fullname);
        $this->assertEquals(11, $DB->count_records('course_sections', ['course' => $course2->id]));
        $cmid = $DB->get_field('course_sections', 'id', ['course' => $course2->id, 'section' => 0]);
        $option = \format_compass\local\section_options::get_section_options($course2->id, $cmid);
        $decodedsectionoptions = json_decode(json_encode($option), true);
        // Compare the retrieved value of the option for the weightforassessment from database with inserted one.
        $this->assertEquals($decodedsectionoptions['weightforassessment'], 15);
        $courseformat = course_get_format($course2->id);
        // Compare the retrieved value of the option for the startdate from database with inserted one.
        $this->assertEquals($startdate, $courseformat->get_course()->startdate);
    }

}
