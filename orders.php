<?php
header('Content-Type: application/json');

// MySQL connection
$host = 'localhost';
$db   = 'restaurant_pos';
$user = 'root';       // change if needed
$pass = '';           // change if needed
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['status'=>'error','message'=>'Database connection failed']);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'add':
            if (empty($_POST['items']) || !isset($_POST['total'])) {
                echo json_encode(['status'=>'error','message'=>'Missing required fields']);
                exit;
            }
            
            $items = $_POST['items'];
            $total = floatval($_POST['total']);
            $payment = in_array($_POST['payment_method'] ?? '', ['cash', 'card']) ? $_POST['payment_method'] : 'cash';
            
            $stmt = $pdo->prepare("INSERT INTO orders (items, total, payment_method) VALUES (?, ?, ?)");
            $stmt->execute([$items, $total, $payment]);
            
            echo json_encode(['status'=>'success','message'=>'Order added', 'id' => $pdo->lastInsertId()]);
            break;
            
        case 'update':
            if (empty($_POST['id']) || empty($_POST['items']) || !isset($_POST['total'])) {
                echo json_encode(['status'=>'error','message'=>'Missing required fields']);
                exit;
            }
            
            $id = intval($_POST['id']);
            $items = $_POST['items'];
            $total = floatval($_POST['total']);
            $payment = in_array($_POST['payment_method'] ?? '', ['cash', 'card']) ? $_POST['payment_method'] : 'cash';
            
            $stmt = $pdo->prepare("UPDATE orders SET items=?, total=?, payment_method=? WHERE id=?");
            $stmt->execute([$items, $total, $payment, $id]);
            
            if ($stmt->rowCount() === 0) {
                echo json_encode(['status'=>'error','message'=>'Order not found or no changes made']);
                exit;
            }
            
            echo json_encode(['status'=>'success','message'=>'Order updated']);
            break;
            
        case 'delete':
            if (empty($_POST['id'])) {
                echo json_encode(['status'=>'error','message'=>'ID required']);
                exit;
            }
            
            $id = intval($_POST['id']);
            
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id=?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                echo json_encode(['status'=>'error','message'=>'Order not found']);
                exit;
            }
            
            echo json_encode(['status'=>'success','message'=>'Order deleted']);
            break;
            
        default:
            echo json_encode(['status'=>'error','message'=>'Invalid action']);
            exit;
    }
    exit;
}

// GET request: return all orders
try {
    // Check which column exists (order_date or created_at)
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'order_date'");
    $hasOrderDate = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'created_at'");
    $hasCreatedAt = $stmt->rowCount() > 0;
    
    if ($hasOrderDate) {
        $stmt = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC");
    } elseif ($hasCreatedAt) {
        $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    } else {
        $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
    }
    
    $orders = $stmt->fetchAll();
    echo json_encode($orders);
    
} catch (\PDOException $e) {
    echo json_encode(['status'=>'error','message'=>'Failed to fetch orders']);
}
?>