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
