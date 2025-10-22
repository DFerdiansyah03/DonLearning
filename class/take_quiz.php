<?php
require_once '../config/path.php';
require_once '../config/db.php';
$page_title = htmlspecialchars($quiz['title']) . ' - Kuis';
include $base_path . '/includes/header.php';

$quiz_id = $_GET['id'];
$quiz = $conn->query("SELECT q.*, c.title as class_title FROM quizzes q JOIN classes c ON q.class_id = c.id WHERE q.id=$quiz_id")->fetch_assoc();

if (!$quiz) {
    die("Kuis tidak ditemukan!");
}

$is_teacher = ($_SESSION['role'] === 'teacher');
$class_id = $quiz['class_id'];

// Check if teacher owns the class
if ($is_teacher) {
    $check = $conn->query("SELECT id FROM classes WHERE id=$class_id AND teacher_id=" . $_SESSION['user_id'])->fetch_assoc();
    if (!$check) {
        die("Anda bukan guru kelas ini!");
    }
}

$questions = $conn->query("SELECT * FROM questions WHERE quiz_id=$quiz_id ORDER BY id");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_teacher) {
    try {
        // Create attempt
        $stmt = safe_query($conn, "INSERT INTO attempts (quiz_id, user_id, started_at) VALUES (?, ?, NOW())", [$quiz_id, $_SESSION['user_id']], "ii");
        if (!$stmt) {
            throw new Exception("Gagal membuat attempt kuis.");
        }
        $attempt_id = $conn->insert_id;

        $auto_score = 0;
        $total_points = 0;

        foreach ($questions as $q) {
            $question_id = $q['id'];
            $answer_text = isset($_POST['answers'][$question_id]) ? trim($_POST['answers'][$question_id]) : '';
            $selected_choice_id = isset($_POST['choices'][$question_id]) ? (int)$_POST['choices'][$question_id] : null;

            $is_correct = 0;
            $awarded_points = 0;

            if ($q['type'] === 'mcq' && $selected_choice_id) {
                // Check if correct
                $correct_choice = $conn->query("SELECT id FROM choices WHERE question_id=$question_id AND is_correct=1")->fetch_assoc();
                if ($correct_choice && $correct_choice['id'] == $selected_choice_id) {
                    $is_correct = 1;
                    $awarded_points = $q['points'];
                    $auto_score += $q['points'];
                }
            }

            $total_points += $q['points'];

            // Save answer
            $stmt = safe_query($conn, "INSERT INTO answers (attempt_id, question_id, answer_text, selected_choice_id, is_correct, awarded_points) VALUES (?, ?, ?, ?, ?, ?)", [$attempt_id, $question_id, $answer_text, $selected_choice_id, $is_correct, $awarded_points], "iisiii");
            if (!$stmt) {
                throw new Exception("Gagal menyimpan jawaban.");
            }
        }

        $final_score = ($total_points > 0) ? ($auto_score / $total_points) * 100 : 0;

        // Update attempt
        $stmt = safe_query($conn, "UPDATE attempts SET submitted_at=NOW(), auto_score=?, final_score=? WHERE id=?", [$auto_score, $final_score, $attempt_id], "ddi");
        if (!$stmt) {
            throw new Exception("Gagal mengupdate skor attempt.");
        }

        error_log("Quiz submitted: Attempt ID=$attempt_id, User ID={$_SESSION['user_id']}, Score=$final_score");
        $success = "Kuis selesai! Skor Anda: " . round($final_score, 2) . "%";
    } catch (Exception $e) {
        error_log("Quiz submission error: " . $e->getMessage());
        $error = "Terjadi kesalahan saat menyimpan jawaban. Silakan coba lagi.";
    }
}

if ($is_teacher) {
    // Show results for teacher
    $attempts = $conn->query("
        SELECT a.*, u.name
        FROM attempts a
        JOIN users u ON a.user_id = u.id
        WHERE a.quiz_id = $quiz_id
        ORDER BY a.submitted_at DESC
    ");

    // Handle grading for essays
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_attempt'])) {
        $attempt_id = $_POST['attempt_id'];
        $manual_score = $_POST['manual_score'];
        $stmt = $conn->prepare("UPDATE attempts SET manual_score=?, final_score=auto_score + ? WHERE id=?");
        $stmt->bind_param('ddi', $manual_score, $manual_score, $attempt_id);
        $stmt->execute();
        header("Location: $base_url/class/take_quiz.php?id=$quiz_id");
        exit;
    }
} else {
    // Check if student already attempted
    $attempt = $conn->query("SELECT * FROM attempts WHERE quiz_id=$quiz_id AND user_id=" . $_SESSION['user_id'])->fetch_assoc();
}
?>
<div class="container-fluid">
  <h4><i class="fas fa-question-circle me-2"></i><?= htmlspecialchars($quiz['title']) ?> - <?= htmlspecialchars($quiz['class_title']) ?></h4>

  <?php if ($is_teacher): ?>
    <h5 class="mt-4"><i class="fas fa-chart-bar me-2"></i>Hasil Kuis</h5>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Nama Murid</th>
            <th>Skor Auto</th>
            <th>Skor Manual</th>
            <th>Skor Total</th>
            <th>Waktu Submit</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($att = $attempts->fetch_assoc()): ?>
            <tr>
              <td><i class="fas fa-user me-2"></i><?= htmlspecialchars($att['name']) ?></td>
              <td><span class="badge bg-primary"><?= round($att['auto_score'], 2) ?>%</span></td>
              <td><span class="badge bg-secondary"><?= round($att['manual_score'], 2) ?>%</span></td>
              <td><span class="badge bg-success"><?= round($att['final_score'], 2) ?>%</span></td>
              <td><i class="fas fa-clock me-1"></i><?= $att['submitted_at'] ? date('d/m/Y H:i', strtotime($att['submitted_at'])) : 'Belum submit' ?></td>
              <td>
                <?php if ($att['status'] === 'submitted'): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="attempt_id" value="<?= $att['id'] ?>">
                    <input type="number" name="manual_score" step="0.01" placeholder="Skor manual" class="form-control form-control-sm d-inline-block w-auto" required>
                    <button type="submit" name="grade_attempt" class="btn btn-warning btn-sm ms-1">
                      <i class="fas fa-edit me-1"></i>Grade
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= $error ?></div>
    <?php elseif (isset($success)): ?>
      <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
    <?php elseif ($attempt): ?>
      <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Anda sudah mengerjakan kuis ini. Skor: <strong><?= round($attempt['final_score'], 2) ?>%</strong></div>
    <?php else: ?>
      <div class="alert alert-info"><i class="fas fa-clock me-2"></i>Waktu mulai: <span id="start-time"></span></div>
      <form method="POST" id="quiz-form" class="card p-4">
        <div class="card-body">
          <?php $i = 1; foreach ($questions as $q): ?>
            <div class="mb-4 question-item border-bottom pb-3">
              <h5 class="text-primary"><i class="fas fa-question me-2"></i>Pertanyaan <?= $i ?>.</h5>
              <p class="mb-3"><?= htmlspecialchars($q['text']) ?></p>
              <?php if ($q['type'] === 'mcq'): ?>
                <?php $choices = $conn->query("SELECT * FROM choices WHERE question_id={$q['id']} ORDER BY id"); ?>
                <?php while ($choice = $choices->fetch_assoc()): ?>
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="choices[<?= $q['id'] ?>]" value="<?= $choice['id'] ?>" id="q<?= $q['id'] ?>c<?= $choice['id'] ?>" required>
                    <label class="form-check-label" for="q<?= $q['id'] ?>c<?= $choice['id'] ?>">
                      <?= htmlspecialchars($choice['text']) ?>
                    </label>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <textarea name="answers[<?= $q['id'] ?>]" class="form-control" rows="4" placeholder="Tulis jawaban Anda di sini..." required></textarea>
              <?php endif; ?>
            </div>
          <?php $i++; endforeach; ?>
          <div class="text-center">
            <button type="submit" class="btn btn-success btn-lg" id="submit-btn">
              <i class="fas fa-paper-plane me-2"></i>Submit Kuis
            </button>
          </div>
        </div>
      </form>

      <script>
      // Simple timer to prevent rapid refresh/submit
      let startTime = new Date();
      document.getElementById('start-time').textContent = startTime.toLocaleTimeString();

      document.getElementById('quiz-form').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';

        // Prevent multiple submissions
        setTimeout(() => {
          if (!submitBtn.disabled) return;
          submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Kuis';
          submitBtn.disabled = false;
        }, 5000);
      });

      // Warn before leaving page
      window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = 'Apakah Anda yakin ingin meninggalkan halaman? Jawaban Anda belum disimpan.';
      });
      </script>
    <?php endif; ?>
  <?php endif; ?>

  <a href="<?= $base_url ?>/class/quizzes.php?id=<?= $class_id ?>" class="btn btn-secondary mt-3">
    <i class="fas fa-arrow-left me-1"></i>Kembali
  </a>
</div>
<?php include $base_path . '/includes/footer.php'; ?>
