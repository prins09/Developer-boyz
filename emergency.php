<?php
session_start();
include('config.php');

if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Handle adding to emergency fund
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_funds'])) {
    $amount = floatval($_POST['amount']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    // Check if emergency fund exists
    $check_query = "SELECT * FROM emergency_funds WHERE student_id = '$student_id'";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $update_query = "UPDATE emergency_funds SET current_amount = current_amount + $amount, last_updated = NOW() WHERE student_id = '$student_id'";
        mysqli_query($conn, $update_query);
    } else {
        // Create emergency fund with target of £500 by default
        $insert_query = "INSERT INTO emergency_funds (student_id, target_amount, current_amount) VALUES ('$student_id', 500, $amount)";
        mysqli_query($conn, $insert_query);
    }
    
    // Record transaction
    $ref = 'EMERG' . date('Ymd') . rand(1000, 9999);
    $trans_query = "INSERT INTO transactions (student_id, transaction_type, amount, description, reference_number, status, category) 
                    VALUES ('$student_id', 'withdrawal', '$amount', 'Emergency Fund Contribution', '$ref', 'completed', 'savings')";
    mysqli_query($conn, $trans_query);
    
    $_SESSION['success'] = "Added £$amount to your emergency fund!";
    header("Location: emergency.php");
    exit();
}

// Handle withdrawal from emergency fund
if(isset($_GET['withdraw'])) {
    $amount = floatval($_GET['amount'] ?? 0);
    
    if($amount > 0) {
        $query = "UPDATE emergency_funds SET current_amount = current_amount - $amount, last_updated = NOW() WHERE student_id = '$student_id' AND current_amount >= $amount";
        if(mysqli_query($conn, $query) && mysqli_affected_rows($conn) > 0) {
            // Record transaction
            $ref = 'EMERG' . date('Ymd') . rand(1000, 9999);
            $trans_query = "INSERT INTO transactions (student_id, transaction_type, amount, description, reference_number, status, category) 
                            VALUES ('$student_id', 'deposit', '$amount', 'Emergency Fund Withdrawal', '$ref', 'completed', 'savings')";
            mysqli_query($conn, $trans_query);
            
            $_SESSION['success'] = "Withdrew £$amount from your emergency fund!";
        } else {
            $_SESSION['error'] = "Insufficient funds in emergency fund!";
        }
    }
    header("Location: emergency.php");
    exit();
}

// Get emergency fund data
$emergency_query = "SELECT * FROM emergency_funds WHERE student_id = '$student_id'";
$emergency_result = mysqli_query($conn, $emergency_query);
$emergency = mysqli_fetch_assoc($emergency_result);

// Get contribution history
$history_query = "SELECT * FROM transactions WHERE student_id = '$student_id' AND category = 'savings' AND description LIKE '%Emergency Fund%' ORDER BY transaction_date DESC LIMIT 10";
$history_result = mysqli_query($conn, $history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Fund - Student Wallet</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo-container">
                    <img src="https://www.wlv.ac.uk/media/dam/wlv/images/logos/uni-logo.png" alt="University" class="logo">
                    <h1>Emergency Fund</h1>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student'); ?></span>
                    <a href="index.php" class="logout-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </header>

        <main>
            <div class="emergency-container">
                <?php if($emergency): ?>
                    <!-- Emergency Fund Overview -->
                    <div class="emergency-overview">
                        <div class="emergency-card-large">
                            <div class="emergency-header">
                                <h2><i class="fas fa-shield-alt"></i> Your Emergency Fund</h2>
                                <span class="emergency-status <?php echo $emergency['current_amount'] >= $emergency['target_amount'] ? 'achieved' : 'active'; ?>">
                                    <?php echo $emergency['current_amount'] >= $emergency['target_amount'] ? '🎉 Fully Funded!' : 'Building...'; ?>
                                </span>
                            </div>
                            <div class="emergency-balance">
                                <div class="balance-amount">£<?php echo number_format($emergency['current_amount'], 2); ?></div>
                                <div class="balance-target">Target: £<?php echo number_format($emergency['target_amount'], 2); ?></div>
                            </div>
                            <?php $progress = ($emergency['current_amount'] / $emergency['target_amount']) * 100; ?>
                            <div class="progress-bar-large">
                                <div class="progress-fill" style="width: <?php echo min($progress, 100); ?>%;"></div>
                            </div>
                            <div class="progress-text"><?php echo number_format($progress, 1); ?>% Complete</div>
                            <div class="emergency-actions">
                                <button onclick="showAddFundsModal()" class="action-btn deposit-btn">
                                    <i class="fas fa-plus"></i> Add Funds
                                </button>
                                <?php if($emergency['current_amount'] >= 10): ?>
                                    <button onclick="showWithdrawModal()" class="action-btn withdraw-btn">
                                        <i class="fas fa-hand-holding-usd"></i> Withdraw
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="quick-stats">
                        <div class="stat-card">
                            <i class="fas fa-calendar-alt"></i>
                            <h3><?php 
                                $days_ago = floor((time() - strtotime($emergency['last_updated'])) / (60 * 60 * 24));
                                echo $days_ago;
                            ?></h3>
                            <p>Days Since Last Update</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-percent"></i>
                            <h3><?php echo number_format($progress, 1); ?>%</h3>
                            <p>Progress</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-clock"></i>
                            <h3>
                                <?php 
                                $remaining = $emergency['target_amount'] - $emergency['current_amount'];
                                if($remaining > 0) {
                                    // Estimate based on average contribution (if any)
                                    $avg_query = "SELECT AVG(amount) as avg_amount FROM transactions WHERE student_id = '$student_id' AND category = 'savings' AND description LIKE '%Emergency Fund%'";
                                    $avg_result = mysqli_query($conn, $avg_query);
                                    $avg = mysqli_fetch_assoc($avg_result);
                                    if($avg['avg_amount'] > 0) {
                                        $weeks = ceil($remaining / ($avg['avg_amount']));
                                        echo $weeks . 'w';
                                    } else {
                                        echo 'N/A';
                                    }
                                } else {
                                    echo '✓';
                                }
                                ?>
                            </h3>
                            <p>Time to Goal</p>
                        </div>
                    </div>

                    <!-- Contribution History -->
                    <div class="contribution-history">
                        <h3><i class="fas fa-history"></i> Recent Contributions</h3>
                        <?php if(mysqli_num_rows($history_result) > 0): ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_assoc($history_result)): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($row['transaction_date'])); ?></td>
                                            <td><span class="type-badge <?php echo $row['transaction_type']; ?>">
                                                <?php echo ucfirst($row['transaction_type']); ?>
                                            </span></td>
                                            <td class="<?php echo $row['transaction_type'] == 'deposit' ? 'positive' : 'negative'; ?>">
                                                <?php echo $row['transaction_type'] == 'deposit' ? '+' : '-'; ?>£<?php echo number_format($row['amount'], 2); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No contributions yet. Start building your emergency fund today!</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- No Emergency Fund -->
                    <div class="emergency-setup">
                        <div class="setup-card">
                            <i class="fas fa-shield-alt fa-3x"></i>
                            <h2>Start Your Emergency Fund</h2>
                            <p>An emergency fund helps you handle unexpected expenses. Start building your safety net today.</p>
                            <form method="POST" action="" class="setup-form">
                                <div class="form-group">
                                    <label for="amount">Initial Deposit (£)</label>
                                    <input type="number" step="0.01" min="1" name="amount" id="amount" placeholder="50.00" required>
                                </div>
                                <button type="submit" name="add_funds" class="submit-btn">
                                    <i class="fas fa-plus"></i> Start Emergency Fund
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Funds Modal -->
    <div id="addFundsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddFundsModal()">&times;</span>
            <h2><i class="fas fa-plus-circle"></i> Add to Emergency Fund</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="amount">Amount (£)</label>
                    <input type="number" step="0.01" min="1" name="amount" id="amount_modal" required placeholder="25.00">
                </div>
                <div class="form-group">
                    <label for="notes">Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="2" placeholder="e.g., Monthly contribution"></textarea>
                </div>
                <button type="submit" name="add_funds" class="submit-btn">
                    <i class="fas fa-check"></i> Add Funds
                </button>
            </form>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div id="withdrawModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeWithdrawModal()">&times;</span>
            <h2><i class="fas fa-hand-holding-usd"></i> Withdraw from Emergency Fund</h2>
            <p class="warning-text">⚠️ Only withdraw from your emergency fund for genuine emergencies.</p>
            <form method="GET" action="">
                <input type="hidden" name="withdraw" value="1">
                <div class="form-group">
                    <label for="withdraw_amount">Amount to Withdraw (£)</label>
                    <input type="number" step="0.01" min="1" name="amount" id="withdraw_amount" required 
                           max="<?php echo $emergency['current_amount'] ?? 0; ?>">
                    <small>Available: £<?php echo number_format($emergency['current_amount'] ?? 0, 2); ?></small>
                </div>
                <button type="submit" class="submit-btn withdraw-btn">
                    <i class="fas fa-hand-holding-usd"></i> Withdraw
                </button>
            </form>
        </div>
    </div>

    <script>
    function showAddFundsModal() {
        document.getElementById('addFundsModal').style.display = 'block';
    }
    
    function closeAddFundsModal() {
        document.getElementById('addFundsModal').style.display = 'none';
    }
    
    function showWithdrawModal() {
        document.getElementById('withdrawModal').style.display = 'block';
    }
    
    function closeWithdrawModal() {
        document.getElementById('withdrawModal').style.display = 'none';
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const addModal = document.getElementById('addFundsModal');
        const withdrawModal = document.getElementById('withdrawModal');
        if (event.target == addModal) {
            closeAddFundsModal();
        }
        if (event.target == withdrawModal) {
            closeWithdrawModal();
        }
    }
    </script>
</body>
</html>