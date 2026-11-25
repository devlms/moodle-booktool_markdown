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
 * Tests for splitting Markdown into chapters.
 *
 * @package    booktool_markdown
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \booktool_markdown\splitter
 */
final class splitter_test extends \basic_testcase {
    /** @var string A Markdown code fence (three backticks). */
    private const FENCE = "\x60\x60\x60";

    /**
     * Level 1 headings become chapters and level 2 headings subchapters.
     */
    public function test_h1_chapters_h2_subchapters(): void {
        $md = "# One\nText one.\n\n## One A\nText A.\n\n### Deep\nStays inside.\n\n# Two\nText two.\n";
        $parts = splitter::split($md, true);
        $this->assertCount(3, $parts);
        $this->assertSame(['One', false], [$parts[0]->title, $parts[0]->subchapter]);
        $this->assertSame(['One A', true], [$parts[1]->title, $parts[1]->subchapter]);
        $this->assertSame(['Two', false], [$parts[2]->title, $parts[2]->subchapter]);
        $this->assertSame("Text one.", $parts[0]->markdown);
        $this->assertStringContainsString('### Deep', $parts[1]->markdown);
    }

    /**
     * Without subchapters, level 2 headings stay inside their chapter.
     */
    public function test_no_subchapters(): void {
        $parts = splitter::split("# One\nA\n## Sub\nB\n# Two\nC", false);
        $this->assertCount(2, $parts);
        $this->assertStringContainsString('## Sub', $parts[0]->markdown);
    }

    /**
     * When the text has no level 1 heading, the highest level found makes the chapters.
     */
    public function test_top_level_is_the_highest_found(): void {
        $parts = splitter::split("## A\nx\n### A1\ny\n## B\nz", true);
        $this->assertSame(['A', 'A1', 'B'], array_column($parts, 'title'));
        $this->assertSame([false, true, false], array_column($parts, 'subchapter'));
    }

    /**
     * Headings inside fenced code blocks are not chapters.
     */
    public function test_code_fences_are_ignored(): void {
        $md = "# Shell\n" . self::FENCE . "bash\n# not a heading\necho hi\n" . self::FENCE . "\n~~~\n# also not\n~~~\n# Next\nok";
        $parts = splitter::split($md, true);
        $this->assertSame(['Shell', 'Next'], array_column($parts, 'title'));
        $this->assertStringContainsString('# not a heading', $parts[0]->markdown);
    }

    /**
     * Text before the first heading becomes its own chapter.
     */
    public function test_text_before_first_heading(): void {
        $parts = splitter::split("Welcome.\n\n# One\nA", true);
        $this->assertCount(2, $parts);
        $this->assertSame('', $parts[0]->title);
        $this->assertSame('Welcome.', $parts[0]->markdown);
    }

    /**
     * Blank text before the first heading makes no chapter.
     */
    public function test_blank_preamble_skipped(): void {
        $parts = splitter::split("\n\n  \n# One\nA", true);
        $this->assertCount(1, $parts);
    }

    /**
     * Text with no headings at all is one chapter without a title.
     */
    public function test_no_headings(): void {
        $parts = splitter::split("Just a paragraph.\n\nAnd another.", true);
        $this->assertCount(1, $parts);
        $this->assertSame('', $parts[0]->title);
    }

    /**
     * YAML front matter (Obsidian, Notion, static sites) is dropped.
     */
    public function test_front_matter_dropped(): void {
        $parts = splitter::split("---\ntitle: Notes\ntags: [a]\n---\n# One\nA", true);
        $this->assertCount(1, $parts);
        $this->assertSame('One', $parts[0]->title);
    }

    /**
     * Titles lose closing hashes and inline Markdown, and Windows line ends work.
     */
    public function test_titles_are_plain_text(): void {
        $parts = splitter::split("# **Week 1**: \x60intro\x60 ##\r\nA\r\n#    Spaced   \r\nB", true);
        $this->assertSame('Week 1: intro', $parts[0]->title);
        $this->assertSame('Spaced', $parts[1]->title);
        $this->assertSame('A', $parts[0]->markdown);
    }

    /**
     * A hash without a space after it (a hashtag) is not a heading.
     */
    public function test_hashtag_is_not_heading(): void {
        $parts = splitter::split("# One\n#hashtag here\n", true);
        $this->assertCount(1, $parts);
        $this->assertStringContainsString('#hashtag', $parts[0]->markdown);
    }

    /**
     * Long titles are cut to fit the chapter title column.
     */
    public function test_long_title_cut(): void {
        $parts = splitter::split('# ' . str_repeat('word ', 80), true);
        $this->assertLessThanOrEqual(255, \core_text::strlen($parts[0]->title));
    }
}
