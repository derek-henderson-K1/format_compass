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
 * Helper functions for use in tests.
 *
 * @package    format_compass
*  @copyright  2024 Knowledgeone inc. <https://knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Helper function to set an image in text editors.
 * @param int $courseid
 * @param string fieldname
 * @param string $filename
 * @return int $filrtrecord->id id of the file created.
 * @throws dml_exception
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function helper_set_textarea_image(int $courseid, string $fieldname, string $filename) {
    global $DB, $CFG;
    $filepath = "$CFG->dirroot/course/format/compass/tests/fixtures/images";   
    $context = context_course::instance($courseid);
    $filename = $filepath.'/'.$filename;
    $fid = compass_course_format_fill_draft_area($filename);

    return $fid;
}

/**
 * Helper function to set an image in text editors.
 * @param int $courseid
 * @param string fieldname
 * @param string $filename
 * @return int $filrtrecord->id id of the file created.
 * @throws dml_exception
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function helper_set_banner_image(int $courseid, string $fieldname, string $filename) {
    global $DB, $CFG;
    $filepath = "$CFG->dirroot/course/format/compass/tests/fixtures/images";   
    $context = context_course::instance($courseid);
    $filename = $filepath.'/'.$filename;
    $fid = compass_course_format_fill_draft_area($filename);
    return $fid;
}

    /**
     * Creates a draft area for current user and fills it with files
     *
     * @param string $filename  filename of file that need to be added to filearea, filename => filecontents
     * @return int draftid for the filearea
     */
    function compass_course_format_fill_draft_area(string $filename) {
        global $USER;
        $usercontext = \context_user::instance($USER->id);
        $draftid = file_get_unused_draft_itemid();
        // Add actual file there.
        $filerecord = array('component' => 'user', 'filearea' => 'draft',
                    'contextid' => $usercontext->id, 'itemid' => $draftid,
                    'filename' => $filename, 'filepath' => '/');
        $fs = get_file_storage();
        $fs->create_file_from_string($filerecord, $filename);
        
        return $draftid;
    }

