<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "New Certificate Request";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$certificateTypes = [
  'Barangay Clearance',
  'Certificate of Residency',
  'Certificate of Indigency',
  'Certificate of Good Moral Character',
  'Certificate of Cohabitation',
  'Certificate of No Objection',
  'Certificate of Solo Parent',
  'Certificate of Guardianship',
  'Certificate of Non-Residency',
  'Certificate of Unemployment',
  'Barangay Business Clearance',
  'Construction Clearance',
  'Burial Assistance Certificate',
  'Scholarship Requirement Certificate',
];

$errors = [];
$form = [
  'resident_name' => '',
  'request_date' => date('Y-m-d'),
  'certificate_type' => '',
  'status' => 'ongoing',
  'mobile_no' => '',
  'purok' => '',
  'address' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form['resident_name'] = trim((string) ($_POST['resident_name'] ?? ''));
  $form['request_date'] = trim((string) ($_POST['request_date'] ?? date('Y-m-d')));
  $form['certificate_type'] = trim((string) ($_POST['certificate_type'] ?? ''));
  $form['status'] = trim((string) ($_POST['status'] ?? 'ongoing'));
  $form['mobile_no'] = trim((string) ($_POST['mobile_no'] ?? ''));
  $form['purok'] = trim((string) ($_POST['purok'] ?? ''));
  $form['address'] = trim((string) ($_POST['address'] ?? ''));

  if ($form['resident_name'] === '') $errors[] = 'Name is required.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['request_date'])) $errors[] = 'Request date is invalid.';
  if (!in_array($form['certificate_type'], $certificateTypes, true)) $errors[] = 'Certificate type is required.';
  if (!in_array($form['status'], ['ongoing', 'received', 'cancelled'], true)) $errors[] = 'Status is invalid.';
  if ($form['purok'] === '') $errors[] = 'Purok is required.';
  if ($form['address'] === '') $errors[] = 'Address is required.';

  if (count($errors) === 0) {
    try {
      $stmt = db()->prepare("
        INSERT INTO certificates (resident_name, request_date, certificate_type, status, mobile_no, purok, address)
        VALUES (:resident_name, :request_date, :certificate_type, :status, :mobile_no, :purok, :address)
      ");
      $stmt->execute($form);
      header('Location: /BarangaySystem/public/certificates/index.php?msg=saved');
      exit;
    } catch (Throwable $e) {
      $errors[] = 'Failed to save certificate request. Check database setup.';
    }
  }
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
          <div class="card-title">New Certificate Request</div>
          <div class="card-sub">Create and track certificate issuance requests</div>
        </div>
        <div class="table-actions">
          <a class="btn btn-light" href="/BarangaySystem/public/certificates/index.php">Back to List</a>
        </div>
      </div>

      <?php if (count($errors) > 0): ?>
      <div style="margin-bottom:14px;padding:10px 12px;border-radius:10px;background:#ffe9e9;border:1px solid #ffd0d0;color:#a10000;font-size:12px;font-weight:800;">
        <?= htmlspecialchars(implode(' ', $errors)) ?>
      </div>
      <?php endif; ?>

      <form method="post">
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Name</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="resident_name" value="<?= htmlspecialchars($form['resident_name']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Request Date</label>
            <input class="input" style="width:100%;min-width:0;" type="date" name="request_date" value="<?= htmlspecialchars($form['request_date']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Certificate Type</label>
            <select class="input" style="width:100%;min-width:0;" name="certificate_type" required>
              <option value="">Select certificate type</option>
              <?php foreach ($certificateTypes as $type): ?>
              <option value="<?= htmlspecialchars($type) ?>" <?= $form['certificate_type'] === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Status</label>
            <select class="input" style="width:100%;min-width:0;" name="status" required>
              <option value="ongoing" <?= $form['status'] === 'ongoing' ? 'selected' : '' ?>>On Going</option>
              <option value="received" <?= $form['status'] === 'received' ? 'selected' : '' ?>>Received</option>
              <option value="cancelled" <?= $form['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Mobile No</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="mobile_no" value="<?= htmlspecialchars($form['mobile_no']) ?>">
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Purok</label>
            <select class="input" style="width:100%;min-width:0;" name="purok" required>
              <option value="">Select purok</option>
              <?php foreach (['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6'] as $p): ?>
              <option value="<?= htmlspecialchars($p) ?>" <?= $form['purok'] === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Address</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="address" value="<?= htmlspecialchars($form['address']) ?>" required>
          </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
          <a class="btn btn-light" href="/BarangaySystem/public/certificates/index.php">Cancel</a>
          <button class="btn btn-primary" type="submit">Save Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

