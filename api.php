<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'getMenu':
        getMenu();
        break;
    case 'getTables':
        getTables();
        break;
    case 'updateTable':
        updateTable();
        break;
    case 'saveOrder':
        saveOrder();
        break;
    case 'getOrders':
        getOrders();
        break;
    case 'saveHeldOrder':
        saveHeldOrder();
        break;
    case 'getHeldOrders':
        getHeldOrders();
        break;
    case 'getAnalytics':
        getAnalytics();
        break;
    case 'voidOrder':
        voidOrder();
        break;
    default:
        sendResponse('error', 'Invalid action');
}

function handleLogin() {
    global $pdo;
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND role = ?");
    $stmt->execute([$username, $password, $role]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        sendResponse('success', 'Login successful', [
            'name' => $user['name'],
            'role' => $user['role']
        ]);
    } else {
        sendResponse('error', 'Invalid credentials');
    }
}

function handleLogout() {
    session_destroy();
    sendResponse('success', 'Logout successful');
}

function getMenu() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY category, name");
        $menu = $stmt->fetchAll();
        
        // Ensure proper data types
        foreach ($menu as &$item) {
            $item['price'] = floatval($item['price']);
            $item['id'] = intval($item['id']);
            $item['popular'] = (bool)$item['popular'];
            $item['stock'] = intval($item['stock']);
        }
        
        sendResponse('success', 'Menu fetched', $menu);
    } catch (Exception $e) {
        sendResponse('error', 'Failed to fetch menu: ' . $e->getMessage());
    }
}

function getTables() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM tables ORDER BY table_number");
        $tables = $stmt->fetchAll();
        sendResponse('success', 'Tables fetched', $tables);
    } catch (Exception $e) {
        sendResponse('error', 'Failed to fetch tables');
    }
}

function updateTable() {
    global $pdo;
    
    $tableId = $_POST['table_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $currentOrder = $_POST['current_order'] ?? null;
    
    try {
        if ($currentOrder) {
            $stmt = $pdo->prepare("UPDATE tables SET status = ?, current_order = ? WHERE table_number = ?");
            $stmt->execute([$status, $currentOrder, $tableId]);
        } else {
            $stmt = $pdo->prepare("UPDATE tables SET status = ? WHERE table_number = ?");
            $stmt->execute([$status, $tableId]);
        }
        sendResponse('success', 'Table updated');
    } catch (Exception $e) {
        sendResponse('error', 'Failed to update table');
    }
}

function saveOrder() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO orders (order_id, table_number, items, subtotal, tax, discount, total, payment_method, status, customer_note, cashier_name, order_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['id'],
            $data['table'],
            $data['items'],
            $data['subtotal'],
            $data['tax'],
            $data['discount'],
            $data['total'],
            $data['payment'],
            $data['status'],
            $data['note'],
            $data['cashier'],
            $data['time']
        ]);
        
        sendResponse('success', 'Order saved', ['order_id' => $data['id']]);
    } catch (Exception $e) {
        sendResponse('error', 'Failed to save order: ' . $e->getMessage());
    }
}

function getOrders() {
    global $pdo;
    
    $filter = $_GET['filter'] ?? 'all';
    
    try {
        if ($filter === 'all') {
            $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 50");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$filter]);
        }
        
        $orders = $stmt->fetchAll();
        sendResponse('success', 'Orders fetched', $orders);
    } catch (Exception $e) {
        sendResponse('error', 'Failed to fetch orders');
    }
}

function saveHeldOrder() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO held_orders (hold_id, table_number, cart_data, discount_type, discount_value, customer_note, hold_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $cartJson = json_encode($data['cart']);
        
        $stmt->execute([
            $data['id'],
            $data['table'],
            $cartJson,
            $data['discount'] ? $data['discount']['type'] : null,
            $data['discount'] ? $data['discount']['value'] : 0,
            $data['note'],
            $data['time']
        ]);
        
        sendResponse('success', 'Held order saved');
    } catch (Exception $e) {
        sendResponse('error', 'Failed to save held order');
    }
}

function getHeldOrders() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM held_orders ORDER BY created_at DESC");
        $heldOrders = $stmt->fetchAll();
        
        // Parse cart data
        foreach ($heldOrders as &$order) {
            $order['cart_data'] = json_decode($order['cart_data'], true);
        }
        
        sendResponse('success', 'Held orders fetched', $heldOrders);
    } catch (Exception $e) {
        sendResponse('error', 'Failed to fetch held orders');
    }
}

function getAnalytics() {
    global $pdo;
    
    try {
        // Today's sales
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total), 0) as total 
            FROM orders 
            WHERE DATE(created_at) = ? AND status = 'completed'
        ");
        $stmt->execute([$today]);
        $todaySales = $stmt->fetch()['total'];
        
        // Total orders
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'");
        $totalOrders = $stmt->fetch()['count'];
        
        // Average order
        $stmt = $pdo->query("SELECT COALESCE(AVG(total), 0) as avg FROM orders WHERE status = 'completed'");
        $avgOrder = $stmt->fetch()['avg'];
        
        sendResponse('success', 'Analytics fetched', [
            'today_sales' => $todaySales,
            'total_orders' => $totalOrders,
            'avg_order' => $avgOrder
        ]);
    } catch (Exception $e) {
        sendResponse('error', 'Failed to fetch analytics');
    }
}

function voidOrder() {
    global $pdo;
    
    $orderId = $_POST['order_id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
        $stmt->execute([$orderId]);
        sendResponse('success', 'Order voided');
    } catch (Exception $e) {
        sendResponse('error', 'Failed to void order');
    }
}
?>