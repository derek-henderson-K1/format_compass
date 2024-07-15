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
 * Course index main component.
 *
 * @module     format_compass/local/courseindex/courseindex
 * @class      format_compass/local/courseindex/courseindex
 * @copyright  2023 KnowledgeOne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_courseformat/local/courseindex/courseindex';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

export default class CourseIndexComponent extends Component {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'courseindex';
        // Default query selectors.
        this.selectors = {
            SECTION: `[data-for='section']`,
            SECTION_CMLIST: `[data-for='cmlist']`,
            CM: `[data-for='cm']`,
            TOGGLER: `[data-action="togglecourseindexsection"]`,
            COLLAPSE: `[data-toggle="collapse"]`,
            DRAWER: `.drawer`,
            HOME_CONTAINER: `#courseindex-home-container`,
            LESSONS_CONTAINER: `#courseindex-lessons-container`,
            ASSESSMENTS_CONTAINER: `#courseindex-assessments-container`,
        };
        // Default classes to toggle on refresh.
        this.classes = {
            SECTIONHIDDEN: 'dimmed',
            CMHIDDEN: 'dimmed',
            SECTIONCURRENT: 'current',
            COLLAPSED: `collapsed`,
            SHOW: `show`,
        };
        // Arrays to keep cms and sections elements.
        this.sections = {};
        this.cms = {};
    }

    /**
     * Static method to create a component instance form the mustache template.
     *
     * @param {element|string} target the DOM main element or its ID
     * @param {object} selectors optional css selector overrides
     * @return {CourseIndexComponent}
     */
    static init(target, selectors) {
        return new CourseIndexComponent({
            element: document.getElementById(target),
            reactive: getCurrentCourseEditor(),
            selectors,
        });
    }

    getWatchers() {
        return [
            {watch: `section.indexcollapsed:updated`, handler: this._refreshSectionCollapsed},
            {watch: `cm:created`, handler: this._createCm},
            {watch: `cm:deleted`, handler: this._deleteCm},
            {watch: `section:created`, handler: this._createSection},
            {watch: `section:deleted`, handler: this._deleteSection},
            {watch: `course.pageItem:created`, handler: this._refreshPageItem},
            {watch: `course.pageItem:updated`, handler: this._refreshPageItem},
            // Sections and cm sorting.
            {watch: `course.sectionlist:updated`, handler: this._refreshCourseSectionlist},
            {watch: `course_format_options:updated`, handler: this._refreshCourseSectionlist},
            {watch: `section.cmlist:updated`, handler: this._refreshSectionCmlist},
        ];
    }

    /**
     * Refresh a section cm list.
     *
     * @param {object} param
     * @param {Object} param.element
     */
    _refreshSectionCmlist({element}) {
        const cmlist = element.cmlist ?? [];
        const listparent = this.getElement(this.selectors.SECTION_CMLIST, element.id);
        this._fixCmOrder(listparent, cmlist, this.cms);
    }

    /**
     * Fix/reorder the cms order.
     *
     * @param {Element} container the HTML element to reorder.
     * @param {Array} neworder an array with the ids order
     * @param {Array} allitems the list of html elements that can be placed in the container
     */
    _fixCmOrder(container, neworder, allitems) {

        // Empty lists should not be visible.
        if (!neworder.length) {
            container.classList.add('hidden');
            container.innerHTML = '';
            return;
        }

        // Grant the list is visible (in case it was empty).
        container.classList.remove('hidden');

        // Move the elements in order at the beginning of the list.
        neworder.forEach((itemid, index) => {
            const item = allitems[itemid];
            // Get the current element at that position.
            const currentitem = container.children[index];
            if (currentitem === undefined) {
                container.append(item);
                return;
            }
            if (currentitem !== item) {
                container.insertBefore(item, currentitem);
            }
        });
        // Remove the remaining elements.
        while (container.children.length > neworder.length) {
            container.removeChild(container.lastChild);
        }
    }

    /**
     * Refresh the section list.
     *
     * @param {object} param
     * @param {Object} param.state
     */
    _refreshCourseSectionlist({state}) {
        const sectionlist = state.course.sectionlist ?? [];
        const course_format_options = state.course_format_options;
        this._fixSectionsOrder(sectionlist, this.sections, course_format_options);
    }

    /**
     * Fix/reorder the section order.
     *
     * @param {Array} neworder an array with the ids order
     * @param {Array} allitems the list of html elements that can be placed in the container
     * @param {Array} course_format_options
     */
    _fixSectionsOrder(neworder, allitems, course_format_options) {

        let cutoff = parseInt(course_format_options.assessmentscutoff);

        // Move the elements in order at the beginning of the list.
        neworder.forEach((itemid, index) => {
            const item = allitems[itemid];
            let currentitem;

            // Goes into course home
            if(index === 0) {
                currentitem = this.getElement(this.selectors.HOME_CONTAINER).children[index];
                this.getElement(this.selectors.HOME_CONTAINER).insertBefore(item, currentitem);
            } else {
                //Assessments tab on
                if(course_format_options.hasassessments) {
                    // goes into lessons
                    if(index > 0 && index < cutoff) {
                        currentitem = this.getElement(this.selectors.LESSONS_CONTAINER).children[index-1];
                        if (currentitem === undefined) {
                            this.getElement(this.selectors.LESSONS_CONTAINER).append(item);
                            return;
                        }
                        if (currentitem !== item) {
                            this.getElement(this.selectors.LESSONS_CONTAINER).insertBefore(item, currentitem);
                        }
                    }
                    // goes into assessments
                    if(index >= cutoff) {
                        currentitem = this.getElement(this.selectors.ASSESSMENTS_CONTAINER).children[index-cutoff];
                        if (currentitem === undefined) {
                            this.getElement(this.selectors.ASSESSMENTS_CONTAINER).append(item);
                            return;
                        }
                        if (currentitem !== item) {
                            this.getElement(this.selectors.ASSESSMENTS_CONTAINER).insertBefore(item, currentitem);
                        }
                    }
                } else {
                    //Assessments tab off everything else goes to lessons
                    currentitem = this.getElement(this.selectors.LESSONS_CONTAINER).children[index-1];
                    if (currentitem === undefined) {
                        this.getElement(this.selectors.LESSONS_CONTAINER).append(item);
                        return;
                    }
                    if (currentitem !== item) {
                        this.getElement(this.selectors.LESSONS_CONTAINER).insertBefore(item, currentitem);
                    }
                }
            }
        });
    }
}
