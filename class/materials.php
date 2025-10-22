<?php
require_once '../config/path.php';
require_once '../config/db.php';
$page_title = 'Materi Pembelajaran';
include $base_path . '/includes/header.php';

$class_id = $_GET['id'];
$is_teacher = ($_SESSION['role'] === 'teacher');

// Pagination dan sorting
$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_materials = $conn->query("SELECT COUNT(*) as count FROM materials WHERE class_id=$class_id")->fetch_assoc()['count'];
$total_pages = ceil($total_materials / $limit);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Ambil materi
$query = $conn->query("SELECT * FROM materials WHERE class_id=$class_id ORDER BY $sort $order LIMIT $limit OFFSET $offset");
?>
<div class="container-fluid">
  <h4><i class="fas fa-book me-2"></i>Materi Pembelajaran</h4>

  <?php if ($is_teacher): ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars(urldecode($_GET['error'])) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success">Materi berhasil diupload!</div>
    <?php endif; ?>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Upload Materi Pembelajaran</h5>
      </div>
      <div class="card-body">
        <form method="POST" action="<?= $base_url ?>/class/upload_material.php" enctype="multipart/form-data">
          <input type="hidden" name="class_id" value="<?= $class_id ?>">
          <div class="mb-3">
            <label for="title" class="form-label">Judul Materi</label>
            <input type="text" name="title" id="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="file" class="form-label">File (PDF, PPT, PPTX, MP4 - Max 10MB)</label>
            <input type="file" name="file" id="file" class="form-control" accept=".pdf,.ppt,.pptx,.mp4" required>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-upload"></i> Upload Materi
          </button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <div class="mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <a href="?id=<?= $class_id ?>&sort=title&order=ASC" class="btn btn-sm btn-outline-secondary">Sort by Name A-Z</a>
        <a href="?id=<?= $class_id ?>&sort=title&order=DESC" class="btn btn-sm btn-outline-secondary">Sort by Name Z-A</a>
        <a href="?id=<?= $class_id ?>&sort=created_at&order=DESC" class="btn btn-sm btn-outline-secondary">Sort by Newest</a>
        <a href="?id=<?= $class_id ?>&sort=created_at&order=ASC" class="btn btn-sm btn-outline-secondary">Sort by Oldest</a>
      </div>
      <small class="text-muted">Total: <?= $total_materials ?> materi</small>
    </div>
    <div class="row">
      <?php while ($m = $query->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4 mb-3">
          <div class="card h-100">
            <div class="card-body">
              <h6 class="card-title">
                <?php if ($m['file_type'] === 'pdf'): ?>
                  <i class="fas fa-file-pdf text-danger me-2"></i>
                <?php elseif ($m['file_type'] === 'video'): ?>
                  <i class="fas fa-video text-primary me-2"></i>
                <?php else: ?>
                  <i class="fas fa-file-powerpoint text-warning me-2"></i>
                <?php endif; ?>
                <?= htmlspecialchars($m['title']) ?>
              </h6>
              <p class="card-text small text-muted">
                Tipe: <?= htmlspecialchars($m['file_type']) ?><br>
                Ukuran: <?= number_format($m['file_size'] / 1024 / 1024, 2) ?> MB<br>
                Diunggah: <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?>
              </p>
              <a href="<?= $base_url ?>/uploads/<?= htmlspecialchars($m['file_url']) ?>" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-eye me-1"></i>Lihat / Unduh
              </a>
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
  </div>

  <a href="<?= $base_url ?>/class/view.php?id=<?= $class_id ?>" class="btn btn-secondary mt-3">
    <i class="fas fa-arrow-left me-1"></i>Kembali ke Kelas
  </a>
</div>
<?php include $base_path . '/includes/footer.php'; ?>
