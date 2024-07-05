<?php
include '../php/connection.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Mendapatkan riwayat transaksi
$sql = "SELECT t.id as transaction_id, t.buyer_name, t.total_price as transaction_total, t.created_at, ti.quantity, ti.total_price, s.item_name, s.image, s.price as unit_price 
        FROM transactions t 
        JOIN transaction_items ti ON t.id = ti.transaction_id 
        JOIN stock s ON ti.item_id = s.id
        ORDER BY t.id DESC";
$transactions_result = $conn->query($sql);

// Mengelompokkan item berdasarkan ID transaksi
$transactions = [];
while ($row = $transactions_result->fetch_assoc()) {
    $transactions[$row['transaction_id']]['buyer_name'] = $row['buyer_name'];
    $transactions[$row['transaction_id']]['transaction_total'] = $row['transaction_total'];
    $transactions[$row['transaction_id']]['created_at'] = $row['created_at'];
    $transactions[$row['transaction_id']]['items'][] = [
        'item_name' => $row['item_name'],
        'quantity' => $row['quantity'],
        'total_price' => $row['total_price'],
        'image' => $row['image'],
        'unit_price' => $row['unit_price']
    ];
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transaction List - Warung Bunda</title>
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
        .total-info {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php include '../php/navbar.php'; ?>
<div class="container">
    <div class="header mt-5 mb-4">
        <h2>Transaction List</h2>
    </div>
    <div class="accordion" id="transactionList">
        <?php if (!empty($transactions)): ?>
            <?php foreach ($transactions as $transaction_id => $transaction): ?>
                <?php
                    $total_items = array_sum(array_column($transaction['items'], 'quantity'));
                    $total_price = number_format($transaction['transaction_total'], 2);
                ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?php echo $transaction_id; ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $transaction_id; ?>" aria-expanded="false" aria-controls="collapse<?php echo $transaction_id; ?>">
                            Transaction ID: <?php echo $transaction_id; ?> - Buyer: <?php echo $transaction['buyer_name']; ?> - Total: <?php echo $total_price; ?>
                        </button>
                    </h2>
                    <div id="collapse<?php echo $transaction_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $transaction_id; ?>" data-bs-parent="#transactionList">
                        <div class="accordion-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Image</th>
                                        <th>Unit Price</th>
                                        <th>Quantity</th>
                                        <th>Total Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transaction['items'] as $item): ?>
                                        <tr>
                                            <td><?php echo $item['item_name']; ?></td>
                                            <td><img src="../uploads/<?php echo $item['image']; ?>" alt="<?php echo $item['item_name']; ?>" width="50"></td>
                                            <td><?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo number_format($item['total_price'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="total-info">
                                Total Items: <?php echo $total_items; ?>, Total Price: <?php echo $total_price; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                No transactions found.
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
