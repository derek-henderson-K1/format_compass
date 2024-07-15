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
use format_compass\local\section_options;
use stdClass;
use renderer_base;

/**
 * Base class to render a course section.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessmentsallonepage extends section_base {

    /** @var course_format the course format */
    protected $format;

    public function export_for_template(\renderer_base $output): stdClass {
        global $USER, $COURSE;
        $format = $this->format;

        $data = parent::export_for_template($output);
        $section = $this->section;
        $sectionformatoptions = $format->get_format_options($section);
        $options = $format->get_format_options();
        $optionslist = $format::course_format_options_list(false);
        $data->displayassessmentsweight = $options['displaysectionweights'] == $optionslist['displaysectionweights']['default'];

        $chartvalues = [];
        $chartcolors = [];
        $data->displaywheel = false;

        $modinfo = $format->get_modinfo();
        $sectionslist = $modinfo->get_section_info_all();

        $isassessment = section_options::section_in_assessments($section->section);

        if ($isassessment && $data->displayassessmentsweight) {

            foreach ($sectionslist as $forsection) {
                // Display chart if user visible or if format hidden section setting shows unavailable sections.
                if ($forsection->uservisible == true || $options['hiddensections'] == 0) {
                    $so = section_options::get_section_options($COURSE->id, $forsection->id);
                    $chartpercent = '';
                    $chartpercentbreakdown = '';

                    if (isset($so->type_of_grade_weighting) && $so->type_of_grade_weighting == 'weighttype_weighted') {
                        // Weighted.
                        if ($so->weighttype_singlemultiple == 'weighttype_singlemultiple_single') {

                            $chartpercent = get_string('assessments_chart_percent', 'format_compass', $so->weighttype_singlevalue_weightvalue);
                            $chartvalues[$forsection->id] = $so->weighttype_singlevalue_weightvalue;

                        } else if ($so->weighttype_singlemultiple == 'weighttype_singlemultiple_multiplier') {

                            $percentagetotal = $so->weighttype_multiplier_weightvalue * $so->weighttype_multiplier_multipliervalue;
                            $chartpercent = get_string('assessments_chart_percent', 'format_compass', $percentagetotal);
                            $chartpercentbreakdown = get_string('assessments_chart_multiplier', 'format_compass', $so);
                            $chartvalues[$forsection->id] = $percentagetotal;

                        } else if ($so->weighttype_singlemultiple == 'weighttype_singlemultiple_csv') {

                            $totalweight = 0;
                            $csvvalues = explode(',', str_replace(' ', '', $so->weighttype_multiplier_csvvalue));

                            foreach ($csvvalues as $value) {
                                $totalweight += intval($value);
                            }

                            $chartpercent = get_string('assessments_chart_percent', 'format_compass', $totalweight);
                            $chartpercentbreakdown = '(' . str_replace(',', '+', str_replace(' ', '', $so->weighttype_multiplier_csvvalue)) . ')';
                            $chartvalues[$forsection->id] = $totalweight;

                        }

                        $chartcolors[$forsection->id] = 'color_inactive';

                        // Display values for the current section you are in.
                        if ($forsection->id == $section->id) {

                            $data->chartpercent = $chartpercent;
                            $data->chartpercentbreakdown = $chartpercentbreakdown;
                            $data->displaywheel = true;
                            $chartcolors[$section->id] = 'color_active';

                        }

                    } else if (isset($so->type_of_grade_weighting) && $so->type_of_grade_weighting == 'weighttype_bonus') {
                        // Bonus.
                        if ($forsection->id == $section->id) {
                            $data->displaybonus = true;
                            $data->bonuspercent = get_string('assessments_chart_percent', 'format_compass', $so->weighttype_bonus_value);
                        }
                    } else {
                        // Ungraded.
                        if ($forsection->id == $section->id) {
                            $data->displayungraded = true;
                        }
                    }
                }
            }

            // Check if total is lower than 100, add a value to fill the gap.
            $valuestotal = 0;
            $chartkeys = [];

            foreach ($chartvalues as $key => $value) {
                $valuestotal += intval($value);
                $chartkeys[] = $key;
            }

            if ($valuestotal < 100) {
                $chartvalues[] = 100 - $valuestotal;
                $chartkeys[] = 'fill';
                $chartcolors[] = 'color_disabled';
            }

            // Need to send the values as a string to the chart javascript.
            $data->chartvalues = implode(',', $chartvalues);
            $data->chartkeys = implode(',', $chartkeys);
            $data->chartcolors = implode(',', $chartcolors);
            $data->countsfor = get_string('assessments_counts_for', 'format_compass');
        }

        // If text is not a certain length, do not display expand for more.
        $data->summaryexpandcollapse = true;
        if (isset($data->summary->summarytext) && $data->summary->summarytext == '') {
            $data->nosummary = true;
        }

        if (!$this->format->get_section_number()) {
            $addsectionclass = $format->get_output_classname('content\\addsection');
            $addsection = new $addsectionclass($format);
            $data->numsections = $addsection->export_for_template($output);
            $data->insertafter = true;
            $data->contentcollapsed = false;
            $preferences = $format->get_sections_preferences();
            $visits = section_options::check_assessmenttab_viewed($USER->id, $COURSE->id);

            // The log event is triggered but the record has not yet been saved in the DB.
            if ($visits == 0) {
                if ($options['initialcollapsestate'] != 'initialcollapsestate_expand') {
                    $data->contentcollapsed = true;
                }
            } else {
                if (isset($preferences[$section->id])) {
                    $sectionpreferences = $preferences[$section->id];
                    if (!empty($sectionpreferences->contentcollapsed)) {
                        $data->contentcollapsed = true;
                    }
                }
            }
            $data->contentcollapsed = section_options::get_collapsed_state($section, $preferences);
            if ($data->contentcollapsed) {
                $data->seemore = get_string('assessments_seemore', 'format_compass');
            }
        }

        return $data;
    }

    /**
     * Always display CM, no summary
     *
     * @param stdClass $data the current cm data reference
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return bool if the cm has name data
     */
    protected function add_cm_data(stdClass &$data, renderer_base $output): bool {
        $section = $this->section;
        $format = $this->format;

        $cmlist = new $this->cmlistclass($format, $section);
        $data->cmlist = $cmlist->export_for_template($output);

        return true;
    }

    /**
     * Returns current section number
     */
    public function get_section_num() {
        return $this->section->section;
    }

     /**
     * Check to see if the assessmenttab has been viewed.
     *
     * @param int $userid the id of the current user
     * @param int $courseid  the id of the current course
     * @return int assessmenttab  return 1 if its first time on the tab, otherwise 2.
     */
    public static function check_assessmenttab_viewed(int $userid, int $courseid) : int {
        global $DB;
        static $assessmenttab = 0;
        if ($assessmenttab > 0) {
            return $assessmenttab;
        }
        if ($assessmenttab == 0 ) {
            // See if the record is in the log store;
            // Check to see if this is the first visit on this tab in the course..
            // The logstore will be blank if this is the first time the user this tab in this course..
            // Otherwise it will have a record.
            $params = [
                'userid' => $userid,
                'courseid' => $courseid,
            ];
            $selectvisit = 'SELECT *
                              FROM {logstore_standard_log}
                              WHERE eventname LIKE "%course_viewed%"
                                AND userid = :userid
                                AND courseid = :courseid
                                AND JSON_EXTRACT(other, "$.tab") = "assessments" ';
            $firstvisitrecord = $DB->record_exists_sql($selectvisit, $params);
            if ($firstvisitrecord) {
                // This means we the user has been on the tab once before.
                $assessmenttab = 2;
            } else {
                // Add it to the log store so we don't use the settings again.
                $context = \context_course::instance($courseid);
                $eventdata = array('context' => $context);
                $eventdata['other']['tab'] = 'assessments';
                $event = \core\event\course_viewed::create($eventdata);
                $event->trigger();
                $assessmenttab = 1;
            }
        }
        return $assessmenttab;
    }
}
