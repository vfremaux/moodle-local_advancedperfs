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
 * Version details.
 *
 * @package    local_advancedperfs
 * @category   local
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright  2010 onwards Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version  = 2016111901;   // The (date) version of this plugin.
$plugin->requires = 2016051900;   // Requires this Moodle version.
$plugin->component  = 'local_advancedperfs';   // The plugin name.
$plugin->release = '3.1.0 (Build 2016111901)';
$plugin->maturity = MATURITY_RC;
$plugin->dependencies = array('local_vflibs' => 2016081100);

// Non moodle attributes.
$plugin->codeincrement = '3.1.0001';