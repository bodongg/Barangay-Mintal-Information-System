<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/database.php';

final class ResidentModel
{
    public const GENDER_OPTIONS = ['Male', 'Female', 'Prefer not to say'];
    public const CIVIL_STATUS_OPTIONS = ['Single', 'Married', 'Widowed', 'Separated'];
    public const STATUS_OPTIONS = ['active', 'inactive'];
    public const PUROK_OPTIONS = ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6'];
    public const PWD_TYPES = [
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
    public const CLASSIFICATION_OPTIONS = [
        'None',
        'Solo Parent',
        '4Ps Beneficiary',
        'Indigenous People (IP)',
        'Indigent/ Low Income',
        'Overseas Filipino Worker (OFW)',
    ];

    public static function defaultForm(): array
    {
        return [
            'name' => '',
            'address' => '',
            'gender' => '',
            'age' => '',
            'civil_status' => 'Single',
            'purok' => '',
            'status' => 'active',
            'pwd_type' => '',
            'additional_classification' => 'None',
        ];
    }

    public static function normalizeForm(array $input): array
    {
        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'address' => trim((string) ($input['address'] ?? '')),
            'gender' => trim((string) ($input['gender'] ?? '')),
            'age' => trim((string) ($input['age'] ?? '')),
            'civil_status' => trim((string) ($input['civil_status'] ?? 'Single')),
            'purok' => trim((string) ($input['purok'] ?? '')),
            'status' => trim((string) ($input['status'] ?? 'active')),
            'pwd_type' => trim((string) ($input['pwd_type'] ?? '')),
            'additional_classification' => trim((string) ($input['additional_classification'] ?? 'None')),
        ];
    }

    public static function validate(array $form): array
    {
        $errors = [];

        if ($form['name'] === '') {
            $errors[] = 'Name is required.';
        }
        if ($form['address'] === '') {
            $errors[] = 'Address is required.';
        }
        if (!in_array($form['gender'], self::GENDER_OPTIONS, true)) {
            $errors[] = 'Gender is required.';
        }
        if (!ctype_digit($form['age']) || (int) $form['age'] < 0 || (int) $form['age'] > 130) {
            $errors[] = 'Age must be a valid number.';
        }
        if (!in_array($form['civil_status'], self::CIVIL_STATUS_OPTIONS, true)) {
            $errors[] = 'Civil status is invalid.';
        }
        if (!in_array($form['purok'], self::PUROK_OPTIONS, true)) {
            $errors[] = 'Purok is required.';
        }
        if (!in_array($form['status'], self::STATUS_OPTIONS, true)) {
            $errors[] = 'Status is invalid.';
        }
        if (!in_array($form['pwd_type'], array_merge([''], self::PWD_TYPES), true)) {
            $errors[] = 'PWD type is invalid.';
        }
        if (!in_array($form['additional_classification'], self::CLASSIFICATION_OPTIONS, true)) {
            $errors[] = 'Additional classification is invalid.';
        }

        return $errors;
    }

    public static function create(array $form): void
    {
        $stmt = db()->prepare("
            INSERT INTO residents (name, address, purok, gender, age, civil_status, status, is_pwd, pwd_type, additional_classification)
            VALUES (:name, :address, :purok, :gender, :age, :civil_status, :status, :is_pwd, :pwd_type, :additional_classification)
        ");
        $stmt->execute(self::toPersistenceParams($form));
    }

    public static function update(int $id, array $form): void
    {
        $stmt = db()->prepare("
            UPDATE residents
            SET name = :name,
                address = :address,
                purok = :purok,
                gender = :gender,
                age = :age,
                civil_status = :civil_status,
                status = :status,
                is_pwd = :is_pwd,
                pwd_type = :pwd_type,
                additional_classification = :additional_classification
            WHERE id = :id
        ");
        $params = self::toPersistenceParams($form);
        $params['id'] = $id;
        $stmt->execute($params);
    }

    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare("
            SELECT
              id,
              name,
              address,
              purok,
              gender,
              age,
              civil_status,
              status,
              is_pwd,
              pwd_type,
              additional_classification,
              created_at,
              updated_at
            FROM residents
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findEditableById(int $id): ?array
    {
        $row = self::findById($id);
        if (!$row) {
            return null;
        }

        return [
            'name' => (string) ($row['name'] ?? ''),
            'address' => (string) ($row['address'] ?? ''),
            'gender' => (string) ($row['gender'] ?? ''),
            'age' => (string) ($row['age'] ?? ''),
            'civil_status' => (string) ($row['civil_status'] ?? 'Single'),
            'purok' => (string) ($row['purok'] ?? ''),
            'status' => (string) ($row['status'] ?? 'active'),
            'pwd_type' => (string) ($row['pwd_type'] ?? ''),
            'additional_classification' => (string) ($row['additional_classification'] ?? 'None'),
        ];
    }

    public static function listByArchived(bool $archived): array
    {
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
        $stmt->execute(['is_archived' => $archived ? 1 : 0]);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'purok' => (string) ($row['purok'] ?? ''),
                'gender' => (string) ($row['gender'] ?? ''),
                'age' => (int) ($row['age'] ?? 0),
                'civil' => (string) ($row['civil_status'] ?? ''),
                'status' => strtolower((string) ($row['status'] ?? '')),
                'is_pwd' => ((int) ($row['is_pwd'] ?? 0) === 1) || !empty($row['pwd_type']),
                'pwd_type' => (string) ($row['pwd_type'] ?? ''),
                'date' => (string) ($row['date_added'] ?? ''),
            ];
        }, $stmt->fetchAll());
    }

    public static function countArchived(): int
    {
        return (int) (db()->query("
            SELECT COUNT(*)
            FROM residents
            WHERE COALESCE(is_archived, 0) = 1
        ")->fetchColumn() ?: 0);
    }

    public static function archive(int $id): void
    {
        $stmt = db()->prepare("UPDATE residents SET is_archived = 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public static function restore(int $id): void
    {
        $stmt = db()->prepare("UPDATE residents SET is_archived = 0 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    private static function toPersistenceParams(array $form): array
    {
        return [
            'name' => $form['name'],
            'address' => $form['address'],
            'purok' => $form['purok'],
            'gender' => $form['gender'],
            'age' => (int) $form['age'],
            'civil_status' => $form['civil_status'],
            'status' => $form['status'],
            'is_pwd' => $form['pwd_type'] === '' ? 0 : 1,
            'pwd_type' => $form['pwd_type'] === '' ? null : $form['pwd_type'],
            'additional_classification' => $form['additional_classification'],
        ];
    }
}
