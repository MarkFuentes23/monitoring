
<top class="topbar topbar-expand-lg topbar-light bg-light">
        <div class="collapse topbar-collapse" id="topbarSupportedContent">
            <ul class="topbar-top ms-auto">
                <li class="top-item dropdown">
                    <a class="top-link dropdown-toggle" href="#" id="topbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i> 
                        <?php 
                        if(isset($_SESSION['username'])) {
                            echo $_SESSION['username'];
                        } else {
                            echo "Account";
                        }
                        ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="topbarDropdown">
                        <li><a class="dropdown-item" href="#">Profile</a></li>
                        <li><a class="dropdown-item" href="#">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../login.php">Logout</a></li>
                    </ul>
                </li>
                <li class="top-item">
                    <a class="top-link" href="#"><i class="fas fa-bell"></i></a>
                </li>
            </ul>
        </div>
    </div>
</top>