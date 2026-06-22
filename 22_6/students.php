<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin']);

$pageTitle = "Quản lý Sinh viên";
$active = "students";
$message = "";
$type = "success";

$periods = $pdo->query("SELECT * FROM internship_periods ORDER BY start_date DESC")->fetchAll();

// Đợt được chọn: mặc định đợt mới nhất
$selectedPeriod = isset($_GET['period_id']) ? (int)$_GET['period_id'] : (int)($periods[0]['period_id'] ?? 0);

// Xử lý thêm sinh viên + đăng ký vào đợt đang chọn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_student') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $major = trim($_POST['major'] ?? '');
    $password = password_hash('123456', PASSWORD_DEFAULT);
    $position_id = (int)($_POST['position_id'] ?? 0);
    $period_id = (int)($_POST['period_id'] ?? $selectedPeriod);

    if ($name === '' || $username === '' || $email === '' || $period_id <= 0) {
        $type = "error";
        $message = "Vui lòng nhập đầy đủ họ tên, tên đăng nhập, email.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO users (username, name, email, password, role) VALUES (?, ?, ?, ?, 'student')");
            $stmt->execute([$username, $name, $email, $password]);
            $studentId = (int)$pdo->lastInsertId();

            $insStu = $pdo->prepare("INSERT INTO students (user_id, student_code, major) VALUES (?, ?, ?)");
            $insStu->execute([$studentId, $code !== '' ? $code : null, $major !== '' ? $major : null]);

            $reg = $pdo->prepare("INSERT INTO internship_registrations (student_id, position_id, period_id, status) VALUES (?, ?, ?, 'Pending')");
            $reg->execute([$studentId, $position_id > 0 ? $position_id : null, $period_id]);

            $pdo->commit();
            $selectedPeriod = $period_id;
            $message = $position_id > 0
                ? "Đã thêm sinh viên và đăng ký vào đợt thực tập."
                : "Đã thêm sinh viên vào đợt (chưa chọn vị trí, trạng thái Pending).";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $type = "error";
            $message = "Không thể thêm. Tên đăng nhập hoặc email có thể đã tồn tại.";
        }
    }
}

// Sửa thông tin sinh viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_student') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $ir_id = (int)($_POST['ir_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $major = trim($_POST['major'] ?? '');
    $position_id = (int)($_POST['position_id'] ?? 0);
    $status = $_POST['status'] ?? 'Pending';
    $lecturer_id = (int)($_POST['lecturer_id'] ?? 0);

    if ($user_id <= 0 || $name === '' || $email === '') {
        $type = "error";
        $message = "Vui lòng nhập đầy đủ họ tên và email.";
    } elseif (!in_array($status, ['Pending', 'Approved', 'Rejected'], true)) {
        $type = "error";
        $message = "Trạng thái không hợp lệ.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ? AND role = 'student'");
            $stmt->execute([$name, $email, $user_id]);

            // Cập nhật thông tin sinh viên (bảng students) — upsert
            $upStu = $pdo->prepare("
                INSERT INTO students (user_id, student_code, major) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE student_code = VALUES(student_code), major = VALUES(major)
            ");
            $upStu->execute([$user_id, $code !== '' ? $code : null, $major !== '' ? $major : null]);

            // Cập nhật vị trí + trạng thái của đăng ký (nếu có)
            if ($ir_id > 0) {
                $reg = $pdo->prepare("UPDATE internship_registrations SET position_id = ?, status = ? WHERE ir_id = ?");
                $reg->execute([$position_id > 0 ? $position_id : null, $status, $ir_id]);

                // Cập nhật phân công GVHD
                if ($lecturer_id > 0) {
                    $asg = $pdo->prepare("
                        INSERT INTO internship_assignments (registration_id, lecturer_id)
                        VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE lecturer_id = VALUES(lecturer_id), assigned_at = CURRENT_TIMESTAMP
                    ");
                    $asg->execute([$ir_id, $lecturer_id]);
                } else {
                    // Bỏ chọn GVHD -> gỡ phân công
                    $pdo->prepare("DELETE FROM internship_assignments WHERE registration_id = ?")->execute([$ir_id]);
                }
            }

            $pdo->commit();
            $message = "Cập nhật sinh viên thành công.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $type = "error";
            $message = "Không thể cập nhật. Email có thể đã tồn tại hoặc sinh viên đã đăng ký vị trí này.";
        }
    }
}

// Xóa tài khoản sinh viên (cascade dọn đăng ký, nhật ký, file...)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_student') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'student'");
            $stmt->execute([$user_id]);
            $message = "Đã xóa tài khoản sinh viên.";
        } catch (PDOException $e) {
            $type = "error";
            $message = "Không thể xóa sinh viên này.";
        }
    }
}

// Vị trí cho dropdown trong modal
$positions = $pdo->query("
    SELECT ip.ip_id, ip.title, ip.major, c.name AS company_name
    FROM internship_positions ip
    JOIN companies c ON ip.company_id = c.company_id
    ORDER BY c.name, ip.title
")->fetchAll();

// Giảng viên cho dropdown GVHD
$lecturers = $pdo->query("
    SELECT u.user_id, u.name, i.instructor_code AS code
    FROM users u
    LEFT JOIN instructors i ON i.user_id = u.user_id
    WHERE u.role = 'lecturer'
    ORDER BY u.name
")->fetchAll();

$students = [];
if ($selectedPeriod > 0) {
    $stmt = $pdo->prepare("
        SELECT
            ir.ir_id,
            ir.position_id,
            ir.status,
            u.user_id,
            u.name AS student_name,
            u.email,
            s.student_code AS code,
            s.major,
            ip.title AS position_title,
            c.name AS company_name,
            lec.name AS lecturer_name,
            ia.lecturer_id,
            COUNT(DISTINCT CASE WHEN wj.status IN ('Submitted','Late') THEN wj.week_number END) AS journal_count
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        LEFT JOIN students s ON s.user_id = u.user_id
        LEFT JOIN internship_positions ip ON ir.position_id = ip.ip_id
        LEFT JOIN companies c ON ip.company_id = c.company_id
        LEFT JOIN internship_assignments ia ON ia.registration_id = ir.ir_id
        LEFT JOIN users lec ON ia.lecturer_id = lec.user_id
        LEFT JOIN weekly_journals wj ON wj.registration_id = ir.ir_id
        WHERE ir.period_id = ?
        GROUP BY ir.ir_id, ir.position_id, ir.status, u.user_id, u.name, u.email, s.student_code, s.major, ip.title, c.name, lec.name, ia.lecturer_id
        ORDER BY u.name
    ");
    $stmt->execute([$selectedPeriod]);
    $students = $stmt->fetchAll();
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
    .form-group input, .form-group select { width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; }
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

    <button onclick="openAddStudentModal()" style="background:#3525cd; color:#fff; padding:10px 16px; border:none; border-radius:8px; font-weight:500; cursor:pointer;">
        ➕ Thêm sinh viên
    </button>
</div>

<div class="card" style="margin-top: 22px;">
    <h3>Sinh viên trong đợt thực tập</h3>
    <p class="help">Tổng cộng <?= count($students) ?> sinh viên đăng ký trong đợt này.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mã SV</th>
                    <th>Sinh viên</th>
                    <th>Email</th>
                    <th>Ngành</th>
                    <th>Vị trí / Doanh nghiệp</th>
                    <th>Trạng thái</th>
                    <th>GVHD</th>
                    <th>Nhật ký đã nộp</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$students): ?>
                <tr><td colspan="10">Chưa có sinh viên nào trong đợt thực tập này.</td></tr>
            <?php endif; ?>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td>#<?= e($s['ir_id']) ?></td>
                    <td><?= $s['code'] ? e($s['code']) : '<span class="help">—</span>' ?></td>
                    <td><b><?= e($s['student_name']) ?></b></td>
                    <td><?= e($s['email']) ?></td>
                    <td><?= $s['major'] ? e($s['major']) : '<span class="help">—</span>' ?></td>
                    <td><?= $s['position_title'] ? e($s['position_title']) . '<br><span class="help">' . e($s['company_name']) . '</span>' : '<span class="badge pending">Chưa chọn vị trí</span>' ?></td>
                    <td><span class="badge <?= strtolower(e($s['status'])) ?>"><?= e($s['status']) ?></span></td>
                    <td><?= $s['lecturer_name'] ? e($s['lecturer_name']) : '<span class="badge pending">Chưa phân công</span>' ?></td>
                    <td><?= e($s['journal_count']) ?> tuần</td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <button type="button" class="btn small"
                                onclick="openEditStudentModal(<?= e($s['user_id']) ?>, <?= e($s['ir_id']) ?>, '<?= e(addslashes($s['student_name'])) ?>', '<?= e(addslashes($s['email'])) ?>', '<?= e(addslashes($s['code'] ?? '')) ?>', '<?= e(addslashes($s['major'] ?? '')) ?>', <?= e($s['position_id'] ?? 0) ?>, '<?= e($s['status']) ?>', <?= e($s['lecturer_id'] ?? 0) ?>)"
                                title="Sửa">✎</button>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Xóa tài khoản sinh viên này? Mọi đăng ký, nhật ký, file liên quan cũng bị xóa.')">
                                <input type="hidden" name="action" value="delete_student">
                                <input type="hidden" name="user_id" value="<?= e($s['user_id']) ?>">
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

<!-- Add Student Modal -->
<div id="addStudentModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeAddStudentModal()">&times;</span>
        <div class="modal-header">Thêm sinh viên vào đợt thực tập</div>
        <form method="POST">
            <input type="hidden" name="action" value="add_student">
            <input type="hidden" name="period_id" value="<?= e($selectedPeriod) ?>">

            <div class="form-group">
                <label>Họ tên *</label>
                <input type="text" name="name" required placeholder="VD: Nguyễn Văn A">
            </div>
            <div class="form-group">
                <label>Tên đăng nhập *</label>
                <input type="text" name="username" required placeholder="VD: sv21">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="VD: sv@ischool.edu.vn">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label>Mã sinh viên</label>
                    <input type="text" name="code" placeholder="VD: SV2026001">
                </div>
                <div class="form-group">
                    <label>Ngành</label>
                    <input type="text" name="major" placeholder="VD: Information Technology">
                </div>
            </div>
            <div class="form-group">
                <label>Vị trí thực tập</label>
                <select name="position_id">
                    <option value="">— Chưa chọn (để Pending) —</option>
                    <?php foreach ($positions as $p): ?>
                        <option value="<?= e($p['ip_id']) ?>"><?= e($p['company_name']) ?> — <?= e($p['title']) ?><?= $p['major'] ? ' [' . e($p['major']) . ']' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAddStudentModal()">Hủy</button>
                <button type="submit" class="btn-submit">Thêm sinh viên</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<div id="editStudentModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeEditStudentModal()">&times;</span>
        <div class="modal-header">Sửa thông tin sinh viên</div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_student">
            <input type="hidden" name="user_id" id="editStudentId">
            <input type="hidden" name="ir_id" id="editStudentIrId">

            <div class="form-group">
                <label>Họ tên *</label>
                <input type="text" name="name" id="editStudentName" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" id="editStudentEmail" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label>Mã sinh viên</label>
                    <input type="text" name="code" id="editStudentCode">
                </div>
                <div class="form-group">
                    <label>Ngành</label>
                    <input type="text" name="major" id="editStudentMajor">
                </div>
            </div>
            <div class="form-group">
                <label>Vị trí ứng tuyển / Công ty thực tập</label>
                <select name="position_id" id="editStudentPosition">
                    <option value="">— Chưa chọn vị trí —</option>
                    <?php foreach ($positions as $p): ?>
                        <option value="<?= e($p['ip_id']) ?>"><?= e($p['company_name']) ?> — <?= e($p['title']) ?><?= $p['major'] ? ' [' . e($p['major']) . ']' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Trạng thái</label>
                <select name="status" id="editStudentStatus">
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <div class="form-group">
                <label>Giảng viên hướng dẫn</label>
                <select name="lecturer_id" id="editStudentLecturer">
                    <option value="">— Chưa phân công —</option>
                    <?php foreach ($lecturers as $l): ?>
                        <option value="<?= e($l['user_id']) ?>"><?= e($l['name']) ?><?= $l['code'] ? ' (' . e($l['code']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditStudentModal()">Hủy</button>
                <button type="submit" class="btn-submit">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddStudentModal() { document.getElementById('addStudentModal').classList.add('active'); }
function closeAddStudentModal() { document.getElementById('addStudentModal').classList.remove('active'); }
function openEditStudentModal(id, irId, name, email, code, major, positionId, status, lecturerId) {
    document.getElementById('editStudentId').value = id;
    document.getElementById('editStudentIrId').value = irId;
    document.getElementById('editStudentName').value = name;
    document.getElementById('editStudentEmail').value = email;
    document.getElementById('editStudentCode').value = code;
    document.getElementById('editStudentMajor').value = major;
    document.getElementById('editStudentPosition').value = positionId > 0 ? positionId : '';
    document.getElementById('editStudentStatus').value = status;
    document.getElementById('editStudentLecturer').value = lecturerId > 0 ? lecturerId : '';
    document.getElementById('editStudentModal').classList.add('active');
}
function closeEditStudentModal() { document.getElementById('editStudentModal').classList.remove('active'); }
window.addEventListener('click', function(e) {
    if (e.target.classList && e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
