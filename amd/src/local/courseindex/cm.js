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
 * Course index cm component.
 *
 * This component is used to control specific course modules interactions like drag and drop.
 *
 * @module     format_compass/local/courseindex/cm
 * @class      format_compass/local/courseindex/cm
 * @copyright  2023 KnowledgeOne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_courseformat/local/courseindex/cm';
import Prefetch from 'core/prefetch';
import Config from 'core/config';

// Prefetch the completion icons template.
const completionTemplate = 'core_courseformat/local/courseindex/cmcompletion';
Prefetch.prefetchTemplate(completionTemplate);

export default class compassComponent extends Component {

    /**
     * Static method to create a component instance form the mustache template.
     *
     * @param {element|string} target the DOM main element or its ID
     * @param {object} selectors optional css selector overrides
     * @return {compassComponent}
     */
    static init(target, selectors) {
        return new compassComponent({
            element: document.getElementById(target),
            selectors,
        });
    }

    /**
     * Initial state ready method.
     *
     * @param {Object} state the course state.
     */
    stateReady(state) {
        this.configDragDrop(this.id);
        const cm = state.cm.get(this.id);
        const course = state.course;
        // Refresh completion icon.
        this._refreshCompletion({
            state,
            element: cm,
        });
        const url = new URL(window.location.href);
        const anchor = url.hash.replace('#', '');
        // Check if the current url is the cm url.
        if (window.location.href == cm.url
            || (window.location.href.includes(course.baseurl) && anchor == cm.anchor)
        ) {
            this.reactive.dispatch('setPageItem', 'cm', this.id);
            this.element.scrollIntoView({block: "center"});
        }
        // Check if this we are displaying this activity page.
        if (Config.contextid != Config.courscompasstextId && Config.contextInstanceId == this.id) {
            this.reactive.dispatch('setPageItem', 'cm', this.id, true);
            this.element.scrollIntoView({block: "center"});
        }
        // Add anchor logic if the element is not user visible.
        if (!cm.uservisible) {
            this.addEventListener(
                this.getElement(this.selectors.CM_NAME),
                'click',
                this._activityAnchor,
            );
        }
        //Display sectiondivider while keeping labels hidden
        if(cm.module == 'sectiondivider') {
            this.element.classList.remove('d-flex-noedit');
            this.element.classList.add('d-flex');
        } else {
            this.element.classList.add('not-divider');
        }
    }
}
