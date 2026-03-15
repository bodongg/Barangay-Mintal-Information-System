<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
$admin = requireAdminAuth();

$pageTitle = "Edit Admin Profile";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
require_once __DIR__ . '/../../app/config/database.php';

$defaultProfile = [
  'full_name' => (string) ($admin['full_name'] ?? 'Admin'),
  'role' => (string) ($admin['role'] ?? 'Barangay Official'),
  'username' => (string) ($admin['username'] ?? 'admin'),
  'email' => (string) ($admin['email'] ?? 'admin@barangay.local'),
  'phone' => '09xx xxx xxxx',
  'address' => 'Barangay Hall',
];

if (!isset($_SESSION['admin_profile']) || !is_array($_SESSION['admin_profile'])) {
  $_SESSION['admin_profile'] = $defaultProfile;
}

$form = $defaultProfile;
$errors = [];
$roleOptions = [
  'Barangay Official',
  'Barangay Captain',
  'Barangay Secretary',
  'Barangay Staff',
];
$errorMessage = '';

try {
  $stmt = db()->prepare("
    SELECT full_name, role, username, email, phone, address
    FROM admin_users
    WHERE id = :id
    LIMIT 1
  ");
  $stmt->execute(['id' => (int) $admin['id']]);
  $row = $stmt->fetch();

  if ($row) {
    $form = [
      'full_name' => (string) ($row['full_name'] ?? $defaultProfile['full_name']),
      'role' => (string) ($row['role'] ?? $defaultProfile['role']),
      'username' => (string) ($row['username'] ?? $defaultProfile['username']),
      'email' => (string) ($row['email'] ?? $defaultProfile['email']),
      'phone' => (string) ($row['phone'] ?? $defaultProfile['phone']),
      'address' => (string) ($row['address'] ?? $defaultProfile['address']),
    ];
  }
} catch (Throwable $e) {
  $errorMessage = 'Database not ready for admin profile. Run the SQL setup first.';
  $form = array_merge($defaultProfile, $_SESSION['admin_profile']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
  $form['role'] = trim((string) ($_POST['role'] ?? ''));
  $form['username'] = trim((string) ($_POST['username'] ?? ''));
  $form['email'] = trim((string) ($_POST['email'] ?? ''));
  $form['phone'] = trim((string) ($_POST['phone'] ?? ''));
  $form['address'] = trim((string) ($_POST['address'] ?? ''));

  if ($form['full_name'] === '') $errors[] = 'Full name is required.';
  if (!in_array($form['role'], $roleOptions, true)) $errors[] = 'Role is invalid.';
  if ($form['username'] === '') $errors[] = 'Username is required.';
  if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is invalid.';

  if (count($errors) === 0) {
    try {
      $stmt = db()->prepare("
        UPDATE admin_users
        SET full_name = :full_name,
            role = :role,
            username = :username,
            email = :email,
            phone = :phone,
            address = :address
        WHERE id = :id
      ");
      $stmt->execute($form + ['id' => (int) $admin['id']]);

      $_SESSION['admin_profile'] = $form;
      $_SESSION['admin_user_name'] = $form['full_name'];
      $_SESSION['admin_username'] = $form['username'];
      $_SESSION['admin_role'] = $form['role'];
      header('Location: /BarangaySystem/public/profile/index.php?msg=updated');
      exit;
    } catch (Throwable $e) {
      $errorMessage = 'Failed to save profile in database. Check SQL setup.';
    }
  }
}
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Profile</div>
    <div class="topbar-right">
      <div class="topbar-user">
        <div class="avatar"><?= htmlspecialchars(strtoupper(substr((string) $form['full_name'], 0, 1))) ?></div>
        <div class="topbar-user-info">
          <div class="topbar-user-name"><?= htmlspecialchars((string) $form['full_name']) ?></div>
          <div class="topbar-user-role"><?= htmlspecialchars((string) $form['role']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="card">
      <div class="table-header">
        <div>
          <div class="card-title">Edit Admin Profile</div>
          <div class="card-sub">Update account information</div>
        </div>
        <div class="table-actions">
          <a class="btn btn-light" href="/BarangaySystem/public/profile/index.php">Back to Profile</a>
        </div>
      </div>

      <?php if (count($errors) > 0): ?>
      <div style="margin-bottom:14px;padding:10px 12px;border-radius:10px;background:#ffe9e9;border:1px solid #ffd0d0;color:#a10000;font-size:12px;font-weight:800;">
        <?= htmlspecialchars(implode(' ', $errors)) ?>
      </div>
      <?php endif; ?>
      <?php if ($errorMessage !== ''): ?>
      <div style="margin-bottom:14px;padding:10px 12px;border-radius:10px;background:#ffe9e9;border:1px solid #ffd0d0;color:#a10000;font-size:12px;font-weight:800;">
        <?= htmlspecialchars($errorMessage) ?>
      </div>
      <?php endif; ?>

      <form method="post">
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Full Name *</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="full_name" value="<?= htmlspecialchars((string) $form['full_name']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Role *</label>
            <select class="input" style="width:100%;min-width:0;" name="role" required>
              <?php foreach ($roleOptions as $role): ?>
              <option value="<?= htmlspecialchars($role) ?>" <?= $form['role'] === $role ? 'selected' : '' ?>><?= htmlspecialchars($role) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Username *</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="username" value="<?= htmlspecialchars((string) $form['username']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Email</label>
            <input class="input" style="width:100%;min-width:0;" type="email" name="email" value="<?= htmlspecialchars((string) $form['email']) ?>">
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Phone</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="phone" value="<?= htmlspecialchars((string) $form['phone']) ?>">
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Office Address</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="address" value="<?= htmlspecialchars((string) $form['address']) ?>">
          </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
          <a class="btn btn-light" href="/BarangaySystem/public/profile/index.php">Cancel</a>
          <button class="btn btn-primary" type="submit">Save Profile</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>
