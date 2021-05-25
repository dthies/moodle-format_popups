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

    return '<div>' . $module->render() . '</div>';
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
    $renderer = $PAGE->get_renderer('format_' . $course->format);
    ob_start();

    if (empty($displaysection)) {
        $renderer->print_multiple_section_page($course, null, null, null, null);
    } else {
        $renderer->print_single_section_page($course, null, null, null, null, $displaysection);
    }

    $contents = ob_get_contents();
    ob_end_clean();

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
