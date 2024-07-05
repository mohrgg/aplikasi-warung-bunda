<?php
include '../php/connection.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

if ($start_date && $end_date) {
    $sql = "SELECT t.id as transaction_id, t.buyer_name, t.created_at, ti.item_id, ti.quantity, ti.total_price, s.item_name 
            FROM transactions t 
            JOIN transaction_items ti ON t.id = ti.transaction_id 
            JOIN stock s ON ti.item_id = s.id 
            WHERE DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'";
} else {
    $sql = "SELECT t.id as transaction_id, t.buyer_name, t.created_at, ti.item_id, ti.quantity, ti.total_price, s.item_name 
            FROM transactions t 
            JOIN transaction_items ti ON t.id = ti.transaction_id 
            JOIN stock s ON ti.item_id = s.id";
}
$result = $conn->query($sql);

$total_revenue = 0;
$total_transactions = 0;

$transactions = []; // Initialize an array to hold transaction data

while ($row = $result->fetch_assoc()) {
    $total_revenue += $row['total_price'];
    $total_transactions++;
    if (!isset($transactions[$row['transaction_id']])) {
        $transactions[$row['transaction_id']] = [
            'buyer_name' => $row['buyer_name'],
            'created_at' => $row['created_at'],
            'total_transaction_price' => 0,
            'items' => []
        ];
    }
    $transactions[$row['transaction_id']]['total_transaction_price'] += $row['total_price'];
    $transactions[$row['transaction_id']]['items'][] = [
        'item_name' => $row['item_name'],
        'quantity' => $row['quantity'],
        'total_price' => $row['total_price']
    ];
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Financial Report - Warung Bunda</title>
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
        .summary-card, .details-card {
            margin-bottom: 20px;
        }
        .table-summary, .table-details {
            width: 100%;
        }
        .table-summary th, .table-summary td, .table-details th, .table-details td {
            text-align: center;
            vertical-align: middle;
        }
        .total-amount {
            font-size: 1.5em;
            font-weight: bold;
        }
        .btn-primary, .btn-secondary {
            width: 100%;
        }
        .accordion-button {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
<?php include '../php/navbar.php'; ?>
<div class="container">
    <div class="header mt-5 mb-4">
        <h1>Financial Report</h1>
        <p>Warung Bunda</p>
        <p><?php echo date('F j, Y'); ?></p>
    </div>
    <form action="financial_report.php" method="POST" class="mb-5">
        <div class="row">
            <div class="col-md-5">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
            </div>
            <div class="col-md-5">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </div>
        </div>
    </form>
    <form action="print_report.php" method="POST" target="_blank" class="mb-5">
        <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
        <input type="hidden" name="end_date" value="<?php echo $end_date; ?>">
        <button type="submit" class="btn btn-secondary">Print Report</button>
    </form>
    <div class="summary-card card">
        <div class="card-body">
            <h3 class="card-title">Report Summary</h3>
            <table class="table table-summary">
                <thead>
                <tr>
                    <th>Total Revenue</th>
                    <th>Total Transactions</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><?php echo number_format($total_revenue, 2); ?></td>
                    <td><?php echo $total_transactions; ?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="details-card card">
        <div class="card-body">
            <h3 class="card-title">Transaction Details</h3>
            <div class="accordion" id="transactionList">
                <?php foreach ($transactions as $transaction_id => $transaction): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $transaction_id; ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $transaction_id; ?>" aria-expanded="false" aria-controls="collapse<?php echo $transaction_id; ?>">
                                Transaction ID: <?php echo $transaction_id; ?> - Buyer: <?php echo $transaction['buyer_name']; ?> - Date: <?php echo date('Y-m-d', strtotime($transaction['created_at'])); ?>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $transaction_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $transaction_id; ?>" data-bs-parent="#transactionList">
                            <div class="accordion-body">
                                <table class="table table-details table-striped">
                                    <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Quantity</th>
                                        <th>Total Price</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($transaction['items'] as $item): ?>
                                        <tr>
                                            <td><?php echo $item['item_name']; ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo number_format($item['total_price'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="2" class="text-end"><strong>Total Transaction</strong></td>
                                        <td><strong><?php echo number_format($transaction['total_transaction_price'], 2); ?></strong></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
