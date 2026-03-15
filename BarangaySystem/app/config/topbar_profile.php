<?php
$topbarProfile = [
  'full_name' => 'Admin',
  'role' => 'Barangay Official',
];

try {
  if (function_exists('getCurrentAdmin')) {
    $row = getCurrentAdmin();
  } else {
    require_once __DIR__ . '/../middleware/auth.php';
    $row = getCurrentAdmin();
  }

  if ($row) {
    $topbarProfile['full_name'] = (string) ($row['full_name'] ?? $topbarProfile['full_name']);
    $topbarProfile['role'] = (string) ($row['role'] ?? $topbarProfile['role']);
  }
} catch (Throwable $e) {
  // Keep defaults when profile table/database is not available.
}
