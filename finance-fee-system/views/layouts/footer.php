<?php
// views/layouts/footer.php
?>
    </div> </div> <style>
/* =========================================
   ✨ PAGE ANIMATIONS & TRANSITIONS ✨
   ========================================= */
body { animation: pageEnter 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes pageEnter { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.page-exit { animation: pageExit 0.3s cubic-bezier(0.8, 0, 0.2, 1) forwards !important; pointer-events: none; }
@keyframes pageExit { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-10px); } }

body, .main-wrapper, .sidebar, .sidebar * , .main-content-wrapper, .card, 
.dashboard-header p, .dashboard-header h1, table, th, td, tr, input, select, textarea {
    transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
}

/* =========================================
   📱 MOBILE RESPONSIVE MAGIC (MEDIA QUERIES)
   ========================================= */
@media (max-width: 768px) {
    /* Itago ang sidebar sa labas ng screen by default */
    .sidebar {
        position: fixed;
        left: -300px;
        top: 0;
        height: 100vh;
        box-shadow: 5px 0 25px rgba(0,0,0,0.1);
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* I-slide papasok ang sidebar kapag pinindot ang menu */
    .sidebar.mobile-active {
        left: 0;
    }

    /* Ipakita ang Mobile Topbar */
    .mobile-topbar {
        display: flex !important;
    }

    /* Ayusin ang padding sa cellphone para mas fit */
    .main-content-wrapper {
        padding: 1rem 0.8rem !important;
    }
    .card {
        padding: 1rem !important;
    }
    .dashboard-header h1 {
        font-size: 1.5rem !important;
    }
}

/* =========================================
   ☀️ LIGHT MODE (DEFAULT)
   ========================================= */
.main-wrapper { background-color: #f8fafc; }
.main-content-wrapper { max-width: none !important; min-width: 0 !important; margin: 0 !important; width: 100% !important; }
.main-content-wrapper h1, .main-content-wrapper h2, .main-content-wrapper h3, .main-content-wrapper p, .main-content-wrapper td, .main-content-wrapper th, .main-content-wrapper label { color: #1e293b; }
.main-content-wrapper .card p { color: #475569; }

.sidebar { background-color: #ffffff; border-right: 1px solid #e2e8f0; }
.sidebar-logo-container { border-bottom: 1px solid #e2e8f0; }
.sidebar-title { color: #1e293b; }
.sidebar-subtitle { color: #64748b; }
.sidebar-category { color: #64748b; }
.sidebar-nav a.nav-link { color: #475569; text-decoration: none; display: block; padding: 0.6rem 0.8rem; border-radius: 6px; font-weight: 500; }
.sidebar-nav a.nav-link:hover { background-color: #f1f5f9; color: #3b82f6; padding-left: 1.2rem; }
.sidebar-footer-container { border-top: 1px solid #e2e8f0; }
.sidebar-footer-text { color: #64748b; }
.sidebar-footer-name { color: #1e293b; }

/* =========================================
   🌙 DARK MODE 
   ========================================= */
body.dark-mode .main-wrapper { background-color: #0f172a; }
body.dark-mode .main-content-wrapper { background-color: #0f172a !important; }
body.dark-mode .card, body.dark-mode [id$="Modal"] .card { background-color: #1e293b !important; border: 1px solid #334155 !important; box-shadow: none !important; }
body.dark-mode .main-content-wrapper h1, body.dark-mode .main-content-wrapper h2, body.dark-mode .main-content-wrapper h3, body.dark-mode .main-content-wrapper p, body.dark-mode .main-content-wrapper td, body.dark-mode .main-content-wrapper th, body.dark-mode .main-content-wrapper label, body.dark-mode .main-content-wrapper div { color: #e2e8f0 !important; }
body.dark-mode .main-content-wrapper .dashboard-header p, body.dark-mode .main-content-wrapper .card p, body.dark-mode .main-content-wrapper th { color: #94a3b8 !important; }
body.dark-mode table tr { border-bottom: 1px solid #334155 !important; }
body.dark-mode thead tr { border-bottom: 2px solid #475569 !important; background-color: #0f172a !important; }
body.dark-mode input, body.dark-mode select, body.dark-mode textarea { background-color: #0f172a !important; color: #f8fafc !important; border: 1px solid #475569 !important; }

body.dark-mode .sidebar { background-color: #1e293b !important; border-right: 1px solid #334155 !important; }
body.dark-mode .sidebar-logo-container { border-bottom: 1px solid #334155 !important; }
body.dark-mode .sidebar-title { color: #f8fafc !important; }
body.dark-mode .sidebar-subtitle { color: #94a3b8 !important; }
body.dark-mode #themeToggle { border-color: #475569 !important; }
body.dark-mode .sidebar-category { color: #94a3b8 !important; }
body.dark-mode .sidebar-nav a.nav-link { color: #cbd5e1 !important; }
body.dark-mode .sidebar-nav a.nav-link:hover { background-color: #334155 !important; color: #60a5fa !important; }
body.dark-mode .sidebar-footer-container { border-top: 1px solid #334155 !important; }
body.dark-mode .sidebar-footer-text { color: #94a3b8 !important; }
body.dark-mode .sidebar-footer-name { color: #f8fafc !important; }

/* Dark Mode para sa Mobile Topbar */
body.dark-mode .mobile-topbar { background: #1e293b !important; border-bottom: 1px solid #334155 !important; }
body.dark-mode .mobile-title, body.dark-mode #mobileToggleBtn { color: #f8fafc !important; }

</style>

<script>
    // =========================================
    // 📱 MOBILE SIDEBAR LOGIC
    // =========================================
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('mobileToggleBtn');

    if (toggleBtn && sidebar && overlay) {
        // Buksan ang menu kapag pinindot ang hamburger icon
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.add('mobile-active');
            overlay.style.display = 'block';
        });

        // Isara ang menu kapag pinindot yung blurred na likuran
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-active');
            overlay.style.display = 'none';
        });
    }

    // =========================================
    // 🌙 THEME TOGGLE & SMOOTH NAV LOGIC
    // =========================================
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        if (localStorage.getItem('financeTheme') === 'dark') {
            document.body.classList.add('dark-mode');
            themeToggle.innerHTML = '☀️'; 
        } else {
            themeToggle.innerHTML = '🌙'; 
        }

        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('financeTheme', 'dark');
                document.documentElement.classList.add('dark-loaded');
                themeToggle.innerHTML = '☀️';
            } else {
                localStorage.setItem('financeTheme', 'light');
                document.documentElement.classList.remove('dark-loaded');
                themeToggle.innerHTML = '🌙';
            }
        });
    }

    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const target = this.href;
            if (target && target !== window.location.href) {
                e.preventDefault(); 
                document.body.classList.add('page-exit'); 
                setTimeout(() => { window.location.href = target; }, 300);
            }
        });
    });

    document.getElementById('logoutBtn')?.addEventListener('click', async () => {
        try {
            const response = await fetch('../../api/auth/logout.php', { method: 'POST' });
            if (response.ok) {
                document.body.classList.add('page-exit');
                setTimeout(() => { window.location.href = '../auth/login.php'; }, 300);
            }
        } catch (error) { console.error('Logout failed', error); }
    });
</script>
</body>
</html>