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
 * Implementation of the quizaccess_savenow plugin.
 *
 * @package     quizaccess_savenow
 * @copyright   2026 UniDistance Suisse
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;

/**
 * Adds a "Save my answers" button (and the Ctrl+S / Cmd+S shortcut) to quiz attempt pages.
 *
 * The answers are saved through the normal quiz navigation: the attempt form is submitted
 * and the student comes back to the same page. The attempt is neither finished nor submitted.
 *
 * @package     quizaccess_savenow
 * @copyright   2026 UniDistance Suisse
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_savenow extends access_rule_base {
    /** @var string Database table holding the per-quiz setting. */
    public const TABLE = 'quizaccess_savenow';

    /**
     * Return an instance of this rule if it is enabled for the quiz.
     *
     * @param quiz_settings $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param bool $canignoretimelimits whether the current user is exempt from time limits.
     * @return self|null the rule, if applicable, else null.
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        if (empty($quizobj->get_quiz()->savenow_enabled)) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    /**
     * Add the setting to the "Extra restrictions on attempts" section of the quiz settings form.
     *
     * @param mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $mform->addElement('selectyesno', 'savenow_enabled', get_string('enabled', 'quizaccess_savenow'));
        $mform->setDefault('savenow_enabled', 1);
        $mform->addHelpButton('savenow_enabled', 'enabled', 'quizaccess_savenow');
    }

    /**
     * Save the setting when the quiz settings form is submitted.
     *
     * @param stdClass $quiz the data from the quiz form, including $quiz->id.
     */
    public static function save_settings($quiz) {
        global $DB;
        if (empty($quiz->savenow_enabled)) {
            $DB->delete_records(self::TABLE, ['quizid' => $quiz->id]);
        } else if (!$DB->record_exists(self::TABLE, ['quizid' => $quiz->id])) {
            $DB->insert_record(self::TABLE, (object) ['quizid' => $quiz->id, 'enabled' => 1]);
        }
    }

    /**
     * Delete the setting when the quiz is deleted.
     *
     * @param stdClass $quiz the quiz being deleted.
     */
    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records(self::TABLE, ['quizid' => $quiz->id]);
    }

    /**
     * Load the setting together with the other quiz settings.
     *
     * @param int $quizid the id of the quiz we are loading settings for.
     * @return array with three elements: fields, joins and parameters.
     */
    public static function get_settings_sql($quizid) {
        return [
            'COALESCE(quizaccess_savenow.enabled, 0) AS savenow_enabled',
            'LEFT JOIN {quizaccess_savenow} quizaccess_savenow ON quizaccess_savenow.quizid = quiz.id',
            [],
        ];
    }

    /**
     * Load the JavaScript that adds the button, on the attempt page only.
     *
     * @param moodle_page $page the page object to initialise.
     */
    public function setup_attempt_page($page) {
        if ($page->pagetype === 'mod-quiz-attempt') {
            $page->requires->js_call_amd('quizaccess_savenow/save', 'init');
        }
    }
}
