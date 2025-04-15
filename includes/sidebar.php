<div class="sidebar col-md-3 col-lg-2 d-md-block">
    <div class="position-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'monitoring.php') ? 'active' : ''; ?>" href="/views/monitoring.php">
                    <i class="fas fa-users"></i> Monitoring
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="..//login.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
/* Sidebar Styles */
.sidebar {
    background-color: #2c3e50;
    min-height: 100vh;
    transition: all 0.3s;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    padding: 0;
}

.sidebar .nav-link {
    color: #ecf0f1;
    padding: 12px 20px;
    border-left: 4px solid transparent;
    transition: all 0.2s;
    font-size: 15px;
}

.sidebar .nav-link:hover {
    background-color: #34495e;
    border-left: 4px solid #1abc9c;
}

.sidebar .nav-link.active {
    background-color: #34495e;
    border-left: 4px solid #1abc9c;
    font-weight: 600;
}

.sidebar .nav-link i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

/* Responsive styles */
@media (max-width: 767.98px) {
    .sidebar {
        width: 100%;
        margin-bottom: 15px;
        min-height: auto;
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
        padding: 10px 15px;
        font-size: 14px;
    }
    
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        border-left: none;
        border-bottom: 3px solid #1abc9c;
    }
    
    .sidebar .nav-link i {
        margin-right: 5px;
    }
}
</style>