<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "View Household";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /BarangaySystem/public/households/index.php');
  exit;
}

try {
  $stmt = db()->prepare("
    SELECT
      h.id,
      h.household_code,
      h.head_resident_id,
      COALESCE(r.name, 'N/A') AS head_name,
      h.purok,
      h.address,
      h.housing_type,
      h.status,
      h.created_at,
      h.updated_at
    FROM households h
    LEFT JOIN residents r ON r.id = h.head_resident_id
    WHERE h.id = :id
    LIMIT 1
  ");
  $stmt->execute(['id' => $id]);
  $household = $stmt->fetch();
  if (!$household) {
    header('Location: /BarangaySystem/public/households/index.php');
    exit;
  }
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/households/index.php');
  exit;
}

$status = strtolower((string) $household['status']);
$statusHtml = $status === 'active'
  ? '<span class="status-pill status-received">Active</span>'
  : '<span class="status-pill status-cancelled">Inactive</span>';
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Households</div>
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
          <div class="card-title">Household Profile</div>
          <div class="card-sub">Read-only household details</div>
        </div>
        <div class="table-actions">
          <a class="btn btn-light" href="/BarangaySystem/public/households/index.php">Back to List</a>
          <a class="btn btn-primary" href="/BarangaySystem/public/households/edit.php?id=<?= (int) $household['id'] ?>">Edit Household</a>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Household Code</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $household['household_code']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Status</label>
          <div class="input" style="width:100%;min-width:0;background:#fff;"><?= $statusHtml ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Head Resident</label>
          <div class="input" style="width:100%;min-width:0;background:#fff;">
            <?php if (!empty($household['head_resident_id'])): ?>
            <a href="/BarangaySystem/public/residents/view.php?id=<?= (int) $household['head_resident_id'] ?>" style="font-weight:800;color:var(--primary);">
              <?= htmlspecialchars((string) $household['head_name']) ?>
            </a>
            <?php else: ?>
            N/A
            <?php endif; ?>
          </div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Purok</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $household['purok']) ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Housing Type</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $household['housing_type']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Address</label>
          <div class="input" style="width:100%;min-width:0;min-height:44px;white-space:normal;"><?= htmlspecialchars((string) $household['address']) ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Date Added</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $household['created_at']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Last Updated</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) ($household['updated_at'] ?? $household['created_at'])) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

