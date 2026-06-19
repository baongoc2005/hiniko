<?php
require 'db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ten = $_POST['ten'];
    $mieu_ta = $_POST['su_mieu_ta'];
    $han_ngach = $_POST['han_ngach'];

    // BUSINESS RULE: Kiểm tra trùng tên công ty (Backend Validation) [cite: 30]
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE ten = ?");
    $stmt_check->execute([$ten]);
    
    if ($stmt_check->fetchColumn() > 0) {
        $message = "Lỗi: Tên doanh nghiệp này đã tồn tại trong hệ thống!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO companies (ten, su_mieu_ta, han_ngach) VALUES (?, ?, ?)");
        if ($stmt->execute([$ten, $mieu_ta, $han_ngach])) {
            header("Location: list_companies.php");
            exit;
        }
    }
}
?>
<h2>Thêm Công Ty Mới</h2>
<p style="color:red"><?= $message ?></p>
<form method="POST">
    Tên: <input type="text" name="ten" required><br><br>
    Mô tả: <textarea name="su_mieu_ta"></textarea><br><br>
    Hạn ngạch: <input type="number" name="han_ngach" value="5"><br><br>
    <button type="submit">Lưu lại</button>
</form>