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
 * Specialised backup for Compass course format.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Format compass features backup.
 */
class backup_format_compass_plugin extends backup_format_plugin {

    /**
     * Define course plugin structure.
     */
    public function define_course_plugin_structure() {

        $compass = new backup_nested_element(
            'format_compass_course_options',
            ['id'],
            ['courseid', 'cmid', 'name', 'value', 'timecreated', 'timemodified']
        );

        $compass->set_source_table(
            'format_compass_options',
            [
                'courseid' => backup::VAR_COURSEID,
                'cmid' => backup_helper::is_sqlparam('0'),
                'name' => backup_helper::is_sqlparam('courseelements'),
            ]
        );

        // Annotate format compass option files to be processed by backup plugin.
        $filemanagers = \format_compass\local\course_options::get_courseoptionsallfiles();
        foreach ($filemanagers as $manager) {
            $compass->annotate_files(
                'format_compass',
                $manager,
                null
            );
        }

        // Build the tree
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'compass');
        $plugin->add_child($compass);

        return $plugin;
    }

    /**
     * Define the sections features to backup.
     */
    public function define_section_plugin_structure() {
        $formatoptions = new backup_nested_element(
            'format_compass_section_options',
            ['id'],
            ['courseid', 'cmid', 'name', 'value', 'timecreated', 'timemodified']
        );

        // Define sources.
        $formatoptions->set_source_table(
            'format_compass_options',
            [
                'courseid' => backup::VAR_COURSEID,
                'cmid' => backup::VAR_SECTIONID,
                'name' => backup_helper::is_sqlparam('sectionelements'),
            ]
        );

        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'compass');

        return $plugin->add_child($formatoptions);
    }
}
