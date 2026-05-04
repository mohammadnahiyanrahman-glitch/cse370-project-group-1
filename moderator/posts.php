<?php
require_once '../config/db.php';
require_once '../includes/header.php';
requireModerator();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrfToken($_POST['csrf_token']);
    $post_id = $_POST['post_id'];
    $action = $_POST['action']; // 'approved' or 'rejected'
    
    if (in_array($action, ['approved', 'rejected'])) {
        $stmt = $pdo->prepare("UPDATE promise_posts SET status = ? WHERE post_id = ?");
        $stmt->execute([$action, $post_id]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => "Post $action successfully."];
        header("Location: posts.php");
        exit;
    }
}

$stmt = $pdo->query("
    SELECT pp.*, p.name AS politician_name, pr.proof_type, pr.file_path, u.username
    FROM promise_posts pp
    JOIN politicians p ON pp.politician_id = p.politician_id
    JOIN proofs pr ON pp.proof_id = pr.proof_id
    JOIN users u ON pp.user_id = u.user_id
    WHERE pp.status = 'pending'
    ORDER BY pp.post_date ASC
");
$posts = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <!-- Reusable Sidebar -->
        <div class="list-group shadow-sm">
            <a href="index.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="posts.php" class="list-group-item list-group-item-action active bg-navy border-navy">Promises</a>
            <a href="completions.php" class="list-group-item list-group-item-action">Completions</a>
            <a href="reports.php" class="list-group-item list-group-item-action">Reports</a>
            <a href="edits.php" class="list-group-item list-group-item-action">Profile Edits</a>
            <a href="users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="proofs.php" class="list-group-item list-group-item-action">Verify Proofs</a>
        </div>
    </div>
    
    <div class="col-md-9">
        <h3 class="font-playfair fw-bold border-bottom pb-2 mb-4">Pending Promise Posts (<?= count($posts) ?>)</h3>
        
        <?php if (empty($posts)): ?>
            <div class="alert alert-success">No pending promises to review.</div>
        <?php else: ?>
            <?php foreach ($posts as $p): ?>
                <div class="glass-card p-4 mb-4">
                    <div class="d-flex justify-content-between">
                        <h4 class="fw-bold"><?= htmlspecialchars($p['title']) ?></h4>
                        <span class="badge bg-warning h-100">Pending</span>
                    </div>
                    <p class="text-muted small">By <?= htmlspecialchars($p['username']) ?> • Politician: <?= htmlspecialchars($p['politician_name']) ?> • Date: <?= $p['promise_date'] ?></p>
                    <p><?= nl2br(htmlspecialchars($p['promise_description'])) ?></p>
                    
                    <div class="p-3 rounded mb-3 border">
                        <h6 class="fw-bold">Proof Attached:</h6>
                        <?php if ($p['proof_type'] === 'link'): ?>
                            <a href="<?= htmlspecialchars($p['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View Link <i class="fa-solid fa-external-link"></i></a>
                        <?php elseif ($p['proof_type'] === 'image'): ?>
                            <img src="/polititrack/uploads/<?= htmlspecialchars($p['file_path']) ?>" style="max-height: 150px;" class="img-fluid rounded border">
                        <?php elseif ($p['proof_type'] === 'video'): ?>
                            <video src="/polititrack/uploads/<?= htmlspecialchars($p['file_path']) ?>" controls style="max-height: 150px;" class="img-fluid rounded border"></video>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="post_id" value="<?= $p['post_id'] ?>">
                        <button type="submit" name="action" value="approved" class="btn btn-success fw-bold flex-grow-1"><i class="fa-solid fa-check me-2"></i>Approve</button>
                        <button type="submit" name="action" value="rejected" class="btn btn-danger fw-bold flex-grow-1"><i class="fa-solid fa-xmark me-2"></i>Reject</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
