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
 * @package performance
 * @subpackage     local
 * @author         Valery Fremaux <valery.fremaux@club-internet.fr>
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright      (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * this file contains a library for capturing and rendering in-code localized performance
 *
 * functions should all start with the perf_ prefix.
 */
if (!defined('MOODLE_EARLY_INTERNAL')) {
    defined('MOODLE_INTERNAL') || die('');
}

define('PUNCHED_IN', 1);
define('PUNCHED_OUT', 0);

class performance_monitor {

    protected $perfs = array();

    protected $perfcategories = array();

    protected $dbcallers = array();

    static private $instance;

    protected $staticconfig;

    protected $isslowpage;

    /**
     * initializes perf measurement globals
     * this function MUST be called before any other performance related function
     * in the Moodle code.
     */
    protected function __construct() {
        $this->perfcategories = array('overall', 'dbcalls', 'rpccalls', 'header', 'content', 'footer');

        $this->perfs = array();

        foreach ($this->perfcategories as $cat) {
            $this->init_cat($cat);
        }

        // Use internals as some API function might not be available yet.
        $this->internal_punchin('overall', __FILE__, __LINE__);
        $this->internal_punchin('init', __FILE__, __LINE__);
        $this->internal_punchin('setup', __FILE__, __LINE__);
        $this->isslowpage = false;
    }

    /**
     * Factory singleton function
     */
    public static function instance() {
        if (!self::$instance) {
            self::$instance = new performance_monitor();
        }

        return self::$instance;
    }

    public function init_config() {
        global $DB, $PAGE;

        if (empty($DB)) {
            return false;
        }

        $PAGE->requires->js('/local/advancedperfs/js/perfspanel.js', false);

        $configs = $DB->get_records('config_plugins', array('plugin' => 'local_advancedperfs'));
        $this->staticconfig = new StdClass;
        foreach ($configs as $c) {
            $name = $c->name;
            $value = $c->value;
            $this->staticconfig->$name = $value;
        }
        return true;
    }

    /**
     * Initializes indicator object for a single track. Is used
     * for hot initializing a new category when discovered.
     * @param string $cat
     */
    protected function init_cat($cat) {

        if (!isset($this->perfs[$cat])) {
            $this->perfs[$cat] = new stdClass();
        }
        $this->perfs[$cat]->state = PUNCHED_OUT;
        $this->perfs[$cat]->in = 0;
        $this->perfs[$cat]->total = 0;
        $this->perfs[$cat]->min = 0;
        $this->perfs[$cat]->max = 0;
        $this->perfs[$cat]->occurrences = 0;
        $this->perfs[$cat]->mean = 0;
    }

    /**
     * @param string $category the category we want to punch in
     * @param string $file feed this with __FILE__
     * @param string $line feed this with __LINE__
     */
    public function punchin($category, $file = __FILE__, $line = __LINE__) {
        global $OUTPUT, $CFG, $DB;

        // Be carefull ! 'dbcalls' patched category is inside "get_records" !
        if (empty($this->staticconfig) && $category != 'dbcalls') {
            $initialized = $this->init_config();
        }

        if (empty($this->staticconfig->enabled)) {
            return false;
        }

        if (!is_array($this->perfs)) {
            echo $OUTPUT->notification("Perf system not initialized");
        }
        $this->internal_punchin($category, $file, $line);

        if ($category == 'dbcalls') {
            $dbusecontext = $this->seek_trace();
            $context = str_replace($CFG->dirroot, '', @$dbusecontext['file'].'$'.@$dbusecontext['line']);
            @$this->dbcallers[$context]++;
        }
    }

    /**
     * Returns first applicative context using the database.
     */
    protected function seek_trace() {
        $e = new Exception();
        $trace = $e->getTrace();

        // Remove first (here !), second (known in dmllib) and third (db call).

        $t = array_shift($trace);
        while (preg_match('/perflib|moodle_database|moodlelib/', @$t['file'])) {
            $t = array_shift($trace);
        }

        return array_shift($trace);
    }

    /**
     * Punches in data in tracks while not relying on any Moodle API
     * function that might not be available.
     * @param string $file feed this with __FILE__
     * @param string $line feed this with __LINE__
     */
    public function internal_punchin($category, $file = '', $line = '') {
        global $CFG;

        // Discovering new categories through a first punch in.
        if (!in_array($category, $this->perfcategories)) {
            $this->perfcategories[] = $category;
            $this->init_cat($category);
        }

        list($usec, $sec) = explode(' ', microtime());
        $tick = (float)$sec + (float)$usec;
        $this->perfs[$category]->in = $tick;
        $this->perfs[$category]->state = PUNCHED_IN;
        $this->perfs[$category]->infile = str_replace($CFG->dirroot, '', $file);
        $this->perfs[$category]->inline = $line;
    }

    /**
     *
     */
    public function reset() {
        global $DB;

        set_config('slowpagescounter', 0, 'local_advancedperfs');
        set_config('slowpagescounterrec', 0, 'local_advancedperfs');
        set_config('slowpagesderiv', 0, 'local_advancedperfs');
    }

    /**
     * punches out recording the cumulated time
     * @param string $category the category we want to punch out
     * @param string $bouncein a new category where to bounce a punchin
     */
    public function punchout($category, $bouncein = '', $file = '', $line = '') {
        global $OUTPUT, $CFG, $DB, $PAGE, $USER, $COURSE;

        // Be carefull ! 'dbcalls' patched category is inside "get_records" !
        if (empty($this->staticconfig) && $category != 'dbcalls') {
            $initialized = $this->init_config();
        }

        if (empty($this->staticconfig->enabled)) {
            return false;
        }

        if (!is_array($this->perfs)) {
            echo $OUTPUT->notification("Perf system not initialized");
        }

        if (!in_array($category, $this->perfcategories)) {
            echo $OUTPUT->notification("Unknown perf category: $category");
            return false;
        }

        // Take OUT time.
        list($usec, $sec) = explode(' ', microtime());
        $tick = (float)$sec + (float)$usec;
        $duration = $tick - (float)$this->perfs[$category]->in;

        if (!empty($CFG->slowexectime[$category]) && ($duration > $CFG->slowexectime[$category])) {
            $slowtime = true;
        } else {
            $slowtime = false;
        }

        $this->perfs[$category]->total += $duration;
        $this->perfs[$category]->occurrences++;
        $this->perfs[$category]->outfile = str_replace($CFG->dirroot, '', $file);
        $this->perfs[$category]->outline = $line;

        // Compute max of all occurrences.
        if ($duration > $this->perfs[$category]->max) {
            $this->perfs[$category]->max = $duration;
        }

        // Compute min time of all accurrences.
        if ($this->perfs[$category]->min == 0) {
            // First initialization.
            $this->perfs[$category]->min = $duration;
        } else {
            if ($duration != 0) {
                if ($duration < $this->perfs[$category]->min) {
                    $this->perfs[$category]->min = $duration;
                }
            }
        }

        // Compute mean time over all occurrences.
        $n = $this->perfs[$category]->occurrences;
        if ($n == 1) {
            $this->perfs[$category]->mean = $duration;
        } else if ($n > 1) {
            $this->perfs[$category]->mean = ($this->perfs[$category]->mean * ($n - 1) + $duration) / $n;
        }
        $this->perfs[$category]->state = PUNCHED_OUT;

        if (!empty($bouncein)) {
            // Bounce to a new measure.
            $this->punchin($bouncein, $file, $line);
        }

        if ($slowtime) {
            // Trace the slow event with info.
            $file = fopen($CFG->dataroot.'/slowexec.log', 'w+');
            fputs($file, $category);
            ob_start();
            $backtrace = debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
            fputs($file, ob_get_clean());
            fclose($file);
        }

        if ($category != 'overall') {
            return;
        }

        // Getting dynamically the settings.
        $settings = $DB->get_records_menu('config_plugins', array('plugin' => 'local_advancedperfs'), 'name', 'name,value');
        if ($duration > $settings['longpagethreshold']) {
            // Get fresh settings values from DB.

            $counterparams = array('plugin' => 'local_advancedperfs', 'name' => 'slowpagescounter');
            $lastspparams = array('plugin' => 'local_advancedperfs', 'name' => 'lastslowpage');

            // When we detect slow pages, we should register them.
            // The cron task will trigger an alert if counter.
            $DB->set_field('config_plugins', 'value', $settings['slowpagescounter'] + 1, $counterparams);

            // Any people wil still active session is considered as online.
            $sql = "
                SELECT
                    COUNT(*)
                FROM
                    {user}
                WHERE
                    lastaccess > ?
            ";

            $onlineusers = $DB->count_records_sql($sql, array(time() - $CFG->sessiontimeout));

            // Any people active from less then long page threshold is potentially calculating a page.
            $sql = "
                SELECT
                    COUNT(*)
                FROM
                    {user}
                WHERE
                    lastaccess > ?
            ";
            $activeusers = $DB->count_records_sql($sql, array(time() - ($settings['longpagethreshold'])));

            // Trace slowpage info.
            $slowpage = new StdClass;
            $slowpage->timecreated = time();
            $slowpage->userid = $USER->id;
            $slowpage->timespent = $duration;
            $slowpage->dbcalls = $this->perfs['dbcalls']->occurrences;
            $slowpage->timeindb = $this->perfs['dbcalls']->total;
            $slowpage->url = str_replace($CFG->wwwroot, '', $PAGE->url);
            $slowpage->memused = memory_get_peak_usage(true);
            $slowpage->onlineusers = $onlineusers;
            $slowpage->activeusers = $activeusers;
            $DB->insert_record('local_advancedperfs_slowp', $slowpage);

            $shorturl = str_replace($CFG->wwwroot, '', $PAGE->url);
            $ts = sprintf('%.2f', $slowpage->timespent);
            $tdb = sprintf('%.3f', $slowpage->timeindb);
            $benchdata = '['.date('Y-m-d H:i:s', time()).'] '.$ts.'s DB:'.$tdb.'s M:'.round($slowpage->memused / 1024).'kb';

            if (!empty($settings['filelogging'])) {
                $log = fopen($CFG->dataroot.'/slowpages.log', 'a');
                fputs($log, $benchdata.' '.$shorturl."\n");
                fclose($log);
            }

            $this->isslowpage = true;
        }

        // Very Long Page threshold is over longpagethreshold. Bench data are available.
        if ($duration > $settings['verylongpagethreshold']) {
            // Push an immediate alert.
            $message = "Moodle has run an unusually long page.\n\n";

            $message .= "# Configuration details:\n";
            $message .= "Debug mode: ".$CFG->debug."\n";
            $message .= "Theme designer mode: ".$CFG->themedesignermode."\n";
            $message .= "Javascript caching: ".$CFG->cachejs."\n";
            $message .= "\n";

            $message .= "# context details:\n";
            $message .= "Course: {$COURSE->id}\n";
            $message .= "User: {$USER->id}\n";
            $message .= "Page: {$PAGE->pagetype}\n";
            $message .= "\n";

            $message .= "# Technical details:\n";

            $message .= 'Url required: '.$shorturl."\n";
            $message .= 'Time bench:'.$benchdata."\n";
            $message .= 'Online users (last access younger than session length): '.$onlineusers."\n";
            $message .= 'Active users (last access younger than '.$settings['longpagethreshold'].'sec): '.$activeusers."\n";

            // Collect more info using nagios probes if avaliable.
             if (is_executable('/usr/lib/nagios/plugins/check_mysql_health')) {
                $cmd = "/usr/lib/nagios/plugins/check_mysql_health --hostname {$CFG->dbhost} --username {$CFG->dbuser} ";
                $cmd .= "--password {$CFG->dbpass} --database {$CFG->dbname} --mode threads-running ";
                $output = array();
                exec($cmd, $output, $return);
                $message .= "Mysql state: ".implode(' ', $output)."\n";
             }

            // Getting swap info.
            if (is_executable('/usr/lib/nagios/plugins/check_swap')) {
                $cmd = "/usr/lib/nagios/plugins/check_swap -w95 -c90 ";
                $output = array();
                exec($cmd, $output, $return);
                $message .= "Server swap: ".implode(' ', $output)."\n";
            }

            // Getting free memory info.
            if (is_executable('/usr/lib/nagios/plugins/check_mem')) {
                $cmd = "/usr/lib/nagios/plugins/check_mem -f -w95 -c90 ";
                $output = array();
                exec($cmd, $output, $return);
                $message .= "Server Free memory: ".implode(' ', $output)."\n";
            }

            // Getting cpu load info.
            if (is_executable('/usr/lib/nagios/plugins/check_load')) {
                $cmd = "/usr/lib/nagios/plugins/check_load -w40 -c60 ";
                $output = array();
                exec($cmd, $output, $return);
                $message .= "Server CPU Load: ".implode(' ', $output)."\n";
            }

            // Getting processes info.
            if (is_executable('/usr/lib/nagios/plugins/check_procs')) {
                $cmd = "/usr/lib/nagios/plugins/check_procs";
                $output = array();
                exec($cmd, $output, $return);
                $message .= "Server Processes: ".implode(' ', $output);
            }

            advancedperfs_send_alert('VERY LONG PAGE', $message);
        }

    }

    /**
     * prints a complete report
     */
    public function print_report($force = false) {
        global $CFG, $USER, $OUTPUT, $PAGE, $DB;

        // Be carefull ! 'dbcalls' patched category is inside "get_records" !
        if (empty($this->staticconfig)) {
            $initialized = $this->init_config();
        }

        // Finish the overall track.
        $this->punchout('overall', null, __FILE__, __LINE__);

        if (empty($this->staticconfig->enabled)) {
            return false;
        }

        $context = context_system::instance();

        if (!has_capability('local/advancedperfs:view', $context) && !$force) {
            return false;
        }

        $catstr = get_string('categories', 'local_advancedperfs');
        $minstr = get_string('location', 'local_advancedperfs');
        $locationstr = get_string('location', 'local_advancedperfs');
        $totalstr = get_string('total', 'local_advancedperfs');
        $minstr = get_string('min', 'local_advancedperfs');
        $maxstr = get_string('max', 'local_advancedperfs');
        $meanstr = get_string('mean', 'local_advancedperfs');
        $occstr = get_string('occurrences', 'local_advancedperfs');

        $table = new html_table();
        $table->head = array("<b>$catstr</b>",
                             "<b>$totalstr</b>",
                             "<b>$minstr</b>",
                             "<b>$maxstr</b>",
                             "<b>$meanstr</b>",
                             "<b>$occstr</b>");
        $table->align = array('left', 'left', 'left', 'left', 'left', 'left', 'center');
        $table->width = "100%";

        asort($this->perfcategories);

        foreach ($this->perfcategories as $cat) {
            $occ = $this->perfs[$cat]->occurrences;
            $total = $this->perfs[$cat]->total;
            if ($occ > 1) {
                $max = $this->perfs[$cat]->max;
                $min = $this->perfs[$cat]->min;
                $mean = $this->perfs[$cat]->mean;
            } else {
                $max = '';
                $min = '';
                $mean = '';
            }
            if (preg_match("/^_(.*)$/", $cat, $matches)) {
                $catname = $matches[1]; // Keep untranlsated.
            } else if (preg_match("/^printing_(.*)$/", $cat, $matches)) {
                $blockstr = get_string('pluginname', 'block_'.$matches[1]);
                if ($blockstr == '[[blockname]]') {
                    $blockstr = $matches[1];
                }
                $catname = get_string('printing', 'local_advancedperfs').' '.$blockstr;
            } else {
                $catname = $cat;
            }

            $locations = str_replace($CFG->dirroot, '', @$this->perfs[$cat]->infile).':$'.@$this->perfs[$cat]->inline;
            $locations .= '<br/>'.@$this->perfs[$cat]->outfile.':$'.@$this->perfs[$cat]->outline;

            $table->data[] = array($catname,
                                   "<span id=\"{$catname}_total\">$total</span>",
                                   "<span id=\"{$catname}_min\">$min</span>",
                                   "<span id=\"{$catname}_max\">$max</span>",
                                   "<span id=\"{$catname}_mean\">$mean</span>", $occ);
        }

        $template = new StdClass;
        $attrs = array('id' => 'perf-panel-report');
        $template->perfpix = $OUTPUT->pix_icon('viewdetails', '', 'local_advancedperfs', $attrs);

        $template->perfsstr = get_string('perfs', 'local_advancedperfs');

        $userpref = $DB->get_field('user_preferences', 'value', array('name' => 'perfspanel', 'userid' => $USER->id));
        $tostate = ($userpref) ? 0 : 1;
        $template->initialclass = ($userpref) ? 'perfs-visible' : 'perfs-hidden';

        $template->benchtable = html_writer::table($table);

        $callerstr = get_string('dbcaller', 'local_advancedperfs');
        $callsstr = get_string('dbcalls', 'local_advancedperfs');
        if (!empty($this->dbcallers)) {
            $dbcallstable = new html_table();
            $dbcallstable->head = array("<b>$callerstr</b>", "<b>$callsstr</b>");
            $dbcallstable->width = '100%';
            $dbcallstable->size = array('80%', '20%');
            $dbcallstable->align = array('left', 'right');
            asort($this->dbcallers);
            $this->dbcallers = array_reverse($this->dbcallers);
            foreach($this->dbcallers as $ctx => $calls) {
                $dbcallstable->data[] = array($ctx, $calls);
            }
            $template->dbcallstable = html_writer::table($dbcallstable);
        }

        $template->slowpagesreportstr = get_string('slowpagesreport', 'local_advancedperfs');
        $template->slowpagereporturl = new moodle_url('/local/advancedperfs/report.php');
        $template->slowpage = $this->isslowpage;

        return $OUTPUT->render_from_template('local_advancedperfs/advancedperfs', $template);
    }

    /**
     * prints a user friendly expression of duration
     * @param int $tick a microtime complete timestamp SSSSSSS,MMMMMM
     * @param boolean $return if true, returns the report HTML as a string
     *
     */
    protected function perf_print_time($tick) {
        $timestamp = floor($tick);
        $micro = $tick - $timestamp;

        $str = userdate($timestamp);
        $str .= ' '.$micro.get_string('micro', 'perfs');

        return $str;
    }

    public static function crontask() {
        global $DB, $CFG;

        $config = get_config('local_advancedperfs');

        // Calculates the derivated value on the period between two cron activations, on a per minute basis.
        $taskdata = $DB->get_record('task_scheduled', array('classname' => '\\local_advancedperfs\\task\\monitor_task'));

        $settings = $DB->get_records_menu('config_plugins', array('plugin' => 'local_advancedperfs'), 'name', 'name,value');

        $delta = round(($taskdata->nextruntime - $taskdata->lastruntime) / 60, 2);
        if ($delta != 0) {
            $deriv = ($settings['slowpagescounter'] - $settings['slowpagescounterrec']) / $delta;
        } else {
            $deriv = 0;
        }

        if (!empty($config->filelogging)) {
            $log = fopen($CFG->dataroot.'/slowpagesvar.log', 'a');
            fputs($log, date('Y-m-d H:i:s', time()).' delta:'.$delta.' var:'.$deriv."\n");
            fclose($log);
        }

        $params = array('plugin' => 'local_advancedperfs', 'name' => 'slowpagederiv');
        $DB->set_field('config_plugins', 'value', $deriv, $params);

        if ($deriv >= $settings['slowpagederivthreshold']) {
            mtrace("TOO MANY SLW PAGES ALERT\n");
            advancedperfs_send_alert('TOO MANY SLOW PAGES', 'This moodle site seems running too many slow pages');
        }

        // Save value in rec for next pass.
        $params = array('plugin' => 'local_advancedperfs', 'name' => 'slowpagescounterrec');
        $DB->set_field('config_plugins', 'value', $settings['slowpagescounter'], $params);
        echo "done.";
    }
}

function punchin($in = '', $file = '', $line = '') {
    $pm = performance_monitor::instance();
    $pm->punchin($in, $file, $line);
}

function punchout($out = '', $in = '', $file = '', $line = '') {
    $pm = performance_monitor::instance();

    if (empty($out)) {
        $pm->punchin($in, $file, $line);
    } else {
        $pm->punchout($out, $in, $file, $line);
    }
}

function advancedperfs_send_alert($faulttype, $notification) {
    global $CFG, $DB, $SITE;

    // We have some notifications.

    $userstosendto = $DB->get_field('config_plugins', 'value', array('plugin' => 'local_advancedperfs', 'name' => 'userstosendto'));

    $targets = array();
    if (empty($userstosendto)) {
        $targets = $DB->get_records_list('user', 'id', explode(',', $CFG->siteadmins));
    } else {
        $usernames = explode(',', $userstosendto);
        foreach ($usernames as $un) {

            $un = trim($un);

            if (strpos($un, '@') !== false) {
                // This is an email.
                $u = $DB->get_record('user', array('email' => $un, 'mnethostid' => $CFG->mnet_localhost_id));
            } else if (is_numeric($un)) {
                // This is an id.
                $u = $DB->get_record('user', array('id' => $un));
            } else {
                // This is a username.
                $u = $DB->get_record('user', array('username' => $un));
            }
            if ($u) {
                $targets[$u->id] = $u;
            }
        }
    }

    foreach ($targets as $a) {
        email_to_user($a, $a, '['.$SITE->shortname.' : '.$faulttype.'] Slowness monitor', $notification);
    }
}