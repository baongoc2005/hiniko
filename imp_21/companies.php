<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin', 'student', 'lecturer']);

$pageTitle = "Companies Management";
$active = "companies";
$message = "";
$type = "success";

$isAdmin = currentRole() === 'admin';

// Thêm doanh nghiệp + nhiều vị trí cùng lúc (chỉ admin)
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_company') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $quota = (int)($_POST['quota'] ?? 0);

    // Mảng vị trí (song song theo index)
    $posTitles = $_POST['pos_title'] ?? [];
    $posDescs = $_POST['pos_desc'] ?? [];
    $posMajors = $_POST['pos_major'] ?? [];
    $posQuotas = $_POST['pos_quota'] ?? [];

    if ($name === '') {
        $type = "error";
        $message = "Vui lòng nhập tên doanh nghiệp.";
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE name = ?");
        $check->execute([$name]);

        if ($check->fetchColumn() > 0) {
            $type = "error";
            $message = "Tên doanh nghiệp đã tồn tại.";
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO companies (name, description, email, phone, address, quota) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $name,
                    $description !== '' ? $description : null,
                    $email !== '' ? $email : null,
                    $phone !== '' ? $phone : null,
                    $address !== '' ? $address : null,
                    $quota > 0 ? $quota : 5
                ]);
                $companyId = (int)$pdo->lastInsertId();

                $posStmt = $pdo->prepare("INSERT INTO internship_positions (company_id, title, description, major, quota) VALUES (?, ?, ?, ?, ?)");
                foreach ($posTitles as $i => $pt) {
                    $pt = trim($pt);
                    if ($pt === '') {
                        continue; // bỏ qua dòng trống
                    }
                    $posStmt->execute([
                        $companyId,
                        $pt,
                        trim($posDescs[$i] ?? '') ?: null,
                        trim($posMajors[$i] ?? '') ?: null,
                        (int)($posQuotas[$i] ?? 0) > 0 ? (int)$posQuotas[$i] : 3
                    ]);
                }

                $pdo->commit();
                $message = "Thêm doanh nghiệp thành công.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $type = "error";
                $message = "Có lỗi khi lưu doanh nghiệp.";
            }
        }
    }
}

if ($isAdmin && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $check = $pdo->prepare("SELECT COUNT(*) FROM internship_positions WHERE company_id = ?");
    $check->execute([$id]);

    if ($check->fetchColumn() > 0) {
        $type = "error";
        $message = "Không thể xóa vì doanh nghiệp đang có vị trí thực tập.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM companies WHERE company_id = ?");
        $stmt->execute([$id]);
        $message = "Đã xóa doanh nghiệp.";
    }
}

$companies = $pdo->query("
    SELECT c.*,
           COUNT(DISTINCT ip.ip_id) AS position_count
    FROM companies c
    LEFT JOIN internship_positions ip ON c.company_id = ip.company_id
    GROUP BY c.company_id
    ORDER BY c.created_at DESC
")->fetchAll();

include 'includes/header.php';
?>

<style>
    .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); overflow-y:auto; }
    .modal.active { display:flex; align-items:flex-start; justify-content:center; padding:40px 16px; }
    .modal-content { background:#fff; border-radius:14px; padding:26px; width:100%; max-width:680px; box-shadow:0 4px 24px rgba(0,0,0,0.18); }
    .modal-header { font-size:20px; font-weight:700; margin-bottom:18px; color:#1e293b; display:flex; justify-content:space-between; align-items:center; }
    .modal-close { font-size:26px; font-weight:bold; color:#94a3b8; cursor:pointer; line-height:1; }
    .modal-close:hover { color:#475569; }
    .section-title { font-weight:700; color:#3525cd; border-left:3px solid #3525cd; padding-left:10px; margin:18px 0 12px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-group { margin-bottom:14px; }
    .form-group.full { grid-column:1 / -1; }
    .form-group label { display:block; font-weight:500; color:#334155; margin-bottom:6px; font-size:13px; }
    .form-group input, .form-group textarea { width:100%; padding:9px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; font-family:inherit; }
    .pos-row { display:grid; grid-template-columns:2fr 2fr 1fr auto; gap:8px; align-items:center; margin-bottom:8px; }
    .pos-row input { padding:8px 10px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; width:100%; }
    .pos-remove { background:#fee2e2; color:#dc2626; border:none; border-radius:6px; padding:8px 10px; cursor:pointer; font-weight:700; }
    .add-pos-btn { background:none; border:none; color:#3525cd; font-weight:600; cursor:pointer; font-size:14px; }
    .modal-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:22px; }
    .btn-cancel { padding:9px 18px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; cursor:pointer; font-weight:500; color:#475569; }
    .btn-submit { padding:9px 18px; background:#3525cd; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; }

    .company-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:18px; margin-top:22px; }
    .company-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:20px; transition:box-shadow .2s, transform .2s; display:flex; flex-direction:column; gap:12px; }
    .company-card:hover { box-shadow:0 8px 24px rgba(15,23,42,0.10); transform:translateY(-2px); }
    .company-card .logo { width:54px; height:54px; border-radius:12px; background:linear-gradient(135deg,#2563eb,#7c3aed); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:20px; }
    .company-card h3 { margin:0; font-size:17px; color:#1e293b; }
    .company-card .meta { color:#64748b; font-size:13px; display:flex; flex-direction:column; gap:4px; }
    .company-card .tags { display:flex; gap:8px; flex-wrap:wrap; }
    .pill { background:#eef2ff; color:#3525cd; font-size:12px; font-weight:600; padding:4px 10px; border-radius:20px; }
    .card-actions { display:flex; gap:8px; margin-top:auto; padding-top:8px; }
</style>

<?php if ($message): ?><div class="alert <?= $type ?>"><?= e($message) ?></div><?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
    <div>
        <h1 style="font-size:26px; font-weight:700; color:#1e293b; margin:0;">Doanh nghiệp đối tác</h1>
        <p class="help" style="margin:4px 0 0;">Tổng cộng <?= count($companies) ?> doanh nghiệp.</p>
    </div>
    <?php if ($isAdmin): ?>
    <button onclick="openAddCompanyModal()" style="background:#3525cd; color:#fff; padding:10px 18px; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
        ➕ Thêm doanh nghiệp
    </button>
    <?php endif; ?>
</div>

<div class="company-grid">
    <?php if (!$companies): ?>
        <p class="help">Chưa có doanh nghiệp nào.</p>
    <?php endif; ?>
    <?php foreach ($companies as $c): ?>
        <div class="company-card">
            <div style="display:flex; gap:12px; align-items:center;">
                <div class="logo"><?= e(strtoupper(mb_substr($c['name'], 0, 1))) ?></div>
                <div>
                    <h3><?= e($c['name']) ?></h3>
                    <div class="tags" style="margin-top:6px;">
                        <span class="pill"><?= e($c['position_count']) ?> vị trí</span>
                        <span class="pill">Quota <?= e($c['quota']) ?></span>
                    </div>
                </div>
            </div>
            <div class="meta">
                <?php if ($c['address']): ?><span>📍 <?= e($c['address']) ?></span><?php endif; ?>
                <?php if ($c['email']): ?><span>✉️ <?= e($c['email']) ?></span><?php endif; ?>
                <?php if ($c['phone']): ?><span>📞 <?= e($c['phone']) ?></span><?php endif; ?>
            </div>
            <div class="card-actions">
                <a class="btn small" href="company_detail.php?id=<?= e($c['company_id']) ?>">Xem chi tiết</a>
                <?php if ($isAdmin): ?>
                <a class="btn danger small" onclick="return confirm('Xóa doanh nghiệp này?')" href="?delete=<?= e($c['company_id']) ?>">Xóa</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($isAdmin): ?>
<!-- Add Company Modal -->
<div id="addCompanyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            Thêm doanh nghiệp mới
            <span class="modal-close" onclick="closeAddCompanyModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_company">

            <div class="section-title">Thông tin doanh nghiệp</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Tên doanh nghiệp *</label>
                    <input type="text" name="name" required placeholder="Nhập tên chính thức">
                </div>
                <div class="form-group">
                    <label>Tổng chỉ tiêu thực tập</label>
                    <input type="number" name="quota" min="1" value="5" placeholder="Ví dụ: 50">
                </div>
                <div class="form-group full">
                    <label>Mô tả ngắn gọn</label>
                    <textarea name="description" rows="3" placeholder="Giới thiệu về doanh nghiệp và lĩnh vực hoạt động..."></textarea>
                </div>
                <div class="form-group">
                    <label>Email liên hệ</label>
                    <input type="email" name="email" placeholder="contact@company.com">
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" placeholder="028 XXXX XXXX">
                </div>
                <div class="form-group full">
                    <label>Địa chỉ trụ sở</label>
                    <input type="text" name="address" placeholder="Số, Tên đường, Phường/Xã, Quận/Huyện, Tỉnh/Thành phố">
                </div>
            </div>

            <div class="section-title" style="display:flex; justify-content:space-between; align-items:center;">
                <span>Vị trí thực tập</span>
                <button type="button" class="add-pos-btn" onclick="addPositionRow()">＋ Thêm vị trí</button>
            </div>
            <div id="positionRows"></div>
            <p class="help" style="margin-top:6px;">Có thể để trống. File JD đính kèm cho từng vị trí được thêm ở trang chi tiết doanh nghiệp.</p>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAddCompanyModal()">Hủy</button>
                <button type="submit" class="btn-submit">Lưu doanh nghiệp</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCompanyModal() {
    document.getElementById('addCompanyModal').classList.add('active');
    if (document.querySelectorAll('#positionRows .pos-row').length === 0) addPositionRow();
}
function closeAddCompanyModal() { document.getElementById('addCompanyModal').classList.remove('active'); }

function addPositionRow() {
    var wrap = document.getElementById('positionRows');
    var row = document.createElement('div');
    row.className = 'pos-row';
    row.innerHTML =
        '<input type="text" name="pos_title[]" placeholder="Tên vị trí (VD: UI/UX Designer)">' +
        '<input type="text" name="pos_desc[]" placeholder="Mô tả công việc">' +
        '<input type="number" name="pos_quota[]" min="1" value="3" placeholder="Chỉ tiêu">' +
        '<button type="button" class="pos-remove" onclick="this.parentNode.remove()">🗑</button>' +
        '<input type="hidden" name="pos_major[]" value="">';
    wrap.appendChild(row);
}

window.addEventListener('click', function(e) {
    var m = document.getElementById('addCompanyModal');
    if (e.target === m) m.classList.remove('active');
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
