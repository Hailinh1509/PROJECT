<?php
session_start();

// Lấy đường dẫn cần quay lại sau khi kiểm tra
$next = isset($_GET['next']) ? $_GET['next'] : '../hienthi.php';

if (!isset($_SESSION['matk'])) {
    // Nếu chưa đăng nhập → lưu lại URL để quay về sau khi login
    $_SESSION['redirect_url'] = $next;
    session_write_close();

    echo "<script>
            alert('Vui lòng đăng nhập để tiếp tục!');
            window.location.href = '../taikhoan.php';
          </script>";
    exit;
} else {
    header("Location: $next");
    exit;
}
?>
