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
class mod_contentdesigner extends mod_page {

    /** @var $contentdesigner contentdesigner instance object */
    public $contentdesigner = null;


    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $DB, $PAGE, $USER;

        $cm = $this->cm;
        $course = $this->course;
        $context = $this->context;

        $contentdesigner = $DB->get_record('contentdesigner', ['id' => $cm->instance], '*', MUST_EXIST);

        $this->contentdesigner = new \mod_contentdesigner\content_display($contentdesigner,  $cm, $course, $context);

        require_capability('mod/contentdesigner:view', $context);

        if (!$data = $DB->get_record('contentdesigner', ['id' => $this->cm->instance])) {
            throw new moodle_exception('course module is incorrect');
        }


        if(!empty($this->data->action)) {
            switch ($this->data->action) {
                case 'finishattempt':
                    $this->contentdesigner->finish_attempt($this->data->attemptid, true);
                    if ($contentdesigner->enablegrading) {
                        return  $this->contentdesigner->view_summary(true);
                    } else {
                        return  $this->contentdesigner->view_content(true);
                    }
                    break;
                case 'makeattempt':
                    return $this->contentdesigner->make_attempt(true);
                    break;
                case 'continueattempt':
                    return $this->contentdesigner->continue_attempt($this->data->attemptid, true);
                    break;
            }
        }

        contentdesigner_view($data, $this->course, $this->cm, $context);

        $PAGE->requires->js_call_amd('mod_contentdesigner/elements', 'animateElements', []);
        $PAGE->requires->js_call_amd('format_popups/form', 'init', [$this->context->id, $this->cm->modname]);

        if ($contentdesigner->enablegrading) {
            return  $this->contentdesigner->view_summary(true);
        } else {
            return  $this->contentdesigner->view_content(true);
        }
    }
}