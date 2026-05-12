<?php
// db.php
$host = 'localhost';
$dbname = 'internship_system'; // Tên database chuẩn em vừa tạo
$username = 'root'; // Mặc định của XAMPP
$password = ''; // XAMPP mặc định không có pass

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Thiết lập chế độ hiển thị lỗi để dễ sửa lỗi (debug)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Bỏ chú thích (comment) dòng dưới đây nếu em muốn in ra để test thử
    // echo "Kết nối Database thành công rồi nha!"; 
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>
