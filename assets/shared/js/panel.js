document.addEventListener('DOMContentLoaded', function () {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    const toggler  = document.getElementById('sidebarToggler');
    const collapseBtn = document.getElementById('sidebarCollapseBtn');
    const body     = document.body;

    // Mobile: open/close sidebar via hamburger
    if (toggler) {
        toggler.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('show');
        });
    }

    // Detect RTL
    const isRtl = document.documentElement.dir === 'rtl';

    // Desktop: collapse sidebar to icon-only mode
    if (collapseBtn) {
        collapseBtn.addEventListener('click', () => {
            body.classList.toggle('sidebar-collapsed');
            const isCollapsed = body.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            // In RTL the arrow direction is already flipped by CSS; use same icon names
            collapseBtn.querySelector('i').className =
                isCollapsed ? 'bi bi-arrow-bar-right' : 'bi bi-arrow-bar-left';
        });
    }

    // Restore collapse state on load
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        body.classList.add('sidebar-collapsed');
        if (collapseBtn) {
            collapseBtn.querySelector('i').className = 'bi bi-arrow-bar-right';
        }
    }

    // Mark active nav link based on current URL
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href !== '#' && currentPath.startsWith(href)) {
            link.classList.add('active');
            // Expand parent submenu if inside one
            const parentSubmenu = link.closest('.nav-submenu');
            if (parentSubmenu) {
                parentSubmenu.classList.add('show');
                const parentToggle = document.querySelector(`[data-submenu="${parentSubmenu.id}"]`);
                if (parentToggle) parentToggle.setAttribute('aria-expanded', 'true');
            }
        }
    });

    // Submenu accordion toggle
    document.querySelectorAll('.nav-link[data-submenu]').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId  = this.dataset.submenu;
            const submenu   = document.getElementById(targetId);
            if (!submenu) return;
            const isOpen = submenu.classList.toggle('show');
            this.setAttribute('aria-expanded', isOpen);
        });
    });

    // Close sidebar on mobile when a regular nav link is clicked
    document.querySelectorAll('.sidebar-nav .nav-link:not([data-submenu])').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('show');
            }
        });
    });
});
