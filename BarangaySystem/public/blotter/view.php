<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "View Blotter Report";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /BarangaySystem/public/blotter/index.php');
  exit;
}

try {
  $stmt = db()->prepare("
    SELECT
      id,
      name,
      blotter_type,
      contact_number,
      address,
      incident_details,
      date,
      purok,
      status,
      created_at,
      updated_at
    FROM blotter_records
    WHERE id = :id
    LIMIT 1
  ");
  $stmt->execute(['id' => $id]);
  $record = $stmt->fetch();
  if (!$record) {
    header('Location: /BarangaySystem/public/blotter/index.php');
    exit;
  }
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/blotter/index.php');
  exit;
}

$status = strtolower((string) $record['status']);
if ($status === 'settled') {
  $statusHtml = '<span class="status-pill status-received">Settled</span>';
} elseif ($status === 'received') {
  $statusHtml = '<span style="display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:999px;font-size:12px;font-weight:900;letter-spacing:.2px;background:#e3f2fd;color:#1565c0;"><span style="width:10px;height:10px;border-radius:50%;background:#2196f3;display:inline-block;"></span>Received</span>';
} elseif ($status === 'cancelled') {
  $statusHtml = '<span class="status-pill status-cancelled">Cancelled</span>';
} else {
  $statusHtml = '<span class="status-pill status-ongoing">On Going</span>';
}
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Blotter</div>
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
          <div class="card-title">Blotter Report Details</div>
          <div class="card-sub">Read-only incident information</div>
        </div>
        <div class="table-actions">
          <a class="btn btn-light" href="/BarangaySystem/public/blotter/index.php">Back to List</a>
          <a class="btn btn-primary" href="/BarangaySystem/public/blotter/update.php?id=<?= (int) $record['id'] ?>">Edit Report</a>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Complainant Name</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $record['name']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Status</label>
          <div class="input" style="width:100%;min-width:0;background:#fff;"><?= $statusHtml ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Blotter Type</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $record['blotter_type']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Incident Date</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $record['date']) ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Contact Number</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $record['contact_number']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Purok</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $record['purok']) ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Address</label>
          <div class="input" style="width:100%;min-width:0;min-height:44px;white-space:normal;"><?= htmlspecialchars((string) $record['address']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Incident Details</label>
          <div class="input" style="width:100%;min-width:0;min-height:44px;white-space:normal;"><?= htmlspecialchars((string) ($record['incident_details'] ?? '')) ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Date Added</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $record['created_at']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Last Updated</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) ($record['updated_at'] ?? $record['created_at'])) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

