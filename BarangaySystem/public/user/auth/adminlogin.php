<?php
session_start();

if (!empty($_SESSION['admin_user_id'])) {
  header('Location: /BarangaySystem/public/dashboard.php');
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
  <title>Barangay Mintal - Admin Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700;900&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BarangaySystem/assets/css/adminlogin.css">
</head>
<body>
  <div class="bg"></div>

  <div class="page">
    <section class="hero">
      <h1>
        A <span class="accent">modern way</span> of managing<br>
        <span class="accent">your barangay</span> services
      </h1>
      <p class="tagline">
        This is the official <span class="highlight">Barangay Mintal Admin Portal</span> for
        managing residents, certificates, blotter reports, and community records.
      </p>
      <div class="features">
        <div class="feature-item">
          <h3>Resident Records Organized</h3>
          <p>Access resident, household, and profiling data from one admin dashboard.</p>
        </div>
        <div class="feature-item">
          <h3>Requests Processed Faster</h3>
          <p>Review online certificate and blotter submissions without switching tools.</p>
        </div>
        <div class="feature-item">
          <h3>Community Updates Centralized</h3>
          <p>Post announcements and monitor barangay activity in a single workspace.</p>
        </div>
      </div>
    </section>

    <aside class="panel">
      <div class="logo-wrap">
        <div class="logo-circle">
          <img src="/BarangaySystem/assets/officialimages/logo_davao.png" alt="Davao logo">
        </div>
        <div class="logo-label">Barangay Mintal Management System</div>
        <span class="admin-badge">Admin Portal</span>
      </div>

      <form class="form-box" method="post" action="/BarangaySystem/public/login.php">
        <?php if ($error !== ''): ?>
          <div class="form-alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
          <div class="form-alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="input-group">
          <span class="icon">@</span>
          <input type="text" id="email" name="username" placeholder="Username or Email" autocomplete="username" required>
        </div>

        <div class="input-group">
          <span class="icon">#</span>
          <input type="password" id="password" name="password" placeholder="Password" autocomplete="current-password" required>
          <button class="eye-btn" onclick="togglePw()" type="button" aria-label="Toggle password visibility">
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

        <button class="btn btn-primary" type="submit">Login</button>

        <div class="privacy"><a href="/BarangaySystem/public/site/index.php">Back to public website</a></div>
      </form>
    </aside>
  </div>

  <script>
    function togglePw() {
      const pw = document.getElementById('password');
      if (!pw) return;
      pw.type = pw.type === 'password' ? 'text' : 'password';
      const btn = document.querySelector('.eye-btn');
      if (btn) {
        btn.classList.toggle('is-visible', pw.type === 'text');
      }
    }
  </script>
</body>
</html>
