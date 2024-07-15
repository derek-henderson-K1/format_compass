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
 * Strings for component compass course format.
 *
 * @package   format_compass
 * @copyright 2023 Knowledgeone inc.  {@link http://knowledgeone.ca}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['addsections'] = 'Add section';
$string['currentsection'] = 'This section';
$string['editsection'] = 'Edit section';
$string['editsectionname'] = 'Edit section name';
$string['deletesection'] = 'Delete section';
$string['newsectionname'] = 'New name for section {$a}';
$string['sectionname'] = 'Section';
$string['confirmdeletesection'] = 'Are you absolutely sure you want to completely delete this section, including activity and other data?';
$string['pluginname'] = 'Compass format';
$string['section0name'] = 'General';
$string['page-course-view-compass'] = 'Any course main page in Compass format';
$string['page-course-view-compass-x'] = 'Any course page in Compass format';
$string['hidefromothers'] = 'Hide section';
$string['showfromothers'] = 'Show section';
$string['privacy:metadata'] = 'The Compass format plugin does not store any personal data.';
$string['firsttabname'] = 'Course Home';
$string['coursesettings'] = 'Course settings';
// Back to course home page.
$string['backtoallsections'] = 'All {$a}';

// Activity additional option fields.
$string['activityoption'] = 'Options for this activity';
$string['activity:functionas'] = 'Function as';
$string['sectiondivider'] = 'Section divider';
$string['textandmedia'] = 'Text and medias';

// Section additional option fields.
$string['sectionassessmentsheader'] = 'Grade Weighting';
$string['subname'] = 'Sub-name';
$string['subname_help'] = 'The sub-name will appear underneath the name on the section tile and at the top of the section page. Typically, the sub-name should contain the topic or title for a lesson/unit/etc. If this isn\'t applicable for the section, then leave this field empty.Note: the sub-name will not display if the section is moved under the Assessments tab.';
$string['sectiongroup'] = 'Section group';
$string['sectiongroup_label'] = 'Start of section group';
$string['sectiongroup_help'] = 'If enabled, students will see a text divider just above this section on the lessons page. Use this option if you want to organize your lessons (or units, modules, etc.) into groups.';
$string['sectiongrouptext'] = 'Section group text';

// Course format option: Term for lessons tab.
$string['ecf_lessonstabname_option_title'] = 'Term for lessons tab';
$string['ecf_lessonstabname_units'] = 'Units';
$string['ecf_lessonstabname_lessons'] = 'Lessons';
$string['ecf_lessonstabname_modules'] = 'Modules';
$string['ecf_lessonstabname_sections'] = 'Sections';
$string['ecf_lessonstabname_topics'] = 'Topics';
$string['ecf_lessonstabname_content'] = 'Content';
$string['calendartabname_calendar'] = 'Calendar';
$string['assessmentcutoff'] = 'assessmentcutoff';

// Course format option: Enable Assessments tab.
$string['ecf_assessmentstab_enable_title'] = 'Enable assessments tab';

// Course format option: Term for Assessments tab.
$string['ecf_assessmentstabname_option_title'] = 'Term for assessments tab';
$string['ecf_assessmentstabname_assessments'] = 'Assessments';
$string['ecf_assessmentstabname_assignments'] = 'Assignments';
$string['ecf_assessmentstabname_gradedactivities'] = 'Graded Activities';

// Course format option: Enable calendar tab.
$string['calendartab_enable_title'] = 'Enable calendar tab';

// Help strings for course options title.
$string['hiddensections'] = 'hidden sections';
$string['coursedisplay'] = 'course display';
$string['termforlessonstab'] = 'term for lessons tab';
$string['enableassessmentstab'] = 'enable assessments tab';
$string['termforassessmentstab'] = 'term for assessments tab';
$string['enablecalendartab'] = 'enable calendar tab';

$string['format_compass'] = 'format_compass plugin';

// Help strings for course options content.
$string['termforlessonstab_help'] = 'This determines the name of the second tab in the course navigation. It also adjusts the text in several other UI elements.';
$string['enableassessmentstab_help'] = 'If enabled, this creates a third tab in the course navigation. You can then create and display assessment sections within the tab. The name of the tab can be changed with the setting below.';
$string['termforassessmentstab_help'] = 'This determines the name of the third tab in the course navigation. It also adjusts the text in several other UI elements.';
$string['enablecalendartab_help'] = 'If enabled this will display the calendar tab.';
$string['format_compass_help'] = 'Placeholder for plugin help';

// Course format option: Assessments layout.
$string['assessmentstablayout_option_title_help'] = 'This setting determines whether all content
under the assessments tab is displayed on one page or split over several pages.';
$string['assessmentslayout_allonepage'] = 'Show all assessment sections on one page';
$string['assessmentslayout_oneperpage'] = 'Show one assessment section per page';
$string['assessmentstablayout_option_title'] = 'Assessments layout';

$string['sectionname_freetype'] = 'Free type';
$string['sectionname_singleterm_singlenumber'] = 'Single term, single number';
$string['sectionname_singleterm_doublenumber'] = 'Single term, double number ';
$string['sectionname_doubleterm_doublenumber'] = 'Double term and number';

$string['sectionname_lesson'] = 'Lesson';
$string['sectionname_unit'] = 'Unit';
$string['sectionname_module'] = 'Module';
$string['sectionname_section'] = 'Section';
$string['sectionname_topic'] = 'Topic';

$string['sectionname_type'] = '';
$string['sectionname_type_help'] = 'Choose from four types of custom name: <br>• Free type - Enter any name as plain text<br>• Single term, single number - e.g. Lesson 1<br>• Single term, double number - e.g. Unit 1.1<br>• Double term and number - e.g. Unit 1, &nbsp;&nbsp;&nbsp;Lesson&nbsp;1';
$string['sectionname_first_term'] = '';
$string['sectionname_second_term'] = '';
$string['sectionname_first_number'] = '';
$string['sectionname_second_number'] = '';
// Format Colours.
$string['format_settings_page_title'] = 'Compass default options';
$string['format_settings_page_description'] = 'These settings are used when creating a new course or activating the compass format for the first time.';
$string['default_accent_1_shading'] = 'Default Accent 1 shading';
$string['default_accent_1_dark'] = 'Default Accent 1 dark';
$string['default_accent_1_light'] = 'Default Accent 1 light';
$string['default_accent_2_shading'] = 'Default Accent 2 shading';
$string['default_accent_2_dark'] = 'Default Accent 2 dark';
$string['default_accent_2_light'] = 'Default Accent 2 light';
$string['light_primary_dark_secondary'] = 'Light shade primary, dark shade secondary';
$string['dark_primary_light_secondary'] = 'Dark shade primary, light shade secondary';
$string['courseformatcolourscheme'] = 'Colour scheme';
$string['custom_accent_1_shading'] = 'Custom accent 1 shading';
$string['custom_accent_2_shading'] = 'Custom accent 2 shading';
$string['custom_accent_1_light'] = 'Custom accent 1 light';
$string['custom_accent_2_light'] = 'Custom accent 2 light';
$string['custom_accent_1_dark'] = 'Custom accent 1 dark';
$string['custom_accent_2_dark'] = 'Custom accent 2 dark';
$string['invalidcolor'] = 'Invalid colour string entered. Must be either a three or six character hex string, preceeded by a # sign';
// Lesson Banner Colours.
$string['lessonbanner'] = 'Banner in lesson page';
$string['bannerimage'] = 'Banner image';
$string['bannerimage_help'] = 'The banner image will appear at the top of the page. The section name & sub-name will be overlaid
                               on top of this image. If no image is uploaded, a placeholder image will appear in its place.';
$string['bannermobile'] = 'Banner image for small screens';
$string['bannermobile_help'] = 'This image will replace the standard banner image (above) on mobile devices. If no image is uploaded,
                               the banner image uploaded above will be used for all screen sizes. This option exists only
                               for optimization purposes. For the sake of consistency, this image should not differ significantly
                               from the banner image (in terms of image content).';
$string['lessonbannercolours'] = 'Banner colours';
$string['lessonbannercolours_help'] = 'The accent colours will be applied to panels overlaying the left & right sides of the banner image.
                                       The transparency of these panels can be controlled with the opacity options below.';
$string['lessonbannerleftaccent1'] = 'Accent 1 on left side, accent 2 on right';
$string['lessonbannerleftaccent2'] = 'Accent 2 on left side, accent 1 on right';
$string['bannerleftopacity'] = 'Colour opacity, left side';
$string['bannerleftopacity_help'] = 'Controls the opacity of the color on the left side of the banner. Note: For accessibility
                                        reasons, the opacity cannot be less than 50% on the left side. However, even at 50%,
                                        the text may not be fully accessible depending on the visual properties of the banner image.
                                        Exercise caution accordingly.';
$string['bannerrightopacity'] = 'Colour opacity, right side';
$string['bannerrightopacity_help'] = 'Controls the opacity of the color on the left side of the banner.
                                         If you want to display the underlying image with no color overlay, then set the opacity to 0%.';
$string['bottomstripe'] = 'Display bottom stripe';
$string['bottomstripe_help'] = 'If enabled, a stripe will be applied along the bottom of the banner image.
                                It will take the accent colour opposite the left side. (E.g. If the left side uses accent 1,
                                then the bottom stripe will use accent 2.)';

// Course Home tab
$string['coursehometab'] = 'Course Home tab';
$string['displaybottomstripecourseimage'] = 'Display bottom stripe over course image';
$string['displaybottomstripecourseimage_help'] = 'If enabled, a stripe will be applied along the bottom of the banner image. It will use accent colour 1.';
$string['activitylistlayout'] = 'Activity list layout';
$string['activitylistlayout_simplebuttons_3columns'] = 'Simple buttons, 3 columns';
$string['activitylistlayout_simplebuttons_2columns'] = 'Simple buttons, 2 columns';
$string['activitylistlayout_fullrows'] = 'Full rows';
$string['activitylistlayout_help'] = 'This determines how activities are displayed below the course image. <br>
• Simple buttons, 3 columns - Recommended if you want the page to have a clean, compact appearance. However, these tiles will only display the icon,
                              activity name, and a notification for unread messages. <br>
• Simple buttons, 2 columns - Recommended if you want the page to have a clean, compact appearance,
                              but your activity names are too long for the 3 column layout. <br>
• Full rows - This is recommended if you need to display detailed information (like completion status) on the course home page. This is identical to how activities are displayed inside other sections.';

// Cmsummary completed.
$string['progressdone'] = 'Done: {$a->complete} / {$a->total}';

$string['lessonstabheader'] = 'Lessons tab';
$string['assessmenttab'] = 'Assessments tab';

$string['verticalimage'] = 'Vertical background image';
$string['verticalimage_help'] = 'The vertical background image will be automatically divided and displayed in each section tile,
                                 forming one continuous background image down the right side of the page. It will be hidden on
                                 mobile devices. If no image is uploaded, a placeholder image will appear in its place.';
$string['vertbgfilter'] = 'Colour filter over image';
$string['vertbgfilter_help'] = 'As an option, either accent 1 or accent 2 can be applied as a filter over the image.                                     This can also be disabled.';
$string['vertbgfilternone'] = 'None';
$string['vertbgfilteraccent1'] = 'Accent 1';
$string['vertbgfilteraccent2'] = 'Accent 2';
$string['vertbgfilteropacity'] = 'Colour filter opacity';
$string['vertbgfilteropacity_help'] = 'Controls the opacity of the color filter over the image.';

$string['initialcollapsestate'] = 'Initial state for expand collapse';
$string['expanded'] = 'Expanded';
$string['collapsed'] = 'Collapsed';
$string['initialcollapsestate_help'] = 'This setting determines whether the assessment sections are initially expanded or collapsed when students first visit the assessments tab.';
$string['displaysectionweights'] = 'Display section weights';
$string['displaysectionweights_help'] = 'If enabled, each section will display a weight value. The specific percentage must be set in the settings for each section. You can also designate section weights as ungraded or bonus';
$string['sectionweightsdisplay'] = 'Display weights';
$string['sectionweightdonotdisplay'] = 'Hide weights';
$string['sectionweighthiddenmessage'] = 'Grade weighting has been hidden for all sections in the tab. To display a grade weight, you must first enable this option in the {$a}.';
$string['displaytextareassections'] = 'Display text area(s) above / below sections';
$string['displaytextareassections_help'] = 'If enabled, you can add text above or below the list of sections.';
$string['displaytextareadonotdisplay'] = 'Do not display';
$string['displaytextareadisplayabove'] = 'Display above sections';
$string['displaytextareadisplaybelow'] = 'Display below sections';
$string['displaytextareadisplayaboveandbelow'] = 'Display above and below sections';
$string['textareabovesection'] = 'Text area above sections';
$string['textareabelowsections'] = 'Text area below sections';

// Grade weighting.
$string['type_of_grade_weighting'] = 'Type of grade weighting';
$string['type_of_grade_weighting_help'] = 'Choose from three options: <br>
                                            •  Ungraded - Displays the word "ungraded" <br>
                                            •  Weighted - Percentage will be displayed and represented in a stylized pie chart <br>
                                            •  Bonus - Percentage will be displayed as a "bonus" mark (excluded from the pie chart) <br><br>
                                            Note: If you want to hide the grade weight entirely, you can disable it for all sections in the tab settings.';

$string['type_of_grade_weighting_ungraded'] = 'Ungraded';
$string['type_of_grade_weighting_weighted'] = 'Weighted';
$string['type_of_grade_weighting_bonus'] = 'Bonus';

$string['weighttype_bonus_value'] = 'Bonus value';
$string['weighttype_bonus_value_help'] = 'Must be a number between 0.01 - 100. Permits 2 decimal places.';

$string['weighttype_singlemultiple'] = 'Single value or multiple values';
$string['weighttype_singlemultiple_help'] = 'Choose from three options: <br>
                                            •  Single value - Use this when you want to represent the weight with a single number <br>
                                            •  Multiple values: multiplier - Use this to display multiple weights of equivalent value     (e.g. 4×10%) <br>
                                            •  Multiple values: comma separated - Use this to display multiple weights of different values (e.g. 10 + 20)';
$string['weighttype_singlemultiple_single'] = 'Single value';
$string['weighttype_singlemultiple_multiplier'] = 'Multiple values: multiplier';
$string['weighttype_singlemultiple_csv'] = 'Multiple values: comma separated';

$string['weighttype_singlevalue_weightvalue'] = 'Weight value';
$string['weighttype_singlevalue_weightvalue_help'] = 'Must be a number between 0.01 - 100. Permits 2 decimal places.';

$string['weighttype_multiplier_weightvalue'] = 'Weight value';
$string['weighttype_multiplier_weightvalue_help'] = 'Must be a number between 0.01 - 100. Permits 2 decimal places.';
$string['weighttype_multiplier_multipliervalue'] = 'Multiplier';
$string['weighttype_multiplier_multipliervalue_help'] = 'Must be a number between 2 - 100. Reject numbers with decimal places.';

$string['weighttype_multiplier_csvvalue'] = 'Comma-separated weight values';
$string['weighttype_multiplier_csvvalue_help'] = 'Use commas to separate multiple values. Accepts values between 0.01 - 100. Permits 2 decimal places.';

$string['weighttype_preview'] = 'Preview';

$string['assessments_seemore'] = 'Expand for more';
$string['assessments_counts_for'] = 'Counts for:';
$string['assessments_ungraded'] = 'Ungraded';
$string['assessments_bonus_upto'] = 'Bonus up to:';
$string['assessments_chart_percent'] = '{$a}%';
$string['assessments_chart_multiplier'] = '({$a->weighttype_multiplier_multipliervalue}x{$a->weighttype_multiplier_weightvalue}%)';
