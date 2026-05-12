<?php
require 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // BUSINESS RULE: Ngăn xóa nếu công ty đã có vị trí thực tập [cite: 31]
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM internship_positions WHERE company_id = ?");
    $stmt_check->execute([$id]);
    
    if ($stmt_check->fetchColumn() > 0) {
        echo "<script>alert('Không thể xóa vì công ty này đang có vị trí thực tập!'); window.location='list_companies.php';</script>";
    } else {
        $stmt = $pdo->prepare("DELETE FROM companies WHERE company_id = ?");
        $stmt->execute([$id]);
        header("Location: list_companies.php");
    }
}