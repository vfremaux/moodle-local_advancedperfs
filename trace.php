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
 * @package     local_performance
 * @subpackage  local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright   (C) 2016 onwards Valery Fremaux
 */
require('../../config.php');

$url = new moodle_url('/local/advancedperfs/trace.php');
$context = context_system::instance();
$action = optional_param('what', '', PARAM_TEXT);

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('trace', 'local_advancedperfs'));
$PAGE->navbar->add(get_string('trace', 'local_advancedperfs'));

$renderer = $PAGE->get_renderer('local_advancedperfs');

if ($action) {
    include_once($CFG->dirroot.'/local/advancedperfs/trace.controller.php');
    $controller = new \local_advancedperfs\trace_controller();
    $controller->receive($action);
    $controller->process($action);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('trace', 'local_advancedperfs'));

$config = get_config('local_advancedperfs');

$strcodes = [
        0 => 'no',
        TRACE_ERRORS => 'errors',
        TRACE_NOTICE => 'notices',
        TRACE_DEBUG => 'debug',
        TRACE_DATA => 'data',
        TRACE_DEBUG_FINE => 'finedebug'
];

echo $OUTPUT->notification(get_string('configtraceout', 'local_advancedperfs').' '.get_string($strcodes[$config->traceout], 'local_advancedperfs'), 'info');

$settingsurl = new moodle_url('/admin/settings.php', ['section' => 'localsettingtimebenches']);
echo '<div style="text-align: right"><a href="'.$settingsurl.'">'.get_string('settings', 'local_advancedperfs').'</a></div>';

// Report global indicators.
$tracelocation = str_replace('%DATAROOT%', $CFG->dataroot, $CFG->trace); // Optional placeolder.

if (!is_file($tracelocation)) {
    echo $OUTPUT->notification(get_string('notracefile', 'local_advancedperfs'), 'error');
} else {
    $tracesize = filesize($tracelocation);
    if ($tracesize > 10 * 1024 * 1024) { // Originally 2M max.
        echo $OUTPUT->notification(get_string('tracetoobig', 'local_advancedperfs'));
        $buttonurl = new moodle_url('/local/advancedperfs/trace.php', array('what' => 'clear', 'sesskey' => sesskey()));
        echo $OUTPUT->single_button($buttonurl, get_string('clear', 'local_advancedperfs'));
    } else {
        $trace = implode("\n", file($tracelocation));

        if (mb_strlen($trace) > 500) {
            echo $renderer->tracebuttons();
        }

        echo '<pre>';
        echo $trace;
        echo '</pre>';

        echo $renderer->tracebuttons();
    }
}

echo $OUTPUT->footer();