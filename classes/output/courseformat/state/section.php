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
use core_courseformat\output\local\state\section as section_base;
use format_compass\local\section_options;
use stdClass;

/**
 * Contains the ajax update section structure.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. {@link http://knowledgeone.ca}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {
    /**
     * Export this data so it can be used as state object in the course editor.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        $format = $this->format;
        $course = $format->get_course();
        $section = $this->section;
        $modinfo = $format->get_modinfo();
        $formatoptions = $format->get_format_options();
        $optionslist = $format::course_format_options_list(false);
        $sectionoptions = section_options::get_section_options($course->id, $section->id);

        $indexcollapsed = false;
        $contentcollapsed = false;
        $preferences = $format->get_sections_preferences();
        if (isset($preferences[$section->id])) {
            $sectionpreferences = $preferences[$section->id];
            if (!empty($sectionpreferences->contentcollapsed)) {
                $contentcollapsed = true;
            }
            if (!empty($sectionpreferences->indexcollapsed)) {
                $indexcollapsed = true;
            }
        }

        // Added subname so it appears in the reactive components.
        $data = (object)[
            'id' => $section->id,
            'section' => $section->section,
            'number' => $section->section,
            'title' => $format->get_section_name($section),
            'hassummary' => !empty($section->summary),
            'rawtitle' => $section->name,
            'cmlist' => [],
            'visible' => !empty($section->visible),
            'sectionurl' => course_get_url($course, $section->section)->out(),
            'current' => $format->is_section_current($section),
            'indexcollapsed' => $indexcollapsed,
            'contentcollapsed' => $contentcollapsed,
            'hasrestrictions' => $this->get_has_restrictions(),
            'subname' => $section->subname,
        ];

        if (isset($formatoptions['displaysectionweights']) && $formatoptions['displaysectionweights'] == $optionslist['displaysectionweights']['default']) {
            $data->sectionweight = (object)[
                'type_of_grade_weighting' => $sectionoptions->type_of_grade_weighting,
                'weighttype_bonus_value' => $sectionoptions->weighttype_bonus_value,
                'weighttype_singlemultiple' => $sectionoptions->weighttype_singlemultiple,
                'weighttype_singlevalue_weightvalue' => $sectionoptions->weighttype_singlevalue_weightvalue,
                'weighttype_multiplier_weightvalue' => $sectionoptions->weighttype_multiplier_weightvalue,
                'weighttype_multiplier_multipliervalue' => $sectionoptions->weighttype_multiplier_multipliervalue,
                'weighttype_multiplier_csvvalue' => $sectionoptions->weighttype_multiplier_csvvalue,
            ];
        }

        if (empty($modinfo->sections[$section->section])) {
            return $data;
        }

        foreach ($modinfo->sections[$section->section] as $modnumber) {
            $mod = $modinfo->cms[$modnumber];
            if ($section->uservisible && $mod->is_visible_on_course_page()) {
                $data->cmlist[] = $mod->id;
            }
        }

        return $data;
    }
}
