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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

use stdClass;
use context_user;
use moodle_exception;
use moodle_url;

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

        require_capability('mod/contentdesigner:view', $context);

        $contentdesigner = $DB->get_record('contentdesigner', ['id' => $cm->instance], '*', MUST_EXIST);

        if (class_exists('\mod_contentdesigner\content_display')) {
            $this->contentdesigner = new \mod_contentdesigner\content_display($contentdesigner, $cm, $course, $context);
        } else {
            // Render the page view of the elements.
            $editor = new \mod_contentdesigner\editor($this->cm, $this->course);
            $editor->initiate_js();
            $content = $editor->render_elements();
        }

        if (!empty($this->data->action)) {
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

        // Completion and trigger events.
        contentdesigner_view($contentdesigner, $this->course, $this->cm, $context);

        $PAGE->requires->js_call_amd('mod_contentdesigner/elements', 'animateElements', []);

        if (!class_exists('\mod_contentdesigner\content_display')) {
            return $content;
        } else if ($contentdesigner->enablegrading) {
            $PAGE->requires->js_call_amd('format_popups/form', 'init', [$this->context->id, $this->cm->modname]);
            return  $this->contentdesigner->view_summary(true);
        } else {
            $PAGE->requires->js_call_amd('format_popups/form', 'init', [$this->context->id, $this->cm->modname]);
            return  $this->contentdesigner->view_content(true);
        }
    }
}
