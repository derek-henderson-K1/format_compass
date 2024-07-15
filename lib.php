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
 * This file contains main class for Topics course format.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot. '/course/format/lib.php');

use core\output\inplace_editable;
// Class to manage additional course, section, activity additional options.
use format_compass\local\options;
use format_compass\local\course_options;
use format_compass\local\image_options;
use format_compass\local\section_options;

/**
 * Main class for the Topics course format.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_compass extends core_courseformat\base {

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Return true if the course format is compatible with
     * the course index drawer. Note that classic based themes
     * are not compatible with the course index.
     */
    public function uses_course_index() {
        return true;
    }

    /**
     * If the format uses the legacy activity indentation.
     */
    public function uses_indentation(): bool {
        return false;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #").
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                ['context' => context_course::instance($this->courseid)]);
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the default section name for the topics course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of course_format::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_compass');
        } else {
            // Use course_format::get_default_section_name implementation which
            // will display the section name in "Topic n" format.
            return parent::get_default_section_name($section);
        }
    }

    /**
     * Generate the title for this section page.
     *
     * @return string the page title
     */
    public function page_title(): string {
        return get_string('topicoutline');
    }

    /**
     * The URL to use for the specified course (with section).
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = []) {
        global $CFG;
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', ['id' => $course->id]);
        $courseformat = course_get_format($course->id);
        $courseformatoptions = $courseformat->get_format_options();
        $enableassessmentstab = $courseformatoptions['enableassessmentstab'];
        $assessmentstablayout = $courseformatoptions['assessmentstablayout'];
        $assessmentscutoff = $courseformatoptions['assessmentscutoff'];

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            // If Lessons.
            if ($enableassessmentstab && $sectionno < $assessmentscutoff || !$enableassessmentstab) {
                if ($sr !== null) {
                    if ($sr) {
                        $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
                        $sectionno = $sr;
                    } else {
                        $usercoursedisplay = COURSE_DISPLAY_SINGLEPAGE;
                    }
                } else {
                    $usercoursedisplay = $course->coursedisplay ?? COURSE_DISPLAY_SINGLEPAGE;
                }
                if ($sectionno != 0 && $usercoursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                    $url->param('section', $sectionno);
                } else {
                    if (empty($CFG->linkcoursesections) && !empty($options['navigation'])) {
                        return null;
                    }
                    $url->set_anchor('section-'.$sectionno);
                }
            } else {
                // If assessments.
                if ($assessmentstablayout == 'assessmentslayout_oneperpage') {
                    $url->param('section', $sectionno);
                } else {
                    $url->param('tab', 'assessments');
                    $url->set_anchor('section-'.$sectionno);
                }
            }
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Since Moodle 4.0 the course is rendered using reactive UI components.
     * This kind of component will be the only standard in Moodle 4.3+ but,
     * until then, formats can override this method to specify if they want
     * to use the previous UI elements or the new ones.
     */
    public function supports_components() {
        return true;
    }

    /**
     * Loads all of the course sections into the navigation.
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     * @return void
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                    $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode.
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = [];
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return ['sectiontitles' => $titles, 'action' => 'move'];
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course.
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Definitions of the additional options that this course format uses for course.
     *
     * Topics format uses the following options:
     * - coursedisplay
     * - hiddensections
     *
     * Compass format uses the following options:
     * - termforlessonstab
     * - enableassessmentstab
     * - termforassessmentstab
     *
     * @param bool $foreditform Whether we are in course setting tab or displaying the course.
     * @return array $courseformatoptions Additional course format options with their default value, if any.
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        $courseformatoptions = self::course_format_options_list($foreditform);

        if ($foreditform) {
            // Get_course() will return null during course creation process.
            $course = $this->get_course();
            if (!is_null($course)) {
                $courseformatoptions = \format_compass\local\course_options::restore_course_options(
                    $courseformatoptions,
                    $this->get_course()
                );
            }
        }

        return $courseformatoptions;
    }

    /**
     * Compass course format options list.
     *
     * @param bool $foreditform False if view the course. True if editing the course settings.
     * @return array $courseformatoptions List of course format options.
     */
    public static function course_format_options_list($foreditform = false) {
        static $courseformatoptions = false;
        $range0to100percent = options::generate_range(0, 100, 10, '%');
        $range50to100percent = options::generate_range(50, 100, 10, '%');
        $range10to90percent = options::generate_range(10, 90, 10, '%');
        $compassconfig = get_config('format_compass');

        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');

            $courseformatoptions = [
                'hiddensections' => [
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ],
                'coursedisplay' => [
                    'default' => COURSE_DISPLAY_MULTIPAGE,
                    'type' => PARAM_INT,
                ],
                'termforlessonstab' => [
                    'default' => 'ecf_lessonstabname_lessons',
                    'type' => PARAM_TEXT,
                ],
                'enableassessmentstab' => [
                    'default' => 1,
                    'type' => PARAM_INT,
                ],
                'termforassessmentstab' => [
                    'default' => 'ecf_assessmentstabname_assessments',
                    'type' => PARAM_TEXT,
                ],
                'enablecalendartab' => [
                    'default' => 1,
                    'type' => PARAM_INT,
                ],
                'assessmentscutoff' => [
                    'default' => 'undefined',
                    'type' => PARAM_TEXT,
                ],
                'visualheader' => [
                    'label' => new lang_string('courseformatcolourscheme', 'format_compass'),
                    'element_type' => 'header',
                ],
                'custom_accent_1_shading' => [
                     'default' => $compassconfig->default_accent_1_shading,
                     'type' => PARAM_TEXT,
                ],
                'custom_accent_1_dark' => [
                     'default' => $compassconfig->default_accent_1_dark,
                     'type' => PARAM_TEXT,
                ],
                'custom_accent_1_light' => [
                     'default' => $compassconfig->default_accent_1_light,
                     'type' => PARAM_TEXT,
                ],
                'custom_accent_2_shading' => [
                      'default' => $compassconfig->default_accent_2_shading,
                      'type' => PARAM_TEXT,
                ],
                'custom_accent_2_dark' => [
                      'default' => $compassconfig->default_accent_2_dark,
                      'type' => PARAM_TEXT,
                ],
                'custom_accent_2_light' => [
                       'default' => $compassconfig->default_accent_2_light,
                       'type' => PARAM_TEXT,
                ],
                'coursehometabheader' => [
                    'label' => new lang_string('coursehometab', 'format_compass'),
                    'element_type' => 'header',
                ],
                'displaybottomstripecourseimage' => [
                    'type' => PARAM_TEXT,
                    'default' => 'displaybottomstripecourseimageyes'
                ],
                'activitylistlayout' => [
                    'type' => PARAM_TEXT,
                    'default' => 'activitylistlayout_simplebuttons_3columns'
                ],
                'lessonstabheader' => [
                    'label' => new lang_string('lessonstabheader', 'format_compass'),
                    'element_type' => 'header'
                ],
                'verticalimage_filemanager' => [
                     'type' => PARAM_CLEANFILE,
                ],
                'vertbgfilter' => [
                     'type' => PARAM_TEXT,
                     'default' => 'vertbgfilteraccent1',
                ],
                'vertbgfilteropacity' => [
                    'type' => PARAM_TEXT,
                    'default' => '40%',
                ],
                'lessonheader' => [
                      'label' => new lang_string('lessonbanner', 'format_compass'),
                      'element_type' => 'header',
                ],
                'bannerimage_filemanager' => [
                      'type' => PARAM_CLEANFILE,
                ],
                'bannermobile_filemanager' => [
                      'type' => PARAM_CLEANFILE,
                ],
                'lessonbannercolours' => [
                    'type' => PARAM_TEXT,
                    'default' => 'lessonbannerleftaccent2'
                ],
                'bannerleftopacity' => [
                      'type' => PARAM_TEXT,
                      'default' => '100%',
                ],
                'bannerrightopacity' => [
                      'type' => PARAM_TEXT,
                      'default' => '40%'
                ],
                'bottomstripe' => [
                      'type' => PARAM_TEXT,
                      'default' => 'bottomstripeyes'
                ],
                'assessmentheader' => [
                      'label' => new lang_string('assessmenttab', 'format_compass'),
                      'element_type' => 'header',
                ],
                'assessmentstablayout' => [
                    'default' => 'assessmentslayout_allonepage',
                    'type' => PARAM_TEXT,
                ],
                'initialcollapsestate' => [
                    'type' => PARAM_TEXT,
                    'default' => 'initialcollapsestate_collapse',
                ],
                'displaysectionweights' => [
                    'default' => 'display_weights',
                    'type' => PARAM_TEXT,
                ],
                'displaytextareas' => [
                    'type' => PARAM_TEXT,
                ],
                'textareaabove' => [
                    'type' => PARAM_RAW,
                    'default' => '',
                ],
                'textareabelow' => [
                    'type' => PARAM_RAW,
                    'default' => '',
                ],
            ];
        }

        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $editoroptions = \format_compass\local\options::get_editor_options();
            $filemanagerdefaults = \format_compass\local\options::get_filemanager_options();
            $courseformatoptionsedit = [
                'hiddensections' => [
                    'label' => new lang_string('hiddensections'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible'),
                        ],
                    ],
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                ],
                'coursedisplay' => [
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi'),
                        ],
                    ],
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                    'disabledif' => [['assessmentscutoff', 'neq', '-1']],

                ],
                'termforlessonstab' => [
                    'label' => new lang_string('ecf_lessonstabname_option_title', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            "ecf_lessonstabname_lessons" => new lang_string(
                                'ecf_lessonstabname_lessons', 'format_compass'
                            ),
                            "ecf_lessonstabname_units" => new lang_string(
                                'ecf_lessonstabname_units', 'format_compass'
                            ),
                            "ecf_lessonstabname_modules" => new lang_string(
                                'ecf_lessonstabname_modules', 'format_compass'
                            ),
                            "ecf_lessonstabname_sections" => new lang_string(
                                'ecf_lessonstabname_sections', 'format_compass'
                            ),
                            "ecf_lessonstabname_topics" => new lang_string(
                                'ecf_lessonstabname_topics', 'format_compass'
                            ),
                            "ecf_lessonstabname_content" => new lang_string(
                                'ecf_lessonstabname_content', 'format_compass'
                            ),
                        ],
                    ],
                    'help' => 'termforlessonstab',
                    'help_component' => 'format_compass',
                ],
                'enableassessmentstab' => [
                    'label' => new lang_string('ecf_assessmentstab_enable_title', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            1 => new lang_string('yes'),
                            0 => new lang_string('no'),
                        ],
                    ],
                    'help' => 'enableassessmentstab',
                    'help_component' => 'format_compass',
                ],
                'termforassessmentstab' => [
                    'label' => new lang_string('ecf_assessmentstabname_option_title', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            "ecf_assessmentstabname_assessments" => new lang_string(
                                'ecf_assessmentstabname_assessments', 'format_compass'
                            ),
                            "ecf_assessmentstabname_assignments" => new lang_string(
                                'ecf_assessmentstabname_assignments', 'format_compass'
                            ),
                            "ecf_assessmentstabname_gradedactivities" => new lang_string(
                                'ecf_assessmentstabname_gradedactivities', 'format_compass'
                            ),
                        ],
                    ],
                    'help' => 'termforassessmentstab',
                    'help_component' => 'format_compass',
                ],
                'assessmentscutoff' => [
                    'label' => new lang_string('assessmentcutoff', 'format_compass'),
                    'element_type' => 'hidden',
                ],
                'enablecalendartab' => [
                    'label' => new lang_string('calendartab_enable_title', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            1 => new lang_string('yes'),
                            0 => new lang_string('no'),
                        ],
                    ],
                    'help' => 'enablecalendartab',
                    'help_component' => 'format_compass',
                ],
                'custom_accent_1_shading' => [
                    'label' => new lang_string('custom_accent_1_shading', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'light_primary_dark_secondary' => new lang_string(
                                'light_primary_dark_secondary', 'format_compass'
                            ),
                            'dark_primary_light_secondary' => new lang_string(
                                'dark_primary_light_secondary', 'format_compass'
                            ),
                        ],
                    ],
                ],
                'custom_accent_1_dark' => [
                    'label' => new lang_string('custom_accent_1_dark', 'format_compass'),
                    'element_type' => 'tccolourpopup',
                    'element_attributes' => [
                        [
                           'defaultcolor' => $compassconfig->default_accent_1_dark,
                        ],
                    ],
                ],
                'custom_accent_1_light' => [
                    'label' => new lang_string('custom_accent_1_light', 'format_compass'),
                    'element_type' => 'tccolourpopup',
                    'element_attributes' => [
                        [
                           'defaultcolor' => $compassconfig->default_accent_1_light,
                        ],
                    ],
                ],
                'custom_accent_2_shading' => [
                    'label' => new lang_string('custom_accent_2_shading', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'light_primary_dark_secondary' => new lang_string(
                                'light_primary_dark_secondary', 'format_compass'
                            ),
                            'dark_primary_light_secondary' => new lang_string(
                                'dark_primary_light_secondary', 'format_compass'
                            ),
                        ],
                    ],
                ],
                'custom_accent_2_dark' => [
                    'label' => new lang_string('custom_accent_2_dark', 'format_compass'),
                    'element_type' => 'tccolourpopup',
                    'element_attributes' => [
                        [
                            'defaultcolor' => $compassconfig->default_accent_2_dark,
                        ],
                    ],
                ],
                'custom_accent_2_light' => [
                    'label' => new lang_string('custom_accent_2_light', 'format_compass'),
                    'element_type' => 'tccolourpopup',
                    'element_attributes' => [
                        [
                            'defaultcolor' => $compassconfig->default_accent_2_light,
                        ],
                    ],
                ],
                'verticalimage_filemanager' => [
                    'label' => new lang_string('verticalimage', 'format_compass'),
                    'element_type' => 'filemanager',
                    'element_attributes' =>
                    [
                        null,
                        $filemanagerdefaults,
                    ],
                    'help' => 'verticalimage',
                    'help_component' => 'format_compass',
                ],
                'vertbgfilter' => [
                    'label' => new lang_string('vertbgfilter', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'vertbgfilternone' => new lang_string('vertbgfilternone', 'format_compass'),
                            'vertbgfilteraccent1' => new lang_string('vertbgfilteraccent1', 'format_compass'),
                            'vertbgfilteraccent2' => new lang_string('vertbgfilteraccent2', 'format_compass'),
                        ],
                    ],
                    'help' => 'vertbgfilter',
                    'help_component' => 'format_compass',
                ],
                'vertbgfilteropacity' => [
                    'label' => new lang_string('vertbgfilteropacity', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [ $range10to90percent ],
                    'help' => 'vertbgfilteropacity',
                    'hideif' => ['vertbgfilter', 'eq', 'vertbgfilternone'],
                ],
                'displaybottomstripecourseimage' => [
                    'label' => new lang_string('displaybottomstripecourseimage', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'displaybottomstripecourseimageyes' => new lang_string('yes'),
                            'displaybottomstripecourseimageno' => new lang_string('no'),
                        ],
                    ],
                    'help' => 'displaybottomstripecourseimage',
                    'help_component' => 'format_compass',
                ],
                'activitylistlayout' => [
                    'label' => new lang_string('activitylistlayout', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'activitylistlayout_simplebuttons_3columns' =>
                                new lang_string('activitylistlayout_simplebuttons_3columns', 'format_compass'),
                            'activitylistlayout_simplebuttons_2columns' =>
                                new lang_string('activitylistlayout_simplebuttons_2columns', 'format_compass'),
                            'activitylistlayout_fullrows' => new lang_string('activitylistlayout_fullrows', 'format_compass'),
                        ],
                    ],
                    'help' => 'activitylistlayout',
                    'help_component' => 'format_compass',
                ],
                'bannerimage_filemanager' => [
                    'label' => new lang_string('bannerimage', 'format_compass'),
                    'element_type' => 'filemanager',
                    'element_attributes' =>
                    [
                        null,
                        $filemanagerdefaults,
                    ],
                    'help' => 'bannerimage',
                    'help_component' => 'format_compass',
                ],
                'bannermobile_filemanager' => [
                    'label' => new lang_string('bannermobile', 'format_compass'),
                    'element_type' => 'filemanager',
                    'element_attributes' =>
                    [
                        null,
                        $filemanagerdefaults,
                    ],
                    'help' => 'bannermobile',
                    'help_component' => 'format_compass'
                ],
                'lessonbannercolours' => [
                    'label' => new lang_string('lessonbannercolours', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'lessonbannerleftaccent2' => new lang_string(
                                'lessonbannerleftaccent2', 'format_compass'
                            ),
                            'lessonbannerleftaccent1' => new lang_string(
                                'lessonbannerleftaccent1', 'format_compass'
                            ),
                        ],
                    ],
                    'help' => 'lessonbannercolours',
                    'help_component' => 'format_compass',
                ],
                'bannerleftopacity' => [
                    'label' => new lang_string('bannerleftopacity', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [  $range50to100percent ],
                    'help' => 'bannerleftopacity',
                    'help_component' => 'format_compass',
                ],
                'bannerrightopacity' => [
                    'label' => new lang_string('bannerrightopacity', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [  $range0to100percent ],
                    'help' => 'bannerrightopacity',
                    'help_component' => 'format_compass',
                ],
                'bottomstripe' => [
                    'label' => new lang_string('bottomstripe', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'bottomstripeyes' => new lang_string('yes'),
                            'bottomstripeno' => new lang_string('no'),
                        ],
                    ],
                    'help' => 'bottomstripe',
                    'help_component' => 'format_compass',
                ],
                'assessmentstablayout' => [
                    'label' => new lang_string('assessmentstablayout_option_title', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'assessmentslayout_allonepage' => new lang_string(
                                'assessmentslayout_allonepage', 'format_compass'
                            ),
                            'assessmentslayout_oneperpage' => new lang_string(
                                'assessmentslayout_oneperpage', 'format_compass'
                            ),
                        ],
                    ],
                    'help' => 'assessmentstablayout_option_title',
                    'help_component' => 'format_compass',
                    'disabledif' => [['enableassessmentstab', 'eq', '0']],
                ],
                'initialcollapsestate' => [
                    'label' => new lang_string('initialcollapsestate', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            "initialcollapsestate_collapse" => new lang_string('collapsed', 'format_compass'),
                            "initialcollapsestate_expand" => new lang_string('expanded', 'format_compass'),
                        ],
                    ],
                    'help' => 'initialcollapsestate',
                    'help_component' => 'format_compass',
                    'disabledif' => [['enableassessmentstab', 'eq', '0']],
                    'hideif' => ['assessmentstablayout', 'eq', 'assessmentslayout_oneperpage']
                ],
                'displaysectionweights' => [
                    'label' => new lang_string('displaysectionweights', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'display_weights' => new lang_string(
                                'sectionweightsdisplay', 'format_compass'
                            ),
                            'hide_weights' => new lang_string(
                                'sectionweightdonotdisplay', 'format_compass'
                            )
                        ],
                    ],
                    'help' => 'displaysectionweights',
                    'help_component' => 'format_compass',
                    'disabledif' => [['enableassessmentstab', 'eq', '0']],
                ],
                'displaytextareas' => [
                    'label' => new lang_string('displaytextareassections', 'format_compass'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'displaytextareadonotdisplay' => new lang_string(
                                'displaytextareadonotdisplay', 'format_compass'
                            ),
                            'displaytextareadisplayabove' => new lang_string(
                                'displaytextareadisplayabove', 'format_compass'
                            ),
                            'displaytextareadisplaybelow' => new lang_string(
                                'displaytextareadisplaybelow', 'format_compass'
                            ),
                            'displaytextareadisplayaboveandbelow' => new lang_string(
                                'displaytextareadisplayaboveandbelow', 'format_compass'
                            ),
                        ],
                    ],
                    'help' => 'displaytextareassections',
                    'help_component' => 'format_compass',
                    'disabledif' => [['enableassessmentstab', 'eq', '0']],
                ],
                'textareaabove' => [
                    'label' => new lang_string('textareabovesection', 'format_compass'),
                    'element_type' => 'editor',
                    'disabledif' => [
                        ['enableassessmentstab', 'eq', '0'],
                        ['displaytextareas', 'eq', 'displaytextareadonotdisplay'],
                        ['displaytextareas', 'eq', 'displaytextareadisplaybelow'],
                    ],
                    'element_attributes' =>
                    [
                        null,
                        $editoroptions,
                    ],
                ],
                'textareabelow' => [
                    'label' => new lang_string('textareabelowsections', 'format_compass'),
                    'element_type' => 'editor',
                    'disabledif' => [
                        ['enableassessmentstab', 'eq', '0'],
                        ['displaytextareas', 'eq', 'displaytextareadonotdisplay'],
                        ['displaytextareas', 'eq', 'displaytextdisplayabove'],
                    ],
                    'element_attributes' =>
                    [
                        null,
                        $editoroptions,
                    ],
                ],
            ];
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }

        return $courseformatoptions;
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $DB, $COURSE, $FULLME, $CFG;

        // Add in a color picker to the form.
        MoodleQuickForm::registerElementType(
            'tccolourpopup',
            "$CFG->dirroot/course/format/compass/js/tc_colourpopup.php",
            'MoodleQuickForm_tccolourpopup');
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            /* Add "numsections" element to the create course form - it will force new course to be prepopulated
               with empty sections.
               The "Number of sections" option is no longer available when editing course, instead teachers should
               delete and add sections when needed.
            */
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }

        if ($forsection) {
            $options = $this->section_format_options(true);

            /* get the section id from param
               we need to get the section id because we need the section number variable
               to determine if the section is under lesson or assessment
            */
            $fullmepageurl = new \moodle_url($FULLME);
            $sectionid = $fullmepageurl->get_param('id');

            if (!is_null($sectionid)) {
                $section = $DB->get_record('course_sections', ['course' => $COURSE->id, 'id' => $sectionid], '*', MUST_EXIST);
                // Get the Compass course format object and the course format options.
                $courseformat = course_get_format($COURSE->id);
                $courseformatoptions = $courseformat->get_format_options();
                $enableassessmentstab = $courseformatoptions['enableassessmentstab'];
                $assessmentscutoff = $courseformatoptions['assessmentscutoff'];

                if ($section->section == 0 || ($enableassessmentstab == true && $section->section >= $assessmentscutoff)) {
                    /* if the section is section 0 OR if the assessment tab is enabled and section is under assessments
                       set the hidden form element for hideif condition below
                    */
                    $mform->addElement('hidden', 'hidesubnamefield');
                    $mform->setType('hidesubnamefield', PARAM_INT);
                    $mform->setDefault('hidesubnamefield', 1);
                }
            }

        } else {
            $options = $this->course_format_options(true);

            $options = course_options::prep_editor_editing($options, null);

            foreach (course_options::courseoptionstexteditor as $editor) {
                $element = $mform->getElement($editor);
                $element->setValue(['text' => $options->{$editor.'_editor'}['text']]);
                $mform->setDefault($editor, $options->{$editor.'_editor'}['text']);
            }

            // // Prepare draftarea for custom images only in an existing course. 
            if ($this->courseid > 0) {
                // Prepare draftarea for custom images.
                options::prep_filemanagers(course_options::courseoptionsfilemanager, $this->courseid, $mform);
            }

            // Add in rules for colour options.
            $invalidcolor = new lang_string('invalidcolor', 'format_compass');
            $mform->addRule('custom_accent_1_light', $invalidcolor, 'regex', '/#(?:[0-9a-fA-F]{3}){1,2}$/');
            $mform->addRule('custom_accent_1_dark', $invalidcolor, 'regex', '/#(?:[0-9a-fA-F]{3}){1,2}$/');
            $mform->addRule('custom_accent_2_light', $invalidcolor, 'regex', '/#(?:[0-9a-fA-F]{3}){1,2}$/');
            $mform->addRule('custom_accent_2_dark', $invalidcolor, 'regex', '/#(?:[0-9a-fA-F]{3}){1,2}$/');
        }

        foreach ($options as $optionname => $option) {

            if (isset($option['disabledif'])) {
                $disabledif = $option['disabledif'];
                foreach ($disabledif as $disable) {
                    if (isset($disable[2])) {
                        $mform->disabledif($optionname, $disable[0], $disable[1], $disable[2]);
                    }
                }
            }

            if (isset($option['hideif'])) {
                $hideif = $option['hideif'];
                if (isset($hideif[1])) {
                    $hide = (isset($hideif[2]))
                        ? $mform->hideif($optionname, $hideif[0], $hideif[1], $hideif[2])
                        : $mform->hideif($optionname, $hideif[0], $hideif[1]);
                }
            }
        }

        return $elements;
    }

    /**
     * Definitions of the additional options that this course format uses for section
     *
     * See course_format::course_format_options() for return array definition.
     *
     * Additionally section format options may have property 'cache' set to true
     * if this option needs to be cached in get_fast_modinfo(). The 'cache' property
     * is recommended to be set only for fields used in course_format::get_section_name(),
     * course_format::extend_course_navigation() and course_format::get_view_url()
     *
     * For better performance cached options are recommended to have 'cachedefault' property
     * Unlike 'default', 'cachedefault' should be static and not access get_config().
     *
     * Regardless of value of 'cache' all options are accessed in the code as
     * $sectioninfo->OPTIONNAME
     * where $sectioninfo is instance of section_info, returned by
     * get_fast_modinfo($course)->get_section_info($sectionnum)
     * or get_fast_modinfo($course)->get_section_info_all()
     *
     * All format options for particular section are returned by calling:
     * $this->get_format_options($section);
     *
     * @param bool $foreditform
     * @return array
     */
    public function section_format_options($foreditform = false) {
        $sectionformatoptions = self::section_format_options_list($foreditform);

        return $sectionformatoptions;
    }

    /**
     * Config list for sections. Used in global settings and section edit.
     *
     * @param bool $foreditform
     * @return array $sectionoptions List of section settings.
     */
    public static function section_format_options_list($foreditform) {

        $sectionoptions['subname'] = [
            'default' => '',
            'type' => PARAM_TEXT,
            'label' => new lang_string('subname', 'format_compass'),
            'element_type' => 'text',
            'help' => 'subname',
            'help_component' => 'format_compass',
        ];

        return $sectionoptions;
    }

    /**
     * Updates format options for a section
     *
     * Section id is expected in $data->id (or $data['id'])
     * If $data does not contain property with the option name, the option will not be updated
     *
     * @param stdClass|array $data return value from {@see moodleform::get_data()} or array with data
     * @return bool whether there were any changes to the options values
     */
    public function update_section_format_options($data) {
        global $COURSE;

        $data = (array) $data;

        $options = \format_compass\local\section_options::save_options($data, $COURSE->id);

        return $this->update_format_options($data, $data['id']);
    }

    /**
     * Updates format options for a course.
     *
     * In case if course format was changed to 'topics', we try to copy options
     * 'coursedisplay' and 'hiddensections' from the previous format.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        $data = (array)$data;

        // If $oldcourse is ! null then we are in course update process.
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    }
                }
            }
        }

        // Convert $data to object so it is compatible with file_postupdate_standard_editor().
        $data = (object)$data;

        $context = context_course::instance($this->get_course()->id, MUST_EXIST);
        $filemanageroptions = $editoroptions = \format_compass\local\options::get_filemanager_options();
        $editoroptions['context'] = $context;
        foreach (course_options::courseoptionstexteditor as $editor) {
            // Create the _editor object property in $data so the data
            // structure is compatible with file_postupdate_standard_editor().

            // Check to see if there is data in the editor field.
            // When adding a section, this code is called. But the editor values are null,
            // so don't update the database.
            if (isset($data->{$editor})) {
                $data->{$editor . '_editor'} = $data->{$editor};

                // Write the editor files into their respective filearea.
                $data = file_postupdate_standard_editor(
                        $data, $editor, $editoroptions, $context, 'format_compass', $editor, 0
                );
            }
        }

        foreach (course_options::courseoptionsfilemanager as $filemanager) {
            $data = file_postupdate_standard_filemanager(
                $data, $filemanager, $filemanageroptions, $context, 'format_compass', $filemanager, 0
            );
        }

        $data = (array)$data;

        // Get the course id from the format in case we are in a course creation process.
        if ($data['id'] === 0) {
            $data['id'] = $this->get_course()->id;
        }

        // Initialize the course format options and save the options.
        $data = course_options::initialize_course_format_options($data);
        course_options::save_options($data);
        course_options::generate_cssfile($data);

        // Restore all sections options from course_format_options table.
        $sectionformatoptions = \format_compass\local\section_options::restore_all_section_options(
            $this->get_course()
        );

        // Update format options before generating the course CSS file.
        $update = $this->update_format_options($data);
        course_options::generate_cssfile($data);

        return $update;
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Prepares the templateable object to display section name.
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return inplace_editable
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
            $editable = null, $edithint = null, $editlabel = null) {
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_compass');
        }
        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new lang_string('newsectionname', 'format_compass', $title);
        }
        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    /**
     * Callback used in WS core_course_edit_section when teacher performs an AJAX action on a section (show/hide).
     *
     * Access to the course is already validated in the WS but the callback has to make sure
     * that particular action is allowed by checking capabilities
     *
     * Course formats should register.
     *
     * @param section_info|stdClass $section
     * @param string $action
     * @param int $sr
     * @return null|array any data for the Javascript post-processor (must be json-encodeable)
     */
    public function section_action($section, $action, $sr) {
        global $PAGE;

        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'topics' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        if ($section->section > 0) {
            $rv = parent::section_action($section, $action, $sr);
        }

        $renderer = $PAGE->get_renderer('format_compass');

        if (!($section instanceof section_info)) {
            $modinfo = course_modinfo::instance($this->courseid);
            $section = $modinfo->get_section_info($section->section);
        }
        $elementclass = $this->get_output_classname('content\\section\\availability');
        $availability = new $elementclass($this, $section);

        $rv['section_availability'] = $renderer->render($availability);
        return $rv;
    }

    /**
     * Return an instance of moodleform to edit a specified section
     *
     * Default implementation returns instance of editsection_form that automatically adds
     * additional fields defined in course_format::section_format_options()
     *
     * Format plugins may extend editsection_form if they want to have custom edit section form.
     *
     * @param mixed $action the action attribute for the form. If empty defaults to auto detect the
     *              current url. If a moodle_url object then outputs params as hidden variables.
     * @param array $customdata the array with custom data to be passed to the form
     *     /course/editsection.php passes section_info object in 'cs' field
     *     for filling availability fields
     * @return moodleform
     */
    public function editsection_form($action, $customdata = []) {
        global $CFG;

        if (!str_contains($action, 'format/compass/editsection.php'))
            $action = str_replace('editsection.php', 'format/compass/editsection.php', $action);

        // Get the original editsection_form.
        require_once($CFG->dirroot. '/course/editsection_form.php');
        if (!array_key_exists('course', $customdata)) {
            $customdata['course'] = $this->get_course();
        }

        // Instantiate & return format_compass editsection_form().
        $form = new format_compass\form\editsection_form($action, $customdata);

        return $form;

    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     * @since Moodle 3.5
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        return $this->get_format_options();
    }

    /**
     * Delete course data in format_compass_options table when the course is deleted.
     *
     * Format plugins can override this method to clean any format specific data and dependencies.
     *
     * @return void
     */
    public function delete_format_data() {
        global $DB;
        $course = $this->get_course();

        // By default, formats store some most display specifics in a user preference.
        $DB->delete_records('user_preferences', ['name' => 'coursesectionspreferences_' . $course->id]);

        // Delete format specific options.
        $DB->delete_records('format_compass_options', ['courseid' => $course->id]);

        $fs = get_file_storage();
        $context = context_system::instance();
        // Prepare file record object.
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'format_compass',
            'filearea' => 'styles',
            'itemid' => $context->id,
            'filepath' => '/css/',
            'filename' => 'course' . $course->id . '.css',
        ];
        // Get file.
        $file = $fs->get_file(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        );
        // Delete CSS file.
        if ($file) {
            $file->delete();
        }

        // Loop over all the fileareas to get the images in the filearea.
        // Then delete the images.
        $coursecontext = context_course::instance($course->id)->id;
        foreach (course_options::courseoptionsfilemanager as $image) {
            $imagestobedeleted = $fs->get_area_files(
                $coursecontext, 'format_compass', $image
            );

            foreach ($imagestobedeleted as $imagetobedeleted) {
                $imagetobedeleted->delete();
            }
        }
    }

    /**
     * Deletes a course section and it's format_compass specific options.
     *
     * @param int|stdClass|section_info $section if sync will be a section object, if async will be the section number
     *
     * @param bool $forcedeleteifnotempty if set to false section will not be deleted if it has modules in it.
     *
     * @return bool whether section was deleted
     */
    public function delete_section($section, $forcedeleteifnotempty = false) {
        global $DB;

        // Somtimes $section is an int or entire object. If it is an int fetch the object.
        // Special Note: We have verified sometimes $section is actually a section number, not a cmid as expected
        // Investigate the parent function section_delete to see how this exception is handled with this code below:
        // We must also do this check before the parent call or otherwise it will not exist.
        if (!is_object($section)) {
            $section = $DB->get_record(
                'course_sections',

                [
                    'course' => $this->courseid,
                    'section' => $section,
                ],

                'id,section,sequence,summary'
            );
        }

        // Call the parent delete_section code, in this way we never miss out
        // from MoodleHQ updates to this code. But if there's a problem
        // we should not execute our custom code below.
        $result = parent::delete_section($section, $forcedeleteifnotempty);
        if ($result == false) {
            return false;
        }

        // Since the parent function has returned true by this point we know that Moodle is operating correctly
        // We can now proceed with our own custom functions.

        // Delete the Compass format_compass section options.
        $DB->delete_records(
            'format_compass_options',
            [
                'courseid' => $this->courseid,
                'cmid' => $section->id,
                'name' => 'sectionelements',
            ]
        );
        return true;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 */
function format_compass_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'compass'], MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * Inject the Compass format elements into all or specific module settings forms.
 *
 * @param moodleform $formwrapper The moodle quickforms wrapper object.
 * @param MoodleQuickForm $mform The actual form object (required to modify the form).
 * @return void
 */
function format_compass_coursemodule_standard_elements($formwrapper, $mform) {

    // Get the course module object.
    $cm = $formwrapper->get_coursemodule();
    $course = $formwrapper->get_course();

    if ($course->format == 'compass') {

        // Get the global options.
        $options = \format_compass\local\options::get_default_options();

        if (isset($cm->id) && $cm->id) {
            // Get the activity specific option values.
            $options = \format_compass\local\options::get_options($cm);
            $modtype = $cm->modname;
        } else {
            // We are creating a new activity. Get the new activity type.
            $modtype = \format_compass\local\options::get_mod_type(
                get_class($formwrapper)
            );
        }

        // Call the method to add the field for this particular activity.
        $method = 'add_options_for_mod_'.$modtype;
        if (method_exists('\format_compass\local\activity_options', $method)) {
            $fields = \format_compass\local\activity_options::$method($mform, $options);
        }
    }
}

/**
 * Hook the add/edit of the course module.
 *
 * @param stdClass $data Data from the form submission.
 * @param stdClass $course The course.
 * @return stdClass $data Data from the form submission.
 */
function format_compass_coursemodule_edit_post_actions($data, $course) {
    global $DB;
    $cmid = $data->coursemodule;
    if ($course->format == 'compass') {
        $fields = ['compass_activityelements'];
        foreach ($fields as $field) {
            if (!isset($data->$field)) {
                continue;
            }
            $name = str_replace('compass_', '', $field);
            if (isset($data->$field)) {
                if (is_array($data->$field)) {
                    $value = json_encode($data->$field);
                } else {
                    $value = $data->{$field};
                }
                \format_compass\local\options::save_option($cmid, $course->id, $name, $value);
            }
        }
    }
    return $data;
}

/**
 * Override the core get_state external function to add our cutoffs.
 *
 * @param stdClass $externalfunctioninfo The parent external func description.
 * @param Array $params The param passed to the parent ext function. Course id in this case.
 *
 * @return String $result The JSON string from the parent function with cutoffs added.
 */
function format_compass_override_webservice_execution($externalfunctioninfo, $params) {
    global $PAGE;

    // This callback overrides all external function call made to the course format.
    // We use it's name to override only the get_state function.
    if ($externalfunctioninfo->name == 'core_courseformat_get_state') {

        // Call parent function.
        $callable = [$externalfunctioninfo->classname, $externalfunctioninfo->methodname];
        $result = call_user_func_array($callable, $params);

        // Make sure we have a proper course id.
        if (is_int($params[0])) {
            $course = get_course($params[0]);
        } else {
            return $result;
        }

        // Transform the JSON from the parent into a stdClass so
        // we can add our cutoff information as an extra property.
        $result = json_decode($result);

        // Get the Compass course format object and the course format options.
        $courseformat = course_get_format($course->id);
        $courseformatoptions = $courseformat->get_format_options();

        // Set the course format options.
        $data = new \stdClass();
        $data->hasassessments = $courseformatoptions['enableassessmentstab'];
        $data->labelforlessonscutoff = $courseformatoptions['termforlessonstab'];
        $data->labelforassessmentscutoff = $courseformatoptions['termforassessmentstab'];
        $data->assessmentscutoff = $courseformatoptions['assessmentscutoff'];

        // Add the course format option to the method return.
        $result->course_format_options = $data;

        return json_encode($result);
    }

    // Continue processing the external call with the non-overridden method.
    return false;
}

 /**
  * Serve the files from the format_compass component.
  *
  * @param stdClass $course the course object
  * @param stdClass $cm the course module object
  * @param stdClass $context the context
  * @param string $filearea the name of the file area
  * @param array $args extra arguments (itemid, path)
  * @param bool $forcedownload whether or not force download
  * @param array $options additional options affecting the file serving
  * @return bool false if the file not found, just send the file otherwise and do not return anything
  */
function format_compass_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=[]) {
    // Make sure the user is logged in and has access to the module.
    // (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // When $args is empty => the path is '/'.
    } else {
        $filepath = '/'.implode('/', $args).'/'; // When $args contains elements of the filepath.
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'format_compass', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
