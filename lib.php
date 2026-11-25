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
 * Library functions.
 *
 * @package    booktool_markdown
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add the import page to the book's settings menu.
 *
 * @param settings_navigation $settings
 * @param navigation_node $node the book's node
 */
function booktool_markdown_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    $cm = $settings->get_page()->cm;
    if ($cm && has_capability('booktool/markdown:import', $cm->context)) {
        $url = new moodle_url('/mod/book/tool/markdown/index.php', ['id' => $cm->id]);
        $node->add(get_string('import', 'booktool_markdown'), $url, navigation_node::TYPE_SETTING, null, 'importmarkdown');
    }
}
