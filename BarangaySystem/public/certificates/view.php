<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "View Certificate Request";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /BarangaySystem/public/certificates/index.php');
  exit;
}

try {
  $stmt = db()->prepare("
    SELECT
      id,
      resident_name,
      request_date,
      certificate_type,
      status,
      mobile_no,
      purok,
      address,
      created_at,
      updated_at
    FROM certificates
    WHERE id = :id
    LIMIT 1
  ");
  $stmt->execute(['id' => $id]);
  $certificate = $stmt->fetch();
  if (!$certificate) {
    header('Location: /BarangaySystem/public/certificates/index.php');
    exit;
  }
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/certificates/index.php');
  exit;
}

$status = strtolower(trim((string) $certificate['status']));
if ($status === 'received') {
  $statusHtml = '<span class="status-pill status-received">Received</span>';
} elseif ($status === 'cancelled') {
  $statusHtml = '<span class="status-pill status-cancelled">Cancelled</span>';
} else {
  $statusHtml = '<span class="status-pill status-ongoing">On Going</span>';
}
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Certificates</div>
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
          <div class="card-title">Certificate Request Details</div>
          <div class="card-sub">Read-only certificate request information</div>
        </div>
        <div class="table-actions">
          <a class="btn btn-light" href="/BarangaySystem/public/certificates/index.php">Back to List</a>
          <a class="btn btn-primary" href="/BarangaySystem/public/certificates/edit.php?id=<?= (int) $certificate['id'] ?>">Edit Request</a>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Name</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $certificate['resident_name']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Status</label>
          <div class="input" style="width:100%;min-width:0;background:#fff;"><?= $statusHtml ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Request Date</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $certificate['request_date']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Certificate Type</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $certificate['certificate_type']) ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Mobile No</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $certificate['mobile_no']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Purok</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $certificate['purok']) ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Address</label>
          <div class="input" style="width:100%;min-width:0;min-height:44px;white-space:normal;"><?= htmlspecialchars((string) $certificate['address']) ?></div>
        </div>

        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Date Added</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) $certificate['created_at']) ?></div>
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Last Updated</label>
          <div class="input" style="width:100%;min-width:0;"><?= htmlspecialchars((string) ($certificate['updated_at'] ?? $certificate['created_at'])) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

