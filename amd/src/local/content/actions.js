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
 * Course state actions dispatcher.
 *
 * This module captures all data-dispatch links in the course content and dispatch the proper
 * state mutation, including any confirmation and modal required.
 *
 * @module     format_compass/local/content/actions
 * @class      format_compass/local/content/actions
 * @copyright  2023 Knowledgeone inc. <https://knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


import {BaseComponent} from 'core/reactive';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import ModalForm from 'core_form/modalform';
import Templates from 'core/templates';
import {prefetchStrings} from 'core/prefetch';
import {get_string as getString, get_strings as getStrings} from 'core/str';
import {getList} from 'core/normalise';
import * as CourseEvents from 'core_course/events';
import Pending from 'core/pending';
import ContentTree from 'core_courseformat/local/courseeditor/contenttree';
// The jQuery module is only used for interacting with Boostrap 4. It can we removed when MDL-71979 is integrated.
import jQuery from 'jquery';
import ajax from 'core/ajax';
import courseActions from 'core_course/actions';
import Chart from 'format_compass/local/vendors/chart';
import {init as chartInit} from 'format_compass/local/assessments_wheels';

// Load global strings.
prefetchStrings('core', ['movecoursesection', 'movecoursemodule', 'confirm', 'delete']);

// Mutations are dispatched by the course content actions.
// Formats can use this module addActions static method to add custom actions.
// Direct mutations can be simple strings (mutation) name or functions.
const directMutations = {
    sectionHide: 'sectionHide',
    sectionShow: 'sectionShow',
    cmHide: 'cmHide',
    cmShow: 'cmShow',
    cmStealth: 'cmStealth',
    cmMoveRight: 'cmMoveRight',
    cmMoveLeft: 'cmMoveLeft',
};

export default class extends BaseComponent {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'content_actions';
        // Default query selectors.
        this.selectors = {
            ACTIONLINK: `[data-action]`,
            // Move modal selectors.
            SECTIONLINK: `[data-for='section']`,
            CMLINK: `[data-for='cm']`,
            SECTIONNODE: `[data-for='sectionnode']`,
            MODALTOGGLER: `[data-toggle='collapse']`,
            ADDSECTION: `[data-action='addSection']`,
            CONTENTTREE: `#destination-selector`,
            ACTIONMENU: `.action-menu`,
            ACTIONMENUTOGGLER: `[data-toggle="dropdown"]`,
        };
        // Component css classes.
        this.classes = {
            DISABLED: `text-body`,
            ITALIC: `font-italic`,
        };
    }

    /**
     * Add extra actions to the module.
     *
     * @param {array} actions array of methods to execute
     */
    static addActions(actions) {
        for (const [action, mutationReference] of Object.entries(actions)) {
            if (typeof mutationReference !== 'function' && typeof mutationReference !== 'string') {
                throw new Error(`${action} action must be a mutation name or a function`);
            }
            directMutations[action] = mutationReference;
        }
    }

    /**
     * Initial state ready method.
     *
     * @param {Object} state the state data.
     *
     */
    stateReady(state) {
        // Delegate dispatch clicks.
        this.addEventListener(
            this.element,
            'click',
            this._dispatchClick
        );
        // Check section limit.
        this._checkSectionlist({state});
        // Add an Event listener to recalculate limits it if a section HTML is altered.
        this.addEventListener(
            this.element,
            CourseEvents.sectionRefreshed,
            () => this._checkSectionlist({state})
        );
    }

    /**
     * Return the component watchers.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        return [
            // Check section limit.
            {watch: `course.sectionlist:updated`, handler: this._checkSectionlist},
            {watch: `section.sectionweight:updated`, handler: this._updateSectionWeight},
        ];
    }

    /**
     * Compile the chart data for all sections
     *
     * @param {Object} detail
     * @param {Object} detail.state the state data.
     * @returns {Array} of all the charts data.
     */
    async _getAllChartData({state}) {
        const course_format_options = await this.getCourseFormatOptions(state.course.id);
        var assessmentscutoff = course_format_options.assessmentscutoff;
        var chartsData = [];


        // Get the chart values for each sections.
        state.section.forEach((section) => {

            // Only for assessments.
            if(parseInt(section.number) >= parseInt(assessmentscutoff)) {
                // Only for graded.
                if(section.sectionweight.type_of_grade_weighting == 'weighttype_weighted') {
                    let chart = {};
                    chart.chartValue = '';
                    chart.chartColor = '';
                    chart.chartKey = '';

                    var percentagetotal = 0;
                    // Single.
                    if(section.sectionweight.weighttype_singlemultiple == 'weighttype_singlemultiple_single') {
                        percentagetotal = section.sectionweight.weighttype_singlevalue_weightvalue;
                        chart.chartValue = percentagetotal;
                    }
                    // Multiplier.
                    if(section.sectionweight.weighttype_singlemultiple == 'weighttype_singlemultiple_multiplier') {
                        percentagetotal = section.sectionweight.weighttype_multiplier_weightvalue * section.sectionweight.weighttype_multiplier_multipliervalue;
                        chart.chartValue = percentagetotal;
                    }
                    // CSV.
                    if(section.sectionweight.weighttype_singlemultiple == 'weighttype_singlemultiple_csv') {
                        var csvValues = section.sectionweight.weighttype_multiplier_csvvalue.replace(' ', '');
                        csvValues = csvValues.split(',');
                        csvValues.forEach((value) => {
                            percentagetotal += parseInt(value);
                        });
                        chart.chartValue = percentagetotal;
                    }

                    // chartKeys will help identifying the chart values.
                    chart.chartKey = section.id.toString();
                    // Set all the colors to the default bg color, the active color will be changed in assessments_wheels.js.
                    chart.chartColor = getComputedStyle(document.body).getPropertyValue('--weight_wheel_bg_colour');
                    chartsData.push(chart);
                }
            }
        });

        // Compile the total percentage.
        var newChartFillTotal = 0;
        chartsData.forEach((item) => {
            newChartFillTotal += parseInt(item.chartValue);
        });

        // If total percentage is less than 100, push a filler value and set it's color to disabled.
        if(newChartFillTotal < 100) {
            let chart = {};
            chart.chartValue = '';
            chart.chartKey = 'fill';
            chart.chartColor = '';
            chart.chartValue = 100-newChartFillTotal;
            chart.chartColor = getComputedStyle(document.body).getPropertyValue('--weight_wheel_disabled_colour');
            chartsData.push(chart);
        }
        return chartsData;
    }

    /**
     * Update all the assessments weightwheels chart.
     *
     * @param {Object} detail
     * @param {Object} detail.state the state data.
     * @param {array} chartData data for the chart.
     */
    async _updateAllCharts({state}, chartData) {
        const course_format_options = await this.getCourseFormatOptions(state.course.id);
        var assessmentscutoff = course_format_options.assessmentscutoff;
        state.section.forEach((section) => {
            if(parseInt(section.number) >= parseInt(assessmentscutoff)) {
                if(section.sectionweight.type_of_grade_weighting == 'weighttype_weighted') {
                    window['chartUpdateData' + section.id](chartData, state.course.sectionlist);
                }
            }
        });
    }

    /**
     * Re-render weightwheels templates
     *
     * @param {Object} detail
     * @param {Object} detail.state the state data.
     * @param {Object} detail.element the element data.
     */
    async _updateSectionWeight({state,element}) {
        // Re-render ungraded.
        if(element.sectionweight.type_of_grade_weighting == 'weighttype_ungraded') {
            Templates.renderForPromise('format_compass/local/assessments/weightwheels/ungraded', {'id' : element.id, 'editing' : true}).then(({html}) => {
                document.getElementById("assessment" + element.id + "-weight-container").innerHTML = html;
            });

            // Update all the charts.
            chartData = await this._getAllChartData({state, element});
            this._updateAllCharts({state},chartData);
        }

        // Re-render bonus.
        if(element.sectionweight.type_of_grade_weighting == 'weighttype_bonus') {
            var bonuspercent;
            await getString('assessments_chart_percent', 'format_compass', element.sectionweight.weighttype_bonus_value).then(str => {
                bonuspercent = str;
            });
            Templates.renderForPromise('format_compass/local/assessments/weightwheels/bonus', {'id' : element.id, 'editing' : true, 'bonuspercent' : bonuspercent}).then(({html}) => {
                document.getElementById("assessment" + element.id + "-weight-container").innerHTML = html;
            });

            // Update all the charts.
            chartData = await this._getAllChartData({state, element});
            this._updateAllCharts({state},chartData);
        }

        // Re-render graded
        if(element.sectionweight.type_of_grade_weighting == 'weighttype_weighted') {
            var chartData;
            var chartPercentBreakdown = '';
            var chartPercent = '';

            // Single.
            if(element.sectionweight.weighttype_singlemultiple == 'weighttype_singlemultiple_single') {
                await getString('assessments_chart_percent', 'format_compass', element.sectionweight.weighttype_singlevalue_weightvalue).then(str => {
                    chartPercent = str;
                });
            }

            // Multiplier.
            if(element.sectionweight.weighttype_singlemultiple == 'weighttype_singlemultiple_multiplier') {
                var percentagetotal = element.sectionweight.weighttype_multiplier_weightvalue * element.sectionweight.weighttype_multiplier_multipliervalue;
                await getString('assessments_chart_percent', 'format_compass', percentagetotal).then(str => {
                    chartPercent = str;
                });

                await getString('assessments_chart_multiplier', 'format_compass', element.sectionweight).then(str => {
                    chartPercentBreakdown = str;
                });
            }

            // CSV.
            if(element.sectionweight.weighttype_singlemultiple == 'weighttype_singlemultiple_csv') {
                var totalweight = 0;
                var csvValues = element.sectionweight.weighttype_multiplier_csvvalue.replace(/ /g, '').split(',');

                csvValues.forEach((value) => {
                    totalweight += parseInt(value);
                });

                chartPercent = await getString('assessments_chart_percent', 'format_compass', totalweight);
                chartPercentBreakdown = '(' + element.sectionweight.weighttype_multiplier_csvvalue.replace(/,/g, '+').replace(/ /g, '') + ')';
            }

            // Update all the charts.
            chartData = await this._getAllChartData({state, element});
            this._updateAllCharts({state},chartData);

            let chartValues = [];
            let chartKeys = [];
            let chartColors = [];

            chartData.forEach((item,i) => {
                chartValues.push(item.chartValue);
                chartKeys.push(item.chartKey);
                chartColors.push(item.chartColor);
            });

            // Create a new chart.
            Templates.renderForPromise('format_compass/local/assessments/weightwheels/graded', {'id' : element.id, 'editing' : true, 'chartpercent' : chartPercent}).then(({html}) => {
                document.getElementById("assessment" + element.id + "-weight-container").innerHTML = html;
                var canvasId = 'weightwheel' + element.id;
                chartInit(canvasId,chartValues.toString(),chartColors.toString(),chartKeys.toString(),element.id);
                document.querySelector("#assessment" + element.id + "-weight-container .compass-weightwheel-percent-breakdown-edit").innerHTML = chartPercentBreakdown;
            });

        }
    }

    _dispatchClick(event) {
        const target = event.target.closest(this.selectors.ACTIONLINK);
        if (!target) {
            return;
        }
        if (target.classList.contains(this.classes.DISABLED)) {
            event.preventDefault();
            return;
        }

        // Invoke proper method.
        const actionName = target.dataset.action;
        const methodName = this._actionMethodName(actionName);

        if (this[methodName] !== undefined) {
            this[methodName](target, event);
            return;
        }

        // Check direct mutations or mutations handlers.
        if (directMutations[actionName] !== undefined) {
            if (typeof directMutations[actionName] === 'function') {
                directMutations[actionName](target, event);
                return;
            }
            this._requestMutationAction(target, event, directMutations[actionName]);
            return;
        }
    }

     /**
     * Section renaming modal
     * @param {Object} target clicked DOM element.
     * @param {Object} event click event.
     */
    async _requestRenameSection(target, event) {
        event.preventDefault();
        const state = JSON.parse(JSON.stringify(this.reactive.state));
        const courseId = state.course.id;
        const sectionId = target.getAttribute('data-id');
        const sectionInfo = this.reactive.get('section', sectionId);
        const urlParams = new URLSearchParams(window.location.href);
        const tab = urlParams.get('tab');

        const modalForm = new ModalForm({
            formClass: 'format_compass\\form\\renamesection_form',
            args: {
                courseid: courseId,
                id: sectionId,
                title: sectionInfo.title,
                num: sectionInfo.number,
                tab: tab
            },
            modalConfig: {
                title: sectionInfo.title,
            },
        });
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
            // Dispatch the renaming so the page doesn't need to be refreshed
            this.reactive.dispatch('sectionRenameState', e.detail.data);
        });
        modalForm.addEventListener("change", (e) => {
            if(e.target!==null && (e.target.id == "id_sectionname_type" || e.target.className.includes("custom-select")))
            {
                // Custom checkbox toggle handler
                this.SetCustomSectionName();
            }
        });
        // Show the form.
        modalForm.show();

    }

    async _requestEditGradeWeight(target, event) {
        event.preventDefault();
        const state = JSON.parse(JSON.stringify(this.reactive.state));
        const courseId = state.course.id;
        const sectionId = target.getAttribute('data-id');
        const sectionInfo = this.reactive.get('section', sectionId);
        const urlParams = new URLSearchParams(window.location.href);
        const tab = urlParams.get('tab');
        const modalForm = new ModalForm({
            formClass: 'format_compass\\form\\editgradeweight_form',
            args: {
                courseid: courseId,
                id: sectionId,
                name: sectionInfo.title,
                num: sectionInfo.number,
                tab: tab
            },
            modalConfig: {
                title: sectionInfo.title,
            },
        });
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
            // Dispatch the renaming so the page doesn't need to be refreshed
            this.reactive.dispatch('sectionEditGradeWeight', e.detail.data);
        });
        modalForm.addEventListener("change", (e) => {
            if(e.target!==null && e.target.id == "id_weighttype_multiplier_csvvalue") {
                this.SetCSVPreviewValue();
            } else if (e.target!==null && (e.target.id == "id_weighttype_multiplier_weightvalue" ||
                                        e.target.id == "id_weighttype_multiplier_multipliervalue")) {
                this.SetMultiplierPreviewValue();
            } else if (e.target!==null && (e.target.id == "id_weighttype_singlemultiple")) {
                this.SetSingleMultiplePreviewValue();
            } else if (e.target!==null && (e.target.id == "id_type_of_grade_weighting")) {
                this.SetTypeOfGradeWeightingValue();
            }
        });

        // Show the form.
        modalForm.show();
    }

    SetCSVPreviewValue() {
        var totalValue = this.CalculateCSVPreviewValue();
        this.SetPreviewValue(totalValue);
    }

    SetMultiplierPreviewValue() {
        var totalValue = this.CalculateMultiplierPreviewValue();
        this.SetPreviewValue(totalValue);
    }

    SetSingleMultiplePreviewValue() {
        var weighttype_singlemultiple = document.getElementById('id_weighttype_singlemultiple').value;
        if (weighttype_singlemultiple == "weighttype_singlemultiple_csv") {
            this.SetCSVPreviewValue();
        } else if (weighttype_singlemultiple == "weighttype_singlemultiple_multiplier") {
            this.SetMultiplierPreviewValue();
        }
    }

    SetTypeOfGradeWeightingValue() {
        var type_of_grade_weighting = document.getElementById('id_type_of_grade_weighting').value;
        if (type_of_grade_weighting == "weighttype_weighted") {
            this.SetSingleMultiplePreviewValue();
        }
    }

    /**
     * Updates the 'section name' input according to the user selection in the modalform
     */
    SetCustomSectionName() {
        var sectionName = document.querySelector('[tag="id_sectionname"]');
        var first_termvalue = document.getElementById('id_sectionname_first_term').selectedOptions[0].text;
        var first_numbervalue = document.getElementById('id_sectionname_first_number').selectedOptions[0].text;
        var second_termvalue = document.getElementById('id_sectionname_second_term').selectedOptions[0].text;
        var second_numbervalue = document.getElementById('id_sectionname_second_number').selectedOptions[0].text;
        var sectionname_typevalue = document.getElementById('id_sectionname_type').value;

        if (sectionname_typevalue != "sectionname_freetype") {
            if (sectionname_typevalue == "sectionname_doubleterm_doublenumber") {
                sectionName.value = first_termvalue + " " + first_numbervalue + " - " + second_termvalue + " " + second_numbervalue;
            } else if (sectionname_typevalue == "sectionname_singleterm_doublenumber") {
                sectionName.value = first_termvalue + " " + first_numbervalue + "." + second_numbervalue;
            } else if (sectionname_typevalue == "sectionname_singleterm_singlenumber") {
                sectionName.value = first_termvalue + " " + first_numbervalue;
            }
        }
    }

    _actionMethodName(name) {
        const requestName = name.charAt(0).toUpperCase() + name.slice(1);
        return `_request${requestName}`;
    }

    /**
     * Calculate CSVPreviewValue from csv string on change function
     */
    CalculateCSVPreviewValue() {
        var weighttype_multiplier_csvvalue = document.getElementById('id_weighttype_multiplier_csvvalue').value;
        var weighttype_multiplier_csvarray = weighttype_multiplier_csvvalue.split(',');
        var totalValue = 0.0;
        var breakdown = '';
        weighttype_multiplier_csvarray.forEach((value, i) => {
            totalValue += parseFloat(value);
            if (i < weighttype_multiplier_csvarray.length-1) {
                breakdown += value + '+';
            } else {
                breakdown += value;
            }
        });
        if (!isNaN(totalValue)) {
            var result = (totalValue - Math.floor(totalValue)) !== 0;
            if (result) {
                totalValue = totalValue.toFixed(2) + "%" + " ("  + breakdown.replace(/\s/g, "") + ")";
            } else {
                totalValue = totalValue + "%" + " ("  + breakdown.replace(/\s/g, "") + ")";
            }
        } else {
            totalValue= "N/A";
        }

        return totalValue;
    }

    /**
     * Calculate MultiplierPreviewValue from weightValue and multiplier on change function
     */
    CalculateMultiplierPreviewValue() {
        var weighttype_multiplier_weightvalue =
            document.getElementById('id_weighttype_multiplier_weightvalue').value;
        var weighttype_multiplier_multipliervalue =
            document.getElementById('id_weighttype_multiplier_multipliervalue').value;
        if (weighttype_multiplier_weightvalue && weighttype_multiplier_multipliervalue) {
            var totalValue = weighttype_multiplier_weightvalue * weighttype_multiplier_multipliervalue;
            if (!isNaN(totalValue)) {
                var result = (totalValue - Math.floor(totalValue)) !== 0;
                if (result) {
                    totalValue = totalValue.toFixed(2);
                }
                var breakdown = weighttype_multiplier_multipliervalue + " x " +  weighttype_multiplier_weightvalue + "%";
                totalValue += "%" + " (" + breakdown + ")";
            } else {
                totalValue= "N/A";
            }
        } else {
            totalValue= "N/A";
        }
        return totalValue;
    }

    /**
     * Set the preview field based on totalvalue variable
     * @param {*} totalValue totalvalue that will show in preview field
     */
    SetPreviewValue(totalValue){
        var static_group = document.querySelector("[data-groupname=staticgroup] [data-fieldtype=group]");
        static_group.innerHTML =  totalValue;
    }

    /**
     * Check the section list and disable some options if needed.
     *
     * @param {Object} detail the update details.
     * @param {Object} detail.state the state object.
     */
    _checkSectionlist({state}) {
        // Disable "add section" actions if the course max sections has been exceeded.
        this._setAddSectionLocked(state.course.sectionlist.length > state.course.maxsections);
    }

    /**
     * Get the course format options
     *
     * @param {String} courseid
     */
    async getCourseFormatOptions(courseid) {
        const args = {};
        args.courseid = courseid;

       //Call to refresh course format options
       let ajaxresult;
       ajaxresult = await ajax.call([{
           methodname: 'core_courseformat_get_state',
           args,
       }])[0];

       //Call to get course format options
       return JSON.parse(ajaxresult).course_format_options;
    }

    /**
     * Get the strings for Lessons and Assessments
     *
     * @param {Object} course_format_options
     */
    async getCutoffStrings(course_format_options) {
        const labels = [
            {
                key: course_format_options.labelforlessonscutoff,
                component: 'format_compass'
            },
            {
                key: course_format_options.labelforassessmentscutoff,
                component: 'format_compass'
            }
        ];

        let strings = await getStrings(labels, 'format_compass');

        return strings;
    }

    /**
     * Handle a move section request.
     *
     * @param {Element} target the dispatch action element
     * @param {Event} event the triggered event
     */
    async _requestMoveSection(target, event) {
        // Check we have an id.
        const sectionId = target.dataset.id;
        if (!sectionId) {
            return;
        }
        const sectionInfo = this.reactive.get('section', sectionId);

        event.preventDefault();

        const pendingModalReady = new Pending(`courseformat/actions:prepareMoveSectionModal`);

        // The section edit menu to refocus on end.
        const editTools = this._getClosestActionMenuToogler(target);

        // Collect section information from the state.
        const exporter = this.reactive.getExporter();
        const data = exporter.course(this.reactive.state);
        const state = JSON.parse(JSON.stringify(this.reactive.state));

        //Call to get course format options
        const course_format_options = await this.getCourseFormatOptions(state.course.id);

        // Labels for course format options
        const labels = await this.getCutoffStrings(course_format_options);

        data.lessonsLabel = labels[0]; //Lessons
        data.assessmentsLabel = labels[1]; //Assessments

        data.hasassessments = course_format_options.hasassessments;
        data.cutoff = course_format_options.assessmentscutoff;

        // Add the target section id and title.
        data.sectionid = sectionInfo.id;
        data.sectiontitle = sectionInfo.title;

        data.coursehome = new Object();
        data.assessments = new Array();

        //Cutoff cannot be 0
        if(data.cutoff == "0") {
            data.cutoff = data.sections.length;
        }

        // Split sections into objects to make the Assessments cut-off
        for(var i = data.sections.length -1; i > -1; i--) {
            if (data.sections[i].number >= data.cutoff) {
                data.sections[i].type = "assessment";
                //Only if assessments are enabled
                if(course_format_options.hasassessments) {
                    data.assessments.push(data.sections[i]);
                    data.sections.splice(i, 1);
                }
            } else {
                data.sections[i].type = "lesson";
            }
        }
        data.assessments.reverse();

        // Isolate course home
        data.coursehome = data.sections[0];
        data.sections.splice(0, 1);

        // Build the modal parameters from the event data.
        const modalParams = {
            title: getString('movecoursesection', 'core'),
            body: Templates.render('format_compass/local/content/movesection', data),
        };

        // Create the modal.
        const modal = await this._modalBodyRenderedPromise(modalParams);

        const modalBody = getList(modal.getBody())[0];

        // Disable current element and section zero.
        const currentElement = modalBody.querySelector(`${this.selectors.SECTIONLINK}[data-id='${sectionId}']`);
        this._disableLink(currentElement);
        const generalSection = modalBody.querySelector(`${this.selectors.SECTIONLINK}[data-number='0']`);
        this._disableLink(generalSection);

        // Setup keyboard navigation.
        new ContentTree(
            modalBody.querySelector(this.selectors.CONTENTTREE),
            {
                SECTION: this.selectors.SECTIONNODE,
                TOGGLER: this.selectors.MODALTOGGLER,
                COLLAPSE: this.selectors.MODALTOGGLER,
            },
            true
        );

        // Capture click.
        modalBody.addEventListener('click', (event) => {
            const target = event.target;
            let offset = 0;

            // Check if the Section is moving within or outside of the assessments
            if(currentElement.dataset.type != "assessment" && target.dataset.type == "assessment") {
                offset--;
            } else if(currentElement.dataset.type == "assessment" && target.dataset.type != "assessment") {
                offset++;
            }

            let cutoffs = [{cutoff: [{ assessment: parseInt(data.cutoff) + offset }]}];

            if (!target.matches('a') || target.dataset.id === undefined) {
                return;
            }
            if (target.getAttribute('aria-disabled')) {
                return;
            }
            event.preventDefault();

            // Targets Assessments Header
            if(target.dataset.id != "assessments_header") {
                this.reactive.dispatch('sectionMove', [sectionId], target.dataset.id, cutoffs);
            } else {
                // If currentElement is an assessment
                if(parseInt(currentElement.dataset.number) >= data.cutoff) {
                    cutoffs[0].cutoff[0].assessment++;
                    // If sections isn't empty
                    if(data.sections[0] || parseInt(currentElement.dataset.number) > 1) {
                        // Only save cutoffs if no move is necessary
                        if(data.assessments[0].id == currentElement.dataset.id) {
                            this.reactive.dispatch('saveCutoffs', cutoffs[0].cutoff[0].assessment);
                        } else {
                            this.reactive.dispatch('sectionMove', [sectionId], data.assessments[0].id, cutoffs);
                        }
                    } else {
                        this.reactive.dispatch('sectionMove', [sectionId], data.coursehome.id, cutoffs);
                    }
                } else {
                    // Checks if a move is necessary
                    if(data.sections.length == 1 || data.sections[data.sections.length-1].id == currentElement.dataset.id) {
                        this.reactive.dispatch('saveCutoffs', cutoffs[0].cutoff[0].assessment);
                    } else {
                        this.reactive.dispatch('sectionMove', [sectionId], data.sections[data.sections.length-1].id, cutoffs);
                    }
                }
            }
            this._destroyModal(modal, editTools);
        });

        pendingModalReady.resolve();
    }

    /**
     * Handle a move cm request.
     *
     * @param {Element} target the dispatch action element
     * @param {Event} event the triggered event
     */
    async _requestMoveCm(target, event) {
        // Check we have an id.
        const cmId = target.dataset.id;
        if (!cmId) {
            return;
        }
        const cmInfo = this.reactive.get('cm', cmId);

        event.preventDefault();

        const pendingModalReady = new Pending(`courseformat/actions:prepareMoveCmModal`);

        // The section edit menu to refocus on end.
        const editTools = this._getClosestActionMenuToogler(target);

        // Collect section information from the state.
        const exporter = this.reactive.getExporter();
        const data = exporter.course(this.reactive.state);
        const state = JSON.parse(JSON.stringify(this.reactive.state));

        //Call to get course format options
        const course_format_options = await this.getCourseFormatOptions(state.course.id);

        // Labels for course format options
        const labels = await this.getCutoffStrings(course_format_options);

        data.lessonsLabel = labels[0]; //Lessons
        data.assessmentsLabel = labels[1]; //Assessments

        data.hasassessments = course_format_options.hasassessments;
        data.cutoff = course_format_options.assessmentscutoff;

        //Cutoff cannot be 0
        if(data.cutoff == "0") {
            data.cutoff = data.sections.length;
        }

        data.coursehome = new Object();
        data.assessments = new Array();

        //Add assessments header if hasassessments is enabled
        if(course_format_options.hasassessments) {
            // Split sections into objects to make the Assessments cut-off
            for(var i = data.sections.length -1; i > -1; i--) {
                if (data.sections[i].number >= data.cutoff) {
                    data.sections[i].type = "assessment";
                    data.assessments.push(data.sections[i]);
                    data.sections.splice(i, 1);
                }
            }
            data.assessments.reverse();
        }

        // Isolate course home
        data.coursehome = data.sections[0];
        data.sections.splice(0, 1);

        // Add the target cm info.
        data.cmid = cmInfo.id;
        data.cmname = cmInfo.name;

        // Build the modal parameters from the event data.
        const modalParams = {
            title: getString('movecoursemodule', 'core'),
            body: Templates.render('format_compass/local/content/movecm', data),
        };

        // Create the modal.
        const modal = await this._modalBodyRenderedPromise(modalParams);

        const modalBody = getList(modal.getBody())[0];

        // Disable current element.
        let currentElement = modalBody.querySelector(`${this.selectors.CMLINK}[data-id='${cmId}']`);
        this._disableLink(currentElement);

        // Setup keyboard navigation.
        new ContentTree(
            modalBody.querySelector(this.selectors.CONTENTTREE),
            {
                SECTION: this.selectors.SECTIONNODE,
                TOGGLER: this.selectors.MODALTOGGLER,
                COLLAPSE: this.selectors.MODALTOGGLER,
                ENTER: this.selectors.SECTIONLINK,
            }
        );

        // Open the cm section node if possible (Bootstrap 4 uses jQuery to interact with collapsibles).
        // All jQuery int this code can be replaced when MDL-71979 is integrated.
        const sectionnode = currentElement.closest(this.selectors.SECTIONNODE);
        const toggler = jQuery(sectionnode).find(this.selectors.MODALTOGGLER);
        let collapsibleId = toggler.data('target') ?? toggler.attr('href');
        if (collapsibleId) {
            // We cannot be sure we have # in the id element name.
            collapsibleId = collapsibleId.replace('#', '');
            jQuery(`#${collapsibleId}`).collapse('toggle');
        }

        // Capture click.
        modalBody.addEventListener('click', (event) => {
            const target = event.target;
            if (!target.matches('a') || target.dataset.for === undefined || target.dataset.id === undefined) {
                return;
            }
            if (target.getAttribute('aria-disabled')) {
                return;
            }
            event.preventDefault();

            // Get draggable data from cm or section to dispatch.
            let targetSectionId;
            let targetCmId;
            if (target.dataset.for == 'cm') {
                const dropData = exporter.cmDraggableData(this.reactive.state, target.dataset.id);
                targetSectionId = dropData.sectionid;
                targetCmId = dropData.nextcmid;
            } else {
                const section = this.reactive.get('section', target.dataset.id);
                targetSectionId = target.dataset.id;
                targetCmId = section?.cmlist[0];
            }

            this.reactive.dispatch('cmMove', [cmId], targetSectionId, targetCmId);
            this._destroyModal(modal, editTools);
        });

        pendingModalReady.resolve();
    }

    /**
     * Handle a create section request.
     *
     * @param {Element} target the dispatch action element
     * @param {Event} event the triggered event
     */
    async _requestAddSection(target, event) {
        // Check we have an id.
        const cmId = target.dataset.id;
        if (!cmId) {
            return;
        }

        //Get the current tab
        const urlParams = new URLSearchParams(window.location.href);
        const tab = urlParams.get('tab');

        event.preventDefault();

        // Collect section information from the state.
        const exporter = this.reactive.getExporter();
        const data = exporter.course(this.reactive.state);
        const state = JSON.parse(JSON.stringify(this.reactive.state));

        //Call to get course format options
        const course_format_options = await this.getCourseFormatOptions(state.course.id);
        data.cutoff = course_format_options.assessmentscutoff;

        //Cutoff cannot be 0
        if(data.cutoff == "0") {
            data.cutoff = data.sections.length;
        }

        // Increment the cutoff only if in the lessons tab
        let cutoffs;
        if(tab.includes('assessments')) {
            cutoffs = [{cutoff: [{ assessment: parseInt(data.cutoff)}]}];
        } else {
            cutoffs = [{cutoff: [{ assessment: parseInt(data.cutoff) + 1 }]}];
        }

        this.reactive.dispatch('addSection', target.dataset.id ?? 0, cutoffs);
    }

    /**
     * Handle a delete section request.
     *
     * @param {Element} target the dispatch action element
     * @param {Event} event the triggered event
     */
    async _requestDeleteSection(target, event) {
        // Check we have an id.
        const sectionId = target.dataset.id;

        if (!sectionId) {
            return;
        }
        const sectionInfo = this.reactive.get('section', sectionId);

        event.preventDefault();

        // Collect section information from the state.
        const state = JSON.parse(JSON.stringify(this.reactive.state));

        //Call to get course format options
        const course_format_options = await this.getCourseFormatOptions(state.course.id);

        let offset = 0;
        let cutoffs;

        // Offset the cutoff if it's within the lessons tab
        if(course_format_options.hasassessments) {
            if(parseInt(sectionInfo.number) < parseInt(course_format_options.assessmentscutoff)) {
                offset--;
            }
            cutoffs = [{cutoff: [{ assessment: parseInt(course_format_options.assessmentscutoff) + offset }]}];
        }

        const cmList = sectionInfo.cmlist ?? [];
        if (cmList.length || sectionInfo.hassummary || sectionInfo.rawtitle) {
            // We need confirmation if the section has something.
            const modalParams = {
                title: getString('confirm', 'core'),
                body: getString('confirmdeletesection', 'moodle', sectionInfo.title),
                saveButtonText: getString('delete', 'core'),
                type: ModalFactory.types.SAVE_CANCEL,
            };

            const modal = await this._modalBodyRenderedPromise(modalParams);

            modal.getRoot().on(
                ModalEvents.save,
                e => {
                    // Stop the default save button behaviour which is to close the modal.
                    e.preventDefault();
                    modal.destroy();
                    this.reactive.dispatch('sectionDelete', [sectionId], cutoffs);
                }
            );
            return;
        } else {
            // We don't need confirmation to delete empty sections.
            this.reactive.dispatch('sectionDelete', [sectionId], cutoffs);
        }
    }

    /**
     * Basic mutation action helper.
     *
     * @param {Element} target the dispatch action element
     * @param {Event} event the triggered event
     * @param {string} mutationName the mutation name
     */
    async _requestMutationAction(target, event, mutationName) {
        if (!target.dataset.id) {
            return;
        }
        event.preventDefault();
        this.reactive.dispatch(mutationName, [target.dataset.id]);
    }

    /**
     * Disable all add sections actions.
     *
     * @param {boolean} locked the new locked value.
     */
    _setAddSectionLocked(locked) {
        const targets = this.getElements(this.selectors.ADDSECTION);
        targets.forEach(element => {
            element.classList.toggle(this.classes.DISABLED, locked);
            element.classList.toggle(this.classes.ITALIC, locked);
            this.setElementLocked(element, locked);
        });
    }

    /**
     * Replace an element with a copy with a different tag name.
     *
     * @param {Element} element the original element
     */
    _disableLink(element) {
        if (element) {
            element.style.pointerEvents = 'none';
            element.style.userSelect = 'none';
            element.classList.add(this.classes.DISABLED);
            element.classList.add(this.classes.ITALIC);
            element.setAttribute('aria-disabled', true);
            element.addEventListener('click', event => event.preventDefault());
        }
    }

    /**
     * Render a modal and return a body ready promise.
     *
     * @param {object} modalParams the modal params
     * @return {Promise} the modal body ready promise
     */
    _modalBodyRenderedPromise(modalParams) {
        return new Promise((resolve, reject) => {
            ModalFactory.create(modalParams).then((modal) => {
                modal.setRemoveOnClose(true);
                // Handle body loading event.
                modal.getRoot().on(ModalEvents.bodyRendered, () => {
                    resolve(modal);
                });
                // Configure some extra modal params.
                if (modalParams.saveButtonText !== undefined) {
                    modal.setSaveButtonText(modalParams.saveButtonText);
                }
                modal.show();
                return;
            }).catch(() => {
                reject(`Cannot load modal content`);
            });
        });
    }

    /**
     * Hide and later destroy a modal.
     *
     * Behat will fail if we remove the modal while some boostrap collapse is executing.
     *
     * @param {Modal} modal
     * @param {HTMLElement} element the dom element to focus on.
     */
    _destroyModal(modal, element) {
        modal.hide();
        const pendingDestroy = new Pending(`courseformat/actions:destroyModal`);
        if (element) {
            element.focus();
        }
        setTimeout(() =>{
            modal.destroy();
            pendingDestroy.resolve();
        }, 500);
    }

    /**
     * Get the closest actions menu toggler to an action element.
     *
     * @param {HTMLElement} element the action link element
     * @returns {HTMLElement|undefined}
     */
    _getClosestActionMenuToogler(element) {
        const actionMenu = element.closest(this.selectors.ACTIONMENU);
        if (!actionMenu) {
            return undefined;
        }
        return actionMenu.querySelector(this.selectors.ACTIONMENUTOGGLER);
    }
}
