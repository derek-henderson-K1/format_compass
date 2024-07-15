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
 * Activity options related unit tests.
 *
 * @package    format_compass
 * @copyright  2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_options_test extends \advanced_testcase {

    /**
     * Test for options::save_option() to ensure that the activity options of the course are saved.
     * @covers ::save_option
     * @return void
     */
    public function test_activity_options() {
        $this->resetAfterTest(true);
        // Create a course using the compass format.
        $params = [];
        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $course = $compassgenerator->create_course($params);

        // Create a new "Text and media area" resource.
        $label = $this->getDataGenerator()->create_module('label', ['course' => $course->id, 'name' => 'My New Label',
        'intro' => '<p>This is the introduction to the label.</p>', 'introformat' => FORMAT_HTML]);
        // Get the course module of the created resource.
        $cm = get_coursemodule_from_instance('label', $label->id);
        // Initiate the value of the option to insert.
        $newactivityoption = new \stdClass();
        $newactivityoption->functionas = "textandmedia";
        // Call the function save_option() to save the option for the test label in Moodle's database.
        \format_compass\local\options::save_option($cm->id, $course->id, "activityelements", json_encode($newactivityoption));
        // Retrieve the value of the option from the database and decode it.
        $option = \format_compass\local\options::get_option($cm->id, 'activityelements');
        $decodedoption = json_decode($option);
        // Compare the retrieved value of the option from database with inserted one.
        $this->assertEquals($decodedoption->functionas, $newactivityoption->functionas);
    }
}
