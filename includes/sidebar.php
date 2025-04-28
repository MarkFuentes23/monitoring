<!-- HTML for the sidebar -->
<div class="sidebar col-md-3 col-lg-2 d-md-block" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <span class="logo-text">BCI</span>
        </div>
        <button id="sidebarToggle" class="btn btn-link">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <div class="position-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'monitoring.php') ? 'active' : ''; ?>" href="/views/monitoring.php">
                    <i class="fas fa-network-wired"></i> <span class="menu-text">Monitoring</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'overall_report.php') ? 'active' : ''; ?>" href="/views/overall_report.php">
                    <i class="fas fa-chart-bar"></i> <span class="menu-text">Reports</span>
                </a>
            </li>
            <li class="nav-item logout-item">
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> <span class="menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- CSS for the sidebar -->
<style>
/* Sidebar Styles */
.sidebar {
    background: linear-gradient(to bottom, #ffffff 30%, #60a5fa 100%);
    color: #1e293b;
    min-height: 100vh;
    transition: all 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.2);
    padding: 0;
    z-index: 100;
    width: 250px;
    position: fixed;
    top: 0;
    left: 0;
}

/* Header */
.sidebar-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.logo-container {
    display: flex;
    align-items: center;
}

.logo-text {
    color: #3b82f6;
    font-weight: 700;
    font-size: 22px;
    letter-spacing: 1px;
    text-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
}

/* Burger toggle button with left margin */
.sidebar-header button {
    color: #374151; /* Tailwind gray-700 */
    border: none;
    background: transparent;
    transition: all 0.3s;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 12px;  /* added left margin */
}

.sidebar-header button:hover {
    background-color: rgba(255,255,255,0.1);
    color: #3b82f6;
    transform: rotate(180deg);
}

/* Navigation links */
.sidebar .nav-link {
    color: #1e293b !important;    /* dark bluish-gray for contrast */
    padding: 14px 20px;
    border-left: 4px solid transparent;
    transition: all 0.3s;
    font-size: 15px;
    display: flex;
    align-items: center;
    margin: 4px 8px;
    border-radius: 8px;
}

.sidebar .nav-link i {
    margin-right: 12px;
    width: 22px;
    text-align: center;
    font-size: 16px;
    color: #1e293b !important;
    transition: all 0.3s;
}

/* Hover & active states */
.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    background-color: rgba(255,255,255,0.2);
    border-left: 4px solid #3b82f6;
    color: #3b82f6 !important;
}

.sidebar .nav-link:hover i,
.sidebar .nav-link.active i {
    color:rgb(0, 191, 255) !important;
}

/* Icon for Monitoring menu item */
.menu-item.monitoring .menu-link i {
    /* e.g. chart-line icon */
    color: #1e293b;
}

/* Sidebar footer */
.sidebar-footer {
    position: absolute;
    bottom: 15px;
    width: 100%;
    text-align: center;
    color: #475569;
    font-size: 12px;
    padding: 10px;
}

/* Logout link */
.logout-item {
    margin-top: 40px;
}

.logout-item .nav-link {
    color: #ef4444;
    opacity: 0.8;
}

.logout-item .nav-link:hover {
    opacity: 1;
}

.logout-item .nav-link i {
    color: #ef4444;
}

/* Collapsed sidebar */
.sidebar.collapsed {
    width: 70px;
}

.sidebar.collapsed .logo-text,
.sidebar.collapsed .menu-text,
.sidebar.collapsed .sidebar-footer {
    display: none;
}

.sidebar.collapsed .nav-link {
    padding: 14px 0;
    justify-content: center;
    margin: 4px auto;
    width: 90%;
}

.sidebar.collapsed .nav-link i {
    margin-right: 0;
    font-size: 18px;
}

.sidebar.collapsed .sidebar-header {
    justify-content: center;
    padding: 20px 5px;
}

.sidebar.collapsed .logout-item {
    margin-top: 20px;
}

/* Main content shift */
.content-wrapper {
    margin-left: 250px;
    transition: all 0.3s ease;
    padding: 15px;
    width: calc(100% - 250px);
}

.content-wrapper.expanded {
    margin-left: 70px;
    width: calc(100% - 70px);
}

/* Responsive: mobile */
@media (max-width: 767.98px) {
    .sidebar {
        width: 100%;
        position: relative;
        min-height: auto;
    }
    .sidebar.collapsed {
        width: 100%;
    }
    .sidebar.collapsed .menu-text {
        display: inline;
    }
    .sidebar .nav {
        display: flex;
        flex-direction: row;
        overflow-x: auto;
        white-space: nowrap;
        padding: 5px;
    }
    .sidebar .nav-item {
        display: inline-block;
        margin-right: 5px;
    }
    .sidebar .nav-link {
        border-left: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        padding: 10px 15px;
    }
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        border-left: none;
        border-bottom: 3px solid #3b82f6;
    }
    .sidebar-footer {
        display: none;
    }
    .logout-item {
        margin-top: 0;
    }
    .content-wrapper, 
    .content-wrapper.expanded {
        margin-left: 0;
        width: 100%;
    }
}

/* Responsive: tablet up */
@media (min-width: 768px) {
    .content-wrapper {
        margin-left: 250px;
        width: calc(100% - 250px);
    }
    .content-wrapper.expanded {
        margin-left: 70px;
        width: calc(100% - 70px);
    }
}

/* Responsive: desktop up */
@media (min-width: 992px) {
    .sidebar {
        width: 250px;
    }
    .sidebar.collapsed {
        width: 70px;
    }
    .content-wrapper {
        margin-left: 250px;
        width: calc(100% - 250px);
    }
    .content-wrapper.expanded {
        margin-left: 70px;
        width: calc(100% - 70px);
    }
}
</style>


<!-- JavaScript for the sidebar toggle functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    // Find main content container
    const mainContent = document.querySelector('.container-fluid') || 
                        document.querySelector('main') || 
                        document.querySelector('body > div:not(.sidebar)');
    
    // Add content-wrapper class
    if (mainContent && !mainContent.classList.contains('content-wrapper')) {
        mainContent.classList.add('content-wrapper');
    }
    
    // Check local storage for saved state
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    // Apply saved state on page load
    if (sidebarCollapsed) {
        sidebar.classList.add('collapsed');
        if (mainContent) {
            mainContent.classList.add('expanded');
        }
    }
    
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        sidebar.classList.toggle('collapsed');
        
        if (mainContent) {
            mainContent.classList.toggle('expanded');
        }
        
        // Save state to local storage
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    });
});
</script>