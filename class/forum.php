<?php
require_once '../config/path.php';
require_once '../config/db.php';
$page_title = 'Forum Diskusi';
include $base_path . '/includes/header.php';

$class_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['csrf_token'])) {
    // Prevent form resubmission on refresh
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['content'])) {
            $content = trim($_POST['content'] ?? '');
            if (empty($content)) {
                $error = "Konten post tidak boleh kosong.";
            } else {
                $stmt = safe_query($conn, "INSERT INTO forum_posts (class_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())", [$class_id, $user_id, $content], "iis");
                if ($stmt) {
                    error_log("Forum post created: User ID=$user_id, Class ID=$class_id");
                    $success = "Post berhasil dikirim!";
                    // Redirect to prevent resubmission
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $error = "Gagal mengirim post.";
                }
            }
        } elseif (isset($_POST['comment_content'])) {
            $post_id = $_POST['post_id'] ?? '';
            $parent_comment_id = isset($_POST['parent_comment_id']) ? $_POST['parent_comment_id'] : null;
            $comment_content = trim($_POST['comment_content'] ?? '');

            if (empty($comment_content)) {
                $error = "Konten komentar tidak boleh kosong.";
            } elseif (empty($post_id) || !is_numeric($post_id)) {
                $error = "Post ID tidak valid.";
            } else {
                $stmt = safe_query($conn, "INSERT INTO forum_comments (post_id, parent_comment_id, user_id, content, created_at) VALUES (?, ?, ?, ?, NOW())", [$post_id, $parent_comment_id, $user_id, $comment_content], "iiis");
                if ($stmt) {
                    error_log("Forum comment created: User ID=$user_id, Post ID=$post_id");
                    $success = "Komentar berhasil dikirim!";
                    // Redirect to prevent resubmission
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $error = "Gagal mengirim komentar.";
                }
            }
        } elseif (isset($_POST['edit_post'])) {
            $post_id = $_POST['edit_post_id'] ?? '';
            $new_content = trim($_POST['edit_content'] ?? '');

            if (empty($new_content)) {
                $error = "Konten edit tidak boleh kosong.";
            } elseif (empty($post_id) || !is_numeric($post_id)) {
                $error = "Post ID tidak valid.";
            } else {
                // Check if user owns the post
                $stmt = safe_query($conn, "SELECT user_id FROM forum_posts WHERE id = ?", [$post_id], "i");
                if ($stmt) {
                    $result = $stmt->get_result();
                    if ($result->num_rows === 1) {
                        $post = $result->fetch_assoc();
                        if ($post['user_id'] == $user_id) {
                            $stmt = safe_query($conn, "UPDATE forum_posts SET content = ?, updated_at = NOW() WHERE id = ?", [$new_content, $post_id], "si");
                            if ($stmt) {
                                error_log("Forum post edited: Post ID=$post_id, User ID=$user_id");
                                $success = "Post berhasil diedit!";
                                // Redirect to prevent resubmission
                                header("Location: " . $_SERVER['REQUEST_URI']);
                                exit;
                            } else {
                                $error = "Gagal mengedit post.";
                            }
                        } else {
                            $error = "Anda tidak memiliki izin untuk mengedit post ini.";
                        }
                    } else {
                        $error = "Post tidak ditemukan.";
                    }
                }
            }
        } elseif (isset($_POST['delete_post'])) {
            $post_id = $_POST['delete_post_id'] ?? '';

            if (empty($post_id) || !is_numeric($post_id)) {
                $error = "Post ID tidak valid.";
            } else {
                // Check if user owns the post
                $stmt = safe_query($conn, "SELECT user_id FROM forum_posts WHERE id = ?", [$post_id], "i");
                if ($stmt) {
                    $result = $stmt->get_result();
                    if ($result->num_rows === 1) {
                        $post = $result->fetch_assoc();
                        if ($post['user_id'] == $user_id) {
                            $stmt = safe_query($conn, "DELETE FROM forum_posts WHERE id = ?", [$post_id], "i");
                            if ($stmt) {
                                error_log("Forum post deleted: Post ID=$post_id, User ID=$user_id");
                                $success = "Post berhasil dihapus!";
                                // Redirect to prevent resubmission
                                header("Location: " . $_SERVER['REQUEST_URI']);
                                exit;
                            } else {
                                $error = "Gagal menghapus post.";
                            }
                        } else {
                            $error = "Anda tidak memiliki izin untuk menghapus post ini.";
                        }
                    } else {
                        $error = "Post tidak ditemukan.";
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Forum error: " . $e->getMessage());
        $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
    }
}

$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_posts = $conn->query("SELECT COUNT(*) as count FROM forum_posts WHERE class_id=$class_id")->fetch_assoc()['count'];
$total_pages = ceil($total_posts / $limit);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'f.created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

$query = $conn->query("
  SELECT f.*, u.name FROM forum_posts f
  JOIN users u ON f.user_id = u.id
  WHERE f.class_id=$class_id ORDER BY $sort $order LIMIT $limit OFFSET $offset
");

function display_comments($post_id, $parent_id = null, $level = 0) {
    global $conn;
    $query = $conn->prepare("SELECT c.*, u.name FROM forum_comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? AND c.parent_comment_id " . ($parent_id ? "= ?" : "IS NULL") . " ORDER BY c.created_at ASC");
    if ($parent_id) {
        $query->bind_param('ii', $post_id, $parent_id);
    } else {
        $query->bind_param('i', $post_id);
    }
    $query->execute();
    $comments = $query->get_result();

    while ($comment = $comments->fetch_assoc()) {
        $indent_class = $level > 0 ? "ms-4 border-start ps-3" : "";
        echo "<div class='comment $indent_class mb-2'>";
        echo "<div class='d-flex align-items-start'>";
        echo "<div class='flex-grow-1'>";
        echo "<strong class='text-primary'>" . htmlspecialchars($comment['name']) . ":</strong> ";
        echo "<span>" . nl2br(htmlspecialchars($comment['content'])) . "</span><br>";
        echo "<small class='text-muted'><i class='fas fa-clock me-1'></i>" . date('d/m/Y H:i', strtotime($comment['created_at'])) . "</small>";
        echo "</div>";
        echo "</div>";
        echo "<div class='mt-2'>";
        echo "<button class='btn btn-sm btn-link p-0 me-3' onclick='showReplyForm(" . $comment['id'] . ")'><i class='fas fa-reply me-1'></i>Balas</button>";
        echo "</div>";
        echo "<div id='reply-form-" . $comment['id'] . "' style='display:none;' class='mt-2'>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='post_id' value='$post_id'>";
        echo "<input type='hidden' name='parent_comment_id' value='" . $comment['id'] . "'>";
        echo "<div class='mb-2'>";
        echo "<textarea name='comment_content' class='form-control form-control-sm' rows='2' placeholder='Tulis balasan...' required></textarea>";
        echo "</div>";
        echo "<button type='submit' class='btn btn-sm btn-primary'><i class='fas fa-paper-plane me-1'></i>Kirim Balasan</button>";
        echo "</form>";
        echo "</div>";
        display_comments($post_id, $comment['id'], $level + 1);
        echo "</div>";
    }
}
?>
<div class="container-fluid">
  <h4><i class="fas fa-comments me-2"></i>Forum Diskusi</h4>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php elseif (isset($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-body">
      <h6 class="card-title"><i class="fas fa-edit me-2"></i>Tulis Post Baru</h6>
      <form method="POST">
        <div class="mb-3">
          <textarea name="content" class="form-control" rows="3" placeholder="Bagikan pemikiran Anda..." required></textarea>
        </div>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-paper-plane me-1"></i>Kirim
        </button>
      </form>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <a href="?id=<?= $class_id ?>&sort=f.created_at&order=DESC" class="btn btn-sm btn-outline-secondary">Sort by Newest</a>
      <a href="?id=<?= $class_id ?>&sort=f.created_at&order=ASC" class="btn btn-sm btn-outline-secondary">Sort by Oldest</a>
    </div>
    <small class="text-muted">Total: <?= $total_posts ?> post</small>
  </div>

  <?php while ($post = $query->fetch_assoc()): ?>
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex align-items-start">
          <div class="flex-grow-1">
            <h6 class="card-title mb-1">
              <i class="fas fa-user-circle me-2 text-primary"></i>
              <?= htmlspecialchars($post['name']) ?>
            </h6>
            <p class="card-text mb-2"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
            <small class="text-muted">
              <i class="fas fa-clock me-1"></i>
              <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
              <?php if (isset($post['updated_at']) && $post['updated_at'] && $post['updated_at'] != $post['created_at']): ?>
                <span class="text-info">(diedit)</span>
              <?php endif; ?>
            </small>
          </div>
        </div>
        <div class="mt-3">
          <button class="btn btn-sm btn-outline-primary me-2" onclick="showReplyForm(<?= $post['id'] ?>)">
            <i class="fas fa-reply me-1"></i>Balas
          </button>
          <?php if ($post['user_id'] == $user_id): ?>
            <button class="btn btn-sm btn-outline-warning me-2" onclick="showEditForm(<?= $post['id'] ?>)">
              <i class="fas fa-edit me-1"></i>Edit
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $post['id'] ?>)">
              <i class="fas fa-trash me-1"></i>Hapus
            </button>
          <?php endif; ?>
        </div>
        <div id="edit-form-<?= $post['id'] ?>" style="display:none;" class="mt-3">
          <form method="POST">
            <input type="hidden" name="edit_post_id" value="<?= $post['id'] ?>">
            <div class="mb-2">
              <textarea name="edit_content" class="form-control" rows="3" required><?= htmlspecialchars($post['content']) ?></textarea>
            </div>
            <button type="submit" name="edit_post" class="btn btn-sm btn-warning me-2">
              <i class="fas fa-save me-1"></i>Update
            </button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="hideEditForm(<?= $post['id'] ?>)">
              <i class="fas fa-times me-1"></i>Batal
            </button>
          </form>
        </div>
        <div id="reply-form-<?= $post['id'] ?>" style="display:none;" class="mt-3">
          <form method="POST">
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
            <div class="mb-2">
              <textarea name="comment_content" class="form-control" rows="2" placeholder="Tulis balasan..." required></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-primary me-2">
              <i class="fas fa-paper-plane me-1"></i>Kirim Balasan
            </button>
          </form>
        </div>
        <?php display_comments($post['id']); ?>
      </div>
    </div>
  <?php endwhile; ?>

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

  <a href="<?= $base_url ?>/class/view.php?id=<?= $class_id ?>" class="btn btn-secondary mt-3">⬅ Kembali</a>
</div>
<?php include $base_path . '/includes/footer.php'; ?>

<script>
function showReplyForm(commentId) {
  const form = document.getElementById('reply-form-' + commentId);
  form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function showEditForm(postId) {
  const form = document.getElementById('edit-form-' + postId);
  form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function hideEditForm(postId) {
  const form = document.getElementById('edit-form-' + postId);
  form.style.display = 'none';
}

function confirmDelete(postId) {
  if (confirm('Apakah Anda yakin ingin menghapus post ini?')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="delete_post_id" value="' + postId + '"><input type="hidden" name="delete_post" value="1">';
    document.body.appendChild(form);
    form.submit();
  }
}
</script>
</body>
</html>
