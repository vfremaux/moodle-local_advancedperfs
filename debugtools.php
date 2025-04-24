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
 * This file contains a set of usefull debug tools for production.
 *
 * @package     local_advancedperfs
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright   (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * used to very locally examining data structures.
 *
 * functions should all start with the debug_ prefix.
 */

if (!defined('MOODLE_EARLY_INTERNAL')) {
    defined('MOODLE_INTERNAL') || die('');
}

define('TRACE_ERRORS', 1); // Errors should be always traced when trace is on.
define('TRACE_NOTICE', 3); // Notices are important notices in normal execution.
define('TRACE_DEBUG', 5); // Debug are debug time notices that should be burried in debug_fine level when debug is ok.
define('TRACE_DATA', 8); // Data level is when requiring to see data structures content.
define('TRACE_DEBUG_FINE', 10); // Debug fine are control points we want to keep when code is refactored and debug needs to be reactivated.

// Helpers collide with native functions of moodle.
define('KINT_SKIP_HELPERS', true);

require_once($CFG->dirroot.'/local/advancedperfs/extra/extralib.php');
require_once($CFG->dirroot.'/local/advancedperfs/extra/kint.phar');

use Kint\Kint;

/**
 * this is an early function that needs to be called as soon as possible.
 */
function debug_set_for_developers() {
    global $CFG, $USER;

    $config = get_config('local_advancedperfs');
    if (!empty($config->devdebuglevel)) {
        $devusers = preg_split("/[\s,]+/", $config->devusers);
        if (in_array($USER->id, $devusers)) {
            $CFG->debug = $config->devdebuglevel;
            $CFG->debugdisplay = true;
        }
    }
}

/**
 * Print an object of a message for a specific user.
 * @param mixed $userorid
 * @param mixed $text
 */
function debug_print_for_user($userorid, $text) {
    global $USER;

    if (!isloggedin()) {
        return;
    }

    if (is_object($text) || is_array($text)) {
        $text = '<pre>'.var_export($text, true).'</pre>';
    }

    $printit = false;

    if (is_int($userorid)) {
        if ($USER->id == $userorid) {
            $printit = true;
        }
    } else {
        if ($USER->username == $userorid) {
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
 * @param int $userid 
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

/**
 * param int $userid
 */
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
 * @param int $userid
 * @param mixed $capnames
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

    $config = get_config('local_advancedperfs');

    if (!$config->traceout) {
        return;
    }

    if (empty($CFG->trace)) {
        return;
    }

    $tracelocation = $CFG->trace;
    $tracelocation = str_replace('%DATAROOT%', $CFG->dataroot, $tracelocation); // Optional placeolder.

    if (!empty($tracelocation) && empty($CFG->tracehandle)) {
        if (!empty($config->maxtracefilesize) && is_file($tracelocation)) {
            // If file already exists and we have some size limit on it.
            $info = stat($tracelocation);
            if ($info['size'] > $config->maxtracefilesize * 1024) {
                // Truncate existing trace to keep it under the maxsize.
                $CFG->tracehandle = @fopen($tracelocation, 'w');
            }
        }

        if (empty($CFG->tracehandle)) {
            $CFG->tracehandle = @fopen($tracelocation, 'a');
        }

        if (isset($CFG->trace_initial_tracing)) {
            $CFG->trace_tracing = $CFG->trace_initial_tracing;
        } else {
            $CFG->trace_tracing = true;
        }
    }

    if (is_null($CFG->tracehandle)) {
        if ($CFG->debug == DEBUG_DEVELOPER) {
            echo $OUTPUT->notification('Trace could not be open at '.$tracelocation);
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
 * Use this call in code to stop execution range tracing.
 */
function debug_trace_off() {
    global $CFG;

    $CFG->trace_tracing = false;
}

/**
 * Use this call in code to start tracing from a code execution statement.
 */
function debug_trace_on() {
    global $CFG;

    $CFG->trace_tracing = true;
    debug_trace("Starting trace", '', 2);
}

/**
 * write to the trace
 */
function debug_trace($str, $tracelevel = TRACE_NOTICE, $label = '', $backtracelevel = 1) {
    global $CFG;

    $config = get_config('local_advancedperfs');
    if (empty($config->traceout)) {
        return;
    }

    // check if labels are required, and filter trace.
    $tracelabels = optional_param('debuglabel', false, PARAM_TEXT);
    if (!empty($tracelabels)) {
        if ($tracelabels = '*') {
            // Options: * means : pass all EXCEPT labelized trace calls.
            if (!empty($label)) {
                return;
            }
        } else {
            $requiredlabels = explode(',', $tracelabels);
            $found = false;
            foreach ($requiredlabels as $tl) {
                if ($label == $tl) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return;
            }
        }
    }

    if ($tracelevel && ($tracelevel > $config->traceout)) {
        return;
    }

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
        if ($caller) {
            $callers[] = $caller;
        }
    }
    $location = '';
    foreach ($callers as $caller) {
        $location .= $caller['file'].' § '.$caller['line']."\n";
    }

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
 * debug trace sql
 * @param string $sql
 * @param int $tracelevel
 * @param array $params
 */
function debug_trace_sql($sql, $params, $tracelevel) {
    global $DB;

    list($sql, $params, $type) = $DB->fix_sql_params($sql, $params);

    if (empty($params)) {
        debug_trace($sql);
    }
    // Ok, we have verified sql statement with ? and correct number of params.
    $parts = array_reverse(explode('?', $sql));
    $return = array_pop($parts);
    foreach ($params as $param) {
        if (is_bool($param)) {
            $return .= (int)$param;
        } else if (is_null($param)) {
            $return .= 'NULL';
        } else if (is_number($param)) {
            // We have to always use strings because mysql is using weird automatic int casting.
            $return .= "'".$param."'";
        } else if (is_float($param)) {
            $return .= $param;
        } else {
            $return .= "'$param'";
        }
        $return .= array_pop($parts);
    }
    debug_trace($return, $tracelevel);
}

/**
 * write to the trace
 * @param object $var
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

/**
 * @param string $options
 */
function debug_print_clean_backtrace($options = BACKTRACE_FUNCNAME) {
    echo '<pre>';
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    echo '</pre>';
}

/**
 * @param string $msg
 */
function debug_trace_clean_backtrace($msg = '') {
    ob_start();
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $backtrace = ob_get_clean();
    debug_trace($msg."\n".$backtrace);
    return $msg."\n".$backtrace;
}

/**
 * Send some report to administrator.
 * @param string $title
 * @param string $msg
 * @param string $file
 * @param string $line
 */
function debug_send_report_admin($title, $msg = '', $file = 'unknown', $line = 'unknown') {
    global $DB, $SITE;

    $admin = $DB->get_record('user', ['username' => 'admin']);
    $sitename = (is_object($SITE)) ? $SITE->shortname : 'unknown';
    $subject = "Debug output on {$sitename} : {$title} ";
    $message = "
        file: $file
        line: $line
        message: $msg
    ";
    email_to_user($admin, $admin, $subject, $message, $message);
}

/**
 * Print an object structure down to some nesting level
 * @param object $object
 * @param int $depth
 * @param bool $return if false, prints result.
 */
/*
function debug_print_object_nr($object, $depth = 1, $return = false) {
    static $currentdepth = 1;
    static $indent = '';

    $str = '';

    if (is_object($object)) {
        $members = [];
        $reflect = new ReflectionClass($object);
        $props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
        foreach ($props as $p) {
            $p->setAccessible(true);
            $members[$p->getName()] = $p->getValue($object);
        }

        $objectclass = get_class($object);
    } else {
        if (is_array($object)) {
            $objectclass = 'Array';
            $members = $object;
        } else {
            if (is_string($object)) {
                if ($currentdepth == 1) {
                    $str .= "\n<pre>\n\n\n\n\n\n";
                }
                echo $indent."String: { $object }";
                if ($currentdepth == 1) {
                    $str .= "\n</pre>";
                }
            } else {
                if ($currentdepth == 1) {
                    $str .= "\n<pre>\n\n\n\n\n\n";
                }
                $str .= $indent."Scalar: { $object }";
                if ($currentdepth == 1) {
                    $str .= "\n</pre>";
                }
            }
            return;
        }
    }

    if ($currentdepth == 1) {
        $str .= "\n<pre>\n\n\n\n\n\n";
    }

    $str .= $objectclass." {\n";
    $indent = $indent."\t";
    ksort($members);
    foreach ($members as $k => $m) {
        if (is_object($m)) {
            if ($depth > $currentdepth) {
                $currentdepth++;
                $str .= $indent."$k : ";
                $str .= debug_print_object_nr($m, $depth, true);
                $str .= "\n";
                $currentdepth--;
            } else {
                $str .= $indent."$k : [Object]\n";
            }
        } else if (is_array($m)) {
            if ($depth > $currentdepth) {
                $currentdepth++;
                $str .= $indent."$k : ";
                $str .= debug_print_object_nr($m, $depth, true);
                $str .= "\n";
                $currentdepth--;
            } else {
                $str .= $indent."$k : [Array]\n";
            }
        } else {
            $str .= $indent."$k : $m \n";
        }
    }
    $indent = substr($indent, 0, -1);
    $str .= $indent."}\n";
    if ($currentdepth == 1) {
        $str .= "\n</pre>\n";
    }

    if ($return) {
        return $str;
    }
    echo $str;
}
*/
function debug_print_object_nr($object, $depth = 7, $return = false) {

    if ($depth > 4) {
        raise_memory_limit(MEMORY_EXTRA);
    }

    Kint::$mode_default = Kint::MODE_RICH;
    Kint::$depth_limit = $depth;
    Kint::$return = $return;

    return Kint::dump($object);
}

/**
 * Print object substitute.
 * @param object $object
 * @param string $file
 * @param string $line
 */
function debug_debug_print_object($object, $file = __FILE__, $line = __LINE__) {
    echo '<pre>';
    echo "Print location : $file § $line";
    echo '</pre>';
    debug_print_object($object);
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

/**
 * 
 * @param string $sql
 * @param array $allparams
 */
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