<?php
session_start();

// Nếu người dùng chưa đăng nhập
if (!isset($_SESSION['matk'])) {
    // Lưu lại URL hiện tại để sau khi login xong quay về
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    echo "<script>
            alert('Vui lòng đăng nhập để tiếp tục!');
            window.location.href = '../login.php';
          </script>";
    exit;
}
?>
