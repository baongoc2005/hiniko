<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin', 'lecturer', 'company']);

$pageTitle = "Đánh giá";
$active = "evaluations";
$message = "";
$type = "success";

$role = currentRole();
$isAdmin = $role === 'admin';
$isLecturer = $role === 'lecturer';
$isCompany = $role === 'company';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_id = (int)$_POST['registration_id'];
    // Company chỉ được nhập đánh giá doanh nghiệp; lecturer chỉ đánh giá giảng viên
    if ($isCompany) {
        $evalType = 'company';
    } elseif ($isLecturer) {
        $evalType = 'lecturer';
    } else {
        $evalType = $_POST['type'] === 'lecturer' ? 'lecturer' : 'company';
    }
    $score = (float)$_POST['score'];
    $comment = trim($_POST['comment']);

    // Kiểm tra quyền trên đăng ký đang chấm
    $allowed = true;
    if ($isCompany) {
        $chk = $pdo->prepare("
            SELECT COUNT(*) FROM internship_registrations ir
            JOIN internship_positions ip ON ir.position_id = ip.ip_id
            WHERE ir.ir_id = ? AND ip.company_id = ?
        ");
        $chk->execute([$registration_id, (int)currentCompanyId()]);
        $allowed = $chk->fetchColumn() > 0;
    } elseif ($isLecturer) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM internship_assignments WHERE registration_id = ? AND lecturer_id = ?");
        $chk->execute([$registration_id, currentUserId()]);
        $allowed = $chk->fetchColumn() > 0;
    }

    if (!$allowed) {
        $type = "error";
        $message = "Bạn không có quyền đánh giá đăng ký này.";
    } else {
        if ($evalType === 'company') {
            $stmt = $pdo->prepare("
                INSERT INTO company_evaluations (registration_id, score, comment)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE score = VALUES(score), comment = VALUES(comment), evaluated_at = CURRENT_TIMESTAMP
            ");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO lecturer_evaluations (registration_id, score, comment)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE score = VALUES(score), comment = VALUES(comment), evaluated_at = CURRENT_TIMESTAMP
            ");
        }
        $stmt->execute([$registration_id, $score, $comment]);
        $message = "Lưu đánh giá thành công.";
    }
}

// Danh sách đăng ký để chấm điểm (lọc theo role)
if ($isCompany) {
    $rStmt = $pdo->prepare("
        SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        WHERE ir.status = 'Approved' AND ip.company_id = ?
        ORDER BY ir.applied_at DESC
    ");
    $rStmt->execute([(int)currentCompanyId()]);
    $registrations = $rStmt->fetchAll();
} elseif ($isLecturer) {
    $rStmt = $pdo->prepare("
        SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name
        FROM internship_registrations ir
        JOIN internship_assignments ia ON ia.registration_id = ir.ir_id
        JOIN users u ON ir.student_id = u.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        WHERE ir.status = 'Approved' AND ia.lecturer_id = ?
        ORDER BY ir.applied_at DESC
    ");
    $rStmt->execute([currentUserId()]);
    $registrations = $rStmt->fetchAll();
} else {
    $registrations = $pdo->query("
        SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        WHERE ir.status = 'Approved'
        ORDER BY ir.applied_at DESC
    ")->fetchAll();
}

// Bảng tổng quan: lọc theo role
$evalBase = "
    SELECT ir.ir_id, u.name AS student_name, s.student_code, ip.title,
           ce.score AS company_score, le.score AS lecturer_score,
           ce.comment AS company_comment, le.comment AS lecturer_comment
    FROM internship_registrations ir
    JOIN users u ON ir.student_id = u.user_id
    LEFT JOIN students s ON s.user_id = u.user_id
    JOIN internship_positions ip ON ir.position_id = ip.ip_id
    LEFT JOIN company_evaluations ce ON ir.ir_id = ce.registration_id
    LEFT JOIN lecturer_evaluations le ON ir.ir_id = le.registration_id
";

if ($isCompany) {
    $eStmt = $pdo->prepare($evalBase . " WHERE ip.company_id = ? ORDER BY ir.applied_at DESC");
    $eStmt->execute([(int)currentCompanyId()]);
    $evaluations = $eStmt->fetchAll();
} elseif ($isLecturer) {
    $eStmt = $pdo->prepare($evalBase . "
        JOIN internship_assignments ia ON ia.registration_id = ir.ir_id
        WHERE ia.lecturer_id = ? ORDER BY ir.applied_at DESC");
    $eStmt->execute([currentUserId()]);
    $evaluations = $eStmt->fetchAll();
} else {
    $evaluations = $pdo->query($evalBase . " ORDER BY ir.applied_at DESC")->fetchAll();
}

include 'includes/header.php';
?>

<style>
    .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); overflow-y:auto; }
    .modal.active { display:flex; align-items:flex-start; justify-content:center; padding:40px 16px; }
    .modal-content { background:#fff; border-radius:14px; padding:26px; width:100%; max-width:560px; box-shadow:0 4px 24px rgba(0,0,0,0.18); }
    .modal-header { font-size:19px; font-weight:700; margin-bottom:18px; color:#1e293b; display:flex; justify-content:space-between; align-items:center; }
    .modal-close { font-size:26px; font-weight:bold; color:#94a3b8; cursor:pointer; line-height:1; }
    .form-group { margin-bottom:14px; }
    .form-group label { display:block; font-weight:500; color:#334155; margin-bottom:6px; font-size:13px; }
    .form-group select, .form-group input, .form-group textarea { width:100%; padding:9px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; font-family:inherit; }
    .modal-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:22px; }
    .btn-cancel { padding:9px 18px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; cursor:pointer; font-weight:500; color:#475569; }
    .btn-submit { padding:9px 18px; background:#3525cd; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
</style>

<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>

<div style="display:flex; justify-content:flex-end; align-items:center; gap:16px; flex-wrap:wrap;">
    <button onclick="openEvalModal()" style="background:#3525cd; color:#fff; padding:10px 18px; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
        ➕ Thêm đánh giá
    </button>
</div>

<!-- Evaluation Modal -->
<div id="evalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            Nhập đánh giá
            <span class="modal-close" onclick="closeEvalModal()">&times;</span>
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Đăng ký đã duyệt *</label>
                <select name="registration_id" required>
                    <?php foreach ($registrations as $r): ?>
                        <option value="<?= e($r['ir_id']) ?>">#<?= e($r['ir_id']) ?> — <?= e($r['student_name']) ?> — <?= e($r['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label>Loại đánh giá</label>
                    <?php if ($isAdmin): ?>
                    <select name="type">
                        <option value="company">Company Evaluation</option>
                        <option value="lecturer">Lecturer Evaluation</option>
                    </select>
                    <?php else: ?>
                    <input type="text" value="<?= $isCompany ? 'Company Evaluation (Doanh nghiệp)' : 'Lecturer Evaluation (Giảng viên)' ?>" disabled>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Điểm số *</label>
                    <input type="number" name="score" min="0" max="10" step="0.1" required>
                </div>
            </div>
            <div class="form-group">
                <label>Nhận xét</label>
                <textarea name="comment" rows="4" placeholder="Nhập nhận xét chi tiết"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEvalModal()">Hủy</button>
                <button type="submit" class="btn-submit">Lưu đánh giá</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEvalModal() { document.getElementById('evalModal').classList.add('active'); }
function closeEvalModal() { document.getElementById('evalModal').classList.remove('active'); }
window.addEventListener('click', function(e) {
    var m = document.getElementById('evalModal');
    if (e.target === m) m.classList.remove('active');
});
</script>

<div class="card" style="margin-top:22px;">
    <h3>Tổng quan đánh giá</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th><?= $isAdmin ? 'Reg ID' : 'Mã SV' ?></th><th>Sinh viên</th><th>Vị trí</th><th>Điểm DN</th><?php if (!$isCompany): ?><th>Điểm GV</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($evaluations as $eRow): ?>
                <tr>
                    <td><?= $isAdmin ? '#' . e($eRow['ir_id']) : ($eRow['student_code'] ? e($eRow['student_code']) : '<span class="help">—</span>') ?></td>
                    <td><?= e($eRow['student_name']) ?></td>
                    <td><?= e($eRow['title']) ?></td>
                    <td><?= $eRow['company_score'] !== null ? e($eRow['company_score']) : '---' ?></td>
                    <?php if (!$isCompany): ?>
                    <td><?= $eRow['lecturer_score'] !== null ? e($eRow['lecturer_score']) : '---' ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
