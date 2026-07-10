<?php
session_start();
include('config.php');

if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Handle new goal
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_goal'])) {
    $goal_name = mysqli_real_escape_string($conn, $_POST['goal_name']);
    $target_amount = floatval($_POST['target_amount']);
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
    
    $insert_query = "INSERT INTO savings_goals (student_id, goal_name, target_amount, deadline) 
                     VALUES ('$student_id', '$goal_name', '$target_amount', '$deadline')";
    if(mysqli_query($conn, $insert_query)) {
        $_SESSION['success'] = "Savings goal created successfully!";
    } else {
        $_SESSION['error'] = "Failed to create savings goal.";
    }
    header("Location: savings.php");
    exit();
}

// Get all goals
$goals_query = "SELECT * FROM savings_goals WHERE student_id = '$student_id' ORDER BY created_at DESC";
$goals_result = mysqli_query($conn, $goals_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savings Goals - Student Wallet</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo-container">
                    <img src="https://www.wlv.ac.uk/media/dam/wlv/images/logos/uni-logo.png" alt="University" class="logo">
                    <h1>Savings Goals</h1>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student'); ?></span>
                    <a href="index.php" class="logout-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </header>

        <main>
            <div class="savings-container">
                <div class="add-goal-section">
                    <h2><i class="fas fa-plus-circle"></i> Create New Savings Goal</h2>
                    <form method="POST" action="" class="goal-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="goal_name">Goal Name</label>
                                <input type="text" name="goal_name" id="goal_name" required placeholder="e.g., New Laptop">
                            </div>
                            <div class="form-group">
                                <label for="target_amount">Target Amount (£)</label>
                                <input type="number" step="0.01" name="target_amount" id="target_amount" required placeholder="500.00">
                            </div>
                            <div class="form-group">
                                <label for="deadline">Deadline</label>
                                <input type="date" name="deadline" id="deadline" required>
                            </div>
                        </div>
                        <button type="submit" name="add_goal" class="submit-btn">
                            <i class="fas fa-save"></i> Create Goal
                        </button>
                    </form>
                </div>

                <div class="goals-list">
                    <h2><i class="fas fa-piggy-bank"></i> Your Savings Goals</h2>
                    <div class="goals-grid">
                        <?php if(mysqli_num_rows($goals_result) > 0): ?>
                            <?php while($goal = mysqli_fetch_assoc($goals_result)): 
                                $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                            ?>
                            <div class="goal-card">
                                <div class="goal-header">
                                    <h4><?php echo htmlspecialchars($goal['goal_name']); ?></h4>
                                    <span class="goal-status <?php echo $goal['status']; ?>">
                                        <?php echo ucfirst($goal['status']); ?>
                                    </span>
                                </div>
                                <div class="goal-amounts">
                                    <span>Saved: £<?php echo number_format($goal['current_amount'], 2); ?></span>
                                    <span>Target: £<?php echo number_format($goal['target_amount'], 2); ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min($progress, 100); ?>%;"></div>
                                </div>
                                <div class="goal-progress-text"><?php echo number_format($progress, 1); ?>% Complete</div>
                                <div class="goal-deadline">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?php if($goal['deadline']): ?>
                                        Deadline: <?php echo date('d/m/Y', strtotime($goal['deadline'])); ?>
                                    <?php else: ?>
                                        No deadline set
                                    <?php endif; ?>
                                </div>
                                <div class="goal-actions">
                                    <a href="add_to_goal.php?id=<?php echo $goal['goal_id']; ?>" class="btn-add-funds">
                                        <i class="fas fa-plus"></i> Add Funds
                                    </a>
                                    <?php if($goal['status'] == 'active'): ?>
                                        <a href="complete_goal.php?id=<?php echo $goal['goal_id']; ?>" class="btn-complete" onclick="return confirm('Mark this goal as completed?')">
                                            <i class="fas fa-check"></i> Complete
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="no-data">No savings goals yet. Create your first goal above!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html>