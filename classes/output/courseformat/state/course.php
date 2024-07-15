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

namespace format_compass\output\courseformat\state;

use core_courseformat\base as course_format;
use core_courseformat\output\local\state\course as course_base;
use stdClass;

/**
 * Contains the ajax update course structure.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. {@link http://knowledgeone.ca}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends course_base {

    /** @var course_format the course format class */
    protected $format;

    /**
     * Constructor.
     *
     * @param course_format $format the course format
     */
    public function __construct(course_format $format) {
        $this->format = $format;
    }

    /**
     * Export this data so it can be used as state object in the course editor.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        $data = parent::export_for_template($output);

        // Inject the course format options in the course put.
        $data->course_format_options = array(
            'name' => 'course_format_options',
            'action' => 'put',
            'fields' => $this->format->get_format_options()
        );

        return $data;
    }
}
