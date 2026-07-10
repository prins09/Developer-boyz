<?php
// admin_deposit.php - For staff to add funds to student accounts
session_start();
include('config.php');

// Check if admin
if(!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admin_deposit'])) {
    $student_id = intval($_POST['student_id']);
    $amount = floatval($_POST['amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes']);
    
    // Get student
    $student_query = "SELECT * FROM students WHERE student_id = '$student_id'";
    $student_result = mysqli_query($conn, $student_query);
    $student = mysqli_fetch_assoc($student_result);
    
    if(!$student) {
        $_SESSION['error'] = "Student not found!";
        header("Location: admin_deposit.php");
        exit();
    }
    
    // Process deposit
    $new_balance = $student['balance'] + $amount;
    $update_query = "UPDATE students SET balance = '$new_balance' WHERE student_id = '$student_id'";
    
    if(mysqli_query($conn, $update_query)) {
        $reference = 'ADM' . date('Ymd') . rand(1000, 9999);
        $insert_query = "INSERT INTO transactions (student_id, transaction_type, amount, description, category, reference_number, status, notes, payment_method) 
                       VALUES ('$student_id', 'deposit', '$amount', '$description', 'admin_deposit', '$reference', 'completed', '$admin_notes', 'admin')";
        
        if(mysqli_query($conn, $insert_query)) {
            // Notify student
            $notif_query = "INSERT INTO notifications (student_id, title, message, type) 
                           VALUES ('$student_id', 'Admin Deposit', '£$amount has been added to your account by admin. New balance: £$new_balance', 'success')";
            mysqli_query($conn, $notif_query);
            
            $_SESSION['success'] = "Deposit of £$amount added to student account!";
        }
    }
    header("Location: admin_deposit.php");
    exit();
}

// Search students
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$students = [];
if($search) {
    $search_query = "SELECT * FROM students WHERE full_name LIKE '%$search%' OR student_number LIKE '%$search%' OR email LIKE '%$search%' LIMIT 10";
    $search_result = mysqli_query($conn, $search_query);
    while($row = mysqli_fetch_assoc($search_result)) {
        $students[] = $row;
    }
}
?>
<!-- HTML for admin deposit page -->