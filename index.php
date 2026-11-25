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
 * Import Markdown as book chapters.
 *
 * @package    booktool_markdown
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../config.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('book', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$book = $DB->get_record('book', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('booktool/markdown:import', $context);

$url = new moodle_url('/mod/book/tool/markdown/index.php', ['id' => $cm->id]);
$bookurl = new moodle_url('/mod/book/view.php', ['id' => $cm->id]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('import', 'booktool_markdown') . ' | ' . format_string($book->name));
$PAGE->set_heading($course->fullname);
$PAGE->activityheader->set_attrs(['hidecompletion' => true, 'description' => '']);

$form = new \booktool_markdown\form\import_form($url);
$form->set_data(['id' => $cm->id]);

if ($form->is_cancelled()) {
    redirect($bookurl);
}
$markdown = $form->get_markdown();
if ($markdown !== null) {
    $added = \booktool_markdown\importer::import($book, $context, $markdown, !empty($form->get_data()->subchapters));
    if (!$added) {
        redirect($url, get_string('errornothing', 'booktool_markdown'), null, \core\output\notification::NOTIFY_ERROR);
    }
    $message = count($added) === 1 ? get_string('chaptercountone', 'booktool_markdown')
        : get_string('chaptercount', 'booktool_markdown', count($added));
    redirect(
        new moodle_url($bookurl, ['chapterid' => $added[0]->id]),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('import', 'booktool_markdown'), 3);
$form->display();
echo $OUTPUT->footer();
