<?php
// bank_transfer.php - Simulate bank transfer deposit
session_start();
include('config.php');

if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simulate_transfer'])) {
    $amount = floatval($_POST['amount']);
    $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name']);
    $account_number = mysqli_real_escape_string($conn, $_POST['account_number']);
    $reference = mysqli_real_escape_string($conn, $_POST['reference']);
    
    // Simulate bank verification (in real system, you'd integrate with a payment gateway)
    // For demo, we'll accept all transfers
    $verification_status = 'verified';
    
    if($verification_status == 'verified') {
        // Process deposit
        $balance_query = "SELECT balance FROM students WHERE student_id = '$student_id'";
        $balance_result = mysqli_query($conn, $balance_query);
        $current_balance = mysqli_fetch_assoc($balance_result)['balance'];
        $new_balance = $current_balance + $amount;
        
        $update_query = "UPDATE students SET balance = '$new_balance' WHERE student_id = '$student_id'";
        mysqli_query($conn, $update_query);
        
        $ref = 'BT' . date('Ymd') . rand(1000, 9999);
        $insert_query = "INSERT INTO transactions (student_id, transaction_type, amount, description, category, reference_number, status, notes, payment_method) 
                       VALUES ('$student_id', 'deposit', '$amount', 'Bank Transfer from $bank_name', 'deposit', '$ref', 'completed', 'Reference: $reference', 'bank_transfer')";
        mysqli_query($conn, $insert_query);
        
        $_SESSION['success'] = "Bank transfer of £$amount processed successfully!";
        header("Location: index.php");
        exit();
    }
}
?>