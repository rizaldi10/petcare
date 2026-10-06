/**
 * Adminator Dashboard Micro-Interactions & Sidebar Controller
 * Implements: Collapsible sidebar, LocalStorage state persistence, mobile drawer
 */

document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const sidebar = document.querySelector('.sidebar');
    const toggleBtns = document.querySelectorAll('.sidebar-toggler, #sidebarToggle');
    const backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);

    // 1. Restore Sidebar State from localStorage (Desktop only)
    if (window.innerWidth >= 992) {
        const isCollapsed = localStorage.getItem('petcare_sidebar_collapsed') === 'true';
        if (isCollapsed) {
            body.classList.add('sidebar-collapsed');
        }
    }

    // 2. Toggle Handler
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (window.innerWidth < 992) {
                // Mobile: toggle mobile open
                body.classList.toggle('sidebar-mobile-open');
            } else {
                // Desktop: toggle collapsed
                body.classList.toggle('sidebar-collapsed');
                const collapsed = body.classList.contains('sidebar-collapsed');
                localStorage.setItem('petcare_sidebar_collapsed', collapsed);
            }
        });
    });

    // 3. Close on backdrop click (Mobile)
    backdrop.addEventListener('click', function () {
        body.classList.remove('sidebar-mobile-open');
    });

    // 4. Close mobile sidebar on navigation link click
    if (sidebar) {
        sidebar.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    body.classList.remove('sidebar-mobile-open');
                }
            });
        });
    }

    // 5. Window resize listener
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            body.classList.remove('sidebar-mobile-open');
            const isCollapsed = localStorage.getItem('petcare_sidebar_collapsed') === 'true';
            if (isCollapsed) {
                body.classList.add('sidebar-collapsed');
            } else {
                body.classList.remove('sidebar-collapsed');
            }
        }
    });
});
