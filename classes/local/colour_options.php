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

namespace format_compass\local;

/**
 * Class to manage the color, opacity and other options from course options.
 *
 * All methods in this class that start with prefix 'cssprop_' will be added as CSS properties
 * to the style sheet file with values corresponding to the user-defined course options.
 * Colour properties will use #rrggbbaa hex color notation to include the opacity level.
 *
 * Here's the summary of all CSS properties:
 *
 * COLOURS:
 *  - active_tab_highlight_colour
 *  - activity_icon_colour
 *  - activity_icon_container_colour
 *  - completion_todo_bg_colour
 *  - course_image_bottom_stripe_colour
 *  - lesson_banner_bottom_stripe_colour
 *  - lesson_banner_left_panel_colour
 *  - lesson_banner_right_panel_colour
 *  - lesson_banner_text_colour
 *  - progress_bar_bg_colour
 *  - progress_bar_fg_colour
 *  - vertical_bg_image_filter_colour
 *  - weight_wheel_bg_colour
 *  - weight_wheel_fg_colour
 *  - unread_notification_text_colour
 *  - unread_notification_bg_colour
 *
 * PATHS:
 *  - lesson_banner_image_path
 *  - lesson_banner_mobile_path
 *  - vertical_bg_image_path
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class colour_options extends \format_compass\local\options {

    /** @var array Course options. */
    public $courseoptions = [];

    /** @var string Color code chosen for accent 1 color in the course setting. */
    public $accent1primary = '';

    /** @var string Color code chosen for accent 2 color in the course setting. */
    public $accent2primary = '';

    /** @var string The template used to format a SCSS property. */
    private $scsspropertytpl = '--%s:%s;';

    /** @var stdClass Object containing the course record. */
    private $course;

    /**
     * Class constructor.
     *
     * @param int $courseid The course id.
     * @param array $courseoptions The course otions.
     *
     * @return void
     */
    public function __construct($courseid, $courseoptions = null) {
        global $DB;
        $this->course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

        // Get course format options.
        if (is_null($courseoptions)) {
            $courseformat = course_get_format($this->course);
            $this->courseoptions = $courseformat->get_format_options();
        } else {
            $this->courseoptions = $courseoptions;
        }

        $this->accent1primary = $this->get_accent1primary();
        $this->accent2primary = $this->get_accent2primary();
    }

    /**
     * Get course option.
     *
     * @param string $option The course option.
     * @return string course option value.
     *
     * @return string String containing colour options for course.
     */
    public function get_course_option($option): string {

        // If the option is defined in the course, use it.
        if (isset($this->courseoptions[$option])) {
            return $this->courseoptions[$option];
        }

        // Get the course option value from the default format setting.
        $defaultoption = str_replace('custom', 'default', $option);
        $courseformatdefault = get_config('format_compass', $defaultoption);
        if ($courseformatdefault !== false) {
            return $courseformatdefault;
        }

        // Get the manually set course option value from it's definition.
        $defaultcourseoptions = \format_compass::course_format_options_list(false);
        if (isset($defaultcourseoptions[$option]['default'])) {
            return $defaultcourseoptions[$option]['default'];
        }

        // If we still don't have value, return ''.
        return '';
    }

    /**
     * Calculate the value of Accent 1 primary color.
     *
     * @return string
     */
    public function get_accent1primary(): string {
        $customaccent1shading = $this->get_course_option('custom_accent_1_shading');

        if ($customaccent1shading === 'light_primary_dark_secondary') {
            return $this->get_course_option('custom_accent_1_light');
        } else {
            return $this->get_course_option('custom_accent_1_dark');
        }
    }

    /**
     * Calculate the value of Accent 2 primary color.
     *
     * @return string
     */
    public function get_accent2primary(): string {
        $customaccent2shading = $this->get_course_option('custom_accent_2_shading');

        if ($customaccent2shading === 'light_primary_dark_secondary') {
            return $this->get_course_option('custom_accent_2_light');
        } else {
            return $this->get_course_option('custom_accent_2_dark');
        }
    }

    /**
     * Converts the opacity percentage to a hexadecimal value.
     *
     * @param int $opacitystring Opacity percentage (0-100).
     * @return string Hexadecimal representation of the opacity.
     */
    private function get_hexvalue($opacitystring): string {
        $opacity = intval($opacitystring);
        $opacity = max(0, min($opacity, 100));
        $alpha = round($opacity * 255 / 100);
        $hexvalue = str_pad(dechex($alpha), 2, '0', STR_PAD_LEFT);
        return strtoupper($hexvalue);
    }

    /**
     * Get all the applicable css properties rendered as SCSS variables.
     *
     * @return string $allcssproperties The formated SCSS variables.
     */
    public function get_all_scss_variables(): string {
        $allcssproperties = '';

        foreach (get_class_methods($this) as $method) {
            if (strpos($method, 'cssprop_') === 0) {
                $allcssproperties .= $this->$method();
            }
        }

        return $allcssproperties;
    }

    /**
     * Return the vertical_bg_image_path SCSS property.
     *
     * @return string vertical_bg_image_path SCSS property.
     */
    public function cssprop_vertical_bg_image_path(): string {
        $value = \format_compass\local\image_options::get_fileareapath_css(\context_course::instance($this->course->id)->id,
            'verticalimage') ?? '';

        if (empty($value)) {
            $formattedvalue = 'url(/course/format/compass/pix/defaults/vertical_bg_image.png) no-repeat center top';
        } else {
            $formattedvalue = sprintf("url('%s')", $value);
        }

        return sprintf($this->scsspropertytpl, 'vertical_bg_image_path', $formattedvalue);
    }

    /**
     * Return the lesson_banner_image_path SCSS property.
     *
     * @return string lesson_banner_image_path SCSS property.
     */
    public function cssprop_lesson_banner_image_path(): string {
        $value = \format_compass\local\image_options::get_fileareapath_css(\context_course::instance($this->course->id)->id,
            'bannerimage') ?? '';

        if (empty($value)) {
            $formattedvalue = 'url(/course/format/compass/pix/defaults/default_lesson_banner.png)';
        } else {
            $formattedvalue = sprintf("url('%s')", $value);
        }

        return sprintf($this->scsspropertytpl, 'lesson_banner_image_path', $formattedvalue);
    }

    /**
     * Return the lesson_banner_mobile_path SCSS property.
     *
     * @return string lesson_banner_mobile_path SCSS property.
     */
    public function cssprop_lesson_banner_mobile_path(): string {
        $value = $value = \format_compass\local\image_options::get_fileareapath_css(\context_course::instance($this->course->id)->id
            , 'bannermobile') ?? '';

        if (empty($value)) {
            $formattedvalue = 'none';
        } else {
            $formattedvalue = sprintf("url('%s')", $value);
        }

        return sprintf($this->scsspropertytpl, 'lesson_banner_mobile_path', $formattedvalue);
    }

    /**
     * Return the active_tab_highlight_colour SCSS property.
     *
     * @return string active_tab_highlight_colour SCSS property.
     */
    public function cssprop_active_tab_highlight_colour(): string {
        $value = $this->accent1primary;
        $value .= $this->get_hexvalue('100');
        return sprintf($this->scsspropertytpl, 'active_tab_highlight_colour', $value);
    }

    /**
     * Return the course_image_bottom_stripe_colour SCSS property.
     *
     * @return string course_image_bottom_stripe_colour SCSS property.
     */
    public function cssprop_course_image_bottom_stripe_colour(): string {
        $value = $this->accent1primary;
        $value .= $this->get_hexvalue('100');
        return sprintf($this->scsspropertytpl, 'course_image_bottom_stripe_colour', $value);
    }

    /**
     * Return the progress_bar_bg_colour SCSS property.
     *
     * @return string progress_bar_bg_colour SCSS property.
     */
    public function cssprop_progress_bar_bg_colour(): string {
        if ($this->get_course_option('custom_accent_1_shading') === 'dark_primary_light_secondary'
         && $this->get_course_option('custom_accent_2_shading') === 'light_primary_dark_secondary') {
            $value = $this->get_course_option('custom_accent_2_light');
        } else {
            $value = $this->get_course_option('custom_accent_1_light');
        }

        $value .= $this->get_hexvalue('100');
        return sprintf($this->scsspropertytpl, 'progress_bar_bg_colour', $value);
    }

    /**
     * Return the progress_bar_fg_colour SCSS property.
     *
     * @return string progress_bar_fg_colour SCSS property.
     */
    public function cssprop_progress_bar_fg_colour(): string {
        if ($this->get_course_option('custom_accent_1_shading') === 'dark_primary_light_secondary'
         && $this->get_course_option('custom_accent_2_shading') === 'light_primary_dark_secondary') {
            $value = $this->get_course_option('custom_accent_1_dark');
        } else {
            $value = $this->get_course_option('custom_accent_2_dark');
        }

        $value .= $this->get_hexvalue('100');
        return sprintf($this->scsspropertytpl, 'progress_bar_fg_colour', $value);
    }

    /**
     * Return the vertical_bg_image_filter_colour SCSS property.
     *
     * @return string vertical_bg_image_filter_colour SCSS property.
     */
    public function cssprop_vertical_bg_image_filter_colour(): string {
        if ($this->get_course_option('vertbgfilter') === 'vertbgfilteraccent2') {
            $value = $this->accent2primary;
        } else {
            $value = $this->accent1primary;
        }

        if ($this->get_course_option('vertbgfilter') === 'vertbgfilternone') {
            $alpha = '0';
        } else {
            $alpha = $this->get_course_option('vertbgfilteropacity') ?? '100';
        }

        $value .= $this->get_hexvalue($alpha);
        return sprintf($this->scsspropertytpl, 'vertical_bg_image_filter_colour', $value);
    }

    /**
     * Return the lesson_banner_left_panel_colour SCSS property.
     *
     * @return string lesson_banner_left_panel_colour SCSS property.
     */
    public function cssprop_lesson_banner_left_panel_colour(): string {
        if ($this->get_course_option('lessonbannercolours') === 'lessonbannerleftaccent1') {
            $value = $this->accent1primary;
        } else {
            $value = $this->accent2primary;
        }

        $value .= $this->get_hexvalue($this->get_course_option('bannerleftopacity') ?? '100');
        return sprintf($this->scsspropertytpl, 'lesson_banner_left_panel_colour', $value);
    }

    /**
     * Return the lesson_banner_right_panel_colour SCSS property.
     *
     * @return string lesson_banner_right_panel_colour SCSS property.
     */
    public function cssprop_lesson_banner_right_panel_colour(): string {
        if ($this->get_course_option('lessonbannercolours') === 'lessonbannerleftaccent1') {
            $value = $this->accent2primary;
        } else {
            $value = $this->accent1primary;
        }

        $value .= $this->get_hexvalue($this->get_course_option('bannerrightopacity') ?? '100');
        return sprintf($this->scsspropertytpl, 'lesson_banner_right_panel_colour', $value);
    }

    /**
     * Return the lesson_banner_bottom_stripe_colour SCSS property.
     *
     * @return string lesson_banner_bottom_stripe_colour SCSS property.
     */
    public function cssprop_lesson_banner_bottom_stripe_colour(): string {
        if ($this->get_course_option('lessonbannercolours') === 'lessonbannerleftaccent1') {
            $value = $this->accent2primary;
        } else {
            $value = $this->accent1primary;
        }

        $value .= $this->get_hexvalue('100');
        return sprintf($this->scsspropertytpl, 'lesson_banner_bottom_stripe_colour', $value);
    }

    /**
     * Return the lesson_banner_text_colour SCSS property.
     *
     * @return string lesson_banner_text_colour SCSS property.
     */
    public function cssprop_lesson_banner_text_colour(): string {
        $lessonbannercolours = $this->get_course_option('lessonbannercolours');
        $value = '';

        if ($lessonbannercolours === 'lessonbannerleftaccent1') {
            if ($this->get_course_option('custom_accent_1_shading') === 'light_primary_dark_secondary') {
                $value = '#000000';
            } else {
                $value = '#FFFFFF';
            }
        } else if ($lessonbannercolours === 'lessonbannerleftaccent2') {
            if ($this->get_course_option('custom_accent_2_shading') === 'light_primary_dark_secondary') {
                $value = '#000000';
            } else {
                $value = '#FFFFFF';
            }
        }

        $value .= $this->get_hexvalue('100');
        return sprintf( $this->scsspropertytpl, 'lesson_banner_text_colour', $value);
    }

    /**
     * Return the activity_icon_colour SCSS property.
     *
     * @return string activity_icon_colour SCSS property.
     */
    public function cssprop_activity_icon_colour(): string {
        if ($this->get_course_option('custom_accent_2_shading') === 'light_primary_dark_secondary') {
            // Disable filter to keep 'black' icon color.
            $value = 'none';
        } else {
            // Add filter to display a white icon.
            $value = 'brightness(0) invert(1)';
        }

        return sprintf($this->scsspropertytpl, 'activity_icon_colour', $value);
    }

    /**
     * Return the activity_icon_container_colour SCSS property.
     *
     * @return string activity_icon_container_colour SCSS property.
     */
    public function cssprop_activity_icon_container_colour(): string {
        $value = $this->accent2primary;
        $value .= $this->get_hexvalue('100');
        return sprintf($this->scsspropertytpl, 'activity_icon_container_colour', $value);
    }

    /**
     * Return the completion_todo_bg_colour SCSS property.
     *
     * @return string completion_todo_bg_colour SCSS property.
     */
    public function cssprop_completion_todo_bg_colour(): string {
        $value = $this->get_course_option('custom_accent_1_light');
        $value .= $this->get_hexvalue('40');
        return sprintf($this->scsspropertytpl, 'completion_todo_bg_colour', $value);
    }

    /**
     * Return the weight_wheel_bg_colour SCSS property.
     *
     * @return string weight_wheel_bg_colour SCSS property.
     */
    public function cssprop_weight_wheel_bg_colour(): string {
        if ($this->get_course_option('custom_accent_1_shading') === 'dark_primary_light_secondary' &&
         $this->get_course_option('custom_accent_2_shading') === 'light_primary_dark_secondary') {
            $value = $this->get_course_option('custom_accent_2_light');
        } else {
            $value = $this->get_course_option('custom_accent_1_light');
        }
        //$value .= $this->get_hexvalue($this->get_course_option('weight_wheel_bg_opacity') ?? '100');
        return sprintf($this->scsspropertytpl, 'weight_wheel_bg_colour', $value);
    }

    /**
     * Return the weight_wheel_fg_colour SCSS property.
     *
     * @return string weight_wheel_fg_colour SCSS property.
     */
    public function cssprop_weight_wheel_fg_colour(): string {
        if ($this->get_course_option('custom_accent_1_shading') === 'dark_primary_light_secondary' &&
         $this->get_course_option('custom_accent_2_shading') === 'light_primary_dark_secondary') {
            $value = $this->get_course_option('custom_accent_1_dark');
        } else {
            $value = $this->get_course_option('custom_accent_2_dark');
        }
        //$value .= $this->get_hexvalue($this->get_course_option('weight_wheel_fg_opacity') ?? '100');
        return sprintf($this->scsspropertytpl, 'weight_wheel_fg_colour', $value);
    }

    /**
     * Return the unread_notification_text_colour SCSS property.
     *
     * @return string unread_notification_text_colour SCSS property.
     */
    public function cssprop_unread_notification_text_colour(): string {
        // Bg color will always be accent 2 dark, so text needs to be white.
        $value = '#FFFFFF';
       return sprintf($this->scsspropertytpl, 'unread_notification_text_colour', $value);
    }

    /**
     * Return the unread_notification_bg_colour SCSS property.
     *
     * @return string unread_notification_bg_colour SCSS property.
     */
    public function cssprop_unread_notification_bg_colour(): string {
        $value = $this->get_course_option('custom_accent_2_dark');
        return sprintf($this->scsspropertytpl, 'unread_notification_bg_colour', $value);
    }
}
