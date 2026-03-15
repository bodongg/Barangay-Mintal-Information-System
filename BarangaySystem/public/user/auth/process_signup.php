<?php
session_start();
require_once __DIR__ . '/../../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /BarangaySystem/public/user/auth/signup.php');
  exit;
}

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($fullName === '' || $username === '' || $email === '' || $password === '') {
  header('Location: /BarangaySystem/public/user/auth/signup.php?err=' . urlencode('Please complete all required fields.'));
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header('Location: /BarangaySystem/public/user/auth/signup.php?err=' . urlencode('Email is invalid.'));
  exit;
}

if (strlen($password) < 6) {
  header('Location: /BarangaySystem/public/user/auth/signup.php?err=' . urlencode('Password must be at least 6 characters.'));
  exit;
}

if (!preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
  header('Location: /BarangaySystem/public/user/auth/signup.php?err=' . urlencode('Password must include at least 1 number and 1 special character.'));
  exit;
}

try {
  db()->exec("
    CREATE TABLE IF NOT EXISTS site_users (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      account_id VARCHAR(20) NULL UNIQUE,
      full_name VARCHAR(120) NOT NULL,
      username VARCHAR(80) NOT NULL UNIQUE,
      email VARCHAR(120) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'active',
      created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
  ");

  try {
    db()->exec("ALTER TABLE site_users ADD COLUMN account_id VARCHAR(20) NULL UNIQUE AFTER id");
  } catch (Throwable $ignore) {
    // Column already exists.
  }

  $check = db()->prepare("SELECT id FROM site_users WHERE username = :username OR email = :email LIMIT 1");
  $check->execute(['username' => $username, 'email' => $email]);
  if ($check->fetch()) {
    header('Location: /BarangaySystem/public/user/auth/signup.php?err=' . urlencode('Username or email is already used.'));
    exit;
  }

  $stmt = db()->prepare("
    INSERT INTO site_users (full_name, username, email, password_hash, status)
    VALUES (:full_name, :username, :email, :password_hash, 'active')
  ");
  $stmt->execute([
    'full_name' => $fullName,
    'username' => $username,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
  ]);

  $newId = (int) db()->lastInsertId();
  $accountId = 'A' . str_pad((string) $newId, 3, '0', STR_PAD_LEFT);
  $setAccountId = db()->prepare("UPDATE site_users SET account_id = :account_id WHERE id = :id");
  $setAccountId->execute([
    'account_id' => $accountId,
    'id' => $newId,
  ]);

  header('Location: /BarangaySystem/public/user/auth/login.php?msg=' . urlencode('Sign up successful. Your Account ID is ' . $accountId . '. Please login.'));
  exit;
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/user/auth/signup.php?err=' . urlencode('Failed to create account. Check database setup.'));
  exit;
}
