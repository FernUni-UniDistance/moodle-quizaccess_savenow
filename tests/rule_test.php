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

namespace quizaccess_savenow;

use mod_quiz\quiz_settings;
use quizaccess_savenow;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/savenow/rule.php');

/**
 * Unit tests for the quizaccess_savenow rule.
 *
 * @package     quizaccess_savenow
 * @category    test
 * @copyright   2026 UniDistance Suisse
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \quizaccess_savenow
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\quizaccess_savenow::class)]
final class rule_test extends \advanced_testcase {
    /**
     * Create a quiz with the rule enabled or not.
     *
     * @param int|null $enabled value of the setting, null to leave it out.
     * @return \stdClass the quiz record.
     */
    private function create_quiz(?int $enabled): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        $record = ['course' => $course->id];
        if ($enabled !== null) {
            $record['savenow_enabled'] = $enabled;
        }
        return $this->getDataGenerator()->create_module('quiz', $record);
    }

    /**
     * The rule only applies to quizzes where it is enabled.
     *
     * @covers \quizaccess_savenow
     */
    public function test_make(): void {
        $this->resetAfterTest();

        $enabled = quiz_settings::create($this->create_quiz(1)->id);
        $this->assertEquals(1, $enabled->get_quiz()->savenow_enabled);
        $this->assertInstanceOf(quizaccess_savenow::class, quizaccess_savenow::make($enabled, time(), false));

        $disabled = quiz_settings::create($this->create_quiz(0)->id);
        $this->assertEquals(0, $disabled->get_quiz()->savenow_enabled);
        $this->assertNull(quizaccess_savenow::make($disabled, time(), false));

        // A quiz created without the setting (for example before the plugin was installed).
        $missing = quiz_settings::create($this->create_quiz(null)->id);
        $this->assertNull(quizaccess_savenow::make($missing, time(), false));
    }

    /**
     * The rule never restricts access to the quiz.
     *
     * @covers \quizaccess_savenow
     */
    public function test_rule_does_not_restrict_access(): void {
        $this->resetAfterTest();

        $quizobj = quiz_settings::create($this->create_quiz(1)->id);
        $rule = quizaccess_savenow::make($quizobj, time(), false);
        $attempt = new \stdClass();

        $this->assertFalse($rule->prevent_access());
        $this->assertEmpty($rule->description());
        $this->assertFalse($rule->prevent_new_attempt(0, $attempt));
        $this->assertFalse($rule->is_finished(0, $attempt));
        $this->assertFalse($rule->end_time($attempt));
        $this->assertFalse($rule->time_left_display($attempt, 0));
        $this->assertFalse($rule->is_preflight_check_required(null));
    }

    /**
     * Saving the setting adds or removes a single record.
     *
     * @covers \quizaccess_savenow
     */
    public function test_save_settings(): void {
        global $DB;
        $this->resetAfterTest();

        $quiz = $this->create_quiz(0);
        $this->assertFalse($DB->record_exists('quizaccess_savenow', ['quizid' => $quiz->id]));

        $quiz->savenow_enabled = 1;
        quizaccess_savenow::save_settings($quiz);
        quizaccess_savenow::save_settings($quiz);
        $this->assertEquals(1, $DB->count_records('quizaccess_savenow', ['quizid' => $quiz->id]));

        $quiz->savenow_enabled = 0;
        quizaccess_savenow::save_settings($quiz);
        $this->assertFalse($DB->record_exists('quizaccess_savenow', ['quizid' => $quiz->id]));
    }

    /**
     * Deleting the quiz deletes the setting.
     *
     * @covers \quizaccess_savenow
     */
    public function test_delete_settings(): void {
        global $DB;
        $this->resetAfterTest();

        $quiz = $this->create_quiz(1);
        $this->assertTrue($DB->record_exists('quizaccess_savenow', ['quizid' => $quiz->id]));

        quiz_delete_instance($quiz->id);
        $this->assertFalse($DB->record_exists('quizaccess_savenow', ['quizid' => $quiz->id]));
    }

    /**
     * The JavaScript is loaded on the attempt page only.
     *
     * @covers \quizaccess_savenow
     */
    public function test_setup_attempt_page(): void {
        $this->resetAfterTest();

        $quizobj = quiz_settings::create($this->create_quiz(1)->id);
        $rule = quizaccess_savenow::make($quizobj, time(), false);

        $page = new \moodle_page();
        $page->set_context($quizobj->get_context());
        $page->set_pagetype('mod-quiz-attempt');
        $rule->setup_attempt_page($page);
        $this->assertStringContainsString('quizaccess_savenow/save', $page->requires->get_end_code());

        $page = new \moodle_page();
        $page->set_context($quizobj->get_context());
        $page->set_pagetype('mod-quiz-summary');
        $rule->setup_attempt_page($page);
        $this->assertStringNotContainsString('quizaccess_savenow/save', $page->requires->get_end_code());
    }
}
