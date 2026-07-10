<?php
session_start();
include('config.php');

if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_query = "SELECT * FROM students WHERE student_id = '$student_id'";
$student_result = mysqli_query($conn, $student_query);
$student = mysqli_fetch_assoc($student_result);

// Handle deposit
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deposit'])) {
    $amount = floatval($_POST['amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category'] ?? 'deposit');
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    // Validate
    if($amount <= 0) {
        $_SESSION['error'] = "Amount must be greater than 0";
        header("Location: deposit.php");
        exit();
    }
    
    if($amount > 10000) {
        $_SESSION['error'] = "Maximum deposit amount is £10,000";
        header("Location: deposit.php");
        exit();
    }
    
    // Process deposit
    $new_balance = $student['balance'] + $amount;
    $update_query = "UPDATE students SET balance = '$new_balance' WHERE student_id = '$student_id'";
    
    if(mysqli_query($conn, $update_query)) {
        $reference = 'DEP' . date('Ymd') . rand(1000, 9999);
        $insert_query = "INSERT INTO transactions (student_id, transaction_type, amount, description, category, reference_number, status, notes, payment_method) 
                       VALUES ('$student_id', 'deposit', '$amount', '$description', '$category', '$reference', 'completed', '$notes', '$payment_method')";
        
        if(mysqli_query($conn, $insert_query)) {
            $_SESSION['success'] = "Deposit of £" . number_format($amount, 2) . " completed successfully!";
            header("Location: index.php");
            exit();
        }
    }
    
    $_SESSION['error'] = "Failed to process deposit. Please try again.";
    header("Location: deposit.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Funds - Student Wallet</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo-container">
                    <img src="https://www.wlv.ac.uk/media/dam/wlv/images/logos/uni-logo.png" alt="University" class="logo">
                    <h1>Deposit Funds</h1>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($student['full_name']); ?></span>
                    <a href="index.php" class="logout-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </header>

        <main>
            <div class="deposit-container">
                <div class="deposit-card">
                    <div class="current-balance">
                        <h3>Current Balance</h3>
                        <div class="balance-amount">£<?php echo number_format($student['balance'], 2); ?></div>
                    </div>

                    <div class="deposit-form-wrapper">
                        <h2><i class="fas fa-plus-circle"></i> Add Money to Your Wallet</h2>
                        
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="flash-message error">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="deposit-form">
                            <div class="form-group">
                                <label for="amount">Amount to Deposit (£)</label>
                                <input type="number" step="0.01" min="0.01" max="10000" name="amount" id="amount" required placeholder="0.00">
                                <small>Minimum: £0.01 | Maximum: £10,000.00</small>
                            </div>

                            <div class="form-group">
                                <label for="payment_method">Payment Method</label>
                                <select name="payment_method" id="payment_method" required>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="debit_card">Debit Card</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="cash">Cash Deposit</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="category">Category</label>
                                <select name="category" id="category">
                                    <option value="deposit">General Deposit</option>
                                    <option value="salary">Salary/Income</option>
                                    <option value="gift">Gift</option>
                                    <option value="refund">Refund</option>
                                    <option value="scholarship">Scholarship</option>
                                    <option value="student_loan">Student Loan</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <input type="text" name="description" id="description" required placeholder="e.g., Monthly allowance">
                            </div>

                            <div class="form-group">
                                <label for="notes">Notes (Optional)</label>
                                <textarea name="notes" id="notes" rows="3" placeholder="Any additional information..."></textarea>
                            </div>

                            <div class="deposit-actions">
                                <button type="submit" name="deposit" class="submit-btn">
                                    <i class="fas fa-check-circle"></i> Confirm Deposit
                                </button>
                                <a href="index.php" class="cancel-btn">Cancel</a>
                            </div>
                        </form>
                    </div>

                    <!-- Quick Deposit Options -->
                    <div class="quick-deposit">
                        <h4><i class="fas fa-bolt"></i> Quick Deposit Amounts</h4>
                        <div class="quick-amounts">
                            <button onclick="setAmount(10)" class="quick-amount-btn">£10</button>
                            <button onclick="setAmount(20)" class="quick-amount-btn">£20</button>
                            <button onclick="setAmount(50)" class="quick-amount-btn">£50</button>
                            <button onclick="setAmount(100)" class="quick-amount-btn">£100</button>
                            <button onclick="setAmount(200)" class="quick-amount-btn">£200</button>
                            <button onclick="setAmount(500)" class="quick-amount-btn">£500</button>
                        </div>
                    </div>
                </div>

                <!-- Recent Deposits -->
                <div class="recent-deposits">
                    <h3><i class="fas fa-history"></i> Recent Deposits</h3>
                    <?php
                    $deposit_query = "SELECT * FROM transactions WHERE student_id = '$student_id' AND transaction_type = 'deposit' ORDER BY transaction_date DESC LIMIT 5";
                    $deposit_result = mysqli_query($conn, $deposit_query);
                    ?>
                    <?php if(mysqli_num_rows($deposit_result) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Description</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($deposit_result)): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['transaction_date'])); ?></td>
                                        <td class="positive">+£<?php echo number_format($row['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td><?php echo ucfirst($row['payment_method'] ?? 'N/A'); ?></td>
                                        <td><span class="status-badge <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No deposits yet. Make your first deposit!</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    function setAmount(amount) {
        document.getElementById('amount').value = amount;
        document.getElementById('amount').focus();
    }
    </script>
</body>
</html>