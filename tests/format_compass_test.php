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
 * Compass course format related unit tests. These are copied from topics format.
 *
 * @package    format_compass
 * @copyright  2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_compass_test extends \advanced_testcase {

    /**
     * Tests for format_compass::get_section_name method with default section names.
     * @covers ::section_name
     *
     * @return void
     */
    public function test_get_section_name() {
        global $DB;
        $this->resetAfterTest(true);

        // Create a course using the compass format.
        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $params = [];

        $course = $compassgenerator->create_course($params);
        // Get section names for course.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);

        // Test get_section_name with default section names.
        $courseformat = course_get_format($course);
        foreach ($coursesections as $section) {
            // Assert that with unmodified section names, get_section_name returns the same result as get_default_section_name.
            $this->assertEquals($courseformat->get_default_section_name($section), $courseformat->get_section_name($section));
        }
    }

    /**
     * Tests for format_compass::get_section_name method with modified section names.
     * @covers ::custom_section_name
     *
     * @return void
     */
    public function test_get_section_name_customised() {
        global $DB;
        $this->resetAfterTest(true);

        // Create a course using the compass format.
        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $params = [];

        $course = $compassgenerator->create_course($params);
        // Get section names for course.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);
        // Modify section names.
        $customname = "Custom Section";
        foreach ($coursesections as $section) {
            $data = new \stdClass();
            $data->name = "$customname $section->section";
            $data->visible = true;
            course_update_section($section->course, $section, $data);
        }
        $courserec = $DB->get_record('course', ['id' => $course->id]);

        $courseformat = course_get_format($courserec);
        $coursesections = $DB->get_records('course_sections', ['course' => $courserec->id]);
        foreach ($coursesections as $section) {
            // Assert that with modified section names, get_section_name returns the modified section name.
            $this->assertEquals($section->name, $courseformat->get_section_name($section));
        }
    }

    /**
     * Tests for format_compass::get_default_section_name.
     * @covers ::default_section_name
     *
     * @return void
     */
    public function test_get_default_section_name() {
        global $DB;
        $this->resetAfterTest(true);

        // Create a course using the compass format.
        $params = [];

        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $course = $compassgenerator->create_course($params);
        // Get section names for course.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);

        // Test get_default_section_name with default section names.
        $courserec = $DB->get_record('course', ['id' => $course->id]);

        $courseformat = course_get_format($courserec);
        foreach ($coursesections as $section) {
            if ($section->section == 0) {
                $sectionname = get_string('section0name', 'format_compass');
                $this->assertEquals($sectionname, $courseformat->get_default_section_name($section));
            } else {
                $sectionname = get_string('sectionname', 'format_compass') . ' ' . $section->section;
                $this->assertEquals($sectionname, $courseformat->get_default_section_name($section));
            }
        }
    }

    /**
     * Test web service updating section name.
     * @covers ::web service update
     *
     * @return void
     */
    public function test_update_inplace_editable() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/lib/external/externallib.php');

        $this->resetAfterTest();
        // Create a course using the compass format.
        $params = [];

        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $course = $compassgenerator->create_course($params);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 2]);

        // Call webservice without necessary permissions.
        try {
            \core_external::update_inplace_editable('format_compass', 'sectionname', $section->id, 'New section name');
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals('Course or activity not accessible. (Not enrolled)',
                    $e->getMessage());
        }

        // Change to teacher and make sure that section name can be updated using web service update_inplace_editable().
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole->id);

        $res = \core_external::update_inplace_editable('format_compass', 'sectionname', $section->id, 'New section name');
        $res = \external_api::clean_returnvalue(\core_external::update_inplace_editable_returns(), $res);
        $this->assertEquals('New section name', $res['value']);
        $this->assertEquals('New section name', $DB->get_field('course_sections', 'name', ['id' => $section->id]));
    }

    /**
     * Test callback updating section name.
     * @covers ::update section name.
     *
     * @return void
     */
    public function test_inplace_editable() {
        global $DB, $PAGE;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $params = [];

        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $course = $compassgenerator->create_course($params);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole->id);
        $this->setUser($user);

        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 2]);

        // Call callback format_topics_inplace_editable() directly.
        $tmpl = component_callback('format_compass', 'inplace_editable', ['sectionname', $section->id, 'Rename me again']);
        $this->assertInstanceOf('core\output\inplace_editable', $tmpl);
        $res = $tmpl->export_for_template($PAGE->get_renderer('core'));
        $this->assertEquals('Rename me again', $res['value']);
        $this->assertEquals('Rename me again', $DB->get_field('course_sections', 'name', ['id' => $section->id]));

        // Try updating using callback from mismatching course format.
        try {
            component_callback('format_weeks', 'inplace_editable', ['sectionname', $section->id, 'New name']);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals(1, preg_match('/^Can\'t find data record in database/', $e->getMessage()));
        }
    }

    /**
     * Test for get_view_url() to ensure that the url is only given for the correct cases.
     * @covers :: view_url.
     *
     * @return void
     */
    public function test_get_view_url() {
        global $CFG;
        $this->resetAfterTest();

        $params = [];
        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $course1 = $compassgenerator->create_course($params);
        $format = course_get_format($course1);

        // In page.
        $CFG->linkcoursesections = 0;
        $this->assertNotEmpty($format->get_view_url(null));
        $this->assertNotEmpty($format->get_view_url(0));
        $this->assertNotEmpty($format->get_view_url(1));
        $CFG->linkcoursesections = 1;
        $this->assertNotEmpty($format->get_view_url(null));
        $this->assertNotEmpty($format->get_view_url(0));
        $this->assertNotEmpty($format->get_view_url(1));

        // Navigation.
        $CFG->linkcoursesections = 0;
        $this->assertNull($format->get_view_url(0, ['navigation' => 1]));
        $CFG->linkcoursesections = 1;
        $this->assertNotEmpty($format->get_view_url(1, ['navigation' => 1]));
        $this->assertNotEmpty($format->get_view_url(0, ['navigation' => 1]));
    }

    /**
     * Test get_default_course_enddate.
     * @covers ::course enddate.
     *
     * @return void
     */
    public function test_default_course_enddate() {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        require_once($CFG->dirroot . '/course/tests/fixtures/testable_course_edit_form.php');

        $this->setTimezone('UTC');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $params = ['numsections' => 5, 'startdate' => 1445644800];
        $compassgenerator = $this->getDataGenerator()->get_plugin_generator('format_compass');
        $course = $compassgenerator->create_course($params);
        $category = $DB->get_record('course_categories', ['id' => $course->category]);

        $args = [
            'course' => $course,
            'category' => $category,
            'editoroptions' => [
                'context' => \context_course::instance($course->id),
                'subdirs' => 0,
            ],
            'returnto' => new \moodle_url('/'),
            'returnurl' => new \moodle_url('/'),
        ];

        $courseform = new \testable_course_edit_form(null, $args);
        $courseform->definition_after_data();

        $enddate = $params['startdate'] + get_config('moodlecourse', 'courseduration');

        $weeksformat = course_get_format($course->id);
        $this->assertEquals($enddate, $weeksformat->get_default_course_enddate($courseform->get_quick_form()));

    }
}
