<?php
session_start();
require_once __DIR__ . '/../../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /BarangaySystem/public/user/auth/login.php');
  exit;
}

$login = trim((string) ($_POST['login'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($login === '' || $password === '') {
  header('Location: /BarangaySystem/public/user/auth/login.php?err=' . urlencode('Please enter login and password.'));
  exit;
}

try {
  $stmt = db()->prepare("
    SELECT id, account_id, full_name, username, email, password_hash, status
    FROM site_users
    WHERE username = :login_username OR account_id = :login_account_id
    LIMIT 1
  ");
  $stmt->execute([
    'login_username' => $login,
    'login_account_id' => $login,
  ]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, (string) $user['password_hash'])) {
    header('Location: /BarangaySystem/public/user/auth/login.php?err=' . urlencode('Invalid login. Use Username or Account ID plus correct password.'));
    exit;
  }

  if (strtolower((string) ($user['status'] ?? 'active')) !== 'active') {
    header('Location: /BarangaySystem/public/user/auth/login.php?err=' . urlencode('Your account is not active.'));
    exit;
  }

  $_SESSION['site_user_id'] = (int) $user['id'];
  $_SESSION['site_account_id'] = (string) ($user['account_id'] ?? '');
  $_SESSION['site_user_name'] = (string) $user['full_name'];
  $_SESSION['site_username'] = (string) $user['username'];

  header('Location: /BarangaySystem/public/site/index.php');
  exit;
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/user/auth/login.php?err=' . urlencode('Login failed. Check database setup.'));
  exit;
}
