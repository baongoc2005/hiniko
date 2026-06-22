<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user']);
}

function requireLogin(array $roles = []): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    if ($roles !== [] && !in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        echo 'Access denied';
        exit;
    }
}

function passwordMatches(string $inputPassword, string $storedHash): bool
{
    if ($storedHash === '') {
        return false;
    }

    return password_verify($inputPassword, $storedHash);
}

function currentUser(): array
{
    return $_SESSION['user'] ?? [];
}

function currentRole(): string
{
    return $_SESSION['user']['role'] ?? '';
}

function currentUserId(): int
{
    return (int)($_SESSION['user']['user_id'] ?? 0);
}

function currentCompanyId(): ?int
{
    $id = $_SESSION['user']['company_id'] ?? null;
    return $id !== null ? (int)$id : null;
}

function hasRole(string ...$roles): bool
{
    return in_array(currentRole(), $roles, true);
}

// Thông tin sinh viên (student_code, major) của user đang đăng nhập
function currentStudentInfo(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT student_code, major FROM students WHERE user_id = ?");
    $stmt->execute([currentUserId()]);
    return $stmt->fetch() ?: ['student_code' => null, 'major' => null];
}

// Thông tin giảng viên (instructor_code, department) của user đang đăng nhập
function currentInstructorInfo(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT instructor_code, department FROM instructors WHERE user_id = ?");
    $stmt->execute([currentUserId()]);
    return $stmt->fetch() ?: ['instructor_code' => null, 'department' => null];
}
