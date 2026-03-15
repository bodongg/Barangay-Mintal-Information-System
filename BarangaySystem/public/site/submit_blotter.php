<?php
require_once __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /BarangaySystem/public/site/index.php');
  exit;
}

$blotterTypes = [
  'Family conflict',
  'Neighbor dispute',
  'Property boundary issue',
  'Verbal argument',
  'Noise complaint',
  'Public intoxication',
  'Alarm & scandal',
  'Theft',
  'Physical injury',
  'Threat',
  'Harassment',
  'Land conflict',
  'Child custody issue',
  'Lost & found',
];

$name = trim((string) ($_POST['name'] ?? ''));
$blotterType = trim((string) ($_POST['blotter_type'] ?? ''));
$contactNumber = trim((string) ($_POST['contact_number'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$incidentDetails = trim((string) ($_POST['incident_details'] ?? ''));
$date = trim((string) ($_POST['date'] ?? date('Y-m-d')));
$purok = trim((string) ($_POST['purok'] ?? ''));
$status = trim((string) ($_POST['status'] ?? 'ongoing'));

if (
  $name === '' ||
  !in_array($blotterType, $blotterTypes, true) ||
  $address === '' ||
  !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
  $purok === '' ||
  !in_array($status, ['ongoing', 'received', 'cancelled', 'settled'], true)
) {
  header('Location: /BarangaySystem/public/site/index.php?err=' . urlencode('Blotter report failed. Please complete all required fields.'));
  exit;
}

try {
  $stmt = db()->prepare("
    INSERT INTO blotter_records (name, blotter_type, contact_number, address, incident_details, date, purok, status)
    VALUES (:name, :blotter_type, :contact_number, :address, :incident_details, :date, :purok, :status)
  ");
  $stmt->execute([
    'name' => $name,
    'blotter_type' => $blotterType,
    'contact_number' => $contactNumber,
    'address' => $address,
    'incident_details' => $incidentDetails === '' ? null : $incidentDetails,
    'date' => $date,
    'purok' => $purok,
    'status' => $status,
  ]);
  header('Location: /BarangaySystem/public/site/index.php?msg=' . urlencode('Blotter report submitted successfully.'));
  exit;
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/site/index.php?err=' . urlencode('Blotter report failed. Please check your database setup.'));
  exit;
}
