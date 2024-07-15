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
 * Contains the default content output class.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_compass\output\courseformat;
use core_courseformat\output\local\content as content_base;
use core_courseformat\base as course_format;
use core_courseformat\output\local\content\section;
use tool_customlang\local\mlang\langstring;
use \format_compass\local\course_options;
use \format_compass\local\section_options;
use course_modinfo;

/**
 * Base class to render a course content.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * @var bool Topic format has add section after each topic.
     *
     * The responsible for the buttons is core_courseformat\output\local\content\section.
     */
    protected $hasaddsection = false;
    /**
     * Constructor.
     *
     * Based on /course/format/classes/output/local/content,php
     *
     * @param course_format $format the course format
     */
    public function __construct(course_format $format) {
        global $PAGE;

        $this->format = $format;

        $tabname = section_options::get_current_tab();
        switch ($tabname) {
            case 'assessments':
                $sectionclass = section_options::get_assessments_renderer($format);
                break;
            case 'calendar':
                $sectionclass = section_options::get_calendar_renderer($format);
                break;
            default:
                $sectionclass = $format->get_output_classname('content\\section');
        }
        $this->sectionclass = $sectionclass;
        $this->addsectionclass = $format->get_output_classname('content\\addsection');
        $this->sectionnavigationclass = $format->get_output_classname('content\\sectionnavigation');
        $this->sectionselectorclass = $format->get_output_classname('content\\sectionselector');
    }

    /**
     * Returns the output class template path.
     *
     * This method redirects the default template when the course section is rendered.
     *
     * @param \renderer_base $renderer
     *
     * @return string Templates path for compass format.
     */
    public function get_template_name(\renderer_base $renderer): string {

        // Global custom template path.
        $tplpath = "format_compass/local/%s/%s/content";

        // Make sure we are in the assessments tab.
        $tabname = section_options::get_current_tab();
        switch ($tabname) {
            case 'assessments':
                $courseoptions = $this->format->get_format_options();
                $assessmenttablayout = $courseoptions['assessmentstablayout'];

                // Return the assessments layout according to the chosen
                // course option or return the default layout.
                if ($assessmenttablayout == 'assessmentslayout_allonepage') {
                    $layoutname = 'allonepage';
                } else if ($assessmenttablayout == 'assessmentslayout_oneperpage') {
                    $layoutname = 'oneperpage';
                }

                $sectiontemplate = sprintf($tplpath, 'assessments', $layoutname);

                $tplexists = $renderer->template_exists($sectiontemplate);

                // If the layout doesn't exists, return the default one.
                if (!$tplexists) {
                    // TODO : Use the default from format_compass::course_format_options_list().
                    $sectiontemplate = sprintf($tplpath, 'assessments', 'allonepage');
                }
                break;
            case 'calendar':
                $sectiontemplate = sprintf($tplpath, 'calendar', 'oneperpage');
                $tplexists = $renderer->template_exists($sectiontemplate);
                break;
            default:
                // Return the Lessons templates.
                $sectiontemplate = 'format_compass/local/content';
        }

        return $sectiontemplate;
    }

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        global $COURSE, $FULLME, $PAGE;

        // Get the parent output (basically the whole page content).
        $data = parent::export_for_template($output);

        // Get the current page URL as moodle_url object.
        if ($PAGE->has_set_url()) {
            $currentpageurl = $PAGE->url;
        } else {
            $currentpageurl = new \moodle_url($FULLME);
        }

        $format = course_get_format($PAGE->course);
        $options = $format->get_format_options();
        $enableassessmentstab = $options['enableassessmentstab'];
        $enablecalendartab = $options['enablecalendartab'];
        $tabname = section_options::get_current_tab();
        $assessmentcutoff = (int) $options['assessmentscutoff'];
        $assessmenttablayout = $options['assessmentstablayout'];
        $target = 'lessons';

        $modinfo = $this->format->get_modinfo();
        $sectionslist = $this->get_sections_to_display($modinfo);

        // Show text areas in assessments above and/or below the sections.
        foreach(['above', 'below'] as $editor) {
            $data->{'textarea' . $editor} = '';
            if (
                $options['displaytextareas'] == 'displaytextareadisplayaboveandbelow' ||
                $options['displaytextareas'] == 'displaytextareadisplay' . $editor
            ) {
                // Format the editor content into browser compatible HTML code.
                // 1 - Write the file URL to serve files from pluginfile.php.
                // 2 - Use format_text() to apply Moodle formating options and filters.
                $data->{'textarea' . $editor} = course_options::format_editor_text(
                    $COURSE, $options['textarea' . $editor], 'textarea' . $editor
                );
            }
        }

        // Initialize the data for the add section as the first element of the page,
        // ID by default is the Home section ID.
        $data->addsectionbefore = (object) array(
            'showaddsection' => true,
            'emptysection' => false,
            "insertafter" => true,
            "id" => $data->initialsection->id,
            "target" => $target,
            "addsections" => (object) array (
                "url" => "#",
                "title" => "Add section",
            )
        );
        if ($currentpageurl->get_path() == '/course/view.php') {
            // Course home tab.
            if ($tabname == 'coursehome') {
                $data->activitylistlayout = $options['activitylistlayout'];
                $bottomcourseimagestripe = $options['displaybottomstripecourseimage'];
                $data->bottomcourseimagestripe = false;
                if ($bottomcourseimagestripe == 'displaybottomstripecourseimageyes') {
                    $data->bottomcourseimagestripe = true;
                }
            }
            // Lessons tab, hide course home.
            if ($tabname == 'lessons') {
                $data->initialsection = '';

                if ($data->sections == null || count($data->sections) == 0) {
                    $data->addsectionbefore->emptysection = true;
                }

            } else if ($tabname == 'assessments' && $enableassessmentstab) {
                // If the assessment tab is enabled and we are in the assessments tab, hide course home.
                $data->initialsection = '';
                $data->addsectionbefore->target = 'assessments';
                // This condition is there to avoid any crash incase assessment cutoff is set to 0.
                if ($assessmentcutoff > 0) {
                    if ($assessmentcutoff - 2 >= 0) {
                        // Set ID to to the previous element.
                        $data->addsectionbefore->id = $sectionslist[$assessmentcutoff-1]->id;
                    }

                    if (count($data->sections) > 0) {
                        $data->addsectionbefore->emptysection = false;
                    } else {
                        $data->addsectionbefore->emptysection = true;
                    }

                    // Make change here for multiple sections to be displayed.
                    $data->assessmentslayout_allonepage = true;
                    if ($assessmenttablayout == 'assessmentslayout_oneperpage') {
                        $data->assessmentslayout_allonepage = false;
                        $data->addsectionbefore->emptysection = false;
                    }
                    if ($data->assessmentslayout_allonepage) {
                        $data->collapsemenu = true;
                    }

                } else {
                    $data->sections = '';
                }
            } else if ($tabname == 'assessments' && !$enableassessmentstab) {
                // If the assessment tab is disabled and it is a assessments tab, then redirect to Course Home page.
                $fullmepageurl = new \moodle_url($FULLME);
                $fullmepageurl->remove_params(['tab']);
                $redirecturl = $fullmepageurl->out();
                echo "<style> body {display:none !important;}</style><meta http-equiv='refresh' content='0;url=" . $redirecturl . "'>";
                echo $output->footer();
                exit();
            } else if ($tabname == 'calendar' && $enablecalendartab) {
                $categoryid = optional_param('category', null, PARAM_INT);
                $courseid = optional_param('course', $COURSE->id, PARAM_INT);
                $view = optional_param('view', 'month', PARAM_ALPHA);
                $time = optional_param('time', 0, PARAM_INT);
                $lookahead = optional_param('lookahead', null, PARAM_INT);
                $calendar = \calendar_information::create($time, $courseid, $categoryid);
                $renderer = $PAGE->get_renderer('core_calendar');
                $html = $renderer->start_layout();
                list($caldata, $template) = calendar_get_view($calendar, $view, true, false, $lookahead);
                $html .= $renderer->render_from_template($template, $caldata);
                $options = [
                    'showfullcalendarlink' => true
                ];
                list($caldata, $template) = calendar_get_footer_options($calendar, $options);
                $html .= $renderer->render_from_template($template, $caldata);
                $html .= $renderer->complete_layout();
                $data->html = $html;
            } else if ($tabname == 'calendar' && !$enablecalendartab) {
                // If the calendar tab is disabled and it is a calendar tab, then redirect to Course Home page.
                $fullmepageurl = new \moodle_url($FULLME);
                $fullmepageurl->remove_params(['tab']);
                $redirecturl = $fullmepageurl->out();
                echo "<style> body {display:none !important;}</style><meta http-equiv='refresh' content='0;url=" . $redirecturl . "'>";
                echo $output->footer();
                exit();
            } else {
                $data->sections = '';
                $data->addsectionbefore->showaddsection = false;
                $data->initialsection->insertafter = 0;
            }

            if ($format->show_editor(['moodle/course:update']) == false) {
                $data->addsectionbefore->showaddsection = false;
            }
        }

        // Define whether or not we are in a section.
        // null will be returned if the section param
        // is not present in the current URL.
        $hassection = $currentpageurl->get_param('section');

        if (!is_null($hassection)) {
            // do not display section 0 with the sections.
            $data->initialsection = '';

            // Get the current section index and compare it with the assessmentscutoff
            // to know in which tab the user was in.
            $sectionnumber = $this->format->get_section_number();
            if ($options["enableassessmentstab"] == false || $sectionnumber < $options['assessmentscutoff']) {
                $tab = 'lessons';
                $tab_label = new \lang_string(
                    $options['termforlessonstab'], 'format_compass'
                );
            } else {
                $tab = 'assessments';
                $tab_label = new \lang_string(
                    $options['termforassessmentstab'], 'format_compass'
                );
            }

            // Set the URL for the Back button.
            $url = new \moodle_url('/course/view.php', ['id' => $COURSE->id, 'tab' => $tab]);

            // Set the button title.
            $title = get_string('backtoallsections', 'format_compass', $tab_label);

            // Set the button data in the template.
            $data->backtoallsections = ['url' => $url, 'title' => $title];
        }

        return $data;
    }

    /**
     * Export sections array data.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_sections(\renderer_base $output): array {

        $format = $this->format;
        $course = $format->get_course();
        $modinfo = $this->format->get_modinfo();

        // Generate section list.
        $sections = [];
        $stealthsections = [];
        $numsections = $format->get_last_section_number();
        $sectionslist = $this->get_sections_to_display($modinfo);

        $options = $format->get_format_options();
        $enableassessmentstab = $options['enableassessmentstab'];
        $tabname = section_options::get_current_tab();
        $assessmentcutoff = (int) $options['assessmentscutoff'];

        // Spliting the sections into lesson & assessment tabs.
        if ($tabname == "lessons" && $enableassessmentstab) {
            $sectionstodisplay = array_slice($sectionslist, 0, $assessmentcutoff);
        } else if ($tabname == "assessments" && $enableassessmentstab) {
            $numofelements = count($sectionslist) - $assessmentcutoff + 1;
            // Adding the initialsection to the array because the first element will be array_shift in the export_for_template function.
            $sectionstodisplay = array_merge(array_slice($sectionslist,0,1), array_slice($sectionslist, $assessmentcutoff, $numofelements));
        } else {
            // Not using tabs.
            $sectionstodisplay = $sectionslist;
        }

        foreach ($sectionstodisplay as $sectionnum => $thissection) {
            // The course/view.php check the section existence but the output can be called
            // from other parts so we need to check it.
            if (!$thissection) {
                throw new \moodle_exception('unknowncoursesection', 'error', course_get_url($course),
                    format_string($course->fullname));
            }

            $section = new $this->sectionclass($format, $thissection);

            if ($sectionnum > $numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                if (!empty($modinfo->sections[$sectionnum])) {
                    $stealthsections[] = $section->export_for_template($output);
                }
                continue;
            }

            if (!$format->is_section_visible($thissection)) {
                continue;
            }

            $sections[] = $section->export_for_template($output);
        }
        if (!empty($stealthsections)) {
            $sections = array_merge($sections, $stealthsections);
        }
        return $sections;
    }

    /**
     * Return an array of sections to display.
     *
     * This method is used to differentiate between display a specific section
     * or a list of them.
     *
     * @param course_modinfo $modinfo the current course modinfo object
     * @return section_info[] an array of section_info to display
     */
    private function get_sections_to_display(course_modinfo $modinfo): array {
        $singlesection = $this->format->get_section_number();
        if ($singlesection) {
            return [
                $modinfo->get_section_info(0),
                $modinfo->get_section_info($singlesection),
            ];
        }

        return $modinfo->get_section_info_all();
    }
}
