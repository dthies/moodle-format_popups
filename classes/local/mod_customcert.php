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
 *             adapted from mod_customcert 2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

use stdClass;
use context_user;
use core_tag_tag;
use html_writer;
use moodle_url;

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_customcert extends mod_url {
    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $OUTPUT;

        require_capability('mod/customcert:view', $this->context);

        $url = new moodle_url('/mod/customcert/view.php', [
            'id' => $this->cm->id,
            'downloadown' => 1,
        ]);

        return $OUTPUT->render_from_template('format_popups/embedfile', [
            'downloadurl' => $url->out(false),
            'url' => $url->out(false),
            'params' => [
                ['name' => 'id', 'value'  => $this->cm->id],
                ['name' => 'downloadown', 'value'  => 1],
            ],
        ]);
    }
}
