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
 * Backup code for the quizaccess_savenow plugin.
 *
 * @package     quizaccess_savenow
 * @category    backup
 * @copyright   2026 UniDistance Suisse
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/backup_mod_quiz_access_subplugin.class.php');

/**
 * Saves the "Save my answers" setting of a quiz in its backup.
 *
 * @copyright   2026 UniDistance Suisse
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_quizaccess_savenow_subplugin extends backup_mod_quiz_access_subplugin {
    /**
     * Describe the XML structure holding the setting of a quiz.
     *
     * @return backup_subplugin_element
     */
    protected function define_quiz_subplugin_structure() {
        parent::define_quiz_subplugin_structure();

        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $settings = new backup_nested_element('quizaccess_savenow', null, ['enabled']);

        $subplugin->add_child($wrapper);
        $wrapper->add_child($settings);

        $settings->set_source_table('quizaccess_savenow', ['quizid' => backup::VAR_ACTIVITYID]);

        return $subplugin;
    }
}
