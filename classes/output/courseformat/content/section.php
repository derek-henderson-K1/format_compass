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
 * Contains the default section controls output class.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_compass\output\courseformat\content;

use core_courseformat\base as course_format;
use core_courseformat\output\local\content\section as section_base;
use stdClass;
use renderer_base;
use format_compass\local\section_options;

/**
 * Base class to render a course section.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {

    /** @var course_format the course format */
    protected $format;

    public function export_for_template(\renderer_base $output): stdClass {
        $format = $this->format;
        $formatoptions = $format->get_format_options();
        $data = parent::export_for_template($output);

        if ($formatoptions['bottomstripe'] == 'bottomstripeyes') {
            $data->bottomstripe = true;
        }

        if (!$this->format->get_section_number()) {
            $addsectionclass = $format->get_output_classname('content\\addsection');
            $addsection = new $addsectionclass($format);
            $data->numsections = $addsection->export_for_template($output);
            $data->insertafter = true;
            // Hide Expand/Collapse all on Course Home.
            $data->collapsemenu = false;
        }

        return $data;
    }


    /**
     * Add the section cm list to the data structure.
     *
     * @param stdClass $data the current cm data reference
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return bool if the cm has name data
     */
    protected function add_cm_data(stdClass &$data, renderer_base $output): bool {
        global $FULLME, $COURSE;
        $result = false;

        $fullmepageurl = new \moodle_url($FULLME);

        $section = $this->section;
        $format = $this->format;
        $formatoptions = $format->get_format_options();

        $showsummary = ($section->section != 0 &&
            $section->section != $format->get_section_number() &&
            $format->get_course_display() == COURSE_DISPLAY_MULTIPAGE
        );

        $showcmlist = $section->uservisible;
        // If lessons.
        if ($section->section < $formatoptions["assessmentscutoff"]) {
            if ($format->get_course_display() == COURSE_DISPLAY_MULTIPAGE && $section->section != $format->get_section_number()) {
                // Hide cmlist / add activity if you are in the all Lessons tab.
                $showcmlist = false;
            }
        } else if ($section->section >= $formatoptions["assessmentscutoff"]) {
            if ($formatoptions["assessmentstablayout"] == "assessmentslayout_oneperpage" &&
                $section->section != $format->get_section_number()) {
                // Hide cmlist / add activity if you are in the all assessments tab.
                $showcmlist = false;
            }
        }

        // Add activities summary if necessary.
        if ($showsummary) {
            $cmsummary = new $this->cmsummaryclass($format, $section);
            $data->cmsummary = $cmsummary->export_for_template($output);
            $data->onlysummary = true;
            $result = true;

            if (!$format->is_section_current($section)) {
                // In multipage, only the current section (and the section zero) has elements.
                $showcmlist = false;
            }
        }
        // Add the cm list.
        if ($showcmlist) {
            $cmlist = new $this->cmlistclass($format, $section);
            $cmlist = $cmlist->export_for_template($output);

            if (isset($formatoptions['activitylistlayout'])
                && str_contains($formatoptions['activitylistlayout'], 'activitylistlayout_simplebuttons')
                && $format->show_editor(['moodle/course:update']) == false) {
                // Check for forum modules.
                foreach ($cmlist->cms as $key => $cm) {
                    if ($cm->cmitem->module == 'forum') {

                        // Returns an array with the number of unread posts per thread.
                        $unread = forum_get_discussions_unread(
                            get_coursemodule_from_id($cm->cmitem->module, $cm->cmitem->id)
                        );

                        // Find the total number of unread posts.
                        $unreadtotal = 0;
                        foreach ($unread as $value) {
                            $unreadtotal += intval($value, 10);
                        }

                        // Do not display indicator if total is 0.
                        if($unreadtotal > 0) {
                            $cm->cmitem->unread = $unreadtotal;
                        }

                        // Do not display the full unread post message.
                        $cm->cmitem->cmformat->afterlink = '';
                    }
                }
            }

            $data->cmlist = $cmlist;
            $result = true;
        }
        return $result;
    }

    /**
     * Returns current section number
     */
    public function get_section_num() {
        return $this->section->section;
    }
}
