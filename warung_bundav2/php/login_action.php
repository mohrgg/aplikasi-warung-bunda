<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {
            session_start();
            $_SESSION['username'] = $username;
            header('Location: ../pages/dashboard.php');
        } else {
            header('Location: ../pages/login.php?error=Invalid Password');
        }
    } else {
        header('Location: ../pages/login.php?error=User Not Found');
    }
}
?>
