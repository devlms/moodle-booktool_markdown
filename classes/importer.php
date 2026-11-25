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

namespace booktool_markdown;

/**
 * Adds chapters made from Markdown to the end of a book.
 *
 * @package    booktool_markdown
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer {
    /**
     * Import Markdown text as chapters at the end of the book.
     *
     * The book shows chapter content without cleaning it (format_text with noclean), so the
     * HTML made from the Markdown is cleaned here, before it is stored.
     *
     * @param \stdClass $book the book record
     * @param \context_module $context the book's context
     * @param string $markdown the text
     * @param bool $subchapters whether the second heading level makes subchapters
     * @return \stdClass[] the chapters added, in order
     */
    public static function import(\stdClass $book, \context_module $context, string $markdown, bool $subchapters): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/book/locallib.php');

        $parts = splitter::split($markdown, $subchapters);
        if (!$parts) {
            return [];
        }

        $pagenum = (int) $DB->get_field_sql('SELECT MAX(pagenum) FROM {book_chapters} WHERE bookid = ?', [$book->id]);
        $now = time();
        $added = [];
        foreach ($parts as $part) {
            $chapter = (object) [
                'bookid' => $book->id,
                'pagenum' => ++$pagenum,
                'subchapter' => $part->subchapter ? 1 : 0,
                'title' => $part->title === '' ? get_string('untitled', 'booktool_markdown') : $part->title,
                'content' => clean_text(markdown_to_html($part->markdown), FORMAT_HTML),
                'contentformat' => FORMAT_HTML,
                'hidden' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
                'importsrc' => '',
            ];
            $chapter->id = $DB->insert_record('book_chapters', $chapter);
            $added[] = $chapter;
            \mod_book\event\chapter_created::create_from_chapter($book, $context, $chapter)->trigger();
        }

        // Renumbers pages and makes sure the book does not start with a subchapter.
        book_preload_chapters($book);
        $DB->set_field('book', 'revision', $DB->get_field('book', 'revision', ['id' => $book->id]) + 1, ['id' => $book->id]);
        return $added;
    }
}
