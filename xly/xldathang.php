<?php
session_start();
include("../connect.php");

$matk = $_SESSION['matk'];
$err = '';
$popup_status = '';
$popup_message = '';
//kiểm tra thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $addr_detail = trim($_POST['address_detail'] ?? '');
    $payment = $_POST['payment_method'] ;

    if ($fullname === '' || $phone === '' || $province === '') {
        $err = 'Vui lòng nhập đầy đủ thông tin.';
    }

//lấy thông tin từ giỏ hàng
$cart = [];
$subtotal = 0;   
$sql_cart="Select g.masp, s.tensp, s.gia, g.soluong 
                FROM giohang g 
                JOIN sanpham s ON g.masp = s.masp 
                WHERE g.matk= '$matk' AND g.trangthaigio='Tạm thời'";
$rc=$conn->query($sql_cart);
if($rc && $rc->num_rows > 0){
    while($row=$rc->fetch_assoc()){
        $cart[] = $row;
        $subtotal += $row['gia']*$row['soluong'];
    }
}

//tính vat, tổng tiền, phí ship
    $vat =  round($subtotal*0.1,2);
    $shipFee = ($province === 'Hà Nội') ? 30000 : 50000;
    $grandTotal = $subtotal + $vat + $shipFee;
    if ($err === '' && !empty($cart)) {
        $conn->begin_transaction();
        try {
            date_default_timezone_set('Asia/Ho_Chi_Minh'); // múi giờ Việt Nam
            $today = date('Y-m-d H:i:s');
//thêm vào đơn đăt hàng
            $sql_order = "INSERT INTO dondathang(matk, ngaydat, tongtien, VAT, phivanchuyen, phuongthuctt)
              VALUES ('$matk', '$today', '$grandTotal', '$vat', '$shipFee', '$payment')";
            if ($conn->query($sql_order) === TRUE) {
            $mahd = $conn->insert_id; // Lấy mã đơn hàng tự tăng vừa thêm
            }
// tại mảng cart, lấy từng thông tin trong mảng
            foreach ($cart as $item) {
             $masp = $item['masp'];
            $soluong = $item['soluong'];
            $dongia = $item['gia'];
            $thanhtien = $dongia * $soluong;

            // Thêm chi tiết đơn hàng
            $sql_detail = "INSERT INTO chitietdathang (madon, masp, soluong, dongia, thanhtien)
                   VALUES ('$mahd', '$masp', '$soluong', '$dongia', '$thanhtien')";
            $conn->query($sql_detail);

            // Cập nhật tồn kho từng sản phẩm
            $upd_stock = "UPDATE sanpham 
                  SET soluong = GREATEST(soluong - $soluong, 0) 
                  WHERE masp = '$masp'";
            $conn->query($upd_stock);
    }
            //cập nhật gio hang
            $upd_cart="update giohang set trangthaigio='Đã đặt hàng' where matk= '$matk' and trangthaigio='Tạm thời' ";
            $conn->query($upd_cart);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    window.location.href = '../giohang.php';
});
<?php endif; ?>
</script>

</body>
<?php exit; ?>
</html>
