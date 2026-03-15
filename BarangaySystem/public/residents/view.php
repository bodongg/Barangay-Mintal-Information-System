<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "View Resident";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /BarangaySystem/public/residents/index.php');
  exit;
}

try {
  $stmt = db()->prepare("
    SELECT
      id,
      name,
      address,
      purok,
      gender,
      age,
      civil_status,
      status,
      is_pwd,
      pwd_type,
      additional_classification,
      created_at,
      updated_at
    FROM residents
    WHERE id = :id
    LIMIT 1
  ");
  $stmt->execute(['id' => $id]);
  $resident = $stmt->fetch();
  if (!$resident) {
    header('Location: /BarangaySystem/public/residents/index.php');
    exit;
  }
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/residents/index.php');
  exit;
}

$status = strtolower((string) $resident['status']);
$statusHtml = $status === 'active'
  ? '<span class="status-pill status-received">Active</span>'
  : '<span class="status-pill status-cancelled">Inactive</span>';

$isPwd = ((int) ($resident['is_pwd'] ?? 0) === 1) || !empty($resident['pwd_type']);
$isSenior = (int) ($resident['age'] ?? 0) >= 60;
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Residents</div>
    <div class="topbar-right">
      <div class="topbar-user">
        <div class="avatar">A</div>
        <div class="topbar-user-info">
          <div class="topbar-user-name"><?= htmlspecialchars((string) ($topbarProfile['full_name'] ?? 'Admin')) ?></div>
          <div class="topbar-user-role"><?= htmlspecialchars((string) ($topbarProfile['role'] ?? 'Barangay Official')) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="card">
      <div class="table-header">
        <div>
          <div class="card-title">Resident Profile</div>
          <div class="card-sub">Read-only resident details</div>
        </div>
        <div class="table-actions">
          <a class="btn btn-light" href="/BarangaySystem/public/residents/index.php">Back to List</a>
          <a class="btn btn-primary" href="/BarangaySystem/public/residents/edit.php?id=<?= (int) $resident['id'] ?>">Edit Resident</a>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Full Name</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $resident['name']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Status</label>
          <div class="input" style="width:100%;min-width:0;background:#fff;"><?= $statusHtml ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Gender</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $resident['gender']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Age</label>
          <div class="input" style="width:100%;min-width:0;"><?= (int) $resident['age'] ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Civil Status</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $resident['civil_status']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Purok</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $resident['purok']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Address</label>
          <div class="input" style="width:100%;min-width:0;white-space:normal;"><?= htmlspecialchars((string) ($resident['address'] ?? '')) ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Identification</label>
          <div class="input" style="width:100%;min-width:0;background:#fff;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <?php if ($isSenior): ?>
            <span style="display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;background:#e6fbf6;color:#0f8a72;font-size:11px;font-weight:800;">Senior</span>
            <?php endif; ?>
            <?php if ($isPwd): ?>
            <span style="display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;background:#fff0dc;color:#b96500;font-size:11px;font-weight:800;">
              PWD<?= !empty($resident['pwd_type']) ? ': ' . htmlspecialchars((string) $resident['pwd_type']) : '' ?>
            </span>
            <?php endif; ?>
            <?php if (!$isSenior && !$isPwd): ?>
            <span class="muted">None</span>
            <?php endif; ?>
          </div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">PWD Type</label>
          <div class="input" style="width:100%;min-width:0;"><?= !empty($resident['pwd_type']) ? htmlspecialchars((string) $resident['pwd_type']) : 'None' ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Additional Classification</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) ($resident['additional_classification'] ?? 'None')) ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Date Added</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $resident['created_at']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Last Updated</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) ($resident['updated_at'] ?? $resident['created_at'])) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>
