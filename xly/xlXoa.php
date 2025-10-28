<?php
session_start();

if (isset($_GET['masp'])) {
    $masp = $_GET['masp'];

    if (isset($_SESSION['giohang'])) {
        foreach ($_SESSION['giohang'] as $key => $sp) {
            if ($sp['masp'] === $masp) {
                unset($_SESSION['giohang'][$key]);
                break;
            }
        }
        $_SESSION['giohang'] = array_values($_SESSION['giohang']);
    }
}

header("Location: ../giohang.php");
exit;
?>
