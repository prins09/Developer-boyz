<?php
session_start();
include('config.php');

if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_SESSION['student_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate
    if(strlen($new_password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long";
        header("Location: index.php");
        exit();
    }
    
    if($new_password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: index.php");
        exit();
    }
    
    // Verify current password
    $query = "SELECT password FROM students WHERE student_id = '$student_id'";
    $result = mysqli_query($conn, $query);
    $student = mysqli_fetch_assoc($result);
    
    if(md5($current_password) != $student['password']) {
        $_SESSION['error'] = "Current password is incorrect";
        header("Location: index.php");
        exit();
    }
    
    // Update password
    $new_password_hash = md5($new_password);
    $update_query = "UPDATE students SET password = '$new_password_hash', default_password = 0 WHERE student_id = '$student_id'";
    
    if(mysqli_query($conn, $update_query)) {
        $_SESSION['success'] = "Password changed successfully! Please login again.";
        session_destroy();
        header("Location: login.php");
    } else {
        $_SESSION['error'] = "Failed to change password. Please try again.";
        header("Location: index.php");
    }
} else {
    header("Location: index.php");
}
?>