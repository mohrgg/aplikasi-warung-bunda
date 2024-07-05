<?php
include 'connection.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $row['role'];
            header("Location: ../pages/dashboard.php");
        } else {
            $_SESSION['error_message'] = "Invalid password.";
            header("Location: ../pages/login.php");
        }
    } else {
        $_SESSION['error_message'] = "No user found with that username.";
        header("Location: ../pages/login.php");
    }

    $conn->close();
}
?>
