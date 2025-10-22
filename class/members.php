<?php
session_start();
require_once '../config/path.php';
require_once '../config/db.php';
$class_id = $_GET['id'];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: $base_url/auth/login.php");
    exit;
}

$class = $conn->query("SELECT * FROM classes WHERE id=$class_id AND teacher_id=" . $_SESSION['user_id'])->fetch_assoc();

if (!$class) {
    die("Kelas tidak ditemukan atau Anda bukan guru kelas ini!");
}

$page_title = 'Anggota Kelas - ' . htmlspecialchars($class['title']);
include $base_path . '/includes/header.php';

$user_id = $_SESSION['user_id'];

if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $remove_user_id = $_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM class_members WHERE class_id=? AND user_id=?");
    $stmt->bind_param('ii', $class_id, $remove_user_id);
    $stmt->execute();
    header("Location: $base_url/class/members.php?id=$class_id");
    exit;
}

$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_members = $conn->query("SELECT COUNT(*) as count FROM class_members WHERE class_id=$class_id")->fetch_assoc()['count'];
$total_pages = ceil($total_members / $limit);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'm.joined_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

$members = $conn->query("
    SELECT u.id, u.name, u.email, m.joined_at
    FROM class_members m
    JOIN users u ON m.user_id = u.id
    WHERE m.class_id = $class_id
    ORDER BY $sort $order LIMIT $limit OFFSET $offset
");
?>
<div class="container-fluid">
  <h4><i class="fas fa-users me-2"></i>Anggota Kelas: <?= htmlspecialchars($class['title']) ?></h4>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <a href="?id=<?= $class_id ?>&sort=u.name&order=ASC" class="btn btn-sm btn-outline-primary me-2">
        <i class="fas fa-sort-alpha-down me-1"></i>Nama A-Z
      </a>
      <a href="?id=<?= $class_id ?>&sort=u.name&order=DESC" class="btn btn-sm btn-outline-primary me-2">
        <i class="fas fa-sort-alpha-up me-1"></i>Nama Z-A
      </a>
      <a href="?id=<?= $class_id ?>&sort=m.joined_at&order=DESC" class="btn btn-sm btn-outline-primary me-2">
        <i class="fas fa-clock me-1"></i>Terbaru
      </a>
      <a href="?id=<?= $class_id ?>&sort=m.joined_at&order=ASC" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-history me-1"></i>Terlama
      </a>
    </div>
    <div class="badge bg-info text-dark fs-6">
      <i class="fas fa-users me-1"></i>Total: <?= $total_members ?> anggota
    </div>
  </div>

  <div class="row">
    <?php while ($member = $members->fetch_assoc()): ?>
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-3">
              <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                <i class="fas fa-user"></i>
              </div>
              <div>
                <h6 class="card-title mb-1"><?= htmlspecialchars($member['name']) ?></h6>
                <small class="text-muted">
                  <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($member['email']) ?>
                </small>
              </div>
            </div>
            <div class="mb-3">
              <small class="text-muted">
                <i class="fas fa-calendar-plus me-1"></i>Bergabung: <?= date('d/m/Y H:i', strtotime($member['joined_at'])) ?>
              </small>
            </div>
            <div class="d-flex justify-content-end">
              <a href="?id=<?= $class_id ?>&remove=<?= $member['id'] ?>" class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('Yakin ingin mengeluarkan anggota ini?')">
                <i class="fas fa-user-times me-1"></i>Keluarkan
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>

  <?php if ($total_pages > 1): ?>
  <nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center">
      <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="?id=<?= $class_id ?>&page=<?= $page - 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>">Previous</a></li>
      <?php endif; ?>

      <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?id=<?= $class_id ?>&page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>"><?= $i ?></a></li>
      <?php endfor; ?>

      <?php if ($page < $total_pages): ?>
        <li class="page-item"><a class="page-link" href="?id=<?= $class_id ?>&page=<?= $page + 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>">Next</a></li>
      <?php endif; ?>
    </ul>
  </nav>
  <?php endif; ?>

  <a href="<?= $base_url ?>/class/view.php?id=<?= $class_id ?>" class="btn btn-secondary">⬅ Kembali</a>
</div>
</body>
</html>
