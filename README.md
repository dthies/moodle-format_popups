# Pop up activities format #

The Pop up activities course format displays resources and simple
activities embedded in modals instead of redirecting from the course
page.

If you are not using heavy server side assessment, but mostly resources and
client side activities (SCORM, H5P), this format may help provide more
interactive courses by opening the activities in popup modals instead
of separate web pages. When students finish an activity or resource,
they return to the course page by closing the modal without waiting for
the course page to load again.

This plugin uses javascript to add AJAX functionality to the
standard topics format. Otherwise the format is the same as topics
format. Currently Book, Choice, Custom certificate, File resource,
Folder, H5P, Page, Poster, SCORM, URL, Video Time activities are supported
and can be displayed in modals. Other activities will open normally.

Developers should be able to add similar functionality to other formats by
copying the AMD call in format.php and adding this format as a dependency.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted
   to add extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.
4. The capability *format/popups:view* is given to the student role
   and controls whether an individual user sees an activity as a modal
   or in the standard view. Add this to any other role you would like to
   see the modals.
5. When a course is created select this format from the menu in the
   Course format setting  and then add the some of the supported activities
   to the course.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/course/format/popups

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2021 Daniel Thies <dethies@gmail.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
