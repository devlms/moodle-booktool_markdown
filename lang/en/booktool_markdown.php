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
 * English strings.
 *
 * @package    booktool_markdown
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['chaptercount'] = '{$a} chapters were added to the end of the book.';
$string['chaptercountone'] = '1 chapter was added to the end of the book.';
$string['doimport'] = 'Import';
$string['errorboth'] = 'Paste Markdown or choose a file, not both.';
$string['errornothing'] = 'Paste some Markdown or choose a file.';
$string['file'] = 'Markdown file';
$string['file_help'] = 'A .md or .txt file. Images must use full web addresses (https://...); images inside a zip are not imported.';
$string['import'] = 'Import from Markdown';
$string['importedchapters'] = 'Imported chapters';
$string['intro'] = 'Each top heading becomes a chapter. Text in ChatGPT, Notion, Obsidian, GitHub or any other Markdown works. Chapters are added at the end of the book.';
$string['markdown:import'] = 'Import chapters from Markdown';
$string['pluginname'] = 'Markdown import';
$string['privacy:metadata'] = 'The Markdown import plugin does not store any personal data. Imported chapters belong to the Book activity.';
$string['subchapters'] = 'Make subchapters from the next heading level';
$string['subchapters_help'] = 'If the top headings are level 1 (#), level 2 headings (##) become subchapters. If off, they stay inside their chapter. Deeper headings always stay inside the chapter.';
$string['text'] = 'Markdown text';
$string['untitled'] = 'Introduction';
