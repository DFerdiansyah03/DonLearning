<?php
require_once '../config/path.php';
require_once '../config/db.php';
$page_title = 'Dashboard Murid';
include $base_path . '/includes/header.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_token'])) {
    $token = trim($_POST['join_token'] ?? '');
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

$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_classes = $conn->query("SELECT COUNT(*) as count FROM classes c JOIN class_members m ON m.class_id = c.id WHERE m.user_id = $user_id")->fetch_assoc()['count'];
$total_pages = ceil($total_classes / $limit);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'c.created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

$query = $conn->query("
  SELECT c.* FROM classes c
  JOIN class_members m ON m.class_id = c.id
  WHERE m.user_id = $user_id
  ORDER BY $sort $order
  LIMIT $limit OFFSET $offset
");
?>
<div class="container-fluid">
  <h4>Gabung Kelas Baru</h4>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
  <?php if (!empty($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <form method="POST" class="mb-4">
    <div class="input-group">
      <input type="text" name="join_token" class="form-control" placeholder="Masukkan Token Kelas" required>
      <button class="btn btn-success">Gabung</button>
    </div>
  </form>

  <h4>Kelas yang Saya Ikuti</h4>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <a href="?sort=c.title&order=ASC" class="btn btn-sm btn-outline-secondary">Sort by Name A-Z</a>
      <a href="?sort=c.title&order=DESC" class="btn btn-sm btn-outline-secondary">Sort by Name Z-A</a>
      <a href="?sort=c.created_at&order=DESC" class="btn btn-sm btn-outline-secondary">Sort by Newest</a>
      <a href="?sort=c.created_at&order=ASC" class="btn btn-sm btn-outline-secondary">Sort by Oldest</a>
    </div>
    <small class="text-muted">Total: <?= $total_classes ?> kelas</small>
  </div>
  <div class="list-group mt-3">
    <?php while ($row = $query->fetch_assoc()): ?>
      <a href="<?= $base_url ?>/class/view.php?id=<?= $row['id'] ?>" class="list-group-item list-group-item-action">
        <strong><?= htmlspecialchars($row['title']) ?></strong><br>
        <small>Dibuat: <?= date('d/m/Y', strtotime($row['created_at'])) ?></small>
      </a>
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
<?php include $base_path . '/includes/footer.php'; ?>
