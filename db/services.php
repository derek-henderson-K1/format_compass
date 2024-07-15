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
 * External functions and service definitions.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'format_compass_set_activity_options' => [
        'classpath'     => '',
        'classname'     => 'format_compass\external\external',
        'methodname'    => 'set_activity_options',
        'description'   => 'Set activity options.',
        'type'          => 'write',
        'ajax'          => true
    ],
    'format_compass_set_course_options' => [
        'classpath'     => '',
        'classname'     => 'format_compass\external\external',
        'methodname'    => 'set_course_options',
        'description'   => 'Set course options.',
        'type'          => 'write',
        'ajax'          => true
    ],
    'format_compass_update_course' => [
        'classname'     => 'format_compass\external\update_course',
        'methodname'    => 'execute',
        'description'   => 'Update course contents.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'moodle/course:sectionvisibility, moodle/course:activityvisibility',
    ]
];
