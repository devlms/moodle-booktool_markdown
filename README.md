# Markdown import for Book (booktool_markdown)

Turn Markdown into chapters of a Moodle Book. Paste text or upload a `.md` file, and every top heading becomes a chapter, the next level a subchapter.

Course notes written in ChatGPT, Claude, Notion, Obsidian, Google Docs (Markdown export) or a GitHub repository are all Markdown. Moodle's Book can import a zip of HTML files, and its "one HTML file with headings as chapters" option has been marked unfinished in core for years. This plugin covers the Markdown case.

## Use it

1. Open a Book activity as a teacher.
2. In the **More** menu choose **Import from Markdown**.
3. Paste the text or choose a file, then press **Import**. The chapters are added at the end of the book.

## How the text is split

- The highest heading level in the text makes chapters. If it has `#` headings, those are chapters; if it starts at `##` (common in AI answers), those are.
- The next level makes subchapters (untick **Make subchapters from the next heading level** to keep them inside their chapter). Deeper headings stay in the chapter text.
- Text before the first heading becomes a chapter called "Introduction".
- Headings inside code blocks are not chapters. YAML front matter (`---` at the top, as Obsidian and static sites write) is dropped.
- Formatting, tables, lists, links, code blocks (with their language, for syntax highlighting) and images with full web addresses come across. Images stored inside a zip are not imported.

## Safety

Book shows chapter text without filtering it, so the HTML made from the Markdown is cleaned (scripts, event handlers and `javascript:` links removed) before it is saved. The capability `booktool/markdown:import` is given to editing teachers and managers, like Book's own import.

Moodle 4.5 to 5.3. Built by DevLMS: https://devlms.com
