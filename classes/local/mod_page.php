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
 *             adapted from Moodle mod_page
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

use stdClass;
use context_user;

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_page {

    /** @var string page path if submittend */
    protected $path = null;

    /** @var object $cm course module */
    protected $cm = null;

    /** @var object $course course for course module */
    protected $course = null;

    /** @var object $context context for course module */
    protected $context = null;

    /** @var object $data data for course module */
    protected $data = null;

    /**
     * Constructor
     *
     * @param object $cm course module
     * @param object $context object course module context
     * @param object $course course record
     * @param object $data search params
     * @param string $path requested file path
     */
    public function __construct($cm, $context, $course, $data, $path) {

        $this->cm = $cm;

        $this->course = $course;

        $this->context = $context;

        $this->path = $path;

        $this->data = $data;
    }

    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $DB;

        $page = $DB->get_record('page', array('id' => $this->cm->instance), '*', MUST_EXIST);
        require_capability('mod/page:view', $this->context);
        // Completion and trigger events.
        page_view($page, $this->course, $this->cm, $this->context);

        $content = file_rewrite_pluginfile_urls(
            $page->content,
            'pluginfile.php',
            $this->context->id,
            'mod_page',
            'content',
            $page->revision
        );
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $this->context;
        $content = format_text($content, $page->contentformat, $formatoptions);

        return $content;
    }
}
