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
require_once($CFG->dirroot.'/local/vflibs/jqplotlib.php');
local_vflibs_require_jqplot_libs();
$PAGE->requires->jquery_plugin('jqwidgets-barchart', 'local_vflibs');

$view = optional_param('view', 'slowpages', PARAM_TEXT);

$url = new moodle_url('/local/advancedperfs/report.php', array('view' => $view));
$context = context_system::instance();
$action = optional_param('what', '', PARAM_TEXT);

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_advancedperfs'));
$PAGE->navbar->add(get_string('pluginname', 'local_advancedperfs'));

$renderer = $PAGE->get_renderer('local_advancedperfs');

if ($action) {
    include_once($CFG->dirroot.'/local/advancedperfs/report.controller.php');
    $controller = new \local_advancedperfs\report_controller();
    $controller->receive($action);
    $controller->process($action);
}

echo $OUTPUT->header();

// Report global indicators.

$renderer->load_data();

if ($renderer->is_empty()) {
    echo $OUTPUT->heading(get_string('pluginname', 'local_advancedperfs'));
    echo $OUTPUT->notification(get_string('noslowpages', 'local_advancedperfs'));
    echo $OUTPUT->footer();
    die;
}

echo $renderer->tabs($view);

echo $renderer->globals();

// Report slow pages over time.

switch ($view) {
    case 'slowpages';
        echo $renderer->time_form();

        echo $renderer->heading(get_string('distribution', 'local_advancedperfs'));
        echo $renderer->time_dist_graph();

        echo $renderer->heading(get_string('timeline', 'local_advancedperfs'));
        echo $renderer->slowp_timeline();
        break;

    case 'db';
        echo $renderer->heading(get_string('dbtimedist', 'local_advancedperfs'));
        echo $renderer->slowp_dbratio_dist();

        echo $renderer->heading(get_string('dbcallsdist', 'local_advancedperfs'));
        echo $renderer->slowp_dbquery_dist();
        break;

    case 'mem';
        echo $renderer->time_rel_memory();
        echo $renderer->urls_by_memory();
        break;

    case 'users';
        echo $renderer->users_globals();
        echo $renderer->top_affected_users();
        break;

    case 'urls';
        echo $renderer->url_ranking_by_occurrence();
        break;

}

echo $OUTPUT->footer();