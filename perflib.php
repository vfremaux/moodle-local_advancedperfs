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

if (!defined('MOODLE_EARLY_INTERNAL')) {
    defined('MOODLE_INTERNAL') || die('');
}

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

global $MOODLE_PERFS;
global $MOODLE_PERFS_CATS;

$MOODLE_PERFS = array();
$MOODLE_PERFS_CATS = array();

define('PUNCHED_IN', 1);
define('PUNCHED_OUT', 0);

/**
* initializes perf measurement globals
* this function MUST be called before any other performance related function
* in the Moodle code.
*/
function perf_init() {
    global $MOODLE_PERFS;
    global $MOODLE_PERFS_CATS;

    $MOODLE_PERFS_CATS = array('overall', 'dbcalls', 'rpccalls', 'header', 'content', 'footer');

    $MOODLE_PERFS = array();
    foreach($MOODLE_PERFS_CATS as $cat){
        perf_init_cat($cat);
    }

    _perf_punchin('overall', __FILE__, __LINE__);
    _perf_punchin('init', __FILE__, __LINE__);
}

/**
* Initializes indicator object for a single track. Is used 
* for hot initializing a new category when discovered.
* @param string $cat
*/
function perf_init_cat($cat){
    global $MOODLE_PERFS;

    if (!isset($MOODLE_PERFS[$cat]))
        $MOODLE_PERFS[$cat] = new stdClass();
    $MOODLE_PERFS[$cat]->state = PUNCHED_OUT;
    $MOODLE_PERFS[$cat]->in = 0;
    $MOODLE_PERFS[$cat]->total = 0;
    $MOODLE_PERFS[$cat]->min = 0;
    $MOODLE_PERFS[$cat]->max = 0;
    $MOODLE_PERFS[$cat]->occurrences = 0;
    $MOODLE_PERFS[$cat]->mean = 0;
}

/**
* punches in a time measurement. 
* @param string $category the category to setup
*/
function perf_punchin($category, $file, $line){
    global $MOODLE_PERFS;
    global $MOODLE_PERFS_CATS;
    global $CFG;

    if (empty($CFG->timebenches)) return false;

    _perf_punchin($category, $file, $line);
}

/**
* unprotected version. To be privatized...
* @param string $category the category we want to punch in
*/
function _perf_punchin($category, $file = __FILE__, $line = __LINE__){
    global $MOODLE_PERFS;
    global $MOODLE_PERFS_CATS;
    global $OUTPUT, $CFG;
    static $PERFS_INITIALIZED = false;
    
    if (($category == 'init')){
        if (($PERFS_INITIALIZED == true)) {
            throw(new Exception());
            die;
        } else {
            $PERFS_INITIALIZED = true;
        }
    }

    if (!is_array($MOODLE_PERFS)){
        echo $OUTPUT->notification("Perf system not initialized");
    }

    // discovering new categories through a first punch in
    if (!in_array($category, $MOODLE_PERFS_CATS)){
        $MOODLE_PERFS_CATS[] = $category;
        perf_init_cat($category);
    }

    list($usec, $sec) = explode(' ',microtime()); 
    $tick = (float)$sec + (float)$usec;
    $MOODLE_PERFS[$category]->in = $tick;
    $MOODLE_PERFS[$category]->state = PUNCHED_IN;
    $MOODLE_PERFS[$category]->infile = str_replace($CFG->dirroot, '', $file);
    $MOODLE_PERFS[$category]->inline = $line;
}

function perf_punchout($category, $bouncein = '', $file = __FILE__, $line = __LINE__){
    global $MOODLE_PERFS;
    global $MOODLE_PERFS_CATS;
    global $CFG;

    if (empty($CFG->timebenches)) return false;

    _perf_punchout($category, $bouncein, $file, $line);
}
/**
* punches out recording the cumulated time
* @param string $category the category we want to punch out
* @param string $bouncein a new category where to bounce a punchin
*/
function _perf_punchout($category, $bouncein = '', $file = '', $line = ''){
    global $MOODLE_PERFS;
    global $MOODLE_PERFS_CATS;
    global $OUTPUT, $CFG;
    
    $return = 0;

    if (!is_array($MOODLE_PERFS)){
        echo $OUTPUT->notification("Perf system not initialized");
    }
    
    if (!in_array($category, $MOODLE_PERFS_CATS)){
        echo $OUTPUT->notification("Unknown perf category: $category");
        return false;
    }    
        
    list($usec, $sec) = explode(' ', microtime()); 
    $tick = (float)$sec + (float)$usec;
    $duration = $tick - (float)$MOODLE_PERFS[$category]->in;
    if (!empty($CFG->slowexectime) && ($duration > $CFG->slowexectime)){
        $return  = -1;
    }
    $MOODLE_PERFS[$category]->total += $duration;
    $MOODLE_PERFS[$category]->occurrences++;
    $MOODLE_PERFS[$category]->outfile = str_replace($CFG->dirroot, '', $file);
    $MOODLE_PERFS[$category]->outline = $line;

    if ($duration > $MOODLE_PERFS[$category]->max){
        $MOODLE_PERFS[$category]->max = $duration;
    }

    if ($MOODLE_PERFS[$category]->min == 0){ // first initialization
        $MOODLE_PERFS[$category]->min = $duration;
    } else {
        if ($duration != 0){
            if ($duration < $MOODLE_PERFS[$category]->min){
                $MOODLE_PERFS[$category]->min = $duration;
            }
        }
    } 

    $n = $MOODLE_PERFS[$category]->occurrences;
    if ($n == 1){
        $MOODLE_PERFS[$category]->mean = $duration;
    } else if ($n > 1){
        $MOODLE_PERFS[$category]->mean = ($MOODLE_PERFS[$category]->mean * ($n - 1) + $duration) / $n;
    }
    $MOODLE_PERFS[$category]->state = PUNCHED_OUT;

    if (!empty($bouncein)){
        _perf_punchin($bouncein, $file, $line);
    }

    if ($return == -1) {
        $FILE = fopen($CFG->dataroot.'/slowexec.log', 'w+');
        fputs($FILE, $category);
        ob_start();
        $backtrace = debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        fputs($FILE, ob_get_clean());
        fclose($FILE);
    }
    
    return $return;
}

/**
* prints a complete report
*
**/
function perf_print_report($force = false) {
    global $MOODLE_PERFS;
    global $MOODLE_PERFS_CATS;
    global $CFG, $USER, $OUTPUT;

    perf_punchout('overall', null, __FILE__, __LINE__);

    if (empty($CFG->timebenches)) return false;

    $context = context_system::instance();

    if (!has_capability('local/advancedperfs:view', $context) && !$force) return false;

    $catstr = get_string('categories', 'local_advancedperfs');
    $minstr = get_string('location', 'local_advancedperfs');
    $locationstr = get_string('location', 'local_advancedperfs');
    $totalstr = get_string('total', 'local_advancedperfs');
    $minstr = get_string('min', 'local_advancedperfs');
    $maxstr = get_string('max', 'local_advancedperfs');
    $meanstr = get_string('mean', 'local_advancedperfs');
    $occstr = get_string('occurrences', 'local_advancedperfs');

    $table = new html_table();
    $table->head = array("<b>$catstr</b>", "<b>$locationstr</b>", "<b>$totalstr</b>", "<b>$minstr</b>", "<b>$maxstr</b>", "<b>$meanstr</b>", "<b>$occstr</b>");
    $table->align = array('left', 'left', 'left', 'left', 'left', 'left', 'center');
    $table->width = "100%";
    
    asort($MOODLE_PERFS_CATS);
    
    foreach ($MOODLE_PERFS_CATS as $cat) {
        $occ = $MOODLE_PERFS[$cat]->occurrences;
        $total = $MOODLE_PERFS[$cat]->total;
        if ($occ > 1) {
            $max = $MOODLE_PERFS[$cat]->max;
            $min = $MOODLE_PERFS[$cat]->min;
            $mean = $MOODLE_PERFS[$cat]->mean;
        } else {
            $max = '';
            $min = '';
            $mean = '';
        }
        if (preg_match("/^_(.*)$/", $cat, $matches)) {
            $catname = $matches[1]; // keep untranlsated
        } elseif (preg_match("/^printing_(.*)$/", $cat, $matches)) {
            $blockstr = get_string('pluginname', 'block_'.$matches[1]);
            if ($blockstr == '[[blockname]]'){
                $blockstr = $matches[1];
            }
            $catname = get_string('printing', 'local_advancedperfs').' '.$blockstr;
        } else {
            $catname = $cat;
        }
        
        $locations = str_replace($CFG->dirroot, '', @$MOODLE_PERFS[$cat]->infile).':$'.@$MOODLE_PERFS[$cat]->inline;
        $locations .= '<br/>'.@$MOODLE_PERFS[$cat]->outfile.':$'.@$MOODLE_PERFS[$cat]->outline;
        
        $table->data[] = array($catname, $locations, "<span id=\"{$catname}_total\">$total</span>", "<span id=\"{$catname}_min\">$min</span>", "<span id=\"{$catname}_max\">$max</span>", "<span id=\"{$catname}_mean\">$mean</span>", $occ);
    }
    echo '<div style="background-color:#EAE9F3">';
    echo $OUTPUT->heading(get_string('perfs', 'local_advancedperfs'));
    echo "<div id=\"timebenches\">\n";
    echo html_writer::table($table);
    echo "</div>\n";
    echo "</div>\n";
}

/**
* prints a user friendly expression of duration
* @param int $tick a microtime complete timestamp SSSSSSS,MMMMMM
* @param boolean $return if true, returns the report HTML as a string
*
**/
function perf_print_time($tick, $return = false){
    $timestamp = floor($tick);
    $micro = $tick - $timestamp;
    
    $str = userdate($timestamp);
    $str .= ' '.$micro.get_string('micro', 'perfs');
    
    if ($return) return $str;
    echo $str;
}
