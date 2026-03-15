<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
requireAdminAuth();

$pageTitle = "Reports";
require_once __DIR__ . '/../../app/config/database.php';

$selectedFrom = $_GET['from'] ?? date('Y-m-01');
$selectedTo = $_GET['to'] ?? date('Y-m-d');
$selectedPurok = $_GET['purok'] ?? 'All Purok';

$puroks = ['All Purok', 'Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6'];

$errorMessage = '';
$fromDate = DateTimeImmutable::createFromFormat('Y-m-d', $selectedFrom) ?: new DateTimeImmutable('first day of this month');
$toDate = DateTimeImmutable::createFromFormat('Y-m-d', $selectedTo) ?: new DateTimeImmutable('today');
if ($fromDate > $toDate) {
  $tmp = $fromDate;
  $fromDate = $toDate;
  $toDate = $tmp;
}
$selectedFrom = $fromDate->format('Y-m-d');
$selectedTo = $toDate->format('Y-m-d');
$yearStart = $toDate->setDate((int) $toDate->format('Y'), 1, 1)->format('Y-m-d');
$yearEnd = $toDate->setDate((int) $toDate->format('Y'), 12, 31)->format('Y-m-d');

function fetchValue(string $sql, array $params = []): int {
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  return (int) $stmt->fetchColumn();
}

function fetchRows(string $sql, array $params = []): array {
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll();
}

$summary = [
  'new_residents' => 0,
  'new_households' => 0,
  'certificates' => 0,
  'blotter' => 0,
  'male' => 0,
  'female' => 0,
  'pwd' => 0,
  'seniors' => 0,
  'age_child' => 0,
  'age_youth' => 0,
  'age_adult' => 0,
  'age_senior' => 0,
  'households_active' => 0,
  'households_inactive' => 0,
  'officials_active' => 0,
  'officials_vacant' => 0,
  'officials_expiring' => 0,
];
$certificateStatus = ['received' => 0, 'ongoing' => 0, 'cancelled' => 0];
$blotterStatus = ['settled' => 0, 'received' => 0, 'ongoing' => 0, 'cancelled' => 0];
$certificateRows = [];
$blotterRows = [];
$committeeRows = [];
$monthlyCertificates = array_fill(0, 12, 0);
$monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

$paramsRange = [':from' => $selectedFrom, ':to' => $selectedTo];
$resPurokSql = '';
$rowPurokSql = '';
if ($selectedPurok !== 'All Purok') {
  $paramsRange[':purok'] = $selectedPurok;
  $resPurokSql = " AND purok = :purok";
  $rowPurokSql = " WHERE purok = :purok";
}

try {
  $summary['new_residents'] = fetchValue(
    "SELECT COUNT(*) FROM residents WHERE DATE(created_at) BETWEEN :from AND :to{$resPurokSql}",
    $paramsRange
  );
  $summary['new_households'] = fetchValue(
    "SELECT COUNT(*) FROM households WHERE DATE(created_at) BETWEEN :from AND :to{$resPurokSql}",
    $paramsRange
  );
  $summary['certificates'] = fetchValue(
    "SELECT COUNT(*) FROM certificates WHERE request_date BETWEEN :from AND :to{$resPurokSql}",
    $paramsRange
  );
  $summary['blotter'] = fetchValue(
    "SELECT COUNT(*) FROM blotter_records WHERE date BETWEEN :from AND :to{$resPurokSql}",
    $paramsRange
  );

  $population = fetchRows(
    "SELECT
      SUM(CASE WHEN LOWER(gender) = 'male' THEN 1 ELSE 0 END) AS male_count,
      SUM(CASE WHEN LOWER(gender) = 'female' THEN 1 ELSE 0 END) AS female_count,
      SUM(CASE WHEN age >= 60 THEN 1 ELSE 0 END) AS senior_count,
      SUM(CASE WHEN is_pwd = 1 OR COALESCE(pwd_type,'') <> '' THEN 1 ELSE 0 END) AS pwd_count,
      SUM(CASE WHEN age BETWEEN 0 AND 12 THEN 1 ELSE 0 END) AS child_count,
      SUM(CASE WHEN age BETWEEN 13 AND 17 THEN 1 ELSE 0 END) AS youth_count,
      SUM(CASE WHEN age BETWEEN 18 AND 59 THEN 1 ELSE 0 END) AS adult_count,
      SUM(CASE WHEN age >= 60 THEN 1 ELSE 0 END) AS age_senior_count
    FROM residents
    WHERE DATE(created_at) BETWEEN :from AND :to{$resPurokSql}",
    $paramsRange
  );
  if (!empty($population)) {
    $pop = $population[0];
    $summary['male'] = (int) ($pop['male_count'] ?? 0);
    $summary['female'] = (int) ($pop['female_count'] ?? 0);
    $summary['seniors'] = (int) ($pop['senior_count'] ?? 0);
    $summary['pwd'] = (int) ($pop['pwd_count'] ?? 0);
    $summary['age_child'] = (int) ($pop['child_count'] ?? 0);
    $summary['age_youth'] = (int) ($pop['youth_count'] ?? 0);
    $summary['age_adult'] = (int) ($pop['adult_count'] ?? 0);
    $summary['age_senior'] = (int) ($pop['age_senior_count'] ?? 0);
  }

  $householdStatus = fetchRows(
    "SELECT status, COUNT(*) AS total
     FROM households
     WHERE DATE(created_at) BETWEEN :from AND :to{$resPurokSql}
     GROUP BY status",
    $paramsRange
  );
  foreach ($householdStatus as $row) {
    $key = strtolower((string) $row['status']);
    if ($key === 'active') $summary['households_active'] = (int) $row['total'];
    if ($key === 'inactive') $summary['households_inactive'] = (int) $row['total'];
  }

  $certStatusRows = fetchRows(
    "SELECT LOWER(status) AS status, COUNT(*) AS total
     FROM certificates
     WHERE request_date BETWEEN :from AND :to{$resPurokSql}
     GROUP BY LOWER(status)",
    $paramsRange
  );
  foreach ($certStatusRows as $row) {
    $key = (string) $row['status'];
    if (isset($certificateStatus[$key])) $certificateStatus[$key] = (int) $row['total'];
  }

  $blotterStatusRows = fetchRows(
    "SELECT LOWER(status) AS status, COUNT(*) AS total
     FROM blotter_records
     WHERE date BETWEEN :from AND :to{$resPurokSql}
     GROUP BY LOWER(status)",
    $paramsRange
  );
  foreach ($blotterStatusRows as $row) {
    $key = (string) $row['status'];
    if (isset($blotterStatus[$key])) $blotterStatus[$key] = (int) $row['total'];
  }

  $certificateRows = fetchRows(
    "SELECT certificate_type AS label, COUNT(*) AS total
     FROM certificates
     WHERE request_date BETWEEN :from AND :to{$resPurokSql}
     GROUP BY certificate_type
     ORDER BY total DESC, certificate_type ASC
     LIMIT 6",
    $paramsRange
  );

  $blotterRows = fetchRows(
    "SELECT blotter_type AS label, COUNT(*) AS total
     FROM blotter_records
     WHERE date BETWEEN :from AND :to{$resPurokSql}
     GROUP BY blotter_type
     ORDER BY total DESC, blotter_type ASC
     LIMIT 6",
    $paramsRange
  );

  $committeeRows = fetchRows(
    "SELECT committee AS label, COUNT(*) AS total
     FROM officials
     WHERE LOWER(COALESCE(status, '')) = 'active'
     GROUP BY committee
     ORDER BY total DESC, committee ASC
     LIMIT 6"
  );

  $summary['officials_active'] = fetchValue("SELECT COUNT(*) FROM officials WHERE LOWER(status) = 'active'");
  $summary['officials_vacant'] = fetchValue("SELECT COUNT(*) FROM officials WHERE LOWER(status) = 'vacant'");
  $summary['officials_expiring'] = fetchValue(
    "SELECT COUNT(*) FROM officials
     WHERE term_end >= CURDATE() AND term_end <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)"
  );

  $paramsYear = [':from' => $yearStart, ':to' => $yearEnd];
  if ($selectedPurok !== 'All Purok') {
    $paramsYear[':purok'] = $selectedPurok;
  }
  $monthRows = fetchRows(
    "SELECT MONTH(request_date) AS month_no, COUNT(*) AS total
     FROM certificates
     WHERE request_date BETWEEN :from AND :to" . ($selectedPurok !== 'All Purok' ? " AND purok = :purok" : "") . "
     GROUP BY MONTH(request_date)",
    $paramsYear
  );
  foreach ($monthRows as $row) {
    $i = (int) $row['month_no'] - 1;
    if ($i >= 0 && $i < 12) $monthlyCertificates[$i] = (int) $row['total'];
  }
} catch (Throwable $e) {
  $errorMessage = 'Database connection failed. Check your database setup.';
}

function statusBadge(string $status): string {
  $s = strtolower(trim($status));
  if ($s === 'settled') {
    return '<span class="status-pill status-received">Settled</span>';
  }
  if ($s === 'received') {
    return '<span class="status-pill status-received">Received</span>';
  }
  if ($s === 'cancelled') {
    return '<span class="status-pill status-cancelled">Cancelled</span>';
  }
  return '<span class="status-pill status-ongoing">On Going</span>';
}

function reportStatusChip(string $label, int $count, string $variant): string {
  $safeLabel = htmlspecialchars($label);
  $safeCount = number_format($count);
  return '<span class="report-chip report-chip-' . $variant . '">' . $safeLabel . '<strong>' . $safeCount . '</strong></span>';
}

$maxMonthlyCount = 0;
foreach ($monthlyCertificates as $v) {
  if ($v > $maxMonthlyCount) $maxMonthlyCount = $v;
}

$exportMode = strtolower(trim((string) ($_GET['export'] ?? '')));
if ($exportMode === 'csv') {
  $fileName = 'reports_' . $selectedFrom . '_to_' . $selectedTo . '.csv';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $fileName . '"');

  $out = fopen('php://output', 'w');
  fputcsv($out, ['Barangay Reports']);
  fputcsv($out, ['Date From', $selectedFrom]);
  fputcsv($out, ['Date To', $selectedTo]);
  fputcsv($out, ['Purok', $selectedPurok]);
  fputcsv($out, []);

  fputcsv($out, ['Monthly Dashboard Summary']);
  fputcsv($out, ['Metric', 'Total']);
  fputcsv($out, ['New Residents (Period)', $summary['new_residents']]);
  fputcsv($out, ['New Households (Period)', $summary['new_households']]);
  fputcsv($out, ['Certificates (Period)', $summary['certificates']]);
  fputcsv($out, ['Blotter Cases (Period)', $summary['blotter']]);
  fputcsv($out, []);

  fputcsv($out, ['Population Breakdown']);
  fputcsv($out, ['Category', 'Total']);
  fputcsv($out, ['Male', $summary['male']]);
  fputcsv($out, ['Female', $summary['female']]);
  fputcsv($out, ['PWD', $summary['pwd']]);
  fputcsv($out, ['Senior Citizens', $summary['seniors']]);
  fputcsv($out, ['0-12 (Children)', $summary['age_child']]);
  fputcsv($out, ['13-17 (Youth)', $summary['age_youth']]);
  fputcsv($out, ['18-59 (Adults)', $summary['age_adult']]);
  fputcsv($out, ['60+ (Senior)', $summary['age_senior']]);
  fputcsv($out, []);

  fputcsv($out, ['Certificate Status']);
  fputcsv($out, ['Received', $certificateStatus['received']]);
  fputcsv($out, ['On Going', $certificateStatus['ongoing']]);
  fputcsv($out, ['Cancelled', $certificateStatus['cancelled']]);
  fputcsv($out, []);
  fputcsv($out, ['Top Certificate Types']);
  fputcsv($out, ['Type', 'Total']);
  foreach ($certificateRows as $row) {
    fputcsv($out, [(string) $row['label'], (int) $row['total']]);
  }
  fputcsv($out, []);

  fputcsv($out, ['Blotter Status']);
  fputcsv($out, ['Settled', $blotterStatus['settled']]);
  fputcsv($out, ['Received', $blotterStatus['received']]);
  fputcsv($out, ['On Going', $blotterStatus['ongoing']]);
  fputcsv($out, ['Cancelled', $blotterStatus['cancelled']]);
  fputcsv($out, []);
  fputcsv($out, ['Top Blotter Categories']);
  fputcsv($out, ['Category', 'Total']);
  foreach ($blotterRows as $row) {
    fputcsv($out, [(string) $row['label'], (int) $row['total']]);
  }
  fputcsv($out, []);

  fputcsv($out, ['Officials']);
  fputcsv($out, ['Active Officials', $summary['officials_active']]);
  fputcsv($out, ['Vacant Positions', $summary['officials_vacant']]);
  fputcsv($out, ['Expiring Terms (60 days)', $summary['officials_expiring']]);
  fputcsv($out, []);
  fputcsv($out, ['Committee Distribution']);
  fputcsv($out, ['Committee', 'Total']);
  foreach ($committeeRows as $row) {
    fputcsv($out, [(string) $row['label'], (int) $row['total']]);
  }
  fputcsv($out, []);

  fputcsv($out, ['Monthly Certificate Trend (Current Year)']);
  fputcsv($out, ['Month', 'Total']);
  foreach ($monthLabels as $i => $label) {
    fputcsv($out, [$label, (int) $monthlyCertificates[$i]]);
  }
  fclose($out);
  exit;
}

if ($exportMode === 'pdf') {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Barangay Reports PDF</title>
  <style>
    @page{size:A4;margin:14mm}
    *{box-sizing:border-box}
    body{font-family:Arial,sans-serif;color:#1f243d;margin:0;background:#fff}
    .wrap{max-width:180mm;margin:0 auto}
    .header{border-bottom:2px solid #2c2f6b;padding-bottom:10px;margin-bottom:14px}
    .header-top{display:flex;align-items:center;gap:12px}
    .header-logo{
      width:160px;
      height:auto;
      max-height:48px;
      object-fit:contain;
      flex-shrink:0;
      border:none;
      border-radius:0;
      background:transparent;
      box-shadow:none;
      display:block;
    }
    h1{font-size:24px;margin:0 0 4px}
    .meta{font-size:12px;color:#4f5879;line-height:1.45}
    h2{font-size:16px;margin:16px 0 8px}
    .section{page-break-inside:avoid}
    table{width:100%;border-collapse:collapse;margin-top:6px}
    th,td{border:1px solid #dfe3f4;padding:7px 9px;font-size:12px;text-align:left}
    th{background:#f5f7ff}
    .kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
    .kpi{border:1px solid #dfe3f4;border-radius:8px;padding:10px;background:#fafbff}
    .kpi .label{font-size:11px;color:#5a607a}
    .kpi .value{font-size:20px;font-weight:700;margin-top:4px}
    .actions{display:flex;gap:8px;margin:8px 0 0}
    .btn{border:1px solid #d6daf0;background:#fff;padding:6px 10px;border-radius:6px;font-size:12px;cursor:pointer}
    @media print{
      .no-print{display:none}
      .wrap{max-width:none}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <div class="header-top">
        <img class="header-logo" src="/BarangaySystem/assets/images/LOGO%20BARANGAY.png" alt="Barangay Logo">
        <h1>Barangay Reports</h1>
      </div>
      <div class="meta">Date Range: <?= h($selectedFrom) ?> to <?= h($selectedTo) ?></div>
      <div class="meta">Purok: <?= h($selectedPurok) ?></div>
      <div class="actions no-print">
        <button class="btn" onclick="window.print()">Print / Save as PDF</button>
      </div>
    </div>

    <div class="section">
      <h2>Monthly Dashboard Summary</h2>
      <div class="kpis">
        <div class="kpi"><div class="label">New Residents</div><div class="value"><?= (int) $summary['new_residents'] ?></div></div>
        <div class="kpi"><div class="label">New Households</div><div class="value"><?= (int) $summary['new_households'] ?></div></div>
        <div class="kpi"><div class="label">Certificates</div><div class="value"><?= (int) $summary['certificates'] ?></div></div>
        <div class="kpi"><div class="label">Blotter Cases</div><div class="value"><?= (int) $summary['blotter'] ?></div></div>
      </div>
    </div>

    <div class="section">
      <h2>Population Breakdown</h2>
      <table>
        <tr><th>Category</th><th>Total</th></tr>
        <tr><td>Male</td><td><?= (int) $summary['male'] ?></td></tr>
        <tr><td>Female</td><td><?= (int) $summary['female'] ?></td></tr>
        <tr><td>PWD</td><td><?= (int) $summary['pwd'] ?></td></tr>
        <tr><td>Senior Citizens</td><td><?= (int) $summary['seniors'] ?></td></tr>
        <tr><td>0-12 (Children)</td><td><?= (int) $summary['age_child'] ?></td></tr>
        <tr><td>13-17 (Youth)</td><td><?= (int) $summary['age_youth'] ?></td></tr>
        <tr><td>18-59 (Adults)</td><td><?= (int) $summary['age_adult'] ?></td></tr>
        <tr><td>60+ (Senior)</td><td><?= (int) $summary['age_senior'] ?></td></tr>
      </table>
    </div>

    <div class="section">
      <h2>Certificate Status</h2>
      <table>
        <tr><th>Status</th><th>Total</th></tr>
        <tr><td>Received</td><td><?= (int) $certificateStatus['received'] ?></td></tr>
        <tr><td>On Going</td><td><?= (int) $certificateStatus['ongoing'] ?></td></tr>
        <tr><td>Cancelled</td><td><?= (int) $certificateStatus['cancelled'] ?></td></tr>
      </table>
    </div>

    <div class="section">
      <h2>Blotter Status</h2>
      <table>
        <tr><th>Status</th><th>Total</th></tr>
        <tr><td>Settled</td><td><?= (int) $blotterStatus['settled'] ?></td></tr>
        <tr><td>Received</td><td><?= (int) $blotterStatus['received'] ?></td></tr>
        <tr><td>On Going</td><td><?= (int) $blotterStatus['ongoing'] ?></td></tr>
        <tr><td>Cancelled</td><td><?= (int) $blotterStatus['cancelled'] ?></td></tr>
      </table>
    </div>

    <div class="section">
      <h2>Officials</h2>
      <table>
        <tr><th>Metric</th><th>Total</th></tr>
        <tr><td>Active Officials</td><td><?= (int) $summary['officials_active'] ?></td></tr>
        <tr><td>Vacant Positions</td><td><?= (int) $summary['officials_vacant'] ?></td></tr>
        <tr><td>Expiring Terms (60 days)</td><td><?= (int) $summary['officials_expiring'] ?></td></tr>
      </table>
    </div>
  </div>
</body>
</html>
  <?php
  exit;
}

include __DIR__ . '/../../views/layouts/header.php';
include __DIR__ . '/../../views/layouts/sidebar.php';
include __DIR__ . '/../../app/config/topbar_profile.php';
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Reports</div>
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
    <style>
      .report-chip-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
      .report-chip{display:inline-flex;align-items:center;gap:10px;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:800}
      .report-chip strong{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 7px;border-radius:999px;background:rgba(255,255,255,.85);font-size:12px}
      .report-chip-received{background:#d9f7df;color:#0b6b2a}
      .report-chip-ongoing{background:#fff7cc;color:#9a7b00}
      .report-chip-cancelled{background:#ffd7d7;color:#a10000}
      .report-chip-settled{background:#d9f7df;color:#0b6b2a}
      .report-chip-info{background:#e3f2fd;color:#1565c0}

      .report-trend-fallback{display:none}
      .report-trend-fallback.show{display:block}
      .report-trend-shell{margin-top:8px;border:1px solid var(--border);border-radius:14px;background:#fafbff;padding:12px}
      .report-trend-scroll{overflow-x:auto;padding-bottom:4px}
      .report-trend-grid{display:grid;grid-template-columns:repeat(12,44px);gap:12px;align-items:end;height:220px;min-width:660px}
      .report-trend-col{display:flex;flex-direction:column;align-items:center;gap:8px}
      .report-trend-bar-wrap{height:170px;display:flex;align-items:flex-end;justify-content:center}
      .report-trend-bar{width:16px;border-radius:8px 8px 4px 4px;background:linear-gradient(180deg,#6c63ff,#948dff);min-height:4px}
      .report-trend-val{font-size:11px;font-weight:800;color:var(--text-soft);line-height:1}
      .report-trend-label{font-size:11px;font-weight:800;color:var(--muted);line-height:1}
      .report-trend-empty{font-size:12px;color:var(--muted);font-weight:700;margin-top:10px}
    </style>

    <div class="card">
      <div class="table-header">
        <div>
          <div class="card-title">Reports Center</div>
          <div class="card-sub">Summarized analytics by date range and purok</div>
        </div>
        <div class="table-actions">
          <button class="btn btn-light" type="button" onclick="window.print()">Print</button>
          <a class="btn btn-light" href="/BarangaySystem/public/reports/index.php?from=<?= urlencode($selectedFrom) ?>&to=<?= urlencode($selectedTo) ?>&purok=<?= urlencode($selectedPurok) ?>&export=csv">Export CSV</a>
          <a class="btn btn-primary" href="/BarangaySystem/public/reports/index.php?from=<?= urlencode($selectedFrom) ?>&to=<?= urlencode($selectedTo) ?>&purok=<?= urlencode($selectedPurok) ?>&export=pdf" target="_blank" rel="noopener">Export PDF</a>
        </div>
      </div>

      <form method="get" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Date From</label>
          <input class="input" style="width:100%;min-width:0;" type="date" name="from" value="<?= htmlspecialchars($selectedFrom) ?>">
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Date To</label>
          <input class="input" style="width:100%;min-width:0;" type="date" name="to" value="<?= htmlspecialchars($selectedTo) ?>">
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:800;color:var(--text-soft);">Purok</label>
          <select class="input" style="width:100%;min-width:0;" name="purok">
            <?php foreach ($puroks as $p): ?>
            <option value="<?= htmlspecialchars($p) ?>" <?= $p === $selectedPurok ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:flex;align-items:flex-end;gap:8px;">
          <button class="btn btn-primary" type="submit">Apply Filters</button>
          <a class="btn btn-light" href="/BarangaySystem/public/reports/index.php">Reset</a>
        </div>
      </form>

      <?php if ($errorMessage !== ''): ?>
      <div style="margin-top:10px;padding:10px 12px;border-radius:10px;background:#ffe9e9;border:1px solid #ffd0d0;color:#a10000;font-size:12px;font-weight:800;">
        <?= htmlspecialchars($errorMessage) ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value"><?= number_format($summary['new_residents']) ?></div>
        <div class="stat-label">New Residents (Period)</div>
        <div class="stat-change neu">Male: <?= $summary['male'] ?> | Female: <?= $summary['female'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= number_format($summary['new_households']) ?></div>
        <div class="stat-label">New Households (Period)</div>
        <div class="stat-change neu">Active: <?= $summary['households_active'] ?> | Inactive: <?= $summary['households_inactive'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= number_format($summary['certificates']) ?></div>
        <div class="stat-label">Certificates (Period)</div>
        <div class="stat-change neu">Received: <?= $certificateStatus['received'] ?> | On Going: <?= $certificateStatus['ongoing'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= number_format($summary['blotter']) ?></div>
        <div class="stat-label">Blotter Cases (Period)</div>
        <div class="stat-change neu">Settled: <?= $blotterStatus['settled'] ?> | Cancelled: <?= $blotterStatus['cancelled'] ?></div>
      </div>
    </div>

    <div class="charts-row">
      <div class="chart-card">
        <div class="chart-header">
          <div>
            <div class="card-title">Monthly Certificate Trend</div>
            <div class="card-sub">Issued certificates per month (current year)</div>
          </div>
        </div>
        <canvas id="reportTrendChart" height="120"></canvas>
        <div id="reportTrendFallback" class="report-trend-fallback">
          <div class="report-trend-shell">
            <?php if ($maxMonthlyCount <= 0): ?>
            <div class="report-trend-empty">No certificate activity for this year yet.</div>
            <?php else: ?>
            <div class="report-trend-scroll">
              <div class="report-trend-grid">
              <?php foreach ($monthlyCertificates as $i => $count): ?>
              <?php $height = $count > 0 ? (int) max(12, round(($count / $maxMonthlyCount) * 160)) : 4; ?>
              <div class="report-trend-col">
                <div class="report-trend-val"><?= $count > 0 ? (int) $count : '' ?></div>
                <div class="report-trend-bar-wrap">
                  <div class="report-trend-bar" style="height:<?= $height ?>px;opacity:<?= $count > 0 ? '1' : '.35' ?>"></div>
                </div>
                <div class="report-trend-label"><?= htmlspecialchars($monthLabels[$i]) ?></div>
              </div>
              <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="table-header">
          <div>
            <div class="card-title">Population Breakdown</div>
            <div class="card-sub">Gender, PWD, seniors, and age groups in selected range</div>
          </div>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Category</th>
                <th>Total Count</th>
              </tr>
            </thead>
            <tbody>
              <tr><td><strong>Male</strong></td><td><?= number_format($summary['male']) ?></td></tr>
              <tr><td><strong>Female</strong></td><td><?= number_format($summary['female']) ?></td></tr>
              <tr><td><strong>PWD</strong></td><td><?= number_format($summary['pwd']) ?></td></tr>
              <tr><td><strong>Senior Citizens</strong></td><td><?= number_format($summary['seniors']) ?></td></tr>
              <tr><td><strong>0-12 (Children)</strong></td><td><?= number_format($summary['age_child']) ?></td></tr>
              <tr><td><strong>13-17 (Youth)</strong></td><td><?= number_format($summary['age_youth']) ?></td></tr>
              <tr><td><strong>18-59 (Adults)</strong></td><td><?= number_format($summary['age_adult']) ?></td></tr>
              <tr><td><strong>60+ (Senior)</strong></td><td><?= number_format($summary['age_senior']) ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="charts-row">
      <div class="card">
        <div class="table-header">
          <div>
            <div class="card-title">Certificate Type Report</div>
            <div class="card-sub">Top requested types in selected range</div>
          </div>
        </div>
        <div class="report-chip-row">
          <?= reportStatusChip('Received', (int) $certificateStatus['received'], 'received') ?>
          <?= reportStatusChip('On Going', (int) $certificateStatus['ongoing'], 'ongoing') ?>
          <?= reportStatusChip('Cancelled', (int) $certificateStatus['cancelled'], 'cancelled') ?>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Certificate Type</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($certificateRows as $row): ?>
              <tr>
                <td><strong><?= htmlspecialchars((string) $row['label']) ?></strong></td>
                <td><?= number_format((int) $row['total']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (count($certificateRows) === 0): ?>
              <tr><td colspan="2" class="muted">No certificate records in selected range.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="table-header">
          <div>
            <div class="card-title">Blotter Category Report</div>
            <div class="card-sub">Top complaint categories in selected range</div>
          </div>
        </div>
        <div class="report-chip-row">
          <?= reportStatusChip('Settled', (int) $blotterStatus['settled'], 'settled') ?>
          <?= reportStatusChip('Received', (int) $blotterStatus['received'], 'info') ?>
          <?= reportStatusChip('On Going', (int) $blotterStatus['ongoing'], 'ongoing') ?>
          <?= reportStatusChip('Cancelled', (int) $blotterStatus['cancelled'], 'cancelled') ?>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Case Category</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($blotterRows as $row): ?>
              <tr>
                <td><strong><?= htmlspecialchars((string) $row['label']) ?></strong></td>
                <td><?= number_format((int) $row['total']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (count($blotterRows) === 0): ?>
              <tr><td colspan="2" class="muted">No blotter records in selected range.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="charts-row">
      <div class="card">
        <div class="table-header">
          <div>
            <div class="card-title">Officials Report</div>
            <div class="card-sub">Active, vacant, expiring terms and committee distribution</div>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:12px;">
          <div class="stat-card" style="padding:12px;">
            <div class="stat-value" style="font-size:22px;"><?= number_format($summary['officials_active']) ?></div>
            <div class="stat-label">Active Officials</div>
          </div>
          <div class="stat-card" style="padding:12px;">
            <div class="stat-value" style="font-size:22px;"><?= number_format($summary['officials_vacant']) ?></div>
            <div class="stat-label">Vacant Positions</div>
          </div>
          <div class="stat-card" style="padding:12px;">
            <div class="stat-value" style="font-size:22px;"><?= number_format($summary['officials_expiring']) ?></div>
            <div class="stat-label">Expiring (60 days)</div>
          </div>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Committee</th>
                <th>Total Officials</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($committeeRows as $row): ?>
              <tr>
                <td><strong><?= htmlspecialchars((string) $row['label']) ?></strong></td>
                <td><?= number_format((int) $row['total']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (count($committeeRows) === 0): ?>
              <tr><td colspan="2" class="muted">No officials data found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="table-header">
          <div>
            <div class="card-title">Monthly Dashboard Summary</div>
            <div class="card-sub">Quick monthly analytics snapshot</div>
          </div>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Metric</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr><td><strong>New Residents this month</strong></td><td><?= number_format($summary['new_residents']) ?></td></tr>
              <tr><td><strong>Certificates issued this month</strong></td><td><?= number_format($summary['certificates']) ?></td></tr>
              <tr><td><strong>Blotter cases this month</strong></td><td><?= number_format($summary['blotter']) ?></td></tr>
              <tr><td><strong>Households added this month</strong></td><td><?= number_format($summary['new_households']) ?></td></tr>
              <tr><td><strong>PWD this month</strong></td><td><?= number_format($summary['pwd']) ?></td></tr>
              <tr><td><strong>Senior citizens this month</strong></td><td><?= number_format($summary['seniors']) ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const trendCanvas = document.getElementById('reportTrendChart');
const trendFallback = document.getElementById('reportTrendFallback');

if (trendCanvas && typeof Chart !== 'undefined') {
  new Chart(trendCanvas, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      datasets: [{
        label: 'Certificates Issued',
        data: <?= json_encode($monthlyCertificates) ?>,
        borderColor: '#6c63ff',
        backgroundColor: 'rgba(108,99,255,.12)',
        borderWidth: 3,
        fill: true,
        tension: 0.35,
        pointRadius: 4,
        pointBackgroundColor: '#6c63ff'
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { family: 'Nunito', weight: '700', size: 12 } } },
        y: { grid: { color: '#f0f0f8' }, ticks: { precision: 0, font: { family: 'Nunito', weight: '700', size: 11 } } }
      }
    }
  });
  if (trendFallback) trendFallback.classList.remove('show');
} else {
  if (trendCanvas) trendCanvas.style.display = 'none';
  if (trendFallback) trendFallback.classList.add('show');
}
</script>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

