<?php
session_start();
include("../connect.php");

$matk = $_SESSION['matk'];
$err = '';
$popup_status = '';
$popup_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $addr_detail = trim($_POST['address_detail'] ?? '');
    $payment = $_POST['payment_method'] ;

    if ($fullname === '' || $phone === '' || $province === '') {
        $err = 'Vui lòng nhập đầy đủ thông tin.';
    }

    // Lấy giỏ hàng
    $sql_cart = "SELECT g.masp, s.tensp, s.gia, g.soluong 
                 FROM giohang g 
                 JOIN sanpham s ON g.masp = s.masp 
                 WHERE g.matk=? AND g.trangthaigio='Tạm thời'";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("s", $matk);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();

    $cart = [];
    $subtotal = 0;
    while ($row = $result_cart->fetch_assoc()) {
        $cart[] = $row;
        $subtotal += $row['gia'] * $row['soluong'];
    }
    $stmt_cart->close();
    $vat =  round($subtotal*0.1,2);
    $shipFee = ($province === 'Hà Nội') ? 30000 : 50000;
    $grandTotal = $subtotal + $vat + $shipFee;
    if ($err === '' && !empty($cart)) {
        $conn->begin_transaction();
        try {
            $today = date('Y-m-d H:i:s');

            // 1 Thêm đơn đặt hàng
            $sql_order = "INSERT INTO dondathang (matk, ngaydat, tongtien, VAT, phivanchuyen, phuongthuctt)
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->bind_param('ssddds', $matk, $today, $grandTotal, $vat, $shipFee, $payment);
            $stmt_order->execute();
            $madon = $stmt_order->insert_id;
            $stmt_order->close();

            // 2 Thêm chi tiết đơn
            $sql_detail = "INSERT INTO chitietdathang (madon, masp, soluong, dongia, thanhtien)
                           VALUES (?, ?, ?, ?, ?)";
            $stmt_detail = $conn->prepare($sql_detail);

            foreach ($cart as $item) {
                $thanhtien = $item['gia'] * $item['soluong'];
                $stmt_detail->bind_param('isiii', $madon, $item['masp'], $item['soluong'], $item['gia'], $thanhtien);
                $stmt_detail->execute();
                // 3 Cập nhật tồn kho
                $sql_update_stock = "UPDATE sanpham SET soluong = GREATEST(soluong - ?, 0) WHERE masp = ?";
                $stmt_stock = $conn->prepare($sql_update_stock);
                $stmt_stock->bind_param('is', $item['soluong'], $item['masp']);
                $stmt_stock->execute();
                $stmt_stock->close();
            }
            $stmt_detail->close();

            // 4 Cập nhật trạng thái giỏ hàng
            $upd = $conn->prepare("UPDATE giohang SET trangthaigio='Đã đặt hàng' WHERE matk=? AND trangthaigio='Tạm thời'");
            $upd->bind_param('s', $matk);
            $upd->execute();
            $upd->close();

            $conn->commit();
            $popup_status = 'success';
            $popup_message = 'Đặt hàng thành công! Cảm ơn bạn đã mua sắm.';
        } catch (Exception $e) {
            $conn->rollback();
            $popup_status = 'error';
            $popup_message = 'Lỗi xử lý đơn hàng: ' . $e->getMessage();
        }
    } else {
        $popup_status = 'error';
        $popup_message = 'Không thể đặt hàng. Vui lòng kiểm tra lại thông tin.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả đặt hàng</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    
<script>
<?php if ($popup_status === 'success'): ?>
Swal.fire({
    title: '🎉 Đặt hàng thành công!',
    text: '<?= $popup_message ?>',
    icon: 'success',
    confirmButtonText: 'Về trang chủ',
    timer: 3000,
    timerProgressBar: true
}).then(() => {
    window.location.href = '../hienthi.php';
});
<?php elseif ($popup_status === 'error'): ?>
Swal.fire({
    title: 'Lỗi!',
    text: '<?= $popup_message ?>',
    icon: 'error',
    confirmButtonText: 'Thử lại'
}).then(() => {
    window.location.href = 'giohang.php';
});
<?php endif; ?>
</script>

</body>
</html>
