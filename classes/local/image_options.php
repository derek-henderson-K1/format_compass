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

use stdClass;
use context_system;
use format_compass\local\course_options;

/**
 * Class to manage the course options.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class image_options extends \format_compass\local\options {

    public static function get_allfileareapath_css($contextid) {
        $css = '';
        $fs = get_file_storage();
        foreach(course_options::courseoptionsfilemanager as $image) {
            $images = $fs->get_area_files(
                $contextid,
                'format_compass',
                $image
            );

            foreach ($images as $imagefile) {
                // Create file URL.
                if (!$imagefile->is_directory()) {
                    $csspath = \moodle_url::make_pluginfile_url(
                        $imagefile->get_contextid(),
                        $imagefile->get_component(),
                        $imagefile->get_filearea(),
                        $imagefile->get_itemid(),
                        $imagefile->get_filepath(),
                        $imagefile->get_filename(),
                        false
                    );

                    $css .= sprintf('--%s:%s;', $image, $csspath);
                }
            }
        }
        return $css;
    }

    public static function get_fileareapath_css($contextid, $filearea) {
        $css = '';
        $fs = get_file_storage();
        $images = $fs->get_area_files(
            $contextid,
            'format_compass',
            $filearea
        );

        foreach ($images as $imagefile) {
            // Create file URL.
            if (!$imagefile->is_directory()) {
                $csspath = \moodle_url::make_pluginfile_url(
                    $imagefile->get_contextid(),
                    $imagefile->get_component(),
                    $imagefile->get_filearea(),
                    $imagefile->get_itemid(),
                    $imagefile->get_filepath(),
                    $imagefile->get_filename(),
                    false
                );

                $css = $csspath;
            }
        }
        return $css;
    }


}
