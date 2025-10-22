<?php
session_start();
require_once '../config/path.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: $base_url/auth/login.php");
    exit;
}

$errors = [];
$class_id = $_POST['class_id'] ?? '';
$title = trim($_POST['title'] ?? '');
$file = $_FILES['file'] ?? null;

// Validasi input
if (empty($class_id) || !is_numeric($class_id)) {
    $errors[] = "ID kelas tidak valid.";
}

if (empty($title)) {
    $errors[] = "Judul materi tidak boleh kosong.";
}

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errors[] = "File tidak ditemukan atau terjadi kesalahan upload.";
} else {
    // Validasi tipe file
    $allowed_types = ['pdf' => 'application/pdf', 'ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'mp4' => 'video/mp4'];
    $allowed_extensions = array_keys($allowed_types);
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_mime = mime_content_type($file['tmp_name']);

    if (!in_array($file_ext, $allowed_extensions)) {
        $errors[] = "Tipe file tidak diizinkan. Hanya PDF, PPT, PPTX, atau MP4.";
    } elseif (!in_array($file_mime, $allowed_types)) {
        $errors[] = "Tipe MIME file tidak valid.";
    }

    // Validasi ukuran file (max 10MB)
    $max_size = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $max_size) {
        $errors[] = "Ukuran file maksimal 10MB.";
    }
}

if (empty($errors)) {
    $upload_dir = $base_path . '/uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $target = $upload_dir . $file_name;

    try {
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $file_type = ($file_ext === 'pdf') ? 'pdf' : (($file_ext === 'mp4') ? 'video' : 'ppt');

            $stmt = safe_query($conn, "INSERT INTO materials (class_id, teacher_id, title, file_url, file_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())", [$class_id, $_SESSION['user_id'], $title, $file_name, $file_type], "iisss");
            if ($stmt) {
                error_log("Material uploaded: Class ID=$class_id, Title=$title, File=$file_name");
                header("Location: $base_url/class/materials.php?id=$class_id&success=1");
                exit;
            } else {
                unlink($target); // Hapus file jika gagal insert
                $error = "Gagal menyimpan data materi.";
            }
        } else {
            $error = "Gagal mengupload file.";
        }
    } catch (Exception $e) {
        if (file_exists($target)) {
            unlink($target);
        }
        error_log("Upload error: " . $e->getMessage());
        $error = "Terjadi kesalahan saat upload. Silakan coba lagi.";
    }
} else {
    $error = implode("<br>", $errors);
    error_log("Upload validation failed: " . $error);
}

header("Location: $base_url/class/materials.php?id=$class_id&error=" . urlencode($error));
exit;
?>
