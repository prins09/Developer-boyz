<?php
session_start();
include('config.php');

// Check if user is logged in
if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$query = "SELECT * FROM students WHERE student_id = '$student_id'";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);

// Check if using default password
$using_default = $student['default_password'] == 1;

// Get transaction history
$trans_query = "SELECT * FROM transactions WHERE student_id = '$student_id' ORDER BY transaction_date DESC LIMIT 10";
$trans_result = mysqli_query($conn, $trans_query);

// Get savings goals
$goals_query = "SELECT * FROM savings_goals WHERE student_id = '$student_id' AND status = 'active'";
$goals_result = mysqli_query($conn, $goals_query);

// Get unread notifications
$notif_query = "SELECT * FROM notifications WHERE student_id = '$student_id' AND is_read = 0 ORDER BY created_at DESC LIMIT 5";
$notif_result = mysqli_query($conn, $notif_query);
$unread_count = mysqli_num_rows($notif_result);

// Get budget summary
$budget_query = "SELECT * FROM budget_categories WHERE student_id = '$student_id' AND month = MONTH(CURRENT_DATE) AND year = YEAR(CURRENT_DATE)";
$budget_result = mysqli_query($conn, $budget_query);

// Get daily spending
$today = date('Y-m-d');
$daily_spend_query = "SELECT SUM(amount) as total FROM transactions WHERE student_id = '$student_id' AND transaction_type IN ('withdrawal', 'payment', 'transfer') AND DATE(transaction_date) = '$today'";
$daily_spend_result = mysqli_query($conn, $daily_spend_query);
$daily_spend = mysqli_fetch_assoc($daily_spend_result)['total'] ?? 0;

// Get monthly spending by category for chart
$category_spending_query = "SELECT category, SUM(amount) as total FROM transactions WHERE student_id = '$student_id' AND transaction_type IN ('withdrawal', 'payment') AND MONTH(transaction_date) = MONTH(CURRENT_DATE) AND YEAR(transaction_date) = YEAR(CURRENT_DATE) GROUP BY category";
$category_spending_result = mysqli_query($conn, $category_spending_query);
$category_data = [];
while($row = mysqli_fetch_assoc($category_spending_result)) {
    $category_data[] = $row;
}

// Get recurring transactions
$recurring_query = "SELECT * FROM recurring_transactions WHERE student_id = '$student_id' AND status = 'active'";
$recurring_result = mysqli_query($conn, $recurring_query);

// Get emergency fund
$emergency_query = "SELECT * FROM emergency_funds WHERE student_id = '$student_id'";
$emergency_result = mysqli_query($conn, $emergency_query);
$emergency = mysqli_fetch_assoc($emergency_result);

// Handle flash messages
$success_msg = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_msg = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Wallet - University of Wolverhampton</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo-container">
                    <img src="https://www.wlv.ac.uk/media/dam/wlv/images/logos/uni-logo.png" alt="University of Wolverhampton" class="logo">
                    <h1>Student Wallet</h1>
                </div>
                <div class="user-info">
                    <?php if($using_default): ?>
                        <span class="default-password-badge">
                            <i class="fas fa-exclamation-triangle"></i> Default Password
                        </span>
                    <?php endif; ?>
                    <div class="notification-bell" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <?php if($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </div>
                    <span>Welcome, <?php echo htmlspecialchars($student['full_name']); ?></span>
                    <a href="profile.php" class="profile-btn">
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php if($success_msg): ?>
            <div class="flash-message success">
                <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                <span class="close-flash" onclick="this.parentElement.remove()">&times;</span>
            </div>
        <?php endif; ?>
        <?php if($error_msg): ?>
            <div class="flash-message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                <span class="close-flash" onclick="this.parentElement.remove()">&times;</span>
            </div>
        <?php endif; ?>

        <!-- Notifications Dropdown -->
        <div id="notificationDropdown" class="notification-dropdown" style="display:none;">
            <div class="notification-header">
                <h4>Notifications</h4>
                <a href="mark_all_read.php">Mark all as read</a>
            </div>
            <div class="notification-list">
                <?php if(mysqli_num_rows($notif_result) > 0): ?>
                    <?php while($notif = mysqli_fetch_assoc($notif_result)): ?>
                        <div class="notification-item <?php echo $notif['type']; ?>">
                            <div class="notification-icon">
                                <i class="fas fa-<?php echo $notif['type'] == 'success' ? 'check-circle' : ($notif['type'] == 'warning' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                            </div>
                            <div class="notification-content">
                                <h5><?php echo htmlspecialchars($notif['title']); ?></h5>
                                <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                <small><?php echo time_elapsed_string($notif['created_at']); ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-notifications">No new notifications</p>
                <?php endif; ?>
            </div>
        </div>

        <main>
            <div class="dashboard-grid">
                <!-- Balance Card -->
                <div class="wallet-balance">
                    <div class="balance-card">
                        <h2><i class="fas fa-wallet"></i> Current Balance</h2>
                        <div class="balance-amount">£<?php echo number_format($student['balance'], 2); ?></div>
                        <div class="student-info">
                            <p><i class="fas fa-id-card"></i> <strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_number']); ?></p>
                            <p><i class="fas fa-graduation-cap"></i> <strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?></p>
                            <p><i class="fas fa-calendar-alt"></i> <strong>Year:</strong> <?php echo $student['year_of_study']; ?></p>
                        </div>
                        
                        <!-- Daily Spending Limit -->
                        <div class="daily-spending">
                            <div class="daily-spending-header">
                                <i class="fas fa-clock"></i>
                                <span>Today's Spending</span>
                                <span class="daily-amount">£<?php echo number_format($daily_spend, 2); ?></span>
                            </div>
                            <?php 
                            $daily_limit = $student['daily_spending_limit'] ?? 50;
                            $percentage = min(($daily_spend / $daily_limit) * 100, 100);
                            ?>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $percentage > 90 ? 'danger' : ($percentage > 70 ? 'warning' : ''); ?>" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                            <div class="daily-limit-text">Limit: £<?php echo number_format($daily_limit, 2); ?></div>
                        </div>
                        
                        <?php if($using_default): ?>
                            <div class="default-password-warning">
                                <i class="fas fa-lock"></i>
                                <span>Please change your default password for security</span>
                                <a href="change_password.php" class="change-password-btn">Change Now</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="quick-stats">
                    <div class="stat-card">
                        <i class="fas fa-exchange-alt"></i>
                        <h3><?php 
                            $count_query = "SELECT COUNT(*) as count FROM transactions WHERE student_id = '$student_id'";
                            $count_result = mysqli_query($conn, $count_query);
                            $count = mysqli_fetch_assoc($count_result);
                            echo $count['count'];
                        ?></h3>
                        <p>Total Transactions</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-piggy-bank"></i>
                        <h3><?php 
                            $goal_count = mysqli_num_rows($goals_result);
                            echo $goal_count;
                        ?></h3>
                        <p>Active Savings Goals</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-chart-line"></i>
                        <h3><?php 
                            $monthly_query = "SELECT SUM(amount) as total FROM transactions WHERE student_id = '$student_id' AND transaction_type = 'deposit' AND MONTH(transaction_date) = MONTH(CURRENT_DATE) AND YEAR(transaction_date) = YEAR(CURRENT_DATE)";
                            $monthly_result = mysqli_query($conn, $monthly_query);
                            $monthly = mysqli_fetch_assoc($monthly_result);
                            echo '£' . number_format($monthly['total'] ?? 0, 2);
                        ?></h3>
                        <p>This Month's Income</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3><?php 
                            $alert_query = "SELECT COUNT(*) as count FROM notifications WHERE student_id = '$student_id' AND is_read = 0 AND type = 'warning'";
                            $alert_result = mysqli_query($conn, $alert_query);
                            $alert = mysqli_fetch_assoc($alert_result);
                            echo $alert['count'];
                        ?></h3>
                        <p>Unread Alerts</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button onclick="showModal('deposit')" class="action-btn deposit-btn">
                    <i class="fas fa-plus-circle"></i> Deposit
                </button>
                <button onclick="showModal('withdraw')" class="action-btn withdraw-btn">
                    <i class="fas fa-minus-circle"></i> Withdraw
                </button>
                <button onclick="showModal('payment')" class="action-btn payment-btn">
                    <i class="fas fa-credit-card"></i> Make Payment
                </button>
                <button onclick="showModal('transfer')" class="action-btn transfer-btn">
                    <i class="fas fa-exchange-alt"></i> Transfer
                </button>
                <button onclick="location.href='savings.php'" class="action-btn savings-btn">
                    <i class="fas fa-piggy-bank"></i> Savings Goals
                </button>
                <button onclick="location.href='reports.php'" class="action-btn reports-btn">
                    <i class="fas fa-file-alt"></i> Reports
                </button>
                <button onclick="location.href='budget.php'" class="action-btn budget-btn">
                    <i class="fas fa-chart-pie"></i> Budget
                </button>
                <button onclick="location.href='recurring.php'" class="action-btn recurring-btn">
                    <i class="fas fa-sync"></i> Recurring
                </button>
                <button onclick="location.href='emergency.php'" class="action-btn emergency-btn">
                    <i class="fas fa-shield-alt"></i> Emergency Fund
                </button>
            </div>

            <!-- Spending Chart -->
            <div class="chart-container">
                <h3><i class="fas fa-chart-pie"></i> Monthly Spending by Category</h3>
                <div class="chart-wrapper">
                    <canvas id="spendingChart"></canvas>
                </div>
            </div>

            <!-- Recurring Transactions -->
            <?php if(mysqli_num_rows($recurring_result) > 0): ?>
            <div class="recurring-section">
                <h3><i class="fas fa-sync"></i> Active Recurring Transactions</h3>
                <div class="recurring-grid">
                    <?php while($recurring = mysqli_fetch_assoc($recurring_result)): ?>
                    <div class="recurring-card">
                        <div class="recurring-header">
                            <span class="recurring-name"><?php echo htmlspecialchars($recurring['name']); ?></span>
                            <span class="recurring-amount">£<?php echo number_format($recurring['amount'], 2); ?></span>
                        </div>
                        <div class="recurring-details">
                            <span><i class="fas fa-calendar"></i> <?php echo ucfirst($recurring['frequency']); ?></span>
                            <span><i class="fas fa-tag"></i> <?php echo ucfirst($recurring['type']); ?></span>
                        </div>
                        <div class="recurring-next">Next: <?php echo date('d/m/Y', strtotime($recurring['next_date'])); ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Emergency Fund -->
            <?php if($emergency): ?>
            <div class="emergency-section">
                <div class="emergency-card">
                    <div class="emergency-header">
                        <h3><i class="fas fa-shield-alt"></i> Emergency Fund</h3>
                        <span class="emergency-status <?php echo $emergency['current_amount'] >= $emergency['target_amount'] ? 'achieved' : 'active'; ?>">
                            <?php echo $emergency['current_amount'] >= $emergency['target_amount'] ? '🎉 Achieved!' : 'In Progress'; ?>
                        </span>
                    </div>
                    <div class="emergency-progress">
                        <div class="emergency-amounts">
                            <span>Saved: £<?php echo number_format($emergency['current_amount'], 2); ?></span>
                            <span>Target: £<?php echo number_format($emergency['target_amount'], 2); ?></span>
                        </div>
                        <?php $emergency_progress = ($emergency['current_amount'] / $emergency['target_amount']) * 100; ?>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min($emergency_progress, 100); ?>%;"></div>
                        </div>
                        <div class="emergency-actions">
                            <a href="add_emergency.php" class="btn-add-funds"><i class="fas fa-plus"></i> Add Funds</a>
                            <?php if($emergency['current_amount'] >= $emergency['target_amount']): ?>
                                <a href="withdraw_emergency.php" class="btn-complete"><i class="fas fa-hand-holding-usd"></i> Withdraw</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Savings Goals Preview -->
            <?php if(mysqli_num_rows($goals_result) > 0): ?>
            <div class="savings-preview">
                <h3><i class="fas fa-piggy-bank"></i> Your Savings Goals</h3>
                <div class="goals-grid">
                    <?php while($goal = mysqli_fetch_assoc($goals_result)): 
                        $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                    ?>
                    <div class="goal-card">
                        <div class="goal-header">
                            <h4><?php echo htmlspecialchars($goal['goal_name']); ?></h4>
                            <span class="goal-amount">£<?php echo number_format($goal['current_amount'], 2); ?> / £<?php echo number_format($goal['target_amount'], 2); ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min($progress, 100); ?>%;"></div>
                        </div>
                        <div class="goal-deadline">
                            <i class="fas fa-calendar-alt"></i> Deadline: <?php echo date('d/m/Y', strtotime($goal['deadline'])); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Budget Overview -->
            <?php if(mysqli_num_rows($budget_result) > 0): ?>
            <div class="budget-overview">
                <h3><i class="fas fa-chart-pie"></i> Monthly Budget</h3>
                <div class="budget-grid">
                    <?php while($budget = mysqli_fetch_assoc($budget_result)): 
                        $spent_percent = ($budget['spent_amount'] / $budget['monthly_budget']) * 100;
                    ?>
                    <div class="budget-item">
                        <div class="budget-info">
                            <span class="budget-name"><?php echo htmlspecialchars($budget['category_name']); ?></span>
                            <span class="budget-amount">£<?php echo number_format($budget['spent_amount'], 2); ?> / £<?php echo number_format($budget['monthly_budget'], 2); ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo $spent_percent > 90 ? 'danger' : ($spent_percent > 70 ? 'warning' : ''); ?>" style="width: <?php echo min($spent_percent, 100); ?>%;"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Transaction History -->
            <div class="transaction-history">
                <div class="history-header">
                    <h3><i class="fas fa-history"></i> Recent Transactions</h3>
                    <div class="history-actions">
                        <a href="transactions.php" class="view-all-btn">View All</a>
                        <a href="export_csv.php" class="export-btn"><i class="fas fa-file-csv"></i> Export</a>
                    </div>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Reference</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($trans_result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($trans_result)): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['transaction_date'])); ?></td>
                                    <td><span class="type-badge <?php echo $row['transaction_type']; ?>">
                                        <i class="fas fa-<?php echo $row['transaction_type'] == 'deposit' ? 'arrow-down' : ($row['transaction_type'] == 'withdrawal' ? 'arrow-up' : ($row['transaction_type'] == 'transfer' ? 'exchange-alt' : 'credit-card')); ?>"></i>
                                        <?php echo ucfirst($row['transaction_type']); ?>
                                    </span></td>
                                    <td class="<?php echo in_array($row['transaction_type'], ['deposit', 'refund']) ? 'positive' : 'negative'; ?>">
                                        <?php echo in_array($row['transaction_type'], ['deposit', 'refund']) ? '+' : '-'; ?>£<?php echo number_format($row['amount'], 2); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category'] ?? 'Uncategorized'); ?></td>
                                    <td><small><?php echo htmlspecialchars($row['reference_number']); ?></small></td>
                                    <td><span class="status-badge <?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">No transactions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- Transaction Modal -->
        <div id="transactionModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2 id="modalTitle">Transaction</h2>
                <form id="transactionForm" action="process_transaction.php" method="POST">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    <input type="hidden" name="transaction_type" id="transactionType">
                    
                    <div class="form-group">
                        <label for="amount">Amount (£)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" name="description" id="description" placeholder="Enter description" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category">
                            <option value="food">Food & Dining</option>
                            <option value="transport">Transport</option>
                            <option value="shopping">Shopping</option>
                            <option value="entertainment">Entertainment</option>
                            <option value="education">Education</option>
                            <option value="housing">Housing</option>
                            <option value="utilities">Utilities</option>
                            <option value="health">Health</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group" id="transferStudentGroup" style="display:none;">
                        <label for="recipient_email">Recipient Email</label>
                        <input type="email" name="recipient_email" id="recipient_email" placeholder="student@wlv.ac.uk">
                    </div>

                    <div id="cardDetails" style="display:none;">
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" name="card_number" placeholder="1234 5678 9012 3456" pattern="[0-9]{16}" maxlength="16">
                        </div>
                        <div class="form-group">
                            <label for="card_holder">Card Holder Name</label>
                            <input type="text" name="card_holder" placeholder="John Doe">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry">Expiry Date</label>
                                <input type="month" name="expiry">
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="password" name="cvv" placeholder="***" maxlength="4">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea name="notes" id="notes" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-check-circle"></i> Process Transaction
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password Modal (if default password) -->
        <?php if($using_default): ?>
        <div id="changePasswordModal" class="modal" style="display:block;">
            <div class="modal-content">
                <h2><i class="fas fa-lock"></i> Change Default Password</h2>
                <p class="warning-text">For security reasons, please change your default password immediately.</p>
                <form action="change_password.php" method="POST">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" name="current_password" id="current_password" value="student123" readonly>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" id="new_password" required minlength="8">
                        <small>Password must be at least 8 characters long</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required>
                    </div>
                    <button type="submit" class="submit-btn">Change Password</button>
                    <button type="button" onclick="document.getElementById('changePasswordModal').style.display='none'" class="skip-btn">Skip for now</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <footer>
            <p>&copy; 2024 University of Wolverhampton. All rights reserved. | <a href="privacy.php">Privacy Policy</a></p>
        </footer>
    </div>

    <script>
    // Chart.js for spending visualization
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('spendingChart');
        if (ctx) {
            const categories = <?php echo json_encode(array_column($category_data, 'category')); ?>;
            const amounts = <?php echo json_encode(array_column($category_data, 'total')); ?>;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: categories.length ? categories : ['No Data'],
                    datasets: [{
                        data: amounts.length ? amounts : [1],
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    });

    // Toggle notifications
    function toggleNotifications() {
        const dropdown = document.getElementById('notificationDropdown');
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }

    // Click outside to close notifications
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('notificationDropdown');
        const bell = document.querySelector('.notification-bell');
        if (dropdown && !dropdown.contains(event.target) && !bell.contains(event.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Modal functions
    function showModal(type) {
        const modal = document.getElementById('transactionModal');
        const title = document.getElementById('modalTitle');
        const transactionType = document.getElementById('transactionType');
        const cardDetails = document.getElementById('cardDetails');
        const transferGroup = document.getElementById('transferStudentGroup');
        
        modal.style.display = 'block';
        transactionType.value = type;
        
        // Hide all conditional fields
        cardDetails.style.display = 'none';
        transferGroup.style.display = 'none';
        
        switch(type) {
            case 'deposit':
                title.textContent = '💰 Deposit Funds';
                cardDetails.style.display = 'block';
                document.getElementById('description').placeholder = 'e.g., Bank transfer, Cash deposit';
                break;
            case 'withdraw':
                title.textContent = '🏦 Withdraw Funds';
                cardDetails.style.display = 'block';
                document.getElementById('description').placeholder = 'e.g., ATM withdrawal';
                break;
            case 'payment':
                title.textContent = '💳 Make Payment';
                cardDetails.style.display = 'block';
                document.getElementById('description').placeholder = 'e.g., Tuition fee, Library fine';
                break;
            case 'transfer':
                title.textContent = '🔄 Transfer Funds';
                transferGroup.style.display = 'block';
                document.getElementById('description').placeholder = 'e.g., Transfer to friend';
                break;
        }
    }

    function closeModal() {
        document.getElementById('transactionModal').style.display = 'none';
        document.getElementById('transactionForm').reset();
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('transactionModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    // Auto-hide flash messages
    document.addEventListener('DOMContentLoaded', function() {
        const messages = document.querySelectorAll('.flash-message');
        messages.forEach(function(msg) {
            setTimeout(function() {
                msg.style.display = 'none';
            }, 5000);
        });
    });
    </script>
</body>
</html>

<?php
// Helper function for time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    
    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>