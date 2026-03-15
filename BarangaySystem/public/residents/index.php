<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "Residents";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$rows = [];
$errorMessage = '';
$showArchived = isset($_GET['view']) && $_GET['view'] === 'archived';
$archivedCount = 0;

if (isset($_GET['archive_id'])) {
  try {
    $archiveId = (int) $_GET['archive_id'];
    if ($archiveId > 0) {
      $stmt = db()->prepare("UPDATE residents SET is_archived = 1 WHERE id = :id");
      $stmt->execute(['id' => $archiveId]);
    }
    header('Location: /BarangaySystem/public/residents/index.php?msg=archived');
    exit;
  } catch (Throwable $e) {
    $errorMessage = 'Failed to archive resident record.';
  }
}

if (isset($_GET['restore_id'])) {
  try {
    $restoreId = (int) $_GET['restore_id'];
    if ($restoreId > 0) {
      $stmt = db()->prepare("UPDATE residents SET is_archived = 0 WHERE id = :id");
      $stmt->execute(['id' => $restoreId]);
    }
    header('Location: /BarangaySystem/public/residents/index.php?view=archived&msg=restored');
    exit;
  } catch (Throwable $e) {
    $errorMessage = 'Failed to restore resident record.';
  }
}

try {
  $stmt = db()->prepare("
    SELECT
      id,
      name,
      purok,
      gender,
      age,
      civil_status,
      status,
      is_pwd,
      pwd_type,
      DATE(created_at) AS date_added
    FROM residents
    WHERE COALESCE(is_archived, 0) = :is_archived
    ORDER BY created_at DESC
  ");
  $stmt->execute(['is_archived' => $showArchived ? 1 : 0]);
  foreach ($stmt->fetchAll() as $row) {
    $rows[] = [
      'id' => (int) $row['id'],
      'name' => (string) $row['name'],
      'purok' => (string) $row['purok'],
      'gender' => (string) $row['gender'],
      'age' => (int) $row['age'],
      'civil' => (string) $row['civil_status'],
      'status' => strtolower((string) $row['status']),
      'is_pwd' => ((int) $row['is_pwd'] === 1) || !empty($row['pwd_type']),
      'pwd_type' => (string) ($row['pwd_type'] ?? ''),
      'date' => (string) $row['date_added'],
    ];
  }

  $archivedCount = (int) (db()->query("SELECT COUNT(*) FROM residents WHERE COALESCE(is_archived, 0) = 1")->fetchColumn() ?: 0);
} catch (Throwable $e) {
  // Keep page usable while DB is being set up.
  $rows = [];
  if ($errorMessage === '') {
    $errorMessage = 'Database connection failed. Check your database setup.';
  }
}

function statusBadge(string $s): string {
  if ($s === 'active')   return '<span class="status-pill status-received">Active</span>';
  return '<span class="status-pill status-cancelled">Inactive</span>';
}

function identificationBadges(array $resident): string {
  $badges = [];
  if (($resident['age'] ?? 0) >= 60) {
    $badges[] = '<span style="display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;background:#e6fbf6;color:#0f8a72;font-size:11px;font-weight:800;">Senior</span>';
  }
  if (!empty($resident['is_pwd'])) {
    $label = !empty($resident['pwd_type']) ? 'PWD: ' . $resident['pwd_type'] : 'PWD';
    $badges[] = '<span style="display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;background:#fff0dc;color:#b96500;font-size:11px;font-weight:800;">' . htmlspecialchars($label) . '</span>';
  }
  if (empty($badges)) {
    return '<span style="color:var(--muted);font-size:12px;font-weight:700;">None</span>';
  }
  return implode(' ', $badges);
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
          <div class="card-title">Residents</div>
          <div class="card-sub"><?= $showArchived ? 'Archived resident records' : 'Manage barangay residents and their information' ?></div>
        </div>
        <div class="table-actions">
          <input class="input" type="text" placeholder="Search name / purok...">
          <?php if ($showArchived): ?>
            <a class="btn btn-light" href="/BarangaySystem/public/residents/index.php">Back to Active</a>
          <?php else: ?>
            <a class="btn btn-light" href="/BarangaySystem/public/residents/index.php?view=archived">Archived (<?= $archivedCount ?>)</a>
            <a class="btn btn-primary" href="/BarangaySystem/public/residents/create.php">+ Add Resident</a>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($errorMessage !== ''): ?>
      <div style="margin-bottom:10px;padding:10px 12px;border-radius:10px;background:#ffe9e9;border:1px solid #ffd0d0;color:#a10000;font-size:12px;font-weight:800;">
        <?= htmlspecialchars($errorMessage) ?>
      </div>
      <?php endif; ?>
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
      <div style="margin-bottom:10px;padding:10px 12px;border-radius:10px;background:#e9f9ee;border:1px solid #cbeed6;color:#0b6b2a;font-size:12px;font-weight:800;">
        Resident record saved successfully.
      </div>
      <?php endif; ?>
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
      <div style="margin-bottom:10px;padding:10px 12px;border-radius:10px;background:#e9f9ee;border:1px solid #cbeed6;color:#0b6b2a;font-size:12px;font-weight:800;">
        Resident record updated successfully.
      </div>
      <?php endif; ?>
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'archived'): ?>
      <div style="margin-bottom:10px;padding:10px 12px;border-radius:10px;background:#e9f9ee;border:1px solid #cbeed6;color:#0b6b2a;font-size:12px;font-weight:800;">
        Resident record archived successfully.
      </div>
      <?php endif; ?>
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'restored'): ?>
      <div style="margin-bottom:10px;padding:10px 12px;border-radius:10px;background:#e9f9ee;border:1px solid #cbeed6;color:#0b6b2a;font-size:12px;font-weight:800;">
        Resident record restored successfully.
      </div>
      <?php endif; ?>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Gender</th>
              <th>Age</th>
              <th>Civil Status</th>
              <th>Purok</th>
              <th>Identification</th>
              <th>Status</th>
              <th>Date Added</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
              <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
              <td><?= htmlspecialchars($r['gender']) ?></td>
              <td><?= $r['age'] ?></td>
              <td><?= htmlspecialchars($r['civil']) ?></td>
              <td><?= htmlspecialchars($r['purok']) ?></td>
              <td><?= identificationBadges($r) ?></td>
              <td><?= statusBadge($r['status']) ?></td>
              <td style="color:var(--muted);font-size:12px;"><?= $r['date'] ?></td>
              <td class="text-right action-cell">
                <div class="action-buttons">
                  <a class="icon-btn" href="/BarangaySystem/public/residents/view.php?id=<?= (int) $r['id'] ?>" title="View">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                      <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                  </a>
                  <a class="icon-btn" href="/BarangaySystem/public/residents/edit.php?id=<?= (int) $r['id'] ?>" title="Edit">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                      <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                    </svg>
                  </a>
                  <?php if ($showArchived): ?>
                    <a class="icon-btn" href="/BarangaySystem/public/residents/index.php?view=archived&restore_id=<?= (int) $r['id'] ?>" title="Restore" onclick="return confirm('Restore this resident record?');">
                      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 5 17 10"/>
                        <line x1="12" y1="5" x2="12" y2="17"/>
                      </svg>
                    </a>
                  <?php else: ?>
                    <a class="icon-btn danger" href="/BarangaySystem/public/residents/index.php?archive_id=<?= (int) $r['id'] ?>" title="Archive" onclick="return confirm('Archive this resident record?');">
                      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>
                      </svg>
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($rows) === 0): ?>
            <tr>
              <td colspan="9" class="muted"><?= $showArchived ? 'No archived resident records found.' : 'No resident records found. Add residents in create page after DB setup.' ?></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>


