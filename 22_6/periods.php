<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin']);

$pageTitle = "Đợt thực tập";
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

<style>
    .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); overflow-y:auto; }
    .modal.active { display:flex; align-items:flex-start; justify-content:center; padding:40px 16px; }
    .modal-content { background:#fff; border-radius:14px; padding:26px; width:100%; max-width:520px; box-shadow:0 4px 24px rgba(0,0,0,0.18); }
    .modal-header { font-size:19px; font-weight:700; margin-bottom:18px; color:#1e293b; display:flex; justify-content:space-between; align-items:center; }
    .modal-close { font-size:26px; font-weight:bold; color:#94a3b8; cursor:pointer; line-height:1; }
    .form-group { margin-bottom:14px; }
    .form-group label { display:block; font-weight:500; color:#334155; margin-bottom:6px; font-size:13px; }
    .form-group input { width:100%; padding:9px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; }
    .modal-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:22px; }
    .btn-cancel { padding:9px 18px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; cursor:pointer; font-weight:500; color:#475569; }
    .btn-submit { padding:9px 18px; background:#3525cd; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
</style>

<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>

<div style="display:flex; justify-content:flex-end; align-items:center; gap:16px; flex-wrap:wrap;">
    <button onclick="openAddPeriodModal()" style="background:#3525cd; color:#fff; padding:10px 18px; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
        ➕ Thêm đợt thực tập
    </button>
</div>

<!-- Add Period Modal -->
<div id="addPeriodModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            Thêm đợt thực tập
            <span class="modal-close" onclick="closeAddPeriodModal()">&times;</span>
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Tên đợt *</label>
                <input name="name" placeholder="VD: Summer Internship 2026" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label>Ngày bắt đầu *</label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>Ngày kết thúc *</label>
                    <input type="date" name="end_date" required>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAddPeriodModal()">Hủy</button>
                <button type="submit" class="btn-submit">Lưu đợt thực tập</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddPeriodModal() { document.getElementById('addPeriodModal').classList.add('active'); }
function closeAddPeriodModal() { document.getElementById('addPeriodModal').classList.remove('active'); }
window.addEventListener('click', function(e) {
    var m = document.getElementById('addPeriodModal');
    if (e.target === m) m.classList.remove('active');
});
</script>

<div class="card">
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

<?php foreach ($periods as $p): ?>
<div class="card" style="margin-top: 22px;">
    <h3>Lịch <?= INTERNSHIP_WEEKS ?> tuần — <?= e($p['name']) ?></h3>
    <p class="help">Mỗi tuần kéo dài 7 ngày tính từ ngày bắt đầu. Sinh viên nộp nhật ký sau ngày kết thúc tuần sẽ bị tính là <b>Trễ</b>.</p>
    <table>
        <thead><tr><th>Tuần</th><th>Từ ngày</th><th>Đến ngày</th></tr></thead>
        <tbody>
        <?php foreach (periodWeeks($p['start_date']) as $wk): ?>
            <tr>
                <td><b>Tuần <?= e($wk['week']) ?></b></td>
                <td><?= e($wk['start']) ?></td>
                <td><?= e($wk['end']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<?php include 'includes/footer.php'; ?>
