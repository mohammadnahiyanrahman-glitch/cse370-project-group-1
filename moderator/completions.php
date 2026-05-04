<?php
require_once '../config/db.php';
require_once '../includes/header.php';
requireModerator();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrfToken($_POST['csrf_token']);
    $completion_id = $_POST['completion_id'];
    $action = $_POST['action']; // 'approved' or 'rejected'
    
    if (in_array($action, ['approved', 'rejected'])) {
        $stmt = $pdo->prepare("UPDATE completion_posts SET status = ? WHERE completion_id = ?");
        $stmt->execute([$action, $completion_id]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => "Completion $action successfully."];
        header("Location: completions.php");
        exit;
    }
}

$stmt = $pdo->query("
    SELECT cp.*, pp.title as promise_title, p.name AS politician_name, pr.proof_type, pr.file_path, u.username
    FROM completion_posts cp
    JOIN promise_posts pp ON cp.promise_id = pp.post_id
    JOIN politicians p ON pp.politician_id = p.politician_id
    JOIN proofs pr ON cp.proof_id = pr.proof_id
    JOIN users u ON cp.user_id = u.user_id
    WHERE cp.status = 'pending'
    ORDER BY cp.post_date ASC
");
$completions = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <!-- Reusable Sidebar -->
        <div class="list-group shadow-sm">
            <a href="index.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="posts.php" class="list-group-item list-group-item-action">Promises</a>
            <a href="completions.php" class="list-group-item list-group-item-action active bg-navy border-navy">Completions</a>
            <a href="reports.php" class="list-group-item list-group-item-action">Reports</a>
            <a href="edits.php" class="list-group-item list-group-item-action">Profile Edits</a>
            <a href="users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="proofs.php" class="list-group-item list-group-item-action">Verify Proofs</a>
        </div>
    </div>
    
    <div class="col-md-9">
        <h3 class="font-playfair fw-bold border-bottom pb-2 mb-4">Pending Completions (<?= count($completions) ?>)</h3>
        
        <?php if (empty($completions)): ?>
            <div class="alert alert-success">No pending completions to review.</div>
        <?php else: ?>
            <?php foreach ($completions as $cp): ?>
                <div class="glass-card p-4 mb-4 border-success border">
                    <div class="d-flex justify-content-between">
                        <h5 class="fw-bold text-success"><i class="fa-solid fa-check-circle me-1"></i> Fulfillment Evidence</h5>
                        <span class="badge bg-warning h-100">Pending</span>
                    </div>
                    <p class="text-muted small">Submitted by <?= htmlspecialchars($cp['username']) ?> for Promise: <strong><?= htmlspecialchars($cp['promise_title']) ?></strong> (<?= htmlspecialchars($cp['politician_name']) ?>)</p>
                    <p><?= nl2br(htmlspecialchars($cp['completion_description'])) ?></p>
                    
                    <div class="p-3 rounded mb-3 border">
                        <h6 class="fw-bold">Proof Attached:</h6>
                        <?php if ($cp['proof_type'] === 'link'): ?>
                            <a href="<?= htmlspecialchars($cp['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View Link <i class="fa-solid fa-external-link"></i></a>
                        <?php elseif ($cp['proof_type'] === 'image'): ?>
                            <img src="/polititrack/uploads/<?= htmlspecialchars($cp['file_path']) ?>" style="max-height: 150px;" class="img-fluid rounded border">
                        <?php elseif ($cp['proof_type'] === 'video'): ?>
                            <video src="/polititrack/uploads/<?= htmlspecialchars($cp['file_path']) ?>" controls style="max-height: 150px;" class="img-fluid rounded border"></video>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="completion_id" value="<?= $cp['completion_id'] ?>">
                        <button type="submit" name="action" value="approved" class="btn btn-success fw-bold flex-grow-1"><i class="fa-solid fa-check me-2"></i>Approve</button>
                        <button type="submit" name="action" value="rejected" class="btn btn-danger fw-bold flex-grow-1"><i class="fa-solid fa-xmark me-2"></i>Reject</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
