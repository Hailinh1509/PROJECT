<?php
session_start();
include 'connect.php';

// Nếu đã đăng nhập - muốn đăng xuất
if (isset($_GET['logout'])) {
    session_destroy(); //Xóa phiên đăng nhập
    header("Location: taikhoan.php");
    exit;
}

// ==== Xử lý ĐĂNG NHẬP ====
if (isset($_POST['dangnhap'])) {
    $tentk = $_POST['tentk'];
    $matkhau = $_POST['matkhau'];

    if ($tentk === "" || $matkhau === "") {
        echo "<script>alert('Vui lòng nhập tên tài khoản và mật khẩu.');</script>";
    } else {
        // Truy vấn 
        $sql = "SELECT matk, tentk, matkhau FROM nguoidung 
                WHERE tentk = '$tentk' OR matk = '$tentk'";
        $res = $conn->query($sql);

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $stored = $row['matkhau'];

            if ($matkhau === $stored) {
                $_SESSION['user'] = $row['tentk'];
                $_SESSION['matk'] = $row['matk'];
                echo "<script>alert('Đăng nhập thành công!');window.location='hienthi.php';</script>";
                exit;
            } else {
                echo "<script>alert('Sai mật khẩu!');</script>";
            }
        } else {
            echo "<script>alert('Tài khoản không tồn tại!');</script>";
        }
    }
}

// ==== XỬ LÝ ĐĂNG KÝ ====
if (isset($_POST['dangky'])) {
    $tentk = $_POST['tentk'];
    $matkhau = $_POST['matkhau'];
    $nhaplai = $_POST['nhaplai'];
    $diachi = $_POST['diachi'];
    $sodt = $_POST['sodt'];

    if ($matkhau != $nhaplai) {
        echo "<script>alert('Mật khẩu nhập lại không khớp!');</script>";
    } else {
        // Kiểm tra trùng tentk
        $sql = "SELECT tentk FROM nguoidung WHERE tentk = '$tentk'";
        $check = $conn->query($sql); 

        if ($check && $check->num_rows > 0) {
            echo "<script>alert('Tên tài khoản đã tồn tại!');</script>";
        } else {
            // Sinh matk tự động dạng TK001, TK002...
            //lấy matk lớn nhất
            $last = $conn->query("SELECT matk FROM nguoidung ORDER BY matk DESC LIMIT 1");
            if ($last && $last->num_rows > 0) {
                $r = $last->fetch_assoc();
                // Cắt từ ký tự thứ 2 -> chuyển  thành số nguyên để cộng thêm 1
                $num = (int)substr($r['matk'], 2);
                $num = $num + 1;
                $matk = "TK" . str_pad($num, 3, "0", STR_PAD_LEFT); //Đệm số 0 bên trái cho đủ 3 chữ số
            } else {
                $matk = "TK001";
            }

            // Lưu dữ liệu người dùng vào cơ sở dữ liệu
            $sql = "INSERT INTO nguoidung (matk, tentk, matkhau, diachi, sodt) 
                    VALUES ('$matk', '$tentk', '$matkhau', '$diachi', '$sodt')";

            if ($conn->query($sql) === TRUE) {
                echo "<script>alert('Đăng ký thành công! Mã tài khoản: $matk');window.location='hienthi.php';</script>";
                exit;
            } else {
                echo "<script>alert('Lỗi khi đăng ký: " . $conn->error . "');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Đăng nhập / Đăng ký</title>
    <style>
        .container {
            width: 380px;
            margin: 70px auto;
            background: #fff;
            border-radius: 10px;
            padding: 25px 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
        }
        form {
            margin-top: 20px;
        }
        input[type=text], input[type=password] {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #0056b3;
        }
        .switch {
            text-align: center;
            margin-top: 15px;
        }
        .switch a {
            color: #007bff;
            text-decoration: none;
        }

        .logged {
            text-align: center;
        }
        .logout {
            display:inline-block;
            margin-top:15px;
            background:#dc3545;
            color:white;
            padding:8px 12px;
            border-radius:6px;
            text-decoration:none;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Kiểm tra đã có biến user (đã đăng nhập) chưa -->
    <?php if (isset($_SESSION['user'])) { ?> 
        <div class="logged">
            <h2>Xin chào, <span style="color:green;"><?php echo $_SESSION['user']; ?></span>!</h2>
            <p>Bạn muốn đăng xuất tài khoản?</p>
            <a class="logout" href="taikhoan.php?logout=1">Đăng xuất</a>
        </div>
    <?php } else { ?>

        <?php if (!isset($_GET['mode']) || $_GET['mode'] == 'login') { ?>
            <h2>Đăng nhập</h2>
            <!-- Gửi dữ liệu đăng nhập bằng phương thức POST -->
            <form method="POST">
                <label>Tên tài khoản (hoặc mã TK):</label>
                <input type="text" name="tentk" required>

                <label>Mật khẩu:</label>
                <input type="password" name="matkhau" required>

                <button type="submit" name="dangnhap">Đăng nhập</button>

                <div class="switch">
                    <p>Chưa có tài khoản? <a href="?mode=register">Đăng ký</a></p>
                </div>
            </form>

        <?php } else { ?>
            <h2>Đăng ký tài khoản</h2>
            <form method="post">
                <label>Tên tài khoản (hiển thị):</label>
                <input type="text" name="tentk" required>

                <label>Mật khẩu:</label>
                <input type="password" name="matkhau" required>

                <label>Nhập lại mật khẩu:</label>
                <input type="password" name="nhaplai" required>

                <label>Địa chỉ:</label>
                <input type="text" name="diachi" required>

                <label>Số điện thoại:</label>
                <input type="text" name="sodt" required>

                <button type="submit" name="dangky">Đăng ký</button>

                <div class="switch">
                    <p>Đã có tài khoản? <a href="taikhoan.php?mode=login">Đăng nhập</a></p>
                </div>
            </form>
        <?php } ?>

    <?php } ?>
</div>

</body>
</html>
