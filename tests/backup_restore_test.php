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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Backup and restore of the quizaccess_savenow setting.
 *
 * @package     quizaccess_savenow
 * @category    test
 * @copyright   2026 UniDistance Suisse
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \backup_quizaccess_savenow_subplugin
 * @covers      \restore_quizaccess_savenow_subplugin
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\backup_quizaccess_savenow_subplugin::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_quizaccess_savenow_subplugin::class)]
final class backup_restore_test extends \advanced_testcase {
    /**
     * The setting of each quiz is kept when the course is backed up and restored.
     *
     * @covers \backup_quizaccess_savenow_subplugin
     * @covers \restore_quizaccess_savenow_subplugin
     */
    public function test_backup_restore(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('quiz', ['course' => $course->id, 'name' => 'With button', 'savenow_enabled' => 1]);
        $generator->create_module('quiz', ['course' => $course->id, 'name' => 'Without button', 'savenow_enabled' => 0]);

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $newcourseid = \restore_dbops::create_new_course('Restored', 'R1', $course->category);
        $rc = new \restore_controller(
            $backupid,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $with = $DB->get_record('quiz', ['course' => $newcourseid, 'name' => 'With button'], '*', MUST_EXIST);
        $without = $DB->get_record('quiz', ['course' => $newcourseid, 'name' => 'Without button'], '*', MUST_EXIST);
        $this->assertTrue($DB->record_exists('quizaccess_savenow', ['quizid' => $with->id, 'enabled' => 1]));
        $this->assertFalse($DB->record_exists('quizaccess_savenow', ['quizid' => $without->id]));
    }
}
