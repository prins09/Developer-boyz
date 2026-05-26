<?php
$conn = new mysqli("localhost", "root", "", "pos_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard</title>
  <style>
    body { font-family: Arial; padding: 20px; background: #f4f4f4; }
    h1 { margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; background: white; }
    th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
    th { background: #333; color: white; }
    tr:hover { background: #f1f1f1; }
    .badge { padding: 5px 10px; border-radius: 5px; color: white; }
    .completed { background: green; }
    .pending { background: orange; }
  </style>
</head>
<body>

<h1>📊 Orders Dashboard</h1>

<table>
  <tr>
    <th>ID</th>
    <th>Items</th>
    <th>Total ($)</th>
    <th>Payment</th>
    <th>Status</th>
    <th>Date</th>
  </tr>

  <?php while($row = $result->fetch_assoc()): ?>
  <tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['items'] ?></td>
    <td><?= $row['total'] ?></td>
    <td><?= $row['payment_method'] ?></td>
    <td>
      <span class="badge <?= $row['status'] ?>">
        <?= $row['status'] ?>
      </span>
    </td>
    <td><?= $row['created_at'] ?></td>
  </tr>
  <?php endwhile; ?>

</table>

</body>
</html>

<?php $conn->close(); ?>