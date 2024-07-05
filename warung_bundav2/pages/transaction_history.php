<?php
include '../php/connection.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete_transaction'])) {
        $transaction_id = $_POST['transaction_id'];
        // Menghapus transaksi dan semua item terkait
        $sql = "DELETE FROM transactions WHERE id='$transaction_id'";
        $conn->query($sql);
        $sql = "DELETE FROM transaction_items WHERE transaction_id='$transaction_id'";
        $conn->query($sql);
    } elseif (isset($_POST['delete_transaction_item'])) {
        $transaction_item_id = $_POST['transaction_item_id'];
        $transaction_id = $_POST['transaction_id'];

        // Menghapus item dari transaction_items
        $sql = "DELETE FROM transaction_items WHERE id='$transaction_item_id'";
        $conn->query($sql);

        // Memeriksa apakah transaksi masih memiliki item
        $sql = "SELECT COUNT(*) as item_count FROM transaction_items WHERE transaction_id='$transaction_id'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();

        // Jika tidak ada item yang tersisa, hapus transaksi
        if ($row['item_count'] == 0) {
            $sql = "DELETE FROM transactions WHERE id='$transaction_id'";
            $conn->query($sql);
        }
    }
}

$sql = "SELECT t.id as transaction_id, t.buyer_name, t.created_at, ti.id as transaction_item_id, ti.item_id, ti.quantity, ti.total_price, s.item_name, s.image 
        FROM transactions t 
        JOIN transaction_items ti ON t.id = ti.transaction_id 
        JOIN stock s ON ti.item_id = s.id
        ORDER BY t.id DESC";
$result = $conn->query($sql);

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[$row['transaction_id']]['buyer_name'] = $row['buyer_name'];
    $transactions[$row['transaction_id']]['created_at'] = $row['created_at'];
    $transactions[$row['transaction_id']]['items'][] = $row;
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transaction History - Warung Bunda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .header {
            background-color: #343a40;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .accordion-button:not(.collapsed) {
            color: #0d6efd;
            background-color: #e7f1ff;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }
        .table td img {
            max-width: 50px;
            max-height: 50px;
        }
    </style>
</head>
<body>
<?php include '../php/navbar.php'; ?>
<div class="container">
    <div class="header mt-5 mb-4">
        <h2>Transaction History</h2>
    </div>
    <div class="accordion" id="transactionList">
        <?php foreach ($transactions as $transaction_id => $transaction): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?php echo $transaction_id; ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $transaction_id; ?>" aria-expanded="false" aria-controls="collapse<?php echo $transaction_id; ?>">
                        Transaction ID: <?php echo $transaction_id; ?> - Buyer: <?php echo $transaction['buyer_name']; ?> - Date: <?php echo date('Y-m-d', strtotime($transaction['created_at'])); ?>
                    </button>
                    <form action="transaction_history.php" method="POST" style="display:inline;">
                        <input type="hidden" name="transaction_id" value="<?php echo $transaction_id; ?>">
                        <button type="submit" name="delete_transaction" class="btn btn-danger ms-3">Delete Transaction</button>
                    </form>
                </h2>
                <div id="collapse<?php echo $transaction_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $transaction_id; ?>" data-bs-parent="#transactionList">
                    <div class="accordion-body">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Image</th>
                                <th>Quantity</th>
                                <th>Total Price</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($transaction['items'] as $item): ?>
                                <tr>
                                    <td><?php echo $item['item_name']; ?></td>
                                    <td><img src="../uploads/<?php echo $item['image']; ?>" alt="<?php echo $item['item_name']; ?>"></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo number_format($item['total_price'], 2); ?></td>
                                    <td>
                                        <form action="transaction_history.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="transaction_item_id" value="<?php echo $item['transaction_item_id']; ?>">
                                            <input type="hidden" name="transaction_id" value="<?php echo $transaction_id; ?>">
                                            <button type="submit" name="delete_transaction_item" class="btn btn-danger">Delete Item</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
