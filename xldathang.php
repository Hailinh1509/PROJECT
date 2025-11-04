<?php
session_start();
include("../connect.php");
$matk = $_SESSION['matk'];
$err = '';
#$placed = '';
/* XỬ LÝ SUBMIT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullname   = trim($_POST['fullname'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $province   = trim($_POST['province'] ?? '');
    $addr_detail= trim($_POST['address_detail'] ?? '');  // ✅ địa chỉ chi tiết
    $payment = $_POST['payment_method'] ?? 'COD';
    if ($fullname === '' || $phone === '' || $province === '') {
        $err = 'Vui lòng nhập Họ tên, SĐT và chọn Tỉnh/Thành.';
    } else {
        $shipFee    = calcShipFee($province);
        $grandTotal = (int)$subtotal + (int)$vat + (int)$shipFee;

        if ($err === '') {
            // ✅ thay doi trang thai "tam thoi"->"da dat hang"

           $upd = $conn->prepare("UPDATE giohang SET trangthaigio='Đã đặt hàng' WHERE matk=? AND trangthaigio='Tạm thời'");
            $upd->bind_param('s', $matk);
            $upd->execute();
            $upd->close();
            // làm trống giỏ trong trang hiện tại
            $cart = [];
            $subtotal = 0;
            $vat = 0;
        }
    }
     $conn->begin_transaction();
        try {
            $payment = $_POST['payment_method'] ?? 'COD';
            $today   = date('Y-m-d H:i:s');

            // 1️⃣ Thêm vào bảng dondathang
            $sql_order = "INSERT INTO dondathang (matk, ngaydat, tongtien, VAT, phivanchuyen, phuongthucthanhtoan)
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->bind_param('ssiiis', $matk, $today, $grandTotal, $vat, $shipFee, $payment);
            $stmt_order->execute();
            $madon = $stmt_order->insert_id;
            $stmt_order->close();

            // 2️⃣ Thêm từng sản phẩm vào chitietdathang
            $sql_detail = "INSERT INTO chitietdathang (madon, masp, soluong, dongia, thanhtien)
                           VALUES (?, ?, ?, ?, ?)";
            $stmt_detail = $conn->prepare($sql_detail);

            foreach ($cart as $item) {
                $thanhtien = $item['qty'] * $item['price'];
                $stmt_detail->bind_param('isiii', $madon, $item['id'], $item['qty'], $item['price'], $thanhtien);
                $stmt_detail->execute();

                // 3️⃣ Giảm hàng tồn trong bảng sanpham
                $sql_update_stock = "UPDATE sanpham 
                                     SET soluong = GREATEST(soluong - ?, 0)
                                     WHERE masp = ?";
                $stmt_stock = $conn->prepare($sql_update_stock);
                $stmt_stock->bind_param('is', $item['qty'], $item['id']);
                $stmt_stock->execute();
                $stmt_stock->close();
            }
            $stmt_detail->close();
}
       


    // ✅ Cờ thông báo popup
    $popup_status = 'success';
    $popup_message = 'Đặt hàng thành công! Cảm ơn bạn đã mua sắm.';
} else {
    $popup_status = 'error';
    $popup_message = 'Đặt hàng không thành công! Vui lòng thử lại.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Thông báo đặt hàng</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>

    </style>
</head>
<body>
     <?php
    $status = $_GET['status'] ?? '';
    if ($status === 'success') {
        echo "
        <script>
            Swal.fire({
                title: '🎉 Đặt hàng thành công!',
                text: 'Cảm ơn bạn đã mua hàng tại cửa hàng của chúng tôi!',
                icon: 'success',
                confirmButtonText: 'Về trang chủ',
                timer: 3000,
                timerProgressBar: true
            }).then(() => {
                window.location.href = 'index.php';
            });
        </script>
        ";
    } elseif ($status === 'error') {
        echo "
        <script>
            Swal.fire({
                title: 'Lỗi!',
                text: 'Có lỗi xảy ra khi đặt hàng. Vui lòng thử lại sau!',
                icon: 'error',
                confirmButtonText: 'Thử lại'
            }).then(() => {
                window.location.href = 'giohang.php';
            });
        </script>
        ";
    } else {
        // Nếu truy cập trực tiếp mà không có status
        header("Location: ../hienthi.php");
        exit;
    }
?>
</body>
</html>