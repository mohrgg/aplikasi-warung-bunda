<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include '../php/connection.php';

// Ambil data untuk ringkasan stok
$stok_sql = "SELECT COUNT(id) AS total_items, SUM(quantity) AS total_quantity FROM stock";
$stok_result = $conn->query($stok_sql);
$stok_data = $stok_result->fetch_assoc();

// Ambil data untuk ringkasan transaksi
$transaksi_sql = "SELECT COUNT(id) AS total_transactions, SUM(total_price) AS total_revenue FROM transactions";
$transaksi_result = $conn->query($transaksi_sql);
$transaksi_data = $transaksi_result->fetch_assoc();

// Ambil data pendapatan bulanan
$pendapatan_sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(total_price) AS revenue FROM transactions GROUP BY month ORDER BY month";
$pendapatan_result = $conn->query($pendapatan_sql);

$pendapatan_bulanan = [];
while ($row = $pendapatan_result->fetch_assoc()) {
    $pendapatan_bulanan[] = $row;
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Warung Bunda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card-indicator {
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .bg-blue {
            background-color: #007bff;
        }
        .bg-orange {
            background-color: #fd7e14;
        }
        .bg-green {
            background-color: #28a745;
        }
        .bg-red {
            background-color: #dc3545;
        }
        .header {
            background-color: #343a40;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../php/navbar.php'; ?>
    <div class="container">
        <div class="header mt-5 mb-4">
            <h2>Dashboard</h2>
            <p>Welcome, <?php echo $_SESSION['username']; ?>! You are logged in as <?php echo $_SESSION['role']; ?>.</p>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="card card-indicator bg-blue">
                    <h5>Total Items</h5>
                    <p><?php echo $stok_data['total_items']; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-indicator bg-orange">
                    <h5>Total Quantity</h5>
                    <p><?php echo $stok_data['total_quantity']; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-indicator bg-green">
                    <h5>Total Revenue</h5>
                    <p><?php echo $transaksi_data['total_revenue']; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-indicator bg-red">
                    <h5>Total Transactions</h5>
                    <p><?php echo $transaksi_data['total_transactions']; ?></p>
                </div>
            </div>
        </div>

        <!-- Grafik Pendapatan Bulanan -->
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Revenue</h5>
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fitur Khusus Admin -->
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Admin Actions</h5>
                    <a href="manage_accounts.php" class="btn btn-primary">Manage Account</a>
                    <a href="manage_stock.php" class="btn btn-primary">Manage Stock</a>
                    <a href="financial_report.php" class="btn btn-primary">View Financial Report</a>
                    <a href="transactions_history.php" class="btn btn-primary">Transaction History</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Fitur Khusus Employee -->
        <?php if ($_SESSION['role'] == 'employee'): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Employee Actions</h5>
                    <a href="transactions.php" class="btn btn-primary">Record Transaction</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('monthlyRevenueChart').getContext('2d');
            const monthlyRevenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($pendapatan_bulanan, 'month')); ?>,
                    datasets: [{
                        label: 'Monthly Revenue',
                        data: <?php echo json_encode(array_column($pendapatan_bulanan, 'revenue')); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            beginAtZero: true
                        },
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
