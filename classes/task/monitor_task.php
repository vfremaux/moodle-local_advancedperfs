<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   local_advancedperfs
 * @category  local
 * @author    Valery Fremaux <valery.fremaux@gmail.com>, <valery@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_advancedperfs\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to alert admins or targets that too many
 * slow pages are run for users.
 */
class monitor_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskmonitor', 'local_advancedperfs');
    }

    /**
     * Do the job.
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot.'/local/advancedperfs/perflib.php');
        \performance_monitor::crontask();
    }
}