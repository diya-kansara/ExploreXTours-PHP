<?php
/**
 * Secure image uploads for package photos.
 *  - size limit from config
 *  - real MIME sniffing with finfo (client-supplied type is ignored)
 *  - getimagesize() must succeed and dimensions are bounded
 *  - image is re-encoded with GD (strips any embedded payload/metadata)
 *  - random generated filename, extension chosen by us, stored in uploads/packages/
 */
declare(strict_types=1);

/**
 * @return array{0: ?string, 1: ?string} [stored filename | null, error message | null]
 */
function save_package_image(array $file): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return [null, 'Invalid upload.'];
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return [null, null];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return [null, 'The image is larger than the allowed size.'];
        default:
            return [null, 'The image could not be uploaded. Please try again.'];
    }
    $max = (int) config('upload_max_bytes', 3 * 1024 * 1024);
    if ($file['size'] <= 0 || $file['size'] > $max) {
        return [null, 'Image must be smaller than ' . round($max / 1048576, 1) . ' MB.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return [null, 'Invalid upload.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string) $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        return [null, 'Only JPG, PNG or WebP images are allowed.'];
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || $info[0] < 200 || $info[1] < 150 || $info[0] > 8000 || $info[1] > 8000) {
        return [null, 'The image is not valid or its dimensions are out of range (min 200x150, max 8000x8000).'];
    }

    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
    };
    if (!$src) {
        return [null, 'The image could not be processed.'];
    }
    // downscale very large images
    $w = imagesx($src);
    $h = imagesy($src);
    if ($w > 1800) {
        $nh  = (int) round($h * 1800 / $w);
        $dst = imagecreatetruecolor(1800, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, 1800, $nh, $w, $h);
        imagedestroy($src);
        $src = $dst;
    }

    $dir = APP_ROOT . '/uploads/packages';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        imagedestroy($src);
        return [null, 'Upload folder is not writable.'];
    }
    $name = 'pkg_' . bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
    $path = $dir . '/' . $name;
    $ok = match ($mime) {
        'image/jpeg' => imagejpeg($src, $path, 86),
        'image/png'  => imagepng($src, $path, 6),
        'image/webp' => imagewebp($src, $path, 86),
    };
    imagedestroy($src);
    if (!$ok) {
        return [null, 'Could not save the image. Check folder permissions on uploads/packages.'];
    }
    @chmod($path, 0644);
    return [$name, null];
}

/** Delete an uploaded (not bundled sample) image safely. */
function delete_package_image(?string $file): void
{
    $file = basename((string) $file);
    if ($file === '' || !str_starts_with($file, 'pkg_')) {
        return;
    }
    $path = APP_ROOT . '/uploads/packages/' . $file;
    if (is_file($path)) {
        @unlink($path);
    }
}

/** Normalises $_FILES['x'] with multiple files into a list of single-file arrays. */
function files_list(string $key): array
{
    if (empty($_FILES[$key]) || !is_array($_FILES[$key]['name'])) {
        return empty($_FILES[$key]) ? [] : [$_FILES[$key]];
    }
    $out = [];
    foreach ($_FILES[$key]['name'] as $i => $_) {
        $out[] = [
            'name' => $_FILES[$key]['name'][$i], 'type' => $_FILES[$key]['type'][$i],
            'tmp_name' => $_FILES[$key]['tmp_name'][$i], 'error' => $_FILES[$key]['error'][$i],
            'size' => $_FILES[$key]['size'][$i],
        ];
    }
    return $out;
}
