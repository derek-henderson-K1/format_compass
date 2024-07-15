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
 * Contains the default activities summary (used for singlesection format).
 *
 * @package   format_compass
 * @copyright 2023 KnowledgeOne
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace format_compass\output\courseformat\content\section;
use core_courseformat\output\local\content\section\cmsummary as cmsummary_base;
use stdClass;
use completion_info;

/**
 * Base class to render a course section summary.
 *
 * @package   format_compass
 * @copyright 2023 KnowledgeOne
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmsummary extends cmsummary_base {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {

        list($mods, $complete, $total, $showcompletion) = $this->calculate_section_stats();

        if (empty($mods)) {
            return new stdClass();
        }

        $format = $this->format;
        $showmods = false;
        if($format->show_editor()) {
            $showmods = true;
        }

        $data = (object)[
            'showcompletion' => $showcompletion,
            'total' => $total,
            'complete' => $complete,
            'showmods' => $showmods,
            'mods' => array_values($mods),
        ];

        if($data->total == $data->complete) {
            $data->modprogress = get_string('progressdone', 'format_compass', $data);
        } else {
            $data->modprogress = get_string('progresstotal', 'completion', $data);
        }

        return $data;
    }

    /**
     * Calculate the activities count of the current section.
     *
     * @return array with [[count by activity type], completed activities, total of activitites]
     */
    private function calculate_section_stats(): array {
        $format = $this->format;
        $course = $format->get_course();
        $section = $this->section;
        $modinfo = $format->get_modinfo();
        $completioninfo = new completion_info($course);

        $mods = [];
        $total = 0;
        $complete = 0;

        $cmids = $modinfo->sections[$section->section] ?? [];

        $cancomplete = isloggedin() && !isguestuser();
        $showcompletion = false;
        foreach ($cmids as $cmid) {
            $thismod = $modinfo->cms[$cmid];

            if ($thismod->uservisible) {
                if (isset($mods[$thismod->modname])) {
                    $mods[$thismod->modname]['name'] = $thismod->modplural;
                    $mods[$thismod->modname]['count']++;
                } else {
                    $mods[$thismod->modname]['name'] = $thismod->modfullname;
                    $mods[$thismod->modname]['count'] = 1;
                }
                if ($cancomplete && $completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $showcompletion = true;
                    $total++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                            $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $complete++;
                    }
                }
            }
        }

        return [$mods, $complete, $total, $showcompletion];
    }
}
