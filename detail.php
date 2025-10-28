<?php
session_start();
include('connect.php');

// ===== LẤY THÔNG TIN SẢN PHẨM =====
$masp = isset($_GET['masp']) ? $_GET['masp'] : '';

$sql = "SELECT sp.*, l.tenloai 
        FROM sanpham sp 
        JOIN loaisp l ON sp.maloai = l.maloai 
        WHERE sp.masp = '$masp'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    echo "<h2>Không tìm thấy sản phẩm!</h2>";
    exit;
}

// ===== XỬ LÝ THÊM VÀO GIỎ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['matk'])) {
        echo "<script>alert('Bạn cần đăng nhập để thêm sản phẩm vào giỏ!');window.location='taikhoan.php';</script>";
        exit;
    }

    $matk = $_SESSION['matk'];
    $masp = $_POST['masp'];
    $soluong = (int)$_POST['soluong'];
    $ngaychonhang = date('Y-m-d');
    $trangthai = 'Tạm thời';

    // kiểm tra có sản phẩm đó trong giỏ chưa
    $check = $conn->query("SELECT * FROM giohang WHERE matk='$matk' AND masp='$masp' AND trangthaigio='Tạm thời'");
    if ($check->num_rows > 0) {
        $conn->query("UPDATE giohang SET soluong = soluong + $soluong WHERE matk='$matk' AND masp='$masp' AND trangthaigio='Tạm thời'");
    } else {
        $conn->query("INSERT INTO giohang (matk, masp, ngaychonhang, soluong, trangthaigio)
                      VALUES ('$matk', '$masp', '$ngaychonhang', '$soluong', '$trangthai')");
    }

    echo "<script>alert('Đã thêm sản phẩm vào giỏ hàng!');window.location='giohang.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết sản phẩm</title>
    <style>
        body { margin: 40px auto; width: 80%; font-family: Arial; }
        .topbar { text-align: right; margin: 10px; }
        .topbar a { padding: 8px 14px; text-decoration: none; color: black; border: 1px solid #ccc; border-radius: 5px; }
        .product-container { display: flex; align-items: flex-start; gap: 40px; margin-top: 20px; }
        .product-container img { width: 300px; height: auto; border-radius: 10px; box-shadow: 0px 0px 8px #ccc; }
        .product-info h2 { color: #c0392b; }
        .price { color: #27ae60; font-weight: bold; font-size: 18px; }
        input[type="number"] { width: 80px; padding: 6px; font-size: 15px; }
        button { padding: 10px 18px; border: none; border-radius: 5px; cursor: pointer; color: white; margin-top: 10px; }
        .btn-add { background: #3498db; }
        .btn-back { background: #7f8c8d; margin-left: 10px; }
        .btn-add:hover { background: #2980b9; }
        .btn-back:hover { background: #636e72; }
    </style>
</head>
<body>
    <div class="topbar">
        <a href="giohang.php">🛒 Xem giỏ hàng</a>
        <?php if (isset($_SESSION['matk'])): ?>
            <a href="taikhoan.php">🚪 Đăng xuất</a>
        <?php else: ?>
            <a href="taikhoan.php">🔐 Đăng nhập / Đăng ký</a>
        <?php endif; ?>
    </div>

    <h1>Chi tiết sản phẩm</h1>

    <div class="product-container">
        <div>
            <img src="<?php echo $row['hinhanh']; ?>" alt="<?php echo $row['tensp']; ?>">
        </div>
        <div class="product-info">
            <h2><?php echo $row['tensp']; ?></h2>
            <p><b>Mã sản phẩm:</b> <?php echo $row['masp']; ?></p>
            <p><b>Loại sản phẩm:</b> <?php echo $row['tenloai']; ?></p>
            <p><b>Giá:</b> <span class="price"><?php echo $row['gia']; ?> VNĐ</span></p>

            <form action="" method="POST">
                <input type="hidden" name="masp" value="<?php echo $row['masp']; ?>">
                <label><b>Số lượng:</b></label>
                <input type="number" name="soluong" value="1" min="1" max="<?php echo $row['soluong']; ?>">
                <p>(Còn <?php echo $row['soluong']; ?> sản phẩm)</p>
                <button type="submit" name="add_to_cart" class="btn-add">Thêm vào giỏ hàng</button>
                <button type="button" class="btn-back" onclick="window.location.href='hienthi.php'">Quay lại</button>
            </form>
        </div>
    </div>
</body>
</html>
