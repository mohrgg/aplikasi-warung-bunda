<?php
include '../php/connection.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $sql = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        $conn->query($sql);
    }

    if (isset($_POST['confirm_delete_user'])) {
        $user_id = $_POST['user_id'];
        $sql = "DELETE FROM users WHERE id='$user_id'";
        $conn->query($sql);
    }

    if (isset($_POST['edit_user'])) {
        $user_id = $_POST['user_id'];
        $username = $_POST['username'];
        $role = $_POST['role'];
        $sql = "UPDATE users SET username='$username', role='$role' WHERE id='$user_id'";
        $conn->query($sql);
    }
}

$sql = "SELECT * FROM users";
$result = $conn->query($sql);
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Accounts - Warung Bunda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        .header {
            background-color: #343a40;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .modal .modal-header {
            background-color: #343a40;
            color: white;
        }
    </style>
  </head>
  <body>
    <?php include '../php/navbar.php'; ?>
    <div class="container">
      <div class="header mt-5 mb-4">
        <h2>Manage Accounts</h2>
      </div>
      <div class="card mb-3">
        <div class="card-body">
          <form action="manage_accounts.php" method="POST">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="username" class="form-label">Username</label>
                  <input type="text" class="form-control" id="username" name="username" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <input type="password" class="form-control" id="password" name="password" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="role" class="form-label">Role</label>
                  <select class="form-select" id="role" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="employee">Employee</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="text-end">
              <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
            </div>
          </form>
        </div>
      </div>
      <hr>
      <h3>Account List</h3>
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['username']; ?></td>
                <td><?php echo $row['role']; ?></td>
                <td>
                  <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $row['id']; ?>">Delete</button>
                  <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">Edit</button>
                </td>
              </tr>

              <!-- Edit Modal -->
              <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="manage_accounts.php" method="POST">
                      <div class="modal-body">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <div class="mb-3">
                          <label for="username" class="form-label">Username</label>
                          <input type="text" class="form-control" id="username" name="username" value="<?php echo $row['username']; ?>" required>
                        </div>
                        <div class="mb-3">
                          <label for="role" class="form-label">Role</label>
                          <select class="form-select" id="role" name="role" required>
                            <option value="admin" <?php if ($row['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                            <option value="employee" <?php if ($row['role'] == 'employee') echo 'selected'; ?>>Employee</option>
                          </select>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Save changes</button>
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
                      Apakah Anda yakin ingin menghapus akun ini?
                    </div>
                    <div class="modal-footer">
                      <form action="manage_accounts.php" method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="confirm_delete_user" class="btn btn-danger">Hapus</button>
                      </form>
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                  </div>
                </div>
              </div>

            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4">No users found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>

<?php
$conn->close();
?>
