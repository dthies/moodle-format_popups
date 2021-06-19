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
 * Class containing the external API functions for the Popup activies format
 *
 * @package     format_popups
 * @copyright   2021 Daniel Thies <dethies@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/course/format/popups/lib.php');

use coding_exception;
use core\notification;
use external_api;
use external_description;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use invalid_parameter_exception;
use moodle_exception;
use required_capability_exception;
use restricted_context_exception;
use context_course;
use context_helper;

/**
 * Class external.
 *
 * Class containing the external API functions for the Popup activies format
 *
 * @copyright   2021 Daniel Thies <dethies@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Parameter description for get_available_mods().
     *
     * @return external_function_parameters
     */
    public static function get_available_mods_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The course context'),
        ]);
    }

    /**
     * Get list of mods available as popups
     *
     * @param int $contextid The course module id
     * @return array
     * @throws invalid_persistent_exception
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_available_mods($contextid) {
        external_api::validate_parameters(self::get_available_mods_parameters(), [
            'contextid' => $contextid,
        ]);

        // Validate context and access to manage the registry.
        $context = context_helper::instance_by_id($contextid);
        self::validate_context($context);

        $course = get_course($context->instanceid);

        $modules = format_popups_mods_available($course);

        return $modules;
    }

    /**
     * Parameter description for get_available_mods().
     *
     * @return external_description
     */
    public static function get_available_mods_returns() {
        return new external_multiple_structure(new external_single_structure(
            [
                'contextid' => new external_value(PARAM_INT, 'Module context ID.'),
                'id' => new external_value(PARAM_INT, 'Course module ID.'),
                'modname' => new external_value(PARAM_TEXT, 'Module type.'),
                'title' => new external_value(PARAM_TEXT, 'Module type.'),
            ]
        ));
    }
}
