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
 * @copyright  2023 Daniel Thies <dethies@gmail.com>
 *             adapted from Moodle mod_assign
 *             base on work by Manuel Mejia <manimejia.me@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local\helper;

defined('MOODLE_INTERNAL') || die();

use assign_form;
use assign_plugin_header;
use assign as assign_base;
use context_user;
use core_user;
use core_tag_tag;
use html_writer;
use mod_assign_submission_form;
use moodle_exception;
use moodle_url;
use stdClass;

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/renderable.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign extends assign_base {
    /** @var $data data object */
    protected $data = null;

    /**
     * View submissions page (contains details of current submission).
     *
     * @return string
     */
    public function view_submission_page() {
        global $CFG, $DB, $USER, $PAGE;

        $instance = $this->get_instance();

        $this->add_grade_notices();

        $o = '';

        $postfix = '';
        if ($this->has_visible_attachments() && (!$this->get_instance($USER->id)->submissionattachments)) {
            $postfix = $this->render_area_files('mod_assign', ASSIGN_INTROATTACHMENT_FILEAREA, 0);
        }
        if (class_exists('mod_assign\\output\\assign_header')) {
            $headerclass = 'mod_assign\\output\\assign_header';
        } else {
            $headerclass = '\\assign_header';
        }
        $header = new $headerclass(
            $instance,
            $this->get_context(),
            $this->show_intro(),
            $this->get_course_module()->id,
            '',
            '',
            $postfix
        );

        // We just want description for header.
        $description = $header->preface;
        if ($header->showintro || $header->activity) {
            $description = $this->get_renderer()->box_start('generalbox boxaligncenter');
            if ($header->showintro) {
                $description .= format_module_intro('assign', $header->assign, $header->coursemoduleid);
            }
            if ($header->activity) {
                $description .= $this->format_activity_text($header->assign, $header->coursemoduleid);
            }
            $description .= $header->postfix;
            $description .= $this->get_renderer()->box_end();
        }
        $o .= $description;

        // Display plugin specific headers.
        $plugins = array_merge($this->get_submission_plugins(), $this->get_feedback_plugins());
        foreach ($plugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                $o .= $this->get_renderer()->render(new assign_plugin_header($plugin));
            }
        }

        if ($this->can_view_grades()) {
            $actionbuttons = new \mod_assign\output\actionmenu($this->get_course_module()->id);
            $o .= $this->get_renderer()->submission_actionmenu($actionbuttons);

            $summary = $this->get_assign_grading_summary_renderable();
            $o .= $this->get_renderer()->render($summary);
        }

        if ($this->can_view_submission($USER->id)) {
            $o .= $this->view_submission_action_bar($instance, $USER);
            $o .= $this->view_student_summary($USER, true);
        }

        \mod_assign\event\submission_status_viewed::create_from_assign($this)->trigger();

        return $o;
    }

    /**
     * Save assignment submission.
     *
     * @param  moodleform $mform
     * @param  array $notices Any error messages that should be shown
     *                        to the user at the top of the edit submission form.
     * @return bool
     */
    public function process_save_submission(&$mform, &$notices) {
        global $CFG, $USER;

        // Include submission form.
        require_once($CFG->dirroot . '/mod/assign/submission_form.php');

        $userid = optional_param('userid', $USER->id, PARAM_INT);
        // Need submit permission to submit an assignment.
        require_sesskey();
        if (!$this->submissions_open($userid)) {
            $notices[] = get_string('duedatereached', 'assign');
            return false;
        }
        $instance = $this->get_instance();

        $data = new stdClass();
        $data->userid = $userid;
        $mform = new mod_assign_submission_form(null, [$this, $data]);
        if ($this->data) {
            return $this->save_submission($this->data, $notices);
        }
        return false;
    }
    /**
     * Set data from form
     *
     * @param stdClass $data
     */
    public function set_data($data) {
        $this->data = $data;
    }

    /**
     * View edit submissions page.
     *
     * @param moodleform $mform
     * @param array $notices A list of notices to display at the top of the
     *                       edit submission form (e.g. from plugins).
     * @return string The page output.
     */
    public function view_edit_submission_page($mform, $notices) {
        global $CFG, $USER, $DB, $PAGE;

        $o = '';
        require_once($CFG->dirroot . '/mod/assign/submission_form.php');
        // Need submit permission to submit an assignment.
        $userid = optional_param('userid', $USER->id, PARAM_INT);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $timelimitenabled = get_config('assign', 'enabletimelimit');

        // This variation on the url will link direct to this student.
        // The benefit is the url will be the same every time for this student, so Atto autosave drafts can match up.
        $returnparams = ['userid' => $userid, 'rownum' => 0, 'useridlistid' => 0];
        $this->register_return_link('editsubmission', $returnparams);

        if ($userid == $USER->id) {
            if (!$this->can_edit_submission($userid, $USER->id)) {
                throw new \moodle_exception('nopermission');
            }
            // User is editing their own submission.
            require_capability('mod/assign:submit', $this->get_context());
            $title = get_string('editsubmission', 'assign');
        } else {
            // User is editing another user's submission.
            if (!$this->can_edit_submission($userid, $USER->id)) {
                throw new \moodle_exception('nopermission');
            }

            $name = $this->fullname($user);
            $title = get_string('editsubmissionother', 'assign', $name);
        }

        if (!$this->submissions_open($userid)) {
            $message = [get_string('submissionsclosed', 'assign')];
            return $this->view_notices($title, $message);
        }

        $postfix = '';
        if ($this->has_visible_attachments()) {
            $postfix = $this->render_area_files('mod_assign', ASSIGN_INTROATTACHMENT_FILEAREA, 0);
        }

        $data = new stdClass();
        $data->userid = $userid;
        if (!$mform) {
            $mform = new mod_assign_submission_form(new moodle_url('/mod/assign/view.php'), [$this, $data]);
        }

        if ($this->get_instance()->teamsubmission) {
            $submission = $this->get_group_submission($userid, 0, false);
        } else {
            $submission = $this->get_user_submission($userid, false);
        }

        if ($timelimitenabled && !empty($submission->timestarted) && $this->get_instance()->timelimit) {
            $navbc = $this->get_timelimit_panel($submission);
            $regions = $PAGE->blocks->get_regions();
            $bc = new \block_contents();
            $bc->attributes['id'] = 'mod_assign_timelimit_block';
            $bc->attributes['role'] = 'navigation';
            $bc->attributes['aria-labelledby'] = 'mod_assign_timelimit_block_title';
            $bc->title = get_string('assigntimeleft', 'assign');
            $bc->content = $navbc;
            $PAGE->blocks->add_fake_block($bc, reset($regions));
        }

        // Show plagiarism disclosure for any user submitter.
        $o .= $this->plagiarism_print_disclosure();

        foreach ($notices as $notice) {
            $o .= $this->get_renderer()->notification($notice);
        }

        $o .= $this->get_renderer()->render(new assign_form('editsubmissionform', $mform));

        \mod_assign\event\submission_form_viewed::create_from_user($this, $user)->trigger();

        return $o;
    }

    /**
     * Show a confirmation page to make sure they want to remove submission data.
     *
     * @return string
     */
    public function view_remove_submission_confirm() {
        global $USER, $PAGE;

        $userid = optional_param('userid', $USER->id, PARAM_INT);

        if (!$this->can_edit_submission($userid, $USER->id)) {
            throw new \moodle_exception('nopermission');
        }
        $user = core_user::get_user($userid, '*', MUST_EXIST);

        $o = '';

        $urlparams = [
            'id' => $this->get_course_module()->id,
            'action' => 'removesubmission',
            'userid' => $userid,
            'sesskey' => sesskey(),
        ];
        $confirmurl = new moodle_url('/mod/assign/view.php', $urlparams);

        $urlparams = [
            'id' => $this->get_course_module()->id,
            'action' => 'view',
        ];
        $cancelurl = new moodle_url('/mod/assign/view.php', $urlparams);

        if ($userid == $USER->id) {
            if ($this->is_time_limit_enabled($userid)) {
                $confirmstr = get_string('removesubmissionconfirmwithtimelimit', 'assign');
            } else {
                $confirmstr = get_string('removesubmissionconfirm', 'assign');
            }
        } else {
            if ($this->is_time_limit_enabled($userid)) {
                $confirmstr = get_string('removesubmissionconfirmforstudentwithtimelimit', 'assign', $this->fullname($user));
            } else {
                $confirmstr = get_string('removesubmissionconfirmforstudent', 'assign', $this->fullname($user));
            }
        }
        $o .= $this->get_renderer()->confirm(
            $confirmstr,
            $confirmurl,
            $cancelurl
        );

        \mod_assign\event\remove_submission_form_viewed::create_from_user($this, $user)->trigger();

        return $o;
    }

    /**
     * Remove the current submission.
     *
     * @param int $userid
     * @return boolean
     */
    public function process_remove_submission($userid = 0) {
        global $USER;

        return parent::process_remove_submission($USER->id);
    }
}
