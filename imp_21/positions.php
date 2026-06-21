<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin', 'company', 'student']);

$pageTitle = "Internship Positions";
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

<?php if ($message): ?><div class="alert <?= $type ?>"><?= e($message) ?></div><?php endif; ?>

<?php if ($isAdmin || $isCompany): ?>
<div class="card">
    <h3>Thêm vị trí thực tập</h3>
    <form class="form" method="POST" enctype="multipart/form-data">
        <?php if ($isAdmin): ?>
        <div>
            <label>Doanh nghiệp</label>
            <select name="company_id" required>
                <?php foreach ($companies as $c): ?>
                    <option value="<?= e($c['company_id']) ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div>
            <label>Tên vị trí</label>
            <input name="title" placeholder="VD: Business Analyst Intern" required>
        </div>
        <div>
            <label>Ngành yêu cầu (để trống = nhận mọi ngành)</label>
            <input name="major" placeholder="VD: Information Technology">
        </div>
        <div>
            <label>Mô tả công việc</label>
            <textarea name="description"></textarea>
        </div>
        <div>
            <label>Quota vị trí</label>
            <input type="number" name="quota" min="1" value="3" required>
        </div>
        <div>
            <label>File JD (PDF, DOC, DOCX) — tùy chọn</label>
            <input type="file" name="jd_file" accept=".pdf,.doc,.docx">
        </div>
        <button>Lưu vị trí</button>
    </form>
</div>
<?php endif; ?>

<div class="card" style="margin-top: 22px;">
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
