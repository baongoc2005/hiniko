<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin();

$pageTitle = "Trang chủ";
$active = "dashboard";

$role = currentRole();
$isAdmin = $role === 'admin';

// Cảnh báo điều kiện cho sinh viên: thiếu nhật ký tuần / nộp trễ
$studentWarnings = [];
$studentInfo = [];
if ($role === 'student') {
    $infoStmt = $pdo->prepare("
        SELECT ip.title AS position_title, c.name AS company_name, p.name AS period_name,
               ir.status, lec.name AS lecturer_name, lec.email AS lecturer_email
        FROM internship_registrations ir
        LEFT JOIN internship_positions ip ON ir.position_id = ip.ip_id
        LEFT JOIN companies c ON ip.company_id = c.company_id
        LEFT JOIN internship_periods p ON ir.period_id = p.period_id
        LEFT JOIN internship_assignments ia ON ia.registration_id = ir.ir_id
        LEFT JOIN users lec ON ia.lecturer_id = lec.user_id
        WHERE ir.student_id = ?
        ORDER BY ir.applied_at DESC
    ");
    $infoStmt->execute([currentUserId()]);
    $studentInfo = $infoStmt->fetchAll();

    $wStmt = $pdo->prepare("
        SELECT ip.title, c.name AS company_name, p.start_date, p.end_date,
               COUNT(DISTINCT CASE WHEN wj.status IN ('Submitted','Late') THEN wj.week_number END) AS submitted_weeks,
               SUM(CASE WHEN wj.status = 'Late' THEN 1 ELSE 0 END) AS late_count
        FROM internship_registrations ir
        LEFT JOIN internship_positions ip ON ir.position_id = ip.ip_id
        LEFT JOIN companies c ON ip.company_id = c.company_id
        JOIN internship_periods p ON ir.period_id = p.period_id
        LEFT JOIN weekly_journals wj ON wj.registration_id = ir.ir_id
        WHERE ir.student_id = ? AND ir.status = 'Approved'
        GROUP BY ir.ir_id, ip.title, c.name, p.start_date, p.end_date
    ");
    $wStmt->execute([currentUserId()]);

    $today = new DateTimeImmutable('today');
    foreach ($wStmt->fetchAll() as $row) {
        $start = new DateTimeImmutable($row['start_date']);
        $end = new DateTimeImmutable($row['end_date']);
        if ($today < $start) {
            continue;
        }
        $effective = $today < $end ? $today : $end;
        $expectedWeeks = (int)floor($start->diff($effective)->days / 7) + 1;
        $totalWeeks = (int)floor($start->diff($end)->days / 7) + 1;
        if ($expectedWeeks > $totalWeeks) {
            $expectedWeeks = $totalWeeks;
        }
        $missing = $expectedWeeks - (int)$row['submitted_weeks'];
        $late = (int)$row['late_count'];

        if ($missing > 0 || $late > 0) {
            $parts = [];
            if ($missing > 0) {
                $parts[] = "thiếu $missing tuần nhật ký (kỳ vọng $expectedWeeks tuần, đã nộp {$row['submitted_weeks']})";
            }
            if ($late > 0) {
                $parts[] = "$late tuần nộp trễ";
            }
            $studentWarnings[] = ($row['title'] ?: 'Thực tập') . ': ' . implode(', ', $parts) . '.';
        }
    }
}

$counts = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'companies' => $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
    'positions' => $pdo->query("SELECT COUNT(*) FROM internship_positions")->fetchColumn(),
    'registrations' => $pdo->query("SELECT COUNT(*) FROM internship_registrations")->fetchColumn()
];

// Đăng ký gần đây: lọc theo role
if ($role === 'student') {
    $recentStmt = $pdo->prepare("
        SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name, ir.status, ir.applied_at
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        WHERE ir.student_id = ?
        ORDER BY ir.applied_at DESC
        LIMIT 6
    ");
    $recentStmt->execute([currentUserId()]);
    $recent = $recentStmt->fetchAll();
} elseif ($role === 'lecturer') {
    $recentStmt = $pdo->prepare("
        SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name, ir.status, ir.applied_at
        FROM internship_registrations ir
        JOIN internship_assignments ia ON ia.registration_id = ir.ir_id
        JOIN users u ON ir.student_id = u.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        WHERE ia.lecturer_id = ?
        ORDER BY ir.applied_at DESC
        LIMIT 6
    ");
    $recentStmt->execute([currentUserId()]);
    $recent = $recentStmt->fetchAll();
} elseif ($role === 'company') {
    $recentStmt = $pdo->prepare("
        SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name, ir.status, ir.applied_at
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        WHERE ip.company_id = ?
        ORDER BY ir.applied_at DESC
        LIMIT 6
    ");
    $recentStmt->execute([(int)currentCompanyId()]);
    $recent = $recentStmt->fetchAll();
} else {
    $recent = $pdo->query("
        SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name, ir.status, ir.applied_at
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        ORDER BY ir.applied_at DESC
        LIMIT 6
    ")->fetchAll();
}

include 'includes/header.php';
?>

<?php if ($role === 'student' && $studentWarnings): ?>
<div id="warnModal" style="display:flex; position:fixed; z-index:2000; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:16px; padding:28px; max-width:520px; width:100%; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
            <div style="width:44px; height:44px; border-radius:50%; background:#fef2f2; color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:24px;">⚠️</div>
            <h2 style="margin:0; font-size:20px; color:#991b1b;">Cảnh báo điều kiện thực tập</h2>
        </div>
        <p style="color:#475569; margin:0 0 12px;">Bạn đang chưa đáp ứng đủ điều kiện theo tiến độ. Vui lòng cập nhật nhật ký hàng tuần sớm:</p>
        <ul style="color:#334155; line-height:1.7; margin:0 0 18px; padding-left:20px;">
            <?php foreach ($studentWarnings as $w): ?>
                <li><?= e($w) ?></li>
            <?php endforeach; ?>
        </ul>
        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <a href="journals.php" style="padding:10px 18px; background:#3525cd; color:#fff; border-radius:8px; text-decoration:none; font-weight:600;">Nộp nhật ký ngay</a>
            <button type="button" onclick="document.getElementById('warnModal').style.display='none'" style="padding:10px 18px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; cursor:pointer; font-weight:500; color:#475569;">Đã hiểu</button>
        </div>
    </div>
</div>
<?php endif; ?>

<section class="hero">
    <h1>Hệ thống Quản lý Thực tập & Đánh giá Doanh nghiệp</h1>
    <p>
        Website quản lý toàn bộ vòng đời thực tập: tạo doanh nghiệp, mở vị trí,
        đăng ký thực tập, phân công giảng viên hướng dẫn, nộp nhật ký, upload hồ sơ,
        đánh giá kép và tính điểm tổng kết.
    </p>
</section>

<?php if ($role === 'student'): ?>
<div class="card" style="margin-top: 22px;">
    <h3>Thông tin thực tập của bạn</h3>
    <?php if (!$studentInfo): ?>
        <p class="help">Bạn chưa có đăng ký thực tập nào.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Đợt</th><th>Vị trí</th><th>Doanh nghiệp</th><th>Trạng thái</th><th>Giảng viên hướng dẫn</th></tr></thead>
                <tbody>
                <?php foreach ($studentInfo as $si): ?>
                    <tr>
                        <td><?= e($si['period_name'] ?: '—') ?></td>
                        <td><?= e($si['position_title'] ?: 'Chưa chọn vị trí') ?></td>
                        <td><?= e($si['company_name'] ?: '—') ?></td>
                        <td><span class="badge <?= strtolower(e($si['status'])) ?>"><?= e($si['status']) ?></span></td>
                        <td>
                            <?php if ($si['lecturer_name']): ?>
                                <b><?= e($si['lecturer_name']) ?></b>
                                <?php if ($si['lecturer_email']): ?><br><span class="help"><?= e($si['lecturer_email']) ?></span><?php endif; ?>
                            <?php else: ?>
                                <span class="badge pending">Chưa phân công</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="grid grid-4" style="margin-top: 22px;">
    <div class="card stat"><div class="label">Users</div><div class="num"><?= e($counts['users']) ?></div></div>
    <div class="card stat"><div class="label">Companies</div><div class="num"><?= e($counts['companies']) ?></div></div>
    <div class="card stat"><div class="label">Positions</div><div class="num"><?= e($counts['positions']) ?></div></div>
    <div class="card stat"><div class="label">Registrations</div><div class="num"><?= e($counts['registrations']) ?></div></div>
</div>

<div class="card" style="margin-top: 22px;">
    <h3>Đăng ký gần đây</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sinh viên</th>
                    <th>Vị trí</th>
                    <th>Doanh nghiệp</th>
                    <th>Trạng thái</th>
                    <th>Ngày đăng ký</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$recent): ?>
                <tr><td colspan="6">Chưa có đăng ký nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($recent as $r): ?>
                <tr>
                    <td>#<?= e($r['ir_id']) ?></td>
                    <td><?= e($r['student_name']) ?></td>
                    <td><?= e($r['title']) ?></td>
                    <td><?= e($r['company_name']) ?></td>
                    <td><span class="badge <?= strtolower(e($r['status'])) ?>"><?= e($r['status']) ?></span></td>
                    <td><?= e($r['applied_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
