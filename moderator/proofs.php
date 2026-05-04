<?php
require_once '../config/db.php';
require_once '../includes/header.php';
requireModerator();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrfToken($_POST['csrf_token']);
    $proof_id = $_POST['proof_id'];
    $verdict = $_POST['action'];
    $notes = trim($_POST['notes']);
    
    if (in_array($verdict, ['valid', 'invalid'])) {
        // Check if verification already exists
        $stmt = $pdo->prepare("SELECT verification_id FROM verification WHERE proof_id = ?");
        $stmt->execute([$proof_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE verification SET verified_by = ?, verdict = ?, notes = ?, verified_at = CURRENT_TIMESTAMP WHERE proof_id = ?");
            $stmt->execute([$_SESSION['user_id'], $verdict, $notes, $proof_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO verification (proof_id, verified_by, verdict, notes, verified_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$proof_id, $_SESSION['user_id'], $verdict, $notes]);
        }
        
        $_SESSION['toast'] = ['type' => 'success', 'message' => "Proof marked as $verdict."];
        header("Location: proofs.php");
        exit;
    }
}

// Fetch proofs that haven't been verified yet
$stmt = $pdo->query("
    SELECT p.*, u.username,
           pp.title as promise_title, cp.completion_description
    FROM proofs p
    JOIN users u ON p.uploaded_by = u.user_id
    LEFT JOIN promise_posts pp ON pp.proof_id = p.proof_id
    LEFT JOIN completion_posts cp ON cp.proof_id = p.proof_id
    LEFT JOIN verification v ON p.proof_id = v.proof_id
    WHERE v.verdict IS NULL OR v.verdict = 'pending'
    ORDER BY p.uploaded_at DESC
");
$proofs = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="index.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="posts.php" class="list-group-item list-group-item-action">Promises</a>
            <a href="completions.php" class="list-group-item list-group-item-action">Completions</a>
            <a href="reports.php" class="list-group-item list-group-item-action">Reports</a>
            <a href="edits.php" class="list-group-item list-group-item-action">Profile Edits</a>
            <a href="users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="proofs.php" class="list-group-item list-group-item-action active bg-navy border-navy">Verify Proofs</a>
        </div>
    </div>
    
    <div class="col-md-9">
        <h3 class="font-playfair fw-bold border-bottom pb-2 mb-4">Verify Uploaded Proofs (<?= count($proofs) ?>)</h3>
        <p class="text-muted">Review standalone proofs attached to promises and completions to ensure they are legitimate.</p>
        
        <?php if (empty($proofs)): ?>
            <div class="alert alert-success">No pending proofs to verify.</div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($proofs as $pr): ?>
                    <div class="col">
                        <div class="glass-card p-3 h-100">
                            <div class="mb-3 border-bottom pb-2">
                                <?php if ($pr['promise_title']): ?>
                                    <span class="badge bg-navy mb-2">Attached to Promise</span><br>
                                    <small class="fw-bold"><?= htmlspecialchars($pr['promise_title']) ?></small>
                                <?php elseif ($pr['completion_description']): ?>
                                    <span class="badge bg-success mb-2">Attached to Completion</span><br>
                                    <small class="fw-bold"><?= htmlspecialchars(substr($pr['completion_description'], 0, 50)) ?>...</small>
                                <?php else: ?>
                                    <span class="badge bg-secondary mb-2">Orphaned Proof</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-2 rounded mb-3 text-center border" style="height: 180px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <?php if ($pr['proof_type'] === 'link'): ?>
                                    <a href="<?= htmlspecialchars($pr['file_path']) ?>" target="_blank" class="btn btn-primary"><i class="fa-solid fa-link me-2"></i>Open Link</a>
                                <?php elseif ($pr['proof_type'] === 'image'): ?>
                                    <img src="/polititrack/uploads/<?= htmlspecialchars($pr['file_path']) ?>" class="img-fluid" style="max-height: 100%; object-fit: contain;">
                                <?php elseif ($pr['proof_type'] === 'video'): ?>
                                    <video src="/polititrack/uploads/<?= htmlspecialchars($pr['file_path']) ?>" controls class="img-fluid" style="max-height: 100%;"></video>
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="proof_id" value="<?= $pr['proof_id'] ?>">
                                
                                <div class="mb-2">
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Moderator notes (optional)">
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="action" value="valid" class="btn btn-success btn-sm flex-grow-1 fw-bold"><i class="fa-solid fa-check"></i> Valid</button>
                                    <button type="submit" name="action" value="invalid" class="btn btn-danger btn-sm flex-grow-1 fw-bold"><i class="fa-solid fa-xmark"></i> Invalid</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
