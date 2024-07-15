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
 * Course index section component.
 *
 * This component is used to control specific course section interactions like drag and drop
 * in both course index and course content.
 *
 * @module     format_compass/local/courseeditor/dndsection
 * @class      format_compass/local/courseeditor/dndsection
 * @copyright  2023 KnowledgeOne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import DndSection from 'core_courseformat/local/courseeditor/dndsection';

export default class extends DndSection {

    /**
     * Save some values form the state.
     *
     * @param {Object} state the current state
     */
    configState(state) {
        this.id = this.element.dataset.id;
        this.section = state.section.get(this.id);
        this.course = state.course;
        this.course_format_options = state.course_format_options;
    }

    /**
     * Display the component dropzone.
     *
     * @param {Object} dropdata the accepted drop data
     */
    showDropZone(dropdata) {
        //Show dropzones fort assessments header
        if(this.sectionitem.id == "assessmentscutoff") {
            if (dropdata.type == 'section') {
                if (parseInt(this.course_format_options.assessmentscutoff) > dropdata.number) {
                    this.sectionitem.element.classList.remove(this.classes.DROPUP);
                    this.sectionitem.element.classList.add(this.classes.DROPDOWN);
                } else {
                    this.sectionitem.element.classList.add(this.classes.DROPUP);
                    this.sectionitem.element.classList.remove(this.classes.DROPDOWN);
                }
            }
        } else {
            if (dropdata.type == 'cm') {
                this.getLastCm()?.classList.add(this.classes.DROPDOWN);
            }
            if (dropdata.type == 'section') {
                // The relative move of section depends on the section number.
                if (this.section.number > dropdata.number) {
                    this.element.classList.remove(this.classes.DROPUP);
                    this.element.classList.add(this.classes.DROPDOWN);
                } else {

                    this.element.classList.add(this.classes.DROPUP);
                    this.element.classList.remove(this.classes.DROPDOWN);
                }
            }
        }
    }

    /**
     * Hide the component dropzone.
     */
    hideDropZone() {
        this.getLastCm()?.classList.remove(this.classes.DROPDOWN);
        this.element.classList.remove(this.classes.DROPUP);
        this.element.classList.remove(this.classes.DROPDOWN);
        // Hide assessments header dropzone
        this.sectionitem.element.classList.remove(this.classes.DROPUP);
        this.sectionitem.element.classList.remove(this.classes.DROPDOWN);
    }

    /**
     * Drop event handler.
     *
     * @param {Object} dropdata the accepted drop data
     */
    drop(dropdata) {
        let cutoffs;
        let cutoff = parseInt(this.course_format_options.assessmentscutoff);

        // Drops into assessments header
        if(this.sectionitem.id == "assessmentscutoff") {
            if(dropdata.type == 'section') {
                if(dropdata.number == cutoff) {
                    //Drop from assessments to lessons without changing position
                    this.reactive.dispatch('saveCutoffs', cutoff+1);
                } else if(dropdata.number == cutoff-1) {
                    //Drop from lessons to assessments without changing position
                    this.reactive.dispatch('saveCutoffs', cutoff-1);
                } else if (dropdata.number < cutoff) {
                    //Drop from lessons to assessments
                    let sectionid = this.course.sectionlist[cutoff-1];
                    cutoffs = [{cutoff: [{ assessment: (cutoff-1) }]}];
                    this.reactive.dispatch('sectionMove', [dropdata.id], sectionid, cutoffs);
                } else if (dropdata.number >= cutoff) {
                    //Drop from assessments to lessons
                    let sectionid = this.course.sectionlist[cutoff];
                    cutoffs = [{cutoff: [{ assessment: (cutoff+1) }]}];
                    this.reactive.dispatch('sectionMove', [dropdata.id], sectionid, cutoffs);
                }
            }
        } else {
            if (dropdata.type == 'cm') {
                this.reactive.dispatch('cmMove', [dropdata.id], this.id);
            }
            if (dropdata.type == 'section') {
                //Assessments tab on
                if(this.course_format_options.hasassessments) {
                    // Moving within lessons
                    if(dropdata.number < cutoff && this.section.number < cutoff) {
                        this.reactive.dispatch('sectionMove', [dropdata.id], this.id);
                    }

                    //Moving to assessments
                    if(dropdata.number < cutoff && this.section.number >= cutoff) {
                        cutoffs = [{cutoff: [{ assessment: (cutoff-1) }]}];
                        this.reactive.dispatch('sectionMove', [dropdata.id], this.id, cutoffs);
                    }

                    // Moving within assessments
                    if(dropdata.number >= cutoff && this.section.number >= cutoff) {
                        this.reactive.dispatch('sectionMove', [dropdata.id], this.id);
                    }

                    //Moving to lessons
                    if(dropdata.number >= cutoff && this.section.number < cutoff) {
                        cutoffs = [{cutoff: [{ assessment: (cutoff+1) }]}];
                        this.reactive.dispatch('sectionMove', [dropdata.id], this.id, cutoffs);
                    }
                } else {
                    //assessments tab off
                    this.reactive.dispatch('sectionMove', [dropdata.id], this.id);
                }
            }
        }
    }
}
