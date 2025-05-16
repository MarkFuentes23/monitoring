<div class="sidebar col-md-3 col-lg-2 d-md-block" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="../logo.png" alt="BCI Logo" class="logo-image">
        </div>
        <button id="sidebarToggle" class="btn btn-link toggle-btn">
        <i class="fas fa-bars burger-icon"></i>
        </button>
    </div>
    <div class="position-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home-alt"></i> <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'monitoring.php') ? 'active' : ''; ?>" href="/views/monitoring.php">
                    <i class="fas fa-chart-line"></i> <span class="menu-text">Monitoring</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'overall_report.php') ? 'active' : ''; ?>" href="/views/overall_report.php">
                <i class="fas fa-chart-bar menu-icon"></i></i> <span class="menu-text">Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'overall_report.php') ? 'active' : ''; ?>" href="/register.php">
                <i class="fas fa-user menu-icon"></i></i> <span class="menu-text">User Management</span>
                </a>
            </li>
            <li class="nav-item logout-item">
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-arrow-right-from-bracket"></i> <span class="menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <p>© 2025 BCI System</p>
    </div>
</div>

<!-- CSS for the improved sidebar -->
<style>
/* Sidebar Styles */
.sidebar {
    background: linear-gradient(145deg, #ffffff 20%, #3b82f6 130%);
    color: #1e293b;
    min-height: 100vh;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 0;
    z-index: 100;
    width: 260px;
    position: fixed;
    top: 0;
    left: 0;
    border-right: 3px solid #fbbf24;
    overflow-x: hidden;
}

/* Header */
.sidebar-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid rgba(251, 191, 36, 0.5);
    overflow: hidden;
}

.logo-container {
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.logo-image {
    max-width: 150px;
    height: auto;
    transition: all 0.3s ease;
    filter: drop-shadow(0 0 3px rgba(59, 130, 246, 0.4));
}


/* Toggle button */
.toggle-btn {
    color: #1e40af;
    border: none;
    background: transparent;
    transition: all 0.4s;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.toggle-btn:hover {
    background-color: rgba(251, 191, 36, 0.2);
    color: #1e40af;
    transform: scale(1.1);
    box-shadow: 0 0 8px rgba(251, 191, 36, 0.5);
}

/* Navigation links */
.sidebar .nav-link {
    color: #1e293b !important;
    padding: 14px 20px;
    border-left: 4px solid transparent;
    transition: all 0.3s ease-out;
    font-size: 15px;
    display: flex;
    align-items: center;
    margin: 8px 12px;
    border-radius: 10px;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.sidebar .nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(251, 191, 36, 0.1), transparent);
    transition: all 0.5s ease;
}

.sidebar .nav-link i {
    margin-right: 12px;
    width: 22px;
    text-align: center;
    font-size: 16px;
    color: #1e40af !important;
    transition: all 0.3s;
}

/* Hover & active states */
.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    background-color: rgba(255,255,255,0.8);
    border-left: 4px solid #fbbf24;
    color: #1e40af !important;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.15);
    transform: translateX(5px);
}

.sidebar .nav-link:hover::before {
    left: 100%;
}

.sidebar .nav-link:hover i,
.sidebar .nav-link.active i {
    color: #fbbf24 !important;
    transform: scale(1.2);
}

/* Sidebar footer */
.sidebar-footer {
    position: absolute;
    bottom: 15px;
    width: 100%;
    text-align: center;
    color: #1e293b;
    font-size: 12px;
    padding: 10px;
    border-top: 1px solid rgba(251, 191, 36, 0.3);
    transition: all 0.3s ease;
}

/* Logout link */
.logout-item {
    margin-top: 40px;
}

.logout-item .nav-link {
    color: #ef4444;
    opacity: 0.8;
    border-top: 1px solid rgba(251, 191, 36, 0.2);
    padding-top: 20px;
}

.logout-item .nav-link:hover {
    opacity: 1;
    background-color: rgba(239, 68, 68, 0.1);
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
    opacity: 0;
    visibility: hidden;
    width: 0;
}

.sidebar.collapsed .logo-image {
    max-width: 40px;
    margin: 0 auto;
}

.sidebar.collapsed .logo-container {
    justify-content: center;
    width: 100%;
}

.sidebar.collapsed .nav-link {
    padding: 14px 0;
    justify-content: center;
    margin: 8px auto;
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
    margin-top: 30px;
}

.sidebar.collapsed .toggle-btn i {
    transform: rotate(180deg);
}

/* Main content shift */
.content-wrapper {
    margin-left: 260px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    padding: 15px;
    width: calc(100% - 260px);
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
        border-bottom: 3px solid #fbbf24;
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


<!-- JavaScript for the sidebar toggle functionality remains unchanged -->
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