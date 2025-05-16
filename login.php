<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildNet - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/poppins.css">
</head>
<body>
    <div class="login-container">
        <div class="form-container">
            <div class="auth-form">
                <!-- Yellow accent dots -->
                <div class="yellow-dot dot-1"></div>
                <div class="yellow-dot dot-2"></div>
                <div class="yellow-dot dot-3"></div>
                
                <div class="logo-container">
                    <img src="logo.png" alt="BuildNet Logo" class="logo-image">
                </div>

                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <form action="backend/process.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                        <i class="fas fa-user form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <i class="fas fa-lock form-icon"></i>
                    </div>
                    
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn-primary">Sign In</button>
                    
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Add animation to form elements when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const formElements = document.querySelectorAll('.form-group, .remember-me, .btn-primary, .form-footer');
            
            formElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, 300 + (index * 100));
            });
            
            // Add focus animation
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
        });
    </script>
</body>
</html>