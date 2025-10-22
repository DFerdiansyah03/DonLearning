<?php
require_once '../config/path.php';
require_once '../config/db.php';
$page_title = 'Dashboard Guru';
include $base_path . '/includes/header.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['class_title'])) {
        $title = $_POST['class_title'];
        $token = substr(md5(uniqid(rand(), true)), 0, 6);

        $stmt = $conn->prepare("INSERT INTO classes (teacher_id, title, token, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param('iss', $user_id, $title, $token);
        $stmt->execute();

        $success = "Kelas '$title' berhasil dibuat! Token: $token";
    } elseif (isset($_POST['delete_class'])) {
        $class_id = $_POST['delete_class_id'];

        // Verify the class belongs to the teacher
        $stmt = $conn->prepare("SELECT title FROM classes WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param('ii', $class_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $class = $result->fetch_assoc();

            // Delete related data first (cascade delete)
            $conn->query("DELETE FROM forum_comments WHERE post_id IN (SELECT id FROM forum_posts WHERE class_id = $class_id)");
            $conn->query("DELETE FROM forum_posts WHERE class_id = $class_id");
            $conn->query("DELETE FROM quiz_attempts WHERE quiz_id IN (SELECT id FROM quizzes WHERE class_id = $class_id)");
            $conn->query("DELETE FROM choices WHERE question_id IN (SELECT id FROM questions WHERE quiz_id IN (SELECT id FROM quizzes WHERE class_id = $class_id))");
            $conn->query("DELETE FROM questions WHERE quiz_id IN (SELECT id FROM quizzes WHERE class_id = $class_id)");
            $conn->query("DELETE FROM quizzes WHERE class_id = $class_id");
            $conn->query("DELETE FROM materials WHERE class_id = $class_id");
            $conn->query("DELETE FROM class_members WHERE class_id = $class_id");

            // Delete the class
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ? AND teacher_id = ?");
            $stmt->bind_param('ii', $class_id, $user_id);
            $stmt->execute();

            $success = "Kelas '" . htmlspecialchars($class['title']) . "' berhasil dihapus!";
        } else {
            $error = "Kelas tidak ditemukan atau Anda tidak memiliki izin untuk menghapusnya.";
        }
    }
}

$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_classes = $conn->query("SELECT COUNT(*) as count FROM classes WHERE teacher_id = $user_id")->fetch_assoc()['count'];
$total_pages = ceil($total_classes / $limit);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

$query = $conn->query("SELECT * FROM classes WHERE teacher_id = $user_id ORDER BY $sort $order LIMIT $limit OFFSET $offset");
?>
<div class="container-fluid">
  <h4>Buat Kelas Baru</h4>
  <?php if (!empty($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
  <form method="POST" class="mb-4">
    <div class="input-group">
      <input type="text" name="class_title" class="form-control" placeholder="Nama Kelas" required>
      <button class="btn btn-primary">Buat Kelas</button>
    </div>
  </form>

  <h4>Daftar Kelas Saya</h4>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <a href="?sort=title&order=ASC" class="btn btn-sm btn-outline-secondary">Sort by Name A-Z</a>
      <a href="?sort=title&order=DESC" class="btn btn-sm btn-outline-secondary">Sort by Name Z-A</a>
      <a href="?sort=created_at&order=DESC" class="btn btn-sm btn-outline-secondary">Sort by Newest</a>
      <a href="?sort=created_at&order=ASC" class="btn btn-sm btn-outline-secondary">Sort by Oldest</a>
    </div>
    <small class="text-muted">Total: <?= $total_classes ?> kelas</small>
  </div>
  <div class="list-group mt-3">
    <?php while ($row = $query->fetch_assoc()): ?>
      <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <a href="<?= $base_url ?>/class/view.php?id=<?= $row['id'] ?>" class="text-decoration-none flex-grow-1">
          <strong><?= htmlspecialchars($row['title']) ?></strong><br>
          <small>Token: <?= htmlspecialchars($row['token']) ?> | Dibuat: <?= date('d/m/Y', strtotime($row['created_at'])) ?></small>
        </a>
        <button class="btn btn-sm btn-outline-danger ms-2" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['title']) ?>')">
          <i class="fas fa-trash"></i> Hapus
        </button>
      </div>
    <?php endwhile; ?>
  </div>

  <?php if ($total_pages > 1): ?>
  <nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center">
      <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>">Previous</a></li>
      <?php endif; ?>

      <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>"><?= $i ?></a></li>
      <?php endfor; ?>

      <?php if ($page < $total_pages): ?>
        <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>">Next</a></li>
      <?php endif; ?>
    </ul>
  </nav>
  <?php endif; ?>
</div>

<script>
function confirmDelete(classId, classTitle) {
  if (confirm('Apakah Anda yakin ingin menghapus kelas "' + classTitle + '"? Semua data terkait (materi, kuis, forum, dll.) akan dihapus secara permanen.')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="delete_class_id" value="' + classId + '"><input type="hidden" name="delete_class" value="1">';
    document.body.appendChild(form);
    form.submit();
  }
}
</script>

<?php include $base_path . '/includes/footer.php'; ?>
