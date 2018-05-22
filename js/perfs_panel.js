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

// jshint undef:false, unused:false, scripturl:true

/**
 * Javascript controller for controlling the sections.
 *
 * @module     local_advancedperfs/perfs_panel
 * @package    local_advancedperfs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function perf_panel_change_state(state) {
    var url = M.cfg.wwwroot + '/local/advancedperfs/ajax/services.php?what=changepanelpreference&state=' + state;

    if (state) {
        $('#timebenches').addClass('perfs-visible');
        $('#timebenches').removeClass('perfs-hidden');
        $('#perfs-pref-toggler').attr('href', 'Javascript:perfs_panel_change_state(0)');
    } else {
        $('#timebenches').addClass('perfs-hidden');
        $('#timebenches').removeClass('perfs-visible');
        $('#perfs-pref-toggler').attr('href', 'Javascript:perfs_panel_change_state(1)');
    }

    $.get(url, function(data, status){});
}
