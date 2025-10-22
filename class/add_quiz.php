<?php
require_once '../config/path.php';
require_once '../config/db.php';
$page_title = 'Buat Kuis';
include $base_path . '/includes/header.php';

$user_id = $_SESSION['user_id'];

$class_id = $_GET['id'];
$class = $conn->query("SELECT * FROM classes WHERE id=$class_id AND teacher_id=" . $_SESSION['user_id'])->fetch_assoc();

if (!$class) {
    die("Kelas tidak ditemukan atau Anda bukan guru kelas ini!");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quiz_title = trim($_POST['quiz_title'] ?? '');
    $questions = $_POST['questions'] ?? [];
    $time_limit = isset($_POST['time_limit']) ? (int)$_POST['time_limit'] : 0; // dalam menit, 0 = tidak ada batas waktu

    $errors = [];

    // Validasi input
    if (empty($quiz_title)) {
        $errors[] = "Judul kuis tidak boleh kosong.";
    }

    if (empty($questions) || !is_array($questions)) {
        $errors[] = "Minimal satu pertanyaan harus ditambahkan.";
    }

    if ($time_limit < 0 || $time_limit > 180) { // max 3 jam
        $errors[] = "Batas waktu harus antara 0-180 menit.";
    }

    $valid_questions = [];
    foreach ($questions as $index => $q) {
        $question_text = trim($q['text'] ?? '');
        $question_type = $q['type'] ?? '';

        if (empty($question_text)) {
            $errors[] = "Pertanyaan " . ($index + 1) . " tidak boleh kosong.";
            continue;
        }

        if (!in_array($question_type, ['multiple_choice', 'essay'])) {
            $errors[] = "Tipe pertanyaan " . ($index + 1) . " tidak valid.";
            continue;
        }

        if ($question_type === 'multiple_choice') {
            $options = $q['options'] ?? [];
            $correct = $q['correct'] ?? '';

            $filled_options = array_filter($options, function($opt) { return !empty(trim($opt)); });
            if (count($filled_options) < 2) {
                $errors[] = "Pertanyaan " . ($index + 1) . " harus memiliki minimal 2 pilihan.";
                continue;
            }

            if (empty($correct) || !isset($options[$correct]) || empty(trim($options[$correct]))) {
                $errors[] = "Jawaban benar untuk pertanyaan " . ($index + 1) . " belum dipilih.";
                continue;
            }
        }

        $valid_questions[] = $q;
    }

    if (empty($errors)) {
        try {
            // Insert quiz
            $stmt = safe_query($conn, "INSERT INTO quizzes (class_id, title, created_by, created_at) VALUES (?, ?, ?, NOW())", [$class_id, $quiz_title, $_SESSION['user_id']], "isi");
            if (!$stmt) {
                throw new Exception("Gagal membuat kuis.");
            }
            $quiz_id = $conn->insert_id;

            // Insert questions
            foreach ($valid_questions as $q) {
                $question_text = trim($q['text']);
                $question_type = ($q['type'] === 'multiple_choice') ? 'mcq' : 'essay';
                $points = 1; // Default points

                $stmt = safe_query($conn, "INSERT INTO questions (quiz_id, type, text, points) VALUES (?, ?, ?, ?)", [$quiz_id, $question_type, $question_text, $points], "issi");
                if (!$stmt) {
                    throw new Exception("Gagal menyimpan pertanyaan.");
                }
                $question_id = $conn->insert_id;

                // If MCQ, insert choices
                if ($question_type === 'mcq' && isset($q['options'])) {
                    $correct = $q['correct'];
                    foreach ($q['options'] as $key => $option_text) {
                        $option_text = trim($option_text);
                        if (!empty($option_text)) {
                            $is_correct = ($key === $correct) ? 1 : 0;
                            $stmt = safe_query($conn, "INSERT INTO choices (question_id, text, is_correct) VALUES (?, ?, ?)", [$question_id, $option_text, $is_correct], "isi");
                            if (!$stmt) {
                                throw new Exception("Gagal menyimpan pilihan jawaban.");
                            }
                        }
                    }
                }
            }

            error_log("Quiz created: ID=$quiz_id, Title=$quiz_title, Class ID=$class_id");
            header("Location: $base_url/class/quizzes.php?id=$class_id&success=1");
            exit;
        } catch (Exception $e) {
            error_log("Quiz creation error: " . $e->getMessage());
            $error = "Terjadi kesalahan saat menyimpan kuis. Silakan coba lagi.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<div class="container-fluid">
  <h4>Buat Kuis untuk <?= htmlspecialchars($class['title']) ?></h4>

  <?php if (!empty($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

  <form method="POST" id="quizForm">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Informasi Kuis</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label for="quiz_title" class="form-label">Judul Kuis</label>
          <input type="text" name="quiz_title" id="quiz_title" class="form-control" required>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Pertanyaan</h5>
        <button type="button" class="btn btn-outline-primary btn-sm" id="addQuestion">
          <i class="fas fa-plus"></i> Tambah Pertanyaan
        </button>
      </div>
      <div class="card-body">
        <div id="questions">
          <div class="question mb-4 border rounded p-3 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0">Pertanyaan 1</h6>
              <button type="button" class="btn btn-outline-danger btn-sm remove-question" style="display: none;">
                <i class="fas fa-trash"></i>
              </button>
            </div>
            <div class="mb-3">
              <label class="form-label">Teks Pertanyaan</label>
              <input type="text" name="questions[0][text]" class="form-control" placeholder="Masukkan teks pertanyaan" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Tipe Pertanyaan</label>
              <select name="questions[0][type]" class="form-control question-type" required>
                <option value="multiple_choice">Pilihan Ganda</option>
                <option value="essay">Essay</option>
              </select>
            </div>
            <div class="options" style="display: none;">
              <label class="form-label">Pilihan Jawaban</label>
              <div class="mb-2">
                <input type="text" name="questions[0][options][A]" placeholder="Pilihan A" class="form-control">
              </div>
              <div class="mb-2">
                <input type="text" name="questions[0][options][B]" placeholder="Pilihan B" class="form-control">
              </div>
              <div class="mb-2">
                <input type="text" name="questions[0][options][C]" placeholder="Pilihan C" class="form-control">
              </div>
              <div class="mb-2">
                <input type="text" name="questions[0][options][D]" placeholder="Pilihan D" class="form-control">
              </div>
              <div class="mb-3">
                <label class="form-label">Jawaban Benar</label>
                <select name="questions[0][correct]" class="form-control">
                  <option value="">Pilih jawaban benar</option>
                  <option value="A">A</option>
                  <option value="B">B</option>
                  <option value="C">C</option>
                  <option value="D">D</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between">
      <a href="<?= $base_url ?>/class/view.php?id=<?= $class_id ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Simpan Kuis
      </button>
    </div>
  </form>
</div>

<script>
let questionCount = 1;

document.getElementById('addQuestion').addEventListener('click', function() {
  questionCount++;
  const questionsDiv = document.getElementById('questions');
  const newQuestion = document.querySelector('.question').cloneNode(true);
  newQuestion.querySelector('h6').textContent = 'Pertanyaan ' + questionCount;

  // Show remove button for questions after the first
  const removeBtn = newQuestion.querySelector('.remove-question');
  if (questionCount > 1) {
    removeBtn.style.display = 'inline-block';
  }

  // Update names
  const inputs = newQuestion.querySelectorAll('input, select');
  inputs.forEach(input => {
    if (input.name) {
      input.name = input.name.replace('[0]', '[' + (questionCount - 1) + ']');
    }
    input.value = '';
  });

  questionsDiv.appendChild(newQuestion);
  updateQuestionTypes();
  updateRemoveButtons();
});

function updateQuestionTypes() {
  document.querySelectorAll('.question-type').forEach(select => {
    const optionsDiv = select.closest('.question').querySelector('.options');
    if (select.value === 'multiple_choice') {
      optionsDiv.style.display = 'block';
    } else {
      optionsDiv.style.display = 'none';
    }
    select.addEventListener('change', function() {
      const optionsDiv = this.closest('.question').querySelector('.options');
      if (this.value === 'multiple_choice') {
        optionsDiv.style.display = 'block';
      } else {
        optionsDiv.style.display = 'none';
      }
    });
  });
}

function updateRemoveButtons() {
  document.querySelectorAll('.remove-question').forEach(button => {
    button.addEventListener('click', function() {
      this.closest('.question').remove();
      questionCount--;
      // Renumber remaining questions
      document.querySelectorAll('.question h6').forEach((header, index) => {
        header.textContent = 'Pertanyaan ' + (index + 1);
      });
      // Hide remove button if only one question left
      const questions = document.querySelectorAll('.question');
      if (questions.length === 1) {
        questions[0].querySelector('.remove-question').style.display = 'none';
      }
    });
  });
}

updateQuestionTypes();
updateRemoveButtons();
</script>
<?php include $base_path . '/includes/footer.php'; ?>
