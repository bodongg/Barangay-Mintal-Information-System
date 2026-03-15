<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "Edit Resident";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /BarangaySystem/public/residents/index.php');
  exit;
}

$errors = [];
$form = [
  'name' => '',
  'address' => '',
  'gender' => '',
  'age' => '',
  'civil_status' => 'Single',
  'purok' => '',
  'status' => 'active',
  'pwd_type' => '',
  'additional_classification' => 'None',
];

$pwdTypes = [
  'psychosocial',
  'learning',
  'mental',
  'visual',
  'orthopedic',
  'physical',
  'speech/language impairment',
  'deaf/hard of hearing',
  'chronic illness',
  'cancer',
  'rare diseases',
];

$classificationOptions = [
  'None',
  'Solo Parent',
  '4Ps Beneficiary',
  'Indigenous People (IP)',
  'Indigent/ Low Income',
  'Overseas Filipino Worker (OFW)',
];

try {
  $stmt = db()->prepare("SELECT * FROM residents WHERE id = :id LIMIT 1");
  $stmt->execute(['id' => $id]);
  $resident = $stmt->fetch();
  if (!$resident) {
    header('Location: /BarangaySystem/public/residents/index.php');
    exit;
  }
  $form = [
    'name' => (string) $resident['name'],
    'address' => (string) ($resident['address'] ?? ''),
    'gender' => (string) $resident['gender'],
    'age' => (string) $resident['age'],
    'civil_status' => (string) $resident['civil_status'],
    'purok' => (string) $resident['purok'],
    'status' => (string) $resident['status'],
    'pwd_type' => (string) ($resident['pwd_type'] ?? ''),
    'additional_classification' => (string) ($resident['additional_classification'] ?? 'None'),
  ];
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/residents/index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form['name'] = trim((string) ($_POST['name'] ?? ''));
  $form['address'] = trim((string) ($_POST['address'] ?? ''));
  $form['gender'] = trim((string) ($_POST['gender'] ?? ''));
  $form['age'] = trim((string) ($_POST['age'] ?? ''));
  $form['civil_status'] = trim((string) ($_POST['civil_status'] ?? 'Single'));
  $form['purok'] = trim((string) ($_POST['purok'] ?? ''));
  $form['status'] = trim((string) ($_POST['status'] ?? 'active'));
  $form['pwd_type'] = trim((string) ($_POST['pwd_type'] ?? ''));
  $form['additional_classification'] = trim((string) ($_POST['additional_classification'] ?? 'None'));

  if ($form['name'] === '') $errors[] = 'Name is required.';
  if ($form['address'] === '') $errors[] = 'Address is required.';
  if (!in_array($form['gender'], ['Male', 'Female', 'Prefer not to say'], true)) $errors[] = 'Gender is required.';
  if (!ctype_digit($form['age']) || (int) $form['age'] < 0 || (int) $form['age'] > 130) $errors[] = 'Age must be a valid number.';
  if ($form['purok'] === '') $errors[] = 'Purok is required.';
  if (!in_array($form['status'], ['active', 'inactive'], true)) $errors[] = 'Status is invalid.';
  if (!in_array($form['pwd_type'], array_merge([''], $pwdTypes), true)) $errors[] = 'PWD type is invalid.';
  if (!in_array($form['additional_classification'], $classificationOptions, true)) $errors[] = 'Additional classification is invalid.';

  if (count($errors) === 0) {
    try {
      $stmt = db()->prepare("
        UPDATE residents
        SET name = :name,
            address = :address,
            purok = :purok,
            gender = :gender,
            age = :age,
            civil_status = :civil_status,
            status = :status,
            is_pwd = :is_pwd,
            pwd_type = :pwd_type,
            additional_classification = :additional_classification
        WHERE id = :id
      ");
      $stmt->execute([
        'id' => $id,
        'name' => $form['name'],
        'address' => $form['address'],
        'purok' => $form['purok'],
        'gender' => $form['gender'],
        'age' => (int) $form['age'],
        'civil_status' => $form['civil_status'],
        'status' => $form['status'],
        'is_pwd' => $form['pwd_type'] === '' ? 0 : 1,
        'pwd_type' => $form['pwd_type'] === '' ? null : $form['pwd_type'],
        'additional_classification' => $form['additional_classification'],
      ]);
      header('Location: /BarangaySystem/public/residents/index.php?msg=updated');
      exit;
    } catch (Throwable $e) {
      $errors[] = 'Failed to update resident. Check database connection.';
    }
  }
}
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
          <div class="card-title">Edit Resident</div>
          <div class="card-sub">Update resident information</div>
        </div>
        <div class="table-actions">
          <a class="btn btn-light" href="/BarangaySystem/public/residents/index.php">Back to List</a>
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
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Full Name *</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="name" value="<?= htmlspecialchars($form['name']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Address *</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="address" value="<?= htmlspecialchars($form['address']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Gender *</label>
            <select class="input" style="width:100%;min-width:0;" name="gender" required>
              <option value="Male" <?= $form['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
              <option value="Female" <?= $form['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
              <option value="Prefer not to say" <?= $form['gender'] === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Age *</label>
            <input class="input" style="width:100%;min-width:0;" type="number" min="0" max="130" name="age" value="<?= htmlspecialchars($form['age']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Civil Status</label>
            <select class="input" style="width:100%;min-width:0;" name="civil_status">
              <?php foreach (['Single', 'Married', 'Widowed', 'Separated'] as $civil): ?>
              <option value="<?= htmlspecialchars($civil) ?>" <?= $form['civil_status'] === $civil ? 'selected' : '' ?>><?= htmlspecialchars($civil) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Purok *</label>
            <select class="input" style="width:100%;min-width:0;" name="purok" required>
              <?php foreach (['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6'] as $purok): ?>
              <option value="<?= htmlspecialchars($purok) ?>" <?= $form['purok'] === $purok ? 'selected' : '' ?>><?= htmlspecialchars($purok) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Status *</label>
            <select class="input" style="width:100%;min-width:0;" name="status" required>
              <option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= $form['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">PWD Type</label>
            <select class="input" style="width:100%;min-width:0;" name="pwd_type">
              <option value="">None</option>
              <?php foreach ($pwdTypes as $pwdType): ?>
              <option value="<?= htmlspecialchars($pwdType) ?>" <?= $form['pwd_type'] === $pwdType ? 'selected' : '' ?>><?= htmlspecialchars(ucwords($pwdType)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Additional Classification</label>
            <select class="input" style="width:100%;min-width:0;" name="additional_classification">
              <?php foreach ($classificationOptions as $classification): ?>
              <option value="<?= htmlspecialchars($classification) ?>" <?= $form['additional_classification'] === $classification ? 'selected' : '' ?>><?= htmlspecialchars($classification) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
          <a class="btn btn-light" href="/BarangaySystem/public/residents/index.php">Cancel</a>
          <button class="btn btn-primary" type="submit">Update Resident</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

