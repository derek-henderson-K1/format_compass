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
 * Contains the default section header format output class.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_compass\output\courseformat\content\section;
use core_courseformat\output\local\content\section\header as header_base;
use core\output\named_templatable;
use core_courseformat\base as course_format;
use core_courseformat\output\local\content\section;
use core_courseformat\output\local\courseformat_named_templatable;
use format_compass\local\section_options;
use renderable;
use section_info;
use stdClass;

/**
 * Base class to render a section header.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class header extends header_base {

    /** @var course_format the course format class */
    protected $format;

    /** @var section_info the course section class */
    protected $section;

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        $tabname = section_options::get_current_tab();

        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();
        $courseformatoptions = $format->get_format_options();
        $assessmentscutoff = $courseformatoptions['assessmentscutoff'];
        $enableassessmentstab = $courseformatoptions['enableassessmentstab'];

        $data = parent::export_for_template($output);
        // Get section title without the URL and edit in place button included.
        $data->title = $format->get_section_name($section, $course);
        // If multipage display the URL to the section.
        $coursedisplay = $format->get_course_display();
        $data->headerdisplaymultipage = false;
        if ($coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            $data->url = course_get_url($course, $section->section, ['navigation' => true]);
            $data->headerdisplaymultipage = true;
            $data->title = $format->get_section_name($section, $course);
        }
        // To hide the edit button from course Home.
        if ($section->section == 0) {
            $data->initialsection = true;
        }
        $singlesection = $format->get_section_number();
        $data->singlesection = $singlesection;

        if ($section->section >= $courseformatoptions["assessmentscutoff"]) {
            if ($courseformatoptions['assessmentstablayout'] == "assessmentslayout_oneperpage") {
                $data->url = course_get_url($course, $section->section, ['navigation' => true]);
                $data->headerdisplaymultipage = true;
                $data->title = $format->get_section_name($section, $course);
            } else {
                $data->headerdisplaymultipage = false;
                $data->title = $format->get_section_name($section, $course);
            }
        }
        list($data->hassubname, $data->subname) = section_options::get_subname($section, $course);
        return $data;
    }
}
