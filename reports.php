<?php
session_start();
include('config.php');

if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Get date range from GET or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get transactions for the period
$trans_query = "SELECT * FROM transactions WHERE student_id = '$student_id' AND DATE(transaction_date) BETWEEN '$start_date' AND '$end_date' ORDER BY transaction_date DESC";
$trans_result = mysqli_query($conn, $trans_query);

// Calculate totals
$total_income = 0;
$total_expenses = 0;
$transactions = [];
while($row = mysqli_fetch_assoc($trans_result)) {
    $transactions[] = $row;
    if(in_array($row['transaction_type'], ['deposit', 'refund'])) {
        $total_income += $row['amount'];
    } else {
        $total_expenses += $row['amount'];
    }
}

// Get category breakdown
$category_query = "SELECT category, SUM(amount) as total FROM transactions WHERE student_id = '$student_id' AND transaction_type IN ('withdrawal', 'payment') AND DATE(transaction_date) BETWEEN '$start_date' AND '$end_date' GROUP BY category";
$category_result = mysqli_query($conn, $category_query);
$categories = [];
while($row = mysqli_fetch_assoc($category_result)) {
    $categories[] = $row;
}

// Handle PDF generation
if(isset($_GET['generate_pdf'])) {
    // Simple PDF generation using HTML2PDF (you'd need to install a library)
    // For now, we'll just show a message
    $_SESSION['success'] = "PDF report generated! (Feature coming soon)";
    header("Location: reports.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Student Wallet</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo-container">
                    <img src="https://www.wlv.ac.uk/media/dam/wlv/images/logos/uni-logo.png" alt="University" class="logo">
                    <h1>Financial Reports</h1>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student'); ?></span>
                    <a href="index.php" class="logout-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </header>

        <main>
            <div class="reports-container">
                <!-- Date Filter -->
                <div class="report-filter">
                    <form method="GET" action="" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="submit-btn"><i class="fas fa-filter"></i> Apply Filter</button>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <a href="reports.php?generate_pdf=1" class="submit-btn pdf-btn"><i class="fas fa-file-pdf"></i> Generate PDF</a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Summary Cards -->
                <div class="report-summary">
                    <div class="summary-card">
                        <h3>Total Income</h3>
                        <div class="summary-amount positive">£<?php echo number_format($total_income, 2); ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Total Expenses</h3>
                        <div class="summary-amount negative">£<?php echo number_format($total_expenses, 2); ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Net Balance</h3>
                        <div class="summary-amount <?php echo ($total_income - $total_expenses) >= 0 ? 'positive' : 'negative'; ?>">
                            £<?php echo number_format($total_income - $total_expenses, 2); ?>
                        </div>
                    </div>
                    <div class="summary-card">
                        <h3>Transaction Count</h3>
                        <div class="summary-amount"><?php echo count($transactions); ?></div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="chart-section">
                    <h3><i class="fas fa-chart-pie"></i> Spending by Category</h3>
                    <div class="chart-wrapper">
                        <canvas id="reportChart"></canvas>
                    </div>
                </div>

                <!-- Transaction List -->
                <div class="report-transactions">
                    <h3><i class="fas fa-list"></i> Transaction Details</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($transactions) > 0): ?>
                                    <?php foreach($transactions as $row): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($row['transaction_date'])); ?></td>
                                        <td><span class="type-badge <?php echo $row['transaction_type']; ?>">
                                            <?php echo ucfirst($row['transaction_type']); ?>
                                        </span></td>
                                        <td class="<?php echo in_array($row['transaction_type'], ['deposit', 'refund']) ? 'positive' : 'negative'; ?>">
                                            <?php echo in_array($row['transaction_type'], ['deposit', 'refund']) ? '+' : '-'; ?>£<?php echo number_format($row['amount'], 2); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td><?php echo htmlspecialchars($row['category'] ?? 'Uncategorized'); ?></td>
                                        <td><span class="status-badge <?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="no-data">No transactions found for this period</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('reportChart');
        if (ctx) {
            const categories = <?php echo json_encode(array_column($categories, 'category')); ?>;
            const amounts = <?php echo json_encode(array_column($categories, 'total')); ?>;
            
            new Chart(ctx, {
                type: 'pie',
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
    </script>
</body>
</html>