<?php
// Cấu hình kết nối CSDL
$host = 'localhost';
$dbname = 'internship_manager'; // Đã sửa tên theo yêu cầu của bạn
$username = 'root'; // Mặc định của XAMPP
$password = ''; // Mặc định của XAMPP thường để trống

try {
    // Tạo kết nối PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Thiết lập chế độ báo lỗi (quan trọng để đi thi không bị trừ điểm lỗi kết nối)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Nếu muốn kiểm tra, có thể bỏ comment dòng dưới
    // echo "Kết nối thành công tới database: " . $dbname; 
} catch (PDOException $e) {
    // Nếu lỗi, hệ thống sẽ dừng và báo lỗi cụ thể [cite: 50]
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>