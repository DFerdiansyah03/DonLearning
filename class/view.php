<?php
session_start();
require_once '../config/path.php';
require_once '../config/db.php';

$class_id = $_GET['id'];
$class = $conn->query("SELECT * FROM classes WHERE id=$class_id")->fetch_assoc();

$page_title = htmlspecialchars($class['title']);
include $base_path . '/includes/header.php';

if (!$class) {
    die("Kelas tidak ditemukan!");
}

// Check if user is member of this class (for students)
if ($_SESSION['role'] === 'student') {
    $is_member = $conn->query("SELECT id FROM class_members WHERE class_id=$class_id AND user_id=" . $_SESSION['user_id'])->fetch_assoc();
    if (!$is_member) {
        die("Anda tidak terdaftar di kelas ini!");
    }
}

// Check if user is the teacher of this class
if ($_SESSION['role'] === 'teacher' && $class['teacher_id'] != $_SESSION['user_id']) {
    die("Anda bukan guru kelas ini!");
}
?>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"><?= htmlspecialchars($class['title']) ?></h4>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>Token Kelas:</strong> <code><?= htmlspecialchars($class['token']) ?></code></p>
            </div>
            <div class="col-md-6">
              <p><strong>Dibuat:</strong> <?= date('d F Y', strtotime($class['created_at'])) ?></p>
            </div>
          </div>
          <?php if (!empty($class['description'])): ?>
            <div class="mt-3">
              <h6>Deskripsi Kelas:</h6>
              <p class="text-muted"><?= nl2br(htmlspecialchars($class['description'])) ?></p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="row mt-4">
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body text-center">
              <div class="mb-3">
                <i class="fas fa-book fa-3x text-primary"></i>
              </div>
              <h5 class="card-title">Materi Pembelajaran</h5>
              <p class="card-text text-muted">Akses materi yang diunggah oleh guru</p>
              <a href="<?= $base_url ?>/class/materials.php?id=<?= $class_id ?>" class="btn btn-primary btn-lg">
                <i class="fas fa-eye me-2"></i>Lihat Materi
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body text-center">
              <div class="mb-3">
                <i class="fas fa-brain fa-3x text-success"></i>
              </div>
              <h5 class="card-title">Kuis & Ujian</h5>
              <p class="card-text text-muted">Kerjakan kuis yang tersedia</p>
              <a href="<?= $base_url ?>/class/quizzes.php?id=<?= $class_id ?>" class="btn btn-success btn-lg">
                <i class="fas fa-play me-2"></i>Lihat Kuis
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body text-center">
              <div class="mb-3">
                <i class="fas fa-comments fa-3x text-secondary"></i>
              </div>
              <h5 class="card-title">Forum Diskusi</h5>
              <p class="card-text text-muted">Diskusikan topik dengan teman dan guru</p>
              <a href="<?= $base_url ?>/class/forum.php?id=<?= $class_id ?>" class="btn btn-secondary btn-lg">
                <i class="fas fa-comment me-2"></i>Buka Forum
              </a>
            </div>
          </div>
        </div>
        <?php if ($_SESSION['role'] === 'teacher'): ?>
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body text-center">
              <div class="mb-3">
                <i class="fas fa-users fa-3x text-warning"></i>
              </div>
              <h5 class="card-title">Anggota Kelas</h5>
              <p class="card-text text-muted">Kelola anggota kelas</p>
              <a href="<?= $base_url ?>/class/members.php?id=<?= $class_id ?>" class="btn btn-warning btn-lg">
                <i class="fas fa-user-friends me-2"></i>Lihat Anggota
              </a>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Navigasi Cepat</h5>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="<?= ($_SESSION['role'] === 'teacher') ? "$base_url/teacher/dashboard.php" : "$base_url/student/dashboard.php" ?>" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
            </a>
            <?php if ($_SESSION['role'] === 'teacher'): ?>
              <a href="<?= $base_url ?>/class/add_quiz.php?id=<?= $class_id ?>" class="btn btn-outline-primary">
                <i class="fas fa-plus me-2"></i>Buat Kuis Baru
              </a>
              <a href="<?= $base_url ?>/class/upload_material.php?id=<?= $class_id ?>" class="btn btn-outline-success">
                <i class="fas fa-upload me-2"></i>Upload Materi
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if ($_SESSION['role'] === 'teacher'): ?>
      <div class="card mt-3">
        <div class="card-header">
          <h5 class="mb-0">Statistik Kelas</h5>
        </div>
        <div class="card-body">
          <?php
          $member_count = $conn->query("SELECT COUNT(*) as count FROM class_members WHERE class_id=$class_id")->fetch_assoc()['count'];
          $material_count = $conn->query("SELECT COUNT(*) as count FROM materials WHERE class_id=$class_id")->fetch_assoc()['count'];
          $quiz_count = $conn->query("SELECT COUNT(*) as count FROM quizzes WHERE class_id=$class_id")->fetch_assoc()['count'];
          ?>
          <div class="row text-center">
            <div class="col-4">
              <div class="h5 mb-0 text-primary"><?= $member_count ?></div>
              <small class="text-muted">Anggota</small>
            </div>
            <div class="col-4">
              <div class="h5 mb-0 text-success"><?= $material_count ?></div>
              <small class="text-muted">Materi</small>
            </div>
            <div class="col-4">
              <div class="h5 mb-0 text-warning"><?= $quiz_count ?></div>
              <small class="text-muted">Kuis</small>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include $base_path . '/includes/footer.php'; ?>
