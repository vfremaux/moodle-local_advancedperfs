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

$string['advancedperfs:view'] = 'View perfs';
$string['advancedperfs:hasdebugrole'] = 'Has a debug role';

$string['actives'] = 'Actives';
$string['blockinits'] = 'Block Initialisations';
$string['categories'] = 'Categories';
$string['clear'] = 'Clear the trace';
$string['configadvancedperfsenabled'] = 'Enable perfs';
$string['configdebugdisplayreleasevalue'] = 'Debug display release value';
$string['configdebugfromips'] = 'Sessions comming from this IPs will be turned in debug mode.';
$string['configdebugreleaseafter'] = 'Release debug after';
$string['configdebugreleasethreshold'] = 'Debug release threshold';
$string['configdebugreleasevalue'] = 'Debug release value';
$string['configdebugusers'] = 'User IDs mentionned in this list will be turned in debug mode.';
$string['configfilelogging'] = 'Enable file Logging';
$string['configfixenabled'] = 'Enable data fixes';
$string['configfixsql'] = 'SQL fixture';
$string['configlongpagethreshold'] = 'Long page threshold';
$string['configslowpagederiv'] = 'Slow page derivate';
$string['configslowpagederivthreshold'] = 'Derivate threshold for alert';
$string['configslowpageexcludes'] = 'Urls to exclude from slow page detection';
$string['configslowpagescounter'] = 'Slow page count';
$string['configslowpagescounterrec'] = 'Slow page count (N-1)';
$string['configuserstosendto'] = 'Users to send to';
$string['configverylongpagethreshold'] = 'Very long page threshold';
$string['content'] = 'Content';
$string['datafixes'] = 'Data fix';
$string['daysslow'] = 'Days affected';
$string['db'] = 'DB';
$string['dbcaller'] = 'DB Caller';
$string['dbcalls'] = 'DBCalls';
$string['dbcallsdist'] = 'DB Calls in slow pages';
$string['dbquerydist'] = 'Amount of DB queries distribution';
$string['dbratiodist'] = 'Exec time DB ratio';
$string['dbtimedist'] = 'DB time spent in slow pages';
$string['debugfromips'] = 'Debugging IPs';
$string['debugtrack'] = 'Debug state tracking';
$string['debugusers'] = 'Debugging users';
$string['distinctusers'] = 'Distinct users';
$string['distribution'] = 'Distribution';
$string['envusers'] = 'Users in environment';
$string['footer'] = 'Footer';
$string['header'] = 'Header';
$string['init'] = 'Init';
$string['layoutinit'] = 'Layout intialisations';
$string['layoutprepareblocks'] = 'Layout prepares blocks';
$string['layoutprepareleftregion'] = 'Layout prepares left blocks';
$string['layoutprepareoutput'] = 'Layout prepares output';
$string['layoutpreparerightregion'] = 'Layout prepares right blocks';
$string['location'] = 'Location';
$string['max'] = 'max';
$string['mean'] = 'Mean (SP/day)';
$string['mean'] = 'mean';
$string['mem'] = 'Memory';
$string['memused'] = 'Memory used';
$string['min'] = 'min';
$string['mostaffecteduser'] = 'Most affected users';
$string['noroles'] = 'No roles';
$string['noslowpages'] = 'No slow pages detected';
$string['nothingsince'] = 'Nothing since';
$string['num'] = 'Ocurrences';
$string['numusersaffected'] = 'Number of affected users';
$string['occurrences'] = 'occurrences';
$string['onlines'] = 'Onlines';
$string['overall'] = 'Overall';
$string['page'] = 'Page';
$string['pagesetup'] = 'Page setup';
$string['perfs'] = 'Performances';
$string['pluginname'] = 'Advanced perfs';
$string['range'] = 'Range';
$string['ratioaffectedusers'] = 'Ratio of affected users among all users';
$string['reset'] = 'Reset';
$string['rpccalls'] = 'RPCCalls';
$string['setup'] = 'Initial Setup';
$string['since'] = 'Since';
$string['slowpages'] = 'Slow Pages';
$string['slowpagescount'] = 'Slow Pages (> {$a}s)';
$string['slowpagesratio'] = 'Slow ratio';
$string['slowpagesreport'] = 'Slow pages report';
$string['taskmonitor'] = 'Task for monitoring slow pages';
$string['tasktrackdebug'] = 'Task for tracking and releasing debug state';
$string['timedist'] = 'Time spent distribution';
$string['timeline'] = 'Time line of slow pages (units per day)';
$string['timerelmem'] = 'timespent vs. memory used';
$string['timerelusers'] = 'timespent vs. users';
$string['timespent'] = 'Time spent';
$string['total'] = 'total';
$string['trace'] = 'Development trace';
$string['tracetoobig'] = 'Trace too big';
$string['unconnectedusers'] = 'Unconnected';
$string['url'] = 'Url';
$string['urls'] = 'Urls';
$string['urlsbyfreq'] = 'Url frequency';
$string['urlsbymem'] = 'Urls by memory used';
$string['users'] = 'Users';
$string['worstday'] = 'Slowest day';

$string['configslowpagederiv_desc'] = 'Current state of the slow page variation';

$string['configadvancedperfsenabled_desc'] = 'If enabled, additional detail perfs are displayed for administrator';

$string['configdebugdisplayreleasevalue_desc'] = 'Debug display mode to set when releasing.';

$string['configdebugreleaseafter_desc'] = 'Release will be triggered if last debug level over threshold is set since
at least that amount (hours).';

$string['configdebugreleasethreshold_desc'] = 'Release will be processed if debug level is over or equal to this debug level.';

$string['configdebugreleasevalue_desc'] = 'Debug level to set when releasing.';

$string['configfilelogging_desc'] = 'If enabled, events are logged in files additionnaly to the database records.';

$string['configfixenabled_desc'] = 'If enabled, the fix SQL queries are run daily.';

$string['configfixsql_desc'] = 'Input any sql statements that will run daily to fix data in moodle.';

$string['configlongpagethreshold_desc'] = 'Threshold for long pages';

$string['configslowpagederivthreshold_desc'] = 'Threshold that triggers administrator alerts telling too many slow pages are run.';

$string['configslowpageexcludes_desc'] = 'Some urls may be known to be slow or needing significant processing time.
You may give a list of url patterns (regexp like)';

$string['configslowpagescounter_desc'] = 'Counts occurrences of slow pages exceeding the long page threshold time.';

$string['configslowpagescounterrec_desc'] = 'The last counter state. Memorizes the counter at previous task run.';

$string['configuserstosendto_desc'] = 'A list of emails, usernames or numeric user IDs comma separated.';

$string['configverylongpagethreshold_desc'] = 'Threshold over which an immediate alert is sent.';

$string['tracetoobig_desc'] = 'the trace is too big to be displayed online. You should truncate it and repeat the test case.';

$string['datafixes_desc'] = 'Fixing data in moodle allow processing a symptomatic data correction in the moodle database. It will not fix
the source of the errors, but allow securising an installation while bugfixes are identified and deployed.';
