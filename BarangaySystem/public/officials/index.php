<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "Officials";
include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$rows = [];
$errorMessage = '';

if (isset($_GET['delete_id'])) {
  try {
    $deleteId = (int) $_GET['delete_id'];
    if ($deleteId > 0) {
      $stmt = db()->prepare("DELETE FROM officials WHERE id = :id");
      $stmt->execute(['id' => $deleteId]);
    }
    header('Location: /BarangaySystem/public/officials/index.php?msg=deleted');
    exit;
  } catch (Throwable $e) {
    $errorMessage = 'Failed to delete official record.';
  }
}

try {
  $stmt = db()->query("
    SELECT
      id,
      full_name,
      official_photo,
      position,
      committee,
      contact_number,
      work_location,
      term_start,
      term_end,
      status
    FROM officials
    ORDER BY created_at DESC
  ");
  $rows = $stmt->fetchAll();
} catch (Throwable $e) {
  $rows = [];
  if ($errorMessage === '') {
    $errorMessage = 'Database connection failed. Check your database setup.';
  }
}

function statusBadge(string $status): string {
  $s = strtolower(trim($status));
  if ($s === 'active') {
    return '<span class="status-pill status-received">Active</span>';
  }
  if ($s === 'inactive') {
    return '<span class="status-pill status-cancelled">Inactive</span>';
  }
  return '<span class="status-pill status-ongoing">Vacant</span>';
}

function termSummary(string $start, string $end): string {
  $today = new DateTimeImmutable('today');
  $endDate = DateTimeImmutable::createFromFormat('Y-m-d', $end) ?: $today;
  $remaining = (int) $today->diff($endDate)->format('%r%a');

  $label = $remaining >= 0 ? $remaining . ' days left' : abs($remaining) . ' days overdue';
  $color = $remaining >= 0 ? '#0f8a72' : '#a10000';
  $bg = $remaining >= 0 ? '#e6fbf6' : '#ffe4e4';

  return '<div style="font-weight:800;">' . htmlspecialchars($start) . ' to ' . htmlspecialchars($end) . '</div>'
    . '<div style="margin-top:4px;display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;background:' . $bg . ';color:' . $color . ';font-size:11px;font-weight:800;">'
    . htmlspecialchars($label) . '</div>';
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
          <div class="card-title">Barangay Officials</div>
          <div class="card-sub">Manage roster, positions, and term information</div>
        </div>
        <div class="table-actions">
          <input class="input" type="text" placeholder="Search name / position...">
          <a class="btn btn-primary" href="/BarangaySystem/public/officials/create.php">+ Add Official</a>
        </div>
      </div>

      <?php if ($errorMessage !== ''): ?>
      <div style="margin-bottom:10px;padding:10px 12px;border-radius:10px;background:#ffe9e9;border:1px solid #ffd0d0;color:#a10000;font-size:12px;font-weight:800;">
        <?= htmlspecialchars($errorMessage) ?>
      </div>
      <?php endif; ?>
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
      <div style="margin-bottom:10px;padding:10px 12px;border-radius:10px;background:#e9f9ee;border:1px solid #cbeed6;color:#0b6b2a;font-size:12px;font-weight:800;">
        Official record saved successfully.
      </div>
      <?php endif; ?>
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
      <div style="margin-bottom:10px;padding:10px 12px;border-radius:10px;background:#e9f9ee;border:1px solid #cbeed6;color:#0b6b2a;font-size:12px;font-weight:800;">
        Official record updated successfully.
      </div>
      <?php endif; ?>
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
      <div style="margin-bottom:10px;padding:10px 12px;border-radius:10px;background:#e9f9ee;border:1px solid #cbeed6;color:#0b6b2a;font-size:12px;font-weight:800;">
        Official record deleted successfully.
      </div>
      <?php endif; ?>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Position</th>
              <th>Committee</th>
              <th>Contact</th>
              <th>Work Location</th>
              <th>Term</th>
              <th>Status</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $o): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px;">
                  <?php if (trim((string) ($o['official_photo'] ?? '')) !== ''): ?>
                  <img src="<?= htmlspecialchars((string) $o['official_photo']) ?>" alt="<?= htmlspecialchars((string) $o['full_name']) ?>" style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:1px solid var(--border);background:#f4f6fb;flex-shrink:0;">
                  <?php else: ?>
                  <div style="width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#eef1fb;color:var(--primary);font-size:14px;font-weight:900;flex-shrink:0;">
                    <?= htmlspecialchars(strtoupper(substr(trim((string) $o['full_name']), 0, 1) ?: '?')) ?>
                  </div>
                  <?php endif; ?>
                  <strong><?= htmlspecialchars((string) $o['full_name']) ?></strong>
                </div>
              </td>
              <td><?= htmlspecialchars((string) $o['position']) ?></td>
              <td><?= htmlspecialchars((string) $o['committee']) ?></td>
              <td><?= htmlspecialchars((string) $o['contact_number']) ?></td>
              <td><?= htmlspecialchars((string) $o['work_location']) ?></td>
              <td><?= termSummary((string) $o['term_start'], (string) $o['term_end']) ?></td>
              <td><?= statusBadge((string) $o['status']) ?></td>
              <td class="text-right action-cell">
                <div class="action-buttons">
                  <a class="icon-btn" href="/BarangaySystem/public/officials/edit.php?id=<?= (int) $o['id'] ?>" title="Edit">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                      <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                    </svg>
                  </a>
                  <a class="icon-btn danger" href="/BarangaySystem/public/officials/index.php?delete_id=<?= (int) $o['id'] ?>" title="Delete" onclick="return confirm('Delete this official record?');">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($rows) === 0): ?>
            <tr>
              <td colspan="8" class="muted">No official records found. Add officials using the button above.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="muted" style="margin-top:10px;">
        Tip: Track term dates to quickly identify expiring or vacant positions.
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

