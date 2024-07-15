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
 * @module     format_compass/local/content
 * @class      format_compass/local/content
 * @copyright  2023 KnowledgeOne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_courseformat/local/content';
import compassActions from 'format_compass/local/content/actions';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import Mutations from "format_compass/local/courseeditor/mutations";
import Exporter from "format_compass/local/courseeditor/exporter";
import * as CourseEvents from 'core_course/events';
import Section from 'format_compass/local/content/section';
import CmItem from 'core_courseformat/local/content/section/cmitem';
import {get_string as getString} from 'core/str';
import jQuery from 'jquery';

export default class compassComponent extends Component {
     /**
     * Static method to create a component instance form the mustahce template.
     *
     * @param {string} target the DOM main element or its ID
     * @param {object} selectors optional css selector overrides
     * @param {number} sectionReturn the content section return
     * @return {Component}
     */

     static init(target, selectors, sectionReturn) {

        const courseEditor = getCurrentCourseEditor();
        courseEditor.getExporter = () => new Exporter(courseEditor);

        // Hack to preserve legacy mutations (added in core_course/actions) after we set own plugin mutations.
        let legacyActivityAction = courseEditor.mutations.legacyActivityAction ?? {};
        let legacySectionAction = courseEditor.mutations.legacySectionAction ?? {};
        courseEditor.setMutations(new Mutations());
        courseEditor.addMutations({legacyActivityAction, legacySectionAction});

        return new compassComponent({
            element: document.getElementById(target),
            reactive: getCurrentCourseEditor(),
            selectors,
            sectionReturn,
        });
    }

    /**
     * Initial state ready method.
     *
     * @param {Object} state the state data
     */
    stateReady(state) {
        this._indexContents();
        // Activate section togglers.
        this.addEventListener(this.element, 'click', this._sectionTogglers);

        // Collapse/Expand all sections button.
        const toogleAll = this.getElement(this.selectors.TOGGLEALL);
        if (toogleAll) {

            // Ensure collapse menu button adds aria-controls attribute referring to each collapsible element.
            const collapseElements = this.getElements(this.selectors.COLLAPSE);
            const collapseElementIds = [...collapseElements].map(element => element.id);
            toogleAll.setAttribute('aria-controls', collapseElementIds.join(' '));

            this.addEventListener(toogleAll, 'click', this._allSectionToggler);
            this.addEventListener(toogleAll, 'keydown', e => {
                // Collapse/expand all sections when Space key is pressed on the toggle button.
                if (e.key === ' ') {
                    this._allSectionToggler(e);
                }
            });
            this._refreshAllSectionsToggler(state);
        }

        if (this.reactive.supportComponents) {
            // Actions are only available in edit mode.
            if (this.reactive.isEditing) {
                new compassActions(this);
            }

            // Mark content as state ready.
            this.element.classList.add(this.classes.STATEDREADY);
        }

        // Capture completion events.
        this.addEventListener(
            this.element,
            CourseEvents.manualCompletionToggled,
            this._completionHandler
        );

        // Capture page scroll to update page item.
        this.addEventListener(
            document.querySelector(this.selectors.PAGE),
            "scroll",
            this._scrollHandler
        );
    }

    /**
     * Override from course/format/amd/src/local/content.js
     * Update section collapsed state via bootstrap 4 if necessary.
     *
     * Formats that do not use bootstrap 4 must override this method in order to keep the section
     * toggling working.
     *
     * @param {object} args
     * @param {Object} args.state The state data
     * @param {Object} args.element The element to update
     */
   async _refreshSectionCollapsed({state, element}) {
        const target = this.getElement(this.selectors.SECTION, element.id);
        // Check if the section is available, avoid throwing javascript errors for sections from other tabs
        if (target) {
            // Check if it is already done.
            const toggler = target.querySelector(this.selectors.COLLAPSE);
            const isCollapsed = toggler?.classList.contains(this.classes.COLLAPSED) ?? false;

            if (element.contentcollapsed !== isCollapsed) {
                let collapsibleId = toggler.dataset.target ?? toggler.getAttribute("href");
                if (!collapsibleId) {
                    return;
                }
                collapsibleId = collapsibleId.replace('#', '');
                const collapsible = document.getElementById(collapsibleId);
                if (!collapsible) {
                    return;
                }

                // Course index is based on Bootstrap 4 collapsibles. To collapse them we need jQuery to
                // interact with collapsibles methods. Hopefully, this will change in Bootstrap 5 because
                // it does not require jQuery anymore (when MDL-71979 is integrated).
                jQuery(collapsible).collapse(element.contentcollapsed ? 'hide' : 'show');
            }

            if(target.querySelector(".compass-assessments-seemore")) {
                var seemore = await getString('assessments_seemore', 'format_compass');

                if(isCollapsed) {
                    target.querySelector(".compass-assessments-seemore").innerHTML = '';
                    target.querySelector(".compass-assessments-seemore").blur();
                    target.querySelector(".compass-assessments-content-container").classList.remove(this.classes.COLLAPSED);
                } else {
                    target.querySelector(".compass-assessments-seemore").innerHTML = seemore;
                    target.querySelector(".compass-assessments-content-container").classList.add(this.classes.COLLAPSED);
                }
            }

            this._refreshAllSectionsToggler(state);
        }
    }

    /**
     * Return the component watchers.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        // Section return is a global page variable but most formats define it just before start printing
        // the course content. This is the reason why we define this page setting here.
        this.reactive.sectionReturn = this.sectionReturn;

        // Check if the course format is compatible with reactive components.
        if (!this.reactive.supportComponents) {
            return [];
        }
        return [
            // State changes that require to reload some course modules.
            {watch: `cm.visible:updated`, handler: this._reloadCm},
            {watch: `cm.stealth:updated`, handler: this._reloadCm},
            {watch: `cm.indent:updated`, handler: this._reloadCm},
            // Update section number and title.
            {watch: `section.number:updated`, handler: this._refreshSectionNumber},
            // Collapse and expand sections.
            {watch: `section.contentcollapsed:updated`, handler: this._refreshSectionCollapsed},
            // Sections and cm sorting.
            {watch: `transaction:start`, handler: this._startProcessing},
            {watch: `course.sectionlist:updated`, handler: this._refreshCourseSectionlist},
            {watch: `section.cmlist:updated`, handler: this._refreshSectionCmlist},
            // Reindex sections and cms.
            {watch: `state:updated`, handler: this._indexContents},
            // State changes thaty require to reload course modules.
            {watch: `cm.visible:updated`, handler: this._reloadCm},
            {watch: `cm.sectionid:updated`, handler: this._reloadCm},
            // Check section limit.
            {watch: `course_format_options.assessmentscutoff:updated`, handler: this._refreshCourseSectionlist},
        ];
    }

    /**
     * Regenerate content indexes.
     *
     * This method is used when a legacy action refresh some content element.
     */
    _indexContents() {
        // Find unindexed sections.
        this._scanIndex(
            this.selectors.SECTION,
            this.sections,
            (item) => {
                return new Section(item);
            }
        );

        // Find unindexed cms.
        this._scanIndex(
            this.selectors.CM,
            this.cms,
            (item) => {
                return new CmItem(item);
            }
        );
    }

    /**
     * Check the section list and disable some options if nee ded.
     *
     * @param {Object} detail the update details.
     * @param {Object} detail.state the state object.
     */
    _refreshCourseSectionlist({state}) {
        // If we have a section return means we only show a single section so no need to fix order.
        if (this.reactive.sectionReturn != 0) {
            return;
        }

        //Get tab param
        const urlParams = new URLSearchParams(window.location.href);
        const tab = urlParams.get('tab');
        let sectionlist = [];
        //If assessments tab is enabled
        if(state.course_format_options.hasassessments) {
            //Only list items from within the active tab lessons/assessments
            if(tab.includes('lessons')) {
                state.course.sectionlist.forEach((section, index) => {
                    if(index < parseInt(state.course_format_options.assessmentscutoff)) {
                        sectionlist.push(section);
                    }
                });
            } else if(tab.includes('assessments')) {
                state.course.sectionlist.forEach((section, index) => {
                    if(index >= parseInt(state.course_format_options.assessmentscutoff)) {
                        sectionlist.push(section);
                    }
                });
            } else {
                sectionlist.push(state.course.sectionlist[0]);
            }
        } else {
            // Assessments tab turned off
            if(!tab.includes("lessons")) {
                //Do not reorder when on the course home tab
                return;
            } else {
                sectionlist = state.course.sectionlist ?? [];
            }
        }

        if(tab.includes('assessments')) {
            sectionlist.forEach((section) => {
                if(window["chartUpdateData" + section]) {
                    window["chartUpdateData" + section](null, sectionlist);
                }
            });
        }

        const listparent = this.getElement(this.selectors.COURSE_SECTIONLIST);
        // For now section cannot be created at a frontend level.
        const createSection = this._createSectionItem.bind(this);
        if (listparent) {
            this._fixOrder(listparent, sectionlist, this.selectors.SECTION, this.dettachedSections, createSection);
        }
    }

    /**
     * Refresh the collapse/expand all sections element.
     *
     * @param {Object} state The state data
     */
      _refreshAllSectionsToggler(state) {
        const target = this.getElement(this.selectors.TOGGLEALL);
        if (!target) {
            return;
        }
        // Check if we have all sections collapsed/expanded.
        let allcollapsed = true;
        let allexpanded = true;
        const urlParams = new URLSearchParams(window.location.href);
        const tab = urlParams.get('tab');
        let cutoff = parseInt(state.course_format_options.assessmentscutoff);
        state.section.forEach(
            section => {
                let secnum = section.section;
                if (tab == 'assessments') {
                    if (secnum >= cutoff) {
                        allcollapsed = allcollapsed && section.contentcollapsed;
                        allexpanded = allexpanded && !section.contentcollapsed;
                    }
                } else {
                    if (secnum < cutoff) {
                        allcollapsed = allcollapsed && section.contentcollapsed;
                        allexpanded = allexpanded && !section.contentcollapsed;
                    }
                }
            }
        );

        if (allcollapsed) {
            target.classList.add(this.classes.COLLAPSED);
            target.setAttribute('aria-expanded', false);
        }
        if (allexpanded) {
            target.classList.remove(this.classes.COLLAPSED);
            target.setAttribute('aria-expanded', true);
        }
    }
}