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
 * Set section options web service function.
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
use context_module;
use coding_exception;
use moodle_exception;
require_once($CFG->libdir.'/externallib.php');

/**
 * Set activity options web service function.
 */
trait set_activity_options {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function set_activity_options_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'options' => new \external_multiple_structure(new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Option name to set on activity'),
                'value' => new external_value(PARAM_RAW, 'Value for the option')
            ]))
        ]);
    }

    /**
     * Set activity options web service function.
     *
     * @param int $courseid Course id.
     * @param int $cmid Course module id.
     * @param array $options
     */
    public static function set_activity_options(int $courseid, int $cmid, array $options) {
        global $PAGE;
        $context = context_course::instance($courseid);
        $PAGE->set_context($context);
        $params = self::validate_parameters(self::set_activity_options_parameters(), [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'options' => $options
        ]);

        $course = get_course($params['courseid']);

        $format = course_get_format($course);

        // We may require cap in the future.
        // require_capability('format/designer:changesectionoptions', $context);

        // Save the new option value.
        foreach ($params['options'] as $option) {
            \format_compass\local\options::save_option(
                $cmid, $course->id, $option['name'], $option['value']
            );
        }

        // Confirm the new value for the activity option.
        $option = \format_compass\local\options::get_option($cmid, 'activityelements');
        $return = json_encode((object) [
            'cmid' => $cmid,
            'value' => $option->value
        ]);

        return $return;
    }

    /**
     * Return structure for edit_section()
     *
     * @since Moodle 3.3
     * @return external_description
     */
    public static function set_activity_options_returns() {
        return new external_value(PARAM_RAW, 'Additional data for javascript (JSON-encoded string)');
    }
}
