<?php
// Helper: create a square thumbnail using GD
function create_thumbnail($source_path, $thumb_path, $max_size = 300) {
    if (!extension_loaded('gd')) {
        return false;
    }
    $info = getimagesize($source_path);
    if (!$info) return false;

    list($width, $height, $type) = $info;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }

    if (!$src) return false;

    $scale = min($max_size / $width, $max_size / $height, 1);
    $new_w = (int)($width * $scale);
    $new_h = (int)($height * $scale);

    $thumb = imagecreatetruecolor($new_w, $new_h);

    // Preserve transparency for PNG/GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefill($thumb, 0, 0, $transparent);
    }

    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $new_w, $new_h, $width, $height);

    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($thumb, $thumb_path, 85);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($thumb, $thumb_path, 8);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($thumb, $thumb_path);
            break;
    }

    imagedestroy($src);
    imagedestroy($thumb);
    return $result;
}

// Helper: validate uploaded image with finfo + size
function validate_upload($file, $max_size = 2 * 1024 * 1024) {
    $result = array('ok' => false, 'error' => '', 'ext' => '');

    if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
        $result['error'] = "No file uploaded or upload error.";
        return $result;
    }

    if ($file['size'] > $max_size) {
        $result['error'] = "File must be under 2MB.";
        return $result;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif');
    if (!isset($allowed[$mime])) {
        $result['error'] = "Only JPG, PNG and GIF allowed.";
        return $result;
    }

    $result['ok'] = true;
    $result['ext'] = $allowed[$mime];
    return $result;
}

function save_upload($file, $prefix, $folder = 'uploads') {
    $check = validate_upload($file);
    if (!$check['ok']) {
        return array('ok' => false, 'error' => $check['error']);
    }

    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    $new_name = $prefix . uniqid() . '_' . rand(1000, 9999) . '.' . $check['ext'];
    $target = $folder . '/' . $new_name;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return array('ok' => false, 'error' => "Upload failed.");
    }

    return array('ok' => true, 'file' => $new_name, 'path' => $target);
}
?>