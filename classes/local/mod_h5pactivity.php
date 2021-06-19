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
 * Activity renderer Popups course format
 *
 * @package    format_popups
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 *             adapted from Moodle mod_h5pactivity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_user;
use core_h5p\factory;
use core_h5p\player;
use core_h5p\helper;
use mod_h5pactivity\local\manager;
use mod_h5pactivity\output\reportlink;
use mod_h5pactivity\event\report_viewed;
use moodle_exception;
use moodle_url;

require_once($CFG->dirroot . '/mod/h5pactivity/lib.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_h5pactivity extends mod_page {

    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $OUTPUT, $PAGE, $USER;

        list ($course, $this->cm) = get_course_and_cm_from_cmid($this->cm->id, 'h5pactivity');

        $manager = manager::create_from_coursemodule($this->cm);

        $moduleinstance = $manager->get_instance();

        $context = $this->context;

        if (!empty($this->data->a) && $this->data->a == $moduleinstance->id) {
            return $this->render_report($manager, $moduleinstance);
        }

        // Trigger module viewed event and completion.
        $manager->set_module_viewed($course);

        // Convert display options to a valid object.
        $factory = new factory();
        $core = $factory->get_core();
        $config = helper::decode_display_options($core, $moduleinstance->displayoptions);

        // Instantiate player.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_h5pactivity', 'package', 0, 'id', false);
        $file = reset($files);
        $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                            $file->get_filearea(), $file->get_itemid(), $file->get_filepath(),
                            $file->get_filename(), false);

        if (!empty($moduleinstance->intro)) {
            $content .= $OUTPUT->box(format_module_intro('h5pactivity', $moduleinstance, $this->cm->id), 'generalbox', 'intro');
        }

        // Attempts review.
        if ($manager->can_view_all_attempts()) {
            $reviewurl = new moodle_url('/mod/h5pactivity/report.php', ['a' => $this->cm->instance]);
            $reviewmessage = get_string('review_all_attempts', 'mod_h5pactivity', $manager->count_attempts());
        } else if ($manager->can_view_own_attempts() && $manager->count_attempts($USER->id)) {
            $reviewurl = new moodle_url('/mod/h5pactivity/report.php', ['a' => $this->cm->instance, 'userid' => $USER->id]);
            $reviewmessage = get_string('review_my_attempts', 'mod_h5pactivity');
        }
        if (isset($reviewurl)) {
            $widget = new reportlink($reviewurl, $reviewmessage);
            $content .= $OUTPUT->render($widget);
        }

        if (!$manager->is_tracking_enabled()) {
            $message = get_string('previewmode', 'mod_h5pactivity');
            $content .= $OUTPUT->notification($message, \core\output\notification::NOTIFY_WARNING);
        }

        $content .= player::display($fileurl, $config, true, 'mod_h5pactivity');

        $PAGE->requires->js_call_amd('format_popups/h5pactivity', 'init', array(
            $this->context->id, 'h5pactivity', $moduleinstance->id
        ));

        return $content;
    }

    /**
     * Renders page contents
     *
     * @param object $manager
     * @param stdClass $moduleinstance
     * @return string page contents
     */
    protected function render_report($manager, $moduleinstance) {
        $report = $manager->get_report($this->data->userid ?? 0, $this->data->attemptid ?? 0);
        if (!$report) {
            throw new moodle_exception('permissiondenied', 'h5pactivity');
        }

        $user = $report->get_user();
        $attempt = $report->get_attempt();

        $context = $this->context;

        $params = ['a' => $this->cm->instance];
        if ($user) {
            $params['userid'] = $user->id;
        }
        if ($attempt) {
            $params['attemptid'] = $attempt->get_id();
        }

        // Trigger event.
        $other = [
            'instanceid' => $params['a'],
            'userid' => $params['userid'] ?? null,
            'attemptid' => $params['attemptid'] ?? null,
        ];
        $event = report_viewed::create([
            'objectid' => $moduleinstance->id,
            'context' => $context,
            'other' => $other,
        ]);
        $event->add_record_snapshot('course', $this->course);
        $event->add_record_snapshot('h5pactivity', $moduleinstance);
        $event->trigger();

        ob_start();
        echo $report->print();
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }
}
