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
 * @package     local_advancedperfs
 * @subpackage  local
 * @author      Valery Fremaux <valery.fremaux@club-internet.fr>
 * @version     Moodle 2.x
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright   (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * this file contains a set of usefull debug tools for production
 *
 * used to very locally examining data structures.
 *
 * functions should all start with the debug_ prefix.
 */
if (!defined('MOODLE_EARLY_INTERNAL')) {
    defined('MOODLE_INTERNAL') || die('');
}

require_once($CFG->dirroot.'/local/advancedperfs/extra/extralib.php');

function debug_print_for_user($user, $text) {
    global $USER;

    if (!isloggedin()) {
        return;
    }

    $printit = false;

    if (is_int($user)) {
        if ($USER->id == $user) {
            $printit = true;
        }
    } else {
        if ($USER->username == $user) {
            $printit = true;
        }
    }

    if ($printit) {
        echo '<div class="debug">';
        echo "<b>Debug track only for ".fullname($USER).'</b><br/>';
        echo $text;
        echo '</div>';
    }
}

/**
 * initializes perf measurement globals
 * this function MUST be called before any other performance related function
 * in the Moodle code.
 */
function debug_print_user_info($userid) {
    global $USER;

    if ($USER->id == $userid) {
        echo "<div class=\"debug\">";
        echo "<b>Debug track only for " . fullname($USER).'</b><br/>';
        debug_print_object($USER);
        echo '</div>';
    }
}

function debug_print_user_access($userid) {
    global $USER, $DB;

    if ($USER->id == $userid) {
        $access = &$USER->access;
        $roles = $DB->get_records_menu('role', array(), 'name', 'id,name');
        echo "<div class=\"debug\">";
        echo "<b>Debug track only for " . fullname($USER).'</b><br/>';
        echo "<b>Roles</b><br/>";
        foreach ($access['ra'] as $racontext => $roleids) {
            foreach ($roleids as $roleid) {
                if (!in_array($roles[$roleid], $rolenames)) {
                    $rolenames[] = $roles[$roleid];
                }
            }
            echo "[$racontext] => ".implode(',', $rolenames).'<br/>';
        }
        echo "<br/><br/><b>Capabilities</b><br/>";
        foreach ($access['rdef'] as $racontext => $caps) {
            asort($caps);
            echo "[$racontext] => ";
            debug_print_object($caps);
            echo '<br/>';
        }
        echo "</div>";
    }
}

/**
 * tracks one or more capability cascade
 *
 */
function debug_track_capabilities($userid, $capnames) {
    global $USER, $DB;

    if (empty($capnames)) {
        echo "<b>Capability tracking</b> : empty set. cannot track.<br/>";
        return;
    }

    if ($USER->id == $userid) {
        $roles = $DB->get_records_menu('role', array(), 'name', 'id,name');
        $access = &$USER->access;
        echo '<div class="debug">';
        echo "<b>Capability track only for " . fullname($USER).'</b><br/>';

        if (is_array($capnames)) {
            $capnamelist = implode(',', $capnames);
        } else {
            $capnamelist = $capnames;
        }

        echo "<b>Capabilities : </b>$capnamelist<br/>";

        foreach ($access['rdef'] as $racontext => $caps) {
            if (!is_array($capnames)) {
                if (array_key_exists($capnames, $caps)) {
                    list($path, $roleid) = explode(':', $racontext);
                    echo "[$path:{$roles[$roleid]}($roleid)] => [$capnames] => {$caps[$capnames]}<br/>";
                }
            } else {
                foreach ($capnames as $capname) {
                    if (array_key_exists($capname, $caps)) {
                        list($path, $roleid) = explode(':', $racontext);
                        echo "[$path:{$roles[$roleid]}($roleid)] => [$capname] => {$caps[$capname]}<br/>";
                    }
                }
            }
            echo '<br/>';
        }

        echo '</dir>';
    }
}

/**
 * opens a trace file
 * IMPORTANT : check very carefully the path and name of the file or it might destroy
 * some piece of code. Do NEVER use in production systems unless hot case urgent tracking
 */
function debug_open_trace() {
    global $CFG, $OUTPUT;

    if (!empty($CFG->trace) && empty($CFG->tracehandle)) {
        $CFG->tracehandle = @fopen($CFG->trace, 'a');

        if (isset($CFG->trace_initial_tracing)) {
            $CFG->trace_tracing = $CFG->trace_initial_tracing;
        } else {
            $CFG->trace_tracing = true;
        }
    }
    if (!empty($CFG->trace) && !$CFG->tracehandle) {
        if (debugging()) {
            echo $OUTPUT->notification('Trace could not be open at '.$CFG->trace);
        }
    }
    return !is_null(@$CFG->tracehandle);
}

/**
 * closes an open trace
 */
function debug_close_trace() {
    global $CFG;

    if (!is_null($CFG->tracehandle)) {
        fclose($CFG->tracehandle);
        $CFG->tracehandle = null;
    }
}

/**
 * outputs into an open trace (ligther than debug_trace)
 */
function debug_trace_open($str, $label = '') {
    global $CFG;

    if (!is_null($CFG->tracehandle)) {
        fputs($CFG->tracehandle, @$CFG->transID." ------- ". date('Y-m-d H:i', time())." ------- {$label} \n".$str."\n");
    }
}

/**
 * write to the trace
 */
function debug_trace_off() {
    global $CFG;

    $CFG->trace_tracing = false;
}

/**
 * write to the trace
 */
function debug_trace_on() {
    global $CFG;

    $CFG->trace_tracing = true;
    debug_trace("Starting trace", '', 2);
}

/**
 * write to the trace
 */
function debug_trace($str, $label = '', $backtracelevel = 1) {
    global $CFG;

    if (!isset($CFG->trace_tracing)) {
        if (isset($CFG->trace_initial_tracing)) {
            $CFG->trace_tracing = $CFG->trace_initial_tracing;
        } else {
            $CFG->trace_tracing = true;
        }
    }

    if (empty($CFG->trace_tracing)) {
        return;
    }

    if (is_object($str) || is_array($str)) {
        $str = print_r($str, true);
    }

    $bt = debug_backtrace();
    for ($i = 0; $i < $backtracelevel; $i++) {
        $caller = array_shift($bt);
    }
    $location = $caller['file'].' § '.$caller['line'];

    $str = $location."\n".$str;
    if (!empty($CFG->traceindent)) {
        $str = $CFG->traceindent.str_replace("\n", "\n".$CFG->traceindent, $str);
    }

    if (!empty($CFG->tracehandle)) {
        debug_trace_open($str, $label);
    } else {
        if (debug_open_trace()) {
            debug_trace_open($str, $label);
            debug_close_trace();
        }
    }
}

/**
 * write to the trace
 */
function debug_dump($var) {
    global $CFG;

    $dump = debug_print_r($var, true);

    if (!is_null($CFG->tracehandle)) {
        debug_trace_open($dump);
    } else {
        if (debug_open_trace()) {
            debug_trace_open($dump);
            debug_close_trace();
        }
    }
}

define('BACKTRACE_FUNCNAME', 1);
define('BACKTRACE_FUNCARGS', 2);
define('BACKTRACE_FUNCRETURN', 4);

function debug_print_clean_backtrace($options = BACKTRACE_FUNCNAME) {
    echo '<pre>';
    $backtrace = debug_print_backtrace();
    echo '</pre>';
}

function debug_print_object_nr($object, $depth = 1) {
    static $currentdepth = 1;
    static $indent = '';

    if (is_object($object)) {
        $members = get_object_vars($object);
        $objectclass = get_class($object);
    } else {
        if (is_array($object)) {
            $objectclass = 'Array';
            $members = $object;
        } else {
            if (is_string($object)) {
                echo "<pre>String: { $object } </pre><br/>";
            } else {
                echo "<pre>Scalar: { $object } </pre><br/>";
            }
            return;
        }
    }

    if ($currentdepth == 1) {
        echo '<pre>';
    }

    echo $indent.$objectclass."{\n";
    $indent = $indent."\t";
    foreach ($members as $k => $m) {
        if (is_object($m)) {
            if ($depth > $currentdepth) {
                $currentdepth++;
                echo $indent."$k : ";
                debug_print_object_nr($m, $depth);
                $currentdepth--;
            } else {
                echo $indent."$k : [Object]\n";
            }
        } else if (is_array($m)) {
            if ($depth > $currentdepth) {
                $currentdepth++;
                echo $indent."$k : ";
                debug_print_object_nr($m, $depth);
                $currentdepth--;
            } else {
                echo $indent."$k : [Array]\n";
            }
        } else {
            echo $indent."$k : $m \n";
        }
    }
    $indent = chop($indent);
    echo $indent."}\n";
    if ($currentdepth == 1) {
        echo '</pre>';
    }
}

function debug_debug_print_object($object, $file = __FILE__, $line = __LINE__) {
    echo '<pre>';
    echo "Print location : $file § $line";
    echo '</pre>';
    debug_print_object($object);
}

/**
 * This function catches debugusers from config and activates the debug mode for those users.
 * It should be called at quite soonest point in the page to toggle debug mode on if required.
 */
function debug_catch_users() {
    global $CFG, $USER, $debugcause, $PAGE, $DB;

    // Ensure we have a database.
    if (empty($DB)) {
        return false;
    }

    if (!$tables = $DB->get_tables(false) ) {    // No tables yet at all.
        return false;

    } else {
        // Check for missing main tables.
        // Some tables used in 1.9 and 2.0, preferable something from the start and end of install.xml.
        $mtables = array('config', 'course', 'groupings');
        foreach ($mtables as $mtable) {
            if (!in_array($mtable, $tables)) {
                return false;
            }
        }
    }

    // Need filter this pages or settings deadloops appear.
    if ('admin-settings' == @$PAGE->pagetype) {
        return;
    }

    if (!empty($CFG->debugusers)) {
        $debugusers = explode(',', $CFG->debugusers);
        if (in_array($USER->id, $debugusers)) {
            $CFG->debug = DEBUG_DEVELOPER;
            $debugcause = 'Debug User Match';
            return;
        }
    }

    if (!empty($CFG->debugfromips)) {
        $debugips = explode(',', $CFG->debugfromips);
        if (in_array($_SERVER['INET_ADDRESS'], $debugips)) {
            $CFG->debug = DEBUG_DEVELOPER;
            $debugcause = 'Debug IP Match';
            return;
        }
    }

    // Early calls of this function may not have sufficiant moodle libraries loaded.
    if (function_exists('has_capability')) {
        if (has_capability('local/advancedperfs:hasdebugrole', context_system::instance(), $USER->id, false)) {
            $debugcause = 'Debug Capability Match';
            $CFG->debug = DEBUG_DEVELOPER;
        }
    }

    if (empty($debugcause) && !empty($CFG->debugdisplay)) {
        $debugcause = 'Standard Debug Mode';
    }
}

/**
 * Shows finalized blocks structure for the current page.
 * Ensures we are postprocessing clones and not original records.
 */
function debug_blocks() {
    global $PAGE, $OUTPUT;

    if (optional_param('blockdebug', false, PARAM_BOOL)) {

        $regions = $PAGE->blocks->get_content_for_all_regions($OUTPUT);
        $output = [];
        foreach ($regions as $regionname => $region) {
            foreach ($region as $block) {
                $outputblock = clone($block);
                unset($outputblock->content);
                unset($outputblock->footer);
                $output[$regionname][] = $outputblock;
            }
        }
        print_object($output);
    }
}

function debug_trace_block_query($sql, $allparams) {
    foreach ($allparams as $key => $value) {
        if (is_numeric($value)) {
            $sql = preg_replace("/:$key\\b/", $value, $sql);
        } else {
            $sql = preg_replace("/:$key\\b/", "'$value'", $sql);
        }
    }
    $sql = preg_replace('/\\{(.*?)\\}/', 'mdl_\\1', $sql);
    debug_trace($sql);
}