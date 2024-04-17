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

namespace format_popups\local;

defined('MOODLE_INTERNAL') || die();

use assign_form;
use assign_header;
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
class mod_assign extends mod_page {
    /** @var $assign assignment instance object */
    protected $assign = null;

    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $DB, $OUTPUT, $PAGE, $USER;
        $cm = $this->cm;
        $course = $this->course;
        $context = $this->context;

        $this->assign = new helper\assign($context, $cm, $course);
        $mform = null;
        $notices = [];

        if (
            !empty($this->data->action)
        ) {
            switch ($this->data->action) {
                case 'editsubmission':
                    $PAGE->requires->js_call_amd('format_popups/form', 'init', [$this->context->id, $this->cm->modname]);
                    return $this->assign->view_edit_submission_page($mform, $notices);
                    break;
                case 'removesubmission':
                    $this->assign->process_remove_submission();
                    break;
                case 'removesubmissionconfirm':
                    return $this->assign->view_remove_submission_confirm();
                    break;
                case 'savesubmission':
                    if ($this->submitbutton != 'cancel') {
                        $this->assign->set_data($this->data);
                        $this->assign->process_save_submission($mform, $notices);
                    }
                    break;
                case 'view':
                    break;
                default:
                    $PAGE->requires->js_amd_inline('window.location.href = "' . (new moodle_url('/mod/assign/view.php', [
                        'id' => $this->data->id,
                        'action' => $this->data->action,
                    ]))->out(false) . '";');
            }
        }
        // Update module completion status.
        $this->assign->set_module_viewed();

        // Apply overrides.
        $this->assign->update_effective_access($USER->id);

        $PAGE->requires->js_call_amd('format_popups/form', 'init', [$this->context->id, $this->cm->modname]);

        // Get the assign class to render the page.
        return $this->assign->view_submission_page();
    }
}
