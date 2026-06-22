<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin();

$pageTitle = "Hồ sơ cá nhân";
$active = "profile";
$message = "";
$type = "success";

$uid = currentUserId();
$role = currentRole();

// Cập nhật họ tên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_info') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $type = "error";
        $message = "Họ tên không được để trống.";
    } else {
        $pdo->prepare("UPDATE users SET name = ? WHERE user_id = ?")->execute([$name, $uid]);
        $_SESSION['user']['name'] = $name;
        $message = "Cập nhật thông tin thành công.";
    }
}

// Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $row = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $row->execute([$uid]);
    $hash = $row->fetchColumn();

    if (!passwordMatches($current, $hash)) {
        $type = "error";
        $message = "Mật khẩu hiện tại không đúng.";
    } elseif (strlen($new) < 6) {
        $type = "error";
        $message = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    } elseif ($new !== $confirm) {
        $type = "error";
        $message = "Xác nhận mật khẩu không khớp.";
    } else {
        $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
        $message = "Đổi mật khẩu thành công.";
    }
}

// Thông tin tài khoản hiện tại (kèm code/major từ bảng con tùy vai trò)
$me = $pdo->prepare("
    SELECT u.*,
           COALESCE(s.student_code, i.instructor_code) AS code,
           COALESCE(s.major, i.department) AS major
    FROM users u
    LEFT JOIN students s ON s.user_id = u.user_id
    LEFT JOIN instructors i ON i.user_id = u.user_id
    WHERE u.user_id = ?
");
$me->execute([$uid]);
$me = $me->fetch();

// Thông tin bổ sung theo role
$extra = [];
if ($role === 'student') {
    $st = $pdo->prepare("
        SELECT ip.title AS position_title, c.name AS company_name, p.name AS period_name,
               ir.status, lec.name AS lecturer_name,
               COUNT(DISTINCT CASE WHEN wj.status IN ('Submitted','Late') THEN wj.week_number END) AS journal_weeks
        FROM internship_registrations ir
        LEFT JOIN internship_positions ip ON ir.position_id = ip.ip_id
        LEFT JOIN companies c ON ip.company_id = c.company_id
        LEFT JOIN internship_periods p ON ir.period_id = p.period_id
        LEFT JOIN internship_assignments ia ON ia.registration_id = ir.ir_id
        LEFT JOIN users lec ON ia.lecturer_id = lec.user_id
        LEFT JOIN weekly_journals wj ON wj.registration_id = ir.ir_id
        WHERE ir.student_id = ?
        GROUP BY ir.ir_id, ip.title, c.name, p.name, ir.status, lec.name
        ORDER BY ir.applied_at DESC
    ");
    $st->execute([$uid]);
    $extra = $st->fetchAll();
} elseif ($role === 'lecturer') {
    $st = $pdo->prepare("SELECT COUNT(DISTINCT ia.registration_id) AS student_count FROM internship_assignments ia WHERE ia.lecturer_id = ?");
    $st->execute([$uid]);
    $extra = $st->fetch();
} elseif ($role === 'company' && currentCompanyId()) {
    $st = $pdo->prepare("
        SELECT c.*, COUNT(DISTINCT ip.ip_id) AS position_count
        FROM companies c LEFT JOIN internship_positions ip ON ip.company_id = c.company_id
        WHERE c.company_id = ? GROUP BY c.company_id
    ");
    $st->execute([currentCompanyId()]);
    $extra = $st->fetch();
}

$roleLabel = [
    'student' => 'Sinh viên',
    'lecturer' => 'Giảng viên',
    'company' => 'Doanh nghiệp',
    'admin' => 'Quản trị viên',
][$role] ?? $role;

include 'includes/header.php';
?>

<style>
    .profile-header { background:linear-gradient(135deg,#2563eb,#7c3aed); color:#fff; border-radius:16px; padding:28px; display:flex; gap:20px; align-items:center; }
    .profile-header .avatar { width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:32px; flex-shrink:0; }
    .profile-header h1 { margin:0 0 6px; font-size:26px; }
    .profile-header .sub { color:#e0e7ff; font-size:14px; display:flex; gap:14px; flex-wrap:wrap; }
    .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-top:22px; }
    @media (max-width:760px){ .info-grid{ grid-template-columns:1fr; } }
    .form-group { margin-bottom:14px; }
    .form-group label { display:block; font-weight:500; color:#334155; margin-bottom:6px; font-size:13px; }
    .form-group input { width:100%; padding:9px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; }
    .form-group input[disabled] { background:#f8fafc; color:#64748b; }
    .btn-submit { padding:9px 18px; background:#3525cd; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
</style>

<?php if ($message): ?><div class="alert <?= $type ?>"><?= e($message) ?></div><?php endif; ?>

<div class="profile-header">
    <div class="avatar"><?= e(strtoupper(mb_substr($me['name'] ?? 'U', 0, 1))) ?></div>
    <div>
        <h1><?= e($me['name']) ?></h1>
        <div class="sub">
            <span>👤 <?= e($me['username'] ?? '') ?></span>
            <span>✉️ <?= e($me['email']) ?></span>
            <span>🏷️ <?= e($roleLabel) ?></span>
            <?php if (!empty($me['code'])): ?><span>🆔 <?= e($me['code']) ?></span><?php endif; ?>
            <?php if (!empty($me['major'])): ?><span>🎓 <?= e($me['major']) ?></span><?php endif; ?>
        </div>
    </div>
</div>

<div class="info-grid">
    <!-- Cập nhật thông tin -->
    <div class="card">
        <h3>Thông tin cá nhân</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update_info">
            <div class="form-group">
                <label>Họ tên</label>
                <input type="text" name="name" value="<?= e($me['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email (không đổi)</label>
                <input type="email" value="<?= e($me['email']) ?>" disabled>
            </div>
            <?php if (!empty($me['code'])): ?>
            <div class="form-group">
                <label><?= $role === 'lecturer' ? 'Mã giảng viên' : 'Mã' ?> (không đổi)</label>
                <input type="text" value="<?= e($me['code']) ?>" disabled>
            </div>
            <?php endif; ?>
            <button class="btn-submit" type="submit">Lưu thông tin</button>
        </form>
    </div>

    <!-- Đổi mật khẩu -->
    <div class="card">
        <h3>Đổi mật khẩu</h3>
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label>Mật khẩu hiện tại</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu mới (≥ 6 ký tự)</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Xác nhận mật khẩu mới</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button class="btn-submit" type="submit">Đổi mật khẩu</button>
        </form>
    </div>
</div>

<!-- Thông tin theo vai trò -->
<?php if ($role === 'student'): ?>
<div class="card" style="margin-top:18px;">
    <h3>Thông tin thực tập</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Đợt</th><th>Vị trí</th><th>Công ty</th><th>Trạng thái</th><th>GVHD</th><th>Nhật ký</th></tr></thead>
            <tbody>
            <?php if (!$extra): ?>
                <tr><td colspan="6">Bạn chưa có đăng ký thực tập nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($extra as $r): ?>
                <tr>
                    <td><?= e($r['period_name'] ?: '—') ?></td>
                    <td><?= e($r['position_title'] ?: 'Chưa chọn vị trí') ?></td>
                    <td><?= e($r['company_name'] ?: '—') ?></td>
                    <td><span class="badge <?= strtolower(e($r['status'])) ?>"><?= e($r['status']) ?></span></td>
                    <td><?= e($r['lecturer_name'] ?: 'Chưa phân công') ?></td>
                    <td><?= e($r['journal_weeks']) ?>/6 tuần</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($role === 'lecturer'): ?>
<div class="card" style="margin-top:18px;">
    <h3>Phụ trách hướng dẫn</h3>
    <p>Bạn đang hướng dẫn <b><?= e($extra['student_count'] ?? 0) ?></b> sinh viên trong kỳ thực tập.</p>
    <a class="btn small" href="assignments.php">Xem danh sách phân công</a>
</div>
<?php elseif ($role === 'company' && $extra): ?>
<div class="card" style="margin-top:18px;">
    <h3>Thông tin doanh nghiệp</h3>
    <p><b><?= e($extra['name']) ?></b> — <?= e($extra['position_count']) ?> vị trí thực tập.</p>
    <?php if ($extra['address']): ?><p class="help">📍 <?= e($extra['address']) ?></p><?php endif; ?>
    <?php if ($extra['phone']): ?><p class="help">📞 <?= e($extra['phone']) ?></p><?php endif; ?>
    <a class="btn small" href="company_detail.php?id=<?= e($extra['company_id']) ?>">Xem trang doanh nghiệp</a>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
