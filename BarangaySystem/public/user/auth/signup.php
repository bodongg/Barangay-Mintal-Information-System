<?php
session_start();

if (!empty($_SESSION['site_user_id'])) {
  header('Location: /BarangaySystem/public/site/index.php');
  exit;
}

$error = trim((string) ($_GET['err'] ?? ''));
$success = trim((string) ($_GET['msg'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Barangay San Jose</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BarangaySystem/assets/css/auth.css?v=<?= urlencode((string) filemtime(__DIR__ . '/../../../assets/css/auth.css')) ?>">
</head>
<body class="signup-page">
  <div class="auth-layout">
    <div class="illus-side image-only" id="authSliderSignup">
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
      <div class="form-title">Sign Up</div>
      <div class="form-subtitle">Create your Barangay San Jose account</div>

      <?php if ($error !== ''): ?>
        <div class="form-alert error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success !== ''): ?>
        <div class="form-alert success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="post" action="/BarangaySystem/public/user/auth/process_signup.php" id="signupForm">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input class="form-input" type="text" name="full_name" placeholder="e.g. Juan Dela Cruz" required>
          </div>
          <div class="form-group">
            <label class="form-label">Username</label>
            <input class="form-input" type="text" name="username" placeholder="e.g. juandelacruz" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input class="form-input" type="email" name="email" placeholder="e.g. juan@email.com" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Password</label>
            <div class="pw-wrap">
              <input class="form-input" id="signupPassword" type="password" name="password" minlength="6" pattern="^(?=.*[0-9])(?=.*[^A-Za-z0-9]).{6,}$" placeholder="Create password" required>
              <button type="button" class="toggle-pw" data-target="signupPassword" aria-label="Toggle password visibility">
                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
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
              <input class="form-input" id="signupConfirmPassword" type="password" minlength="6" placeholder="Re-enter password" required>
              <button type="button" class="toggle-pw" data-target="signupConfirmPassword" aria-label="Toggle password visibility">
                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a21.77 21.77 0 0 1 5.06-6.94"></path>
                  <path d="M9.9 4.24A10.93 10.93 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-2.16 3.19"></path>
                  <path d="M1 1l22 22"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <button class="btn-primary" type="submit">Create Account</button>
      </form>

      <div class="switch-text">
        Already have an account? <a href="/BarangaySystem/public/user/auth/login.php">Login</a>
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

    const signupPasswordInput = document.getElementById('signupPassword');
    signupPasswordInput.addEventListener('input', function () {
      signupPasswordInput.setCustomValidity('');
    });
    signupPasswordInput.addEventListener('invalid', function () {
      const value = signupPasswordInput.value;
      if (value.length < 6) {
        signupPasswordInput.setCustomValidity('Password must be at least 6 characters.');
      } else if (!/[0-9]/.test(value) || !/[^A-Za-z0-9]/.test(value)) {
        signupPasswordInput.setCustomValidity('Password must include at least 1 number and 1 special character.');
      } else {
        signupPasswordInput.setCustomValidity('');
      }
    });

    document.querySelectorAll('.toggle-pw').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.classList.toggle('is-visible', input.type === 'text');
      });
    });

    document.getElementById('signupForm').addEventListener('submit', function (event) {
      const password = document.getElementById('signupPassword').value;
      const confirmPassword = document.getElementById('signupConfirmPassword').value;
      if (password !== confirmPassword) {
        event.preventDefault();
        alert('Password and Confirm Password do not match.');
      }
    });

    initAuthSlider('authSliderSignup');
  </script>
</body>
</html>
