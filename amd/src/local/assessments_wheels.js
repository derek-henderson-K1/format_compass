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
 * @module     format_compass/local/assessments_wheels
 * @class      format_compass/local/assessments_wheels
 * @copyright  2024 KnowledgeOne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Chart from 'format_compass/local/vendors/chart';

export default class Component {
    /**
    * Static method to create a component instance form the mustache template.
    *
    * @param {string} target the DOM main element or its ID
    * @param {string} chartValues list of values to display in chart
    * @param {string} chartColors list of colors to display in chart
    * @param {string} chartKeys list of section ID's with charts
    * @param {object} sectionId current section ID
    */
    static init(target, chartValues, chartColors, chartKeys, sectionId) {

        const ctx = document.getElementById(target).getContext('2d');
        let chartsData = [];

        let valuesArray = chartValues.split(',');
        let keysArray = chartKeys.split(',');
        let colorsArray = chartColors.split(',');

        let colorActive = getComputedStyle(document.body).getPropertyValue('--weight_wheel_fg_colour');
        let colorInactive = getComputedStyle(document.body).getPropertyValue('--weight_wheel_bg_colour');
        let colorDisabled = getComputedStyle(document.body).getPropertyValue('--weight_wheel_disabled_colour');

        valuesArray.forEach((item,i) => {
            let section = {};
            section.chartValue = valuesArray[i];
            section.chartKey = keysArray[i];

            if(colorsArray[i] == "color_active") {
                section.chartColor = colorActive;
                colorsArray[i] = colorActive;
            } else if(colorsArray[i] == "color_inactive") {
                section.chartColor = colorInactive;
                colorsArray[i] = colorInactive;
            } else if(colorsArray[i] != colorActive && colorsArray[i] != colorInactive && colorsArray[i] != colorDisabled){
                section.chartColor = colorDisabled;
                colorsArray[i] = colorDisabled;
            }
            chartsData.push(section);
        });



        var chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
            data: valuesArray,
            backgroundColor: colorsArray,
            borderWidth: 5
            }]
        },
        options: {
            cutout: '72%',
            elements: {
            arc: {
                borderWidth: 3,
                borderRadius: 10
            }
            },
            legend: {
                display: false
            },
            events: [],
            animation: {
                duration: 0
            }
        }
        });


        /**
        * Each section has it's own function which updates the chart with the received data.
        *
        * @param {object} updatedChartData data for all the charts.
        * @param {array} sectionlist new sectionlist order.
        */
        window['chartUpdateData' + sectionId] = function(updatedChartData, sectionlist) {

            // Update with new data.
            if(updatedChartData !== null) {
                chartsData = JSON.parse(JSON.stringify(updatedChartData));
                chartsData = chartsData.filter(item => sectionlist.includes(item.chartKey) || item.chartKey === 'fill');
                let percentTotal = 0;

                // Create a map of IDs to index positions
                const keyMap = new Map();
                chartsData.forEach((item, index) => {
                    keyMap.set(item.chartKey, index);
                    if(item.chartKey != 'fill') {
                        percentTotal += parseInt(item.chartValue);
                    }
                });

                // Sort the values array based on the index positions of IDs
                chartsData.sort((a, b) => {
                    // Filler needs to be set last
                    if (a.chartKey === 'fill') {
                        return 1;
                    }
                    if (b.chartKey === 'fill') {
                        return -1;
                    }
                    const indexA = sectionlist.indexOf(a.chartKey);
                    const indexB = sectionlist.indexOf(b.chartKey);
                    return indexA - indexB;
                });

                // Add active color to this section.
                chartsData.forEach((item) => {
                    if(item.chartKey == sectionId) {
                        item.chartColor = colorActive;
                    }
                });

                valuesArray = [];
                keysArray = [];
                colorsArray = [];

                chartsData.forEach((item) => {
                    valuesArray.push(item.chartValue);
                    keysArray.push(item.chartKey);
                    colorsArray.push(item.chartColor);
                });
            } else {
                // Only move / delete charts.
                chartsData = chartsData.filter(item => sectionlist.includes(item.chartKey) || item.chartKey === 'fill');
                let percentTotal = 0;

                // Create a map of IDs to index positions
                const keyMap = new Map();
                chartsData.forEach((item, index) => {
                    keyMap.set(item.chartKey, index);
                    if(item.chartKey != 'fill') {
                        percentTotal += parseInt(item.chartValue);
                    }
                });

                // Sort the values array based on the index positions of IDs
                chartsData.sort((a, b) => {
                    // Filler needs to be set last
                    if (a.chartKey === 'fill') {
                        return 1;
                    }
                    if (b.chartKey === 'fill') {
                        return -1;
                    }
                    const indexA = sectionlist.indexOf(a.chartKey);
                    const indexB = sectionlist.indexOf(b.chartKey);
                    return indexA - indexB;
                });

                // Reset values.
                valuesArray = [];
                colorsArray = [];
                keysArray = [];

                chartsData.forEach((item) => {
                    if(item.chartKey == 'fill' && percentTotal < 100) {
                        item.chartValue = 100 - percentTotal;
                    }
                    valuesArray.push(item.chartValue);
                    keysArray.push(item.chartKey);
                    colorsArray.push(item.chartColor);
                });
            }

            // Updates chart with the new data.
            chart.data.datasets[0].data = valuesArray;
            chart.data.datasets[0].backgroundColor = colorsArray;
            chart.update();
        };
    }
}