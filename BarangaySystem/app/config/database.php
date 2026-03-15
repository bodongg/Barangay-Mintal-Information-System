<?php
declare(strict_types=1);

function ensureSiteUsersSchema(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            account_id VARCHAR(20) NULL UNIQUE,
            full_name VARCHAR(120) NOT NULL,
            username VARCHAR(80) NOT NULL UNIQUE,
            email VARCHAR(120) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    $columnChecks = [
        'account_id' => "ALTER TABLE site_users ADD COLUMN account_id VARCHAR(20) NULL UNIQUE AFTER id",
        'profile_photo' => "ALTER TABLE site_users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER status",
        'status' => "ALTER TABLE site_users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER password_hash",
        'created_at' => "ALTER TABLE site_users ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER status",
        'updated_at' => "ALTER TABLE site_users ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    ];

    $checkColumn = $pdo->prepare("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'site_users'
          AND COLUMN_NAME = :column_name
        LIMIT 1
    ");
    foreach ($columnChecks as $column => $sql) {
        $checkColumn->execute(['column_name' => $column]);
        if (!$checkColumn->fetch()) {
            $pdo->exec($sql);
        }
    }

    $missingAccountIds = $pdo->query("
        SELECT id
        FROM site_users
        WHERE account_id IS NULL OR account_id = ''
        ORDER BY id ASC
    ")->fetchAll(PDO::FETCH_COLUMN);

    if ($missingAccountIds) {
        $updateAccountId = $pdo->prepare("
            UPDATE site_users
            SET account_id = :account_id
            WHERE id = :id
            LIMIT 1
        ");

        foreach ($missingAccountIds as $id) {
            $updateAccountId->execute([
                'account_id' => 'A' . str_pad((string) $id, 3, '0', STR_PAD_LEFT),
                'id' => (int) $id,
            ]);
        }
    }

    $ensured = true;
}

function ensureAdminUsersSchema(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(120) NOT NULL,
            role VARCHAR(80) NOT NULL DEFAULT 'Barangay Official',
            username VARCHAR(80) NOT NULL UNIQUE,
            email VARCHAR(120) NULL UNIQUE,
            phone VARCHAR(40) NULL,
            address VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    $columnChecks = [
        'full_name' => "ALTER TABLE admin_users ADD COLUMN full_name VARCHAR(120) NOT NULL AFTER id",
        'role' => "ALTER TABLE admin_users ADD COLUMN role VARCHAR(80) NOT NULL DEFAULT 'Barangay Official' AFTER full_name",
        'username' => "ALTER TABLE admin_users ADD COLUMN username VARCHAR(80) NOT NULL UNIQUE AFTER role",
        'email' => "ALTER TABLE admin_users ADD COLUMN email VARCHAR(120) NULL UNIQUE AFTER username",
        'phone' => "ALTER TABLE admin_users ADD COLUMN phone VARCHAR(40) NULL AFTER email",
        'address' => "ALTER TABLE admin_users ADD COLUMN address VARCHAR(255) NULL AFTER phone",
        'status' => "ALTER TABLE admin_users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER address",
        'password_hash' => "ALTER TABLE admin_users ADD COLUMN password_hash VARCHAR(255) NOT NULL AFTER status",
        'created_at' => "ALTER TABLE admin_users ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER password_hash",
        'updated_at' => "ALTER TABLE admin_users ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    ];

    $checkColumn = $pdo->prepare("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'admin_users'
          AND COLUMN_NAME = :column_name
        LIMIT 1
    ");

    foreach ($columnChecks as $column => $sql) {
        $checkColumn->execute(['column_name' => $column]);
        if (!$checkColumn->fetch()) {
            $pdo->exec($sql);
        }
    }

    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    if ($adminCount === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO admin_users (full_name, role, username, email, phone, address, status, password_hash)
            VALUES (:full_name, :role, :username, :email, :phone, :address, 'active', :password_hash)
        ");
        $stmt->execute([
            'full_name' => 'Administrator',
            'role' => 'Barangay Official',
            'username' => 'admin',
            'email' => 'admin@barangay.local',
            'phone' => '09xx xxx xxxx',
            'address' => 'Barangay Hall',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        ]);
    }

    $missingPasswords = $pdo->query("
        SELECT id
        FROM admin_users
        WHERE password_hash IS NULL OR password_hash = ''
    ")->fetchAll(PDO::FETCH_COLUMN);

    if ($missingPasswords) {
        $updatePassword = $pdo->prepare("
            UPDATE admin_users
            SET password_hash = :password_hash
            WHERE id = :id
            LIMIT 1
        ");

        foreach ($missingPasswords as $id) {
            $updatePassword->execute([
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'id' => (int) $id,
            ]);
        }
    }

    $ensured = true;
}

function ensureOfficialsSchema(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS officials (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(120) NOT NULL,
            position VARCHAR(120) NOT NULL,
            committee VARCHAR(120) NOT NULL,
            contact_number VARCHAR(40) NULL,
            work_location VARCHAR(120) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            term_start DATE NOT NULL,
            term_end DATE NOT NULL,
            official_photo VARCHAR(255) NULL,
            is_archived TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    $columnChecks = [
        'official_photo' => "ALTER TABLE officials ADD COLUMN official_photo VARCHAR(255) NULL AFTER term_end",
    ];

    $checkColumn = $pdo->prepare("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'officials'
          AND COLUMN_NAME = :column_name
        LIMIT 1
    ");

    foreach ($columnChecks as $column => $sql) {
        $checkColumn->execute(['column_name' => $column]);
        if (!$checkColumn->fetch()) {
            $pdo->exec($sql);
        }
    }

    $ensured = true;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'barangay_system_new';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    ensureSiteUsersSchema($pdo);
    ensureAdminUsersSchema($pdo);
    ensureOfficialsSchema($pdo);

    return $pdo;
}
