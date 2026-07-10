<?php
session_start();
include('config.php');

if(isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = md5($_POST['password']);
    
    $query = "SELECT * FROM students WHERE email = '$email' AND password = '$password' AND is_active = 1";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $student = mysqli_fetch_assoc($result);
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['student_name'] = $student['full_name'];
        
        // Update last login
        $update_query = "UPDATE students SET last_login = NOW() WHERE student_id = '{$student['student_id']}'";
        mysqli_query($conn, $update_query);
        
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Wallet</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container login-container">
        <div class="login-box">
            <img src="https://www.wlv.ac.uk/media/dam/wlv/images/logos/uni-logo.png" alt="University of Wolverhampton" class="login-logo">
            <h2><i class="fas fa-wallet"></i> Student Wallet</h2>
            <p class="login-subtitle">University of Wolverhampton</p>
            
            <?php if(isset($error)): ?>
                <div class="flash-message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="your.email@wlv.ac.uk">
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" name="login" class="submit-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="demo-credentials">
                <p><strong><i class="fas fa-info-circle"></i> Demo Credentials:</strong></p>
                <div class="demo-grid">
                    <div class="demo-item">
                        <p>Email: john.smith@wlv.ac.uk</p>
                        <p>Password: student123</p>
                    </div>
                    <div class="demo-item">
                        <p>Email: emma.j@wlv.ac.uk</p>
                        <p>Password: student123</p>
                    </div>
                </div>
                <p class="demo-note"><i class="fas fa-shield-alt"></i> Default password: <strong>student123</strong></p>
            </div>
        </div>
    </div>
</body>
</html>