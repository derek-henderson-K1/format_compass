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

namespace format_compass\navigation\views;

use navigation_node;
use url_select;
use settings_navigation;

/**
 * Class secondary_navigation_view.
 *
 * The secondary navigation view is a stripped down tweaked version of the
 * settings_navigation/navigation
 *
 * @package     format_compass
 * @category    navigation
 * @copyright   2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class secondary extends \core\navigation\views\secondary {

    /**
     * Overloads the course secondary navigation in format_compass plugin. Since we are sourcing all the info from existing objects that already do
     * the relevant checks, we don't do it again here.
     *
     * @param $termforlessonstab|null $termforlessonstab is the value set in course format option "termforlessonstab"
     */
    public function rename_coursehome_tab($secondarynav) {
        $coursenode = $this->get('coursehome');
        if ($coursenode) {
            $coursenode->title = get_string('firsttabname', 'format_compass');
            $coursenode->text = get_string('firsttabname', 'format_compass');
        }
    }

    public function rename_secondary_course_tab($secondarynav) {
        global $PAGE;
        $format = course_get_format($PAGE->course);
        $options = $format->get_format_options();
        if (isset($options['termforlessonstab'])) {
            $this->rename_course_tab(
                get_string(
                    'ecf_lessonstabname_'.strtolower($options['termforlessonstab']),
                    'format_compass'
                )
            );
        }
        return $secondarynav;
    }

    /**
     * Add assessments tab to the secondary navigation tab.
     *
     * @param secondarynav $secondarynav The Compass secondary nav object
     * @return void
     */
    public function add_assessments_tab($secondarynav) {
        global $PAGE;

        $format = course_get_format($PAGE->course);
        $options = $format->get_format_options();
        // If the assessment tab is enabled in course format option, show the assessment tab.
        if (isset($options['enableassessmentstab'])) {
            $enableassessmentstab = $options['enableassessmentstab'];
            if ($enableassessmentstab == 1) {
                // Get the assessment tab name from course format option.
                $termforassessmentstab = ucfirst(get_string($options['termforassessmentstab'], 'format_compass'));

                // Assessments tab will be dispayed after the Lessons tab.
                // Here we are detecting the exact node to place "Assessments" tab.
                $this->add_tab_to_secondary_nav('lessons', 'assessments', $termforassessmentstab);
            }
        }
    }

    /**
     * Add lessons tab to the secondary navigation tab.
     *
     * @param secondarynav $secondarynav The Compass secondary nav object
     * @return void
     */
    public function add_lessons_tab($secondarynav) {
        global $PAGE;
        $format = course_get_format($PAGE->course);
        $options = $format->get_format_options();
        $termforlessonstab = 'lessons';
        if (isset($options['termforlessonstab'])) {
            $termforlessonstab = get_string(strtolower($options['termforlessonstab']), 'format_compass');
        }

        // Lessons tab will be dispayed after the Course Home tab.
        // Here we are detecting the exact node to place "Lessons" tab.
        $this->add_tab_to_secondary_nav('coursehome', 'lessons', $termforlessonstab);
    }

    /**
     * Add calendar tab to the secondary navigation tab.
     *
     * @param secondarynav $secondarynav The Compass secondary nav object
     * @return void
     */
    public function add_calendar_tab($secondarynav) {
        global $PAGE;
        $format = course_get_format($PAGE->course);
        $options = $format->get_format_options();
        $calendartab = 'calendar';
        if (isset($options['enablecalendartab'])) {
            $enablecalendartab = $options['enablecalendartab'];
            if ($enablecalendartab == 1) {
                $calendartab = get_string('calendartabname_calendar', 'format_compass');
                // Calendar tab will be dispayed after the Grades tab.
                // Here we are detecting the exact node to place "Calendar" tab.
                $this->add_tab_to_secondary_nav('grades', 'calendar', $calendartab);
            }
        }
    }

    /**
     * Rearrange secondary tabs.
     *
     * @param secondarynav $secondarynav The Compass secondary nav object
     * @return void
     */
    public function rearrange_secondary_tabs($secondarynav) {
        global $PAGE;
        $format = course_get_format($PAGE->course);
        $options = $format->get_format_options();

        $settings = $this->get('editsettings');
        $participants = $this->get('participants');
        $grades = $this->get('grades');
        $coursereports = $this->get('coursereports');
        $calendar = $this->get('calendar');

        // first remove the nodes to be rearranged.
        if ($settings) {
            $settings->remove();
        }
        if ($participants) {
            $participants->remove();
        }
        if ($grades) {
            $grades->remove();
        }
        if ($calendar) {
            $calendar->remove();
        }

        // then find the next node to locate the node insert position
        $next = false;
        $enableassessmentstab = $options['enableassessmentstab'];
        if ($enableassessmentstab == 1) {
            $nextto = 'assessments';
        } else {
            $nextto = 'lessons';
        }

        $childnodes = $this->children;
        $nextnodekey = null;
        foreach ($childnodes as $key => $value) {
            if ($next == true) {
                $nextnodekey = $value->key;
                break;
            }
            if ($value->key == $nextto) {
                $next = true;
            }
        }

        // insert the nodes if the node exists.
        if ($grades) {
            $this->add_node($grades, $nextnodekey);
        }
        if ($calendar) {
            $this->add_node($calendar, $nextnodekey);
        }
        if ($participants) {
            $this->add_node($participants, $nextnodekey);
        }
        if ($settings) {
            $this->add_node($settings, $nextnodekey);
        }
    }

    public function override_active_node_if_neccesary() {
        global $PAGE, $FULLME, $COURSE;
        $format = course_get_format($PAGE->course);
        $options = $format->get_format_options();

        $fullmepageurl = new \moodle_url($FULLME);

        $childnodes = $this->children;

        // Define whether or not we are in a section.
        // null will be returned if the section param
        // is not present in the current URL.
        $sectionnumber = $fullmepageurl->get_param('section');

        if (!is_null($sectionnumber)) {
            // Get the current section index and compare it with the assessmentscutoff
            // to know in which tab the user was in.
            if ($sectionnumber < $options['assessmentscutoff']) {
                $tab = 'lessons';
                $url = new \moodle_url('/course/view.php', ['id' => $COURSE->id, 'tab' => $tab]);
            } else {
                // if enableassessmenttab is disabled, highlight the lesson page
                if ($options['enableassessmentstab'] == true) {
                    $tab = 'assessments';
                    $url = new \moodle_url('/course/view.php', ['id' => $COURSE->id, 'tab' => $tab]);
                } else {
                    $tab = 'lessons';
                    $url = new \moodle_url('/course/view.php', ['id' => $COURSE->id, 'tab' => $tab]);
                }
            }

            foreach ($childnodes as $key => $value) {
                if ($value->action == $url) {
                    $value->make_active();
                } else {
                    $value->make_inactive();
                }
            }
        } else {
            // Manual activation is only needed for lessons and assessments tab.
            // So we check for the url to make sure this logic gets executed only
            // for those 2 pages.
            if (str_contains($FULLME, '/course/view.php')) {
                foreach ($childnodes as $key => $value) {
                    if ($value->action == $fullmepageurl) {
                        $value->make_active();
                    } else {
                        $value->make_inactive();
                    }
                }
            } else if (str_contains($FULLME, '/backup/backup.php')) {
                // Make sure backup.php contains id querystring
                // $FULLME was not providing the id querystring for backup.php, so using $PAGE->url instead.
                $fullmepageurl = $PAGE->url;
            }
        }
        // Updated Page->url to include the tab display when page edit is
        // enabled, then correct url is used.
        $PAGE->set_url($fullmepageurl);
    }

    private function add_tab_to_secondary_nav($nextto, $tablabel, $termfortab) {
        global $PAGE;
        $childnodes = $this->children;
        $nextnodekey = '';
        $next = false;

        foreach ($childnodes as $key => $value) {
            if ($next == true) {
                $nextnodekey = $value->key;
                break;
            }
            if ($value->key == $nextto) {
                $next = true;
            }
        }

        if (!empty($nextnodekey)) {
            // Add the lesson tab.
            $this->add_node(
                navigation_node::create($termfortab, new \moodle_url('/course/view.php', ['id' => $PAGE->course->id, 'tab' => $tablabel]),
                    self::TYPE_COURSE, null, $tablabel), $nextnodekey
                );
        } else {
            // if the nextto node exist but it is the very last node, then we insert the new tab key at the end of the menu
            if ($next) {
                $this->add_node(
                    navigation_node::create($termfortab, new \moodle_url('/course/view.php', ['id' => $PAGE->course->id, 'tab' => $tablabel]),
                        self::TYPE_COURSE, null, $tablabel)
                    );
            }
        }

    }
}
