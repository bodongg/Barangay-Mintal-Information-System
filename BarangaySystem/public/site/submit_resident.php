<?php
require_once __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /BarangaySystem/public/site/index.php');
  exit;
}

$pwdTypes = [
  'psychosocial',
  'learning',
  'mental',
  'visual',
  'orthopedic',
  'physical',
  'speech/language impairment',
  'deaf/hard of hearing',
  'chronic illness',
  'cancer',
  'rare diseases',
];

$classificationOptions = [
  'None',
  'Solo Parent',
  '4Ps Beneficiary',
  'Indigenous People (IP)',
  'Indigent/ Low Income',
  'Overseas Filipino Worker (OFW)',
];

$name = trim((string) ($_POST['name'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$gender = trim((string) ($_POST['gender'] ?? ''));
$age = trim((string) ($_POST['age'] ?? ''));
$civilStatus = trim((string) ($_POST['civil_status'] ?? 'Single'));
$purok = trim((string) ($_POST['purok'] ?? ''));
$status = trim((string) ($_POST['status'] ?? 'active'));
$pwdType = trim((string) ($_POST['pwd_type'] ?? ''));
$additionalClassification = trim((string) ($_POST['additional_classification'] ?? 'None'));

if (
  $name === '' ||
  $address === '' ||
  !in_array($gender, ['Male', 'Female', 'Prefer not to say'], true) ||
  !ctype_digit($age) || (int) $age < 0 || (int) $age > 130 ||
  $purok === '' ||
  !in_array($status, ['active', 'inactive'], true) ||
  !in_array($pwdType, array_merge([''], $pwdTypes), true) ||
  !in_array($additionalClassification, $classificationOptions, true)
) {
  header('Location: /BarangaySystem/public/site/index.php?err=' . urlencode('Resident registration failed. Please complete all required fields.'));
  exit;
}

try {
  $stmt = db()->prepare("
    INSERT INTO residents (name, address, purok, gender, age, civil_status, status, is_pwd, pwd_type, additional_classification)
    VALUES (:name, :address, :purok, :gender, :age, :civil_status, :status, :is_pwd, :pwd_type, :additional_classification)
  ");
  $stmt->execute([
    'name' => $name,
    'address' => $address,
    'purok' => $purok,
    'gender' => $gender,
    'age' => (int) $age,
    'civil_status' => $civilStatus,
    'status' => $status,
    'is_pwd' => $pwdType === '' ? 0 : 1,
    'pwd_type' => $pwdType === '' ? null : $pwdType,
    'additional_classification' => $additionalClassification,
  ]);
  header('Location: /BarangaySystem/public/site/index.php?msg=' . urlencode('Resident registration submitted successfully.'));
  exit;
} catch (Throwable $e) {
  header('Location: /BarangaySystem/public/site/index.php?err=' . urlencode('Resident registration failed. Please check your database setup.'));
  exit;
}
