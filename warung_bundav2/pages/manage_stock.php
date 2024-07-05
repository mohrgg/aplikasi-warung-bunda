<?php
include '../php/connection.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$search = "";
$category_filter = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $price = $_POST['price'];
        $quantity = $_POST['stock'];
        $category = $_POST['category'];
        $expiry_date = $_POST['expiry_date'];
        $image = $_FILES['image']['name'];
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($image);

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $expiry_date_value = !empty($expiry_date) ? "'$expiry_date'" : "NULL";
            $sql = "SELECT id, quantity, status FROM stock WHERE item_name='$name' AND category='$category' AND price='$price'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $existing_item = $result->fetch_assoc();
                $existing_id = $existing_item['id'];
                if ($existing_item['status'] == 'deleted') {
                    $new_quantity = $quantity;
                    $sql = "UPDATE stock SET quantity='$new_quantity', expiry_date=$expiry_date_value, image='$image', status='active' WHERE id='$existing_id'";
                } else {
                    $new_quantity = $existing_item['quantity'] + $quantity;
                    $sql = "UPDATE stock SET quantity='$new_quantity', expiry_date=$expiry_date_value, image='$image' WHERE id='$existing_id'";
                }
            } else {
                $sql = "INSERT INTO stock (item_name, price, quantity, category, expiry_date, image, status) VALUES ('$name', '$price', '$quantity', '$category', $expiry_date_value, '$image', 'active')";
            }
            $conn->query($sql);
        }
    }

    if (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'];
        $sql = "UPDATE stock SET status='deleted' WHERE id='$product_id'";
        $conn->query($sql);
    }

    if (isset($_POST['edit_product'])) {
        $product_id = $_POST['product_id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $quantity = $_POST['stock'];
        $category = $_POST['category'];
        $expiry_date = $_POST['expiry_date'];
        $image = $_FILES['image']['name'];
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($image);
        if ($image) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $expiry_date_value = !empty($expiry_date) ? "'$expiry_date'" : "NULL";
                $sql = "UPDATE stock SET item_name='$name', price='$price', quantity='$quantity', category='$category', expiry_date=$expiry_date_value, image='$image', status='active' WHERE id='$product_id'";
            }
        } else {
            $expiry_date_value = !empty($expiry_date) ? "'$expiry_date'" : "NULL";
            $sql = "UPDATE stock SET item_name='$name', price='$price', quantity='$quantity', category='$category', expiry_date=$expiry_date_value, status='active' WHERE id='$product_id'";
        }
        $conn->query($sql);
    }

    if (isset($_POST['search'])) {
        $search = $_POST['search'];
    }

    if (isset($_POST['category_filter'])) {
        $category_filter = $_POST['category_filter'];
    }
}

$sql = "SELECT * FROM stock WHERE status='active' AND item_name LIKE '%$search%'";
if (!empty($category_filter)) {
    $sql .= " AND category='$category_filter'";
}
$result = $conn->query($sql);
$category_sql = "SELECT DISTINCT category FROM stock";
$category_result = $conn->query($category_sql);
$categories = [];
if ($category_result->num_rows > 0) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Stock - Warung Bunda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-card {
            height: 500px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-radius: 10px;
        }
        .product-card img {
            height: 200px;
            width: 100%;
            object-fit: contain;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .product-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .product-card .btn {
            width: 48%;
        }
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
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
            <h2>Manage Stock</h2>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <form action="manage_stock.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" class="form-control" id="price" name="price" required>
                            </div>
                            <div class="mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="category" name="category" required>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Image</label>
                                <input type="file" class="form-control" id="image" name="image" required>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
        <hr>
        <h3>Product List</h3>
        <form method="POST" action="manage_stock.php">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search product by name" value="<?php echo $search; ?>">
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="category_filter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category; ?>" <?php if ($category == $category_filter) echo 'selected'; ?>><?php echo $category; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="card product-card">
                            <img src="../uploads/<?php echo $row['image']; ?>" class="card-img-top" alt="<?php echo $row['item_name']; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $row['item_name']; ?></h5>
                                <p class="card-text">Price: <?php echo $row['price']; ?></p>
                                <p class="card-text">Stock: <?php echo $row['quantity']; ?></p>
                                <p class="card-text">Category: <?php echo $row['category']; ?></p>
                                <p class="card-text">Expiry Date: <?php echo $row['expiry_date']; ?></p>
                                <div class="btn-container">
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $row['id']; ?>">Delete</button>
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">Edit</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel">Edit Product</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="manage_stock.php" method="POST" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Product Name</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $row['item_name']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="price" class="form-label">Price</label>
                                            <input type="number" class="form-control" id="price" name="price" value="<?php echo $row['price']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="stock" class="form-label">Stock</label>
                                            <input type="number" class="form-control" id="stock" name="stock" value="<?php echo $row['quantity']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="category" class="form-label">Category</label>
                                            <input type="text" class="form-control" id="category" name="category" value="<?php echo $row['category']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="expiry_date" class="form-label">Expiry Date</label>
                                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?php echo $row['expiry_date']; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="image" class="form-label">Image</label>
                                            <input type="file" class="form-control" id="image" name="image">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="edit_product" class="btn btn-primary">Save changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Apakah Anda yakin ingin menghapus barang ini?
                                </div>
                                <div class="modal-footer">
                                    <form action="manage_stock.php" method="POST">
                                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger">Hapus</button>
                                    </form>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-md-12">
                    <div class="alert alert-warning" role="alert">
                        No products found
                    </div>
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
