<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * This file contains main class for Popups course format.
 *
 * @package     format_popups
 * @copyright   2021 Daniel Thies <dethies@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot. '/course/format/lib.php');
require_once($CFG->dirroot. '/course/format/topics/lib.php');

use core\output\inplace_editable;

/**
 * Main class for the Popups course format.
 *
 * @package     format_popups
 * @copyright   2021 Daniel Thies <dethies@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_popups extends format_topics {

    /**
     * Returns true if this course format uses course index
     *
     * This function may be called without specifying the course id
     * i.e. in course_index_drawer()
     *
     * @return bool
     */
    public function uses_course_index() {
        $course = $this->get_course();
        return !empty(get_config('format_popups', 'usecourseindex'))
            || isset($course->coursedisplay)
            && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE;
    }

    /**
     * Definitions of the additional options that this course format uses for course.
     *
     * Topics format uses the following options:
     * - coursedisplay
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = [
                'hiddensections' => [
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ],
                'coursedisplay' => [
                    'default' => $courseconfig->coursedisplay,
                    'type' => PARAM_INT,
                ],
                'addnavigation' => [
                    'default' => get_config('format_popups', 'addnavigation'),
                    'type' => PARAM_BOOL,
                ],
            ];
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseformatoptionsedit = [
                'addnavigation' => [
                    'label' => new lang_string('addnavigation', 'format_popups'),
                    'help' => 'addnavigation',
                    'help_component' => 'format_popups',
                    'element_type' => 'advcheckbox',
                ],
                'hiddensections' => [
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        ],
                    ],
                ],
                'coursedisplay' => [
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi'),
                        ],
                    ],
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                ],
            ];
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 */
function format_popups_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'popups'], MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * Return view for activity
 *
 * @param array $args arguments
 * @return string content
 */
function format_popups_output_fragment_mod($args) {
    global $OUTPUT, $PAGE, $USER;

    $context = $args['context'];
    $modname = clean_param($args['modname'], PARAM_COMPONENT);
    if (key_exists('jsondata', $args)) {
        $data = array();
        parse_str(json_decode($args['jsondata']), $data);
        $data = (object) $data;
    } else {
        $data = null;
    }

    $path = key_exists('path', $args) ? json_decode($args['path']) : null;

    $class = '\\format_popups\\local\\mod_' . $modname;
    if (!class_exists($class)) {
        throw new moodle_exception('modulenotsupported');
    }
    list ($course, $cm) = get_course_and_cm_from_cmid($context->instanceid, $modname);
    require_course_login($course, true, $cm);

    $module = new $class($cm, $context, $course, $data, $path);

    $content = $module->render();

    // Render the activity information.
    if (
        class_exists('\\core_completion\\activity_custom_completion')
        && $course->showcompletionconditions != COMPLETION_SHOW_CONDITIONS
    ) {
        $cminfo = cm_info::create($cm);
        $completiondetails = \core_completion\cm_completion_details::get_instance($cminfo, $USER->id);
        $activitydates = \core\activity_dates::get_dates_for_module($cminfo, $USER->id);
        $content = $OUTPUT->activity_information($cminfo, $completiondetails, $activitydates) . $content;
    }
    $format = course_get_format($course);
    $course = $format->get_course();
    $options = $format->get_format_options();
    if (!empty(($options['addnavigation']))) {
        $PAGE->set_pagelayout('frametop');
        $content .= $OUTPUT->activity_navigation();
    }
    return '<div>' . $content . '</div>';
}

/**
 * Return course page content  for reloading
 *
 * @param array $args arguments
 * @return string content
 */
function format_popups_output_fragment_page($args) {
    global $PAGE;

    // Do not update if editing.
    if ($PAGE->user_is_editing()) {
        return '';
    }
    $context = $args['context'];
    $displaysection = $args['displaysection'];
    $course = get_course($context->instanceid);

    $displaysection = clean_param($args['displaysection'], PARAM_INT);

    // Retrieve course format option fields and add them to the $course object.
    $format = course_get_format($course);
    $course = $format->get_course();

    $renderer = $PAGE->get_renderer('format_' . $format->get_format());
    if (!empty($displaysection)) {
        $format->set_section_number($displaysection);
    }
    $outputclass = $format->get_output_classname('content');
    $widget = new $outputclass($format);

    $contents = $renderer->render($widget);

    // Trigger course viewed event.
    course_view(context_course::instance($course->id), $displaysection);

    return $contents;
}

/**
 * Get list of activities current accessible as popup
 *
 * @param stdClass $course course record
 * @return array List of currently available modules
 */
function format_popups_mods_available($course) {
    $modinfo = get_fast_modinfo($course);
    $modules = array();
    foreach ($modinfo->get_cms() as $cmid => $cminfo) {
        $class = '\\format_popups\\local\\mod_' . $cminfo->modname;
        if (
            class_exists($class) &&
            has_capability('format/popups:view', context_module::instance($cmid))
        ) {
            $modules[] = (object) array(
                'contextid' => $cminfo->context->id,
                'id' => $cmid,
                'instance' => $cminfo->instance,
                'modname' => $cminfo->modname,
                'title' => $cminfo->name,
            );
        }
    }
    return $modules;
}
