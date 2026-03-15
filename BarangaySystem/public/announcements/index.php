<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "Announcements";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$categories = ['Fiesta', 'Seminar', 'Health', 'Meeting', 'Advisory', 'Event'];
$statusOptions = ['active', 'inactive'];
$errorMessage = '';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$form = [
  'category' => 'Meeting',
  'title' => '',
  'description' => '',
  'event_date' => date('Y-m-d'),
  'location' => '',
  'status' => 'active',
];

try {
  db()->exec("
    CREATE TABLE IF NOT EXISTS announcements (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      category VARCHAR(50) NOT NULL DEFAULT 'Meeting',
      title VARCHAR(180) NOT NULL,
      description TEXT NOT NULL,
      event_date DATE NOT NULL,
      location VARCHAR(120) NOT NULL DEFAULT '',
      status VARCHAR(20) NOT NULL DEFAULT 'active',
      is_archived TINYINT(1) NOT NULL DEFAULT 0,
      created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
  ");
} catch (Throwable $e) {
  $errorMessage = 'Announcements table is not ready. Run database/setup_announcements.sql.';
}

if (isset($_GET['delete_id'])) {
  try {
    $deleteId = (int) $_GET['delete_id'];
    if ($deleteId > 0) {
      $stmt = db()->prepare("DELETE FROM announcements WHERE id = :id");
      $stmt->execute(['id' => $deleteId]);
    }
    header('Location: /BarangaySystem/public/announcements/index.php?msg=deleted');
    exit;
  } catch (Throwable $e) {
    $errorMessage = 'Failed to delete announcement.';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $saveId = (int) ($_POST['id'] ?? 0);
  $form['category'] = trim((string) ($_POST['category'] ?? 'Meeting'));
  $form['title'] = trim((string) ($_POST['title'] ?? ''));
  $form['description'] = trim((string) ($_POST['description'] ?? ''));
  $form['event_date'] = trim((string) ($_POST['event_date'] ?? ''));
  $form['location'] = trim((string) ($_POST['location'] ?? ''));
  $form['status'] = strtolower(trim((string) ($_POST['status'] ?? 'active')));

  $errors = [];
  if (!in_array($form['category'], $categories, true)) $errors[] = 'Category is invalid.';
  if ($form['title'] === '') $errors[] = 'Title is required.';
  if ($form['description'] === '') $errors[] = 'Description is required.';
  if ($form['event_date'] === '') $errors[] = 'Event date is required.';
  if (!in_array($form['status'], $statusOptions, true)) $errors[] = 'Status is invalid.';

  if (count($errors) === 0) {
    try {
      if ($saveId > 0) {
        $stmt = db()->prepare("
          UPDATE announcements
          SET category = :category,
              title = :title,
              description = :description,
              event_date = :event_date,
              location = :location,
              status = :status
          WHERE id = :id
        ");
        $stmt->execute([
          'id' => $saveId,
          'category' => $form['category'],
          'title' => $form['title'],
          'description' => $form['description'],
          'event_date' => $form['event_date'],
          'location' => $form['location'],
          'status' => $form['status'],
        ]);
        header('Location: /BarangaySystem/public/announcements/index.php?msg=updated');
      } else {
        $stmt = db()->prepare("
          INSERT INTO announcements (category, title, description, event_date, location, status)
          VALUES (:category, :title, :description, :event_date, :location, :status)
        ");
        $stmt->execute([
          'category' => $form['category'],
          'title' => $form['title'],
          'description' => $form['description'],
          'event_date' => $form['event_date'],
          'location' => $form['location'],
          'status' => $form['status'],
        ]);
        header('Location: /BarangaySystem/public/announcements/index.php?msg=created');
      }
      exit;
    } catch (Throwable $e) {
      $errorMessage = 'Failed to save announcement.';
    }
  } else {
    $errorMessage = implode(' ', $errors);
  }
}

if ($editId > 0) {
  try {
    $stmt = db()->prepare("
      SELECT id, category, title, description, event_date, location, status
      FROM announcements
      WHERE id = :id AND COALESCE(is_archived, 0) = 0
      LIMIT 1
    ");
    $stmt->execute(['id' => $editId]);
    $row = $stmt->fetch();
    if ($row) {
      $form = [
        'category' => (string) $row['category'],
        'title' => (string) $row['title'],
        'description' => (string) $row['description'],
        'event_date' => (string) $row['event_date'],
        'location' => (string) $row['location'],
        'status' => strtolower((string) $row['status']),
      ];
    }
  } catch (Throwable $e) {
    $errorMessage = 'Failed to load announcement for editing.';
  }
}

$rows = [];
try {
  $stmt = db()->query("
    SELECT id, category, title, description, event_date, location, status, created_at
    FROM announcements
    WHERE COALESCE(is_archived, 0) = 0
    ORDER BY event_date DESC, id DESC
  ");
  foreach ($stmt->fetchAll() as $row) {
    $rows[] = [
      'id' => (int) $row['id'],
      'category' => (string) $row['category'],
      'title' => (string) $row['title'],
      'description' => (string) $row['description'],
      'event_date' => (string) $row['event_date'],
      'location' => (string) $row['location'],
      'status' => strtolower((string) $row['status']),
      'created_at' => (string) $row['created_at'],
    ];
  }
} catch (Throwable $e) {
  if ($errorMessage === '') {
    $errorMessage = 'Failed to load announcements.';
  }
}
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Announcements</div>
    <div class="topbar-right">
      <div class="topbar-user">
        <div class="avatar"><?= htmlspecialchars(strtoupper(substr((string) ($topbarProfile['full_name'] ?? 'A'), 0, 1))) ?></div>
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
          <div class="card-title"><?= $editId > 0 ? 'Edit Announcement' : 'Post Announcement' ?></div>
          <div class="card-sub">Create updates for dashboard bulletin board and public website</div>
        </div>
      </div>

      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
      <div style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#e9f9ee;border:1px solid #cbeed6;color:#0b6b2a;font-size:12px;font-weight:800;">
        Announcement posted successfully.
      </div>
      <?php endif; ?>
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
      <div style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#e9f9ee;border:1px solid #cbeed6;color:#0b6b2a;font-size:12px;font-weight:800;">
        Announcement updated successfully.
      </div>
      <?php endif; ?>
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
      <div style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#e9f9ee;border:1px solid #cbeed6;color:#0b6b2a;font-size:12px;font-weight:800;">
        Announcement deleted successfully.
      </div>
      <?php endif; ?>
      <?php if ($errorMessage !== ''): ?>
      <div style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#ffe9e9;border:1px solid #ffd0d0;color:#a10000;font-size:12px;font-weight:800;">
        <?= htmlspecialchars($errorMessage) ?>
      </div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="id" value="<?= $editId > 0 ? $editId : 0 ?>">
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Category *</label>
            <select class="input" style="width:100%;min-width:0;" name="category" required>
              <?php foreach ($categories as $category): ?>
              <option value="<?= htmlspecialchars($category) ?>" <?= $form['category'] === $category ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Event Date *</label>
            <input class="input" style="width:100%;min-width:0;" type="date" name="event_date" value="<?= htmlspecialchars((string) $form['event_date']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Title *</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="title" value="<?= htmlspecialchars((string) $form['title']) ?>" required>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Location</label>
            <input class="input" style="width:100%;min-width:0;" type="text" name="location" value="<?= htmlspecialchars((string) $form['location']) ?>">
          </div>
          <div style="grid-column:1 / span 2;">
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Description *</label>
            <textarea class="input" style="width:100%;min-width:0;min-height:100px;resize:vertical;" name="description" required><?= htmlspecialchars((string) $form['description']) ?></textarea>
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Status *</label>
            <select class="input" style="width:100%;min-width:0;" name="status" required>
              <option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= $form['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:12px;">
          <a class="btn btn-light" href="/BarangaySystem/public/announcements/index.php">Cancel</a>
          <button class="btn btn-primary" type="submit"><?= $editId > 0 ? 'Update Announcement' : 'Post Announcement' ?></button>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="table-header">
        <div>
          <div class="card-title">Announcement List</div>
          <div class="card-sub">Latest posted bulletins</div>
        </div>
      </div>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Category</th>
              <th>Title</th>
              <th>Date</th>
              <th>Location</th>
              <th>Status</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= htmlspecialchars((string) $row['category']) ?></td>
              <td>
                <div style="font-weight:800;"><?= htmlspecialchars((string) $row['title']) ?></div>
                <div class="muted" style="max-width:520px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars((string) $row['description']) ?></div>
              </td>
              <td><?= htmlspecialchars((string) $row['event_date']) ?></td>
              <td><?= htmlspecialchars((string) $row['location']) ?></td>
              <td>
                <?php if ($row['status'] === 'active'): ?>
                <span class="status-pill status-received">Active</span>
                <?php else: ?>
                <span class="status-pill status-cancelled">Inactive</span>
                <?php endif; ?>
              </td>
              <td class="action-cell text-right">
                <div class="action-buttons">
                  <a class="icon-btn" href="/BarangaySystem/public/announcements/index.php?edit=<?= $row['id'] ?>" title="Edit">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                      <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                    </svg>
                  </a>
                  <a class="icon-btn danger" href="/BarangaySystem/public/announcements/index.php?delete_id=<?= $row['id'] ?>" title="Delete" onclick="return confirm('Delete this announcement?')">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($rows) === 0): ?>
            <tr><td colspan="6" class="muted">No announcements yet. Post your first bulletin above.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>
