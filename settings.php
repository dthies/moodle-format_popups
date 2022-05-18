<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     format_popups
 * @category    admin
 * @copyright   2021 Daniel Thies <dethies@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    if ($ADMIN->fulltree) {
        // Define the plugin settings page - {@link https://docs.moodle.org/dev/Admin_settings}.
        $settings->add(new admin_setting_heading(
            'format_popups/defaults',
            new lang_string('coursesettings', 'moodle'),
            ''
        ));
        $settings->add(new admin_setting_configcheckbox( 'format_popups/addnavigation',
            new lang_string('addnavigation', 'format_popups'),
            new lang_string('addnavigation_help', 'format_popups'),
            0
        ));
        $settings->add(new admin_setting_configcheckbox(
            'format_popups/usecourseindex',
            new lang_string('usecourseindex', 'format_popups'),
            new lang_string('usecourseindex_help', 'format_popups'),
            0
        ));
    }
}
