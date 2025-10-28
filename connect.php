<?php
$host = "127.0.0.1";
$username = "root";
$password = "";     
$dbname = "qlmypham";

// Tạo kết nối và đặt tên biến là $conn (phải nhất quán)
$ocon = new mysqli($host, $username, $password, $dbname);
$ocon->set_charset("utf8");

// Kiểm tra kết nối
if ($ocon->connect_error) {
    die("Kết nối thất bại: " . $ocon->connect_error);
}

?>