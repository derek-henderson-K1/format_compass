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
 * A javascript module to enhance the editsection form.
 *
 * @copyright  2023 KnowledgeOne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';


export const editsection_form_helper = () => {

    /**
     * A common javascript function to set custom section names
     */
    function SetCustomSectionName()
    {
        var sectionName = $('[tag="id_sectionname"]').get(0);
        var first_termvalue = $('#id_sectionname_first_term').get(0).selectedOptions[0].text;
        var first_numbervalue = $('#id_sectionname_first_number').get(0).selectedOptions[0].text;
        var second_termvalue = $('#id_sectionname_second_term').get(0).selectedOptions[0].text;
        var second_numbervalue = $('#id_sectionname_second_number').get(0).selectedOptions[0].text;

        var sectionname_typevalue = $('#id_sectionname_type').val();
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

    $("body").on("change", function(event) {
        var defaultcustomcheckbox = $('input[type=checkbox][name="name[customize]"]').get(0);
        if (defaultcustomcheckbox!==null && event.target!==null && event.target.id == defaultcustomcheckbox.id) {
            if (event.target.checked == true) {
                SetCustomSectionName();
            }
        }
    });

    $(document).ready(function() {
        var weighttype_singlemultiple = $('#id_weighttype_singlemultiple').val();
        if (weighttype_singlemultiple == "weighttype_singlemultiple_csv") {
            var totalValue = CalculateCSVPreviewValue();
            SetPreviewValue(totalValue);
        } else if (weighttype_singlemultiple == "weighttype_singlemultiple_multiplier") {
            var totalValue = CalculateMultiplierPreviewValue();
            SetPreviewValue(totalValue);
        }
    });

    $('form').on('submit', function() {
        $('[tag="id_sectionname"]').removeAttr('disabled');
        // get all the inputs into an array.
        var $inputs = $('form :input');

        // loop through the array to set the disabled elements back to their original value
        $inputs.each(function() {
            if (this.disabled == true) {
                if (this.attributes['value']!== undefined && this.attributes['value']!==null) {
                    this.disabled = false;
                    this.value = this.attributes['value'].value;
                    this.disabled = true;
                }
            }
        });
    });

    $('.select_name_type_option').on('change', function() {
        SetCustomSectionName();
    });

    $('#id_weighttype_singlemultiple').on('change', function() {
        var weighttype_singlemultiple = $('#id_weighttype_singlemultiple').val();
        if (weighttype_singlemultiple == "weighttype_singlemultiple_csv") {
            var totalValue = CalculateCSVPreviewValue();
            SetPreviewValue(totalValue);
        } else if (weighttype_singlemultiple == "weighttype_singlemultiple_multiplier") {
            var totalValue = CalculateMultiplierPreviewValue();
            SetPreviewValue(totalValue);
        }
    });

    $('#id_weighttype_multiplier_csvvalue').on('change', function() {
        var totalValue = CalculateCSVPreviewValue();
        SetPreviewValue(totalValue);
    });

    $('.class_weighttype_multiplier').on('change', function() {
        var totalValue = CalculateMultiplierPreviewValue();
        SetPreviewValue(totalValue);
    });

    /**
     * Calculate CSVPreviewValue from csv string on change function
     */
     function CalculateCSVPreviewValue() {
        var weighttype_multiplier_csvvalue = $('#id_weighttype_multiplier_csvvalue').get(0).value;
        var weighttype_multiplier_csvarray = weighttype_multiplier_csvvalue.split(',');
        var totalValue = 0.0;
        var breakdown = '';
        $.each(weighttype_multiplier_csvarray, function(i, value) {
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
    function CalculateMultiplierPreviewValue() {
        var weighttype_multiplier_weightvalue = $('#id_weighttype_multiplier_weightvalue').get(0).value;
        var weighttype_multiplier_multipliervalue = $('#id_weighttype_multiplier_multipliervalue').get(0).value;
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
    function SetPreviewValue(totalValue){
        var static_group = $("[data-groupname=staticgroup] [data-fieldtype=group]").get(0);
        static_group.innerHTML =  totalValue;
    }
};
