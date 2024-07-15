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
 * Compass additional options for activities.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_compass\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');

use external_api;

/**
 * Web service and ajax functions.
 *
 * Each external function is implemented in its own trait. This class
 * aggregates them all.
 */
class external extends external_api {

    // Save an activity option for a given activity.
    use set_activity_options;

    // Save an course format option for a given course.
    use set_course_options;
}
