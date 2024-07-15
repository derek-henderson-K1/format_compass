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
 * Specialised restore for Compass course format.
 *
 * @package   format_compass
 * @category  backup
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Specialised restore for Compass course format.
 *
 * Processes 'numsections' from the old backup files and hides sections that used to be "orphaned".
 *
 * @package   format_compass
 * @category  backup
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_format_compass_plugin extends restore_format_plugin {

    /** @var int */
    protected $originalnumsections = 0;

    /**
     * Checks if backup file was made on Moodle before 3.3 and we should respect the 'numsections'
     * and potential "orphaned" sections in the end of the course.
     *
     * @return bool
     */
    protected function need_restore_numsections() {
        $backupinfo = $this->step->get_task()->get_info();
        $backuprelease = $backupinfo->backup_release; // The major version: 2.9, 3.0, 3.10...
        return version_compare($backuprelease, '3.3', '<');
    }

    /**
     * Creates a dummy path element in order to be able to execute code after restore.
     *
     * @return restore_path_element[]
     */
    public function define_course_plugin_structure() {
        global $DB;

        // We add the custom files to be processed by restore plugin.
        $filemanagers = \format_compass\local\course_options::get_courseoptionsallfiles();
        foreach ($filemanagers as $manager) {
            $this->add_related_files('format_compass', $manager, null);
        }

        // Since this method is executed before the restore we can do some pre-checks here.
        // In case of merging backup into existing course find the current number of sections.
        $target = $this->step->get_task()->get_target();
        if (($target == backup::TARGET_CURRENT_ADDING || $target == backup::TARGET_EXISTING_ADDING) &&
                $this->need_restore_numsections()) {
            $maxsection = $DB->get_field_sql(
                'SELECT max(section) FROM {course_sections} WHERE course = ?',
                [$this->step->get_task()->get_courseid()]);
            $this->originalnumsections = (int)$maxsection;
        }
         // Dummy path element is needed in order for after_restore_course() to be called
        $dummycourse = new restore_path_element('dummy_course', $this->get_pathfor('/dummycourse'));
        // adding new restore path element for processing customized course format options during restore
        $format_compass_course_options = new restore_path_element('format_compass_course_options', '/course/format_compass_course_options');
        $restorepathelements = array($dummycourse, $format_compass_course_options);
        return $restorepathelements;
    }

    /**
     * Creates a dummy path element in order to be able to execute code after restore.
     *
     * @return restore_path_element[]
     */
    public function define_section_plugin_structure() {
        // adding new restore path element for processing customized section format options during restore
        $format_compass_section_options = new restore_path_element('format_compass_section_options', '/section/format_compass_section_options');
        $restorepathelements = array($format_compass_section_options);
        return $restorepathelements;
    }

    /**
     * This function processes the compass course format options data and saves it in the custom table 'format_compass_options'
     */
    public function process_format_compass_course_options($data) {
        $courseid = $this->step->get_task()->get_courseid();
        \format_compass\local\course_options::save_option(0, $courseid, $data['name'], $data['value']);
        $dataarray = json_decode($data['value'], true);
        $dataarray['id'] = $courseid;
        \format_compass\local\course_options::generate_cssfile($dataarray);
    }

    /**
     * This function processes the compass section format options data and saves it in the custom table 'format_compass_options'
     */
    public function process_format_compass_section_options($data) {
        $courseid = $this->step->get_task()->get_courseid();
        $sectionid = $this->step->get_task()->get_sectionid();
        \format_compass\local\course_options::save_option($sectionid, $courseid, $data['name'], $data['value']);
    }

    /**
     * Dummy process method.
     *
     * @return void
     */
    public function process_dummy_course() {

    }

    /**
     * Executed after course restore is complete.
     *
     * This method is only executed if course configuration was overridden.
     *
     * @return void
     */
    public function after_restore_course() {
        global $DB;

        if (!$this->need_restore_numsections()) {
            // Backup file was made in Moodle 3.3 or later, we don't need to process 'numsecitons'.
            return;
        }

        $backupinfo = $this->step->get_task()->get_info();
        if ($backupinfo->original_course_format !== 'compass' || !isset($data['tags']['numsections'])) {
            // Backup from another course format or backup file does not even have 'numsections'.
            return;
        }

        $data = $this->connectionpoint->get_data();
        $numsections = (int)$data['tags']['numsections'];
        foreach ($backupinfo->sections as $key => $section) {
            // For each section from the backup file check if it was restored and if was "orphaned" in the original
            // course and mark it as hidden. This will leave all activities in it visible and available just as it was
            // in the original course.
            // Exception is when we restore with merging and the course already had a section with this section number,
            // in this case we don't modify the visibility.
            if ($this->step->get_task()->get_setting_value($key . '_included')) {
                $sectionnum = (int)$section->title;
                if ($sectionnum > $numsections && $sectionnum > $this->originalnumsections) {
                    $DB->execute("UPDATE {course_sections} SET visible = 0 WHERE course = ? AND section = ?",
                        [$this->step->get_task()->get_courseid(), $sectionnum]);
                }
            }
        }
    }
}
