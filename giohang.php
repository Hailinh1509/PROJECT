<?php
session_start();
include("connect.php");

// Nếu người dùng chưa đăng nhập → chuyển đến login
if (!isset($_SESSION['matk'])) {
    header("Location: login.php");
    exit;
}

$matk = $_SESSION['matk'];

// Khởi tạo session giỏ hàng nếu chưa có
if (!isset($_SESSION['giohang'])) {
    $_SESSION['giohang'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $masp = $_POST['masp'];
    $tensp = $_POST['tensp'];
    $gia = $_POST['gia'];
    $soluong = intval($_POST['soluong']);
    $hinhanh = $_POST['hinhanh'];
    $ngaychonhang = date('Y-m-d');

    // 1️⃣ Cập nhật SESSION
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

    // Kiểm tra xem sản phẩm đã tồn tại trong giỏ DB chưa
    $check_sql = "SELECT * FROM giohang WHERE matk='$matk' AND masp='$masp' AND trangthaigio='Tạm thời'";
    $check_result = mysqli_query($ocon, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        // Nếu có rồi thì cộng dồn số lượng
        $update_sql = "UPDATE giohang 
                       SET soluong = soluong + $soluong 
                       WHERE matk='$matk' AND masp='$masp' AND trangthaigio='Tạm thời'";
        mysqli_query($ocon, $update_sql);
    } else {
        // Nếu chưa có thì thêm mới
        $insert_sql = "INSERT INTO giohang (matk, masp, soluong, ngaychonhang, trangthaigio)
                       VALUES ('$matk', '$masp', '$soluong', '$ngaychonhang', N'Tạm thời')";
        mysqli_query($ocon, $insert_sql);
    }

    header("Location: giohang.php");
    exit;
}

// ------------------- XỬ LÝ CẬP NHẬT -------------------
if (isset($_POST['capnhat'])) {
    foreach ($_POST['soluong'] as $masp => $soluongmoi) {
        $soluongmoi = max(1, intval($soluongmoi));
        foreach ($_SESSION['giohang'] as &$sp) {
            if ($sp['masp'] === $masp) {
                $sp['soluong'] = $soluongmoi;
            }
        }
        unset($sp);
        mysqli_query($ocon, "UPDATE giohang 
                             SET soluong=$soluongmoi 
                             WHERE matk='$matk' AND masp='$masp' AND trangthaigio='Tạm thời'");
    }
    header("Location: giohang.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Giỏ hàng của bạn</title>
<style>
body { margin: 40px auto; width: 80%;}
table {width: 100%; border-collapse: collapse;}
th, td {border: 1px solid #ccc; padding: 10px; text-align: center;}
th {background-color: #3498db; color: white;}
img {width: 100px; border-radius: 8px;}
.btn {padding: 8px 14px; background-color: #3498db; border: none; color: white; border-radius: 5px; cursor: pointer;}
.btn:hover {background-color: #2980b9;}
.delete-btn {background-color: #e74c3c;}
.delete-btn:hover {background-color: #c0392b;}
.total {text-align: right; margin-top: 15px; font-size: 18px; font-weight: bold;}
.back-btn {margin-top: 20px; padding: 10px 20px; background-color: #7f8c8d; border: none; color: white; border-radius: 5px; cursor: pointer;}
.back-btn:hover {background-color: #636e72;}
</style>
</head>
<body>

<h1>🛒 Giỏ hàng của bạn</h1>

<?php if (empty($_SESSION['giohang'])): ?>
    <p>Giỏ hàng hiện đang trống!</p>
    <button class="back-btn" onclick="window.location.href='hienthi.php'">⬅ Tiếp tục mua hàng</button>
<?php else: ?>
<form method="POST" action="giohang.php">
    <table>
        <tr>
            <th>Ảnh</th>
            <th>Mã SP</th>
            <th>Tên sản phẩm</th>
            <th>Giá (VNĐ)</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
            <th>Thao tác</th>
        </tr>
        <?php
        $tongtien = 0;
        foreach ($_SESSION['giohang'] as $sp):
            $thanhtien = $sp['gia'] * $sp['soluong'];
            $tongtien += $thanhtien;
        ?>
        <tr>
            <td><img src="<?php echo $sp['hinhanh']; ?>" alt="<?php echo $sp['tensp']; ?>"></td>
            <td><?php echo $sp['masp']; ?></td>
            <td><?php echo $sp['tensp']; ?></td>
            <td><?php echo number_format($sp['gia'], 0, ',', '.'); ?></td>
            <td>
                <input type="number" name="soluong[<?php echo $sp['masp']; ?>]" value="<?php echo $sp['soluong']; ?>" min="1" style="width: 70px;">
            </td>
            <td><?php echo number_format($thanhtien, 0, ',', '.'); ?></td>
            <td>
                <a href="xly/xlXoa.php?masp=<?php echo $sp['masp']; ?>"  
                class="btn delete-btn"
                onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này không?');">
                Xóa
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p class="total">Tổng tiền: <?php echo number_format($tongtien, 0, ',', '.'); ?> VNĐ</p>

    <button type="submit" name="capnhat" class="btn">Cập nhật giỏ hàng</button>
    <button type="button" class="back-btn" onclick="window.location.href='hienthi.php'">⬅ Tiếp tục mua hàng</button>
</form>
<?php endif; ?>

</body>
</html>
