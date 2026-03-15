<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "New Blotter Report";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$blotterTypes = [
  'Family conflict',
  'Neighbor dispute',
  'Property boundary issue',
  'Verbal argument',
  'Noise complaint',
  'Public intoxication',
  'Alarm & scandal',
  'Theft',
  'Physical injury',
  'Threat',
  'Harassment',
  'Land conflict',
  'Child custody issue',
  'Lost & found',
];

$errors = [];
$form = [
  'name' => '',
  'blotter_type' => '',
  'contact_number' => '',
  'address' => '',
  'incident_details' => '',
  'date' => date('Y-m-d'),
  'purok' => '',
  'status' => 'ongoing',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form['name'] = trim((string) ($_POST['name'] ?? ''));
  $form['blotter_type'] = trim((string) ($_POST['blotter_type'] ?? ''));
  $form['contact_number'] = trim((string) ($_POST['contact_number'] ?? ''));
  $form['address'] = trim((string) ($_POST['address'] ?? ''));
  $form['incident_details'] = trim((string) ($_POST['incident_details'] ?? ''));
  $form['date'] = trim((string) ($_POST['date'] ?? date('Y-m-d')));
  $form['purok'] = trim((string) ($_POST['purok'] ?? ''));
  $form['status'] = trim((string) ($_POST['status'] ?? 'ongoing'));

  if ($form['name'] === '') $errors[] = 'Name is required.';
  if (!in_array($form['blotter_type'], $blotterTypes, true)) $errors[] = 'Blotter type is required.';
  if ($form['address'] === '') $errors[] = 'Address is required.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['date'])) $errors[] = 'Date is invalid.';
  if ($form['purok'] === '') $errors[] = 'Purok is required.';
  if (!in_array($form['status'], ['ongoing', 'received', 'cancelled', 'settled'], true)) $errors[] = 'Status is invalid.';

  if (count($errors) === 0) {
    try {
      $stmt = db()->prepare("
        INSERT INTO blotter_records (name, blotter_type, contact_number, address, incident_details, date, purok, status)
        VALUES (:name, :blotter_type, :contact_number, :address, :incident_details, :date, :purok, :status)
      ");
      $stmt->execute($form);
      header('Location: /BarangaySystem/public/blotter/index.php?msg=saved');
      exit;
    } catch (Throwable $e) {
      $errors[] = 'Failed to save blotter report. Check database setup.';
    }
  }
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
          <div class="card-title">New Blotter Report</div>
          <div class="card-sub">Create and track blotter incidents</div>
        </div>
        <div class="table-actions">
          <a class="btn btn-light" href="/BarangaySystem/public/blotter/index.php">Back to List</a>
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
            <input class="input" style="width:100%;min-width:0;" type="text" name="name" value="<?= htmlspecialchars($form['name']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Blotter Type</label>
            <select class="input" style="width:100%;min-width:0;" name="blotter_type" required>
              <option value="">Select blotter type</option>
              <?php foreach ($blotterTypes as $type): ?>
              <option value="<?= htmlspecialchars($type) ?>" <?= $form['blotter_type'] === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Contact Number</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="contact_number" value="<?= htmlspecialchars($form['contact_number']) ?>">
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Date</label>
            <input class="input" style="width:100%;min-width:0;" type="date" name="date" value="<?= htmlspecialchars($form['date']) ?>" required>
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
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Status</label>
            <select class="input" style="width:100%;min-width:0;" name="status" required>
              <option value="ongoing" <?= $form['status'] === 'ongoing' ? 'selected' : '' ?>>On Going</option>
              <option value="settled" <?= $form['status'] === 'settled' ? 'selected' : '' ?>>Settled</option>
              <option value="received" <?= $form['status'] === 'received' ? 'selected' : '' ?>>Received</option>
              <option value="cancelled" <?= $form['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Address</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="address" value="<?= htmlspecialchars($form['address']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Incident Details</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="incident_details" value="<?= htmlspecialchars($form['incident_details']) ?>" placeholder="Describe what happened">
          </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
          <a class="btn btn-light" href="/BarangaySystem/public/blotter/index.php">Cancel</a>
          <button class="btn btn-primary" type="submit">Save Report</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

