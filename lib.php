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
 * @package     local_advancedperfs
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * This function is not implemented in thos plugin, but is needed to mark
 * the vf documentation custom volume availability.
 */
function local_advancedperfs_supports_feature() {
    assert(1);
}

function local_advancedperfs_enable() {
    set_config('enabled', 1, 'local_advancedperfs');
}

function local_advancedperfs_disable() {
    set_config('enabled', 0, 'local_advancedperfs');
}

function local_advancedperfs_enabled() {
    return get_config('local_advancedperfs', 'enabled');
}