/*
 *
 */
// jshint unused:false, undef:false

function perfs_panel_change_state(state) {
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