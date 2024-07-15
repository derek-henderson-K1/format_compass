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
 * Course index section title draggable component.
 *
 * This component is used to control specific course section interactions like drag and drop
 * in both course index and course content.
 *
 * @module     format_compass/local/courseeditor/dndsectionitem
 * @class      format_compass/local/courseeditor/dndsectionitem
 * @copyright  2023 KnowledgeOne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {DragDrop} from 'core/reactive';
import DndSectionItem from 'core_courseformat/local/courseeditor/dndsectionitem';

export default class extends DndSectionItem {

    /**
     * Initial state ready method.
     *
     * @param {number} sectionid the section id
     * @param {Object} state the initial state
     * @param {Element} fullregion the complete section region to mark as dragged
     */
    configDragDrop(sectionid, state, fullregion) {
        if(this.element.dataset.type == "assessmentscutoff") {
            this.id = "assessmentscutoff";
            this.getDraggableData = this._getDraggableData;
            // Drag and drop is only available for components compatible course formats.
            if (this.reactive.isEditing && this.reactive.supportComponents) {
                // Init the dropzone.
                this.dragdrop = new DragDrop(this);
                // Save dropzone classes.
                this.classes = this.dragdrop.getClasses();
            }
        } else {

            this.id = sectionid;

            if (this.section === undefined) {
                this.section = state.section.get(this.id);
            }
            if (this.course === undefined) {
                this.course = state.course;
            }

            // Prevent topic zero from being draggable.
            if (this.section.number > 0) {
                this.getDraggableData = this._getDraggableData;
            }

            this.fullregion = fullregion;

            // Drag and drop is only available for components compatible course formats.
            if (this.reactive.isEditing && this.reactive.supportComponents) {
                // Init the dropzone.
                this.dragdrop = new DragDrop(this);
                // Save dropzone classes.
                this.classes = this.dragdrop.getClasses();
            }
        }
    }
}
