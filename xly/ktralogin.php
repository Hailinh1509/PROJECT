<?php
session_start();

// Kiểm tra đã đăng nhập chưa
if (!isset($_SESSION['matk'])) {
    // Chưa đăng nhập → chuyển về trang login
    echo "<script>
            alert('Vui lòng đăng nhập để tiếp tục!');
            window.location.href = '../taikhoan.php';
          </script>";
    exit;
}

// Nếu đã đăng nhập → chuyển thẳng vào giỏ hàng
header("Location: ../giohang.php");
exit;
?>
