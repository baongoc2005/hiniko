<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin']);

$pageTitle = "Account Management";
$active = "users";
$message = "";
$type = "success";

// Handle add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'student';
    $password = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);

    if (empty($name) || empty($email)) {
        $type = "error";
        $message = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role]);
            $message = "Thêm tài khoản thành công.";
        } catch (PDOException $e) {
            $type = "error";
            $message = "Không thể thêm tài khoản. Email có thể đã tồn tại.";
        }
    }
}

// Handle edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'student';

    if ($user_id <= 0 || empty($name) || empty($email)) {
        $type = "error";
        $message = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $role, $user_id]);
            $message = "Cập nhật tài khoản thành công.";
        } catch (PDOException $e) {
            $type = "error";
            $message = "Không thể cập nhật tài khoản. Email có thể đã tồn tại.";
        }
    }
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id <= 0) {
        $type = "error";
        $message = "Không tìm thấy tài khoản.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $message = "Xóa tài khoản thành công.";
        } catch (PDOException $e) {
            $type = "error";
            $message = "Không thể xóa tài khoản. Tài khoản này có thể đang được sử dụng.";
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
include 'includes/header.php';

// Role mapping
$roleMap = [
    'student' => 'SINH VIÊN',
    'lecturer' => 'GIẢNG VIÊN',
    'company' => 'QUẢN LÝ DOANH NGHIỆP',
    'admin' => 'QUẢN TRỊ VIÊN'
];

$roleColors = [
    'student' => 'bg-blue-100 text-blue-700',
    'lecturer' => 'bg-slate-200 text-slate-700',
    'company' => 'bg-emerald-100 text-emerald-700',
    'admin' => 'bg-rose-100 text-rose-700'
];

$initialsColors = [
    'student' => 'bg-indigo-100 text-indigo-700',
    'lecturer' => 'bg-amber-100 text-amber-700',
    'company' => 'bg-emerald-100 text-emerald-700',
    'admin' => 'bg-rose-100 text-rose-700'
];

function getInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper($part[0]);
        }
    }
    return substr($initials, 0, 2);
}
?>

<style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }
    .modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background-color: white;
        border-radius: 12px;
        padding: 24px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }
    .modal-header {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #1e293b;
    }
    .modal-close {
        float: right;
        font-size: 28px;
        font-weight: bold;
        color: #94a3b8;
        cursor: pointer;
    }
    .modal-close:hover {
        color: #475569;
    }
    .form-group {
        margin-bottom: 16px;
    }
    .form-group label {
        display: block;
        font-weight: 500;
        color: #334155;
        margin-bottom: 6px;
        font-size: 14px;
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        font-family: Inter, sans-serif;
    }
    .form-group input:focus, .form-group select:focus {
        outline: none;
        border-color: #3525cd;
        box-shadow: 0 0 0 3px rgba(53, 37, 205, 0.1);
    }
    .modal-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 24px;
    }
    .btn-cancel {
        padding: 8px 16px;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        color: #475569;
    }
    .btn-cancel:hover {
        background: #f1f5f9;
    }
    .btn-submit {
        padding: 8px 16px;
        background: #3525cd;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
    }
    .btn-submit:hover {
        background: #2b1da5;
    }
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
    }
    .table tbody tr:hover {
        background-color: #f8fafc;
    }
    .text-primary {
        color: #3525cd;
        font-weight: 600;
    }
    .status-active {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #10b981;
    }
</style>

<?php if ($message): ?>
    <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 8px; background: <?= $type === 'error' ? '#fee2e2' : '#dcfce7' ?>; color: <?= $type === 'error' ? '#991b1b' : '#166534' ?>;">
        <?= e($message) ?>
    </div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h1 style="font-size: 28px; font-weight: 700; color: #1e293b; margin: 0;">Account Management</h1>
    <button onclick="openAddUserModal()" style="background: #3525cd; color: white; padding: 10px 16px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <span>➕</span> Thêm tài khoản
    </button>
</div>

<div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden;">
    
    <!-- Filters -->
    <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; gap: 16px; align-items: center;">
        <div style="flex: 1; max-width: 300px; position: relative;">
            <input type="text" id="searchInput" placeholder="Tìm kiếm theo tên hoặc email..." style="width: 100%; padding: 8px 12px 8px 32px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px;">
            <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #94a3b8;">🔍</span>
        </div>
        
        <div style="display: flex; align-items: center; gap: 8px;">
            <label style="font-weight: 500; font-size: 13px; color: #475569;">Vai trò:</label>
            <select id="roleFilter" style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 8px; font-size: 13px;">
                <option value="">Tất cả</option>
                <option value="student">Sinh viên</option>
                <option value="lecturer">Giảng viên</option>
                <option value="company">Doanh nghiệp</option>
                <option value="admin">Admin</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="border-bottom: 1px solid #e2e8f0; background: white;">
                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; width: 60px;">ID</th>
                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Họ tên</th>
                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Email</th>
                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Vai trò</th>
                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Trạng thái</th>
                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Ngày tạo</th>
                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Thao tác</th>
                </tr>
            </thead>
            <tbody id="usersTable">
                <?php foreach ($users as $u): ?>
                    <tr data-role="<?= e($u['role']) ?>" data-name="<?= strtolower(e($u['name'])) ?>" data-email="<?= strtolower(e($u['email'])) ?>" style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 16px; color: #64748b; font-weight: 500;">#<?= e($u['user_id']) ?></td>
                        <td style="padding: 12px 16px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="user-avatar <?= $initialsColors[$u['role']] ?? 'bg-slate-100 text-slate-700' ?>">
                                    <?= getInitials($u['name']) ?>
                                </div>
                                <span class="text-primary"><?= e($u['name']) ?></span>
                            </div>
                        </td>
                        <td style="padding: 12px 16px; color: #64748b;"><?= e($u['email']) ?></td>
                        <td style="padding: 12px 16px;">
                            <span class="badge <?= $roleColors[$u['role']] ?? 'bg-slate-100 text-slate-700' ?>">
                                <?= e($roleMap[$u['role']] ?? $u['role']) ?>
                            </span>
                        </td>
                        <td style="padding: 12px 16px;">
                            <div class="status-active">
                                <div class="status-dot"></div>
                                <span style="color: #059669; font-weight: 500;">Hoạt động</span>
                            </div>
                        </td>
                        <td style="padding: 12px 16px; color: #64748b;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td style="padding: 12px 16px;">
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <button onclick="openEditUserModal(<?= $u['user_id'] ?>, '<?= addslashes(e($u['name'])) ?>', '<?= addslashes(e($u['email'])) ?>', '<?= $u['role'] ?>')" style="background: #3b82f6; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">✎ Sửa</button>
                                <button onclick="openDeleteModal(<?= $u['user_id'] ?>, '<?= addslashes(e($u['name'])) ?>')" style="background: #ef4444; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">🗑 Xóa</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="padding: 12px 16px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
        <p style="color: #64748b; margin: 0;">
            Hiển thị <strong><?= count($users) ?></strong> tài khoản
        </p>
        <div style="display: flex; gap: 4px;">
            <button style="min-width: 32px; height: 32px; border: 1px solid #e2e8f0; border-radius: 6px; background: white; cursor: pointer; color: #64748b;">←</button>
            <button style="min-width: 32px; height: 32px; border-radius: 6px; background: #3525cd; color: white; cursor: pointer; font-weight: 500;">1</button>
            <button style="min-width: 32px; height: 32px; border: 1px solid #e2e8f0; border-radius: 6px; background: white; cursor: pointer; color: #64748b;">→</button>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeAddUserModal()">&times;</span>
        <div class="modal-header">Thêm tài khoản mới</div>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            
            <div class="form-group">
                <label>Họ tên *</label>
                <input type="text" name="name" required placeholder="Nhập họ tên">
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="Nhập email">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" value="123456" placeholder="Mật khẩu mặc định">
                </div>

                <div class="form-group">
                    <label>Vai trò *</label>
                    <select name="role" required>
                        <option value="student">Sinh viên</option>
                        <option value="lecturer">Giảng viên</option>
                        <option value="company">Doanh nghiệp</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAddUserModal()">Hủy</button>
                <button type="submit" class="btn-submit">Thêm tài khoản</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeEditUserModal()">&times;</span>
        <div class="modal-header">Sửa tài khoản</div>
        
        <form method="POST">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" id="editUserId" name="user_id" value="">
            
            <div class="form-group">
                <label>Họ tên *</label>
                <input type="text" id="editUserName" name="name" required placeholder="Nhập họ tên">
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" id="editUserEmail" name="email" required placeholder="Nhập email">
            </div>

            <div class="form-group">
                <label>Vai trò *</label>
                <select id="editUserRole" name="role" required>
                    <option value="student">Sinh viên</option>
                    <option value="lecturer">Giảng viên</option>
                    <option value="company">Doanh nghiệp</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditUserModal()">Hủy</button>
                <button type="submit" class="btn-submit">Cập nhật tài khoản</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete User Modal -->
<div id="deleteUserModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <span class="modal-close" onclick="closeDeleteModal()">&times;</span>
        <div class="modal-header">Xóa tài khoản</div>
        
        <p style="color: #64748b; margin: 16px 0; line-height: 1.6;">
            Bạn có chắc chắn muốn xóa tài khoản <strong id="deleteUserNameDisplay" style="color: #1e293b;"></strong>? Hành động này không thể hoàn tác.
        </p>

        <form method="POST" id="deleteForm">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" id="deleteUserId" name="user_id" value="">

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Hủy</button>
                <button type="submit" class="btn-submit" style="background: #ef4444;">Xóa tài khoản</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('addUserModal').classList.add('active');
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.remove('active');
}

function openEditUserModal(userId, name, email, role) {
    document.getElementById('editUserId').value = userId;
    document.getElementById('editUserName').value = name;
    document.getElementById('editUserEmail').value = email;
    document.getElementById('editUserRole').value = role;
    document.getElementById('editUserModal').classList.add('active');
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.remove('active');
}

function openDeleteModal(userId, name) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUserNameDisplay').textContent = name;
    document.getElementById('deleteUserModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteUserModal').classList.remove('active');
}

// Search and filter functionality
document.getElementById('searchInput')?.addEventListener('keyup', filterTable);
document.getElementById('roleFilter')?.addEventListener('change', filterTable);

function filterTable() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const roleFilter = document.getElementById('roleFilter')?.value || '';
    const rows = document.querySelectorAll('#usersTable tr');

    rows.forEach(row => {
        const name = row.dataset.name || '';
        const email = row.dataset.email || '';
        const role = row.dataset.role || '';

        const matchSearch = name.includes(searchTerm) || email.includes(searchTerm);
        const matchRole = !roleFilter || role === roleFilter;

        row.style.display = matchSearch && matchRole ? '' : 'none';
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addUserModal');
    const editModal = document.getElementById('editUserModal');
    const deleteModal = document.getElementById('deleteUserModal');
    
    if (event.target == addModal) {
        addModal.classList.remove('active');
    }
    if (event.target == editModal) {
        editModal.classList.remove('active');
    }
    if (event.target == deleteModal) {
        deleteModal.classList.remove('active');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
