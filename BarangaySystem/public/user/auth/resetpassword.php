<?php
session_start();
require_once __DIR__ . '/../../../app/config/database.php';

if (!empty($_SESSION['site_user_id'])) {
  header('Location: /BarangaySystem/public/site/index.php');
  exit;
}

$email = trim((string) ($_REQUEST['email'] ?? ''));
$step = trim((string) ($_GET['step'] ?? 'notice'));
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string) ($_POST['email'] ?? ''));
  $password = (string) ($_POST['password'] ?? '');
  $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
  $step = 'form';

  if ($email === '') {
    $error = 'Email is required.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Enter a valid email address.';
  } elseif (strlen($password) < 6 || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
    $error = 'Password must be at least 6 characters with 1 number and 1 special character.';
  } elseif ($password !== $confirmPassword) {
    $error = 'Password and confirm password do not match.';
  } else {
    try {
      $stmt = db()->prepare('UPDATE site_users SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE email = :email LIMIT 1');
      $stmt->execute([
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'email' => $email,
      ]);

      if ($stmt->rowCount() < 1) {
        $error = 'No account found for that email address.';
      } else {
        header('Location: /BarangaySystem/public/user/auth/login.php?msg=' . urlencode('Password reset successful. Please login with your new password.'));
        exit;
      }
    } catch (Throwable $e) {
      $error = 'Failed to reset password. Check database setup.';
    }
  }
}

if ($email === '') {
  $error = $error !== '' ? $error : 'Email is required to continue.';
  $step = 'notice';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - Barangay Mintal</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BarangaySystem/assets/css/auth.css?v=<?= urlencode((string) filemtime(__DIR__ . '/../../../assets/css/auth.css')) ?>">
</head>
<body class="reset-page">
  <div class="auth-single-layout">
    <div class="auth-single-card">
      <?php if ($step === 'notice' && $email !== '' && $error === ''): ?>
        <div class="auth-modal-title">Forgot Password</div>
        <div class="auth-modal-subtitle">Reset link generated for <strong><?= htmlspecialchars($email) ?></strong>.</div>
        <div class="auth-reset-notice">
          <div class="auth-reset-notice-title">Reset link generated successfully</div>
          <div class="auth-reset-notice-text">Click the button below to continue to the password reset form.</div>
          <a class="btn-primary auth-modal-btn" href="/BarangaySystem/public/user/auth/resetpassword.php?step=form&amp;email=<?= urlencode($email) ?>">Reset My Password</a>
          <div class="auth-reset-notice-meta">This demo link stays active while you are on this page.</div>
          <a class="auth-modal-link-inline" href="/BarangaySystem/public/user/auth/login.php">Dismiss and go back to login</a>
        </div>
      <?php else: ?>
        <div class="auth-modal-title">Reset Password</div>
        <div class="auth-modal-subtitle">Enter a new password for <strong><?= htmlspecialchars($email) ?></strong>.</div>

        <?php if ($error !== ''): ?>
          <div class="form-alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/BarangaySystem/public/user/auth/resetpassword.php?step=form">
          <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

          <div class="form-group">
            <label class="form-label">New Password</label>
            <div class="pw-wrap">
              <input class="form-input" id="resetPassword" type="password" name="password" minlength="6" pattern="^(?=.*[0-9])(?=.*[^A-Za-z0-9]).{6,}$" placeholder="Enter new password" required>
              <button type="button" class="toggle-pw" data-target="resetPassword" aria-label="Toggle password visibility">
                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a21.77 21.77 0 0 1 5.06-6.94"></path>
                  <path d="M9.9 4.24A10.93 10.93 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-2.16 3.19"></path>
                  <path d="M1 1l22 22"></path>
                </svg>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <div class="pw-wrap">
              <input class="form-input" id="resetConfirmPassword" type="password" name="confirm_password" minlength="6" placeholder="Confirm new password" required>
              <button type="button" class="toggle-pw" data-target="resetConfirmPassword" aria-label="Toggle password visibility">
                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a21.77 21.77 0 0 1 5.06-6.94"></path>
                  <path d="M9.9 4.24A10.93 10.93 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-2.16 3.19"></path>
                  <path d="M1 1l22 22"></path>
                </svg>
              </button>
            </div>
          </div>

          <button class="btn-primary auth-modal-btn" type="submit">Reset Password</button>
        </form>

        <div class="auth-modal-footer">
          Remember your password?
          <a class="auth-modal-link-inline" href="/BarangaySystem/public/user/auth/login.php">Back to Login</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    document.querySelectorAll('.toggle-pw').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.classList.toggle('is-visible', input.type === 'text');
      });
    });
  </script>
</body>
</html>
