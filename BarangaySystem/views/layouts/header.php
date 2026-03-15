<?php
// header.php
// Use $pageTitle from the page (dashboard.php) if set
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : "Barangay System"; ?></title>
  
  <!-- External Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  
  <!-- Your dashboard CSS -->
  <link rel="stylesheet" href="/BarangaySystem/assets/css/dashboard.css?v=<?= urlencode((string) @filemtime(__DIR__ . '/../../assets/css/dashboard.css')) ?>">
  <link rel="stylesheet" href="/BarangaySystem/assets/css/tables.css">

  <!-- Chart.js (needed for your charts) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/chart.js/4.4.1/chart.umd.min.js"></script>
  <script src="/BarangaySystem/assets/js/asset.js?v=1" defer></script>
</head>
<body>
<div class="layout">   <!-- starts layout wrapper -->
