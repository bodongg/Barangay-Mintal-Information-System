<?php
declare(strict_types=1);

function officialsPhotoDirAbsolute(): string
{
    return dirname(__DIR__, 2) . '/assets/uploads/officials';
}

function officialsPhotoDirPublic(): string
{
    return '/BarangaySystem/assets/uploads/officials';
}

function ensureOfficialsPhotoDirectory(): string
{
    $dir = officialsPhotoDirAbsolute();
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function storeOfficialPhotoUpload(array $file, array &$errors): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'Official photo upload failed.';
        return null;
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        $errors[] = 'Official photo upload is invalid.';
        return null;
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = (string) finfo_file($finfo, $tmpName);
            finfo_close($finfo);
        }
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        $errors[] = 'Official photo must be a JPG, PNG, WEBP, or GIF image.';
        return null;
    }

    ensureOfficialsPhotoDirectory();
    $fileName = 'official_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $destination = officialsPhotoDirAbsolute() . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        $errors[] = 'Official photo could not be saved.';
        return null;
    }

    return officialsPhotoDirPublic() . '/' . $fileName;
}

function deleteOfficialPhotoFile(?string $publicPath): void
{
    $publicPath = trim((string) $publicPath);
    if ($publicPath === '' || strpos($publicPath, officialsPhotoDirPublic() . '/') !== 0) {
        return;
    }

    $filePath = dirname(__DIR__, 2) . str_replace('/BarangaySystem', '', $publicPath);
    if (is_file($filePath)) {
        @unlink($filePath);
    }
}
