<?php
session_start();
require_once '../config/path.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = [];

    // Validasi input
    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }

    if (empty($password)) {
        $errors[] = "Password tidak boleh kosong.";
    }

    if (empty($errors)) {
        // Log request
        $log_message = "Login attempt: Method=" . $_SERVER['REQUEST_METHOD'] . ", Path=" . $_SERVER['REQUEST_URI'] . ", Email=$email";
        error_log($log_message);

        try {
            $stmt = safe_query($conn, "SELECT id, name, email, password_hash, role FROM users WHERE email = ?", [$email], "s");
            if ($stmt) {
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();

                    if (password_verify($password, $user['password_hash'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['name'] = $user['name'];

                        // Log successful login
                        error_log("Login successful: User ID=" . $user['id'] . ", Role=" . $user['role']);

                        if ($user['role'] === 'teacher') {
                            header("Location: $base_url/teacher/dashboard.php");
                        } elseif ($user['role'] === 'student') {
                            header("Location: $base_url/student/dashboard.php");
                        }
                        exit;
                    } else {
                        $error = "Password salah!";
                        error_log("Login failed: Invalid password for email=$email");
                    }
                } else {
                    $error = "Akun tidak ditemukan!";
                    error_log("Login failed: Account not found for email=$email");
                }
                $stmt->close();
            } else {
                $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
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
  <title>Login LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="col-md-4 mx-auto">
    <h3 class="text-center mb-4">Login LMS</h3>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" placeholder="" autocomplete="off" required>
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" placeholder="" autocomplete="off" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    <div class="text-center mt-3">
      <a href="<?= $base_url ?>/auth/register.php">Belum punya akun?</a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
