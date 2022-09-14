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
 * @copyright  2022 Daniel Thies <dethies@gmail.com>
 *             adapted from Moodle mod_choice
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_course;
use context_module;
use context_system;
use context_user;
use moodle_exception;
use moodle_url;

require_once($CFG->dirroot . '/mod/choice/lib.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_choice extends mod_page {

    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $CFG, $DB, $OUTPUT, $PAGE, $SESSION, $USER;

        $course = $DB->get_record('course', array('id' => $this->cm->course), '*', MUST_EXIST);

        if (!$choice = choice_get_choice($this->cm->instance)) {
            throw new moodle_exception('invalidcoursemodule', 'choice');
        }

        list($choiceavailable, $warnings) = choice_get_availability_status($choice);

        if (!empty($this->data->action) && $this->data->action == 'delchoice' && ($this->data->sesskey == sesskey())
            && is_enrolled($this->context, null, 'mod/choice:choose') && $choice->allowupdate && $choiceavailable) {
            $answercount = $DB->count_records('choice_answers', array('choiceid' => $choice->id, 'userid' => $USER->id));
            if ($answercount > 0) {
                $choiceanswers = $DB->get_records('choice_answers', array('choiceid' => $choice->id, 'userid' => $USER->id),
                    '', 'id');
                $todelete = array_keys($choiceanswers);
                choice_delete_responses($todelete, $choice, $this->cm, $course);
            }
        }

        // Submit any new data if there is any.
        if (!empty($this->data) && !empty($this->data->action) && $this->data->sesskey == sesskey()) {
            $timenow = time();
            if (has_capability('mod/choice:deleteresponses', $this->context)) {
                if ($action === 'delchoice') {
                    // Some responses need to be deleted.
                    choice_delete_responses($this->data->attemptids, $choice, $this->cm, $course);
                }
                if (preg_match('/^choose_(\d+)$/', $this->action, $actionmatch)) {
                    // Modify responses of other users.
                    $newoptionid = (int)$actionmatch[1];
                    choice_modify_responses(
                        $this->data->userids,
                        $this->data->attemptids,
                        $newoptionid,
                        $choice,
                        $this->cm,
                        $course
                    );
                }
            }

            // Redirection after all POSTs breaks block editing, we need to be more specific!
            if ($choice->allowmultiple) {
                $answer = $this->data->answer;
            } else {
                $answer = $this->data->answer;
            }

            if (!$choiceavailable) {
                $reason = current(array_keys($warnings));
                throw new moodle_exception($reason, 'choice', '', $warnings[$reason]);
            }

            if ($answer && is_enrolled($this->context, null, 'mod/choice:choose')) {
                choice_user_submit_response($answer, $choice, $USER->id, $course, $this->cm);
            } else if (empty($answer) && $action === 'makechoice') {
                // We cannot use the 'makechoice' alone because there might be some legacy renderers without it,
                // outdated renderers will not get the 'mustchoose' message - bad luck.
                redirect(new moodle_url('/mod/choice/view.php',
                    array('id' => $this->cm->id, 'notify' => 'mustchooseone', 'sesskey' => sesskey())));
            }
        }

        choice_view($choice, $course, $this->cm, $this->context);

        // Display the choice and possibly results.
        $eventdata = array();
        $eventdata['objectid'] = $choice->id;
        $eventdata['context'] = $this->context;

        // Check to see if groups are being used in this choice.
        $groupmode = groups_get_activity_groupmode($this->cm);

        $content = '<div class="clearer"></div>';
        if (isset($this->data->group)) {
            if (has_capability('moodle/site:accessallgroups', $this->context)) {
                $SESSION->activegroup[$this->course->id]['aag'][0] = $this->data->group;
            } else {
                $SESSION->activegroup[$this->course->id][$groupmode][0] = $this->data->group;
            }
        }

        ob_start();
        if ($groupmode) {
            groups_get_activity_group($this->cm, true);
            groups_print_activity_menu($this->cm, $CFG->wwwroot . '/mod/choice/view.php?id=' . $this->context->instanceid);
        }

        // Check if we want to include responses from inactive users.
        $onlyactive = $choice->includeinactive ? false : true;

        // Big function, approx 6 SQL calls per user.
        $allresponses = choice_get_response_data($choice, $this->cm, $groupmode, $onlyactive);

        if (has_capability('mod/choice:readresponses', $this->context)) {
            choice_show_reportlink($allresponses, $this->cm);
        }
        $content .= ob_get_contents();
        ob_end_clean();

        if ($choice->intro) {
            $content .= $OUTPUT->box(format_module_intro('choice', $choice, $this->cm->id), 'generalbox', 'intro');
        }

        $timenow = time();
        $current = choice_get_my_response($choice);
        // If user has already made a selection, and they are not allowed to update it
        // or if choice is not open, show their selected answer.
        if (isloggedin() && (!empty($current)) &&
            (empty($choice->allowupdate) || ($timenow > $choice->timeclose)) ) {
            $choicetexts = array();
            foreach ($current as $c) {
                $choicetexts[] = format_string(choice_get_option_text($choice, $c->optionid));
            }
            $content .= $OUTPUT->box(
                get_string("yourselection", "choice",
                userdate($choice->timeopen)) . ": " . implode('; ', $choicetexts),
                'generalbox',
                'yourselection'
            );
        }

        // Print the form.
        $choiceopen = true;
        if ((!empty($choice->timeopen)) && ($choice->timeopen > $timenow)) {
            if ($choice->showpreview) {
                $content .= $OUTPUT->box(get_string('previewonly', 'choice', userdate($choice->timeopen)), 'generalbox alert');
            } else {
                $content .= $OUTPUT->box(get_string("notopenyet", "choice", userdate($choice->timeopen)), "generalbox notopenyet");
                return $content;
            }
        } else if ((!empty($choice->timeclose)) && ($timenow > $choice->timeclose)) {
            $content .= $OUTPUT->box(get_string("expired", "choice", userdate($choice->timeclose)), "generalbox expired");
            $choiceopen = false;
        }

        if ( (!$current || $choice->allowupdate) && $choiceopen && is_enrolled($this->context, null, 'mod/choice:choose')) {

            // Show information on how the results will be published to students.
            $publishinfo = null;
            switch ($choice->showresults) {
                case CHOICE_SHOWRESULTS_NOT:
                    $publishinfo = get_string('publishinfonever', 'choice');
                    break;

                case CHOICE_SHOWRESULTS_AFTER_ANSWER:
                    if ($choice->publish == CHOICE_PUBLISH_ANONYMOUS) {
                        $publishinfo = get_string('publishinfoanonafter', 'choice');
                    } else {
                        $publishinfo = get_string('publishinfofullafter', 'choice');
                    }
                    break;

                case CHOICE_SHOWRESULTS_AFTER_CLOSE:
                    if ($choice->publish == CHOICE_PUBLISH_ANONYMOUS) {
                        $publishinfo = get_string('publishinfoanonclose', 'choice');
                    } else {
                        $publishinfo = get_string('publishinfofullclose', 'choice');
                    }
                    break;

                default:
                    // No need to inform the user in the case of CHOICE_SHOWRESULTS_ALWAYS
                    // since it's already obvious that the results are being published.
                    break;
            }

            // Show info if necessary.
            if (!empty($publishinfo)) {
                $content .= $OUTPUT->notification($publishinfo, 'info');
            }

            // They haven't made their choice yet or updates allowed and choice is open.
            $options = choice_prepare_options($choice, $USER, $this->cm, $allresponses);
            $renderer = $PAGE->get_renderer('mod_choice');
            $content .= $renderer->display_options($options, $this->cm->id, $choice->display, $choice->allowmultiple);
            $choiceformshown = true;
        } else {
            $choiceformshown = false;
        }

        if (!$choiceformshown) {
            if (isguestuser()) {
                // Guest account.
                $content .= $OUTPUT->confirm(get_string('noguestchoose', 'choice').'<br /><br />'.get_string('liketologin'),
                             get_login_url(), new moodle_url('/course/view.php', array('id' => $course->id)));
            } else if (!is_enrolled($this->context)) {
                // Only people enrolled can make a choice.
                $SESSION->wantsurl = qualified_me();
                $SESSION->enrolcancel = get_local_referer(false);

                $coursecontext = context_course::instance($course->id);
                $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));

                $content .= $OUTPUT->box_start('generalbox', 'notice');
                $content .= '<p align="center">'. get_string('notenrolledchoose', 'choice') .'</p>';
                $content .= $OUTPUT->container_start('continuebutton');
                $content .= $OUTPUT->single_button(new moodle_url('/enrol/index.php?', array(
                    'id' => $course->id,
                )), get_string('enrolme', 'core_enrol', $courseshortname));
                $content .= $OUTPUT->container_end();
                $content .= $OUTPUT->box_end();

            }
        }

        // Print the results at the bottom of the screen.
        if (choice_can_view_results($choice, $current, $choiceopen)) {
            $results = prepare_choice_show_results($choice, $course, $this->cm, $allresponses);
            $renderer = $PAGE->get_renderer('mod_choice');
            $resultstable = $renderer->display_result($results);
            $content .= $OUTPUT->box($resultstable);

        } else if (!$choiceformshown) {
            $content .= $OUTPUT->box(get_string('noresultsviewable', 'choice'));
        }

        $PAGE->requires->js_call_amd('format_popups/form', 'init', array($this->context->id, $this->cm->modname));
        $PAGE->requires->js_call_amd('format_popups/choice', 'init', array($this->context->id, $this->cm->modname));

        return $content;
    }
}
