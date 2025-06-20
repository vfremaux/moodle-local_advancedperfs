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

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__.'/debugtools.php');

if ($hassiteconfig) {
    $settings = new admin_settingpage('localsettingtimebenches', get_string('pluginname', 'local_advancedperfs'));

    $settings->add(new admin_setting_heading('perfshdr', get_string('perfs', 'local_advancedperfs'), ''));

    $key = 'local_advancedperfs/enabled';
    $label = get_string('configadvancedperfsenabled', 'local_advancedperfs');
    $desc = get_string('configadvancedperfsenabled_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, ''));

    $key = 'local_advancedperfs/slowpagescounter';
    $label = get_string('configslowpagescounter', 'local_advancedperfs');
    $desc = get_string('configslowpagescounter_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 0));

    $key = 'local_advancedperfs/slowpagescounterrec';
    $label = get_string('configslowpagescounterrec', 'local_advancedperfs');
    $desc = get_string('configslowpagescounterrec_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 0));

    $key = 'local_advancedperfs/slowpagederiv';
    $label = get_string('configslowpagederiv', 'local_advancedperfs');
    $desc = get_string('configslowpagederiv_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 0));

    $key = 'local_advancedperfs/slowpagederivthreshold';
    $label = get_string('configslowpagederivthreshold', 'local_advancedperfs');
    $desc = get_string('configslowpagederivthreshold_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 10));

    $key = 'local_advancedperfs/longpagethreshold';
    $label = get_string('configlongpagethreshold', 'local_advancedperfs');
    $desc = get_string('configlongpagethreshold_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 4));

    $key = 'local_advancedperfs/verylongpagethreshold';
    $label = get_string('configverylongpagethreshold', 'local_advancedperfs');
    $desc = get_string('configverylongpagethreshold_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 30));

    $key = 'local_advancedperfs/slowpageexcludes';
    $label = get_string('configslowpageexcludes', 'local_advancedperfs');
    $desc = get_string('configslowpageexcludes_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configtextarea($key, $label, $desc, 'local\\/advancedperfs'));

    $key = 'local_advancedperfs/userstosendto';
    $label = get_string('configuserstosendto', 'local_advancedperfs');
    $desc = get_string('configuserstosendto_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configtext($key, $label, $desc, ''));

    $key = 'local_advancedperfs/filelogging';
    $label = get_string('configfilelogging', 'local_advancedperfs');
    $desc = get_string('configfilelogging_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, ''));

    $settings->add(new admin_setting_heading('debughdr', get_string('debugtrack', 'local_advancedperfs'), ''));

    $key = 'local_advancedperfs/debugreleasethreshold';
    $label = get_string('configdebugreleasethreshold', 'local_advancedperfs');
    $desc = get_string('configdebugreleasethreshold_desc', 'local_advancedperfs');
    $default = DEBUG_ALL;
    $debugoptions = array(DEBUG_NONE      => get_string('debugnone', 'admin'),
            DEBUG_MINIMAL   => get_string('debugminimal', 'admin'),
            DEBUG_NORMAL    => get_string('debugnormal', 'admin'),
            DEBUG_ALL       => get_string('debugall', 'admin'),
            DEBUG_DEVELOPER => get_string('debugdeveloper', 'admin'));

    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $debugoptions));

    $key = 'local_advancedperfs/debugreleaseafter';
    $label = get_string('configdebugreleaseafter', 'local_advancedperfs');
    $desc = get_string('configdebugreleaseafter_desc', 'local_advancedperfs');
    $default = 12; // 12 hours.
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_advancedperfs/debugreleasevalue';
    $label = get_string('configdebugreleasevalue', 'local_advancedperfs');
    $desc = get_string('configdebugreleasevalue_desc', 'local_advancedperfs');
    $default = DEBUG_NORMAL;
    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $debugoptions));

    $key = 'local_advancedperfs/debugdisplayreleasevalue';
    $label = get_string('configdebugdisplayreleasevalue', 'local_advancedperfs');
    $desc = get_string('configdebugdisplayreleasevalue_desc', 'local_advancedperfs');
    $default = 0;
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    $key = 'local_advancedperfs/debugnotifyrelease';
    $label = get_string('configdebugnotifyrelease', 'local_advancedperfs');
    $desc = get_string('configdebugnotifyrelease_desc', 'local_advancedperfs');
    $default = 0;
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    $label = get_string('trace', 'local_advancedperfs');
    $traceurl = new moodle_url('/local/advancedperfs/trace.php');
    $ADMIN->add('development', new admin_externalpage('trace', $label, $traceurl, 'moodle/site:config'));

    $key = 'local_advancedperfs/devdebuglevel';
    $label = get_string('configdevdebuglevel', 'local_advancedperfs');
    $desc = get_string('configdevdebuglevel_desc', 'local_advancedperfs');
    $default = DEBUG_ALL;
    $debugoptions = array(DEBUG_NONE      => get_string('debugnone', 'admin'),
            DEBUG_MINIMAL   => get_string('debugminimal', 'admin'),
            DEBUG_NORMAL    => get_string('debugnormal', 'admin'),
            DEBUG_ALL       => get_string('debugall', 'admin'),
            DEBUG_DEVELOPER => get_string('debugdeveloper', 'admin'));

    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $debugoptions));

    $key = 'local_advancedperfs/devusers';
    $label = get_string('configdevusers', 'local_advancedperfs');
    $desc = get_string('configdevusers_desc', 'local_advancedperfs');
    $default = '';

    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));


    $traceurl = new moodle_url('/local/advancedperfs/trace.php');
    $html = '<a name="tracesettings" href="'.$traceurl.'">'.get_string('seetrace', 'local_advancedperfs').'</a>';
    $settings->add(new admin_setting_heading('tracehdr', get_string('trace', 'local_advancedperfs'), $html));

    $key = 'trace';
    $label = get_string('configtrace', 'local_advancedperfs');
    $desc = get_string('configtrace_desc', 'local_advancedperfs');
    $default = '';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $options = [
        '0' => get_string('no'),
        TRACE_ERRORS => get_string('errors', 'local_advancedperfs'),
        TRACE_NOTICE => get_string('notices', 'local_advancedperfs'),
        TRACE_DEBUG => get_string('debug', 'local_advancedperfs'),
        TRACE_DATA => get_string('data', 'local_advancedperfs'),
        TRACE_DEBUG_FINE => get_string('finedebug', 'local_advancedperfs'),
    ];
    $key = 'local_advancedperfs/traceout';
    $label = get_string('configtraceout', 'local_advancedperfs');
    $desc = get_string('configtraceout_desc', 'local_advancedperfs');
    $default = 0;
    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

    $key = 'local_advancedperfs/maxtracefilesize';
    $label = get_string('configmaxtracefilesize', 'local_advancedperfs');
    $desc = get_string('configmaxtracefilesize_desc', 'local_advancedperfs');
    $options = [
        '0' => get_string('nolimit', 'local_advancedperfs'),
        '50' => '50k',
        '100' => '100k',
        '500' => '500k',
        '1000' => '1m',
        '5000' => '5m',
        '10000' => '10m',
        '50000' => '50m',
        '100000' => '100m'
    ];
    $default = 1000;
    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

    $settings->add(new admin_setting_heading('datafixhdr', get_string('datafixes', 'local_advancedperfs'), get_string('datafixes_desc', 'local_advancedperfs')));

    $key = 'local_advancedperfs/fixenabled';
    $label = get_string('configfixenabled', 'local_advancedperfs');
    $desc = get_string('configfixenabled_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, ''));

    $key = 'local_advancedperfs/fixsql';
    $label = get_string('configfixsql', 'local_advancedperfs');
    $desc = get_string('configfixsql_desc', 'local_advancedperfs');
    $settings->add(new admin_setting_configtextarea($key, $label, $desc, ''));

    $ADMIN->add('development', $settings);
}

