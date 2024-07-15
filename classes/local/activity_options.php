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

/**
 * Class to manage the activity options.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_options extends options {

    /**
     * Adds the additional activity options for the label activity.
     *
     * @param MoodleQuickForm $mform The actual activity form object.
     * @param stdClass $defaultvalues The fields value.
     * @return void
     */
    public static function add_options_for_mod_label($mform, $defaultvalues) {

        $option = 'functionas';
        $fieldname = 'compass_activityelements['.$option.']';
        $fieldtitle = get_string('activity:'.$option, 'format_compass');
        $fieldoptions = [
            'sectiondivider' => get_string('sectiondivider', 'format_compass'),
            'textandmedia' => get_string('textandmedia', 'format_compass')
        ];

        $mform->addElement('header', 'moduleoptions', get_string('activityoption', 'format_compass'));
        $mform->addElement('select', $fieldname, $fieldtitle, $fieldoptions);
        $mform->setType($fieldname, PARAM_ALPHAEXT);
        $mform->setDefault($fieldname, $fieldoptions['sectiondivider']);
        if (isset($defaultvalues->activityelements[$option])) {
            $mform->setDefault($fieldname, $defaultvalues->activityelements[$option]);
        }
    }
}
