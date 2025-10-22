<?php
require_once '../config/path.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    $errors = [];

    // Validasi input
    if (empty($name) || strlen($name) < 2) {
        $errors[] = "Nama harus diisi minimal 2 karakter.";
    }

    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password harus minimal 6 karakter.";
    }

    if (!in_array($role, ['student', 'teacher'])) {
        $errors[] = "Role tidak valid.";
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Cek apakah email sudah ada
            $stmt = safe_query($conn, "SELECT id FROM users WHERE email = ?", [$email], "s");
            if ($stmt) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $error = "Email sudah terdaftar!";
                    error_log("Registration failed: Email already exists - $email");
                } else {
                    $stmt->close();
                    $stmt = safe_query($conn, "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)", [$name, $email, $password_hash, $role], "ssss");
                    if ($stmt) {
                        error_log("Registration successful: Name=$name, Email=$email, Role=$role");
                        header("Location: $base_url/auth/login.php");
                        exit;
                    } else {
                        $error = "Pendaftaran gagal! Silakan coba lagi.";
                    }
                }
                $stmt->close();
            } else {
                $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="text-center mb-4">Daftar Akun</h4>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label>Nama</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Role</label>
                            <select name="role" class="form-control" required>
                                <option value="">Pilih Role</option>
                                <option value="student">Murid</option>
                                <option value="teacher">Guru</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Daftar</button>
                        <div class="text-center mt-3">
                            <a href="<?= $base_url ?>/auth/login.php">Sudah punya akun?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
