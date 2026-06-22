<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin']);

$pageTitle = "Quản lý Giảng viên";
$active = "lecturers";
$message = "";
$type = "success";

$periods = $pdo->query("SELECT * FROM internship_periods ORDER BY start_date DESC")->fetchAll();

// Đợt được chọn: mặc định đợt mới nhất
$selectedPeriod = isset($_GET['period_id']) ? (int)$_GET['period_id'] : (int)($periods[0]['period_id'] ?? 0);

// Xử lý thêm giảng viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_lecturer') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $major = trim($_POST['major'] ?? '');
    $password = password_hash('123456', PASSWORD_DEFAULT);

    if ($name === '' || $username === '' || $email === '') {
        $type = "error";
        $message = "Vui lòng nhập đầy đủ họ tên, tên đăng nhập và email.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO users (username, name, email, password, role) VALUES (?, ?, ?, ?, 'lecturer')");
            $stmt->execute([$username, $name, $email, $password]);
            $lecId = (int)$pdo->lastInsertId();

            $insIns = $pdo->prepare("INSERT INTO instructors (user_id, instructor_code, department) VALUES (?, ?, ?)");
            $insIns->execute([$lecId, $code !== '' ? $code : null, $major !== '' ? $major : null]);

            $pdo->commit();
            $message = "Đã thêm giảng viên. GV sẽ hiện trong bảng sau khi được phân công sinh viên ở trang Phân công GVHD.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $type = "error";
            $message = "Không thể thêm. Tên đăng nhập hoặc email có thể đã tồn tại.";
        }
    }
}

// Sửa thông tin giảng viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_lecturer') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $major = trim($_POST['major'] ?? '');

    if ($user_id <= 0 || $name === '' || $email === '') {
        $type = "error";
        $message = "Vui lòng nhập đầy đủ họ tên và email.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ? AND role = 'lecturer'");
            $stmt->execute([$name, $email, $user_id]);

            $upIns = $pdo->prepare("
                INSERT INTO instructors (user_id, instructor_code, department) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE instructor_code = VALUES(instructor_code), department = VALUES(department)
            ");
            $upIns->execute([$user_id, $code !== '' ? $code : null, $major !== '' ? $major : null]);

            $pdo->commit();
            $message = "Cập nhật giảng viên thành công.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $type = "error";
            $message = "Không thể cập nhật. Email có thể đã tồn tại.";
        }
    }
}

// Xóa tài khoản giảng viên (chặn nếu đang phụ trách SV)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_lecturer') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id > 0) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM internship_assignments WHERE lecturer_id = ?");
        $chk->execute([$user_id]);
        if ($chk->fetchColumn() > 0) {
            $type = "error";
            $message = "Không thể xóa: giảng viên đang phụ trách sinh viên. Hãy gỡ phân công trước.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'lecturer'");
                $stmt->execute([$user_id]);
                $message = "Đã xóa tài khoản giảng viên.";
            } catch (PDOException $e) {
                $type = "error";
                $message = "Không thể xóa giảng viên này.";
            }
        }
    }
}

$lecturers = [];
if ($selectedPeriod > 0) {
    $stmt = $pdo->prepare("
        SELECT
            lec.user_id,
            lec.name AS lecturer_name,
            lec.email,
            i.instructor_code AS code,
            i.department AS major,
            COUNT(DISTINCT ir.ir_id) AS student_count,
            GROUP_CONCAT(DISTINCT stu.name ORDER BY stu.name SEPARATOR ', ') AS student_names
        FROM internship_assignments ia
        JOIN internship_registrations ir ON ia.registration_id = ir.ir_id
        JOIN users lec ON ia.lecturer_id = lec.user_id
        LEFT JOIN instructors i ON i.user_id = lec.user_id
        JOIN users stu ON ir.student_id = stu.user_id
        WHERE ir.period_id = ?
        GROUP BY lec.user_id, lec.name, lec.email, i.instructor_code, i.department
        ORDER BY lec.name
    ");
    $stmt->execute([$selectedPeriod]);
    $lecturers = $stmt->fetchAll();
}

include 'includes/header.php';
?>

<style>
    .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); }
    .modal.active { display:flex; align-items:center; justify-content:center; }
    .modal-content { background:#fff; border-radius:12px; padding:24px; width:90%; max-width:500px; box-shadow:0 4px 20px rgba(0,0,0,0.15); }
    .modal-header { font-size:18px; font-weight:600; margin-bottom:20px; color:#1e293b; }
    .modal-close { float:right; font-size:28px; font-weight:bold; color:#94a3b8; cursor:pointer; }
    .modal-close:hover { color:#475569; }
    .form-group { margin-bottom:16px; }
    .form-group label { display:block; font-weight:500; color:#334155; margin-bottom:6px; font-size:14px; }
    .form-group input { width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; }
    .modal-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:24px; }
    .btn-cancel { padding:8px 16px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; cursor:pointer; font-weight:500; color:#475569; }
    .btn-submit { padding:8px 16px; background:#3525cd; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:500; }
</style>

<?php if ($message): ?><div class="alert <?= $type ?>"><?= e($message) ?></div><?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
    <form class="form" method="GET" style="margin:0;">
        <div style="display:flex; align-items:center; gap:10px;">
            <label style="font-weight:600;">Đợt thực tập:</label>
            <select name="period_id" onchange="this.form.submit()" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px;">
                <?php if (!$periods): ?>
                    <option value="0">Chưa có đợt thực tập nào</option>
                <?php endif; ?>
                <?php foreach ($periods as $p): ?>
                    <option value="<?= e($p['period_id']) ?>" <?= $selectedPeriod === (int)$p['period_id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button>Xem</button></noscript>
        </div>
    </form>

    <button onclick="openAddLecturerModal()" style="background:#3525cd; color:#fff; padding:10px 16px; border:none; border-radius:8px; font-weight:500; cursor:pointer;">
        ➕ Thêm giảng viên
    </button>
</div>

<div class="card" style="margin-top: 22px;">
    <h3>Giảng viên hướng dẫn trong đợt thực tập</h3>
    <p class="help">Tổng cộng <?= count($lecturers) ?> giảng viên có sinh viên hướng dẫn trong đợt này.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mã GV</th>
                    <th>Giảng viên</th>
                    <th>Email</th>
                    <th>Ngành</th>
                    <th>Số SV hướng dẫn</th>
                    <th>Danh sách sinh viên</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$lecturers): ?>
                <tr><td colspan="8">Chưa có giảng viên nào được phân công hướng dẫn trong đợt này.</td></tr>
            <?php endif; ?>
            <?php foreach ($lecturers as $l): ?>
                <tr>
                    <td>#<?= e($l['user_id']) ?></td>
                    <td><?= $l['code'] ? e($l['code']) : '<span class="help">—</span>' ?></td>
                    <td><b><?= e($l['lecturer_name']) ?></b></td>
                    <td><?= e($l['email']) ?></td>
                    <td><?= $l['major'] ? e($l['major']) : '<span class="help">—</span>' ?></td>
                    <td><span class="badge approved"><?= e($l['student_count']) ?></span></td>
                    <td><?= e($l['student_names']) ?></td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <button type="button" class="btn small"
                                onclick="openEditLecturerModal(<?= e($l['user_id']) ?>, '<?= e(addslashes($l['lecturer_name'])) ?>', '<?= e(addslashes($l['email'])) ?>', '<?= e(addslashes($l['code'] ?? '')) ?>', '<?= e(addslashes($l['major'] ?? '')) ?>')"
                                title="Sửa">✎</button>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Xóa tài khoản giảng viên này?')">
                                <input type="hidden" name="action" value="delete_lecturer">
                                <input type="hidden" name="user_id" value="<?= e($l['user_id']) ?>">
                                <button type="submit" class="btn danger small" title="Xóa">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Lecturer Modal -->
<div id="addLecturerModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeAddLecturerModal()">&times;</span>
        <div class="modal-header">Thêm giảng viên</div>
        <form method="POST">
            <input type="hidden" name="action" value="add_lecturer">

            <div class="form-group">
                <label>Họ tên *</label>
                <input type="text" name="name" required placeholder="VD: TS. Nguyễn Văn B">
            </div>
            <div class="form-group">
                <label>Tên đăng nhập *</label>
                <input type="text" name="username" required placeholder="VD: gv4">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="VD: gv@ischool.edu.vn">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label>Mã giảng viên</label>
                    <input type="text" name="code" placeholder="VD: GV2026001">
                </div>
                <div class="form-group">
                    <label>Ngành</label>
                    <input type="text" name="major" placeholder="VD: Information Technology">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAddLecturerModal()">Hủy</button>
                <button type="submit" class="btn-submit">Thêm giảng viên</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Lecturer Modal -->
<div id="editLecturerModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeEditLecturerModal()">&times;</span>
        <div class="modal-header">Sửa thông tin giảng viên</div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_lecturer">
            <input type="hidden" name="user_id" id="editLecturerId">

            <div class="form-group">
                <label>Họ tên *</label>
                <input type="text" name="name" id="editLecturerName" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" id="editLecturerEmail" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label>Mã giảng viên</label>
                    <input type="text" name="code" id="editLecturerCode">
                </div>
                <div class="form-group">
                    <label>Ngành</label>
                    <input type="text" name="major" id="editLecturerMajor">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditLecturerModal()">Hủy</button>
                <button type="submit" class="btn-submit">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddLecturerModal() { document.getElementById('addLecturerModal').classList.add('active'); }
function closeAddLecturerModal() { document.getElementById('addLecturerModal').classList.remove('active'); }
function openEditLecturerModal(id, name, email, code, major) {
    document.getElementById('editLecturerId').value = id;
    document.getElementById('editLecturerName').value = name;
    document.getElementById('editLecturerEmail').value = email;
    document.getElementById('editLecturerCode').value = code;
    document.getElementById('editLecturerMajor').value = major;
    document.getElementById('editLecturerModal').classList.add('active');
}
function closeEditLecturerModal() { document.getElementById('editLecturerModal').classList.remove('active'); }
window.addEventListener('click', function(e) {
    if (e.target.classList && e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
