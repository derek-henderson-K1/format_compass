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
 * Settings for format_compass
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */


defined('MOODLE_INTERNAL') || die;
use format_compass\local\course_options;

if ($ADMIN->fulltree) {
    $plugin = 'format_compass';

    $description = '';
    $settings->add(new admin_setting_heading('formatcompassdefaults',
    get_string('format_settings_page_title', $plugin), get_string('format_settings_page_description', $plugin)));

    $menu['light_primary_dark_secondary'] = get_string('light_primary_dark_secondary', $plugin);
    $menu['dark_primary_light_secondary'] = get_string('dark_primary_light_secondary', $plugin);

    $name = get_string('default_accent_1_shading', $plugin);
    $settings->add(new admin_setting_configselect('format_compass/default_accent_1_shading',
                   $name, $description, 'light_primary_dark_secondary', $menu));

    $name = $plugin.'/default_accent_1_dark';
    $title = get_string('default_accent_1_dark', $plugin);
    $settings->add(new admin_setting_configcolourpicker($name, $title, $description, course_options::defaultvalues['custom_accent_1_dark']));

    $name = $plugin.'/default_accent_1_light';
    $title = get_string('default_accent_1_light', $plugin);
    $settings->add(new admin_setting_configcolourpicker($name, $title, $description, course_options::defaultvalues['custom_accent_1_light']));

    $name = get_string('default_accent_2_shading', $plugin);
    $settings->add(new admin_setting_configselect('format_compass/default_accent_2_shading',
                   $name, $description, 'light_primary_dark_secondary', $menu));

    $name = $plugin.'/default_accent_2_dark';
    $title = get_string('default_accent_2_dark', $plugin);
    $settings->add(new admin_setting_configcolourpicker($name, $title, $description, course_options::defaultvalues['custom_accent_2_dark']));

    $name = $plugin.'/default_accent_2_light';
    $title = get_string('default_accent_2_light', $plugin);
    $settings->add(new admin_setting_configcolourpicker($name, $title, $description, course_options::defaultvalues['custom_accent_2_light']));
}
