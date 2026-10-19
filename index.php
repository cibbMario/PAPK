<?php
// index.php
session_start();

if (!empty($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } else {
        header('Location: user/dashboard.php');
        exit;
    }
}

header('Location: login.php');
exit;
?>
