<?php
require_once '../config/path.php';
require_once '../config/db.php';
$page_title = 'Buat Kelas Baru';
include $base_path . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $errors = [];

    if (empty($title)) {
        $errors[] = "Nama kelas tidak boleh kosong.";
    } elseif (strlen($title) > 100) {
        $errors[] = "Nama kelas maksimal 100 karakter.";
    }

    if (empty($errors)) {
        $teacher_id = $_SESSION['user_id'];
        $token = substr(md5(uniqid(rand(), true)), 0, 6);

        $stmt = $conn->prepare("INSERT INTO classes (teacher_id, title, token, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param('iss', $teacher_id, $title, $token);
        $stmt->execute();

        header("Location: $base_url/teacher/dashboard.php");
        exit;
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<div class="container mt-5">
  <h3>Buat Kelas Baru</h3>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
  <form method="POST">
    <div class="mb-3">
      <label>Nama Kelas</label>
      <input type="text" name="title" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Buat Kelas</button>
    <a href="<?= $base_url ?>/teacher/dashboard.php" class="btn btn-secondary">Kembali</a>
  </form>
</div>
<?php include $base_path . '/includes/footer.php'; ?>
