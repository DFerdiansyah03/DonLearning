<?php
require_once '../config/path.php';
require_once '../config/db.php';
$page_title = 'Gabung Kelas';
include $base_path . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $user_id = $_SESSION['user_id'];
    $errors = [];

    if (empty($token)) {
        $errors[] = "Token tidak boleh kosong.";
    } elseif (!ctype_alnum($token) || strlen($token) !== 6) {
        $errors[] = "Token harus berupa 6 karakter alfanumerik.";
    }

    if (empty($errors)) {
        $query = $conn->prepare("SELECT id FROM classes WHERE token = ?");
        $query->bind_param('s', $token);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $class = $result->fetch_assoc();
            $class_id = $class['id'];

            $check = $conn->prepare("SELECT * FROM class_members WHERE class_id=? AND user_id=?");
            $check->bind_param('ii', $class_id, $user_id);
            $check->execute();
            $exists = $check->get_result();

            if ($exists->num_rows === 0) {
                $insert = $conn->prepare("INSERT INTO class_members (class_id, user_id, joined_at) VALUES (?, ?, NOW())");
                $insert->bind_param('ii', $class_id, $user_id);
                $insert->execute();
                $success = "Berhasil bergabung ke kelas!";
            } else {
                $error = "Kamu sudah tergabung di kelas ini!";
            }
        } else {
            $error = "Token tidak valid!";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<div class="container mt-5">
  <h3>Masukkan Token Kelas</h3>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
  <?php if (!empty($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

  <form method="POST" class="mt-4">
    <div class="mb-3">
      <label>Token Kelas</label>
      <input type="text" name="token" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success">Gabung</button>
    <a href="<?= $base_url ?>/student/dashboard.php" class="btn btn-secondary">Kembali</a>
  </form>
</div>
<?php include $base_path . '/includes/footer.php'; ?>
