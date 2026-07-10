<?php
session_start();
include('config.php');

if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Handle adding recurring transaction
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_recurring'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $amount = floatval($_POST['amount']);
    $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    
    // Calculate next date
    $next_date = date('Y-m-d', strtotime($start_date));
    
    $insert_query = "INSERT INTO recurring_transactions (student_id, name, amount, frequency, type, start_date, next_date, category, description) 
                     VALUES ('$student_id', '$name', '$amount', '$frequency', '$type', '$start_date', '$next_date', '$category', '$description')";
    
    if(mysqli_query($conn, $insert_query)) {
        $_SESSION['success'] = "Recurring transaction added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add recurring transaction.";
    }
    header("Location: recurring.php");
    exit();
}

// Handle delete recurring
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_query = "DELETE FROM recurring_transactions WHERE recurring_id = $id AND student_id = '$student_id'";
    mysqli_query($conn, $delete_query);
    $_SESSION['success'] = "Recurring transaction deleted!";
    header("Location: recurring.php");
    exit();
}

// Handle pause/resume
if(isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $toggle_query = "UPDATE recurring_transactions SET status = IF(status = 'active', 'paused', 'active') WHERE recurring_id = $id AND student_id = '$student_id'";
    mysqli_query($conn, $toggle_query);
    header("Location: recurring.php");
    exit();
}

// Get all recurring transactions
$recurring_query = "SELECT * FROM recurring_transactions WHERE student_id = '$student_id' ORDER BY next_date ASC";
$recurring_result = mysqli_query($conn, $recurring_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recurring Transactions - Student Wallet</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo-container">
                    <img src="https://www.wlv.ac.uk/media/dam/wlv/images/logos/uni-logo.png" alt="University" class="logo">
                    <h1>Recurring Transactions</h1>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student'); ?></span>
                    <a href="index.php" class="logout-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </header>

        <main>
            <div class="recurring-container">
                <!-- Add Recurring Form -->
                <div class="add-recurring-section">
                    <h2><i class="fas fa-plus-circle"></i> Add Recurring Transaction</h2>
                    <form method="POST" action="" class="recurring-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" name="name" id="name" required placeholder="e.g., Monthly Rent">
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount (£)</label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="amount" required placeholder="500.00">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="frequency">Frequency</label>
                                <select name="frequency" id="frequency" required>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="biweekly">Bi-Weekly</option>
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="type">Type</label>
                                <select name="type" id="type" required>
                                    <option value="payment">Payment (Expense)</option>
                                    <option value="deposit">Deposit (Income)</option>
                                    <option value="transfer">Transfer</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select name="category" id="category" required>
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
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" name="start_date" id="start_date" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description">Description (Optional)</label>
                            <textarea name="description" id="description" rows="2" placeholder="Additional notes..."></textarea>
                        </div>
                        <button type="submit" name="add_recurring" class="submit-btn">
                            <i class="fas fa-save"></i> Add Recurring Transaction
                        </button>
                    </form>
                </div>

                <!-- Recurring List -->
                <div class="recurring-list">
                    <h2><i class="fas fa-list"></i> Your Recurring Transactions</h2>
                    <?php if(mysqli_num_rows($recurring_result) > 0): ?>
                        <div class="recurring-grid">
                            <?php while($recurring = mysqli_fetch_assoc($recurring_result)): ?>
                            <div class="recurring-card">
                                <div class="recurring-header">
                                    <div class="recurring-info">
                                        <h4><?php echo htmlspecialchars($recurring['name']); ?></h4>
                                        <span class="recurring-amount <?php echo $recurring['type'] == 'deposit' ? 'positive' : 'negative'; ?>">
                                            <?php echo $recurring['type'] == 'deposit' ? '+' : '-'; ?>£<?php echo number_format($recurring['amount'], 2); ?>
                                        </span>
                                    </div>
                                    <span class="recurring-status <?php echo $recurring['status']; ?>">
                                        <?php echo ucfirst($recurring['status']); ?>
                                    </span>
                                </div>
                                <div class="recurring-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo ucfirst($recurring['frequency']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-tag"></i>
                                        <span><?php echo ucfirst($recurring['category']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span>Next: <?php echo date('d/m/Y', strtotime($recurring['next_date'])); ?></span>
                                    </div>
                                </div>
                                <?php if($recurring['description']): ?>
                                    <div class="recurring-description">
                                        <?php echo htmlspecialchars($recurring['description']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="recurring-actions">
                                    <a href="recurring.php?toggle=<?php echo $recurring['recurring_id']; ?>" class="btn-toggle <?php echo $recurring['status']; ?>">
                                        <i class="fas fa-<?php echo $recurring['status'] == 'active' ? 'pause' : 'play'; ?>"></i>
                                        <?php echo $recurring['status'] == 'active' ? 'Pause' : 'Resume'; ?>
                                    </a>
                                    <a href="recurring.php?delete=<?php echo $recurring['recurring_id']; ?>" class="btn-delete" onclick="return confirm('Delete this recurring transaction?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No recurring transactions set up. Add your first one above!</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>