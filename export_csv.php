<?php
session_start();
include('config.php');

if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Get transactions
$query = "SELECT transaction_date, transaction_type, amount, description, category, reference_number, status FROM transactions WHERE student_id = '$student_id' ORDER BY transaction_date DESC";
$result = mysqli_query($conn, $query);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add headers
fputcsv($output, ['Date', 'Type', 'Amount', 'Description', 'Category', 'Reference', 'Status']);

// Add data rows
while($row = mysqli_fetch_assoc($result)) {
    $row['transaction_date'] = date('d/m/Y H:i', strtotime($row['transaction_date']));
    $row['amount'] = ($row['transaction_type'] == 'deposit' ? '+' : '-') . '£' . number_format($row['amount'], 2);
    fputcsv($output, $row);
}

fclose($output);
exit();
?>