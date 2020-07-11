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
 * Scheduled task to reset automatically debug mode if setup for too long.
 */
class trackdebug_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasktrackdebug', 'local_advancedperfs');
    }

    /**
     * Do the job.
     */
    public function execute() {
        global $DB, $SITE, $CFG;

        $config = get_config('local_advancedperfs');

        $lastdebugchange = $DB->get_record_select('config_log', " plugin IS NULL and name = 'debug' ", array(), 'id, MAX(timemodified), name, value, oldvalue');

        if (($CFG->debug >= $config->debugreleasethreshold) && ($lastdebugchange->timemodified < (time() - HOURSECS * $config->canceldebugafter))) {
            $oldddebug = get_config('core', 'debug');
            $oldddebugdisplay = get_config('core', 'debugdisplay');
            set_config('debug', $config->debugreleasevalue);
            set_config('debugdisplay', $config->debugdisplayreleasevalue);
            set_config('themedesignermode', 0);
            add_to_config_log('debug', $olddebug, $config->debugreleasevalue, 'core');
            add_to_config_log('debugdisplay', $olddebugdisplay, $config->debugdisplayreleasevalue, 'core');

            if (!empty($config->debugnotifyrelease)) {
                $a = get_admin();

                $notification = "Moodle AdvancedPerfs TrackDebug monitor\n\n";
                $notification .= 'Debug mode is at '.$olddebug.' for at least '.$config->canceldebugafter." hours\n\n";
                $notification .= 'Passing from '.$oldddebug.' to '.$config->debugreleasevalue;
                email_to_user($a, $a, '['.$SITE->shortname.'] Releasing debug mode ', $notification);
            }
        }
    }
}