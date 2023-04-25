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
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
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

/**
 * this is a wrapper to a standard print_object to allow tests to pass
 * expected use of print_object();
 */
function debug_print_object($object) {
    print_object($object);
}

/**
 * this is a wrapper to a standard print_object to allow tests to pass
 * expected use of print_object();
 */
function debug_print_r($object) {
    print_r($object);
}