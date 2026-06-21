<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin']);

$pageTitle = "Internship Periods";
$active = "periods";
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO internship_periods (name, start_date, end_date) VALUES (?, ?, ?)");
    $stmt->execute([trim($_POST['name']), $_POST['start_date'], $_POST['end_date']]);
    $message = "Thêm đợt thực tập thành công.";
}

$periods = $pdo->query("
    SELECT p.*, COUNT(ir.ir_id) AS registration_count
    FROM internship_periods p
    LEFT JOIN internship_registrations ir ON p.period_id = ir.period_id
    GROUP BY p.period_id
    ORDER BY p.start_date DESC
")->fetchAll();

include 'includes/header.php';
?>

<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>

<div class="card">
    <h3>Thêm đợt thực tập</h3>
    <form class="form" method="POST">
        <div>
            <label>Tên đợt</label>
            <input name="name" placeholder="VD: Summer Internship 2026" required>
        </div>
        <div class="form-row">
            <div>
                <label>Ngày bắt đầu</label>
                <input type="date" name="start_date" required>
            </div>
            <div>
                <label>Ngày kết thúc</label>
                <input type="date" name="end_date" required>
            </div>
        </div>
        <button>Lưu đợt thực tập</button>
    </form>
</div>

<div class="card" style="margin-top: 22px;">
    <h3>Danh sách đợt thực tập</h3>
    <table>
        <thead><tr><th>ID</th><th>Tên đợt</th><th>Bắt đầu</th><th>Kết thúc</th><th>Số đăng ký</th></tr></thead>
        <tbody>
        <?php foreach ($periods as $p): ?>
            <tr>
                <td>#<?= e($p['period_id']) ?></td>
                <td><b><?= e($p['name']) ?></b></td>
                <td><?= e($p['start_date']) ?></td>
                <td><?= e($p['end_date']) ?></td>
                <td><?= e($p['registration_count']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
