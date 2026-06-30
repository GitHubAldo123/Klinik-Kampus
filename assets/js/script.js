document.addEventListener('DOMContentLoaded', function () {
    var sidebar       = document.getElementById('sidebar');
    var overlay       = document.getElementById('sidebar-overlay');
    var btnToggle     = document.getElementById('btn-toggle-sidebar');

    function bukaSidebar() {
        if (sidebar) sidebar.classList.remove('-translate-x-full');
        if (overlay) overlay.classList.remove('hidden');
    }

    function tutupSidebar() {
        if (sidebar) sidebar.classList.add('-translate-x-full');
        if (overlay) overlay.classList.add('hidden');
    }

    if (btnToggle) {
        btnToggle.addEventListener('click', bukaSidebar);
    }
    if (overlay) {
        overlay.addEventListener('click', tutupSidebar);
    }
});

function konfirmasiHapus(pesan) {
    return confirm(pesan || 'Apakah Anda yakin ingin menghapus data ini?');
}