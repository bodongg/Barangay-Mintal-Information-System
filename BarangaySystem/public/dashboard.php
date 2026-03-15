<?php
require_once __DIR__ . '/../app/middleware/auth.php';
requireAdminAuth();

?>
<?php
$pageTitle = "Dashboard";
include __DIR__ . '/../views/layouts/header.php';
include __DIR__ . '/../views/layouts/sidebar.php'; 
include __DIR__ . '/../app/config/topbar_profile.php';
require_once __DIR__ . '/../app/config/database.php';

$residents = [];

$maleCount = 0;
$femaleCount = 0;
$residentTotal = 0;
$seniorCount = 0;
$pwdCount = 0;
$householdTotal = 0;
$householdThisMonth = 0;
$certificateTotal = 0;
$certificateThisWeek = 0;
$blotterTotal = 0;
$blotterUnresolved = 0;
$settledCount = 0;
$unsettledCount = 0;
$unscheduledCount = 0;
$scheduledCount = 0;
$bulletins = [];

try {
  $summary = db()->query("
    SELECT
      COUNT(*) AS total_count,
      SUM(CASE WHEN LOWER(gender) = 'male' THEN 1 ELSE 0 END) AS male_count,
      SUM(CASE WHEN LOWER(gender) = 'female' THEN 1 ELSE 0 END) AS female_count,
      SUM(CASE WHEN age >= 60 THEN 1 ELSE 0 END) AS senior_count,
      SUM(CASE WHEN is_pwd = 1 OR (pwd_type IS NOT NULL AND pwd_type <> '') THEN 1 ELSE 0 END) AS pwd_count
    FROM residents
    WHERE COALESCE(is_archived, 0) = 0
  ")->fetch();

  if ($summary) {
    $residentTotal = (int) ($summary['total_count'] ?? 0);
    $maleCount = (int) ($summary['male_count'] ?? 0);
    $femaleCount = (int) ($summary['female_count'] ?? 0);
    $seniorCount = (int) ($summary['senior_count'] ?? 0);
    $pwdCount = (int) ($summary['pwd_count'] ?? 0);
  }

  $householdSummary = db()->query("
    SELECT
      COUNT(*) AS total_count,
      SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) AS this_month_count
    FROM households
    WHERE COALESCE(is_archived, 0) = 0
  ")->fetch();
  if ($householdSummary) {
    $householdTotal = (int) ($householdSummary['total_count'] ?? 0);
    $householdThisMonth = (int) ($householdSummary['this_month_count'] ?? 0);
  }

  $certificateSummary = db()->query("
    SELECT
      COUNT(*) AS total_count,
      SUM(CASE WHEN YEAR(request_date) = YEAR(CURDATE()) AND WEEK(request_date, 1) = WEEK(CURDATE(), 1) THEN 1 ELSE 0 END) AS this_week_count
    FROM certificates
    WHERE COALESCE(is_archived, 0) = 0
  ")->fetch();
  if ($certificateSummary) {
    $certificateTotal = (int) ($certificateSummary['total_count'] ?? 0);
    $certificateThisWeek = (int) ($certificateSummary['this_week_count'] ?? 0);
  }

  $recent = db()->query("
    SELECT
      name,
      purok,
      gender,
      age,
      status,
      is_pwd,
      pwd_type,
      DATE(created_at) AS date_added
    FROM residents
    WHERE COALESCE(is_archived, 0) = 0
    ORDER BY created_at DESC
    LIMIT 7
  ")->fetchAll();

  foreach ($recent as $row) {
    $residents[] = [
      'name' => (string) $row['name'],
      'purok' => (string) $row['purok'],
      'gender' => (string) $row['gender'],
      'age' => (int) $row['age'],
      'status' => ucfirst(strtolower((string) $row['status'])),
      'date' => (string) $row['date_added'],
      'is_pwd' => ((int) $row['is_pwd'] === 1) || !empty($row['pwd_type']),
      'pwd_type' => (string) ($row['pwd_type'] ?? ''),
    ];
  }

  $blotterSummary = db()->query("
    SELECT
      SUM(CASE WHEN LOWER(status) = 'settled' THEN 1 ELSE 0 END) AS settled_count,
      SUM(CASE WHEN LOWER(status) = 'cancelled' THEN 1 ELSE 0 END) AS unsettled_count,
      SUM(CASE WHEN LOWER(status) = 'received' THEN 1 ELSE 0 END) AS unscheduled_count,
      SUM(CASE WHEN LOWER(status) = 'ongoing' THEN 1 ELSE 0 END) AS scheduled_count
    FROM blotter_records
    WHERE COALESCE(is_archived, 0) = 0
  ")->fetch();

  if ($blotterSummary) {
    $settledCount = (int) ($blotterSummary['settled_count'] ?? 0);
    $unsettledCount = (int) ($blotterSummary['unsettled_count'] ?? 0);
    $unscheduledCount = (int) ($blotterSummary['unscheduled_count'] ?? 0);
    $scheduledCount = (int) ($blotterSummary['scheduled_count'] ?? 0);
    $blotterTotal = $settledCount + $unsettledCount + $unscheduledCount + $scheduledCount;
    $blotterUnresolved = $unscheduledCount + $scheduledCount;
  }
  try {
    $bulletinRows = db()->query(" 
      SELECT
        category,
        title,
        description,
        event_date,
        location
      FROM announcements
      WHERE LOWER(status) = 'active'
        AND COALESCE(is_archived, 0) = 0
      ORDER BY event_date DESC, id DESC
      LIMIT 4
    ")->fetchAll();

    foreach ($bulletinRows as $row) {
      $category = trim((string) ($row['category'] ?? 'Meeting'));
      $categoryKey = strtolower($category);
      $tagClass = 'tag-meeting';

      if (in_array($categoryKey, ['fiesta', 'event'], true)) {
        $tagClass = 'tag-fiesta';
      } elseif (in_array($categoryKey, ['seminar', 'training'], true)) {
        $tagClass = 'tag-seminar';
      } elseif (in_array($categoryKey, ['health', 'medical'], true)) {
        $tagClass = 'tag-health';
      }

      $eventDate = '';
      $rawDate = (string) ($row['event_date'] ?? '');
      if ($rawDate !== '') {
        $ts = strtotime($rawDate);
        if ($ts !== false) {
          $eventDate = date('F j, Y', $ts);
        }
      }

      $location = trim((string) ($row['location'] ?? ''));
      $meta = $eventDate;
      if ($location !== '') {
        $meta = $meta !== '' ? ($meta . ' · ' . $location) : $location;
      }

      $bulletins[] = [
        'tag' => $tagClass,
        'label' => $category !== '' ? $category : 'Meeting',
        'title' => (string) ($row['title'] ?? ''),
        'meta' => $meta,
        'desc' => (string) ($row['description'] ?? ''),
      ];
    }
  } catch (Throwable $e) {
    $bulletins = [];
  }
} catch (Throwable $e) {
  // Keep dashboard usable when DB is not ready yet.
  $residents = [];
  $bulletins = [];
}

function residentTags(array $resident): string {
  $tags = [];
  if (($resident['age'] ?? 0) >= 60) {
    $tags[] = '<span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;background:#e6fbf6;color:#0f8a72;font-size:11px;font-weight:800;">Senior</span>';
  }
  if (!empty($resident['is_pwd'])) {
    $label = !empty($resident['pwd_type']) ? 'PWD: ' . $resident['pwd_type'] : 'PWD';
    $tags[] = '<span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;background:#fff0dc;color:#b96500;font-size:11px;font-weight:800;">' . htmlspecialchars($label) . '</span>';
  }
  if (empty($tags)) {
    return '<span style="color:var(--muted);font-size:12px;font-weight:700;">-</span>';
  }
  return implode(' ', $tags);
}

?>

<div class="main">

  <!-- Ã¢â€â‚¬Ã¢â€â‚¬ Top Bar Ã¢â€â‚¬Ã¢â€â‚¬ -->
  <div class="topbar">
    <div class="topbar-title">Dashboard</div>
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

  <!-- Ã¢â€â‚¬Ã¢â€â‚¬ Content Ã¢â€â‚¬Ã¢â€â‚¬ -->
  <div class="content">

    <!-- STAT CARDS -->
    <div class="stats-row">

      <div class="stat-card">
        <div class="stat-icon pink">&#128101;</div>
        <div>
          <div class="stat-value"><?= number_format($maleCount + $femaleCount) ?></div>
          <div class="stat-label">Total Residents</div>
          <div class="stat-change up">+12 this month</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon orange">&#127968;</div>
        <div>
          <div class="stat-value"><?= number_format($householdTotal) ?></div>
          <div class="stat-label">Total Households</div>
          <div class="stat-change up">+<?= $householdThisMonth ?> this month</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon teal">&#128196;</div>
        <div>
          <div class="stat-value"><?= number_format($certificateTotal) ?></div>
          <div class="stat-label">Certificates Issued</div>
          <div class="stat-change up">+<?= $certificateThisWeek ?> this week</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple">&#128203;</div>
        <div>
          <div class="stat-value"><?= number_format($blotterTotal) ?></div>
          <div class="stat-label">Blotter Cases</div>
          <div class="stat-change down"><?= $blotterUnresolved ?> unresolved</div>
        </div>
      </div>

    </div>

    <!-- CHARTS ROW -->
    <div class="charts-row">

      <!-- Population Bar Chart -->
      <div class="chart-card">
        <div class="chart-header">
          <div>
            <div class="card-title">Population Overview</div>
            <div class="card-sub">Total barangay population breakdown</div>
          </div>
        </div>
        <div id="populationPie" style="width:220px;height:220px;border-radius:50%;margin:8px auto 0;position:relative;background:#eef0fb;">
          <div style="position:absolute;inset:26%;background:#fff;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;border:1px solid #eef0fb;">
            <div style="font-size:11px;color:var(--muted);font-weight:700;">Total</div>
            <div style="font-size:24px;color:var(--text);font-weight:900;line-height:1.1;"><?= $residentTotal ?></div>
            <div style="font-size:10px;color:var(--muted);font-weight:700;">Residents</div>
          </div>
        </div>
        <div class="chart-legend" style="margin-top:12px;">
          <div class="legend-item"><div class="legend-dot" style="background:#6c63ff;"></div> Male (<?= $maleCount ?>)</div>
          <div class="legend-item"><div class="legend-dot" style="background:#ff6b8a;"></div> Female (<?= $femaleCount ?>)</div>
          <div class="legend-item"><div class="legend-dot" style="background:#1abc9c;"></div> Senior (<?= $seniorCount ?>)</div>
          <div class="legend-item"><div class="legend-dot" style="background:#ff9f43;"></div> PWD (<?= $pwdCount ?>)</div>
        </div>
      </div>

      <!-- Blotter Cases Status -->
      <div class="chart-card">
        <div class="chart-header">
          <div>
            <div class="card-title">Blotter Cases</div>
            <div class="card-sub">Current case status overview</div>
          </div>
        </div>
        <div class="blotter-grid">

          <div class="blotter-tile green">
            <div class="blotter-tile-label">Settled Cases</div>
            <div class="blotter-tile-value"><?= $settledCount ?></div>
          </div>

          <div class="blotter-tile red">
            <div class="blotter-tile-label">Dismissed Cases</div>
            <div class="blotter-tile-value"><?= $unsettledCount ?></div>
          </div>

          <div class="blotter-tile blue">
            <div class="blotter-tile-label">Unscheduled Cases</div>
            <div class="blotter-tile-value"><?= $unscheduledCount ?></div>
          </div>

          <div class="blotter-tile orange">
            <div class="blotter-tile-label">Scheduled Cases</div>
            <div class="blotter-tile-value"><?= $scheduledCount ?></div>
          </div>

        </div>
      </div>

    </div>

    <!-- BOTTOM ROW: Recent Residents + Bulletin -->
    <div class="bottom-row">

      <!-- Recent Residents Table -->
      <div class="table-card">
        <div class="table-card-header">
          <div>
            <div class="card-title">Recent Residents</div>
            <div class="card-sub">Newly registered residents</div>
          </div>
          <a class="view-all-link" href="/BarangaySystem/public/residents/index.php">View All &rarr;</a>
        </div>
        <table class="mini-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Gender</th>
              <th>Age</th>
              <th>Identification</th>
              <th>Status</th>
              <th>Date Added</th>
            </tr>
          </thead>
          <tbody>
            <?php
            foreach ($residents as $r):
              $badge = $r['status']==='Active'
                ? '<span style="background:#d9f7df;color:#0b6b2a;font-size:11px;font-weight:800;padding:3px 10px;border-radius:999px;">Active</span>'
                : '<span style="background:#ffd7d7;color:#a10000;font-size:11px;font-weight:800;padding:3px 10px;border-radius:999px;">Inactive</span>';
            ?>
            <tr>
              <td>
                <div class="resident-name"><?= htmlspecialchars($r['name']) ?></div>
                <div class="resident-purok"><?= htmlspecialchars($r['purok']) ?></div>
              </td>
              <td><?= htmlspecialchars($r['gender']) ?></td>
              <td><?= $r['age'] ?></td>
              <td><?= residentTags($r) ?></td>
              <td><?= $badge ?></td>
              <td style="color:var(--muted);font-size:12px;"><?= $r['date'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($residents) === 0): ?>
            <tr>
              <td colspan="6" style="color:var(--muted);font-size:12px;">No resident records found. Complete DB setup and add residents.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Bulletin Board -->
      <div class="bulletin-card">
        <div class="bulletin-card-header">
          <div>
            <div class="card-title">Bulletin Board</div>
            <div class="card-sub">Upcoming events &amp; announcements</div>
          </div>
          <a class="view-all-link" href="/BarangaySystem/public/announcements/index.php">Post &rarr;</a>
        </div>
        <div class="bulletin-list">
          <?php foreach ($bulletins as $b): ?>
          <div class="bulletin-item">
            <div class="bulletin-item-top">
              <span class="bulletin-tag <?= htmlspecialchars((string) $b['tag']) ?>"><?= htmlspecialchars((string) $b['label']) ?></span>
              <span class="bulletin-meta"><?= htmlspecialchars((string) $b['meta']) ?></span>
            </div>
            <div class="bulletin-title"><?= htmlspecialchars((string) $b['title']) ?></div>
            <div class="bulletin-desc"><?= htmlspecialchars((string) $b['desc']) ?></div>
          </div>
          <?php endforeach; ?>
          <?php if (count($bulletins) === 0): ?>
          <div class="bulletin-item">
            <div class="bulletin-title">No announcements yet</div>
            <div class="bulletin-desc">Post your first announcement to show updates here and on the public website.</div>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
    <!-- /bottom-row -->

  </div>
  <!-- /content -->
</div>
<!-- /main -->

<script>
(() => {
  const pie = document.getElementById('populationPie');
  if (!pie) return;

  const values = <?= json_encode([$maleCount, $femaleCount, $seniorCount, $pwdCount]) ?>;
  const colors = ['#6c63ff', '#ff6b8a', '#1abc9c', '#ff9f43'];
  const total = values.reduce((sum, n) => sum + Number(n || 0), 0);

  if (total <= 0) {
    pie.style.background = '#eef0fb';
    return;
  }

  let running = 0;
  const stops = values.map((value, index) => {
    const start = (running / total) * 100;
    running += Number(value || 0);
    const end = (running / total) * 100;
    return `${colors[index]} ${start}% ${end}%`;
  });
  pie.style.background = `conic-gradient(${stops.join(', ')})`;
})();
</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>




