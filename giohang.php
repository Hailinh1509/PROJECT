<?php
session_start();
include('connect.php');

if (!isset($_SESSION['matk'])) {
    echo "<script>alert('Vui lòng đăng nhập để xem giỏ hàng!');window.location='taikhoan.php';</script>";
    exit;
}

$matk = $_SESSION['matk'];

// Cập nhật số lượng
if (isset($_POST['update_cart'])) {
    foreach ($_POST['soluong'] as $masp => $soluong) {
        $conn->query("UPDATE giohang 
                      SET soluong='$soluong' 
                      WHERE matk='$matk' AND masp='$masp' AND trangthaigio='Tạm thời'");
    }
    echo "<script>alert('Cập nhật giỏ hàng thành công!');window.location='giohang.php';</script>";
    exit;
}

//Xóa
if (isset($_GET['xoa'])) {
    $masp = $_GET['xoa'];
    $conn->query("DELETE FROM giohang 
                  WHERE matk='$matk' AND masp='$masp' AND trangthaigio='Tạm thời'");
    echo "<script>alert('Đã xóa sản phẩm khỏi giỏ hàng!');window.location='giohang.php';</script>";
    exit;
}

//lấy DL trong giỏ hàng
$sql = "SELECT g.*, s.tensp, s.gia, s.hinhanh 
        FROM giohang g 
        JOIN sanpham s ON g.masp = s.masp 
        WHERE g.matk='$matk' AND g.trangthaigio='Tạm thời'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Giỏ hàng của bạn</title>
<style>
body { width: 90%; margin: 40px auto; }
table { border-collapse: collapse; width: 100%; text-align: center; }
th, td { border: 1px solid #ccc; padding: 10px; }
th { background-color: #f8f8f8; }
img { width: 70px; height: auto; border-radius: 6px; }
input[type="number"] { width: 60px; text-align: center; }
a.btn-delete { color: red; text-decoration: none; font-weight: bold; }
button, input[type=submit] { padding: 10px 15px; border: none; border-radius: 5px; color: white; cursor: pointer; }
.update { background-color: #3498db; }
.checkout { background-color: #27ae60; margin-left: 10px; }
.back { background-color: #7f8c8d; margin-left: 10px; }
</style>
</head>
<body>

<h1>🛒 Giỏ hàng của bạn</h1>

<form method="POST">
<table>
<tr>
    <th>Hình ảnh</th>
    <th>Tên sản phẩm</th>
    <th>Giá</th>
    <th>Số lượng</th>
    <th>Thành tiền</th>
    <th>Ghi Chú</th>
</tr>

<?php
$tong = 0;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $thanhtien = $row['gia'] * $row['soluong'];
        $tong += $thanhtien;
        echo "<tr>
                <td><img src='{$row['hinhanh']}'></td>
                <td>{$row['tensp']}</td>
                <td>" . number_format($row['gia'], 0, ',', '.') . " VNĐ</td>
                <td><input type='number' name='soluong[{$row['masp']}]' value='{$row['soluong']}' min='1'></td>
                <td>" . number_format($thanhtien, 0, ',', '.') . " VNĐ</td>
                <td><a href='giohang.php?xoa={$row['masp']}' class='btn-delete'>Xóa</a></td>
              </tr>";
    }
    echo "<tr>
            <td colspan='4' align='right'><b>Tổng cộng:</b></td>
            <td colspan='2'><b>" . number_format($tong, 0, ',', '.') . " VNĐ</b></td>
          </tr>";
} else {
    echo "<tr><td colspan='6'>Giỏ hàng trống!</td></tr>";
}
?>
</table>
<br>
<input type="submit" name="update_cart" value="Cập nhật giỏ hàng" class="update">
<<button type="button" class="checkout" onclick="window.location.href='thanhtoan.php'">Đặt hàng</button>
<button type="button" class="back" onclick="window.location.href='hienthi.php'">Tiếp tục mua hàng</button>
</form>

</body>
</html>
