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
 * Legacy file to aviod exceptions when formats require it.
 *
 * @deprecated since Moodle 4.0 MDL-72656
 * @package    format_compass
 * @copyright  2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Setting own secondary nav class to override the core secondarynav.
global $FULLME, $PAGE;

$fs = get_file_storage();
$context = context_system::instance();
// Prepare file record object.
$fileinfo = [
    'contextid' => $context->id,
    'component' => 'format_compass',
    'filearea' => 'styles',
    'itemid' => $context->id,
    'filepath' => '/css/',
    'filename' => 'course' . $PAGE->course->id . '.css',
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
// Create file URL.
$csspath = moodle_url::make_pluginfile_url(
    $fileinfo['contextid'],
    $fileinfo['component'],
    $fileinfo['filearea'],
    $fileinfo['itemid'],
    $fileinfo['filepath'],
    $fileinfo['filename'],
    false
);
// Add CSS to the page.
$PAGE->requires->css($csspath);

// Initialize page navigation.
$PAGE->navigation->initialise();

// If the page is of type "backup-restore, or backup-copy then the secondary nav has already been initialized.
// So, we need to remove all the existing nodes before initializing the format custom nodes.
if ($PAGE->pagetype == "backup-restore" || $PAGE->pagetype == "backup-copyprogress") {
    $childnodes = $PAGE->navigation->children;

    foreach ($childnodes as $key => $value) {
        $value->remove();
    }
}
// Get the inherited seconday navigation from navigation_views_secondary.
$secondarynav = new format_compass\navigation\views\secondary($PAGE);
$secondarynav->initialise();

// Renames the course tab to "course home".
$secondarynav->rename_coursehome_tab($secondarynav);

// Adding lessons tab in the course secondary navigation.
$secondarynav->add_lessons_tab($secondarynav);
// Adding assessment tab in the course secondary navigation.
$secondarynav->add_assessments_tab($secondarynav);
// Adding calendar tab in the course secondary navigation.
$secondarynav->add_calendar_tab($secondarynav);
// rearrange secondary tabs
$secondarynav->rearrange_secondary_tabs($secondarynav);
// Overriding active node.
$secondarynav->override_active_node_if_neccesary();

// Sets the modified secondary navigation.
$PAGE->set_secondarynav($secondarynav);

