<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';

if (!empty($_SESSION['admin_user_id'])) {
  header('Location: /BarangaySystem/public/dashboard.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /BarangaySystem/public/adminlogin.php');
  exit;
}

$login = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($login === '' || $password === '') {
  header('Location: /BarangaySystem/public/adminlogin.php?err=' . urlencode('Please enter username/email and password.'));
  exit;
}

try {
  $stmt = db()->prepare("
    SELECT id, full_name, role, username, email, password_hash, status
    FROM admin_users
    WHERE username = :login_username OR email = :login_email
    LIMIT 1
  ");
  $stmt->execute([
    'login_username' => $login,
    'login_email' => $login,
  ]);
  $admin = $stmt->fetch();

  if (!$admin || !password_verify($password, (string) ($admin['password_hash'] ?? ''))) {
    header('Location: /BarangaySystem/public/adminlogin.php?err=' . urlencode('Invalid admin login credentials.'));
    exit;
  }

  if (strtolower((string) ($admin['status'] ?? 'active')) !== 'active') {
    header('Location: /BarangaySystem/public/adminlogin.php?err=' . urlencode('This admin account is not active.'));
    exit;
  }

  $_SESSION['admin_user_id'] = (int) ($admin['id'] ?? 0);
  $_SESSION['admin_user_name'] = (string) ($admin['full_name'] ?? 'Administrator');
  $_SESSION['admin_username'] = (string) ($admin['username'] ?? '');
  $_SESSION['admin_role'] = (string) ($admin['role'] ?? 'Barangay Official');

  header('Location: /BarangaySystem/public/dashboard.php');
  exit;
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/adminlogin.php?err=' . urlencode('Admin login failed. Check database setup.'));
  exit;
}
