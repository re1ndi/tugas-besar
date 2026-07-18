</div> </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // 1. UTILITY FUNCTIONS
    function saveSidebarState() {
        const isCollapsed = document.body.classList.contains('sb-toggled');
        localStorage.setItem('sidebar-main-state', isCollapsed ? 'collapsed' : 'expanded');
    }

    // 2. FUNGSI UNTUK PERSISTENSI SUBMENU & ROTASI PANAH
    function handleCollapsePersistence(submenuId, iconClass, storageKey) {
        const submenu = document.getElementById(submenuId);
        const arrow = document.querySelector('.' + iconClass);
        const toggleLink = document.getElementById(submenuId.replace('submenu_', '') + '_toggle');

        if (submenu && arrow && toggleLink) {
            
            // Cek apakah ada tautan ANAK yang aktif di halaman ini
            const activeSubmenuItem = submenu.querySelector('.nav-link.active');
            const submenuState = localStorage.getItem(storageKey);
            
            // LOGIKA UTAMA: HANYA BUKA JIKA ADA CHILD YANG AKTIF ATAU STATE TERSIMPAN SEBAGAI 'open'
            if (activeSubmenuItem || submenuState === 'open') {
                submenu.classList.add('show');
                toggleLink.setAttribute('aria-expanded', 'true');
                arrow.classList.replace('bi-chevron-right', 'bi-chevron-down');
                // Simpan state agar terbuka saat pindah antar sub-link
                if (activeSubmenuItem) localStorage.setItem(storageKey, 'open');
            }

            // LISTENERS FOR ROTATION AND SAVE
            submenu.addEventListener('show.bs.collapse', function () {
                localStorage.setItem(storageKey, 'open');
                arrow.classList.replace('bi-chevron-right', 'bi-chevron-down');
            });

            submenu.addEventListener('hide.bs.collapse', function () {
                localStorage.setItem(storageKey, 'closed');
                arrow.classList.replace('bi-chevron-down', 'bi-chevron-right');
            });
        }
    }

    // 3. MAIN INITIALIZATION
    function loadAndAttachSidebarToggle() {
        // LOAD STATE SIDEBAR UTAMA (Narrow/Wide)
        if (localStorage.getItem('sidebar-main-state') === 'collapsed') {
            document.body.classList.add('sb-toggled');
        }

        // ATTACH TOGGLE + SAVE EVENT (Sidebar Utama)
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(event) {
                event.preventDefault();
                document.body.classList.toggle('sb-toggled'); // Toggle utama
                saveSidebarState(); 
            });
        }
        
        // Pasang persistence untuk SEMUA submenu (Fixes Dashboard conflict)
        handleCollapsePersistence('submenu_pembelajaran', 'toggle-icon-pembelajaran', 'submenu-pembelajaran-state');
        handleCollapsePersistence('submenu_administrasi', 'toggle-icon-administrasi', 'submenu-administrasi-state');
        handleCollapsePersistence('submenu_manajemen', 'toggle-icon-manajemen', 'submenu-manajemen-state');
        handleCollapsePersistence('submenu_keuangan', 'toggle-icon-keuangan', 'submenu-keuangan-state');
    }
    
    // Jalankan fungsi saat dokumen siap
    window.addEventListener('DOMContentLoaded', loadAndAttachSidebarToggle);
</script>
</body>
</html>