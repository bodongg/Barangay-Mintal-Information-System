<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
$admin = requireAdminAuth();

$pageTitle = "Admin Profile";
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

$errorMessage = '';
$profile = $defaultProfile;

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
    $profile = [
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
  if (!isset($_SESSION['admin_profile']) || !is_array($_SESSION['admin_profile'])) {
    $_SESSION['admin_profile'] = $defaultProfile;
  }
  $profile = array_merge($defaultProfile, $_SESSION['admin_profile']);
}

$initial = strtoupper(substr((string) $profile['full_name'], 0, 1));
?>

<div class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:10px;">
      <a class="btn btn-light" href="/BarangaySystem/public/dashboard.php">Back</a>
      <div class="topbar-title">Profile</div>
    </div>
    <div class="topbar-right">
      <div class="topbar-user">
        <div class="avatar"><?= htmlspecialchars($initial) ?></div>
        <div class="topbar-user-info">
          <div class="topbar-user-name"><?= htmlspecialchars((string) $profile['full_name']) ?></div>
          <div class="topbar-user-role"><?= htmlspecialchars((string) $profile['role']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="card">
      <div class="table-header">
        <div>
          <div class="card-title">Admin Profile</div>
          <div class="card-sub">View account information</div>
        </div>
        <div class="table-actions">
          <a class="btn btn-primary" href="/BarangaySystem/public/profile/edit.php">Edit Profile</a>
        </div>
      </div>

      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
      <div style="margin-bottom:10px;padding:10px 12px;border-radius:10px;background:#e9f9ee;border:1px solid #cbeed6;color:#0b6b2a;font-size:12px;font-weight:800;">
        Profile updated successfully.
      </div>
      <?php endif; ?>
      <?php if ($errorMessage !== ''): ?>
      <div style="margin-bottom:10px;padding:10px 12px;border-radius:10px;background:#ffe9e9;border:1px solid #ffd0d0;color:#a10000;font-size:12px;font-weight:800;">
        <?= htmlspecialchars($errorMessage) ?>
      </div>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Full Name</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $profile['full_name']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Role</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $profile['role']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Username</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $profile['username']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Email</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $profile['email']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Phone</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $profile['phone']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Office Address</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $profile['address']) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>
