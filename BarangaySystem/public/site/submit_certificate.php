<?php
require_once __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /BarangaySystem/public/site/index.php');
  exit;
}

$certificateTypes = [
  'Barangay Clearance',
  'Certificate of Residency',
  'Certificate of Indigency',
  'Certificate of Good Moral Character',
  'Certificate of Cohabitation',
  'Certificate of No Objection',
  'Certificate of Solo Parent',
  'Certificate of Guardianship',
  'Certificate of Non-Residency',
  'Certificate of Unemployment',
  'Barangay Business Clearance',
  'Construction Clearance',
  'Burial Assistance Certificate',
  'Scholarship Requirement Certificate',
];

$residentName = trim((string) ($_POST['resident_name'] ?? ''));
$requestDate = trim((string) ($_POST['request_date'] ?? date('Y-m-d')));
$certificateType = trim((string) ($_POST['certificate_type'] ?? ''));
$status = trim((string) ($_POST['status'] ?? 'ongoing'));
$mobileNo = trim((string) ($_POST['mobile_no'] ?? ''));
$purok = trim((string) ($_POST['purok'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));

if (
  $residentName === '' ||
  !preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestDate) ||
  !in_array($certificateType, $certificateTypes, true) ||
  !in_array($status, ['ongoing', 'received', 'cancelled'], true) ||
  $purok === '' ||
  $address === ''
) {
  header('Location: /BarangaySystem/public/site/index.php?err=' . urlencode('Certificate request failed. Please complete all required fields.'));
  exit;
}

try {
  $stmt = db()->prepare("
    INSERT INTO certificates (resident_name, request_date, certificate_type, status, mobile_no, purok, address)
    VALUES (:resident_name, :request_date, :certificate_type, :status, :mobile_no, :purok, :address)
  ");
  $stmt->execute([
    'resident_name' => $residentName,
    'request_date' => $requestDate,
    'certificate_type' => $certificateType,
    'status' => $status,
    'mobile_no' => $mobileNo,
    'purok' => $purok,
    'address' => $address,
  ]);
  header('Location: /BarangaySystem/public/site/index.php?msg=' . urlencode('Certificate request submitted successfully.'));
  exit;
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/site/index.php?err=' . urlencode('Certificate request failed. Please check your database setup.'));
  exit;
}
