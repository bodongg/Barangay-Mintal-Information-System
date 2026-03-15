<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "Add Official";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
  $form['position'] = trim((string) ($_POST['position'] ?? ''));
  $form['committee'] = trim((string) ($_POST['committee'] ?? ''));
  $form['contact_number'] = trim((string) ($_POST['contact_number'] ?? ''));
  $form['work_location'] = trim((string) ($_POST['work_location'] ?? ''));
  $form['status'] = trim((string) ($_POST['status'] ?? 'active'));
  $form['term_start'] = trim((string) ($_POST['term_start'] ?? ''));
  $form['term_end'] = trim((string) ($_POST['term_end'] ?? ''));

  if ($form['full_name'] === '') $errors[] = 'Full name is required.';
  if (!in_array($form['position'], $positions, true)) $errors[] = 'Position is required.';
  if (!in_array($form['committee'], $committees, true)) $errors[] = 'Committee is required.';
  if (!in_array($form['work_location'], $workLocations, true)) $errors[] = 'Work location is required.';
  if (!in_array($form['status'], ['active', 'inactive', 'vacant'], true)) $errors[] = 'Status is invalid.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['term_start'])) $errors[] = 'Term start is invalid.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['term_end'])) $errors[] = 'Term end is invalid.';

  $uploadedPhoto = storeOfficialPhotoUpload($_FILES['official_photo'] ?? [], $errors);
  if ($uploadedPhoto !== null) {
    $form['official_photo'] = $uploadedPhoto;
  }

  if (count($errors) === 0) {
    try {
      $stmt = db()->prepare("
        INSERT INTO officials (
          full_name, position, committee, contact_number, work_location, status, term_start, term_end, official_photo
        ) VALUES (
          :full_name, :position, :committee, :contact_number, :work_location, :status, :term_start, :term_end, :official_photo
        )
      ");
      $stmt->execute($form);
      header('Location: /BarangaySystem/public/officials/index.php?msg=saved');
      exit;
    } catch (Throwable $e) {
      if ($form['official_photo'] !== '') {
        deleteOfficialPhotoFile($form['official_photo']);
        $form['official_photo'] = '';
      }
      $errors[] = 'Failed to save official. Check database setup.';
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
          <div class="card-title">Add Official</div>
          <div class="card-sub">Create a new barangay official profile</div>
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
            <input class="input" style="width:100%;min-width:0;" type="text" name="full_name" value="<?= htmlspecialchars($form['full_name']) ?>" placeholder="e.g. Hon. Juan Dela Cruz" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Position *</label>
            <select class="input" style="width:100%;min-width:0;" name="position" required>
              <option value="">Select position</option>
              <?php foreach ($positions as $position): ?>
              <option value="<?= htmlspecialchars($position) ?>" <?= $form['position'] === $position ? 'selected' : '' ?>><?= htmlspecialchars($position) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Committee *</label>
            <select class="input" style="width:100%;min-width:0;" name="committee" required>
              <option value="">Select committee</option>
              <?php foreach ($committees as $committee): ?>
              <option value="<?= htmlspecialchars($committee) ?>" <?= $form['committee'] === $committee ? 'selected' : '' ?>><?= htmlspecialchars($committee) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Contact Number</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="contact_number" value="<?= htmlspecialchars($form['contact_number']) ?>" placeholder="09xx xxx xxxx">
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Work Location *</label>
            <select class="input" style="width:100%;min-width:0;" name="work_location" required>
              <option value="">Select work location</option>
              <?php foreach ($workLocations as $location): ?>
              <option value="<?= htmlspecialchars($location) ?>" <?= $form['work_location'] === $location ? 'selected' : '' ?>><?= htmlspecialchars($location) ?></option>
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
            <input class="input" style="width:100%;min-width:0;padding:11px 14px;" type="file" name="official_photo" accept="image/jpeg,image/png,image/webp,image/gif,image/*" capture="user">
            <div class="muted" style="margin-top:6px;">Choose an image from your files or use your camera on supported devices.</div>
          </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
          <a class="btn btn-light" href="/BarangaySystem/public/officials/index.php">Cancel</a>
          <button class="btn btn-primary" type="submit">Save Official</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

