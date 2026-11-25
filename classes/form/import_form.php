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

namespace booktool_markdown\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * The import form: paste Markdown or upload a file.
 *
 * @package    booktool_markdown
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {
    /**
     * Form fields.
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('static', 'intro', '', get_string('intro', 'booktool_markdown'));

        $mform->addElement(
            'textarea',
            'text',
            get_string('text', 'booktool_markdown'),
            ['rows' => 16, 'cols' => 80, 'class' => 'text-monospace', 'spellcheck' => 'false']
        );
        $mform->setType('text', PARAM_RAW);

        $mform->addElement(
            'filepicker',
            'importfile',
            get_string('file', 'booktool_markdown'),
            null,
            ['accepted_types' => ['.md', '.markdown', '.txt'], 'maxfiles' => 1]
        );
        $mform->addHelpButton('importfile', 'file', 'booktool_markdown');

        $mform->addElement('advcheckbox', 'subchapters', get_string('subchapters', 'booktool_markdown'));
        $mform->addHelpButton('subchapters', 'subchapters', 'booktool_markdown');
        $mform->setDefault('subchapters', 1);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('doimport', 'booktool_markdown'));
    }

    /**
     * Exactly one source: the text box or a file.
     *
     * @param array $data
     * @param array $files
     * @return array errors
     */
    public function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);
        $hastext = trim($data['text'] ?? '') !== '';
        $usercontext = \context_user::instance($USER->id);
        $hasfile = !empty($data['importfile']) &&
            !empty(get_file_storage()->get_area_files($usercontext->id, 'user', 'draft', $data['importfile'], 'id', false));
        if (!$hastext && !$hasfile) {
            $errors['text'] = get_string('errornothing', 'booktool_markdown');
        } else if ($hastext && $hasfile) {
            $errors['text'] = get_string('errorboth', 'booktool_markdown');
        }
        return $errors;
    }

    /**
     * The Markdown to import, from the text box or the uploaded file.
     *
     * @return string|null null when the form was not submitted
     */
    public function get_markdown(): ?string {
        $data = $this->get_data();
        if (!$data) {
            return null;
        }
        $text = (string) $data->text;
        if (trim($text) === '') {
            $text = (string) $this->get_file_content('importfile');
        }
        // Drop a UTF-8 byte order mark and make sure the text is UTF-8.
        if (str_starts_with($text, "\xEF\xBB\xBF")) {
            $text = substr($text, 3);
        }
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = \core_text::convert($text, 'windows-1252', 'utf-8');
        }
        return $text;
    }
}
