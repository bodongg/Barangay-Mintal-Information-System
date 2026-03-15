<?php
session_start();
require_once __DIR__ . '/../../../app/config/database.php';

if (!empty($_SESSION['site_user_id'])) {
  header('Location: /BarangaySystem/public/site/index.php');
  exit;
}

$error = trim((string) ($_GET['err'] ?? ''));
$success = trim((string) ($_GET['msg'] ?? ''));

$forgotModalOpen = false;
$forgotStep = 'email';
$forgotLoginValue = trim((string) ($_POST['forgot_login'] ?? ''));
$forgotEmail = '';
$forgotAccountId = '';
$forgotError = '';
$forgotNotice = '';
$forgotSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $forgotAction = trim((string) ($_POST['forgot_action'] ?? ''));

  if ($forgotAction === 'forgot_request') {
    $forgotModalOpen = true;
    $forgotStep = 'email';

    if ($forgotLoginValue === '') {
      $forgotError = 'Email or Account ID is required.';
    } else {
      try {
        $stmt = db()->prepare('SELECT id, email, account_id FROM site_users WHERE email = :lookup_email OR account_id = :lookup_account_id LIMIT 1');
        $stmt->execute([
          'lookup_email' => $forgotLoginValue,
          'lookup_account_id' => $forgotLoginValue,
        ]);
        $account = $stmt->fetch();

        if (!$account) {
          $forgotError = 'No account found for that email or account ID.';
        } else {
          $forgotEmail = (string) ($account['email'] ?? '');
          $forgotAccountId = (string) ($account['account_id'] ?? '');
          $forgotStep = 'notice';
          $forgotNotice = 'Reset link generated successfully.';
        }
      } catch (Throwable $e) {
        $forgotError = 'Failed to process forgot password. Check database setup.';
      }
    }
  } elseif ($forgotAction === 'forgot_reset') {
    $forgotModalOpen = true;
    $forgotStep = 'reset';
    $forgotEmail = trim((string) ($_POST['forgot_email'] ?? ''));
    $forgotAccountId = trim((string) ($_POST['forgot_account_id'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($forgotEmail === '') {
      $forgotError = 'Email is required.';
    } elseif (!filter_var($forgotEmail, FILTER_VALIDATE_EMAIL)) {
      $forgotError = 'Enter a valid email address.';
    } elseif (strlen($newPassword) < 6 || !preg_match('/[0-9]/', $newPassword) || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
      $forgotError = 'Password must be at least 6 characters with 1 number and 1 special character.';
    } elseif ($newPassword !== $confirmPassword) {
      $forgotError = 'Password and confirm password do not match.';
    } else {
      try {
        $stmt = db()->prepare('UPDATE site_users SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE email = :email LIMIT 1');
        $stmt->execute([
          'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
          'email' => $forgotEmail,
        ]);

        if ($stmt->rowCount() < 1) {
          $forgotError = 'No account found for that email address.';
        } else {
          $forgotStep = 'success';
          $forgotSuccess = 'Password reset successful.';
        }
      } catch (Throwable $e) {
        $forgotError = 'Failed to reset password. Check database setup.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Barangay Mintal</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BarangaySystem/assets/css/auth.css?v=<?= urlencode((string) filemtime(__DIR__ . '/../../../assets/css/auth.css')) ?>">
</head>
<body class="login-page">
  <div class="auth-layout">
    <div class="illus-side image-only" id="authSliderLogin">
      <div class="illus-slider" aria-label="Barangay slideshow">
        <div class="illus-slide active">
          <img src="/BarangaySystem/assets/images/Durian.jpg" alt="Durian Festival">
        </div>
        <div class="illus-slide">
          <img src="/BarangaySystem/assets/images/kadayawan.jpg" alt="Kadayawan Event">
        </div>
        <div class="illus-slide">
          <img src="/BarangaySystem/assets/images/sangguniang.jpg" alt="Sangguniang Meeting">
        </div>
      </div>
      <div class="illus-dots">
        <button type="button" class="illus-dot active" aria-label="Slide 1"></button>
        <button type="button" class="illus-dot" aria-label="Slide 2"></button>
        <button type="button" class="illus-dot" aria-label="Slide 3"></button>
      </div>
    </div>

    <div class="form-side">
      <div class="form-title">Login</div>
      <div class="form-subtitle">Login to access your Barangay Mintal account</div>

      <?php if ($error !== ''): ?>
        <div class="form-alert error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success !== ''): ?>
        <div class="form-alert success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="post" action="/BarangaySystem/public/user/auth/process_login.php">
        <div class="form-group">
          <label class="form-label">Username or Account ID</label>
          <input class="form-input" type="text" name="login" placeholder="Enter username or account ID" required>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="pw-wrap">
            <input class="form-input" type="password" id="pw1" name="password" placeholder="Enter your password" required>
            <button type="button" class="toggle-pw" onclick="togglePw('pw1')" aria-label="Toggle password visibility">
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

        <div class="remember-row">
          <label class="remember-label">
            <input type="checkbox"> Remember me
          </label>
          <button class="forgot-link forgot-link-btn" type="button" id="openForgotModal">Forgot Password</button>
        </div>

        <button class="btn-primary" type="submit">Login</button>
      </form>

      <div class="switch-text">
        Don't have an account?
        <a href="/BarangaySystem/public/user/auth/signup.php">Sign Up</a>
      </div>
    </div>
  </div>

  <div class="auth-modal-overlay<?= $forgotModalOpen ? ' is-open' : '' ?>" id="forgotModal" aria-hidden="<?= $forgotModalOpen ? 'false' : 'true' ?>">
    <div class="auth-modal-card" role="dialog" aria-modal="true" aria-labelledby="forgotPasswordTitle">
      <button class="auth-modal-close" id="closeForgotModal" aria-label="Close forgot password modal" type="button">&times;</button>
      <div class="auth-modal-title" id="forgotPasswordTitle">Forgot Password</div>
      <div class="auth-modal-subtitle">Enter your email or account ID and reset your password without leaving the login page.</div>

      <div class="forgot-step<?= $forgotStep === 'email' ? ' is-active' : '' ?>" data-forgot-step="email">
        <?php if ($forgotError !== '' && $forgotStep === 'email'): ?>
          <div class="form-alert error"><?= htmlspecialchars($forgotError) ?></div>
        <?php endif; ?>
        <form method="post" action="/BarangaySystem/public/user/auth/login.php">
          <input type="hidden" name="forgot_action" value="forgot_request">
          <div class="form-group">
            <label class="form-label">Email or Account ID</label>
            <input class="form-input" type="text" name="forgot_login" placeholder="Enter your email or account ID" value="<?= htmlspecialchars($forgotLoginValue) ?>" required>
          </div>
          <button class="btn-primary auth-modal-btn" type="submit">Send Reset Link</button>
        </form>
        <div class="auth-modal-footer">
          Remember your password?
          <button class="auth-modal-link" id="backToLoginBtn" type="button">Back to Login</button>
        </div>
      </div>

      <div class="forgot-step<?= $forgotStep === 'notice' ? ' is-active' : '' ?>" data-forgot-step="notice">
        <div class="auth-reset-notice">
          <div class="auth-reset-notice-title"><?= htmlspecialchars($forgotNotice !== '' ? $forgotNotice : 'Reset link generated successfully.') ?></div>
          <div class="auth-reset-notice-text">Account found for <strong><?= htmlspecialchars($forgotEmail) ?></strong>. Click below to continue to the reset form.</div>
          <button class="btn-primary auth-modal-btn auth-reset-cta" id="openResetStepBtn" type="button">Reset My Password</button>
          <button class="auth-modal-link-inline" id="dismissForgotNoticeBtn" type="button">Dismiss and go back to login</button>
        </div>
      </div>

      <div class="forgot-step<?= $forgotStep === 'reset' ? ' is-active' : '' ?>" data-forgot-step="reset">
        <?php if ($forgotError !== '' && $forgotStep === 'reset'): ?>
          <div class="form-alert error"><?= htmlspecialchars($forgotError) ?></div>
        <?php endif; ?>
        <form method="post" action="/BarangaySystem/public/user/auth/login.php">
          <input type="hidden" name="forgot_action" value="forgot_reset">
          <input type="hidden" name="forgot_email" value="<?= htmlspecialchars($forgotEmail) ?>">
          <input type="hidden" name="forgot_account_id" value="<?= htmlspecialchars($forgotAccountId) ?>">
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input class="form-input" type="email" value="<?= htmlspecialchars($forgotEmail) ?>" readonly>
          </div>
          <div class="form-group">
            <label class="form-label">Account ID</label>
            <input class="form-input" type="text" value="<?= htmlspecialchars($forgotAccountId) ?>" readonly>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <div class="pw-wrap">
              <input class="form-input" type="password" id="forgotPw1" name="new_password" placeholder="Enter new password" required>
              <button type="button" class="toggle-pw" onclick="togglePw('forgotPw1')" aria-label="Toggle password visibility">
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
              <input class="form-input" type="password" id="forgotPw2" name="confirm_password" placeholder="Confirm new password" required>
              <button type="button" class="toggle-pw" onclick="togglePw('forgotPw2')" aria-label="Toggle password visibility">
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
          <button class="auth-modal-link" id="resetBackToLoginBtn" type="button">Back to Login</button>
        </div>
      </div>

      <div class="forgot-step<?= $forgotStep === 'success' ? ' is-active' : '' ?>" data-forgot-step="success">
        <div class="auth-reset-notice auth-reset-success">
          <div class="auth-reset-notice-title"><?= htmlspecialchars($forgotSuccess !== '' ? $forgotSuccess : 'Password reset successful.') ?></div>
          <div class="auth-reset-notice-text">You can now log in using <strong><?= htmlspecialchars($forgotAccountId) ?></strong> or your username with the new password.</div>
          <button class="btn-primary auth-modal-btn auth-reset-cta" id="successBackToLoginBtn" type="button">Back to Login</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    function initAuthSlider(rootId) {
      const root = document.getElementById(rootId);
      if (!root) return;
      const slides = Array.from(root.querySelectorAll('.illus-slide'));
      const dots = Array.from(root.querySelectorAll('.illus-dot'));
      if (!slides.length || slides.length !== dots.length) return;

      let currentIndex = 0;
      let timerId = null;
      const showSlide = (nextIndex) => {
        currentIndex = (nextIndex + slides.length) % slides.length;
        slides.forEach((slide, idx) => slide.classList.toggle('active', idx === currentIndex));
        dots.forEach((dot, idx) => dot.classList.toggle('active', idx === currentIndex));
      };
      const startAuto = () => {
        if (timerId !== null) return;
        timerId = setInterval(() => showSlide(currentIndex + 1), 5000);
      };

      dots.forEach((dot, idx) => {
        dot.addEventListener('click', () => showSlide(idx));
      });

      startAuto();
    }

    function togglePw(id) {
      const i = document.getElementById(id);
      if (!i) return;
      const btn = i.parentElement ? i.parentElement.querySelector('.toggle-pw') : null;
      i.type = i.type === 'password' ? 'text' : 'password';
      if (btn) {
        btn.classList.toggle('is-visible', i.type === 'text');
      }
    }

    const forgotModal = document.getElementById('forgotModal');
    const openForgotModal = document.getElementById('openForgotModal');
    const closeForgotModal = document.getElementById('closeForgotModal');
    const backToLoginBtn = document.getElementById('backToLoginBtn');
    const resetBackToLoginBtn = document.getElementById('resetBackToLoginBtn');
    const successBackToLoginBtn = document.getElementById('successBackToLoginBtn');
    const openResetStepBtn = document.getElementById('openResetStepBtn');
    const dismissForgotNoticeBtn = document.getElementById('dismissForgotNoticeBtn');
    const forgotSteps = Array.from(document.querySelectorAll('[data-forgot-step]'));

    function showForgotModal() {
      if (!forgotModal) return;
      forgotModal.classList.add('is-open');
      forgotModal.setAttribute('aria-hidden', 'false');
    }

    function switchForgotStep(step) {
      forgotSteps.forEach((section) => {
        section.classList.toggle('is-active', section.getAttribute('data-forgot-step') === step);
      });
    }

    function hideForgotModal() {
      if (!forgotModal) return;
      forgotModal.classList.remove('is-open');
      forgotModal.setAttribute('aria-hidden', 'true');
      switchForgotStep('email');
    }

    if (openForgotModal) {
      openForgotModal.addEventListener('click', function (event) {
        event.preventDefault();
        showForgotModal();
      });
    }

    if (closeForgotModal) {
      closeForgotModal.addEventListener('click', function (event) {
        event.preventDefault();
        hideForgotModal();
      });
    }

    if (backToLoginBtn) {
      backToLoginBtn.addEventListener('click', function (event) {
        event.preventDefault();
        hideForgotModal();
      });
    }

    if (resetBackToLoginBtn) {
      resetBackToLoginBtn.addEventListener('click', function (event) {
        event.preventDefault();
        hideForgotModal();
      });
    }

    if (openResetStepBtn) {
      openResetStepBtn.addEventListener('click', function () {
        switchForgotStep('reset');
      });
    }

    if (dismissForgotNoticeBtn) {
      dismissForgotNoticeBtn.addEventListener('click', function () {
        hideForgotModal();
      });
    }

    if (successBackToLoginBtn) {
      successBackToLoginBtn.addEventListener('click', function () {
        hideForgotModal();
      });
    }

    if (forgotModal) {
      forgotModal.addEventListener('click', function (event) {
        if (event.target === forgotModal) {
          hideForgotModal();
        }
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        hideForgotModal();
      }
    });

    initAuthSlider('authSliderLogin');
    switchForgotStep('<?= htmlspecialchars($forgotStep, ENT_QUOTES) ?>');
  </script>
</body>
</html>
