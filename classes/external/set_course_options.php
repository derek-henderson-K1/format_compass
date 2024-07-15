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
 * Set a course options web service function.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_compass\external;

defined('MOODLE_INTERNAL') || die();

use context_course;
use external_function_parameters;
use external_single_structure;
use external_value;
use stdClass;

require_once($CFG->libdir.'/externallib.php');

/**
 * Set a cutoff position for a given course.
 */
trait set_course_options {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function set_course_options_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'options' => new \external_multiple_structure(new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Option name to set on activity'),
                'value' => new external_value(PARAM_RAW, 'Value for the option')
            ]))
        ]);
    }

    /**
     * Set course options web service function.
     *
     * @param int $courseid Course id.
     * @param array $options
     */
    public static function set_course_options(int $courseid, array $options) {
        global $PAGE;

        $context = context_course::instance($courseid);

        $PAGE->set_context($context);

        $params = self::validate_parameters(
            self::set_course_options_parameters(), [
                'courseid' => $courseid,
                'options' => $options
            ]);

        $course = get_course($params['courseid']);

        // We may require cap in the future.
        // require_capability('format/designer:changesectionoptions', $context);

        $optionstoupdate = new stdClass();
        $optionstoupdate->id = $course->id;

         // Save the new option value.
        foreach ($params['options'] as $option) {
            $optionstoupdate->{$option['name']} = $option['value'];
        }

        $courseformat = course_get_format($course);
        $courseformat->update_course_format_options(
            $optionstoupdate
        );
        $options = $courseformat->get_format_options();
        $return[] = array(
            'name' => 'course_format_options',
            'action' => 'put',
            'fields' => $options
        );

        return json_encode((array) $return);
    }

    /**
     * Return the updated course format options.
     *
     * @return external_description
     */
    public static function set_course_options_returns() {
        return new external_value(PARAM_RAW, 'The updated format options (JSON-encoded string)');
    }
}
