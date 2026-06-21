<?php
if (!isset($pageTitle)) {
    $pageTitle = "Internship Manager";
}

if (!isset($active)) {
    $active = "";
}

$role = $_SESSION['user']['role'] ?? '';

// Quyền xem từng mục menu theo role
$navAccess = [
    'users'         => ['admin'],
    'students'      => ['admin'],
    'lecturers'     => ['admin'],
    'companies'     => ['admin', 'student', 'lecturer'],
    'positions'     => ['company'],
    'periods'       => ['admin'],
    'registrations' => ['admin', 'student', 'company'],
    'assignments'   => ['admin', 'lecturer'],
    'journals'      => ['admin', 'student', 'lecturer'],
    'evaluations'   => ['admin', 'lecturer', 'company'],
    'final_grades'  => ['admin', 'student', 'lecturer', 'company'],
    'compliance'    => ['admin', 'lecturer'],
];

$canSee = static function (string $key) use ($navAccess, $role): bool {
    return isset($navAccess[$key]) && in_array($role, $navAccess[$key], true);
};

$groupVisible = static function (array $keys) use ($canSee): bool {
    foreach ($keys as $k) {
        if ($canSee($k)) {
            return true;
        }
    }
    return false;
};

if (in_array($active, ['users', 'students', 'lecturers', 'companies', 'positions', 'periods'], true)) {
    $memberGroup = 1;
} elseif (in_array($active, ['registrations', 'assignments', 'journals'], true)) {
    $memberGroup = 2;
} elseif (in_array($active, ['evaluations', 'final_grades', 'compliance'], true)) {
    $memberGroup = 3;
} else {
    $memberGroup = 0;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= e($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">IM</div>
            <div>
                <h1>Internship</h1>
                <span>Hệ thống Quản lý Thực tập</span>
            </div>
        </div>

        <nav class="member-nav">
            <a class="nav-main <?= $active === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                Trang chủ
            </a>

            <a class="nav-main <?= $active === 'profile' ? 'active' : '' ?>" href="profile.php">
                Hồ sơ cá nhân
            </a>

            <?php if ($groupVisible(['users', 'students', 'lecturers', 'companies', 'positions', 'periods'])): ?>
            <details class="member-group" <?= $memberGroup === 1 ? 'open' : '' ?>>
                <summary>Dữ liệu nền</summary>

                <div class="member-links">
                    <?php if ($canSee('users')): ?>
                    <a class="<?= $active === 'users' ? 'active' : '' ?>" href="users.php">
                        Quản lý tài khoản
                    </a>
                    <?php endif; ?>

                    <?php if ($canSee('students')): ?>
                    <a class="<?= $active === 'students' ? 'active' : '' ?>" href="students.php">
                        Sinh viên
                    </a>
                    <?php endif; ?>

                    <?php if ($canSee('lecturers')): ?>
                    <a class="<?= $active === 'lecturers' ? 'active' : '' ?>" href="lecturers.php">
                        Giảng viên
                    </a>
                    <?php endif; ?>

                    <?php if ($canSee('companies')): ?>
                    <a class="<?= $active === 'companies' ? 'active' : '' ?>" href="companies.php">
                        Doanh nghiệp
                    </a>
                    <?php endif; ?>

                    <?php if ($canSee('positions')): ?>
                    <a class="<?= $active === 'positions' ? 'active' : '' ?>" href="positions.php">
                        Vị trí thực tập
                    </a>
                    <?php endif; ?>

                    <?php if ($canSee('periods')): ?>
                    <a class="<?= $active === 'periods' ? 'active' : '' ?>" href="periods.php">
                        Đợt thực tập
                    </a>
                    <?php endif; ?>
                </div>
            </details>
            <?php endif; ?>

            <?php if ($groupVisible(['registrations', 'assignments', 'journals'])): ?>
            <details class="member-group" <?= $memberGroup === 2 ? 'open' : '' ?>>
                <summary>Quy trình thực tập</summary>

                <div class="member-links">
                    <?php if ($canSee('registrations')): ?>
                    <a class="<?= $active === 'registrations' ? 'active' : '' ?>" href="registrations.php">
                        Đăng ký thực tập
                    </a>
                    <?php endif; ?>

                    <?php if ($canSee('assignments')): ?>
                    <a class="<?= $active === 'assignments' ? 'active' : '' ?>" href="assignments.php">
                        Phân công GVHD
                    </a>
                    <?php endif; ?>

                    <?php if ($canSee('journals')): ?>
                    <a class="<?= $active === 'journals' ? 'active' : '' ?>" href="journals.php">
                        Nhật ký hàng tuần
                    </a>
                    <?php endif; ?>
                </div>
            </details>
            <?php endif; ?>

            <?php if ($groupVisible(['evaluations', 'final_grades', 'compliance'])): ?>
            <details class="member-group" <?= $memberGroup === 3 ? 'open' : '' ?>>
                <summary>Đánh giá & Báo cáo</summary>

                <div class="member-links">
                    <?php if ($canSee('evaluations')): ?>
                    <a class="<?= $active === 'evaluations' ? 'active' : '' ?>" href="evaluations.php">
                        Đánh giá
                    </a>
                    <?php endif; ?>

                    <?php if ($canSee('final_grades')): ?>
                    <a class="<?= $active === 'final_grades' ? 'active' : '' ?>" href="final_grades.php">
                        Điểm tổng kết
                    </a>
                    <?php endif; ?>

                    <?php if ($canSee('compliance')): ?>
                    <a class="<?= $active === 'compliance' ? 'active' : '' ?>" href="compliance.php">
                        Tuân thủ
                    </a>
                    <?php endif; ?>
                </div>
            </details>
            <?php endif; ?>
        </nav>

        <div class="sidebar-note">
            <b>INS3064</b><br>
            Multimedia Design and Web Development
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <p class="eyebrow">ISchool Internship Management</p>
                <h2><?= e($pageTitle) ?></h2>
            </div>

            <div class="topbar-actions">
                <?php if (isset($_SESSION['user']) && is_array($_SESSION['user'])): ?>
                    <a class="user-chip" href="profile.php" style="text-decoration:none; color:inherit;" title="Hồ sơ cá nhân">
                        <?= e($_SESSION['user']['name'] ?? 'User') ?>
                        <span class="user-role">
                            (<?= e($_SESSION['user']['role'] ?? '') ?>)
                        </span>
                    </a>

                    <a class="btn small secondary" href="logout.php">
                        Đăng xuất
                    </a>
                <?php endif; ?>
            </div>
        </header>