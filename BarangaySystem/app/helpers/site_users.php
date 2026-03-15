<?php
declare(strict_types=1);

function siteUserPhotoDirAbsolute(): string
{
    return dirname(__DIR__, 2) . '/assets/uploads/site-users';
}

function siteUserPhotoDirPublic(): string
{
    return '/BarangaySystem/assets/uploads/site-users';
}

function ensureSiteUserPhotoDirectory(): string
{
    $dir = siteUserPhotoDirAbsolute();
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function storeSiteUserPhotoUpload(array $file, array &$errors): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'Profile photo upload failed.';
        return null;
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        $errors[] = 'Profile photo upload is invalid.';
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
        $errors[] = 'Profile photo must be a JPG, PNG, WEBP, or GIF image.';
        return null;
    }

    ensureSiteUserPhotoDirectory();
    $fileName = 'user_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $destination = siteUserPhotoDirAbsolute() . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        $errors[] = 'Profile photo could not be saved.';
        return null;
    }

    return siteUserPhotoDirPublic() . '/' . $fileName;
}

function deleteSiteUserPhotoFile(?string $publicPath): void
{
    $publicPath = trim((string) $publicPath);
    if ($publicPath === '' || strpos($publicPath, siteUserPhotoDirPublic() . '/') !== 0) {
        return;
    }

    $filePath = dirname(__DIR__, 2) . str_replace('/BarangaySystem', '', $publicPath);
    if (is_file($filePath)) {
        @unlink($filePath);
    }
}
