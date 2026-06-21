<?php
require 'config/db.php';
require 'includes/auth.php';

$message = '';
$type = 'error';

// Nếu đã đăng nhập thì vào thẳng dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = 'Vui lòng nhập đầy đủ thông tin.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && passwordMatches($password, $user['password'])) {
            $_SESSION['user'] = [
                'user_id' => (int)$user['user_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'major' => $user['major'] ?? null,
                'company_id' => $user['company_id'] !== null ? (int)$user['company_id'] : null
            ];

            header('Location: dashboard.php');
            exit;
        } else {
            $message = 'Email hoặc mật khẩu không đúng.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập hệ thống</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.22), transparent 30%),
                radial-gradient(circle at bottom right, rgba(124, 58, 237, 0.22), transparent 30%),
                #f4f7fb;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 92%;
            max-width: 480px;
            background: white;
            padding: 36px;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.12);
        }

        h2 {
            margin: 0 0 8px;
            font-size: 30px;
            color: #111827;
        }

        p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 700;
            color: #334155;
            margin-bottom: 7px;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            margin-bottom: 16px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: white;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 18px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }

        .alert {
            padding: 13px 15px;
            border-radius: 14px;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .success {
            background: #ecfdf5;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Đăng nhập hệ thống</h2>
    <p>Vui lòng đăng nhập để truy cập hệ thống Internship Manager.</p>

    <?php if ($message): ?>
        <div class="alert <?= htmlspecialchars($type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <label>Email</label>
        <input type="email" name="email" autocomplete="off" required>

        <label>Mật khẩu</label>
        <input type="password" name="password" autocomplete="new-password" required>

        <button>Đăng nhập</button>
    </form>

    <a href="index.php" class="back">← Quay lại trang chủ</a>
</div>

</body>
</html>