<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "Edit Certificate Request";
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

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /BarangaySystem/public/certificates/index.php');
  exit;
}

$errors = [];
$form = [
  'resident_name' => '',
  'request_date' => '',
  'certificate_type' => '',
  'status' => 'ongoing',
  'mobile_no' => '',
  'purok' => '',
  'address' => '',
];

try {
  $stmt = db()->prepare("SELECT * FROM certificates WHERE id = :id LIMIT 1");
  $stmt->execute(['id' => $id]);
  $row = $stmt->fetch();
  if (!$row) {
    header('Location: /BarangaySystem/public/certificates/index.php');
    exit;
  }
  $form = [
    'resident_name' => (string) $row['resident_name'],
    'request_date' => (string) $row['request_date'],
    'certificate_type' => (string) $row['certificate_type'],
    'status' => (string) $row['status'],
    'mobile_no' => (string) $row['mobile_no'],
    'purok' => (string) $row['purok'],
    'address' => (string) $row['address'],
  ];
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/certificates/index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form['resident_name'] = trim((string) ($_POST['resident_name'] ?? ''));
  $form['request_date'] = trim((string) ($_POST['request_date'] ?? ''));
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
        UPDATE certificates
        SET resident_name = :resident_name,
            request_date = :request_date,
            certificate_type = :certificate_type,
            status = :status,
            mobile_no = :mobile_no,
            purok = :purok,
            address = :address
        WHERE id = :id
      ");
      $stmt->execute([
        'id' => $id,
        'resident_name' => $form['resident_name'],
        'request_date' => $form['request_date'],
        'certificate_type' => $form['certificate_type'],
        'status' => $form['status'],
        'mobile_no' => $form['mobile_no'],
        'purok' => $form['purok'],
        'address' => $form['address'],
      ]);
      header('Location: /BarangaySystem/public/certificates/index.php?msg=updated');
      exit;
    } catch (Throwable $e) {
      $errors[] = 'Failed to update certificate request. Check database setup.';
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
          <div class="card-title">Edit Certificate Request</div>
          <div class="card-sub">Update request details and status</div>
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
          <button class="btn btn-primary" type="submit">Update Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

