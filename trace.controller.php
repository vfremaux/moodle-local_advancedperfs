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
 *
 * @package     local_performance
 * @subpackage  local
 * @author      Valery Fremaux <valery.fremaux@club-internet.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright   (C) 2016 onwards Valery Fremaux
 */
namespace local_advancedperfs;

defined('MOODLE_INTERNAL') || die;

class trace_controller {

    /**
     * Receives all needed parameters from outside for each action case.
     * @param string $cmd the action keyword
     * @param array $data incoming parameters from form when directly available, otherwise the
     * function shoudl get them from request
     */
    public function receive($cmd, $data = null) {

        if (!empty($data)) {
            // Data is fed from outside.
            $this->data = (object)$data;
            $this->received = true;
            return;
        } else {
            $this->data = new \StdClass;
        }

        switch ($cmd) {
            case 'clear':
                require_sesskey();
                break;
        }

        $this->received = true;
    }

    /**
     * Processes the action
     * @param string $cmd
     */
    public function process($cmd) {
        global $DB, $CFG;

        if (!$this->received) {
            throw new \coding_exception('Data must be received in controller before operation. this is a programming error.');
        }

        if ($cmd == 'clear') {
            if (!empty($CFG->trace)) {
                if ($TRACE = fopen($CFG->trace, 'w')) {
                    fclose($TRACE);
                } else {
                    // Try twice if file is locked by another process.
                    sleep(1000);
                    if ($TRACE = fopen($CFG->trace, 'w')) {
                        fclose($TRACE);
                    }
                }
            }
        }
    }
}