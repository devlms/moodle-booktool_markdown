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
 * Tests for importing Markdown into a book.
 *
 * @package    booktool_markdown
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \booktool_markdown\importer
 */
final class importer_test extends \advanced_testcase {
    /** @var string A Markdown code fence (three backticks). */
    private const FENCE = "\x60\x60\x60";

    /** @var \stdClass */
    protected $book;
    /** @var \context_module */
    protected $context;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->book = $this->getDataGenerator()->create_module('book', ['course' => $course->id]);
        $this->context = \context_module::instance($this->book->cmid);
    }

    /**
     * Get the chapters of the book in order.
     *
     * @return \stdClass[]
     */
    protected function chapters(): array {
        global $DB;
        return array_values($DB->get_records('book_chapters', ['bookid' => $this->book->id], 'pagenum'));
    }

    public function test_imports_chapters_after_existing_ones(): void {
        global $DB;
        $this->getDataGenerator()->get_plugin_generator('mod_book')->create_chapter(
            ['bookid' => $this->book->id, 'title' => 'Existing', 'content' => 'x']
        );
        $revision = $DB->get_field('book', 'revision', ['id' => $this->book->id]);

        $added = importer::import($this->book, $this->context, "# One\nSome **bold** text.\n## One A\nSub.\n# Two\nEnd.", true);

        $this->assertCount(3, $added);
        $chapters = $this->chapters();
        $this->assertSame(['Existing', 'One', 'One A', 'Two'], array_column($chapters, 'title'));
        $this->assertEquals([1, 2, 3, 4], array_column($chapters, 'pagenum'));
        $this->assertEquals([0, 0, 1, 0], array_column($chapters, 'subchapter'));
        $this->assertEquals(FORMAT_HTML, $chapters[1]->contentformat);
        $this->assertStringContainsString('<strong>bold</strong>', $chapters[1]->content);
        $this->assertEquals($revision + 1, $DB->get_field('book', 'revision', ['id' => $this->book->id]));
    }

    public function test_untitled_first_part_gets_a_title(): void {
        importer::import($this->book, $this->context, "Welcome.\n# One\nA", true);
        $this->assertSame([get_string('untitled', 'booktool_markdown'), 'One'], array_column($this->chapters(), 'title'));
    }

    public function test_first_chapter_of_empty_book_is_never_a_subchapter(): void {
        importer::import($this->book, $this->context, "## A\nx\n# B\ny", true);
        $this->assertEquals([0, 0], array_column($this->chapters(), 'subchapter'));
    }

    public function test_dangerous_html_is_removed(): void {
        importer::import(
            $this->book,
            $this->context,
            "# One\n<script>alert(1)</script>\n<a href=\"javascript:alert(2)\">x</a>\n<img src=x onerror=alert(3)>",
            true
        );
        $content = $this->chapters()[0]->content;
        $this->assertStringNotContainsString('<script', $content);
        $this->assertStringNotContainsString('javascript:', $content);
        $this->assertStringNotContainsString('onerror', $content);
    }

    public function test_events_are_triggered(): void {
        $sink = $this->redirectEvents();
        importer::import($this->book, $this->context, "# One\nA\n# Two\nB", true);
        $events = array_filter($sink->get_events(), fn($e) => $e instanceof \mod_book\event\chapter_created);
        $this->assertCount(2, $events);
    }

    public function test_empty_text_adds_nothing(): void {
        global $DB;
        $revision = $DB->get_field('book', 'revision', ['id' => $this->book->id]);
        $this->assertSame([], importer::import($this->book, $this->context, " \n\n", true));
        $this->assertSame([], $this->chapters());
        $this->assertEquals($revision, $DB->get_field('book', 'revision', ['id' => $this->book->id]));
    }

    public function test_code_blocks_keep_their_language(): void {
        importer::import($this->book, $this->context, "# Code\n" . self::FENCE . "php\necho 1;\n" . self::FENCE, true);
        $content = $this->chapters()[0]->content;
        $this->assertMatchesRegularExpression('/<pre[^>]*>\s*<code/', $content);
        $this->assertStringContainsString('echo 1;', $content);
    }

    public function test_normalise_text(): void {
        $this->assertSame('# Hi', importer::normalise_text("\xEF\xBB\xBF# Hi"));
        $this->assertSame('# Café', importer::normalise_text("# Caf\xE9"));
        $this->assertSame('# Café', importer::normalise_text('# Café'));
    }
}
