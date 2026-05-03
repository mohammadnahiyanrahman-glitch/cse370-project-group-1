<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<div class='container mt-5 alert alert-danger'>Post not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch Promise
$stmt = $pdo->prepare("
    SELECT pp.*, p.name AS politician_name, p.photo AS politician_photo, c.name AS category_name, 
           pr.proof_type, pr.file_path as proof_path, u.username as author_name
    FROM promise_posts pp
    JOIN politicians p ON pp.politician_id = p.politician_id
    JOIN users u ON pp.user_id = u.user_id
    LEFT JOIN categories c ON pp.category_id = c.category_id
    JOIN proofs pr ON pp.proof_id = pr.proof_id
    WHERE pp.post_id = ? AND pp.status = 'approved'
");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    echo "<div class='container mt-5 alert alert-danger'>Post not found or pending approval.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Handle Comment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    requireLogin();
    verifyCsrfToken($_POST['csrf_token']);
    $content = trim($_POST['content']);
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$id, $_SESSION['user_id'], $content]);
        header("Location: post.php?id=$id");
        exit;
    }
}

// Handle Report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_content'])) {
    requireLogin();
    verifyCsrfToken($_POST['csrf_token']);
    $reason = trim($_POST['report_reason']);
    $target_type = $_POST['target_type'];
    $target_id = $_POST['target_id'];
    
    $col = 'post_id';
    if ($target_type == 'completion') $col = 'completion_id';
    if ($target_type == 'comment') $col = 'comment_id';
    
    $stmt = $pdo->prepare("INSERT INTO reports (reporter_id, $col, report_reason) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $target_id, $reason]);
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Report submitted successfully.'];
    header("Location: post.php?id=$id");
    exit;
}

// Fetch Completions
$stmt = $pdo->prepare("
    SELECT cp.*, pr.proof_type, pr.file_path as proof_path, u.username as author_name
    FROM completion_posts cp
    JOIN proofs pr ON cp.proof_id = pr.proof_id
    JOIN users u ON cp.user_id = u.user_id
    WHERE cp.promise_id = ? AND cp.status = 'approved'
    ORDER BY cp.post_date ASC
");
$stmt->execute([$id]);
$completions = $stmt->fetchAll();

// Fetch Comments
$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.profile_picture 
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.post_id = ? AND c.is_removed = 0
    ORDER BY c.comment_date DESC
");
$stmt->execute([$id]);
$comments = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Main Promise Post -->
        <div class="glass-card p-4 mb-4">
            <div class="d-flex align-items-center mb-3">
                <?php $photo = $post['politician_photo'] ? '/polititrack/uploads/' . htmlspecialchars($post['politician_photo']) : 'https://ui-avatars.com/api/?name='.urlencode($post['politician_name']).'&background=FFC107&color=000'; ?>
                <img src="<?= $photo ?>" class="avatar-circle me-3" alt="">
                <div>
                    <h5 class="mb-0 fw-bold"><a href="politician.php?id=<?= $post['politician_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($post['politician_name']) ?></a></h5>
                    <small class="text-muted"><?= htmlspecialchars($post['category_name']) ?> • Made on <?= date('M d, Y', strtotime($post['promise_date'])) ?></small>
                </div>
            </div>
            
            <h2 class="font-playfair fw-bold mb-3"><?= htmlspecialchars($post['title']) ?></h2>
            <p class="fs-5 mb-4"><?= nl2br(htmlspecialchars($post['promise_description'])) ?></p>
            
            <!-- Proof -->
            <div class="p-3 rounded border">
                <h6 class="fw-bold mb-2"><i class="fa-solid fa-link me-2"></i>Proof of Promise</h6>
                <?php if ($post['proof_type'] === 'link'): ?>
                    <a href="<?= htmlspecialchars($post['proof_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill">View External Link <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i></a>
                <?php elseif ($post['proof_type'] === 'image'): ?>
                    <img src="/polititrack/uploads/<?= htmlspecialchars($post['proof_path']) ?>" class="img-fluid rounded shadow-sm" style="max-height: 300px;" alt="Proof Image">
                <?php elseif ($post['proof_type'] === 'video'): ?>
                    <video src="/polititrack/uploads/<?= htmlspecialchars($post['proof_path']) ?>" controls class="img-fluid rounded shadow-sm" style="max-height: 300px;"></video>
                <?php endif; ?>
            </div>
            
            <div class="mt-3 text-muted small d-flex justify-content-between align-items-center">
                <span>Posted by <?= htmlspecialchars($post['author_name']) ?> on <?= date('M d, Y', strtotime($post['post_date'])) ?></span>
                <?php if (isLoggedIn()): ?>
                    <button class="btn btn-link text-danger p-0 small text-decoration-none" data-bs-toggle="modal" data-bs-target="#reportModal" onclick="setReport('post', <?= $post['post_id'] ?>)"><i class="fa-solid fa-flag"></i> Report</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Completions -->
        <?php if (!empty($completions)): ?>
            <h4 class="font-playfair border-bottom border-success pb-2 mb-3 text-success"><i class="fa-solid fa-check-circle me-2"></i>Fulfillments</h4>
            <?php foreach ($completions as $cp): ?>
                <div class="glass-card border-success border p-4 mb-4 bg-white">
                    <p class="fs-5 mb-3"><?= nl2br(htmlspecialchars($cp['completion_description'])) ?></p>
                    <div class="p-3 rounded border border-success border-opacity-25">
                        <h6 class="fw-bold mb-2 text-success">Evidence of Completion</h6>
                        <?php if ($cp['proof_type'] === 'link'): ?>
                            <a href="<?= htmlspecialchars($cp['proof_path']) ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill">View External Link</a>
                        <?php elseif ($cp['proof_type'] === 'image'): ?>
                            <img src="/polititrack/uploads/<?= htmlspecialchars($cp['proof_path']) ?>" class="img-fluid rounded shadow-sm" style="max-height: 200px;" alt="Proof Image">
                        <?php elseif ($cp['proof_type'] === 'video'): ?>
                            <video src="/polititrack/uploads/<?= htmlspecialchars($cp['proof_path']) ?>" controls class="img-fluid rounded shadow-sm" style="max-height: 200px;"></video>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3 text-muted small d-flex justify-content-between align-items-center">
                        <span>Logged by <?= htmlspecialchars($cp['author_name']) ?> on <?= date('M d, Y', strtotime($cp['post_date'])) ?></span>
                        <?php if (isLoggedIn()): ?>
                            <button class="btn btn-link text-danger p-0 small text-decoration-none" data-bs-toggle="modal" data-bs-target="#reportModal" onclick="setReport('completion', <?= $cp['completion_id'] ?>)"><i class="fa-solid fa-flag"></i> Report</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Comments -->
        <h4 class="font-playfair border-bottom pb-2 mb-3 mt-5"><i class="fa-solid fa-comments me-2"></i>Discussion (<?= count($comments) ?>)</h4>
        
        <?php if (isLoggedIn()): ?>
            <form method="POST" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="submit_comment" value="1">
                <div class="mb-2">
                    <textarea name="content" class="form-control" rows="3" placeholder="Join the discussion..." required></textarea>
                </div>
                <button type="submit" class="btn btn-navy text-white bg-navy btn-sm px-4 fw-bold rounded-pill">Post Comment</button>
            </form>
        <?php else: ?>
            <div class="alert alert-light border"><a href="login.php" class="fw-bold text-gold">Log in</a> to post a comment.</div>
        <?php endif; ?>

        <?php foreach ($comments as $c): ?>
            <div class="d-flex mb-3">
                <?php $avatar = $c['profile_picture'] ? '/polititrack/uploads/' . htmlspecialchars($c['profile_picture']) : 'https://ui-avatars.com/api/?name='.urlencode($c['username']).'&background=0B192C&color=fff'; ?>
                <img src="<?= $avatar ?>" class="rounded-circle me-3 mt-1" style="width: 40px; height: 40px;" alt="">
                <div class="glass-card p-3 w-100 bg-white">
                    <div class="d-flex justify-content-between">
                        <strong class="text-navy"><?= htmlspecialchars($c['username']) ?></strong>
                        <small class="text-muted"><?= time_elapsed_string($c['comment_date']) ?></small>
                    </div>
                    <p class="mb-1 mt-1"><?= nl2br(htmlspecialchars($c['content'])) ?></p>
                    <?php if (isLoggedIn()): ?>
                        <div class="text-end">
                            <button class="btn btn-link text-danger p-0 small text-decoration-none" style="font-size: 0.8rem;" data-bs-toggle="modal" data-bs-target="#reportModal" onclick="setReport('comment', <?= $c['comment_id'] ?>)">Report</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

    <!-- Sidebar actions -->
    <div class="col-lg-4">
        <div class="glass-card p-4 sticky-top" style="top: 20px;">
            <h5 class="font-playfair mb-3">Take Action</h5>
            <a href="submit_completion.php?promise_id=<?= $post['post_id'] ?>" class="btn btn-success w-100 mb-3 fw-bold rounded-pill"><i class="fa-solid fa-check me-2"></i>Log Fulfillment</a>
            <p class="text-muted small">If this promise has been fulfilled, you can submit evidence to update the politician's Oil Meter.</p>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
          <div class="modal-header">
            <h5 class="modal-title font-playfair fw-bold text-danger"><i class="fa-solid fa-flag me-2"></i>Report Content</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="report_content" value="1">
            <input type="hidden" name="target_type" id="reportTargetType">
            <input type="hidden" name="target_id" id="reportTargetId">
            <div class="mb-3">
                <label class="form-label fw-bold">Reason for reporting:</label>
                <textarea name="report_reason" class="form-control" rows="3" required placeholder="Spam, misinformation, inappropriate content..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-danger w-100 fw-bold">Submit Report</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function setReport(type, id) {
    document.getElementById('reportTargetType').value = type;
    document.getElementById('reportTargetId').value = id;
}
</script>

<?php require_once 'includes/footer.php'; ?>
