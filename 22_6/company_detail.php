<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin', 'student', 'lecturer']);

$companyId = (int)($_GET['id'] ?? 0);
$message = "";
$type = "success";

$role = currentRole();
$isAdmin = $role === 'admin';
$isStudent = $role === 'student';

// Lấy doanh nghiệp
$cStmt = $pdo->prepare("SELECT * FROM companies WHERE company_id = ?");
$cStmt->execute([$companyId]);
$company = $cStmt->fetch();

if (!$company) {
    $pageTitle = "Không tìm thấy";
    $active = "companies";
    include 'includes/header.php';
    echo '<div class="alert error">Không tìm thấy doanh nghiệp.</div><a class="btn" href="companies.php">← Quay lại danh sách</a>';
    include 'includes/footer.php';
    exit;
}

$pageTitle = $company['name'];
$active = "companies";

// Sinh viên ứng tuyển một vị trí -> tạo đăng ký Pending kèm CV (không kiểm tra ngành)
if ($isStudent && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply') {
    $position_id = (int)$_POST['position_id'];
    $period_id = (int)$pdo->query("SELECT period_id FROM internship_periods ORDER BY start_date DESC LIMIT 1")->fetchColumn();

    if ($period_id <= 0) {
        $type = "error";
        $message = "Hiện chưa có đợt thực tập nào để ứng tuyển.";
    } else {
        // Kiểm tra vị trí thuộc công ty + còn quota
        $infoStmt = $pdo->prepare("
            SELECT ip.title, ip.quota,
                   (SELECT COUNT(*) FROM internship_registrations r WHERE r.position_id = ip.ip_id AND r.status IN ('Pending','Approved')) AS current_count
            FROM internship_positions ip
            WHERE ip.ip_id = ? AND ip.company_id = ?
        ");
        $infoStmt->execute([$position_id, $companyId]);
        $info = $infoStmt->fetch();

        // Xử lý CV bắt buộc
        $cvPath = null;
        if (!$info) {
            $type = "error";
            $message = "Vị trí không hợp lệ.";
        } elseif ((int)$info['current_count'] >= (int)$info['quota']) {
            $type = "error";
            $message = "Vị trí này đã đủ quota.";
        } elseif (!isset($_FILES['cv_file']) || $_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
            $type = "error";
            $message = "Vui lòng tải lên CV để ứng tuyển.";
        } else {
            $allowed = ['pdf', 'doc', 'docx'];
            $ext = strtolower(pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $type = "error";
                $message = "CV không hợp lệ. Chỉ nhận PDF, DOC, DOCX.";
            } else {
                if (!is_dir('uploads/cv')) {
                    mkdir('uploads/cv', 0777, true);
                }
                $safeName = 'cv_' . currentUserId() . '_' . $position_id . '_' . time() . '.' . $ext;
                $target = 'uploads/cv/' . $safeName;
                if (!move_uploaded_file($_FILES['cv_file']['tmp_name'], $target)) {
                    $type = "error";
                    $message = "Không thể lưu CV.";
                } else {
                    $cvPath = $target;
                    try {
                        $ins = $pdo->prepare("INSERT INTO internship_registrations (student_id, position_id, cv_file, period_id, status) VALUES (?, ?, ?, ?, 'Pending')");
                        $ins->execute([currentUserId(), $position_id, $cvPath, $period_id]);
                        $message = "Đã gửi yêu cầu ứng tuyển vị trí \"{$info['title']}\" kèm CV. Chờ doanh nghiệp duyệt.";
                    } catch (PDOException $e) {
                        $type = "error";
                        $message = "Bạn đã ứng tuyển vị trí này trong đợt hiện tại rồi.";
                    }
                }
            }
        }
    }
}

// Thêm vị trí (kèm upload JD tùy chọn) — chỉ admin
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_position') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $major = trim($_POST['major'] ?? '');
    $quota = (int)($_POST['quota'] ?? 0);

    if ($title === '') {
        $type = "error";
        $message = "Vui lòng nhập tên vị trí.";
    } else {
        $jdPath = null;

        // Xử lý upload JD nếu có
        if (isset($_FILES['jd_file']) && $_FILES['jd_file']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['pdf', 'doc', 'docx'];
            $ext = strtolower(pathinfo($_FILES['jd_file']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $type = "error";
                $message = "File JD không hợp lệ. Chỉ nhận PDF, DOC, DOCX.";
            } else {
                if (!is_dir('uploads/jd')) {
                    mkdir('uploads/jd', 0777, true);
                }
                $safeName = 'jd_' . $companyId . '_' . time() . '.' . $ext;
                $target = 'uploads/jd/' . $safeName;
                if (move_uploaded_file($_FILES['jd_file']['tmp_name'], $target)) {
                    $jdPath = $target;
                } else {
                    $type = "error";
                    $message = "Không thể lưu file JD.";
                }
            }
        }

        if ($type !== "error") {
            $stmt = $pdo->prepare("INSERT INTO internship_positions (company_id, title, description, jd_file, major, quota) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $companyId,
                $title,
                $description !== '' ? $description : null,
                $jdPath,
                $major !== '' ? $major : null,
                $quota > 0 ? $quota : 3
            ]);
            $message = "Đã thêm vị trí thực tập.";
        }
    }
}

// Xóa vị trí (chặn nếu đã có đăng ký) — chỉ admin
if ($isAdmin && isset($_GET['delete_position'])) {
    $pid = (int)$_GET['delete_position'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM internship_registrations WHERE position_id = ?");
    $check->execute([$pid]);
    if ($check->fetchColumn() > 0) {
        $type = "error";
        $message = "Không thể xóa vì vị trí này đã có sinh viên đăng ký.";
    } else {
        $del = $pdo->prepare("DELETE FROM internship_positions WHERE ip_id = ? AND company_id = ?");
        $del->execute([$pid, $companyId]);
        $message = "Đã xóa vị trí.";
    }
}

// Danh sách vị trí + số đăng ký
$posStmt = $pdo->prepare("
    SELECT ip.*, COUNT(ir.ir_id) AS registration_count
    FROM internship_positions ip
    LEFT JOIN internship_registrations ir ON ip.ip_id = ir.position_id
    WHERE ip.company_id = ?
    GROUP BY ip.ip_id
    ORDER BY ip.created_at DESC
");
$posStmt->execute([$companyId]);
$positions = $posStmt->fetchAll();

include 'includes/header.php';
?>

<style>
    .detail-header { background:linear-gradient(135deg,#2563eb,#7c3aed); color:#fff; border-radius:16px; padding:28px; display:flex; gap:20px; align-items:center; }
    .detail-header .logo { width:80px; height:80px; border-radius:16px; background:rgba(255,255,255,0.18); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:34px; flex-shrink:0; }
    .detail-header h1 { margin:0 0 8px; font-size:28px; }
    .detail-meta { display:flex; gap:18px; flex-wrap:wrap; font-size:14px; color:#e0e7ff; }
    .pos-item { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:18px 20px; margin-bottom:14px; }
    .pos-item h4 { margin:0 0 6px; font-size:17px; color:#1e293b; }
    .pos-badges { display:flex; gap:8px; flex-wrap:wrap; margin:8px 0; }
    .pill { background:#eef2ff; color:#3525cd; font-size:12px; font-weight:600; padding:4px 10px; border-radius:20px; }
    .pill.gray { background:#f1f5f9; color:#475569; }
    .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); overflow-y:auto; }
    .modal.active { display:flex; align-items:flex-start; justify-content:center; padding:40px 16px; }
    .modal-content { background:#fff; border-radius:14px; padding:26px; width:100%; max-width:560px; }
    .modal-header { font-size:19px; font-weight:700; margin-bottom:16px; color:#1e293b; display:flex; justify-content:space-between; }
    .modal-close { font-size:26px; color:#94a3b8; cursor:pointer; line-height:1; }
    .form-group { margin-bottom:14px; }
    .form-group label { display:block; font-weight:500; color:#334155; margin-bottom:6px; font-size:13px; }
    .form-group input, .form-group textarea { width:100%; padding:9px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; font-family:inherit; }
    .modal-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:20px; }
    .btn-cancel { padding:9px 18px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; cursor:pointer; color:#475569; }
    .btn-submit { padding:9px 18px; background:#3525cd; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
</style>

<a class="btn small secondary" href="companies.php">← Quay lại danh sách</a>

<?php if ($message): ?><div class="alert <?= $type ?>" style="margin-top:14px;"><?= e($message) ?></div><?php endif; ?>

<div class="detail-header" style="margin-top:14px;">
    <div class="logo"><?= e(strtoupper(mb_substr($company['name'], 0, 1))) ?></div>
    <div>
        <h1><?= e($company['name']) ?></h1>
        <div class="detail-meta">
            <?php if ($company['address']): ?><span>📍 <?= e($company['address']) ?></span><?php endif; ?>
            <?php if ($company['email']): ?><span>✉️ <?= e($company['email']) ?></span><?php endif; ?>
            <?php if ($company['phone']): ?><span>📞 <?= e($company['phone']) ?></span><?php endif; ?>
            <span>🎯 Quota tổng: <?= e($company['quota']) ?></span>
        </div>
    </div>
</div>

<?php if ($company['description']): ?>
<div class="card" style="margin-top:18px;">
    <h3>Giới thiệu</h3>
    <p style="color:#475569; line-height:1.7; white-space:pre-line;"><?= e($company['description']) ?></p>
</div>
<?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-top:22px;">
    <h3 style="margin:0;">Vị trí đang tuyển (<?= count($positions) ?>)</h3>
    <?php if ($isAdmin): ?>
    <button onclick="openAddPositionModal()" style="background:#3525cd; color:#fff; padding:9px 16px; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
        ➕ Thêm vị trí
    </button>
    <?php endif; ?>
</div>

<div style="margin-top:14px;">
    <?php if (!$positions): ?>
        <p class="help">Chưa có vị trí thực tập nào.</p>
    <?php endif; ?>
    <?php foreach ($positions as $p): ?>
        <div class="pos-item">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                <div style="flex:1;">
                    <h4><?= e($p['title']) ?></h4>
                    <div class="pos-badges">
                        <?php if ($p['major']): ?><span class="pill"><?= e($p['major']) ?></span><?php endif; ?>
                        <span class="pill gray">Chỉ tiêu: <?= e($p['quota']) ?></span>
                        <span class="pill gray">Đã đăng ký: <?= e($p['registration_count']) ?></span>
                    </div>
                    <?php if ($p['description']): ?>
                        <p style="color:#64748b; margin:8px 0 0; font-size:14px; white-space:pre-line;"><?= e($p['description']) ?></p>
                    <?php endif; ?>
                    <?php if ($p['jd_file']): ?>
                        <p style="margin:10px 0 0;"><a class="btn small" href="<?= e($p['jd_file']) ?>" target="_blank">📄 Tải JD</a></p>
                    <?php endif; ?>
                </div>
                <?php if ($isAdmin): ?>
                <a class="btn danger small" onclick="return confirm('Xóa vị trí này?')" href="?id=<?= e($companyId) ?>&delete_position=<?= e($p['ip_id']) ?>">Xóa</a>
                <?php elseif ($isStudent): ?>
                <button type="button" class="btn small" onclick="openApplyModal(<?= e($p['ip_id']) ?>, '<?= e(addslashes($p['title'])) ?>')">Ứng tuyển</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($isAdmin): ?>
<!-- Add Position Modal -->
<div id="addPositionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            Thêm vị trí thực tập
            <span class="modal-close" onclick="closeAddPositionModal()">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_position">

            <div class="form-group">
                <label>Tên vị trí *</label>
                <input type="text" name="title" required placeholder="VD: Business Analyst Intern">
            </div>
            <div class="form-group">
                <label>Mô tả công việc</label>
                <textarea name="description" rows="3" placeholder="Mô tả nhiệm vụ, yêu cầu..."></textarea>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label>Ngành yêu cầu</label>
                    <input type="text" name="major" placeholder="VD: Information Technology">
                </div>
                <div class="form-group">
                    <label>Chỉ tiêu</label>
                    <input type="number" name="quota" min="1" value="3">
                </div>
            </div>
            <div class="form-group">
                <label>File JD (PDF, DOC, DOCX)</label>
                <input type="file" name="jd_file" accept=".pdf,.doc,.docx">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAddPositionModal()">Hủy</button>
                <button type="submit" class="btn-submit">Lưu vị trí</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddPositionModal() { document.getElementById('addPositionModal').classList.add('active'); }
function closeAddPositionModal() { document.getElementById('addPositionModal').classList.remove('active'); }
window.addEventListener('click', function(e) {
    var m = document.getElementById('addPositionModal');
    if (e.target === m) m.classList.remove('active');
});
</script>
<?php endif; ?>

<?php if ($isStudent): ?>
<!-- Apply Modal -->
<div id="applyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span id="applyTitle">Ứng tuyển vị trí</span>
            <span class="modal-close" onclick="closeApplyModal()">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="apply">
            <input type="hidden" name="position_id" id="applyPositionId">
            <div class="form-group">
                <label>CV của bạn (PDF, DOC, DOCX) *</label>
                <input type="file" name="cv_file" accept=".pdf,.doc,.docx" required>
            </div>
            <p class="help">Doanh nghiệp sẽ xem CV và duyệt yêu cầu ứng tuyển của bạn.</p>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeApplyModal()">Hủy</button>
                <button type="submit" class="btn-submit">Gửi ứng tuyển</button>
            </div>
        </form>
    </div>
</div>
<script>
function openApplyModal(posId, title) {
    document.getElementById('applyPositionId').value = posId;
    document.getElementById('applyTitle').textContent = 'Ứng tuyển: ' + title;
    document.getElementById('applyModal').classList.add('active');
}
function closeApplyModal() { document.getElementById('applyModal').classList.remove('active'); }
window.addEventListener('click', function(e) {
    var m = document.getElementById('applyModal');
    if (e.target === m) m.classList.remove('active');
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
