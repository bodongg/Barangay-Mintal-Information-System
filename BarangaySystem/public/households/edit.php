<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "Edit Household";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /BarangaySystem/public/households/index.php');
  exit;
}

$errors = [];
$heads = [];
$form = [
  'household_code' => '',
  'head_resident_id' => '',
  'purok' => '',
  'address' => '',
  'housing_type' => '',
  'status' => 'active',
];

try {
  $heads = db()->query("SELECT id, name FROM residents ORDER BY name ASC")->fetchAll();

  $stmt = db()->prepare("SELECT * FROM households WHERE id = :id LIMIT 1");
  $stmt->execute(['id' => $id]);
  $row = $stmt->fetch();
  if (!$row) {
    header('Location: /BarangaySystem/public/households/index.php');
    exit;
  }
  $form = [
    'household_code' => (string) $row['household_code'],
    'head_resident_id' => $row['head_resident_id'] !== null ? (string) $row['head_resident_id'] : '',
    'purok' => (string) $row['purok'],
    'address' => (string) $row['address'],
    'housing_type' => (string) $row['housing_type'],
    'status' => (string) $row['status'],
  ];
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/households/index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form['household_code'] = trim((string) ($_POST['household_code'] ?? ''));
  $form['head_resident_id'] = trim((string) ($_POST['head_resident_id'] ?? ''));
  $form['purok'] = trim((string) ($_POST['purok'] ?? ''));
  $form['address'] = trim((string) ($_POST['address'] ?? ''));
  $form['housing_type'] = trim((string) ($_POST['housing_type'] ?? ''));
  $form['status'] = trim((string) ($_POST['status'] ?? 'active'));

  if ($form['household_code'] === '') $errors[] = 'Household code is required.';
  if ($form['head_resident_id'] !== '' && !ctype_digit($form['head_resident_id'])) $errors[] = 'Head resident is invalid.';
  if ($form['purok'] === '') $errors[] = 'Purok is required.';
  if ($form['address'] === '') $errors[] = 'Address is required.';
  if ($form['housing_type'] === '') $errors[] = 'Housing type is required.';
  if (!in_array($form['status'], ['active', 'inactive'], true)) $errors[] = 'Status is invalid.';

  if (count($errors) === 0) {
    try {
      $stmt = db()->prepare("
        UPDATE households
        SET household_code = :household_code,
            head_resident_id = :head_resident_id,
            purok = :purok,
            address = :address,
            housing_type = :housing_type,
            status = :status
        WHERE id = :id
      ");
      $stmt->execute([
        'id' => $id,
        'household_code' => $form['household_code'],
        'head_resident_id' => $form['head_resident_id'] === '' ? null : (int) $form['head_resident_id'],
        'purok' => $form['purok'],
        'address' => $form['address'],
        'housing_type' => $form['housing_type'],
        'status' => $form['status'],
      ]);
      header('Location: /BarangaySystem/public/households/index.php?msg=updated');
      exit;
    } catch (Throwable $e) {
      $errors[] = 'Failed to update household. Check database setup.';
    }
  }
}
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
          <div class="card-title">Edit Household</div>
          <div class="card-sub">Update household profile</div>
        </div>
        <div class="table-actions">
          <a class="btn btn-light" href="/BarangaySystem/public/households/index.php">Back to List</a>
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
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Household Code *</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="household_code" value="<?= htmlspecialchars($form['household_code']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Head Resident</label>
            <select class="input" style="width:100%;min-width:0;" name="head_resident_id">
              <option value="">Select resident</option>
              <?php foreach ($heads as $h): ?>
              <option value="<?= (int) $h['id'] ?>" <?= $form['head_resident_id'] === (string) $h['id'] ? 'selected' : '' ?>><?= htmlspecialchars($h['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Purok *</label>
            <select class="input" style="width:100%;min-width:0;" name="purok" required>
              <?php foreach (['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6'] as $p): ?>
              <option value="<?= htmlspecialchars($p) ?>" <?= $form['purok'] === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Housing Type *</label>
            <select class="input" style="width:100%;min-width:0;" name="housing_type" required>
              <?php foreach (['Owned', 'Rented', 'Shared', 'Temporary Shelter'] as $t): ?>
              <option value="<?= htmlspecialchars($t) ?>" <?= $form['housing_type'] === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Address *</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="address" value="<?= htmlspecialchars($form['address']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Status *</label>
            <select class="input" style="width:100%;min-width:0;" name="status" required>
              <option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= $form['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
          <a class="btn btn-light" href="/BarangaySystem/public/households/index.php">Cancel</a>
          <button class="btn btn-primary" type="submit">Update Household</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

