<?php
session_start();
include('config.php');

if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transaction_type = mysqli_real_escape_string($conn, $_POST['transaction_type']);
    $amount = floatval($_POST['amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category'] ?? 'other');
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    // Validate amount
    if($amount <= 0) {
        $_SESSION['error'] = "Amount must be greater than 0";
        header("Location: index.php");
        exit();
    }
    
    // Handle deposit
    if($transaction_type == 'deposit') {
        // Get current balance
        $balance_query = "SELECT balance FROM students WHERE student_id = '$student_id'";
        $balance_result = mysqli_query($conn, $balance_query);
        $current_balance = mysqli_fetch_assoc($balance_result)['balance'];
        
        // Calculate new balance
        $new_balance = $current_balance + $amount;
        
        // Update balance
        $update_query = "UPDATE students SET balance = '$new_balance' WHERE student_id = '$student_id'";
        
        if(mysqli_query($conn, $update_query)) {
            // Record transaction
            $reference = 'DEP' . date('Ymd') . rand(1000, 9999);
            $insert_query = "INSERT INTO transactions (student_id, transaction_type, amount, description, category, reference_number, status, notes) 
                           VALUES ('$student_id', 'deposit', '$amount', '$description', '$category', '$reference', 'completed', '$notes')";
            
            if(mysqli_query($conn, $insert_query)) {
                // Add notification
                $notif_query = "INSERT INTO notifications (student_id, title, message, type) 
                               VALUES ('$student_id', 'Deposit Successful', '£$amount deposited successfully. New balance: £$new_balance', 'success')";
                mysqli_query($conn, $notif_query);
                
                $_SESSION['success'] = "Deposit of £$amount completed successfully!";
            } else {
                $_SESSION['error'] = "Failed to record transaction. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Failed to update balance. Please try again.";
        }
    }
    
    // Handle other transaction types...
    // (withdrawal, payment, transfer code continues here)
    
    header("Location: index.php");
    exit();
}

// Add deposit limits check
function checkDepositLimits($conn, $student_id, $amount) {
    $today = date('Y-m-d');
    $month = date('Y-m-01');
    
    // Check daily limit
    $daily_query = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                   WHERE student_id = '$student_id' 
                   AND transaction_type = 'deposit' 
                   AND DATE(transaction_date) = '$today'";
    $daily_result = mysqli_query($conn, $daily_query);
    $daily_total = mysqli_fetch_assoc($daily_result)['total'];
    
    // Check monthly limit
    $monthly_query = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                     WHERE student_id = '$student_id' 
                     AND transaction_type = 'deposit' 
                     AND DATE(transaction_date) >= '$month'";
    $monthly_result = mysqli_query($conn, $monthly_query);
    $monthly_total = mysqli_fetch_assoc($monthly_result)['total'];
    
    $daily_limit = 500; // £500 per day
    $monthly_limit = 5000; // £5000 per month
    
    if($daily_total + $amount > $daily_limit) {
        return ['success' => false, 'message' => "Daily deposit limit of £$daily_limit exceeded"];
    }
    
    if($monthly_total + $amount > $monthly_limit) {
        return ['success' => false, 'message' => "Monthly deposit limit of £$monthly_limit exceeded"];
    }
    
    return ['success' => true];
}
?>