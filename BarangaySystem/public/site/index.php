<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/officials.php';

if (empty($_SESSION['site_user_id'])) {
  header('Location: /BarangaySystem/public/user/auth/login.php?err=' . urlencode('Please login first.'));
  exit;
}
/* PUBLIC WEBSITE PAGE (FOR DEFENSE) */
$publicMessage = trim((string) ($_GET['msg'] ?? ''));
$publicError = trim((string) ($_GET['err'] ?? ''));
$publicAnnouncements = [];
$publicOfficials = [];
$siteTotalResidents = 0;
$siteTotalHouseholds = 0;
$siteTotalCertificates = 0;
$siteTotalOfficials = 0;
$siteTotalPuroks = 6;
$siteUser = [
  'id' => (int) $_SESSION['site_user_id'],
  'account_id' => (string) ($_SESSION['site_account_id'] ?? ''),
  'full_name' => (string) ($_SESSION['site_user_name'] ?? 'User'),
  'username' => (string) ($_SESSION['site_username'] ?? ''),
  'email' => '',
];

try {
  $userStmt = db()->prepare("
    SELECT id, account_id, full_name, username, email
    FROM site_users
    WHERE id = :id
    LIMIT 1
  ");
  $userStmt->execute(['id' => (int) $_SESSION['site_user_id']]);
  $userRow = $userStmt->fetch();

  if (!$userRow) {
    session_unset();
    session_destroy();
    header('Location: /BarangaySystem/public/user/auth/login.php?err=' . urlencode('Session expired. Please login again.'));
    exit;
  }

  $siteUser = [
    'id' => (int) ($userRow['id'] ?? 0),
    'account_id' => (string) ($userRow['account_id'] ?? ''),
    'full_name' => (string) ($userRow['full_name'] ?? ''),
    'username' => (string) ($userRow['username'] ?? ''),
    'email' => (string) ($userRow['email'] ?? ''),
  ];

  try {
    $siteTotalResidents = (int) db()->query("
      SELECT COUNT(*) FROM residents
      WHERE COALESCE(is_archived, 0) = 0
    ")->fetchColumn();
  } catch (Throwable $e) {
    $siteTotalResidents = (int) db()->query("SELECT COUNT(*) FROM residents")->fetchColumn();
  }

  try {
    $siteTotalHouseholds = (int) db()->query("
      SELECT COUNT(*) FROM households
      WHERE COALESCE(is_archived, 0) = 0
    ")->fetchColumn();
  } catch (Throwable $e) {
    $siteTotalHouseholds = (int) db()->query("SELECT COUNT(*) FROM households")->fetchColumn();
  }

  try {
    $siteTotalCertificates = (int) db()->query("
      SELECT COUNT(*) FROM certificates
      WHERE COALESCE(is_archived, 0) = 0
    ")->fetchColumn();
  } catch (Throwable $e) {
    $siteTotalCertificates = (int) db()->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
  }

  try {
    $siteTotalOfficials = (int) db()->query("
      SELECT COUNT(*) FROM officials
      WHERE COALESCE(is_archived, 0) = 0
        AND LOWER(COALESCE(status, '')) = 'active'
    ")->fetchColumn();
  } catch (Throwable $e) {
    $siteTotalOfficials = (int) db()->query("
      SELECT COUNT(*) FROM officials
      WHERE LOWER(COALESCE(status, '')) = 'active'
    ")->fetchColumn();
  }

  try {
    $officialsStmt = db()->query("
      SELECT full_name, position, official_photo
      FROM officials
      WHERE COALESCE(is_archived, 0) = 0
        AND LOWER(COALESCE(status, '')) = 'active'
      ORDER BY
        CASE
          WHEN LOWER(position) = 'barangay captain' THEN 1
          WHEN LOWER(position) = 'barangay secretary' THEN 2
          WHEN LOWER(position) = 'barangay treasurer' THEN 3
          WHEN LOWER(position) = 'sk chairperson' THEN 4
          WHEN LOWER(position) = 'barangay kagawad' THEN 5
          WHEN LOWER(position) = 'barangay tanod' THEN 6
          ELSE 99
        END,
        full_name ASC,
        id DESC
      LIMIT 8
    ");
  } catch (Throwable $e) {
    $officialsStmt = db()->query("
      SELECT full_name, position, official_photo
      FROM officials
      WHERE LOWER(COALESCE(status, '')) = 'active'
      ORDER BY
        CASE
          WHEN LOWER(position) = 'barangay captain' THEN 1
          WHEN LOWER(position) = 'barangay secretary' THEN 2
          WHEN LOWER(position) = 'barangay treasurer' THEN 3
          WHEN LOWER(position) = 'sk chairperson' THEN 4
          WHEN LOWER(position) = 'barangay kagawad' THEN 5
          WHEN LOWER(position) = 'barangay tanod' THEN 6
          ELSE 99
        END,
        full_name ASC,
        id DESC
      LIMIT 8
    ");
  }

  try {
    foreach ($officialsStmt->fetchAll() as $row) {
      $publicOfficials[] = [
        'full_name' => (string) ($row['full_name'] ?? ''),
        'position' => (string) ($row['position'] ?? ''),
        'official_photo' => (string) ($row['official_photo'] ?? ''),
      ];
    }
  } catch (Throwable $e) {
    $publicOfficials = [];
  }

  try {
    $stmt = db()->query("
      SELECT category, title, description, event_date, location
      FROM announcements
      WHERE LOWER(status) = 'active'
        AND COALESCE(is_archived, 0) = 0
      ORDER BY event_date DESC, id DESC
      LIMIT 3
    ");

    foreach ($stmt->fetchAll() as $row) {
      $category = trim((string) ($row['category'] ?? 'Meeting'));
      $categoryKey = strtolower($category);

      $barClass = 'blue';
      $tagClass = 'tag-meeting';
      if (in_array($categoryKey, ['fiesta', 'event'], true)) {
        $barClass = 'green';
        $tagClass = 'tag-event';
      } elseif (in_array($categoryKey, ['health', 'medical'], true)) {
        $barClass = 'gold';
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
        $meta = $meta !== '' ? ($meta . ' - ' . $location) : $location;
      }

      $publicAnnouncements[] = [
        'category' => $category !== '' ? $category : 'Meeting',
        'title' => (string) ($row['title'] ?? ''),
        'description' => (string) ($row['description'] ?? ''),
        'meta' => $meta,
        'bar_class' => $barClass,
        'tag_class' => $tagClass,
      ];
    }
  } catch (Throwable $e) {
    $publicAnnouncements = [];
  }
} catch (Throwable $e) {
  // Keep public site running even if some database sections are not ready yet.
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Barangay Mintal - Official Website</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/BarangaySystem/assets/site/css/public-website.css?v=<?= urlencode((string) @filemtime(__DIR__ . '/../../assets/site/css/public-website.css')) ?>"/>
  <script src="/BarangaySystem/assets/site/js/public-website.js?v=<?= urlencode((string) @filemtime(__DIR__ . '/../../assets/site/js/public-website.js')) ?>" defer></script>
</head>
<body>
<!-- PUBLIC WEBSITE CONTENT (FOR DEFENSE) -->
<?php if ($publicMessage !== '' || $publicError !== ''): ?>
<div style="max-width:1140px;margin:14px auto 0;padding:0 48px;">
  <?php if ($publicMessage !== ''): ?>
  <div style="padding:10px 14px;border-radius:10px;background:#e9f7ef;border:1px solid #b7e4c7;color:#116530;font-size:13px;font-weight:700;">
    <?= htmlspecialchars($publicMessage) ?>
  </div>
  <?php endif; ?>
  <?php if ($publicError !== ''): ?>
  <div style="padding:10px 14px;border-radius:10px;background:#ffe9e9;border:1px solid #ffd0d0;color:#a10000;font-size:13px;font-weight:700;">
    <?= htmlspecialchars($publicError) ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>
<!-- -- NAVBAR -- -->
<nav class="navbar">
  <a class="nav-brand" href="/BarangaySystem/public/site/index.php" aria-label="Go to homepage">
    <img class="nav-logo" src="/BarangaySystem/assets/images/LOGO%20BARANGAY.png" alt="Barangay Logo"/>
  </a>
  <div class="nav-links">
    <a class="nav-link" href="#services">Services</a>
    <a class="nav-link" href="#agenda">Agenda</a>
    <a class="nav-link" href="#news">News</a>
    <a class="nav-link" href="#officials">Officials</a>
    <a class="nav-link" href="#contact">Contact</a>
    <div style="display:flex;align-items:center;gap:6px;margin-left:8px;border-left:1px solid var(--border);padding-left:14px;">
      <a href="https://www.facebook.com/mintallittletokyo" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:12px;font-weight:700;padding:7px 13px;border-radius:8px;background:var(--primary);transition:opacity .15s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
        Facebook
      </a>
      <a href="#" style="display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:12px;font-weight:700;padding:7px 13px;border-radius:8px;background:var(--primary);transition:opacity .15s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
        Twitter
      </a>
      <a href="/BarangaySystem/public/site/profile.php" style="display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:12px;font-weight:700;padding:7px 13px;border-radius:8px;background:var(--primary);transition:opacity .15s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        My Profile
      </a>
      <a href="/BarangaySystem/public/user/auth/logout.php" style="display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:12px;font-weight:700;padding:7px 13px;border-radius:8px;background:#0a173c;transition:opacity .15s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">Logout</a>
    </div>
  </div>
</nav>

<!-- -- HERO -- -->
<section class="hero">
  <div class="hero-badge">Serving Our Community Since 1945</div>
  <h1>Welcome to <span>Barangay Mintal</span></h1>
  <p class="hero-sub">Your one-stop portal for barangay services, announcements, certificate requests, and community updates.</p>
</section>

<!-- -- STATS -- -->
<div class="stats-strip">
  <div class="stat-item"><div class="num"><?= htmlspecialchars(number_format($siteTotalResidents)) ?></div><div class="lbl">Total Residents</div></div>
  <div class="stat-item"><div class="num"><?= htmlspecialchars(number_format($siteTotalHouseholds)) ?></div><div class="lbl">Households</div></div>
  <div class="stat-item"><div class="num"><?= htmlspecialchars(number_format($siteTotalCertificates)) ?></div><div class="lbl">Certificates Issued</div></div>
  <div class="stat-item"><div class="num"><?= htmlspecialchars(number_format($siteTotalOfficials)) ?></div><div class="lbl">Barangay Officials</div></div>
  <div class="stat-item"><div class="num"><?= htmlspecialchars(number_format($siteTotalPuroks)) ?></div><div class="lbl">Puroks</div></div>
</div>

<!-- -- SERVICES -- -->
<div style="background:var(--bg);">
<section class="section" id="services">
  <div class="section-tag">Our Services</div>
  <div class="section-title">What We Offer</div>
  <p class="section-sub">Quick and easy access to barangay services for all residents of Barangay Mintal.</p>
  <div class="services-grid">
    <div class="service-card" onclick="openModal('certModal')">
      <div class="service-icon">&#128196;</div>
      <div class="service-title">Certificate Request</div>
      <p class="service-desc">Request Barangay Clearance, Certificate of Residency, Indigency, and more online.</p>
      <span class="service-link">Request Now -></span>
    </div>
    <div class="service-card" onclick="location.href='#announcements'">
      <div class="service-icon">&#128226;</div>
      <div class="service-title">Announcements</div>
      <p class="service-desc">Stay updated with the latest barangay news, events, health missions, and seminars.</p>
      <a class="service-link" href="#announcements">View All -></a>
    </div>
    <div class="service-card" onclick="openModal('registerModal')">
      <div class="service-icon">&#128221;</div>
      <div class="service-title">Resident Registration</div>
      <p class="service-desc">Register yourself or your family as an official resident of Barangay Mintal.</p>
      <span class="service-link">Register Now -></span>
    </div>
    <div class="service-card" onclick="openModal('blotterModal')">
      <div class="service-icon">&#128221;</div>
      <div class="service-title">Blotter Report</div>
      <p class="service-desc">File an incident report or check the status of your existing blotter case.</p>
      <span class="service-link">File Report -></span>
    </div>
    <div class="service-card" onclick="location.href='#officials'">
      <div class="service-icon">&#128101;</div>
      <div class="service-title">Barangay Officials</div>
      <p class="service-desc">Meet your elected barangay officials and know who to approach for your concerns.</p>
      <a class="service-link" href="#officials">Meet Officials -></a>
    </div>
    <div class="service-card" onclick="location.href='#contact'">
      <div class="service-icon">&#128222;</div>
      <div class="service-title">Contact & Support</div>
      <p class="service-desc">Reach the barangay hall for questions, concerns, or emergency assistance.</p>
      <a class="service-link" href="#contact">Contact Us -></a>
    </div>
  </div>
</section>
</div>

<!-- -- PRIORITY AGENDA -- -->
<section class="agenda-section" id="agenda">
  <div class="agenda-inner">
    <div style="text-align:center;">
      <div class="section-tag">Our Commitment</div>
      <div class="section-title">Barangay Priority Agenda</div>
    </div>
    <div class="agenda-grid">
      <div class="agenda-item"><div class="agenda-icon">&#129309;</div><div class="agenda-label">Poverty Alleviation</div></div>
      <div class="agenda-item"><div class="agenda-icon">&#127959;&#65039;</div><div class="agenda-label">Infrastructure Development</div></div>
      <div class="agenda-item"><div class="agenda-icon">&#127806;</div><div class="agenda-label">Agriculture and Agribusiness</div></div>
      <div class="agenda-item"><div class="agenda-icon">&#127793;</div><div class="agenda-label">Sustainable Environment</div></div>
      <div class="agenda-item"><div class="agenda-icon">&#129658;</div><div class="agenda-label">Health</div></div>
      <div class="agenda-item"><div class="agenda-icon">&#128218;</div><div class="agenda-label">Education and Human Resource Development</div></div>
      <div class="agenda-item"><div class="agenda-icon">&#127970;</div><div class="agenda-label">Business and Industrial Support Development</div></div>
      <div class="agenda-item"><div class="agenda-icon">&#128737;&#65039;</div><div class="agenda-label">Peace and Order</div></div>
      <div class="agenda-item"><div class="agenda-icon">&#128161;</div><div class="agenda-label">Good Governance Through Innovative ICT</div></div>
      <div class="agenda-item"><div class="agenda-icon">&#128678;</div><div class="agenda-label">Transportation Planning and Traffic Management</div></div>
      <div class="agenda-item"><div class="agenda-icon">&#129517;</div><div class="agenda-label">Tourism and Development Support Services</div></div>
      <div class="agenda-item"><div class="agenda-icon">&#128680;</div><div class="agenda-label">Disaster Risk Reduction and Mitigation</div></div>
    </div>
  </div>
</section>

<!-- -- OFFICES & DEPARTMENTS -- -->
<div class="offices-wrapper">
  <div class="offices-inner-wrap">
    <div class="offices-text">
      <div class="section-tag">Organization</div>
      <div class="section-title">Departments & Offices</div>
      <p>The Barangay Mintal departments and offices work hand in hand to deliver programs and services to our community.</p>
      <ul class="offices-list">
        <li>Office of the Punong Barangay</li>
        <li>Peace and Order Committee</li>
        <li>Health and Sanitation Office</li>
        <li>Lupon Tagapamayapa</li>
        <li>SK (Sangguniang Kabataan)</li>
      </ul>
      <a class="btn-outline-white" href="#officials">View Officials</a>
    </div>
  </div>
</div>

<!-- -- LATEST NEWS -- -->
<section class="news-bg" id="news">
  <div class="news-inner">
    <div class="news-header">
      <div class="section-title">Latest News</div>
    </div>
    <div class="news-grid">
      <a class="news-card" href="https://www.sunstar.com.ph/davao/prayers-urged-for-duterte-amid-icc-hearing" target="_blank" rel="noopener noreferrer" title="Prayers urged for Duterte amid ICC hearing">
        <div class="news-card-bg c1">N1</div>
        <div class="news-overlay">
          <div class="news-date">February 28, 2026</div>
          <div class="news-title">Prayers urged for Duterte amid ICC hearing</div>
        </div>
      </a>
      <a class="news-card" href="https://www.mindanaotimes.com.ph/march-16-is-special-non-working-day-in-davao-city/" target="_blank" rel="noopener noreferrer" title="March 16 is special non-working day in Davao City">
        <div class="news-card-bg c2">N2</div>
        <div class="news-overlay">
          <div class="news-date">March 16, 2026</div>
          <div class="news-title">March 16 is special non-working day in Davao City</div>
        </div>
      </a>
      <a class="news-card" href="https://www.sunstar.com.ph/davao/dabawenyos-share-concern-on-impending-highest-jump-in-fuel-prices" target="_blank" rel="noopener noreferrer" title="72-hr TRO halts demolition in Matina Crossing">
        <div class="news-card-bg c3">N3</div>
        <div class="news-overlay">
          <div class="news-date">March 9, 2026</div>
          <div class="news-title">Dabawenyos share concern on impending 'highest jump' in fuel prices</div>
        </div>
      </a>
      <a class="news-card" href="https://www.sunstar.com.ph/davao/davao-light-slams-nordecos-misleading-claims-on-samal-power" target="_blank" rel="noopener noreferrer" title="2 separate bomb threats turn out hoaxes">
        <div class="news-card-bg c4">N4</div>
        <div class="news-overlay">
          <div class="news-date">Latest</div>
          <div class="news-title">Davao Light slams Nordeco's 'misleading' claims on Samal Power</div>
        </div>
      </a>
    </div>
    <div class="news-more"> 
      <a class="btn-news-more" href="https://www.sunstar.com.ph/davao" target="_blank" rel="noopener noreferrer">VIEW MORE ARTICLES</a>
    </div>
  </div>
</section>

<!-- -- ANNOUNCEMENTS -- -->
<div style="background:var(--bg);">
<section class="section" id="announcements">
  <div class="section-tag">Bulletin Board</div>
  <div class="section-title">Upcoming Events & Announcements</div>
  <p class="section-sub">Stay informed with the latest happenings in Barangay Mintal.</p>
  <div class="announcements-grid">
    <?php foreach ($publicAnnouncements as $ann): ?>
    <div class="ann-card">
      <div class="ann-bar <?= htmlspecialchars((string) $ann['bar_class']) ?>"></div>
      <div class="ann-body">
        <span class="ann-tag <?= htmlspecialchars((string) $ann['tag_class']) ?>"><?= htmlspecialchars((string) $ann['category']) ?></span>
        <div class="ann-title"><?= htmlspecialchars((string) $ann['title']) ?></div>
        <p class="ann-desc"><?= htmlspecialchars((string) $ann['description']) ?></p>
        <div class="ann-date"><?= htmlspecialchars((string) $ann['meta']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (count($publicAnnouncements) === 0): ?>
    <div class="ann-card">
      <div class="ann-bar blue"></div>
      <div class="ann-body">
        <span class="ann-tag tag-meeting">Notice</span>
        <div class="ann-title">No announcements yet</div>
        <p class="ann-desc">Barangay announcements posted in admin dashboard will appear here.</p>
        <div class="ann-date">Please check back soon</div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
</div>

<!-- -- OFFICIALS -- -->
<section class="officials-section" id="officials">
  <div class="officials-inner">
    <div class="section-tag">Meet the Team</div>
    <div class="section-title">Barangay Officials</div>
    <p class="section-sub">Your elected leaders serving Barangay Mintal.</p>
    <div class="officials-grid">
      <?php foreach ($publicOfficials as $official): ?>
      <div class="official-card">
        <div class="official-avatar" aria-hidden="true">
          <?php if (trim((string) $official['official_photo']) !== ''): ?>
          <img class="official-avatar-img" src="<?= htmlspecialchars((string) $official['official_photo']) ?>" alt="<?= htmlspecialchars((string) $official['full_name']) ?>">
          <?php else: ?>
          <?= htmlspecialchars(strtoupper(substr(trim((string) $official['full_name']), 0, 1) ?: '?')) ?>
          <?php endif; ?>
        </div>
        <div class="official-name"><?= htmlspecialchars((string) $official['full_name']) ?></div>
        <div class="official-pos"><?= htmlspecialchars((string) $official['position']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php if (count($publicOfficials) === 0): ?>
      <div class="official-card">
        <div class="official-avatar" aria-hidden="true">?</div>
        <div class="official-name">No active officials</div>
        <div class="official-pos">Vacant positions will not be shown here.</div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- -- CONTACT -- -->
<div style="background:var(--bg);">
<section class="section" id="contact">
  <div class="section-tag">Contact Us</div>
  <div class="section-title">Get In Touch</div>
  <p class="section-sub">Visit us at the barangay hall or reach us through the following contact details.</p>
  <div class="contact-grid">
    <div class="contact-info">
      <div class="contact-item"><div class="contact-icon">&#128205;</div><div><div class="contact-label">Address</div><div class="contact-value">Barangay Hall, Mintal<br>Davao City, Philippines</div></div></div>
      <div class="contact-item"><div class="contact-icon">&#9742;&#65039;</div><div><div class="contact-label">Hotline</div><div class="contact-value">0917-781-967</div></div></div>
      <div class="contact-item"><div class="contact-icon">&#128338;</div><div><div class="contact-label">Office Hours</div><div class="contact-value">Monday - Friday · 8:00 AM - 5:00 PM</div></div></div>
      <div class="contact-item"><div class="contact-icon" aria-label="Facebook"><svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2" xmlns="http://www.w3.org/2000/svg"><path d="M24 12.073C24 5.404 18.627 0 12 0S0 5.404 0 12.073c0 6.019 4.388 11.009 10.125 11.927v-8.437H7.078V12.07h3.047V9.413c0-3.018 1.792-4.686 4.533-4.686 1.313 0 2.686.235 2.686.235v2.963h-1.514c-1.49 0-1.955.931-1.955 1.887v2.258h3.328l-.532 3.493h-2.796V24C19.612 23.082 24 18.092 24 12.073z"/></svg></div><div><div class="contact-label">Facebook</div><div class="contact-value"><a href="https://www.facebook.com/mintallittletokyo" target="_blank" rel="noopener noreferrer">facebook.com/mintallittletokyo</a></div></div></div>
    </div>
    <div class="map-box">
      <img class="map-box-img" src="/BarangaySystem/assets/officialimages/MAP.png" alt="Barangay map">
    </div> 
  </div>
</section>
</div>

<!-- -- FOOTER -- -->
<footer class="footer">
  <div class="footer-grid">
    <div class="footer-col">
      <div class="footer-col-brand">Barangay Mintal</div>
      <p>Official website of Barangay Mintal. Serving our community with transparency and dedication.</p>
      <div style="margin-top:16px;display:flex;gap:10px;">
        <a href="https://www.facebook.com/mintallittletokyo" target="_blank" rel="noopener noreferrer" style="width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.6);font-size:14px;">f</a>
        <a href="#" style="width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.6);font-size:14px;">@</a>
      </div>
      <a class="footer-admin-login" href="/BarangaySystem/public/adminlogin.php">Admin Login</a>
    </div>
    <div class="footer-col">
      <div class="footer-col-title">About the Barangay</div>
      <div class="footer-links">
        <a href="#">About Us</a>
        <a href="#officials">Barangay Officials</a>
        <a href="#agenda">Priority Agenda</a>
        <a href="#">Barangay History</a>
        <a href="#">Transparency</a>
      </div>
    </div>
    <div class="footer-col">
      <div class="footer-col-title">Services</div>
      <div class="footer-links">
        <a href="#" onclick="openModal('certModal')">Certificate Request</a>
        <a href="#" onclick="openModal('registerModal')">Resident Registration</a>
        <a href="#">Blotter Report</a>
        <a href="#announcements">Announcements</a>
        <a href="#contact">Contact Us</a>
      </div>
    </div>
    <div class="footer-col">
      <div class="footer-col-title">Government Links</div>
      <div class="footer-links">
        <a href="#">Republic of the Philippines</a>
        <a href="#">DILG</a>
        <a href="#">DepEd</a>
        <a href="#">DOH</a>
        <a href="#">DOLE</a>
        <a href="#">DSWD</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <span>Â© 2026 Barangay Mintal. All Rights Reserved.</span>
  </div>
</footer>

<!-- -- CERTIFICATE REQUEST MODAL -- -->
<div class="modal-overlay" id="certModal" onclick="closeBg(event,'certModal')">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('certModal')">?</button>
    <div class="modal-title">Certificate Request</div>
    <div class="modal-sub">Fill in your details â€” pick up at the barangay hall in 1â€“2 days</div>
    <form method="post" action="/BarangaySystem/public/site/submit_certificate.php">
      <div class="template-preview" onclick="openImagePreview('/BarangaySystem/assets/images/Template.jpg','Certificate Request Template')">
        <img class="template-preview-img" src="/BarangaySystem/assets/images/Template.jpg" alt="Certificate request template preview">
        <div class="template-preview-overlay">
          <span class="template-preview-tag">Template Preview (Blurred)</span>
          <span class="template-preview-action">Click to view full image</span>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input class="form-input" type="text" name="resident_name" placeholder="e.g. Juan Dela Cruz" required>
      </div>
      <div class="form-group">
        <label class="form-label">Certificate Type</label>
        <select class="form-select" name="certificate_type" required>
          <option value="">Select certificate type...</option>
          <option value="Barangay Clearance">Barangay Clearance</option>
          <option value="Certificate of Residency">Certificate of Residency</option>
          <option value="Certificate of Indigency">Certificate of Indigency</option>
          <option value="Certificate of Good Moral Character">Certificate of Good Moral Character</option>
          <option value="Certificate of Cohabitation">Certificate of Cohabitation</option>
          <option value="Certificate of No Objection">Certificate of No Objection</option>
          <option value="Certificate of Solo Parent">Certificate of Solo Parent</option>
          <option value="Certificate of Guardianship">Certificate of Guardianship</option>
          <option value="Certificate of Non-Residency">Certificate of Non-Residency</option>
          <option value="Certificate of Unemployment">Certificate of Unemployment</option>
          <option value="Barangay Business Clearance">Barangay Business Clearance</option>
          <option value="Construction Clearance">Construction Clearance</option>
          <option value="Burial Assistance Certificate">Burial Assistance Certificate</option>
          <option value="Scholarship Requirement Certificate">Scholarship Requirement Certificate</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Purok</label>
          <select class="form-select" name="purok" required>
            <option value="">Select purok...</option>
            <option value="Purok 1">Purok 1</option><option value="Purok 2">Purok 2</option><option value="Purok 3">Purok 3</option>
            <option value="Purok 4">Purok 4</option><option value="Purok 5">Purok 5</option><option value="Purok 6">Purok 6</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Mobile No.</label>
          <input class="form-input" type="text" name="mobile_no" placeholder="09xx xxx xxxx">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Purpose</label>
        <input class="form-input" type="text" name="purpose" placeholder="e.g. Employment, Loan, School">
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <input class="form-input" type="text" name="address" placeholder="House No., Street, Barangay San Jose" required>
      </div>
      <input type="hidden" name="status" value="ongoing">
      <input type="hidden" name="request_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>">
      <button class="btn-submit" type="submit">Submit Request</button>
    </form>
  </div>
</div>

<!-- -- RESIDENT REGISTRATION MODAL -- -->
<div class="modal-overlay register-modal" id="registerModal" onclick="closeBg(event,'registerModal')">
  <div class="modal" style="max-width:580px;">
    <button class="modal-close" onclick="closeModal('registerModal')">?</button>
    <div class="modal-title">Resident Registration</div>
    <div class="modal-sub">Register as an official resident of Barangay San Jose</div>
    <form method="post" action="/BarangaySystem/public/site/submit_resident.php">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input class="form-input" type="text" name="name" placeholder="e.g. Juan Dela Cruz" required>
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <input class="form-input" type="text" name="address" placeholder="House No., Street, Barangay San Jose" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Age</label>
          <input class="form-input" type="number" min="0" max="130" name="age" required>
        </div>
        <div class="form-group">
          <label class="form-label">Gender</label>
          <select class="form-select" name="gender" required>
            <option value="">Select gender...</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Prefer not to say">Prefer not to say</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Civil Status</label>
          <select class="form-select" name="civil_status">
            <option value="Single">Single</option><option value="Married">Married</option><option value="Widowed">Widowed</option><option value="Separated">Separated</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Purok</label>
          <select class="form-select" name="purok" required>
            <option value="">Select purok...</option>
            <option value="Purok 1">Purok 1</option><option value="Purok 2">Purok 2</option><option value="Purok 3">Purok 3</option>
            <option value="Purok 4">Purok 4</option><option value="Purok 5">Purok 5</option><option value="Purok 6">Purok 6</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">PWD Type</label>
          <select class="form-select" name="pwd_type">
            <option value="">None</option>
            <option value="psychosocial">Psychosocial</option>
            <option value="learning">Learning</option>
            <option value="mental">Mental</option>
            <option value="visual">Visual</option>
            <option value="orthopedic">Orthopedic</option>
            <option value="physical">Physical</option>
            <option value="speech/language impairment">Speech/Language Impairment</option>
            <option value="deaf/hard of hearing">Deaf/Hard of Hearing</option>
            <option value="chronic illness">Chronic Illness</option>
            <option value="cancer">Cancer</option>
            <option value="rare diseases">Rare Diseases</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Additional Classification</label>
          <select class="form-select" name="additional_classification">
            <option value="None">None</option>
            <option value="Solo Parent">Solo Parent</option>
            <option value="4Ps Beneficiary">4Ps Beneficiary</option>
            <option value="Indigenous People (IP)">Indigenous People (IP)</option>
            <option value="Indigent/ Low Income">Indigent/ Low Income</option>
            <option value="Overseas Filipino Worker (OFW)">Overseas Filipino Worker (OFW)</option>
          </select>
        </div>
      </div>
      <input type="hidden" name="status" value="active">
      <button class="btn-submit" type="submit">Submit Registration</button>
    </form>
  </div>
</div>

<!-- -- BLOTTER REPORT MODAL -- -->
<div class="modal-overlay" id="blotterModal" onclick="closeBg(event,'blotterModal')">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('blotterModal')">?</button>
    <div class="modal-title">Blotter Report</div>
    <div class="modal-sub">Report incidents and concerns for barangay assistance and mediation</div>
    <form method="post" action="/BarangaySystem/public/site/submit_blotter.php">
      <div class="template-preview" onclick="openImagePreview('/BarangaySystem/assets/images/template2.png','Blotter Report Template')">
        <img class="template-preview-img" src="/BarangaySystem/assets/images/template2.png" alt="Blotter report template preview">
        <div class="template-preview-overlay">
          <span class="template-preview-tag">Template Preview (Blurred)</span>
          <span class="template-preview-action">Click to view full image</span>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Complainant Name</label>
        <input class="form-input" type="text" name="name" placeholder="e.g. Juan Dela Cruz" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Contact Number</label>
          <input class="form-input" type="text" name="contact_number" placeholder="09xx xxx xxxx">
        </div>
        <div class="form-group">
          <label class="form-label">Purok</label>
          <select class="form-select" name="purok" required>
            <option value="">Select purok...</option>
            <option value="Purok 1">Purok 1</option><option value="Purok 2">Purok 2</option><option value="Purok 3">Purok 3</option>
            <option value="Purok 4">Purok 4</option><option value="Purok 5">Purok 5</option><option value="Purok 6">Purok 6</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Blotter Type</label>
        <select class="form-select" name="blotter_type" required>
          <option value="">Select blotter type...</option>
          <option value="Family conflict">Family conflict</option>
          <option value="Neighbor dispute">Neighbor dispute</option>
          <option value="Property boundary issue">Property boundary issue</option>
          <option value="Verbal argument">Verbal argument</option>
          <option value="Noise complaint">Noise complaint</option>
          <option value="Public intoxication">Public intoxication</option>
          <option value="Alarm & scandal">Alarm & scandal</option>
          <option value="Theft">Theft</option>
          <option value="Physical injury">Physical injury</option>
          <option value="Threat">Threat</option>
          <option value="Harassment">Harassment</option>
          <option value="Land conflict">Land conflict</option>
          <option value="Child custody issue">Child custody issue</option>
          <option value="Lost & found">Lost & found</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <input class="form-input" type="text" name="address" placeholder="House No., Street, Barangay San Jose" required>
      </div>
      <div class="form-group">
        <label class="form-label">Incident Details</label>
        <input class="form-input" type="text" name="incident_details" placeholder="Briefly describe the incident (optional)">
      </div>
      <input type="hidden" name="status" value="ongoing">
      <input type="hidden" name="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>">
      <button class="btn-submit" type="submit">Submit Report</button>
    </form>
  </div>
</div>

<!-- -- TEMPLATE IMAGE PREVIEW MODAL -- -->
<div class="modal-overlay image-preview-modal" id="imagePreviewModal" onclick="closeBg(event,'imagePreviewModal')">
  <div class="modal image-preview-content">
    <button class="modal-close" onclick="closeModal('imagePreviewModal')">?</button>
    <div class="modal-title" id="imagePreviewTitle">Template Preview</div>
    <img id="imagePreviewElement" class="image-preview-full" src="" alt="Template preview">
  </div>
</div>
</body>
</html>

