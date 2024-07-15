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
 * Install script for compass course format.
 *
 * @package    format_compass
 * @copyright  2023 Knowledgeone inc. {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
 * Post installation procedure
 */
function xmldb_format_compass_install() {
    $plugin = 'format_compass';
    set_config('default_accent_1_shading','light_primary_dark_secondary' ,$plugin);
    set_config('default_accent_2_shading','light_primary_dark_secondary' ,$plugin);
    set_config('default_accent_1_dark','#E97D11' ,$plugin);
    set_config('default_accent_1_light','#FFDFD1' ,$plugin);
    set_config('default_accent_2_dark','#3F4197' ,$plugin);
    set_config('default_accent_2_light','#c9DCF3' ,$plugin);
    return true;
}
