<?php
include("connect.php");

// Lấy danh sách loại sản phẩm
$sql_loaisp = "SELECT * FROM loaisp";
$res_loaisp = $ocon->query($sql_loaisp);

// Phân trang
$limit = 4;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Lọc theo loại nếu có
$maloai = isset($_GET['maloai']) ? $_GET['maloai'] : '';
if (!empty($maloai)) {
    $sql_sp = "SELECT * FROM sanpham WHERE maloai = '$maloai' LIMIT $start, $limit";
    $sql_count = "SELECT COUNT(*) as total FROM sanpham WHERE maloai = '$maloai'";
} else {
    $sql_sp = "SELECT * FROM sanpham LIMIT $start, $limit";
    $sql_count = "SELECT COUNT(*) as total FROM sanpham";
}

$res_sp = $ocon->query($sql_sp);
$total_sp = $ocon->query($sql_count)->fetch_assoc()['total'];
$total_page = ceil($total_sp / $limit);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách sản phẩm</title>
    <style>
        body {
            font-family: Roboto, sans-serif;
            color: #000;
            font-size: 1.1em;
        }
        .container {
            display: flex;
            border: 1px solid #333;
        }
        .danhmucloaisp {
            width: 20%;
            border-right: 1px solid #333;
            padding: 10px;
        }
        .danhmucsp {
            width: 80%;
            padding: 10px;
        }
        .vungsp {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        .product {
            border: 1px solid #333;
            text-align: center;
            padding: 5px;
            height: 380px;
            transition: all 0.3s;
        }
        .product:hover {
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
            transform: translateY(-5px);
            border-color: #ffd166;
        }
        .product img {
            width: 100%;
            height: 80%;
            object-fit: cover;
            border-bottom: 1px solid #ccc;
        }
        .phantrang {
            text-align: center;
            margin-top: 40px;
        }
        .phantrang a {
            margin: 0 5px;
            text-decoration: none;
            color: #2980b9;
        }
        .phantrang a:hover {
            text-decoration: underline;
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
        .giohang {
            background: #ffe082;
        }
        .dangnhap {
            background: #fde4ec;
        }
    </style>
</head>
<body>

<div class="topbar">
    <a href="xly/ktralogin.php?next=../giohang.php" class="giohang" >🛒 Xem giỏ hàng</a>
    <a href="#" class="dangnhap">🔐 Đăng ký / Đăng nhập</a>
</div>

<div class="container">
    <!-- Danh mục loại sản phẩm -->
    <div class="danhmucloaisp">
        <h3>DANH MỤC LOẠI SẢN PHẨM</h3>
        <hr>
        <?php
        $maloai_chon = isset($_GET['maloai']) ? $_GET['maloai'] : '';
        echo "<h4><a href='hienthi.php' style='text-decoration:none; color:" . 
             (empty($maloai_chon) ? "red" : "black") . ";'>Tất cả sản phẩm</a></h4>";

        if ($res_loaisp && $res_loaisp->num_rows > 0) {
            while ($row = $res_loaisp->fetch_assoc()) {
                $mau = ($maloai_chon == $row['maloai']) ? "red" : "black";
                echo "<h4><a href='?maloai=" . $row['maloai'] . "' style='text-decoration:none; color:$mau;'>" . $row['tenloai'] . "</a></h4>";
            }
        }
        ?>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="danhmucsp">
        <h3 style="text-align:center;">DANH MỤC SẢN PHẨM</h3>
        <div class="vungsp">
        <?php
        if ($res_sp && $res_sp->num_rows > 0) {
            while ($sp = $res_sp->fetch_assoc()) {
                echo "
                <div class='product'>
                    <a href='detail.php?masp=" . $sp['masp'] . "' style='text-decoration:none; color:black;'>
                        <img src='" . $sp['hinhanh'] . "' alt='" . $sp['tensp'] . "'>
                        <strong>" . $sp['tensp'] . "</strong><br>
                        Số lượng: " . $sp['soluong'] . "<br>
                        Giá bán: " . number_format($sp['gia'], 0, ',', '.') . " VNĐ
                    </a>
                </div>";
            }
        } else {
            echo "<p>Không có sản phẩm!</p>";
        }
        ?>
        </div>

        <!-- Phân trang -->
        <div class="phantrang">
            <?php
            for ($i = 1; $i <= $total_page; $i++) {
                if (!empty($maloai)) {
                    echo "<a href='?maloai=$maloai&page=$i'>$i</a>";
                } else {
                    echo "<a href='?page=$i'>$i</a>";
                }
            }
            ?>
        </div>
    </div>
</div>

</body>
</html>
<?php $ocon->close(); ?>
