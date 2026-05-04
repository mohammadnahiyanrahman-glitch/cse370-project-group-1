<?php
require_once '../config/db.php';
require_once '../includes/header.php';
requireModerator();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrfToken($_POST['csrf_token']);
    $report_id = $_POST['report_id'];
    $action = $_POST['action'];
    
    if ($action === 'dismiss') {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'dismissed' WHERE report_id = ?");
        $stmt->execute([$report_id]);
        $_SESSION['toast'] = ['type' => 'info', 'message' => 'Report dismissed.'];
    } elseif ($action === 'remove') {
        // Needs to identify what to remove based on the report
        $stmt = $pdo->prepare("SELECT post_id, completion_id, comment_id FROM reports WHERE report_id = ?");
        $stmt->execute([$report_id]);
        $r = $stmt->fetch();
        
        if ($r['post_id']) {
            $pdo->prepare("UPDATE promise_posts SET status = 'rejected' WHERE post_id = ?")->execute([$r['post_id']]);
        } elseif ($r['completion_id']) {
            $pdo->prepare("UPDATE completion_posts SET status = 'rejected' WHERE completion_id = ?")->execute([$r['completion_id']]);
        } elseif ($r['comment_id']) {
            $pdo->prepare("UPDATE comments SET is_removed = 1 WHERE comment_id = ?")->execute([$r['comment_id']]);
        }
        
        $stmt = $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE report_id = ?");
        $stmt->execute([$report_id]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Content removed and report marked as reviewed.'];
    }
    
    header("Location: reports.php");
    exit;
}

$stmt = $pdo->query("
    SELECT r.*, u.username as reporter_name
    FROM reports r
    JOIN users u ON r.reporter_id = u.user_id
    WHERE r.status = 'pending'
    ORDER BY r.reported_at ASC
");
$reports = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="index.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="posts.php" class="list-group-item list-group-item-action">Promises</a>
            <a href="completions.php" class="list-group-item list-group-item-action">Completions</a>
            <a href="reports.php" class="list-group-item list-group-item-action active bg-navy border-navy">Reports</a>
            <a href="edits.php" class="list-group-item list-group-item-action">Profile Edits</a>
            <a href="users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="proofs.php" class="list-group-item list-group-item-action">Verify Proofs</a>
        </div>
    </div>
    
    <div class="col-md-9">
        <h3 class="font-playfair fw-bold border-bottom pb-2 mb-4">Pending Reports (<?= count($reports) ?>)</h3>
        
        <?php if (empty($reports)): ?>
            <div class="alert alert-success">No pending reports.</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($reports as $r): ?>
                    <div class="list-group-item p-4 mb-3 border border-danger rounded glass-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="text-danger mb-0"><i class="fa-solid fa-flag me-2"></i>Report by <?= htmlspecialchars($r['reporter_name']) ?></h5>
                            <small class="text-muted"><?= $r['reported_at'] ?></small>
                        </div>
                        <p class="mb-3"><strong>Reason:</strong> <?= nl2br(htmlspecialchars($r['report_reason'])) ?></p>
                        
                        <div class="p-3 rounded border mb-3">
                            <strong>Target:</strong>
                            <?php if ($r['post_id']): ?>
                                <a href="../post.php?id=<?= $r['post_id'] ?>" target="_blank">View Promise Post</a>
                            <?php elseif ($r['completion_id']): ?>
                                <span class="badge bg-success">Completion Post #<?= $r['completion_id'] ?></span>
                            <?php elseif ($r['comment_id']): ?>
                                <span class="badge bg-secondary">Comment #<?= $r['comment_id'] ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                            <button type="submit" name="action" value="remove" class="btn btn-danger btn-sm px-4 fw-bold">Remove Content</button>
                            <button type="submit" name="action" value="dismiss" class="btn btn-outline-secondary btn-sm px-4 fw-bold">Dismiss Report</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
