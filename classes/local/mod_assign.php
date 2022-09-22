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
 * Activity renderer for Popups course format
 * extends classes from the core assign module
 * for rendering SUBMIT, EDIT, and VIEW content in popups
 *
 * @package    format_popups
 * @copyright  2022 Manuel Mejia <manimejia.me@gmail.com>
 *             adapted from Moodle mod_assign
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/config.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/form/classes/dynamic_form.php');
require_once($CFG->dirroot. '/mod/assign/locallib.php');
require_once($CFG->dirroot. '/mod/assign/renderer.php');
require_once($CFG->dirroot . '/mod/assign/submission_form.php');

/**
 * class instantiated by format_popups
 * to render the content within a modal
 */
class mod_assign extends  mod_page {

  public function __construct($cm, $context, $course, $data, $path) {
    parent::__construct($cm, $context, $course, $data, $path);
    global $PAGE;
    global $_GET;
    $this->data = (array) $this->data;
    // provide default value for 'action' param
    $this->action = $this->data["action"]  ? $this->data["action"]  : 'editsubmission';

    // instanciate our custom assign class
    // $context = $this->mod->context;
    $context = \context_module::instance($this->cm->id);
    $this->assign = new assign($context, $this->cm, $this->course);

    // register javascript file for inline AMD class loading
    $require_inline = json_encode(
        ['paths' =>['format_popups/assign' =>
        '/course/format/popups/amd/inline/assign']]);
    $PAGE->requires->js_amd_inline('require.config('.$require_inline.')');
  }

  /**
  * method called by format_popups
  */
  public function render(){
    global $PAGE;
    global $USER;
    $assign = $this->assign;

    // from : /mod/assign/view.php
    // Update module completion status.
    $assign->set_module_viewed();
    // Apply overrides.
    $assign->update_effective_access($USER->id);
    // Get the assign class to render the page.
    $o .= $assign->view($this->action,$this->data);

    // call init on preloaded javascript AMD classes
    $PAGE->requires->js_call_amd('format_popups/assign', 'init', array($this->context->id, $this->cm->modname));

    return $o;
  }
}

/**
* extending the default assign class in mod/assign/locallib.php
* assign a custom output (core) renderer and override some classes
*/
class assign extends \assign {
  /**
   * Override the get_renderer method
   * to lazy load our custom renderer
   */
  public function get_renderer() {
      global $PAGE;
      if ($this->output) {
          return $this->output;
      }
      $this->output = new assign_renderer($PAGE, RENDERER_TARGET_GENERAL);
      return $this->output;
  }

  /**
   * Overriden to add formdata property to class instance.
   * for passing as ajaxdata to mforms instantiated in later methods
   * AND to override ALL calls to weblib.php\redirect()
   * which is called whenever $action == 'redirect'
   * and is NOT AJAX compatible. (who writes this stuff?)
   */
  public function view($action='', $args = array()) {
    // make $args (ajax formdata) available to other methods
    $this->formdata = $args;
    // instanciate variables same as parent method
    global $PAGE;
    $o = '';
    $mform = null;
    $notices = array();
    // begin redirect overrides
    if ($action == 'savesubmission') {
      if ($this->process_save_submission($mform, $notices)) {
      }
      return $this->view();
    }
    if ($action == 'editsubmission') {
      $o .= $this->view_edit_submission_page($mform, $notices);
    } else {
      $o .= $this->view_submission_page();
    }
    // $o = parent::view($action, $args);
    return $o;
  }

  /**
   * Save assignment submission.
   * This method was overriden simply to change one line
   * to instansiate the mform WITH passed ajax formdata
   *
   * TODO fix bug in this AJAX form submission where htmlspecialcharacters
   * < > and & are removed ALONG WITH any input text that follows.
   * This bug ONLY manifests when submitting from this assign class
   * and MAY have to do with moodle content filters for AJAX forms.
   *
   * @param  moodleform $mform
   * @param  array $notices Any error messages that should be shown
   *                        to the user at the top of the edit submission form.
   * @return bool
   */
  protected function process_save_submission(&$mform, &$notices) {
    global $CFG, $USER;

    $userid = optional_param('userid', $USER->id, PARAM_INT);
    // Need submit permission to submit an assignment.
    require_sesskey();
    if (!$this->submissions_open($userid)) {
        $notices[] = get_string('duedatereached', 'assign');
        return false;
    }
    $instance = $this->get_instance();

    $data = new \stdClass();
    $data->userid = $userid;
    // CHANGED one line to instantiate mform with ajax formdata
    // TODO refactor this to use moodle dynamic_form
    // @see https://docs.moodle.org/dev/Modal_and_AJAX_forms
    $mform = new \mod_assign_submission_form(null, array($this, $data),'post','',null,true,$this->formdata);

    if ($mform->is_cancelled()) {
        return true;
    }
    if ($data = $mform->get_data()) {
        return $this->save_submission($data, $notices);
    }
    return false;
  }
}

/**
 * custom renderer for assign module content
 * to be displayed in popup modals
 */
class assign_renderer extends \mod_assign_renderer{

  /**
   * Constructor method, calls the parent constructor
   * overridden here to use a custom core renderer
   * for formatting page content within popup modals
   *
   * @param \moodle_page $page
   * @param string $target one of rendering target constants
   */
  public function __construct(\moodle_page $page, $target) {
    parent::__construct($page, $target);
    $this->output = new assign_core_renderer($page, $target);

  }

   /**
     * Render the header.
     * overridden to remove page title and activity info
     * @param assign_header $header
     * @return string
     */
    public function render_assign_header(\assign_header $header) {
      // CHANGED removed all output prior to the assignment intro content
      $o = '';
      $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
      $o .= format_module_intro('assign', $header->assign, $header->coursemoduleid);
      $o .= $header->postfix;
      $o .= $this->output->box_end();
      return $o;
    }
     /**
     * Render the generic form
     * overridden to (via CSS) hide the form label column
     *
     * TODO decode where specialchars have been added to input (if needed)
     *
     * @param assign_form $form The form to render
     * @return string
     */
    public function render_assign_form(\assign_form $form) {
      $o = '';
      $classname = $form->classname;
      if ($form->jsinitfunction) {
          $this->page->requires->js_init_call($form->jsinitfunction, array());
      }
      $o .= $this->output->box_start("boxaligncenter $classname");
      $o .= $this->moodleform($form->form);
      $o .= $this->output->box_end();
      // CHANGED hack CSS to style pre-rendered HTML
      $o .= "<style> .$classname .col-form-label { display:none !important }</style>";
      return $o;
  }

    /**
     * overwrites \mod_assign_renderer::render_assign_submission_status
     * to render submission content but NOT the submission status table in output.
     * This handles the case where students may not EDIT submissions, by rendering
     * ONLY their submitted content in activity popups, rather than including
     * grading status, timestamp, comments, and other additional info.
     *
     * @param \assign_submission_status $status
     * @return string
     */
    public function render_assign_submission_status(\assign_submission_status $status) {
      $o = '';
      $o .= $this->output->container_start('submissionstatustable');
      // do not output heading
      $time = time();

      $o .= $this->output->box_start('submissiondisplaybox');

      // do not output table or table cells

      $warningmsg = '';
      // ...
      $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;
      // ...

      // only output submission
      if ($submission) {
          // ... do not output time stamp
          if (!$status->teamsubmission || $status->submissiongroup != false || !$status->preventsubmissionnotingroup) {
              // ... do not loop through submissionplugins (submission & comments) only output the first
                  $plugin = $status->submissionplugins[0];
                  $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
                  if ($plugin->is_enabled() &&
                      $plugin->is_visible() &&
                      $plugin->has_user_summary() &&
                      $pluginshowsummary
                  ) {
                      // ... do not output name, only content
                      $displaymode = \assign_submission_plugin_submission::FULL;
                      $pluginsubmission = new \assign_submission_plugin_submission($plugin,
                          $submission,
                          $displaymode,
                          $status->coursemoduleid,
                          $status->returnaction,
                          $status->returnparams);
                      // send submission content directly to output variable
                      $o .= $this->render($pluginsubmission);
                      // ... do not output table cell.
                  }
          }
      }

      $o .= $warningmsg;
      // ... do not output table
      $o .= $this->output->box_end();

      // output links as usual.
      if ($status->view == \assign_submission_status::STUDENT_VIEW) {
          if ($status->canedit) {
              if (!$submission || $submission->status == ASSIGN_SUBMISSION_STATUS_NEW) {
                  $o .= $this->output->box_start('generalbox submissionaction');
                  $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                  $o .= $this->output->single_button(new \moodle_url('/mod/assign/view.php', $urlparams),
                                                     get_string('addsubmission', 'assign'), 'get');
                  $o .= $this->output->box_start('boxaligncenter submithelp');
                  $o .= get_string('addsubmission_help', 'assign');
                  $o .= $this->output->box_end();
                  $o .= $this->output->box_end();
              } else if ($submission->status == ASSIGN_SUBMISSION_STATUS_REOPENED) {
                  $o .= $this->output->box_start('generalbox submissionaction');
                  $urlparams = array('id' => $status->coursemoduleid,
                                     'action' => 'editprevioussubmission',
                                     'sesskey'=>sesskey());
                  $o .= $this->output->single_button(new \moodle_url('/mod/assign/view.php', $urlparams),
                                                     get_string('addnewattemptfromprevious', 'assign'), 'get');
                  $o .= $this->output->box_start('boxaligncenter submithelp');
                  $o .= get_string('addnewattemptfromprevious_help', 'assign');
                  $o .= $this->output->box_end();
                  $o .= $this->output->box_end();
                  $o .= $this->output->box_start('generalbox submissionaction');
                  $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                  $o .= $this->output->single_button(new \moodle_url('/mod/assign/view.php', $urlparams),
                                                     get_string('addnewattempt', 'assign'), 'get');
                  $o .= $this->output->box_start('boxaligncenter submithelp');
                  $o .= get_string('addnewattempt_help', 'assign');
                  $o .= $this->output->box_end();
                  $o .= $this->output->box_end();
              } else {
                  $o .= $this->output->box_start('generalbox submissionaction');
                  $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                  $o .= $this->output->single_button(new \moodle_url('/mod/assign/view.php', $urlparams),
                                                     get_string('editsubmission', 'assign'), 'get');
                  $urlparams = array('id' => $status->coursemoduleid, 'action' => 'removesubmissionconfirm');
                  $o .= $this->output->single_button(new \moodle_url('/mod/assign/view.php', $urlparams),
                                                     get_string('removesubmission', 'assign'), 'get');
                  $o .= $this->output->box_start('boxaligncenter submithelp');
                  $o .= get_string('editsubmission_help', 'assign');
                  $o .= $this->output->box_end();
                  $o .= $this->output->box_end();
              }
          }

          if ($status->cansubmit) {
              $urlparams = array('id' => $status->coursemoduleid, 'action'=>'submit');
              $o .= $this->output->box_start('generalbox submissionaction');
              $o .= $this->output->single_button(new \moodle_url('/mod/assign/view.php', $urlparams),
                                                 get_string('submitassignment', 'assign'), 'get');
              $o .= $this->output->box_start('boxaligncenter submithelp');
              $o .= get_string('submitassignment_help', 'assign');
              $o .= $this->output->box_end();
              $o .= $this->output->box_end();
          }
      }

      $o .= $this->output->container_end();
      return $o;
  }

}

/*
 * custon core renderer to hide header and footer
 * when rendering assign renderer content in modals.
 */
class assign_core_renderer extends \core_renderer {
  public function header() {
    return '';
  }
  public function footer(){
    return '';
  }
}
