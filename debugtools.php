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
 * @subpackage local
 * @author     Valery Fremaux <valery.fremaux@club-internet.fr>
 * @version Moodle 2.x
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * this file contains a set of usefull debug tools for production
 *
 * used to very locally examining data structures.
 *
 * functions should all start with the debug_ prefix.
 */

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
        print_object($USER);
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
            print_object($caps);
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
        echo "<div class=\"debug\">";
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
                    list($path, $roleid) = split(':', $racontext);
                    echo "[$path:{$roles[$roleid]}($roleid)] => [$capnames] => {$caps[$capnames]}<br/>";
                }
            } else {
                foreach ($capnames as $capname) {
                    if (array_key_exists($capname, $caps)) {
                        list($path, $roleid) = split(':', $racontext);
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
    global $CFG, $TRACE, $OUTPUT;

    if (!empty($CFG->trace) && is_null($TRACE)) {
        $TRACE = @fopen($CFG->trace, 'a');
    }
    if (!empty($CFG->trace) && !$TRACE) {
        if (debugging()) {
            echo $OUTPUT->notification('Trace could not be open at '.$CFG->trace);
        }
    }
    return !is_null($TRACE);
}

/**
 * closes an open trace
 */
function debug_close_trace() {
    global $TRACE;

    if (!is_null($TRACE)) {
        fclose($TRACE);
        $TRACE = null;
    }
}

/**
 * outputs into an open trace (ligther than debug_trace)
 */
function debug_trace_open($str) {
    global $TRACE, $CFG;

    if (!is_null($TRACE)) {
        fputs($TRACE, @$CFG->transID." ------- ". date('Y-m-d h:i', time())." -------\n".$str."\n");
    }
}

/**
 * write to the trace
 */
function debug_trace($str) {
    global $TRACE;

    if (!is_null($TRACE)) {
        debug_trace_open($str);
    } else {
        if (debug_open_trace()) {
            debug_trace_open($str);
            debug_close_trace();
        }
    }
}

/**
 * write to the trace
 */
function debug_dump($var) {
    global $TRACE;

    ob_start();
    var_dump($var);
    $dump = ob_get_contents();
    ob_end_clean();

    if (!is_null($TRACE)) {
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

function print_object_nr($object, $depth = 1) {
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
                print_object_nr($m, $depth);
                $currentdepth--;
            } else {
                echo $indent."$k : [Object]\n";
            }
        } elseif (is_array($m)) {
            if ($depth > $currentdepth) {
                $currentdepth++;
                echo $indent."$k : ";
                print_object_nr($m, $depth);
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

function debug_print_object($object, $file = __FILE__, $line = __LINE__) {
    echo '<pre>';
    echo "Print location : $file § $line";
    echo '</pre>';
    print_object($object);
}

/**
 * This function catches debugusers from config and activates the debug mode for those users.
 * It should be called at quite soonest point in the page to toggle debug mode on if required.
 */
function debug_catch_users() {
    global $CFG, $USER, $DEBUGCAUSE, $PAGE, $DB;

    // Ensure we have a database.
    if (empty($DB)) {
        return false;
    }

    if (!$tables = $DB->get_tables(false) ) {    // No tables yet at all.
        return false;

    } else {                                 // Check for missing main tables
        $mtables = array('config', 'course', 'groupings'); // some tables used in 1.9 and 2.0, preferable something from the start and end of install.xml
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
            $DEBUGCAUSE = 'Debug User Match';
            return;
        }
    }

    if (!empty($CFG->debugfromips)) {
        $debugips = explode(',', $CFG->debugfromips);
        if (in_array($_SERVER['INET_ADDRESS'], $debugips)) {
            $CFG->debug = DEBUG_DEVELOPER;
            $DEBUGCAUSE = 'Debug IP Match';
            return;
        }
    }

    // Early calls of this function may not have sufficiant moodle libraries loaded.
    if (function_exists('has_capability')) {
        if (has_capability('local/advancedperfs:hasdebugrole', context_system::instance(), $USER->id, false)) {
            $DEBUGCAUSE = 'Debug Capability Match';
            $CFG->debug = DEBUG_DEVELOPER;
        }
    }

    if (empty($DEBUGCAUSE) && !empty($CFG->debugdisplay)) $DEBUGCAUSE = 'Standard Debug Mode';
}