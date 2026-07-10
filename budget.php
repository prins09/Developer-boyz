<?php
session_start();
include('config.php');

if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Handle adding budget category
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_budget'])) {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $monthly_budget = floatval($_POST['monthly_budget']);
    $month = date('n');
    $year = date('Y');
    
    $check_query = "SELECT * FROM budget_categories WHERE student_id = '$student_id' AND category_name = '$category_name' AND month = $month AND year = $year";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $update_query = "UPDATE budget_categories SET monthly_budget = '$monthly_budget' WHERE student_id = '$student_id' AND category_name = '$category_name' AND month = $month AND year = $year";
        mysqli_query($conn, $update_query);
        $_SESSION['success'] = "Budget updated successfully!";
    } else {
        $insert_query = "INSERT INTO budget_categories (student_id, category_name, monthly_budget, month, year) VALUES ('$student_id', '$category_name', '$monthly_budget', $month, $year)";
        mysqli_query($conn, $insert_query);
        $_SESSION['success'] = "Budget category added successfully!";
    }
    header("Location: budget.php");
    exit();
}

// Handle delete budget
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_query = "DELETE FROM budget_categories WHERE budget_id = $id AND student_id = '$student_id'";
    mysqli_query($conn, $delete_query);
    $_SESSION['success'] = "Budget category deleted!";
    header("Location: budget.php");
    exit();
}

// Get all budget categories
$budget_query = "SELECT * FROM budget_categories WHERE student_id = '$student_id' AND month = MONTH(CURRENT_DATE) AND year = YEAR(CURRENT_DATE)";
$budget_result = mysqli_query($conn, $budget_query);

// Calculate totals
$total_budget = 0;
$total_spent = 0;
$budgets = [];
while($row = mysqli_fetch_assoc($budget_result)) {
    $budgets[] = $row;
    $total_budget += $row['monthly_budget'];
    $total_spent += $row['spent_amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planner - Student Wallet</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo-container">
                    <img src="https://www.wlv.ac.uk/media/dam/wlv/images/logos/uni-logo.png" alt="University" class="logo">
                    <h1>Budget Planner</h1>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student'); ?></span>
                    <a href="index.php" class="logout-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </header>

        <main>
            <div class="budget-container">
                <!-- Budget Summary -->
                <div class="budget-summary">
                    <div class="summary-card">
                        <h3>Monthly Budget</h3>
                        <div class="summary-amount">£<?php echo number_format($total_budget, 2); ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Total Spent</h3>
                        <div class="summary-amount">£<?php echo number_format($total_spent, 2); ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Remaining</h3>
                        <div class="summary-amount <?php echo ($total_budget - $total_spent) >= 0 ? 'positive' : 'negative'; ?>">
                            £<?php echo number_format($total_budget - $total_spent, 2); ?>
                        </div>
                    </div>
                </div>

                <!-- Add Budget Form -->
                <div class="add-budget-section">
                    <h2><i class="fas fa-plus-circle"></i> Add Budget Category</h2>
                    <form method="POST" action="" class="budget-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="category_name">Category</label>
                                <select name="category_name" id="category_name" required>
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
                                <label for="monthly_budget">Monthly Budget (£)</label>
                                <input type="number" step="0.01" min="0" name="monthly_budget" id="monthly_budget" required placeholder="100.00">
                            </div>
                        </div>
                        <button type="submit" name="add_budget" class="submit-btn">
                            <i class="fas fa-save"></i> Set Budget
                        </button>
                    </form>
                </div>

                <!-- Budget List -->
                <div class="budget-list">
                    <h2><i class="fas fa-list"></i> Your Budget Categories</h2>
                    <?php if(count($budgets) > 0): ?>
                        <?php foreach($budgets as $budget): 
                            $percentage = ($budget['spent_amount'] / $budget['monthly_budget']) * 100;
                        ?>
                        <div class="budget-item-card">
                            <div class="budget-item-header">
                                <div class="budget-item-info">
                                    <h4><i class="fas fa-tag"></i> <?php echo ucfirst($budget['category_name']); ?></h4>
                                    <span class="budget-amounts">
                                        Spent: £<?php echo number_format($budget['spent_amount'], 2); ?> / £<?php echo number_format($budget['monthly_budget'], 2); ?>
                                    </span>
                                </div>
                                <div class="budget-item-actions">
                                    <span class="budget-status <?php echo $percentage > 90 ? 'danger' : ($percentage > 70 ? 'warning' : 'success'); ?>">
                                        <?php echo number_format($percentage, 1); ?>% used
                                    </span>
                                    <a href="budget.php?delete=<?php echo $budget['budget_id']; ?>" class="delete-btn" onclick="return confirm('Delete this budget category?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $percentage > 90 ? 'danger' : ($percentage > 70 ? 'warning' : ''); ?>" style="width: <?php echo min($percentage, 100); ?>%;"></div>
                            </div>
                            <div class="budget-remaining">
                                <?php $remaining = $budget['monthly_budget'] - $budget['spent_amount']; ?>
                                <span class="<?php echo $remaining >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $remaining >= 0 ? 'Remaining: £' . number_format($remaining, 2) : 'Over budget by: £' . number_format(abs($remaining), 2); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">No budget categories set. Add your first budget above!</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>