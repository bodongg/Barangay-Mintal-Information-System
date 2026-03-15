<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function ensureSessionStarted(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function getCurrentAdmin(): ?array
{
    static $admin = false;

    if (is_array($admin)) {
        return $admin;
    }

    ensureSessionStarted();

    $adminUserId = (int) ($_SESSION['admin_user_id'] ?? 0);
    if ($adminUserId <= 0) {
        return null;
    }

    try {
        $stmt = db()->prepare("
            SELECT id, full_name, role, username, email, status
            FROM admin_users
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $adminUserId]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        return null;
    }

    if (!$row || strtolower((string) ($row['status'] ?? 'inactive')) !== 'active') {
        unset(
            $_SESSION['admin_user_id'],
            $_SESSION['admin_user_name'],
            $_SESSION['admin_username'],
            $_SESSION['admin_role']
        );
        return null;
    }

    $_SESSION['admin_user_id'] = (int) $row['id'];
    $_SESSION['admin_user_name'] = (string) ($row['full_name'] ?? 'Administrator');
    $_SESSION['admin_username'] = (string) ($row['username'] ?? '');
    $_SESSION['admin_role'] = (string) ($row['role'] ?? 'Barangay Official');

    $admin = $row;

    return $admin;
}

function requireAdminAuth(): array
{
    $admin = getCurrentAdmin();
    if (is_array($admin)) {
        return $admin;
    }

    header('Location: /BarangaySystem/public/adminlogin.php?err=' . urlencode('Please login as admin first.'));
    exit;
}
