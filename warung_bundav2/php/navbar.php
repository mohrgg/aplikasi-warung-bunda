<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand text-white" href="../pages/dashboard.php">Warung Bunda</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link text-white" href="../pages/dashboard.php">Dashboard</a>
        </li>
        <?php if ($_SESSION['role'] == 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link text-white" href="../pages/manage_accounts.php">Manage Accounts</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="../pages/manage_stock.php">Manage Stock</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="../pages/financial_report.php">Financial Report</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="../pages/transaction_history.php">Transaction History</a>
          </li>
        <?php endif; ?>
        <?php if ($_SESSION['role'] == 'employee'): ?>
          <li class="nav-item">
            <a class="nav-link text-white" href="../pages/transactions.php">Transaksi</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="../pages/transaction_list.php">Daftar Transaksi</a>
          </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link text-white" href="../pages/logout.php">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<style>
  .nav-link:hover {
    color: #f8f9fa; /* Warna teks saat hover */
    background-color: #343a40; /* Warna latar belakang saat hover */
    border-radius: 5px; /* Menambahkan border-radius untuk tampilan yang lebih baik */
  }
  .navbar-brand:hover {
    color: #f8f9fa; /* Warna teks saat hover */
  }
</style>
