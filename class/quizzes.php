<?php
require_once '../config/path.php';
require_once '../config/db.php';
$page_title = 'Kuis';
include $base_path . '/includes/header.php';

$class_id = $_GET['id'];
$is_teacher = ($_SESSION['role'] === 'teacher');
$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_quizzes = $conn->query("SELECT COUNT(*) as count FROM quizzes WHERE class_id=$class_id")->fetch_assoc()['count'];
$total_pages = ceil($total_quizzes / $limit);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

$query = $conn->query("SELECT * FROM quizzes WHERE class_id=$class_id ORDER BY $sort $order LIMIT $limit OFFSET $offset");
?>
<div class="container-fluid">
  <h4><i class="fas fa-question-circle me-2"></i>Kuis</h4>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Kuis berhasil dibuat!</div>
  <?php endif; ?>

  <?php if ($is_teacher): ?>
    <a href="<?= $base_url ?>/class/add_quiz.php?id=<?= $class_id ?>" class="btn btn-primary mb-3">
      <i class="fas fa-plus me-1"></i>Buat Kuis
    </a>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <a href="?id=<?= $class_id ?>&sort=title&order=ASC" class="btn btn-sm btn-outline-secondary">Sort by Name A-Z</a>
      <a href="?id=<?= $class_id ?>&sort=title&order=DESC" class="btn btn-sm btn-outline-secondary">Sort by Name Z-A</a>
      <a href="?id=<?= $class_id ?>&sort=created_at&order=DESC" class="btn btn-sm btn-outline-secondary">Sort by Newest</a>
      <a href="?id=<?= $class_id ?>&sort=created_at&order=ASC" class="btn btn-sm btn-outline-secondary">Sort by Oldest</a>
    </div>
    <small class="text-muted">Total: <?= $total_quizzes ?> kuis</small>
  </div>

  <div class="row">
    <?php while ($quiz = $query->fetch_assoc()): ?>
      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">
              <i class="fas fa-question-circle text-primary me-2"></i>
              <?= htmlspecialchars($quiz['title']) ?>
            </h5>
            <p class="card-text">
              <small class="text-muted">
                <i class="fas fa-calendar me-1"></i>Dibuat: <?= date('d/m/Y H:i', strtotime($quiz['created_at'])) ?>
              </small>
            </p>
            <div class="mt-auto">
              <?php if ($is_teacher): ?>
                <a href="<?= $base_url ?>/class/take_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-outline-info btn-sm w-100">
                  <i class="fas fa-chart-bar me-1"></i>Lihat Hasil
                </a>
              <?php else: ?>
                <?php
                $attempt = $conn->query("SELECT id FROM attempts WHERE quiz_id={$quiz['id']} AND user_id={$_SESSION['user_id']}")->fetch_assoc();
                if ($attempt): ?>
                  <span class="badge bg-success w-100 py-2">
                    <i class="fas fa-check me-1"></i>Sudah Mengerjakan
                  </span>
                <?php else: ?>
                  <a href="<?= $base_url ?>/class/take_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-outline-success btn-sm w-100">
                    <i class="fas fa-play me-1"></i>Mulai Kuis
                  </a>
                <?php endif; ?>
              <?php endif; ?>
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

  <a href="<?= $base_url ?>/class/view.php?id=<?= $class_id ?>" class="btn btn-secondary mt-3">
    <i class="fas fa-arrow-left me-1"></i>Kembali
  </a>
</div>
<?php include $base_path . '/includes/footer.php'; ?>
