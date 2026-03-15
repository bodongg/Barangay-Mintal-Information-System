<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "Edit Official";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/officials.php';

$positions = [
  'Barangay Captain',
  'Barangay Kagawad',
  'SK Chairperson',
  'Barangay Secretary',
  'Barangay Treasurer',
  'Barangay Tanod',
];

$committees = [
  'Peace and Order',
  'Health & Sanitation',
  'Education',
  'Environment',
  'Infrastructure',
  'Disaster Risk Reduction',
  'Youth & Sports',
  'Senior Citizens',
];

$workLocations = [
  'Main Barangay Hall',
  "Barangay Captain's Office",
  "Barangay Secretary's Office",
  "Barangay Treasurer's Office",
  'Lupon Office',
  'Barangay Tanod Outpost',
  'Health Center',
  'DRRM Office',
  'SK Office',
];

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /BarangaySystem/public/officials/index.php');
  exit;
}

$errors = [];
$form = [
  'full_name' => '',
  'position' => '',
  'committee' => '',
  'contact_number' => '',
  'work_location' => '',
  'status' => 'active',
  'term_start' => '',
  'term_end' => '',
  'official_photo' => '',
];

try {
  $stmt = db()->prepare("SELECT * FROM officials WHERE id = :id LIMIT 1");
  $stmt->execute(['id' => $id]);
  $row = $stmt->fetch();
  if (!$row) {
    header('Location: /BarangaySystem/public/officials/index.php');
    exit;
  }
  $form = [
    'full_name' => (string) $row['full_name'],
    'position' => (string) $row['position'],
    'committee' => (string) $row['committee'],
    'contact_number' => (string) $row['contact_number'],
    'work_location' => (string) $row['work_location'],
    'status' => (string) $row['status'],
    'term_start' => (string) $row['term_start'],
    'term_end' => (string) $row['term_end'],
    'official_photo' => (string) ($row['official_photo'] ?? ''),
  ];
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/officials/index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
  $form['position'] = trim((string) ($_POST['position'] ?? ''));
  $form['committee'] = trim((string) ($_POST['committee'] ?? ''));
  $form['contact_number'] = trim((string) ($_POST['contact_number'] ?? ''));
  $form['work_location'] = trim((string) ($_POST['work_location'] ?? ''));
  $form['status'] = trim((string) ($_POST['status'] ?? 'active'));
  $form['term_start'] = trim((string) ($_POST['term_start'] ?? ''));
  $form['term_end'] = trim((string) ($_POST['term_end'] ?? ''));
  $removePhoto = isset($_POST['remove_official_photo']) && $_POST['remove_official_photo'] === '1';

  if ($form['full_name'] === '') $errors[] = 'Full name is required.';
  if (!in_array($form['position'], $positions, true)) $errors[] = 'Position is required.';
  if (!in_array($form['committee'], $committees, true)) $errors[] = 'Committee is required.';
  if (!in_array($form['work_location'], $workLocations, true)) $errors[] = 'Work location is required.';
  if (!in_array($form['status'], ['active', 'inactive', 'vacant'], true)) $errors[] = 'Status is invalid.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['term_start'])) $errors[] = 'Term start is invalid.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['term_end'])) $errors[] = 'Term end is invalid.';

  $oldPhoto = $form['official_photo'];
  $uploadedPhoto = storeOfficialPhotoUpload($_FILES['official_photo'] ?? [], $errors);
  if ($uploadedPhoto !== null) {
    $form['official_photo'] = $uploadedPhoto;
  } elseif ($removePhoto) {
    $form['official_photo'] = '';
  }

  if (count($errors) === 0) {
    try {
      $stmt = db()->prepare("
        UPDATE officials
        SET full_name = :full_name,
            position = :position,
            committee = :committee,
            contact_number = :contact_number,
            work_location = :work_location,
            status = :status,
            term_start = :term_start,
            term_end = :term_end,
            official_photo = :official_photo
        WHERE id = :id
      ");
      $stmt->execute([
        'id' => $id,
        'full_name' => $form['full_name'],
        'position' => $form['position'],
        'committee' => $form['committee'],
        'contact_number' => $form['contact_number'],
        'work_location' => $form['work_location'],
        'status' => $form['status'],
        'term_start' => $form['term_start'],
        'term_end' => $form['term_end'],
        'official_photo' => $form['official_photo'],
      ]);
      if ($uploadedPhoto !== null && $oldPhoto !== '' && $oldPhoto !== $uploadedPhoto) {
        deleteOfficialPhotoFile($oldPhoto);
      }
      if ($removePhoto && $oldPhoto !== '' && $uploadedPhoto === null) {
        deleteOfficialPhotoFile($oldPhoto);
      }
      header('Location: /BarangaySystem/public/officials/index.php?msg=updated');
      exit;
    } catch (Throwable $e) {
      if ($uploadedPhoto !== null) {
        deleteOfficialPhotoFile($uploadedPhoto);
        $form['official_photo'] = $oldPhoto;
      }
      $errors[] = 'Failed to update official. Check database setup.';
    }
  }
}
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Officials</div>
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
          <div class="card-title">Edit Official</div>
          <div class="card-sub">Update official profile and term details</div>
        </div>
        <div class="table-actions">
          <a class="btn btn-light" href="/BarangaySystem/public/officials/index.php">Back to List</a>
        </div>
      </div>

      <?php if (count($errors) > 0): ?>
      <div style="margin-bottom:14px;padding:10px 12px;border-radius:10px;background:#ffe9e9;border:1px solid #ffd0d0;font-size:12px;font-weight:700;color:#a10000;">
        <?= htmlspecialchars(implode(' ', $errors)) ?>
      </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Full Name *</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="full_name" value="<?= htmlspecialchars($form['full_name']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Position *</label>
            <select class="input" style="width:100%;min-width:0;" name="position" required>
              <?php foreach ($positions as $position): ?>
              <option value="<?= htmlspecialchars($position) ?>" <?= $position === $form['position'] ? 'selected' : '' ?>><?= htmlspecialchars($position) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Committee *</label>
            <select class="input" style="width:100%;min-width:0;" name="committee" required>
              <?php foreach ($committees as $committee): ?>
              <option value="<?= htmlspecialchars($committee) ?>" <?= $committee === $form['committee'] ? 'selected' : '' ?>><?= htmlspecialchars($committee) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Contact Number</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="contact_number" value="<?= htmlspecialchars($form['contact_number']) ?>">
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Work Location *</label>
            <select class="input" style="width:100%;min-width:0;" name="work_location" required>
              <?php foreach ($workLocations as $location): ?>
              <option value="<?= htmlspecialchars($location) ?>" <?= $location === $form['work_location'] ? 'selected' : '' ?>><?= htmlspecialchars($location) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Status</label>
            <select class="input" style="width:100%;min-width:0;" name="status">
              <option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= $form['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
              <option value="vacant" <?= $form['status'] === 'vacant' ? 'selected' : '' ?>>Vacant</option>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Term Start *</label>
            <input class="input" style="width:100%;min-width:0;" type="date" name="term_start" value="<?= htmlspecialchars($form['term_start']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Term End *</label>
            <input class="input" style="width:100%;min-width:0;" type="date" name="term_end" value="<?= htmlspecialchars($form['term_end']) ?>" required>
          </div>
          <div style="grid-column:1 / -1;">
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Official Photo</label>
            <?php if ($form['official_photo'] !== ''): ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
              <img src="<?= htmlspecialchars($form['official_photo']) ?>" alt="<?= htmlspecialchars($form['full_name']) ?>" style="width:72px;height:72px;border-radius:16px;object-fit:cover;border:1px solid var(--border);background:#f4f6fb;">
              <label style="display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;color:var(--text-soft);">
                <input type="checkbox" name="remove_official_photo" value="1">
                Remove current photo
              </label>
            </div>
            <?php endif; ?>
            <input class="input" style="width:100%;min-width:0;padding:11px 14px;" type="file" name="official_photo" accept="image/jpeg,image/png,image/webp,image/gif,image/*" capture="user">
            <div class="muted" style="margin-top:6px;">Choose an image from your files or use your camera on supported devices.</div>
          </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
          <a class="btn btn-light" href="/BarangaySystem/public/officials/index.php">Cancel</a>
          <button class="btn btn-primary" type="submit">Update Official</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

