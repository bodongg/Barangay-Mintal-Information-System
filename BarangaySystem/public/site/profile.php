<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/site_users.php';

if (empty($_SESSION['site_user_id'])) {
  header('Location: /BarangaySystem/public/user/auth/login.php?err=' . urlencode('Please login first.'));
  exit;
}

$stmt = db()->prepare("
  SELECT id, account_id, full_name, username, email, status, profile_photo, created_at
  FROM site_users
  WHERE id = :id
  LIMIT 1
");
$stmt->execute(['id' => (int) $_SESSION['site_user_id']]);
$user = $stmt->fetch();

if (!$user) {
  session_unset();
  session_destroy();
  header('Location: /BarangaySystem/public/user/auth/login.php?err=' . urlencode('Session expired. Please login again.'));
  exit;
}

$profileInitials = '';
foreach (preg_split('/\s+/', trim((string) ($user['full_name'] ?? ''))) ?: [] as $part) {
  if ($part !== '') {
    $profileInitials .= strtoupper(substr($part, 0, 1));
  }
  if (strlen($profileInitials) >= 2) {
    break;
  }
}
if ($profileInitials === '') {
  $profileInitials = strtoupper(substr((string) ($user['username'] ?? 'U'), 0, 1));
}

$profileError = '';
$profileSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = trim((string) ($_POST['full_name'] ?? ''));
  $username = trim((string) ($_POST['username'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $newPassword = (string) ($_POST['new_password'] ?? '');
  $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
  $isChangingPassword = ($newPassword !== '' || $confirmPassword !== '');
  $photoErrors = [];
  $uploadedPhoto = storeSiteUserPhotoUpload($_FILES['profile_photo'] ?? [], $photoErrors);

  if (count($photoErrors) > 0) {
    $profileError = implode(' ', $photoErrors);
  } elseif ($fullName === '' || $username === '' || $email === '') {
    $profileError = 'Full name, username, and email are required.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $profileError = 'Email format is invalid.';
  } elseif ($isChangingPassword && ($newPassword === '' || $confirmPassword === '')) {
    $profileError = 'Please fill both New Password and Confirm New Password.';
  } elseif ($isChangingPassword && strlen($newPassword) < 6) {
    $profileError = 'New password must be at least 6 characters.';
  } elseif (
    $isChangingPassword &&
    (!preg_match('/[0-9]/', $newPassword) || !preg_match('/[^A-Za-z0-9]/', $newPassword))
  ) {
    $profileError = 'New password must include at least 1 number and 1 special character.';
  } elseif ($isChangingPassword && $newPassword !== $confirmPassword) {
    $profileError = 'New password and confirm password do not match.';
  } else {
    try {
      $dupCheck = db()->prepare("
        SELECT id
        FROM site_users
        WHERE (username = :username OR email = :email)
          AND id <> :id
        LIMIT 1
      ");
      $dupCheck->execute([
        'username' => $username,
        'email' => $email,
        'id' => (int) $user['id'],
      ]);

      if ($dupCheck->fetch()) {
        $profileError = 'Username or email is already used by another account.';
      } else {
        $profilePhoto = $uploadedPhoto !== null ? $uploadedPhoto : (string) ($user['profile_photo'] ?? '');
        if ($isChangingPassword) {
          $updateStmt = db()->prepare("
            UPDATE site_users
            SET full_name = :full_name,
                username = :username,
                email = :email,
                profile_photo = :profile_photo,
                password_hash = :password_hash
            WHERE id = :id
            LIMIT 1
          ");
          $updateStmt->execute([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'profile_photo' => $profilePhoto,
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => (int) $user['id'],
          ]);
        } else {
          $updateStmt = db()->prepare("
            UPDATE site_users
            SET full_name = :full_name,
                username = :username,
                email = :email,
                profile_photo = :profile_photo
            WHERE id = :id
            LIMIT 1
          ");
          $updateStmt->execute([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'profile_photo' => $profilePhoto,
            'id' => (int) $user['id'],
          ]);
        }

        if ($uploadedPhoto !== null && !empty($user['profile_photo']) && $user['profile_photo'] !== $uploadedPhoto) {
          deleteSiteUserPhotoFile((string) $user['profile_photo']);
        }

        $_SESSION['site_user_name'] = $fullName;
        $_SESSION['site_username'] = $username;
        $profileSuccess = 'Profile updated successfully.';

        $refresh = db()->prepare("
          SELECT id, account_id, full_name, username, email, status, profile_photo, created_at
          FROM site_users
          WHERE id = :id
          LIMIT 1
        ");
        $refresh->execute(['id' => (int) $user['id']]);
        $freshUser = $refresh->fetch();
        if ($freshUser) {
          $user = $freshUser;
        }
      }
    } catch (Throwable $e) {
      if ($uploadedPhoto !== null) {
        deleteSiteUserPhotoFile($uploadedPhoto);
      }
      $profileError = 'Failed to update profile. Check database setup.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile - Barangay San Jose</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/BarangaySystem/assets/site/css/public-website.css?v=1"/>
  <style>
    .profile-page {
      background: #0d1f4c;
      min-height: 100vh;
    }
    .profile-wrap {
      max-width: 980px;
      margin: 24px auto 0;
      padding: 0 20px 36px;
    }
    .profile-headbar {
      position: relative;
      display: inline-block;
      margin-bottom: 12px;
    }
    .profile-back-link {
      position: absolute;
      right: calc(100% + 12px);
      top: 50%;
      transform: translateY(-50%);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 14px;
      border-radius: 999px;
      background: rgba(255,255,255,.1);
      border: 1px solid rgba(255,255,255,.16);
      color: #fff;
      font-size: 12px;
      font-weight: 800;
      white-space: nowrap;
      transition: background .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .profile-back-link:hover {
      background: rgba(255,255,255,.16);
      border-color: rgba(255,255,255,.26);
      box-shadow: 0 10px 22px rgba(5, 13, 36, 0.18);
    }
    .profile-card {
      margin-top: 18px;
      background: #ffffff;
      border: 1px solid #dfe5fb;
      border-radius: 18px;
      padding: 24px;
      box-shadow: 0 16px 38px rgba(12, 30, 74, 0.08);
    }
    .profile-hero {
      margin-top: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .profile-avatar {
      width: 132px;
      height: 132px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(255,255,255,.26), rgba(255,255,255,.12));
      border: 2px solid rgba(255,255,255,.35);
      box-shadow: 0 18px 36px rgba(6, 17, 46, 0.26);
      color: #fff;
      font-size: 40px;
      font-weight: 900;
      letter-spacing: 1px;
      flex-shrink: 0;
      backdrop-filter: blur(10px);
      overflow: hidden;
      cursor: pointer;
      transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }
    .profile-avatar-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform .25s ease, filter .25s ease;
    }
    .profile-avatar:hover {
      transform: translateY(-4px) scale(1.03);
      border-color: rgba(255,255,255,.55);
      box-shadow: 0 22px 42px rgba(6, 17, 46, 0.34);
    }
    .profile-avatar:hover .profile-avatar-img {
      transform: scale(1.08);
      filter: saturate(1.06);
    }
    .profile-avatar-panel {
      margin-top: 18px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      margin-bottom: 18px;
    }
    .profile-avatar-picker {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 9px 16px;
      border-radius: 999px;
      background: #ffffff;
      border: 1px solid #d8e0fb;
      color: #0d1f4c;
      font-size: 13px;
      font-weight: 800;
      cursor: pointer;
      box-shadow: 0 10px 24px rgba(12, 30, 74, 0.12);
      transition: transform .18s ease, background .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .profile-avatar-picker:hover {
      transform: translateY(-2px);
      background: #f5f8ff;
      border-color: #bfcdf8;
      box-shadow: 0 14px 28px rgba(12, 30, 74, 0.16);
    }
    .profile-avatar-input {
      display: none;
    }
    .profile-avatar-help {
      font-size: 12px;
      color: #7082a6;
      font-weight: 700;
      text-align: center;
    }
    .profile-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }
    .profile-span-2 {
      grid-column: 1 / span 2;
    }
    .profile-label {
      font-size: 12px;
      color: #7082a6;
      font-weight: 700;
      margin-bottom: 6px;
    }
    .profile-input,
    .profile-readonly {
      width: 100%;
      background: #f3f6ff;
      border: 1px solid #dfe5fb;
      border-radius: 10px;
      padding: 11px 12px;
      font-size: 14px;
      font-weight: 700;
      color: #1a1f2e;
      font-family: inherit;
    }
    .profile-pw-wrap {
      position: relative;
    }
    .profile-pw-wrap .profile-input {
      padding-right: 42px;
    }
    .profile-toggle-pw {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      border: none;
      background: transparent;
      padding: 0;
      color: #7f8fb2;
      display: inline-flex;
      align-items: center;
      cursor: pointer;
    }
    .profile-toggle-pw:hover {
      color: #1b3a87;
    }
    .profile-toggle-pw svg {
      width: 17px;
      height: 17px;
    }
    .profile-toggle-pw .eye-closed {
      display: none;
    }
    .profile-toggle-pw.is-visible .eye-open {
      display: none;
    }
    .profile-toggle-pw.is-visible .eye-closed {
      display: inline;
    }
    .profile-input:focus {
      outline: none;
      border-color: #284da8;
      box-shadow: 0 0 0 3px rgba(40, 77, 168, 0.14);
      background: #fff;
    }
    .profile-readonly {
      opacity: 0.85;
    }
    .profile-actions {
      margin-top: 16px;
      display: flex;
      justify-content: flex-end;
    }
    .profile-save {
      border: none;
      background: #0d1f4c;
      color: #fff;
      border-radius: 10px;
      padding: 10px 16px;
      font-size: 13px;
      font-weight: 800;
      cursor: pointer;
    }
    .profile-alert {
      border-radius: 10px;
      padding: 10px 12px;
      font-size: 12px;
      font-weight: 700;
      margin-top: 14px;
    }
    .profile-alert.error {
      background: #ffeaea;
      border: 1px solid #ffcaca;
      color: #9d1212;
    }
    .profile-alert.success {
      background: #e9f8ee;
      border: 1px solid #c8edcf;
      color: #0f6b2d;
    }
    .profile-help {
      margin-top: 8px;
      font-size: 12px;
      color: #6b7fa9;
      font-weight: 600;
    }
    @media (max-width: 820px) {
      .profile-headbar {
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .profile-back-link {
        position: static;
        transform: none;
      }
      .profile-grid {
        grid-template-columns: 1fr;
      }
      .profile-span-2 {
        grid-column: auto;
      }
    }
  </style>
</head>
<body class="profile-page">
<nav class="navbar">
  <a class="nav-brand" href="/BarangaySystem/public/site/index.php" aria-label="Go to homepage">
    <img class="nav-logo" src="/BarangaySystem/assets/images/LOGO%20BARANGAY.png" alt="Barangay Logo"/>
  </a>
  <div class="nav-links">
    <a class="nav-link" href="/BarangaySystem/public/site/index.php#services">Services</a>
    <a class="nav-link" href="/BarangaySystem/public/site/index.php#agenda">Agenda</a>
    <a class="nav-link" href="/BarangaySystem/public/site/index.php#news">News</a>
    <a class="nav-link" href="/BarangaySystem/public/site/index.php#officials">Officials</a>
    <a class="nav-link" href="/BarangaySystem/public/site/index.php#contact">Contact</a>
    <div style="display:flex;align-items:center;gap:6px;margin-left:8px;border-left:1px solid var(--border);padding-left:14px;">
      <a href="/BarangaySystem/public/site/profile.php" style="display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:12px;font-weight:700;padding:7px 13px;border-radius:8px;background:var(--primary);">My Profile</a>
      <a href="/BarangaySystem/public/user/auth/logout.php" style="display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:12px;font-weight:700;padding:7px 13px;border-radius:8px;background:#0a173c;">Logout</a>
    </div>
  </div>
</nav>

<section class="profile-wrap">
  <div class="profile-headbar">
    <a class="profile-back-link" href="/BarangaySystem/public/site/index.php">
      <span aria-hidden="true">&larr;</span>
      <span>Back</span>
    </a>
    <div class="section-tag" style="margin-bottom:0;">Account</div>
  </div>
  <div class="section-title" style="color:#fff;">My Profile</div>
  <p class="section-sub" style="color:#d0dcff;">Edit your account details and password.</p>

  <div class="profile-hero">
    <div class="profile-avatar" id="profileAvatarPreview">
      <?php if (trim((string) ($user['profile_photo'] ?? '')) !== ''): ?>
      <img class="profile-avatar-img" src="<?= htmlspecialchars((string) $user['profile_photo']) ?>" alt="<?= htmlspecialchars((string) ($user['full_name'] ?? 'User')) ?>">
      <?php else: ?>
      <?= htmlspecialchars($profileInitials) ?>
      <?php endif; ?>
    </div>
  </div>

  <form class="profile-card" method="post" action="/BarangaySystem/public/site/profile.php" enctype="multipart/form-data">
    <div class="profile-avatar-panel">
      <label class="profile-avatar-picker" for="profilePhotoInput">Change Avatar</label>
      <input class="profile-avatar-input" id="profilePhotoInput" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp,image/gif,image/*" capture="user">
      <div class="profile-avatar-help">Click "Change Avatar" to open your files or camera.</div>
    </div>

    <div class="profile-grid">
      <div>
        <div class="profile-label">Account ID</div>
        <input class="profile-readonly" type="text" value="<?= htmlspecialchars((string) ($user['account_id'] ?? '')) ?>" readonly>
      </div>
      <div>
        <div class="profile-label">Status</div>
        <input class="profile-readonly" type="text" value="<?= htmlspecialchars(ucfirst((string) ($user['status'] ?? 'active'))) ?>" readonly>
      </div>
      <div>
        <div class="profile-label">Full Name</div>
        <input class="profile-input" type="text" name="full_name" value="<?= htmlspecialchars((string) ($user['full_name'] ?? '')) ?>" required>
      </div>
      <div>
        <div class="profile-label">Username</div>
        <input class="profile-input" type="text" name="username" value="<?= htmlspecialchars((string) ($user['username'] ?? '')) ?>" required>
      </div>
      <div class="profile-span-2">
        <div class="profile-label">Email</div>
        <input class="profile-input" type="email" name="email" value="<?= htmlspecialchars((string) ($user['email'] ?? '')) ?>" required>
      </div>
      <div>
        <div class="profile-label">New Password</div>
        <div class="profile-pw-wrap">
          <input class="profile-input" id="profileNewPassword" type="password" name="new_password" placeholder="Leave blank if no change" minlength="6" pattern="^(?=.*[0-9])(?=.*[^A-Za-z0-9]).{6,}$">
          <button type="button" class="profile-toggle-pw" data-target="profileNewPassword" aria-label="Toggle password visibility">
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
      <div>
        <div class="profile-label">Confirm New Password</div>
        <div class="profile-pw-wrap">
          <input class="profile-input" id="profileConfirmPassword" type="password" name="confirm_password" placeholder="Re-enter new password" minlength="6">
          <button type="button" class="profile-toggle-pw" data-target="profileConfirmPassword" aria-label="Toggle password visibility">
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
      <div class="profile-span-2">
        <div class="profile-label">Member Since</div>
        <input class="profile-readonly" type="text" value="<?= htmlspecialchars((string) ($user['created_at'] ?? '')) ?>" readonly>
      </div>
    </div>

    <?php if ($profileError !== ''): ?>
      <div class="profile-alert error"><?= htmlspecialchars($profileError) ?></div>
    <?php endif; ?>
    <?php if ($profileSuccess !== ''): ?>
      <div class="profile-alert success"><?= htmlspecialchars($profileSuccess) ?></div>
    <?php endif; ?>

    <div class="profile-help">Password change is optional. If changing, use at least 6 characters with 1 number and 1 special character.</div>
    <div class="profile-actions">
      <button class="profile-save" type="submit">Save Profile</button>
    </div>
  </form>
</section>
<script>
  const profilePasswordInput = document.getElementById('profileNewPassword');
  profilePasswordInput.addEventListener('input', function () {
    profilePasswordInput.setCustomValidity('');
  });
  profilePasswordInput.addEventListener('invalid', function () {
    const value = profilePasswordInput.value;
    if (value === '') {
      profilePasswordInput.setCustomValidity('');
      return;
    }
    if (value.length < 6) {
      profilePasswordInput.setCustomValidity('New password must be at least 6 characters.');
    } else if (!/[0-9]/.test(value) || !/[^A-Za-z0-9]/.test(value)) {
      profilePasswordInput.setCustomValidity('New password must include at least 1 number and 1 special character.');
    } else {
      profilePasswordInput.setCustomValidity('');
    }
  });

  document.querySelectorAll('.profile-toggle-pw').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      btn.classList.toggle('is-visible', input.type === 'text');
    });
  });

  const profilePhotoInput = document.getElementById('profilePhotoInput');
  const profileAvatarPreview = document.getElementById('profileAvatarPreview');
  if (profilePhotoInput && profileAvatarPreview) {
    profilePhotoInput.addEventListener('change', function () {
      const file = profilePhotoInput.files && profilePhotoInput.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function (event) {
        const result = event.target && event.target.result ? String(event.target.result) : '';
        profileAvatarPreview.innerHTML = '<img class="profile-avatar-img" src="' + result + '" alt="Profile avatar preview">';
      };
      reader.readAsDataURL(file);
    });
  }
</script>
</body>
</html>
