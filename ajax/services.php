<?php
// This file is part of the learningtimecheck plugin for Moodle - http://moodle.org/
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
 * @package     local_advancedperfs
 * @category    local
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require('../../../config.php');

// Security.

require_login();

$url = new moodle_url('/local/advancedperfs/ajax/services.php');
$PAGE->set_url($url);

$action = required_param('what', PARAM_TEXT);

$context = context_system::instance();
$PAGE->set_context($context);

if ($action == 'changepanelpreference') {
    $state = required_param('state', PARAM_BOOL);

    if ($pref = $DB->get_record('user_preferences', array('userid' => $USER->id, 'name' => 'perfspanel'))) {
        $pref->value = $state;
        $DB->update_record('user_preferences', $pref);
    } else {
        $pref = new StdClass;
        $pref->userid = $USER->id;
        $pref->name = 'perfspanel';
        $pref->value = $state;
        $DB->insert_record('user_preferences', $pref);
    }
}
