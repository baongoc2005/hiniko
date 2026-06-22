<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin', 'student', 'company']);

$pageTitle = "Đăng ký thực tập";
$active = "registrations";
$message = "";
$type = "success";

$isStudent = currentRole() === 'student';
$isAdmin = currentRole() === 'admin';
$isCompany = currentRole() === 'company';
$myCompanyId = currentCompanyId();

// Thông tin SV của user đang đăng nhập (cho popup tạo đăng ký)
$myStudent = $isStudent ? currentStudentInfo($pdo) : ['student_code' => null, 'major' => null];

// Doanh nghiệp duyệt/từ chối đăng ký vào vị trí của công ty mình
if ($isCompany && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'company_decision') {
    $ir_id = (int)$_POST['ir_id'];
    $decision = $_POST['decision'] === 'Approved' ? 'Approved' : 'Rejected';

    // Xác minh đăng ký này thuộc 1 vị trí của công ty đang đăng nhập
    $chk = $pdo->prepare("
        SELECT COUNT(*) FROM internship_registrations ir
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        WHERE ir.ir_id = ? AND ip.company_id = ?
    ");
    $chk->execute([$ir_id, (int)$myCompanyId]);

    if ($chk->fetchColumn() == 0) {
        $type = "error";
        $message = "Bạn không có quyền duyệt đăng ký này.";
    } else {
        $pdo->prepare("UPDATE internship_registrations SET status = ? WHERE ir_id = ?")->execute([$decision, $ir_id]);
        $message = $decision === 'Approved' ? "Đã duyệt ứng viên." : "Đã từ chối ứng viên.";
    }
}

if (($isStudent || $isAdmin) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $student_id = $isStudent ? currentUserId() : (int)$_POST['student_id'];
    $period_id = (int)$_POST['period_id'];
    $mode = $_POST['mode'] ?? 'existing';

    if ($mode === 'propose') {
        // Đề xuất doanh nghiệp mới: không gắn position_id
        $proposed_company = trim($_POST['proposed_company'] ?? '');
        $proposed_position = trim($_POST['proposed_position'] ?? '');

        if ($proposed_company === '') {
            $type = "error";
            $message = "Vui lòng nhập tên doanh nghiệp đề xuất.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO internship_registrations (student_id, position_id, proposed_company, proposed_position, period_id, status)
                    VALUES (?, NULL, ?, ?, ?, 'Pending')
                ");
                $stmt->execute([$student_id, $proposed_company, $proposed_position !== '' ? $proposed_position : null, $period_id]);
                $message = "Đã gửi đề xuất doanh nghiệp mới. Chờ admin duyệt.";
            } catch (PDOException $e) {
                $type = "error";
                $message = "Có lỗi khi gửi đề xuất.";
            }
        }
    } else {
        // Chọn vị trí có sẵn
        $position_id = (int)$_POST['position_id'];

        if ($position_id <= 0) {
            $type = "error";
            $message = "Vui lòng chọn vị trí thực tập.";
        } else {
        try {
            $matchStmt = $pdo->prepare("
                SELECT s.major AS student_major, ip.major AS position_major
                FROM internship_positions ip
                LEFT JOIN students s ON s.user_id = ?
                WHERE ip.ip_id = ?
            ");
            $matchStmt->execute([$student_id, $position_id]);
            $matchInfo = $matchStmt->fetch();
            $studentMajor = $matchInfo['student_major'] ?? null;
            $positionMajor = $matchInfo['position_major'] ?? null;

            $quotaStmt = $pdo->prepare("
                SELECT ip.quota, COUNT(ir.ir_id) AS current_count
                FROM internship_positions ip
                LEFT JOIN internship_registrations ir
                    ON ip.ip_id = ir.position_id
                    AND ir.status IN ('Pending','Approved')
                WHERE ip.ip_id = ?
                GROUP BY ip.ip_id
            ");
            $quotaStmt->execute([$position_id]);
            $quotaInfo = $quotaStmt->fetch();

            if ($positionMajor !== null && $positionMajor !== '' && strcasecmp((string)$studentMajor, (string)$positionMajor) !== 0) {
                $type = "error";
                $message = "Không đúng ngành. Vị trí yêu cầu ngành \"{$positionMajor}\", ngành sinh viên là \"" . ($studentMajor ?: 'chưa cập nhật') . "\".";
            } elseif ($quotaInfo && $quotaInfo['current_count'] >= $quotaInfo['quota']) {
                $type = "error";
                $message = "Vị trí này đã đủ quota.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO internship_registrations (student_id, position_id, period_id, status)
                    VALUES (?, ?, ?, 'Pending')
                ");
                $stmt->execute([$student_id, $position_id, $period_id]);
                $message = "Đăng ký thực tập thành công. Trạng thái mặc định là Pending.";
            }
        } catch (PDOException $e) {
            $type = "error";
            $message = "Sinh viên đã đăng ký vị trí này trong cùng đợt thực tập hoặc dữ liệu không hợp lệ.";
        }
        }
    }
}

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $ir_id = (int)$_POST['ir_id'];
    $student_id = (int)$_POST['student_id'];
    $position_id = (int)$_POST['position_id'];
    $period_id = (int)$_POST['period_id'];
    $status = $_POST['status'];

    if (!in_array($status, ['Pending', 'Approved', 'Rejected'], true)) {
        $type = "error";
        $message = "Trạng thái không hợp lệ.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE internship_registrations
                SET student_id = ?, position_id = ?, period_id = ?, status = ?
                WHERE ir_id = ?
            ");
            $stmt->execute([
                $student_id,
                $position_id > 0 ? $position_id : null,
                $period_id,
                $status,
                $ir_id
            ]);
            $message = "Cập nhật đăng ký thành công.";
        } catch (PDOException $e) {
            $type = "error";
            $message = "Không thể cập nhật. Sinh viên có thể đã đăng ký vị trí này trong cùng đợt.";
        }
    }
}

if ($isAdmin && isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM internship_registrations WHERE ir_id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    $message = "Đã xóa đăng ký.";
}

$students = $pdo->query("
    SELECT u.*, s.student_code, s.major
    FROM users u
    LEFT JOIN students s ON s.user_id = u.user_id
    WHERE u.role = 'student'
    ORDER BY u.name
")->fetchAll();
$periods = $pdo->query("SELECT * FROM internship_periods ORDER BY start_date DESC")->fetchAll();

// Hiện mọi vị trí (kèm nhãn ngành); check đúng ngành xử lý khi submit
$positions = $pdo->query("
    SELECT ip.*, c.name AS company_name
    FROM internship_positions ip
    JOIN companies c ON ip.company_id = c.company_id
    ORDER BY c.name, ip.title
")->fetchAll();

if ($isStudent) {
    $regStmt = $pdo->prepare("
        SELECT ir.*, u.name AS student_name, s.student_code, ip.title, c.name AS company_name, p.name AS period_name
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        LEFT JOIN students s ON s.user_id = u.user_id
        LEFT JOIN internship_positions ip ON ir.position_id = ip.ip_id
        LEFT JOIN companies c ON ip.company_id = c.company_id
        JOIN internship_periods p ON ir.period_id = p.period_id
        WHERE ir.student_id = ?
        ORDER BY ir.applied_at DESC
    ");
    $regStmt->execute([currentUserId()]);
    $registrations = $regStmt->fetchAll();
} elseif ($isCompany) {
    $regStmt = $pdo->prepare("
        SELECT ir.*, u.name AS student_name, s.student_code, ip.title, c.name AS company_name, p.name AS period_name
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        LEFT JOIN students s ON s.user_id = u.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        JOIN internship_periods p ON ir.period_id = p.period_id
        WHERE ip.company_id = ?
        ORDER BY ir.applied_at DESC
    ");
    $regStmt->execute([(int)$myCompanyId]);
    $registrations = $regStmt->fetchAll();
} else {
    $registrations = $pdo->query("
        SELECT ir.*, u.name AS student_name, s.student_code, ip.title, c.name AS company_name, p.name AS period_name
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        LEFT JOIN students s ON s.user_id = u.user_id
        LEFT JOIN internship_positions ip ON ir.position_id = ip.ip_id
        LEFT JOIN companies c ON ip.company_id = c.company_id
        JOIN internship_periods p ON ir.period_id = p.period_id
        ORDER BY ir.applied_at DESC
    ")->fetchAll();
}

include 'includes/header.php';
?>

<style>
    .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); overflow-y:auto; }
    .modal.active { display:flex; align-items:flex-start; justify-content:center; padding:40px 16px; }
    .modal-content { background:#fff; border-radius:14px; padding:26px; width:100%; max-width:560px; box-shadow:0 4px 24px rgba(0,0,0,0.18); }
    .modal-header { font-size:19px; font-weight:700; margin-bottom:18px; color:#1e293b; display:flex; justify-content:space-between; align-items:center; }
    .modal-close { font-size:26px; font-weight:bold; color:#94a3b8; cursor:pointer; line-height:1; }
    .modal-close:hover { color:#475569; }
    .form-group { margin-bottom:14px; }
    .form-group label { display:block; font-weight:500; color:#334155; margin-bottom:6px; font-size:13px; }
    .form-group select, .form-group input { width:100%; padding:9px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; font-family:inherit; }
    .modal-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:22px; }
    .btn-cancel { padding:9px 18px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; cursor:pointer; font-weight:500; color:#475569; }
    .btn-submit { padding:9px 18px; background:#3525cd; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
</style>

<?php if ($message): ?><div class="alert <?= $type ?>"><?= e($message) ?></div><?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
    <div>
        <p class="help" style="margin:0;">Tổng cộng <?= count($registrations) ?> đăng ký.</p>
    </div>
    <?php if ($isStudent || $isAdmin): ?>
    <button onclick="openCreateRegModal()" style="background:#3525cd; color:#fff; padding:10px 18px; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
        ➕ Thêm đăng ký
    </button>
    <?php endif; ?>
</div>

<div class="card" style="margin-top: 22px;">
    <h3>Danh sách đăng ký</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Mã SV</th><th>Sinh viên</th><th>Vị trí</th><th>Công ty</th><th>Đợt</th><th>CV</th><th>Status</th><th>Ngày</th><?php if ($isAdmin || $isCompany): ?><th>Thao tác</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($registrations as $r): ?>
                <tr>
                    <td>#<?= e($r['ir_id']) ?></td>
                    <td><?= $r['student_code'] ? e($r['student_code']) : '<span class="help">—</span>' ?></td>
                    <td><?= e($r['student_name']) ?></td>
                    <td>
                        <?php if ($r['title']): ?>
                            <b><?= e($r['title']) ?></b>
                        <?php elseif ($r['proposed_position'] || $r['proposed_company']): ?>
                            <b><?= e($r['proposed_position'] ?: 'Vị trí đề xuất') ?></b>
                            <br><span class="badge pending">Đề xuất mới</span>
                        <?php else: ?>
                            <span class="help">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $r['company_name'] ? e($r['company_name']) : ($r['proposed_company'] ? e($r['proposed_company']) : '<span class="help">—</span>') ?></td>
                    <td><?= e($r['period_name']) ?></td>
                    <td>
                        <?php if ($r['cv_file']): ?>
                            <a class="btn small" href="<?= e($r['cv_file']) ?>" target="_blank">📄 Xem CV</a>
                        <?php else: ?>
                            <span class="help">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= strtolower(e($r['status'])) ?>"><?= e($r['status']) ?></span></td>
                    <td><?= e($r['applied_at']) ?></td>
                    <?php if ($isAdmin): ?>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <button class="btn small" type="button"
                                onclick="openEditRegModal(<?= e($r['ir_id']) ?>, <?= e($r['student_id']) ?>, <?= e($r['position_id'] ?? 0) ?>, <?= e($r['period_id']) ?>, '<?= e($r['status']) ?>')"
                                title="Sửa">✎</button>
                            <a class="btn danger small" onclick="return confirm('Xóa đăng ký này?')" href="?delete=<?= e($r['ir_id']) ?>" title="Xóa">🗑</a>
                        </div>
                    </td>
                    <?php elseif ($isCompany): ?>
                    <td>
                        <?php if ($r['status'] === 'Pending'): ?>
                        <div style="display:flex; gap:6px;">
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="company_decision">
                                <input type="hidden" name="ir_id" value="<?= e($r['ir_id']) ?>">
                                <input type="hidden" name="decision" value="Approved">
                                <button class="btn small" type="submit">Duyệt</button>
                            </form>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="company_decision">
                                <input type="hidden" name="ir_id" value="<?= e($r['ir_id']) ?>">
                                <input type="hidden" name="decision" value="Rejected">
                                <button class="btn danger small" type="submit">Từ chối</button>
                            </form>
                        </div>
                        <?php else: ?>
                            <span class="help"><?= $r['status'] === 'Approved' ? '✓ Đã duyệt' : '✕ Đã từ chối' ?></span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Registration Modal -->
<div id="createRegModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            Tạo đăng ký thực tập
            <span class="modal-close" onclick="closeCreateRegModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <?php if ($isStudent): ?>
                <div class="form-group">
                    <label>Sinh viên</label>
                    <input type="text" value="<?= e(currentUser()['name'] ?? '') ?> (<?= e($myStudent['major'] ?? 'chưa có ngành') ?>)" disabled>
                </div>
                <div class="form-group">
                    <label>Mã sinh viên</label>
                    <input type="text" value="<?= e($myStudent['student_code'] ?? '') ?>" disabled placeholder="Chưa có mã">
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label>Sinh viên *</label>
                    <select name="student_id" id="regStudentSelect" required onchange="syncStudentCode()">
                        <?php foreach ($students as $s): ?>
                            <option value="<?= e($s['user_id']) ?>" data-code="<?= e($s['student_code'] ?? '') ?>"><?= e($s['name']) ?> — <?= $s['student_code'] ? e($s['student_code']) : 'Chưa có mã' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mã sinh viên</label>
                    <input type="text" id="regStudentCode" disabled placeholder="Tự điền theo sinh viên">
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Hình thức</label>
                <div style="display:flex; gap:16px; padding:4px 0;">
                    <label style="display:flex; align-items:center; gap:6px; font-weight:400; cursor:pointer;">
                        <input type="radio" name="mode" value="existing" checked onchange="toggleRegMode()"> Chọn doanh nghiệp có sẵn
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-weight:400; cursor:pointer;">
                        <input type="radio" name="mode" value="propose" onchange="toggleRegMode()"> Đề xuất doanh nghiệp mới
                    </label>
                </div>
            </div>

            <div id="modeExisting">
                <div class="form-group">
                    <label>Vị trí thực tập *</label>
                    <select name="position_id" id="regPositionSelect">
                        <option value="">— Chọn vị trí —</option>
                        <?php foreach ($positions as $p): ?>
                            <option value="<?= e($p['ip_id']) ?>"><?= e($p['company_name']) ?> — <?= e($p['title']) ?><?= $p['major'] ? ' [' . e($p['major']) . ']' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isStudent): ?><p class="help" style="margin-top:6px;">Hệ thống sẽ kiểm tra đúng ngành và quota khi gửi.</p><?php endif; ?>
                </div>
            </div>

            <div id="modePropose" style="display:none;">
                <div class="form-group">
                    <label>Tên doanh nghiệp đề xuất *</label>
                    <input type="text" name="proposed_company" id="regProposedCompany" placeholder="VD: Công ty TNHH ABC">
                </div>
                <div class="form-group">
                    <label>Vị trí mong muốn</label>
                    <input type="text" name="proposed_position" placeholder="VD: Software Engineer Intern">
                </div>
                <p class="help">Đề xuất sẽ ở trạng thái Pending để admin xem xét và liên hệ doanh nghiệp.</p>
            </div>

            <div class="form-group">
                <label>Đợt thực tập *</label>
                <select name="period_id" required>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= e($p['period_id']) ?>"><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeCreateRegModal()">Hủy</button>
                <button type="submit" class="btn-submit">Tạo đăng ký</button>
            </div>
        </form>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Edit Registration Modal -->
<div id="editRegModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            Sửa đăng ký thực tập
            <span class="modal-close" onclick="closeEditRegModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="ir_id" id="editIrId">
            <div class="form-group">
                <label>Sinh viên *</label>
                <select name="student_id" id="editStudentId" required>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= e($s['user_id']) ?>"><?= e($s['name']) ?> — <?= $s['student_code'] ? e($s['student_code']) : 'Chưa có mã' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Vị trí thực tập *</label>
                <select name="position_id" id="editPositionId" required>
                    <?php foreach ($positions as $p): ?>
                        <option value="<?= e($p['ip_id']) ?>"><?= e($p['company_name']) ?> — <?= e($p['title']) ?><?= $p['major'] ? ' [' . e($p['major']) . ']' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Đợt thực tập *</label>
                <select name="period_id" id="editPeriodId" required>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= e($p['period_id']) ?>"><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Trạng thái *</label>
                <select name="status" id="editStatus" required>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditRegModal()">Hủy</button>
                <button type="submit" class="btn-submit">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openCreateRegModal() { document.getElementById('createRegModal').classList.add('active'); syncStudentCode(); toggleRegMode(); }
function closeCreateRegModal() { document.getElementById('createRegModal').classList.remove('active'); }
function toggleRegMode() {
    var mode = document.querySelector('input[name="mode"]:checked');
    mode = mode ? mode.value : 'existing';
    var existing = document.getElementById('modeExisting');
    var propose = document.getElementById('modePropose');
    var posSel = document.getElementById('regPositionSelect');
    var propCompany = document.getElementById('regProposedCompany');
    if (mode === 'propose') {
        existing.style.display = 'none';
        propose.style.display = '';
        if (posSel) posSel.required = false;
        if (propCompany) propCompany.required = true;
    } else {
        existing.style.display = '';
        propose.style.display = 'none';
        if (posSel) posSel.required = true;
        if (propCompany) propCompany.required = false;
    }
}
function syncStudentCode() {
    var sel = document.getElementById('regStudentSelect');
    var codeInput = document.getElementById('regStudentCode');
    if (!sel || !codeInput) return;
    var opt = sel.options[sel.selectedIndex];
    codeInput.value = opt ? (opt.getAttribute('data-code') || '') : '';
}
function openEditRegModal(irId, studentId, positionId, periodId, status) {
    document.getElementById('editIrId').value = irId;
    document.getElementById('editStudentId').value = studentId;
    var posSel = document.getElementById('editPositionId');
    if (positionId > 0) posSel.value = positionId;
    document.getElementById('editPeriodId').value = periodId;
    document.getElementById('editStatus').value = status;
    document.getElementById('editRegModal').classList.add('active');
}
function closeEditRegModal() { document.getElementById('editRegModal').classList.remove('active'); }
window.addEventListener('click', function(e) {
    if (e.target.classList && e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
