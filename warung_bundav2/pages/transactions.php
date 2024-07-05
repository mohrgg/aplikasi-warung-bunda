<?php
include '../php/connection.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Inisialisasi keranjang
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Menambahkan item ke keranjang
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];
    $sql = "SELECT * FROM stock WHERE id='$item_id'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        if ($item['quantity'] >= $quantity) {
            if (!empty($item['expiry_date']) && strtotime($item['expiry_date']) < time()) {
                $_SESSION['expired_warning'] = true;
                echo "<script>sessionStorage.setItem('expired_warning', 'true');</script>";
            } else {
                $item['quantity'] = $quantity;
                $_SESSION['cart'][$item_id] = $item;
                $success_message = "Item added to cart successfully.";
            }
        } else {
            $error_message = "Insufficient stock for item: " . $item['item_name'];
        }
    }

    // Simpan kembali pencarian ke variabel sesi
    $_SESSION['searchKeyword'] = $_POST['searchKeyword'];
    $_SESSION['category'] = $_POST['category'];
    $_SESSION['rows_per_page'] = $_POST['rows_per_page'];
}

// Menghapus item dari keranjang
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_from_cart'])) {
    $item_id = $_POST['item_id'];
    unset($_SESSION['cart'][$item_id]);
}

// Mengedit kuantitas di keranjang
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];
    if ($quantity > 0) {
        $_SESSION['cart'][$item_id]['quantity'] = $quantity;
    }
}

// Menghapus semua item dari keranjang
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = array();
}

// Merekam transaksi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_payment'])) {
    if (isset($_POST['buyer_name']) && !empty($_POST['buyer_name'])) {
        $buyer_name = $_POST['buyer_name'];
        $total_price = array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $_SESSION['cart']));
        
        // Merekam transaksi utama
        $sql = "INSERT INTO transactions (buyer_name, total_price, created_at) VALUES ('$buyer_name', '$total_price', NOW())";
        if ($conn->query($sql) === TRUE) {
            $transaction_id = $conn->insert_id;

            // Merekam setiap item dalam transaksi
            foreach ($_SESSION['cart'] as $item) {
                $item_id = $item['id'];
                $quantity = $item['quantity'];
                $item_total_price = $item['price'] * $quantity;

                $sql = "INSERT INTO transaction_items (transaction_id, item_id, quantity, total_price) VALUES ('$transaction_id', '$item_id', '$quantity', '$item_total_price')";
                if ($conn->query($sql) === TRUE) {
                    // Mengurangi stok
                    $sql = "UPDATE stock SET quantity = quantity - '$quantity' WHERE id='$item_id'";
                    $conn->query($sql);
                } else {
                    echo "Error recording transaction item: " . $conn->error . "<br>";
                }
            }
            // Bersihkan keranjang
            $_SESSION['cart'] = array();
            $success_message = "Transaction recorded successfully.";
        } else {
            $error_message = "Error recording transaction: " . $conn->error . "<br>";
        }
    } else {
        $error_message = "Buyer name not set.";
    }
}

// Mendapatkan data stok dan kategori untuk pencarian
$categories_result = $conn->query("SELECT DISTINCT category FROM stock");

// Memuat pencarian dari variabel sesi
$searchKeyword = isset($_SESSION['searchKeyword']) ? $_SESSION['searchKeyword'] : '';
$category = isset($_SESSION['category']) ? $_SESSION['category'] : '';
$rows_per_page = isset($_SESSION['rows_per_page']) ? $_SESSION['rows_per_page'] : 10;

$search_result = array();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_items'])) {
    $searchKeyword = $_POST['searchKeyword'];
    $category = $_POST['category'];
    $rows_per_page = $_POST['rows_per_page'];
    
    $_SESSION['searchKeyword'] = $searchKeyword;
    $_SESSION['category'] = $category;
    $_SESSION['rows_per_page'] = $rows_per_page;
}

$search_sql = "SELECT * FROM stock WHERE item_name LIKE '%$searchKeyword%'";
if ($category != '' && $category != 'All Categories') {
    $search_sql .= " AND category='$category'";
}
$search_sql .= " LIMIT $rows_per_page";
$search_result = $conn->query($search_sql);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cashier Application - Warung Bunda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .cart-table th, .cart-table td {
            text-align: center;
        }
        .total-amount {
            font-size: 1.5em;
            font-weight: bold;
        }
        .item-table img {
            width: 50px;
            height: 50px;
        }
        .search-card, .results-card {
            margin-bottom: 20px;
            background-color: #e9ecef;
            border-radius: 10px;
        }
        .cart-card, .summary-card {
            margin-top: 20px;
        }
        .summary-card .card-body {
            font-size: 1.2em;
            font-weight: bold;
        }
        .btn-add-to-cart {
            margin-top: 5px;
        }
        .form-quantity {
            width: 80px;
            margin: 0 auto;
        }
        .form-quantity input {
            text-align: center;
        }
        .cart-section, .summary-section {
            margin-bottom: 20px;
        }
        .header {
            background-color: #343a40;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
        }
    </style>
    <script src="../js/transactions.js"></script>
</head>
<body>
<?php include '../php/navbar.php'; ?>
<div class="container">
    <div class="header mt-5 mb-4">
        <h2>Cashier Application</h2>
    </div>
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-4">
            <div class="card search-card">
                <div class="card-body">
                    <form action="transactions.php" method="POST">
                        <div class="mb-3">
                            <label for="searchKeyword" class="form-label">Search Item</label>
                            <input type="text" id="searchKeyword" name="searchKeyword" class="form-control" value="<?php echo $searchKeyword; ?>" placeholder="Enter item name">
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select id="category" name="category" class="form-select">
                                <option value="All Categories">All Categories</option>
                                <?php while ($row = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $row['category']; ?>" <?php echo ($category == $row['category']) ? 'selected' : ''; ?>><?php echo $row['category']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="rows_per_page" class="form-label">Rows per Page</label>
                            <select id="rows_per_page" name="rows_per_page" class="form-select">
                                <option value="5" <?php echo ($rows_per_page == 5) ? 'selected' : ''; ?>>5</option>
                                <option value="10" <?php echo ($rows_per_page == 10) ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?php echo ($rows_per_page == 20) ? 'selected' : ''; ?>>20</option>
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="search_items" class="btn btn-primary">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <?php if (!empty($search_result) && $search_result->num_rows > 0): ?>
                <div class="card results-card">
                    <div class="card-body">
                        <h3 class="mb-3">Search Results</h3>
                        <table class="table table-striped item-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Expiry Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $search_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><img src="../uploads/<?php echo $row['image']; ?>" alt="<?php echo $row['item_name']; ?>"></td>
                                        <td><?php echo $row['item_name']; ?></td>
                                        <td><?php echo $row['category']; ?></td>
                                        <td><?php echo $row['price']; ?></td>
                                        <td><?php echo $row['quantity']; ?></td>
                                        <td><?php echo $row['expiry_date']; ?></td>
                                        <td>
                                            <form action="transactions.php" method="POST" class="form-quantity">
                                                <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="searchKeyword" value="<?php echo $searchKeyword; ?>">
                                                <input type="hidden" name="category" value="<?php echo $category; ?>">
                                                <input type="hidden" name="rows_per_page" value="<?php echo $rows_per_page; ?>">
                                                <input type="number" name="quantity" class="form-control" placeholder="Qty" required>
                                                <button type="submit" name="add_to_cart" class="btn btn-primary btn-add-to-cart">Add to Cart</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    No items found in this category or search term.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row cart-section">
        <div class="col-md-12">
            <div class="card cart-card">
                <div class="card-body">
                    <h3 class="mb-3">Cart</h3>
                    <table class="table table-striped cart-table">
                        <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $total_amount = 0; ?>
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <tr>
                                <td><?php echo $item['item_name']; ?></td>
                                <td>
                                    <form action="transactions.php" method="POST" class="d-inline">
                                        <input type="number" name="quantity" class="form-control d-inline-block w-50" value="<?php echo $item['quantity']; ?>" required>
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="update_cart" class="btn btn-secondary">Update</button>
                                    </form>
                                </td>
                                <td class="item-price"><?php echo $item['price']; ?></td>
                                <td><?php echo $item['price'] * $item['quantity']; ?></td>
                                <td>
                                    <form action="transactions.php" method="POST">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_from_cart" class="btn btn-danger">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php $total_amount += $item['price'] * $item['quantity']; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="text-end mb-3 total-amount">
                        Total Amount: <?php echo number_format($total_amount, 2); ?>
                    </div>
                    <div class="text-end mb-3">
                        <form action="transactions.php" method="POST">
                            <button type="submit" name="clear_cart" class="btn btn-danger">Clear Cart</button>
                        </form>
                    </div>
                    <div class="mb-3">
                        <label for="buyer_name" class="form-label">Buyer's Name</label>
                        <input type="text" name="buyer_name" id="buyer_name" class="form-control" required>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmPaymentModal" onclick="return setBuyerName()">Confirm Payment</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Pembayaran -->
<div class="modal fade" id="confirmPaymentModal" tabindex="-1" aria-labelledby="confirmPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmPaymentModalLabel">Confirm Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="paymentError" class="alert alert-danger d-none">Please enter the buyer's name.</div>
                Are you sure you want to proceed with the payment?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="transactions.php" method="POST">
                    <input type="hidden" name="buyer_name" id="modalBuyerName">
                    <button type="submit" name="confirm_payment" class="btn btn-primary">Confirm Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Peringatan Nama Pembeli -->
<div class="modal fade" id="buyerNameModal" tabindex="-1" aria-labelledby="buyerNameModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="buyerNameModalLabel">Warning</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Please enter the buyer's name before confirming the payment.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Peringatan Barang Kedaluwarsa -->
<div class="modal fade" id="expiredWarningModal" tabindex="-1" aria-labelledby="expiredWarningModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="expiredWarningModalLabel">Expired Item Warning</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Some items in the search results are expired. Please check their expiry dates before adding to the cart.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/transactions.js"></script>
</body>
</html>

<?php
$conn->close();
?>
