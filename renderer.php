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
 * Renderer for advanced performance monitoring
 *
 * @package     local_performance
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright   (C) 2016 onwards Valery Fremaux
 */

/**
 * Renderer class
 */
class local_advancedperfs_renderer extends plugin_renderer_base {

    /** @var A cache of slow pages records */
    protected $slowpages;

    /** @var slow pages count */
    protected $slowcount;

    /**
     * UI tabs
     */
    public function tabs($view) {
        $taburl = new moodle_url('/local/advancedperfs/report.php', array('view' => 'slowpages'));
        $rows[0][] = new tabobject('slowpages', $taburl, get_string('slowpages', 'local_advancedperfs'));

        $taburl = new moodle_url('/local/advancedperfs/report.php', array('view' => 'db'));
        $rows[0][] = new tabobject('db', $taburl, get_string('db', 'local_advancedperfs'));

        $taburl = new moodle_url('/local/advancedperfs/report.php', array('view' => 'mem'));
        $rows[0][] = new tabobject('mem', $taburl, get_string('mem', 'local_advancedperfs'));

        $taburl = new moodle_url('/local/advancedperfs/report.php', array('view' => 'users'));
        $rows[0][] = new tabobject('users', $taburl, get_string('users', 'local_advancedperfs'));

        $taburl = new moodle_url('/local/advancedperfs/report.php', array('view' => 'urls'));
        $rows[0][] = new tabobject('urls', $taburl, get_string('urls', 'local_advancedperfs'));

        return print_tabs($rows, $view, null, null, true);
    }

    /**
     * Load data.
     */
    public function load_data() {
        global $DB;

        $config = get_config('local_advancedperfs');

        if (!empty($config->slowpageexcludes)) {
            $this->excludepatterns = explode("\n", $config->slowpageexcludes);
        }
        $this->slowpageexcludes[] = 'local\\/advancedperfs';

        if (!isset($this->slowpages)) {
            $this->slowpages = $DB->get_records('local_advancedperfs_slowp', array(), 'id DESC', '*', 0, 200);

            if (!empty($this->slowpages)) {
                $pagearray = array_values($this->slowpages);
                $this->slowcount = count($this->slowpages);
                $this->first = $pagearray[0]->timecreated;
                $this->last = $pagearray[$this->slowcount - 1]->timecreated;

                // Calculate min/max once.
                $this->mintime = 10000;
                $this->maxtime = 0;
                $this->mindbcalls = 10000;
                $this->maxdbcalls = 0;
                $this->minmem = 10000000;
                $this->maxmem = 0;
                $this->minindb = 10000000;
                $this->maxindb = 0;
                $this->minonline = 10000000;
                $this->maxonline = 0;
                $this->minactive = 10000000;
                $this->maxactive = 0;
                foreach ($this->slowpages as $pid => $p) {

                    if (!empty($this->excludepatterns)) {
                        foreach ($this->excludepatterns as $ex) {
                            $ex = trim($ex);
                            if (preg_match('/'.$ex.'/', $p->url)) {
                                // Discard unwanted pages from any graphs.
                                unset($this->slowpages[$pid]);
                                continue 2;
                            }
                        }
                    }

                    $this->mintime = min($this->mintime, $p->timespent);
                    $this->maxtime = max($this->maxtime, $p->timespent);
                    $this->mindbcalls = min($this->mindbcalls, $p->dbcalls);
                    $this->maxdbcalls = max($this->maxdbcalls, $p->dbcalls);
                    $this->minmem = min($this->minmem, $p->memused);
                    $this->maxmem = max($this->maxmem, $p->memused);
                    $this->minindb = min($this->minindb, 0 + $p->timeindb);
                    $this->maxindb = max($this->maxindb, 0 + $p->timeindb);
                    $this->minonline = min($this->minonline, 0 + $p->onlineusers);
                    $this->maxonline = max($this->maxonline, 0 + $p->onlineusers);
                    $this->minactive = min($this->minactive, 0 + $p->activeusers);
                    $this->maxactive = max($this->maxactive, 0 + $p->activeusers);
                }
            }
        }
    }

    /**
     * Print globals indicators.
     */
    public function globals() {
        global $DB;

        $config = get_config('local_advancedperfs');

        $this->load_data();

        $str = '';

        // Global slow pages count.

        $sql = "
            SELECT
                DATE_FORMAT(FROM_UNIXTIME(timecreated), '%Y-%m-%d') as date,
                SUM(timespent) as slowtime
            FROM
                {local_advancedperfs_slowp}
            GROUP BY
                DATE_FORMAT(FROM_UNIXTIME(timecreated), '%Y-%m-%d')
            ORDER BY
                slowtime DESC
        ";

        // Get the first record.
        if ($slowrecs = $DB->get_records_sql($sql, array(), 0, 1)) {
            $slowrec = array_shift($slowrecs);
            $slowest = $slowrec->date;
        } else {
            $slowest = '--';
        }

        $logsize = $DB->count_records_select('logstore_standard_log', " timecreated > ? AND origin = 'web' ", array($this->first));

        if ($logsize) {
            $slowpagesratio = $this->slowcount / $logsize;
        } else {
            $slowpagesratio = 0;
        }

        $sql = "
            SELECT DISTINCT
                DATE_FORMAT(FROM_UNIXTIME(timecreated), '%Y-%m-%d') as date
            FROM
                {local_advancedperfs_slowp}
            GROUP BY
                DATE_FORMAT(FROM_UNIXTIME(timecreated), '%Y-%m-%d')
        ";
        $daysslow = count($DB->get_records_sql($sql));

        $str .= '<div class="perfs-container">';

        $str .= '<div class="perfs-row">';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('slowpagescount', 'local_advancedperfs', $config->longpagethreshold);
        $str .= '</div>';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('since', 'local_advancedperfs');
        $str .= '</div>';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('nothingsince', 'local_advancedperfs');
        $str .= '</div>';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('daysslow', 'local_advancedperfs');
        $str .= '</div>';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('slowpagesratio', 'local_advancedperfs');
        $str .= '</div>';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('worstday', 'local_advancedperfs');
        $str .= '</div>';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('onlines', 'local_advancedperfs');
        $str .= '</div>';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('actives', 'local_advancedperfs');
        $str .= '</div>';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('mem', 'local_advancedperfs');
        $str .= '</div>';
        $str .= '</div>';

        $str .= '<div class="perfs-row">';
        $str .= '<div class="perfs-cell perfs-big globals">';
        $str .= 0 + $this->slowcount;
        $str .= '</div>';
        $str .= '<div class="perfs-cell globals">';
        $str .= '<div class="perfs-big">'.userdate($this->first).'</div>';
        $clearurl = new moodle_url('/local/advancedperfs/report.php', array('what' => 'reset', 'sesskey' => sesskey()));
        $str .= '<div><a href="'.$clearurl.'">'.get_string('reset', 'local_advancedperfs').'</a></div>';
        $str .= '</div>';
        $str .= '<div class="perfs-cell perfs-big globals">';
        $str .= format_time(time() - $this->last);
        $str .= '</div>';
        $str .= '<div class="perfs-cell perfs-big globals">';
        $str .= $daysslow.' '.get_string('days');
        $str .= '</div>';
        $str .= '<div class="perfs-cell perfs-big globals">';
        $str .= sprintf('%0.3f', $slowpagesratio).' %';
        $str .= '</div>';
        $str .= '<div class="perfs-cell perfs-big globals">';
        $str .= $slowest;
        $str .= '</div>';

        $str .= '<div class="perfs-cell perfs-small globals">';
        $str .= '<div class="perfs-small perfs-max globals">';
        $str .= $this->maxonline;
        $str .= '</div>';
        $str .= '<div class="perfs-small perfs-min globals">';
        $str .= $this->minonline;
        $str .= '</div>';
        $str .= '</div>';

        $str .= '<div class="perfs-cell perfs-small globals">';
        $str .= '<div class="perfs-small perfs-max globals">';
        $str .= $this->maxactive;
        $str .= '</div>';
        $str .= '<div class="perfs-small perfs-min globals">';
        $str .= $this->minactive;
        $str .= '</div>';
        $str .= '</div>';

        $str .= '<div class="perfs-cell perfs-small globals">';
        $str .= '<div class="perfs-small perfs-max globals">';
        $str .= $this->format_mem($this->maxmem);
        $str .= '</div>';
        $str .= '<div class="perfs-small perfs-min globals">';
        $str .= $this->format_mem($this->minmem);
        $str .= '</div>';
        $str .= '</div>';

        $str .= '</div>';

        $str .= '</div>';

        return $str;
    }

    public function time_form() {
        $str = 'Time form. ';

        return $str;
    }

    /**
     * Prints a bargraph of distribution.
     */
    public function time_dist_graph($qdiv = 50) {
        global $DB, $PAGE;

        $str = '';

        $this->load_data();

        if ($this->slowpages) {

            $min = floor($this->mintime);
            $max = ceil($this->maxtime);
            $qwidth = max(1, round(($max - $min) / $qdiv));

            foreach ($this->slowpages as $p) {
                @$dist[round(($p->timespent - $min) / $qwidth)]++;
            }

            // Fill null ranges.
            for ($i = 0; $i < $qdiv; $i++) {
                if (!isset($dist[$i])) {
                    $dist[$i] = 0;
                }
            }

            $graph = array();
            // Shift keys to band midrange.
            for ($i = 0; $i < $qdiv; $i++) {
                $key = $min + ($qwidth / 2) + ($i * $qwidth);
                $rec = new StdClass;
                $rec->timeband = round($key);
                $rec->num = $dist[$i];
                $graph[] = $rec;
            }
        }

        $options['id'] = '10';
        $options['desc'] = '';
        $options['width'] = 700;
        $options['height'] = 500;

        $title = get_string('timedist', 'local_advancedperfs');
        $renderer = $PAGE->get_renderer('local_vflibs');
        $str = $renderer->jqw_bar_chart($title, $graph, $options, 'local_advancedperfs');

        return $str;
    }

    /**
     * Prints a bargraph of distribution.
     */
    public function top_url_freq_graph($qdiv = 50) {
    }

    /**
     * Prints url by memory
     */
    public function urls_by_memory() {
        global $DB, $CFG, $PAGE;

        $data = array();
        $ticks = array();
        $datacount = 0;
        if ($graphdata = $DB->get_records('local_advancedperfs_slowp', array(), 'memused DESC,url DESC', '*', 0, 100)) {
            $datacount = count($graphdata);
            $options['xmax'] = 0;
            foreach ($graphdata as $d) {
                $moused = round($d->memused / 1000000, 2);
                if (!array_key_exists($d->url, $data)) {
                    // Keep highest.
                    $ticks[] = "'".$d->url."'";
                    $data[] = $moused;

                    // For jqw.
                    $rec = new StdClass();
                    $rec->url = $d->url;
                    $rec->memused = round($d->memused / 1000000, 2);
                    $jqwdata[] = $rec;
                }
                $options['xmax'] = max($moused, $options['xmax']);
            }
        }

        $title = get_string('urlsbymem', 'local_advancedperfs');

        $options['id'] = 10;
        $options['desc'] = '';
        $options['xunit'] = ' Mo';
        $options['xlabel'] = ' Memory used (Mo)';
        $options['seriename'] = ' Slow pages';
        $options['width'] = 900;
        $options['yflip'] = 'true';
        $options['tickwidth'] = 400;
        $options['height'] = 80 + $datacount * 22;
        $options['direction'] = 'horizontal';

        $renderer = $PAGE->get_renderer('local_vflibs');
        $str = $renderer->jqw_bar_chart($title, $jqwdata, $options, 'local_advancedperfs');

        return $str;
    }

    /**
     * Prints calculation time vs. used memory
     */
    public function time_rel_memory() {
        global $DB, $CFG;

        $options['xmin'] = 0;
        $options['xmax'] = 0;
        $options['ymin'] = 0;
        $options['ymax'] = 0;

        $data = array();
        if ($graphdata = $DB->get_records('local_advancedperfs_slowp', array(), 'timespent')) {
            $datacount = count($graphdata);
            foreach ($graphdata as $g) {

                if ($options['xmin'] != 0) {
                    $options['xmin'] = min($g->timespent, $options['xmin']);
                } else {
                    $options['xmin'] = $g->timespent;
                }
                $options['xmax'] = max($g->timespent, $options['xmax']);

                if ($options['ymin'] != 0) {
                    $options['ymin'] = min($g->memused, $options['ymin']);
                } else {
                    $options['ymin'] = $g->memused;
                }
                $options['ymax'] = max($g->memused, $options['ymax']);

                // Feed data table.
                $data[0][] = $g->timespent;
                $data[1][] = $g->memused / 1000000;
                if ($datacount < 30) {
                    $data[2][] = str_replace($CFG->wwwroot, '', $g->url);
                } else {
                    // Too many urls to be displayed.
                    $data[2][] = '';
                }
            }
            $options['ymax'] = ceil($options['ymax'] / 1000000);
            $options['ymin'] = floor($options['ymin'] / 1000000);
        }

        $options['xlabel'] = get_string('timespent', 'local_advancedperfs');
        $options['ylabel'] = get_string('memused', 'local_advancedperfs');
        $options['xunit'] = ' s';
        $options['yunit'] = ' Mo';
        $options['width'] = '680';
        $options['height'] = '680';
        $title = get_string('timerelmem', 'local_advancedperfs');
        $str = local_vflibs_jqplot_print_labelled_graph($data, $title, 'timememmap', $options);

        return $str;
    }

    /**
     * Prints calculation time vs number of users.
     */
    public function time_rel_users() {
        global $DB, $CFG;

        $options['xmin'] = 0;
        $options['xmax'] = 0;
        $options['ymin'] = 0;
        $options['ymax'] = 0;

        $data = array();
        if ($graphdata = $DB->get_records('local_advancedperfs_slowp', array(), 'timespent')) {
            $datacount = count($graphdata);
            foreach ($graphdata as $g) {

                if ($options['xmin'] != 0) {
                    $options['xmin'] = min($g->timespent, $options['xmin']);
                } else {
                    $options['xmin'] = $g->timespent;
                }
                $options['xmax'] = max($g->timespent, $options['xmax']);

                if ($options['ymin'] != 0) {
                    $options['ymin'] = min($g->onlineusers, $g->activeusers, $options['ymin']);
                } else {
                    $options['ymin'] = min($g->onlineusers, $g->activeusers);
                }
                $options['ymax'] = max($g->onlineusers, $g->activeusers, $options['ymax']);

                // Feed data table.
                $data[0][] = $g->timespent;
                $data[1][] = $g->onlineusers;
                $data[2][] = $g->activeusers;
                if ($datacount < 30) {
                    $data[2][] = str_replace($CFG->wwwroot, '', $g->url);
                } else {
                    // Too many urls to be displayed.
                    $data[2][] = '';
                }
            }
            $options['ymax'] = ceil($options['ymax']);
            $options['ymin'] = floor($options['ymin']);
        }

        $options['xlabel'] = get_string('timespent', 'local_advancedperfs');
        $options['ylabel'] = get_string('envusers', 'local_advancedperfs');
        $options['xunit'] = ' s';
        $options['yunit'] = ' u';
        $options['width'] = '680';
        $options['height'] = '680';
        $title = get_string('timerelusers', 'local_advancedperfs');
        $str = local_vflibs_jqplot_print_labelled_graph($data, $title, 'timeusersmap', $options);

        return $str;
    }

    /**
     * Prints slow pages timeline.
     */
    public function slowp_timeline() {

        $this->load_data();

        $title = get_string('slowpages', 'local_advancedperfs');
        $ylabel = get_string('num', 'local_advancedperfs');
        $labelobj = new StdClass;
        $labelobj->label = get_string('slowpages', 'local_advancedperfs');
        $labelobj->color = '#000000';
        $labelobj->lineWidth = 3;
        $labelobj->showMarker = 1;
        $labels = array($labelobj);
        $htmlid = uniqid();

        // Count per day.
        $freq = array();
        foreach ($this->slowpages as $p) {
            @$freq[date('Y-m-d', $p->timecreated)]++;
        }

        $data = array();
        $xserie = array();
        $yserie = array();
        if (!empty($freq)) {
            foreach ($freq as $day => $q) {
                $xserie[] = $day;
                $yserie[] = 0 + $q;
            }
        }
        $data[] = $xserie;
        $data[] = $yserie;

        return local_vflibs_jqplot_print_timecurve_bars($data, $title, $htmlid, $labels, $ylabel);
    }

    /**
     * 
     */
    public function slowp_dbratio_dist() {
        global $PAGE;

        if (empty($this->slowpages)) {
            return;
        }

        $this->load_data();
        $bdiv = 10;
        $bwidth = 100 / $bdiv;

        $dist = array();
        foreach ($this->slowpages as $p) {
            $dbratio = round($p->timeindb / $p->timespent * 100);
            @$dist[floor($dbratio / $bwidth)]++;
        }

        // Fill null ranges.
        for ($i = 0; $i < $bdiv; $i++) {
            if (!isset($dist[$i])) {
                $dist[$i] = 0;
            }
        }

        $graph = array();
        // Shift keys to band midrange.
        for ($i = 0; $i < $bdiv; $i++) {
            $key = ($bwidth / 2) + ($i * $bwidth);
            $rec = new StdClass;
            $rec->ratio = round($key);
            $rec->num = $dist[$i];
            $graph[] = $rec;
        }

        $title = get_string('dbratiodist', 'local_advancedperfs');
        $options['id'] = uniqid();
        $options['desc'] = '';
        $options['width'] = 700;
        $options['height'] = 500;

        $title = get_string('dbratiodist', 'local_advancedperfs');
        $renderer = $PAGE->get_renderer('local_vflibs');
        $str = $renderer->jqw_bar_chart($title, $graph, $options, 'local_advancedperfs');

        return $str;
    }

    /**
     *
     */
    public function slowp_dbquery_dist() {
        global $PAGE;

        if (empty($this->slowpages)) {
            return;
        }

        $this->load_data();
        $bdiv = 20;
        $bwidth = $this->maxdbcalls / $bdiv;

        $dist = array();
        foreach ($this->slowpages as $p) {
            @$dist[floor($p->dbcalls / $bwidth)]++;
        }

        $graph = $this->build_graph($dist, $bdiv, $bwidth);

        $title = get_string('dbquerydist', 'local_advancedperfs');
        $options['id'] = uniqid();
        $options['desc'] = '';
        $options['width'] = 700;
        $options['height'] = 500;

        $title = get_string('dbquerydist', 'local_advancedperfs');
        $renderer = $PAGE->get_renderer('local_vflibs');
        $str = $renderer->jqw_bar_chart($title, $graph, $options, 'local_advancedperfs');

        return $str;
    }

    /**
     * Top db queries.
     */
    public function top_dbqueries_dist() {
        global $PAGE;

        if (empty($this->slowpages)) {
            return;
        }

        $this->load_data();
        $bdiv = 10;
        $bwidth = 100 / $bdiv;

        $dist = array();
        foreach ($this->slowpages as $p) {
            $dbratio = round($p->timeindb / $p->timespent * 100);
            @$dist[floor($dbratio / $bwidth)]++;
        }

        $graph = $this->build_graph($dist, $bdiv, $bwidth);

        $title = get_string('dbratiodist', 'local_advancedperfs');
        $options['id'] = uniqid();
        $options['desc'] = '';
        $options['width'] = 700;
        $options['height'] = 500;

        $title = get_string('dbratiodist', 'local_advancedperfs');
        $renderer = $PAGE->get_renderer('local_vflibs');
        $str = $renderer->jqw_bar_chart($title, $graph, $options, 'local_advancedperfs');

        return $str;
    }

    /**
     * Graph builder.
     */
    protected function build_graph($dist, $bdiv, $bwidth) {

        // Fill null ranges.
        for ($i = 0; $i < $bdiv; $i++) {
            if (!isset($dist[$i])) {
                $dist[$i] = 0;
            }
        }

        $graph = array();
        // Shift keys to band midrange.
        for ($i = 0; $i < $bdiv; $i++) {
            $key = ($bwidth / 2) + ($i * $bwidth);
            $rec = new StdClass;
            $rec->ratio = round($key);
            $rec->num = $dist[$i];
            $graph[] = $rec;
        }

        return $graph;
    }

    /**
     * Top affected users.
     */
    public function top_affected_users() {
        global $DB, $CFG;

        $this->load_data();

        if (!empty($this->slowpages)) {
            foreach ($this->slowpages as $p) {
                @$dist[$p->userid]++;
            }
        }

        $siteadmins = explode(',', $CFG->siteadmins);

        asort($dist);
        array_reverse($dist);

        $table = new html_table();

        $table->head = array('<b>'.get_string('user').'</b>', '<b>'.get_string('slowpages', 'local_advancedperfs'));
        $table->align = array('left', 'left');
        $table->size = array('70%', '30%');

        $i = 0;
        foreach ($dist as $userid => $sp) {
            @$allrolesstats += $sp;
            $userurl = '';
            $user = $DB->get_record('user', array('id' => $userid));
            if ($user) {
                if (in_array($user->id, $siteadmins)) {
                    // Site admins are counted apart.
                    @$rolestats[0]->users++;
                    @$rolestats[0]->sp += $sp;
                } else {
                    // Count per higest ranked role avaialble.
                    $sql = "
                        SELECT
                            MAX(r.sortorder)
                        FROM
                            {role_assignments} ra,
                            {role} r
                        WHERE
                            ra.roleid = r.id AND
                            ra.userid = ?
                    ";
                    if ($rolerank = $DB->get_field_sql($sql, array($userid))) {
                        $role = $DB->get_record('role', array('sortorder' => $rolerank));
                        @$rolestats[$role->id]->users++;
                        @$rolestats[$role->id]->sp += $sp;
                    } else {
                        @$rolestats[-1]->users++;
                        @$rolestats[-1]->sp += $sp;
                    }
                }
                $userurl = new moodle_url('/user/view.php', array('id' => $userid));
                $userlink = '<a href="'.$userurl.'">'.fullname($user).'</a>';
            } else {
                $userlink = get_string('unconnectedusers', 'local_advancedperfs');
            }

            $table->data[] = array($userlink, $sp);
            $i++;
            if ($i > 50) {
                break;
            }
        }

        $str = html_writer::table($table);

        $table = new html_table();

        $table->head = array('<b>'.get_string('role').'</b>',
                             '<b>'.get_string('distinctusers', 'local_advancedperfs').'</b>',
                             '<b>'.get_string('slowpages', 'local_advancedperfs').'</b>',
                             '%');
        $table->align = array('left', 'left', 'left', 'left');
        $table->size = array('60%', '20%', '10%', '10%');

        $roles = role_get_names(null, ROLENAME_ALIAS, true);

        if (!empty($rolestats)) {
            foreach ($rolestats as $roleid => $rolestat) {
                if ($roleid == 0) {
                    $rolename = get_string('admin');
                } else if ($roleid == -1) {
                    $rolename = get_string('noroles', 'local_advancedperfs');
                } else {
                    $rolename = $roles[$roleid];
                }
                $ratio = ($allrolesstats > 0) ? $rolestat->sp / $allrolesstats * 100 : 0;
                $table->data[] = array($rolename, $rolestat->users, $rolestat->sp, sprintf('%.1f', $ratio));
            }
        }

        $str .= html_writer::table($table);

        return $str;
    }

    /**
     * Users globals.
     */
    public function users_globals() {
        global $DB;

        $sql = "
            SELECT
                COUNT(DISTINCT userid)
            FROM
                {local_advancedperfs_slowp}
        ";
        $numaffected = $DB->count_records_sql($sql);

        $sql = "
            SELECT
                userid,
                COUNT(*) as usercount
            FROM
                {local_advancedperfs_slowp}
            GROUP BY
                userid
            ORDER BY
                usercount DESC
        ";
        if ($maxaffectedrecs = $DB->get_records_sql($sql, array(), 0, 1)) {
            $maxaffected = array_shift($maxaffectedrecs);
            $maxaffecteduser = $DB->get_record('user', array('id' => $maxaffected->userid));
            $userurl = new moodle_url('/user/view.php', array('id' => $maxaffected->userid));
            $maxaffectedstr = '<a href="'.$userurl.'">'.fullname($maxaffecteduser).'</a>';
        } else {
            $maxaffectedstr = 'N.C.';
        }

        $alluserscount = $DB->count_records('user', array('confirmed' => 1, 'deleted' => 0));

        $str = '';

        $str .= '<div class="perfs-container">';

        $str .= '<div class="perfs-row">';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('numusersaffected', 'local_advancedperfs');
        $str .= '</div>';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('mostaffecteduser', 'local_advancedperfs');
        $str .= '</div>';
        $str .= '<div class="perfs-cell globals">';
        $str .= get_string('ratioaffectedusers', 'local_advancedperfs');
        $str .= '</div>';
        $str .= '</div>';

        $str .= '<div class="perfs-row">';
        $str .= '<div class="perfs-cell perfs-big globals">';
        $str .= '<a href"'.$userurl.'">'.$numaffected.'</a>';
        $str .= '</div>';
        $str .= '<div class="perfs-cell perfs-big globals">';
        $str .= $maxaffectedstr;
        $str .= '</div>';
        $str .= '<div class="perfs-cell perfs-big globals">';
        $str .= (!empty($alluserscount)) ? sprintf('%.3f', $numaffected / $alluserscount).' %' : '0 %';
        $str .= '</div>';
        $str .= '</div>';

        $str .= '</div>';

        return $str;
    }

    /**
     * Url y slowpage occurrences.
     */
    public function url_ranking_by_occurrence() {
        global $CFG, $PAGE;

        $this->load_data();

        if (empty($this->slowpages)) {
            return;
        }

        $rank = array();
        foreach ($this->slowpages as $p) {
            @$rank[$p->url]++;
        }

        asort($rank);
        array_reverse($rank);

        $i = 0;
        $jqwdata = array();
        foreach ($rank as $url => $rk) {
            $rec = new StdClass();
            $rec->url = $url;
            $rec->num = $rk;
            $jqwdata[] = $rec;
            $i++;
            if ($i >= 50) {
                // Limit the graph on screen.
                break;
            }
        }

        $title = get_string('urlsbyfreq', 'local_advancedperfs');

        $options['id'] = uniqid();
        $options['desc'] = '';
        $options['xunit'] = ' Q';
        $options['xlabel'] = ' Slow pages';
        $options['seriename'] = ' ';
        $options['width'] = 900;
        $options['xflip'] = 'true';
        $options['yflip'] = 'true';
        $options['tickwidth'] = 400;
        $options['height'] = 150 + count($jqwdata) * 22;
        $options['direction'] = 'horizontal';

        $renderer = $PAGE->get_renderer('local_vflibs');
        $str = $renderer->jqw_bar_chart($title, $jqwdata, $options, 'local_advancedperfs');

        return $str;

    }

    /**
     * Do we have slow pages ?
     */
    public function is_empty() {
        return empty($this->slowpages);
    }

    /**
     * Memory size formatter.
     */
    protected function format_mem($memsize) {
        if ($memsize < 1024) {
            return $memsize.'b';
        } else if ($memsize < 1024 * 1024) {
            return sprintf('%.2f', $memsize / 1024).'k';
        } else {
            return sprintf('%.2f', $memsize / (1024 * 1024)).'M';
        }
    }

    /**
     * Trace buttons.
     */
    public function tracebuttons() {
        $str = '';
        $buttonurl = new moodle_url('/local/advancedperfs/trace.php', array('what' => 'clear', 'sesskey' => sesskey()));
        $str .= $this->output->single_button($buttonurl, get_string('clear', 'local_advancedperfs'));
        $buttonurl = new moodle_url('/local/advancedperfs/trace.php', array('sesskey' => sesskey()));
        $str .= $this->output->single_button($buttonurl, get_string('reload', 'local_advancedperfs'));
        return $str;
    }
}
