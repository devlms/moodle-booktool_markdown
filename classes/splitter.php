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
 * Splits Markdown text into book chapters at its headings.
 *
 * @package    booktool_markdown
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class splitter {
    /** @var int Length of the book_chapters title column. */
    public const TITLE_MAX = 255;

    /**
     * Split Markdown into chapters.
     *
     * The highest heading level in the text makes chapters. With $subchapters, the next level
     * makes subchapters. Deeper headings, and anything inside fenced code, stay in the chapter text.
     *
     * @param string $markdown the text
     * @param bool $subchapters whether the next heading level makes subchapters
     * @return \stdClass[] each with title (plain text, '' for text before the first heading),
     *                     markdown (the chapter body) and subchapter (bool)
     */
    public static function split(string $markdown, bool $subchapters): array {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $markdown));
        $lines = self::drop_front_matter($lines);

        // First pass: find headings outside code fences.
        $headings = [];
        $fence = null;
        foreach ($lines as $i => $line) {
            if (preg_match('/^ {0,3}(\x60{3,}|~{3,})/', $line, $m)) {
                if ($fence === null) {
                    $fence = $m[1];
                } else if ($m[1][0] === $fence[0] && strlen($m[1]) >= strlen($fence)) {
                    $fence = null;
                }
                continue;
            }
            if (
                $fence === null && preg_match('/^ {0,3}(#{1,6})[ \t]+(.*?)(?:[ \t]+#+)?[ \t]*$/', $line, $m)
                    && trim($m[2], " \t#") !== ''
            ) {
                $headings[$i] = [strlen($m[1]), $m[2]];
            }
        }

        $top = $headings ? min(array_column($headings, 0)) : 0;
        $breaks = [];
        foreach ($headings as $i => [$level, $text]) {
            if ($level === $top || ($subchapters && $level === $top + 1)) {
                $breaks[$i] = (object) ['title' => self::plain_title($text), 'subchapter' => $level !== $top];
            }
        }

        // Second pass: cut the text at the chapter headings.
        $parts = [];
        $current = (object) ['title' => '', 'subchapter' => false, 'lines' => []];
        foreach ($lines as $i => $line) {
            if (isset($breaks[$i])) {
                $parts[] = $current;
                $current = (object) ['title' => $breaks[$i]->title, 'subchapter' => $breaks[$i]->subchapter, 'lines' => []];
                continue;
            }
            $current->lines[] = $line;
        }
        $parts[] = $current;

        $result = [];
        foreach ($parts as $index => $part) {
            $body = trim(implode("\n", $part->lines));
            if ($index === 0 && $body === '') {
                continue;
            }
            $result[] = (object) ['title' => $part->title, 'markdown' => $body, 'subchapter' => $part->subchapter];
        }
        return $result;
    }

    /**
     * Remove YAML front matter (a block between two --- lines at the very start).
     *
     * @param string[] $lines
     * @return string[]
     */
    protected static function drop_front_matter(array $lines): array {
        if (($lines[0] ?? '') !== '---') {
            return $lines;
        }
        for ($i = 1; $i < count($lines); $i++) {
            if ($lines[$i] === '---' || $lines[$i] === '...') {
                return array_slice($lines, $i + 1);
            }
        }
        return $lines;
    }

    /**
     * Turn the Markdown of a heading into a plain text title.
     *
     * @param string $text
     * @return string
     */
    protected static function plain_title(string $text): string {
        $plain = html_entity_decode(strip_tags(markdown_to_html($text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = trim(preg_replace('/\s+/u', ' ', $plain));
        return \core_text::substr($plain, 0, self::TITLE_MAX);
    }
}
