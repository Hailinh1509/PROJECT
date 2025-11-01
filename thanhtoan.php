<?php
/***********************
 * THANHTOAN.PHP (v2)
 * - Đọc giỏ từ DB (giohang + sanpham; trangthaigio='Tạm thời')
 * - Dropdown tỉnh/thành (34 tỉnh) + tính ship (HN 30k, tỉnh khác 50k)
 * - Thêm ô "Địa chỉ nhận hàng (chi tiết)"
 * - Sau khi thanh toán thành công: XÓA các dòng giỏ hàng đã thanh toán
 ***********************/
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => '/']);
    session_start();
}
$err = '';
$placed = '';

require_once __DIR__ . '/connect.php'; // tạo $conn (mysqli)
$matk = $_SESSION['matk'] ?? null;
if (!$matk) {
    header('Location: taikhoan.php?err=Vui+long+dang+nhap+de+thanh+toan');
    exit;
}

/* 1) LẤY GIỎ HÀNG TỪ DB */
$cart = [];
$subtotal = 0;

$sql = "
  SELECT g.masp       AS id,
         g.soluong    AS qty,
         s.tensp      AS name,
         s.gia        AS price,
         s.hinhanh    AS image
  FROM giohang g
  JOIN sanpham s ON s.masp = g.masp
  WHERE g.matk = ?
    AND g.trangthaigio = 'Tạm thời'
";
if ($st = $conn->prepare($sql)) {
    $st->bind_param('s', $matk);
    $st->execute();
    $rs = $st->get_result();
    while ($r = $rs->fetch_assoc()) {
        $item = [
            'id'    => (int)$r['id'],
            'name'  => $r['name'],
            'price' => (int)$r['price'],
            'qty'   => (int)$r['qty'],
            'image' => $r['image'] ?: 'images/noimg.png',
        ];
        $subtotal += $item['price'] * $item['qty'];
        $cart[] = $item;
    }
    $st->close();
}

if (empty($cart)) {
    header('Location: giohang.php?msg=Giỏ+hàng+rỗng');
    exit;
}

/* 2) CẤU HÌNH TỈNH/SHIP/VAT */
$PROVINCES = [
  'Hà Nội','Hải Phòng','Bắc Ninh','Hưng Yên','Hải Dương','Quảng Ninh','Thái Nguyên','Vĩnh Phúc',
  'Phú Thọ','Bắc Giang','Lạng Sơn','Cao Bằng','Tuyên Quang','Yên Bái','Lào Cai','Điện Biên',
  'Sơn La','Hòa Bình','Ninh Bình','Nam Định','Thái Bình','Thanh Hóa','Nghệ An','Hà Tĩnh',
  'Quảng Bình','Quảng Trị','Thừa Thiên Huế','Đà Nẵng','Quảng Nam','Quảng Ngãi','Bình Định',
  'Phú Yên','Khánh Hòa','Ninh Thuận'
]; // 34 tỉnh/thành

function calcShipFee(string $province): int {
    return (trim($province) === 'Hà Nội') ? 30000 : 50000;
}
$vat = (int) round($subtotal * 0.10);

/* 3) XỬ LÝ SUBMIT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullname   = trim($_POST['fullname'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $province   = trim($_POST['province'] ?? '');
    $addr_detail= trim($_POST['address_detail'] ?? '');  // ✅ địa chỉ chi tiết

    if ($fullname === '' || $phone === '' || $province === '') {
        $err = 'Vui lòng nhập Họ tên, SĐT và chọn Tỉnh/Thành.';
    } else {
        $shipFee    = calcShipFee($province);
        $grandTotal = (int)$subtotal + (int)$vat + (int)$shipFee;

        // ====== TODO: LƯU ĐƠN HÀNG (nếu có bảng donhang/chitietdonhang) ======
        // Ví dụ mẫu (bạn sửa tên bảng/field cho khớp rồi bỏ comment):
        /*
        $conn->begin_transaction();
        try {
            $pm = $_POST['payment_method'] ?? 'COD';
            $sqlOrder = "INSERT INTO donhang (matk, hoten, sdt, province, diachi, tamtinh, vat, ship, tongtien, payment_method, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $s1 = $conn->prepare($sqlOrder);
            $s1->bind_param('ssssiiiis', $matk, $fullname, $phone, $province, $addr_detail, $subtotal, $vat, $shipFee, $grandTotal, $pm);
            $s1->execute();
            $orderId = $s1->insert_id;
            $s1->close();

            $sqlItem = "INSERT INTO chitietdonhang (madh, masp, tensp, gia, soluong) VALUES (?, ?, ?, ?, ?)";
            $s2 = $conn->prepare($sqlItem);
            foreach ($cart as $it) {
                $s2->bind_param('iisii', $orderId, $it['id'], $it['name'], $it['price'], $it['qty']);
                $s2->execute();
            }
            $s2->close();
        } catch (\Throwable $e) {
            $conn->rollback();
            $err = 'Có lỗi khi lưu đơn: ' . $e->getMessage();
        }
        */
        // =====================================================================

        if ($err === '') {
            // ✅ XÓA HẾT CÁC DÒNG GIỎ HÀNG ĐÃ THANH TOÁN
            $del = $conn->prepare("DELETE FROM giohang WHERE matk=? AND trangthaigio='Tạm thời'");
            $del->bind_param('s', $matk);
            $del->execute();
            $del->close();

            // làm trống giỏ trong trang hiện tại
            $cart = [];
            $subtotal = 0;
            $vat = 0;

            $placed = 'Đặt hàng thành công! Cảm ơn bạn đã mua sắm.';
            // Có thể redirect sang trang cảm ơn:
            // header("Location: camon.php"); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thanh toán</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
  body{background:#f6f7fb;}
  .container{max-width:1080px;}
  .order-summary,.section-card{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.06);}
  .section-card{padding:18px;}
  .product-img{width:52px;height:52px;object-fit:cover;border-radius:8px;border:1px solid #eee;}
  .totals-table td, .totals-table th{padding:.5rem .75rem;}
  .btn-primary{border-radius:10px;padding:.6rem 1.1rem;}
  .text-muted-2{color:#667085;}
</style>
</head>
<body>
<div class="container my-4">
  <h2 class="mb-3">Thanh toán</h2>

  <?php if (!empty($err)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>
  <?php if (!empty($placed)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($placed) ?></div>
  <?php endif; ?>

  <?php if (!empty($cart)): ?>
  <!-- SẢN PHẨM TRONG ĐƠN -->
  <div class="section-card mb-4">
    <h5 class="mb-3">Sản phẩm trong đơn</h5>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Hình</th>
            <th>Tên sản phẩm</th>
            <th class="text-end">Giá</th>
            <th class="text-center">SL</th>
            <th class="text-end">Thành tiền</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($cart as $it): ?>
          <tr>
            <td><img class="product-img" src="<?= htmlspecialchars($it['image']) ?>" alt=""></td>
            <td><?= htmlspecialchars($it['name']) ?></td>
            <td class="text-end"><?= number_format($it['price'], 0, ',', '.') ?> VND</td>
            <td class="text-center"><?= (int)$it['qty'] ?></td>
            <td class="text-end"><?= number_format($it['price']*$it['qty'], 0, ',', '.') ?> VND</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <form method="post">
    <div class="row g-4">
      <!-- THÔNG TIN GIAO HÀNG -->
      <div class="col-md-7">
        <div class="section-card">
          <h5 class="mb-3">Thông tin giao hàng</h5>

          <div class="mb-3">
            <label class="form-label">Họ và tên</label>
            <input type="text" name="fullname" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Số điện thoại</label>
            <input type="tel" name="phone" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="province" class="form-label">Tỉnh/Thành nhận hàng</label>
            <select name="province" id="province" class="form-control" required>
              <option value="" selected disabled>-- Chọn tỉnh/thành --</option>
              <?php foreach ($PROVINCES as $p): ?>
                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="ship_fee" id="ship_fee_input" value="0">
            <div class="form-text">* Phí ship: Hà Nội 30.000 VND; tỉnh khác 50.000 VND.</div>
          </div>

          <!-- ✅ ĐỊA CHỈ CHI TIẾT -->
          <div class="mb-3">
            <label for="address_detail" class="form-label">Địa chỉ nhận hàng (chi tiết)</label>
            <textarea name="address_detail" id="address_detail" class="form-control" rows="3" placeholder="Số nhà, đường, phường/xã, quận/huyện..." ></textarea>
          </div>

          <div class="mb-1">
            <label class="form-label">Phương thức thanh toán</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="payment_method" id="pm_cod" value="COD" checked>
              <label class="form-check-label" for="pm_cod">Thanh toán khi nhận hàng (COD)</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="payment_method" id="pm_bank" value="BANK">
              <label class="form-check-label" for="pm_bank">Chuyển khoản ngân hàng</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="payment_method" id="pm_wallet" value="WALLET">
              <label class="form-check-label" for="pm_wallet">Ví (MoMo/ZaloPay)</label>
            </div>
          </div>
        </div>
      </div>

      <!-- TÓM TẮT ĐƠN HÀNG -->
      <div class="col-md-5">
        <div class="order-summary p-3">
          <h5 class="mb-3">Tóm tắt đơn hàng</h5>
          <table class="w-100 totals-table">
            <tr>
              <td class="text-muted-2">Tạm tính</td>
              <td class="text-end"><strong><?= number_format($subtotal, 0, ',', '.') ?> VND</strong></td>
            </tr>
            <tr>
              <td class="text-muted-2">VAT (10%)</td>
              <td class="text-end"><strong><?= number_format($vat, 0, ',', '.') ?> VND</strong></td>
            </tr>
            <tr>
              <td class="text-muted-2">Phí vận chuyển</td>
              <td class="text-end"><span id="ship-fee">0 VND</span></td>
            </tr>
            <tr>
              <th>Thành tiền</th>
              <th class="text-end"><span id="grand-total"><?= number_format($subtotal + $vat, 0, ',', '.') ?> VND</span></th>
            </tr>
          </table>

          <div class="d-grid gap-2 mt-3">
            <button type="submit" name="place_order" class="btn btn-primary btn-lg">Thanh toán</button>
            <a href="giohang.php" class="btn btn-light">Quay lại giỏ hàng</a>
          </div>
          <div class="form-text mt-2">* Mặc định: Hà Nội 30.000 VND, tỉnh khác 50.000 VND.</div>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- JS tính ship & tổng tiền realtime -->
<script>
(function(){
  const subtotal = <?= (int)$subtotal ?>;
  const vat = <?= (int)$vat ?>;

  const provinceEl = document.getElementById('province');
  const shipFeeEl  = document.getElementById('ship-fee');
  const grandEl    = document.getElementById('grand-total');
  const shipInput  = document.getElementById('ship_fee_input');

  function vnd(n){ return (n||0).toLocaleString('vi-VN') + ' VND'; }
  function calcShip(p){ return (!p ? 0 : (p.trim() === 'Hà Nội' ? 30000 : 50000)); }

  function recalc(){
    const p = provinceEl ? provinceEl.value : '';
    const ship = calcShip(p);
    const grand = subtotal + vat + ship;
    shipFeeEl.textContent = vnd(ship);
    grandEl.textContent = vnd(grand);
    shipInput.value = ship;
  }
  if (provinceEl){ provinceEl.addEventListener('change', recalc); }
  recalc();
})();
</script>
</body>
</html>
