<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin', 'company', 'student']);

$pageTitle = "Vị trí thực tập";
$active = "positions";
$message = "";
$type = "success";

$role = currentRole();
$isAdmin = $role === 'admin';
$isCompany = $role === 'company';
$isStudent = $role === 'student';
$myCompanyId = currentCompanyId();

// Chỉ admin và company được thêm vị trí
if (($isAdmin || $isCompany) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Company chỉ được thêm vị trí cho chính công ty mình
    $company_id = $isCompany ? (int)$myCompanyId : (int)$_POST['company_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $major = trim($_POST['major'] ?? '');
    $quota = (int)$_POST['quota'];

    if ($isCompany && !$myCompanyId) {
        $type = "error";
        $message = "Tài khoản doanh nghiệp chưa được liên kết với công ty nào.";
    } else {
        $jdPath = null;
        $okUpload = true;

        if (isset($_FILES['jd_file']) && $_FILES['jd_file']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['pdf', 'doc', 'docx'];
            $ext = strtolower(pathinfo($_FILES['jd_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $type = "error";
                $message = "File JD không hợp lệ. Chỉ nhận PDF, DOC, DOCX.";
                $okUpload = false;
            } else {
                if (!is_dir('uploads/jd')) {
                    mkdir('uploads/jd', 0777, true);
                }
                $safeName = 'jd_' . $company_id . '_' . time() . '.' . $ext;
                $target = 'uploads/jd/' . $safeName;
                if (move_uploaded_file($_FILES['jd_file']['tmp_name'], $target)) {
                    $jdPath = $target;
                } else {
                    $type = "error";
                    $message = "Không thể lưu file JD.";
                    $okUpload = false;
                }
            }
        }

        if ($okUpload) {
            $stmt = $pdo->prepare("INSERT INTO internship_positions (company_id, title, description, jd_file, major, quota) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$company_id, $title, $description, $jdPath, $major !== '' ? $major : null, $quota]);
            $message = "Thêm vị trí thực tập thành công.";
        }
    }
}

// Xóa: admin xóa bất kỳ; company chỉ xóa vị trí của mình
if (($isAdmin || $isCompany) && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $owner = $pdo->prepare("SELECT company_id FROM internship_positions WHERE ip_id = ?");
    $owner->execute([$id]);
    $ownerCompanyId = $owner->fetchColumn();

    if ($isCompany && (int)$ownerCompanyId !== (int)$myCompanyId) {
        $type = "error";
        $message = "Bạn không có quyền xóa vị trí này.";
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM internship_registrations WHERE position_id = ?");
        $check->execute([$id]);

        if ($check->fetchColumn() > 0) {
            $type = "error";
            $message = "Không thể xóa vì vị trí này đã có sinh viên đăng ký.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM internship_positions WHERE ip_id = ?");
            $stmt->execute([$id]);
            $message = "Đã xóa vị trí thực tập.";
        }
    }
}

$companies = $pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll();

// Company chỉ thấy vị trí của mình; admin và student thấy tất cả
if ($isCompany) {
    $posStmt = $pdo->prepare("
        SELECT ip.*, c.name AS company_name,
               COUNT(ir.ir_id) AS registration_count
        FROM internship_positions ip
        JOIN companies c ON ip.company_id = c.company_id
        LEFT JOIN internship_registrations ir ON ip.ip_id = ir.position_id
        WHERE ip.company_id = ?
        GROUP BY ip.ip_id
        ORDER BY ip.created_at DESC
    ");
    $posStmt->execute([(int)$myCompanyId]);
    $positions = $posStmt->fetchAll();
} else {
    $positions = $pdo->query("
        SELECT ip.*, c.name AS company_name,
               COUNT(ir.ir_id) AS registration_count
        FROM internship_positions ip
        JOIN companies c ON ip.company_id = c.company_id
        LEFT JOIN internship_registrations ir ON ip.ip_id = ir.position_id
        GROUP BY ip.ip_id
        ORDER BY ip.created_at DESC
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
    .form-group { margin-bottom:14px; }
    .form-group label { display:block; font-weight:500; color:#334155; margin-bottom:6px; font-size:13px; }
    .form-group input, .form-group select, .form-group textarea { width:100%; padding:9px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; font-family:inherit; }
    .modal-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:22px; }
    .btn-cancel { padding:9px 18px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; cursor:pointer; font-weight:500; color:#475569; }
    .btn-submit { padding:9px 18px; background:#3525cd; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
</style>

<?php if ($message): ?><div class="alert <?= $type ?>"><?= e($message) ?></div><?php endif; ?>

<?php if ($isAdmin || $isCompany): ?>
<div style="display:flex; justify-content:flex-end; align-items:center; gap:16px; flex-wrap:wrap;">
    <button onclick="openAddPositionModal()" style="background:#3525cd; color:#fff; padding:10px 18px; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
        ➕ Thêm vị trí thực tập
    </button>
</div>

<!-- Add Position Modal -->
<div id="addPositionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            Thêm vị trí thực tập
            <span class="modal-close" onclick="closeAddPositionModal()">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($isAdmin): ?>
            <div class="form-group">
                <label>Doanh nghiệp *</label>
                <select name="company_id" required>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= e($c['company_id']) ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Tên vị trí *</label>
                <input name="title" placeholder="VD: Business Analyst Intern" required>
            </div>
            <div class="form-group">
                <label>Ngành yêu cầu (để trống = nhận mọi ngành)</label>
                <input name="major" placeholder="VD: Information Technology">
            </div>
            <div class="form-group">
                <label>Mô tả công việc</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Quota vị trí *</label>
                <input type="number" name="quota" min="1" value="3" required>
            </div>
            <div class="form-group">
                <label>File JD (PDF, DOC, DOCX) — tùy chọn</label>
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

<div class="card">
    <h3>Danh sách vị trí</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Vị trí</th><th>Doanh nghiệp</th><th>Ngành</th><th>JD</th><th>Quota</th><th>Đăng ký</th><?php if ($isAdmin || $isCompany): ?><th>Hành động</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($positions as $p): ?>
                <tr>
                    <td>#<?= e($p['ip_id']) ?></td>
                    <td><b><?= e($p['title']) ?></b></td>
                    <td><?= e($p['company_name']) ?></td>
                    <td><?= $p['major'] ? e($p['major']) : '<span class="help">Mọi ngành</span>' ?></td>
                    <td><?= $p['jd_file'] ? '<a class="btn small" href="' . e($p['jd_file']) . '" target="_blank">📄 Tải JD</a>' : '<span class="help">—</span>' ?></td>
                    <td><?= e($p['quota']) ?></td>
                    <td><?= e($p['registration_count']) ?></td>
                    <?php if ($isAdmin || $isCompany): ?>
                    <td><a class="btn danger small" onclick="return confirm('Xóa vị trí này?')" href="?delete=<?= e($p['ip_id']) ?>">Xóa</a></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
