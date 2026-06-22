<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin', 'student', 'lecturer']);

$pageTitle = "Nhật ký hàng tuần";
$active = "journals";
$message = "";
$type = "success";

const TOTAL_WEEKS = 6;

$role = currentRole();
$isStudent = $role === 'student';
$isLecturer = $role === 'lecturer';
$isAdmin = $role === 'admin';

// Chỉ student được nộp nhật ký (kèm file báo cáo tùy chọn) theo tuần
if ($isStudent && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_id = (int)$_POST['registration_id'];

    $own = $pdo->prepare("SELECT COUNT(*) FROM internship_registrations WHERE ir_id = ? AND student_id = ?");
    $own->execute([$registration_id, currentUserId()]);
    $allowed = $own->fetchColumn() > 0;

    if (!$allowed) {
        $type = "error";
        $message = "Bạn không có quyền nộp nhật ký cho đăng ký này.";
    } else {
        $week_number = (int)$_POST['week_number'];
        $content = trim($_POST['content']);

        if ($week_number < 1 || $week_number > TOTAL_WEEKS) {
            $type = "error";
            $message = "Tuần phải từ 1 đến " . TOTAL_WEEKS . ".";
        } else {
            // Hệ thống tự xác định trạng thái: so ngày nộp với hạn kết thúc tuần W
            $pStmt = $pdo->prepare("
                SELECT p.start_date
                FROM internship_registrations ir
                JOIN internship_periods p ON ir.period_id = p.period_id
                WHERE ir.ir_id = ?
            ");
            $pStmt->execute([$registration_id]);
            $startDate = $pStmt->fetchColumn();

            $status = 'Submitted';
            if ($startDate) {
                $start = new DateTimeImmutable($startDate);
                // Hạn nộp tuần W = ngày bắt đầu + (W-1)*7 + 6 ngày (hết ngày cuối tuần đó)
                $deadline = $start->modify('+' . (($week_number - 1) * 7 + 6) . ' days');
                $today = new DateTimeImmutable('today');
                if ($today > $deadline) {
                    $status = 'Late';
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO weekly_journals (registration_id, week_number, content, status)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE content = VALUES(content), status = VALUES(status), created_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$registration_id, $week_number, $content, $status]);

            // File báo cáo tuần (tùy chọn)
            if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
                $allowedExt = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
                $ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowedExt)) {
                    $type = "error";
                    $message = "Nhật ký đã lưu, nhưng file không hợp lệ (chỉ PDF, DOC, DOCX, PNG, JPG).";
                } else {
                    if (!is_dir('uploads')) {
                        mkdir('uploads', 0777, true);
                    }
                    $safeName = 'report_' . $registration_id . '_w' . $week_number . '_' . time() . '.' . $ext;
                    $target = 'uploads/' . $safeName;
                    if (move_uploaded_file($_FILES['report_file']['tmp_name'], $target)) {
                        $fstmt = $pdo->prepare("INSERT INTO files (registration_id, week_number, file_path, file_type) VALUES (?, ?, ?, ?)");
                        $fstmt->execute([$registration_id, $week_number, $target, $ext]);
                        $message = "Lưu nhật ký tuần $week_number và file báo cáo thành công.";
                    } else {
                        $type = "error";
                        $message = "Nhật ký đã lưu nhưng không lưu được file.";
                    }
                }
            } else {
                $message = "Lưu nhật ký tuần $week_number thành công.";
            }
        }
    }
}

// Giảng viên lưu nhận xét cho từng tuần (chỉ SV mình phụ trách)
if ($isLecturer && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_comment') {
    $registration_id = (int)$_POST['registration_id'];
    $week_number = (int)$_POST['week_number'];
    $comment = trim($_POST['lecturer_comment'] ?? '');

    // Xác minh GV được phân công đăng ký này
    $chk = $pdo->prepare("SELECT COUNT(*) FROM internship_assignments WHERE registration_id = ? AND lecturer_id = ?");
    $chk->execute([$registration_id, currentUserId()]);

    if ($chk->fetchColumn() == 0) {
        $type = "error";
        $message = "Bạn không phụ trách sinh viên này.";
    } else {
        // Chỉ cập nhật nhận xét nếu tuần đó đã có nhật ký
        $upd = $pdo->prepare("UPDATE weekly_journals SET lecturer_comment = ? WHERE registration_id = ? AND week_number = ?");
        $upd->execute([$comment !== '' ? $comment : null, $registration_id, $week_number]);

        if ($upd->rowCount() === 0) {
            $type = "error";
            $message = "Sinh viên chưa nộp nhật ký tuần $week_number nên chưa thể nhận xét.";
        } else {
            $message = "Đã lưu nhận xét tuần $week_number.";
        }
    }
}

// Đăng ký của student để nộp
$registrations = [];
if ($isStudent) {
    $regStmt = $pdo->prepare("
        SELECT ir.ir_id, u.name AS student_name, ip.title, p.start_date AS period_start
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        LEFT JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN internship_periods p ON ir.period_id = p.period_id
        WHERE ir.student_id = ?
        ORDER BY ir.applied_at DESC
    ");
    $regStmt->execute([currentUserId()]);
    $registrations = $regStmt->fetchAll();
}

// Lịch tuần của đăng ký đầu tiên (để hiện khoảng ngày trong dropdown)
$firstWeeks = $registrations ? periodWeeks($registrations[0]['period_start']) : [];

// Danh sách SINH VIÊN (mỗi đăng ký 1 dòng) theo role + tiến độ nhật ký
$studentBase = "
    SELECT ir.ir_id, u.name AS student_name, s.student_code,
           ip.title AS position_title, c.name AS company_name,
           COUNT(DISTINCT CASE WHEN wj.status IN ('Submitted','Late') THEN wj.week_number END) AS submitted_weeks
    FROM internship_registrations ir
    JOIN users u ON ir.student_id = u.user_id
    LEFT JOIN students s ON s.user_id = u.user_id
    LEFT JOIN internship_positions ip ON ir.position_id = ip.ip_id
    LEFT JOIN companies c ON ip.company_id = c.company_id
    LEFT JOIN weekly_journals wj ON wj.registration_id = ir.ir_id
";

if ($isStudent) {
    $sStmt = $pdo->prepare($studentBase . " WHERE ir.student_id = ? GROUP BY ir.ir_id, u.name, s.student_code, ip.title, c.name ORDER BY u.name");
    $sStmt->execute([currentUserId()]);
    $studentRows = $sStmt->fetchAll();
} elseif ($isLecturer) {
    $sStmt = $pdo->prepare($studentBase . "
        JOIN internship_assignments ia ON ia.registration_id = ir.ir_id
        WHERE ia.lecturer_id = ?
        GROUP BY ir.ir_id, u.name, s.student_code, ip.title, c.name ORDER BY u.name");
    $sStmt->execute([currentUserId()]);
    $studentRows = $sStmt->fetchAll();
} else {
    $studentRows = $pdo->query($studentBase . " GROUP BY ir.ir_id, u.name, s.student_code, ip.title, c.name ORDER BY u.name")->fetchAll();
}

// Nạp dữ liệu chi tiết 6 tuần (nhật ký + file) cho các đăng ký hiển thị, để dựng popup
$regIds = array_column($studentRows, 'ir_id');
$journalsByReg = [];
$filesByReg = [];
if ($regIds) {
    $in = implode(',', array_fill(0, count($regIds), '?'));

    $jq = $pdo->prepare("SELECT registration_id, week_number, content, status, lecturer_comment, created_at FROM weekly_journals WHERE registration_id IN ($in)");
    $jq->execute($regIds);
    foreach ($jq->fetchAll() as $row) {
        $journalsByReg[$row['registration_id']][(int)$row['week_number']] = $row;
    }

    $fq = $pdo->prepare("SELECT registration_id, week_number, file_path, file_type FROM files WHERE registration_id IN ($in) AND week_number IS NOT NULL");
    $fq->execute($regIds);
    foreach ($fq->fetchAll() as $row) {
        $filesByReg[$row['registration_id']][(int)$row['week_number']] = $row;
    }
}

include 'includes/header.php';
?>

<style>
    .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); overflow-y:auto; }
    .modal.active { display:flex; align-items:flex-start; justify-content:center; padding:40px 16px; }
    .modal-content { background:#fff; border-radius:14px; padding:26px; width:100%; max-width:640px; box-shadow:0 4px 24px rgba(0,0,0,0.18); }
    .modal-header { font-size:19px; font-weight:700; margin-bottom:6px; color:#1e293b; display:flex; justify-content:space-between; align-items:center; }
    .modal-close { font-size:26px; font-weight:bold; color:#94a3b8; cursor:pointer; line-height:1; }
    .week-item { border:1px solid #e2e8f0; border-radius:10px; padding:14px 16px; margin-top:10px; }
    .week-item h4 { margin:0 0 6px; font-size:15px; color:#1e293b; display:flex; justify-content:space-between; align-items:center; }
    .week-item p { margin:6px 0 0; color:#475569; font-size:14px; white-space:pre-line; }
    .form-group { margin-bottom:14px; }
    .form-group label { display:block; font-weight:500; color:#334155; margin-bottom:6px; font-size:13px; }
    .form-group select, .form-group input, .form-group textarea { width:100%; padding:9px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; font-family:inherit; }
    .modal-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:20px; }
    .btn-cancel { padding:9px 18px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; cursor:pointer; color:#475569; }
    .btn-submit { padding:9px 18px; background:#3525cd; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
</style>

<?php if ($message): ?><div class="alert <?= $type ?>"><?= e($message) ?></div><?php endif; ?>

<?php if ($isStudent): ?>
<div style="display:flex; justify-content:flex-end; align-items:center; gap:16px; flex-wrap:wrap;">
    <button onclick="openSubmitModal()" style="background:#3525cd; color:#fff; padding:10px 18px; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
        ➕ Nộp nhật ký tuần
    </button>
</div>
<?php else: ?>
<p class="help" style="margin:0;"><?= $isLecturer ? 'Sinh viên bạn phụ trách' : 'Toàn bộ sinh viên' ?> — bấm vào sinh viên để xem chi tiết 6 tuần.</p>
<?php endif; ?>

<div class="card" style="margin-top: 20px;">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Mã SV</th><th>Sinh viên</th><th>Vị trí</th><th>Công ty</th><th>Tiến độ (tuần)</th><th>Chi tiết</th></tr></thead>
            <tbody>
            <?php if (!$studentRows): ?>
                <tr><td colspan="6">Chưa có sinh viên nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($studentRows as $s): ?>
                <tr>
                    <td><?= $s['student_code'] ? e($s['student_code']) : '<span class="help">—</span>' ?></td>
                    <td><b><?= e($s['student_name']) ?></b></td>
                    <td><?= $s['position_title'] ? e($s['position_title']) : '<span class="help">—</span>' ?></td>
                    <td><?= $s['company_name'] ? e($s['company_name']) : '<span class="help">—</span>' ?></td>
                    <td>
                        <span class="badge <?= (int)$s['submitted_weeks'] >= TOTAL_WEEKS ? 'approved' : 'pending' ?>">
                            <?= e($s['submitted_weeks']) ?>/<?= TOTAL_WEEKS ?>
                        </span>
                    </td>
                    <td><button class="btn small" onclick="openWeekModal(<?= e($s['ir_id']) ?>)">Xem 6 tuần</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Popup chi tiết 6 tuần cho từng sinh viên -->
<?php foreach ($studentRows as $s): ?>
<div id="weekModal_<?= e($s['ir_id']) ?>" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>Nhật ký 6 tuần — <?= e($s['student_name']) ?></span>
            <span class="modal-close" onclick="closeWeekModal(<?= e($s['ir_id']) ?>)">&times;</span>
        </div>
        <p class="help"><?= e($s['position_title'] ?: '—') ?> · <?= e($s['company_name'] ?: '—') ?></p>

        <?php for ($w = 1; $w <= TOTAL_WEEKS; $w++):
            $j = $journalsByReg[$s['ir_id']][$w] ?? null;
            $f = $filesByReg[$s['ir_id']][$w] ?? null;
        ?>
            <div class="week-item">
                <h4>
                    Tuần <?= $w ?>
                    <?php if ($j): ?>
                        <span class="badge <?= $j['status'] === 'Submitted' ? 'approved' : ($j['status'] === 'Late' ? 'pending' : 'rejected') ?>"><?= e($j['status']) ?></span>
                    <?php else: ?>
                        <span class="badge rejected">Chưa nộp</span>
                    <?php endif; ?>
                </h4>
                <?php if ($j): ?>
                    <p><?= e($j['content']) ?></p>
                    <p class="help" style="margin-top:6px;">Nộp lúc: <?= e($j['created_at']) ?></p>
                <?php else: ?>
                    <p class="help">Sinh viên chưa nộp nhật ký tuần này.</p>
                <?php endif; ?>
                <?php if ($f): ?>
                    <p style="margin-top:8px;"><a class="btn small" href="<?= e($f['file_path']) ?>" target="_blank">📄 Xem file báo cáo</a></p>
                <?php endif; ?>

                <?php $lc = $j['lecturer_comment'] ?? null; ?>
                <?php if ($lc): ?>
                    <div style="margin-top:10px; padding:10px 12px; background:#eef2ff; border-radius:8px;">
                        <span style="font-weight:600; color:#3525cd; font-size:13px;">Nhận xét của GVHD:</span>
                        <p style="margin:4px 0 0; color:#334155; font-size:14px; white-space:pre-line;"><?= e($lc) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($isLecturer && $j): ?>
                    <form method="POST" style="margin-top:10px; display:flex; gap:8px; align-items:flex-start;">
                        <input type="hidden" name="action" value="save_comment">
                        <input type="hidden" name="registration_id" value="<?= e($s['ir_id']) ?>">
                        <input type="hidden" name="week_number" value="<?= $w ?>">
                        <textarea name="lecturer_comment" rows="2" placeholder="Nhập nhận xét cho tuần này..." style="flex:1; padding:8px 10px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; font-family:inherit;"><?= e($lc ?? '') ?></textarea>
                        <button class="btn small" type="submit">Lưu</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endfor; ?>

        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeWeekModal(<?= e($s['ir_id']) ?>)">Đóng</button>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php if ($isStudent): ?>
<!-- Popup nộp nhật ký tuần -->
<div id="submitModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            Nộp nhật ký tuần
            <span class="modal-close" onclick="closeSubmitModal()">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Đăng ký thực tập *</label>
                <select name="registration_id" required>
                    <?php foreach ($registrations as $r): ?>
                        <option value="<?= e($r['ir_id']) ?>">#<?= e($r['ir_id']) ?> — <?= e($r['title'] ?: 'Chưa có vị trí') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tuần số (1–<?= TOTAL_WEEKS ?>) *</label>
                <select name="week_number" required>
                    <?php for ($w = 1; $w <= TOTAL_WEEKS; $w++): ?>
                        <?php $wk = $firstWeeks[$w - 1] ?? null; ?>
                        <option value="<?= $w ?>">Tuần <?= $w ?><?= $wk ? ' (' . e($wk['start']) . ' → ' . e($wk['end']) . ')' : '' ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <p class="help">Trạng thái (Đúng hạn / Trễ) được hệ thống tự xác định theo hạn cuối mỗi tuần.</p>
            <div class="form-group">
                <label>Nội dung nhật ký *</label>
                <textarea name="content" rows="4" required placeholder="Mô tả công việc trong tuần..."></textarea>
            </div>
            <div class="form-group">
                <label>File báo cáo (PDF, DOC, DOCX, PNG, JPG) — tùy chọn</label>
                <input type="file" name="report_file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeSubmitModal()">Hủy</button>
                <button type="submit" class="btn-submit">Lưu nhật ký</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openWeekModal(id) { document.getElementById('weekModal_' + id).classList.add('active'); }
function closeWeekModal(id) { document.getElementById('weekModal_' + id).classList.remove('active'); }
<?php if ($isStudent): ?>
function openSubmitModal() { document.getElementById('submitModal').classList.add('active'); }
function closeSubmitModal() { document.getElementById('submitModal').classList.remove('active'); }
<?php endif; ?>
window.addEventListener('click', function(e) {
    if (e.target.classList && e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
