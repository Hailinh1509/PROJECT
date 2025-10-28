<?php
session_start();
include('connect.php');

// ====== LẤY THÔNG TIN SẢN PHẨM ======
$masp = isset($_GET['masp']) ? $_GET['masp'] : '';

$sql = "SELECT sp.*, l.tenloai 
        FROM sanpham sp 
        JOIN loaisp l ON sp.maloai = l.maloai 
        WHERE sp.masp = '$masp'";
$result = $ocon->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    echo "<h2>Không tìm thấy sản phẩm!</h2>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    include('xly/ktralogin.php'); //  kiểm tra đăng nhập

    $matk = $_SESSION['matk'];
    $masp = $_POST['masp'];
    $tensp = $_POST['tensp'];
    $gia = $_POST['gia'];
    $soluong = $_POST['soluong'];
    $hinhanh = $_POST['hinhanh'];
    $ngaychonhang = date('Y-m-d');
    $trangthai = 'Tạm thời';

    //  Lưu vào SESSION
    if (!isset($_SESSION['giohang'])) $_SESSION['giohang'] = [];

    $found = false;
    foreach ($_SESSION['giohang'] as &$sp) {
        if ($sp['masp'] === $masp) {
            $sp['soluong'] += $soluong;
            $found = true;
            break;
        }
    }
    unset($sp);

    if (!$found) {
        $_SESSION['giohang'][] = [
            'masp' => $masp,
            'tensp' => $tensp,
            'gia' => $gia,
            'soluong' => $soluong,
            'hinhanh' => $hinhanh
        ];
    }

    // Lưu vào DATABASE
    $check = mysqli_query($ocon, "SELECT * FROM giohang WHERE matk='$matk' AND masp='$masp' AND trangthaigio='Tạm thời'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($ocon, "UPDATE giohang 
                             SET soluong = soluong + $soluong 
                             WHERE matk='$matk' AND masp='$masp' AND trangthaigio='Tạm thời'");
    } else {
        mysqli_query($ocon, "INSERT INTO giohang (matk, masp, tensp, gia, soluong, hinhanh, ngaychonhang, trangthaigio)
                             VALUES ('$matk', '$masp', '$tensp', '$gia', '$soluong', '$hinhanh', '$ngaychonhang', '$trangthai')");
    }

    echo "<script>
            alert('Đã thêm sản phẩm vào giỏ hàng!');
            window.location.href = 'giohang.php';
          </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết sản phẩm</title>
    <style>
        body {
            margin: 50px auto;
            width: 80%;
        }
        .topbar {
            text-align: right;
            margin: 10px;
        }
        .topbar a {
            padding: 8px 14px;
            border-radius: 5px;
            text-decoration: none;
            margin-left: 10px;
            color: black;
        }
        .product-container {
            display: flex;
            align-items: flex-start;
            gap: 50px;
        }
        .product-container img {
            width: 300px;
            height: auto;
            border-radius: 10px;
            box-shadow: 0px 0px 8px #ccc;
        }
        .product-info {
            max-width: 600px;
        }
        .product-info h2 {
            color: #c0392b;
        }
        .product-info p {
            font-size: 16px;
            margin: 6px 0;
        }
        .price {
            color: #27ae60;
            font-weight: bold;
            font-size: 18px;
        }
        .btn {
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #3498db;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .back-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #7f8c8d;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .back-btn:hover {
            background-color: #636e72;
        }
        input[type="number"] {
            width: 80px;
            padding: 6px;
            font-size: 15px;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <a href="xly/ktraDangNhap.php?next=../giohang.php">🛒 Xem giỏ hàng</a>
        <?php if (isset($_SESSION['matk'])): ?>
            <a href="logout.php">🚪 Đăng xuất</a>
        <?php else: ?>
            <a href="login.php">🔐 Đăng nhập / Đăng ký</a>
        <?php endif; ?>
    </div>

    <h1>Chi tiết sản phẩm</h1>

    <div class="product-container">
        <div class="product-image">
            <img src="<?php echo $row['hinhanh']; ?>" alt="<?php echo $row['tensp']; ?>">
        </div>

        <div class="product-info">
            <h2><?php echo $row['tensp']; ?></h2>
            <p><strong>Mã sản phẩm:</strong> <?php echo $row['masp']; ?></p>
            <p><strong>Loại sản phẩm:</strong> <?php echo $row['tenloai']; ?></p>
            <p><strong>Giá:</strong> 
                <span class="price"><?php echo number_format($row['gia'], 0, ',', '.'); ?> VNĐ</span>
            </p>

            <form action="" method="POST">
                <input type="hidden" name="masp" value="<?php echo $row['masp']; ?>">
                <input type="hidden" name="tensp" value="<?php echo $row['tensp']; ?>">
                <input type="hidden" name="gia" value="<?php echo $row['gia']; ?>">
                <input type="hidden" name="hinhanh" value="<?php echo $row['hinhanh']; ?>">

                <label for="quantity"><strong>Số lượng:</strong></label>
                <input type="number" id="quantity" name="soluong" value="1" min="1" max="<?php echo $row['soluong']; ?>">
                <span>(Còn <?php echo $row['soluong']; ?> sản phẩm)</span>
                <br>
                <button type="submit" class="btn" name="add_to_cart">Thêm vào giỏ hàng</button>
                <button type="button" class="back-btn" onclick="window.location.href='hienthi.php'">Quay về trang sản phẩm</button>
            </form>
        </div>
    </div>
</body>
</html>
