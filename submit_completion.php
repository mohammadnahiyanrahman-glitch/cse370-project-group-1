<?php
require_once 'config/db.php';
require_once 'includes/header.php';
requireLogin();

// Fetch approved promises for the dropdown
$stmt = $pdo->query("SELECT post_id, title FROM promise_posts WHERE status = 'approved' ORDER BY post_date DESC");
$promises = $stmt->fetchAll();

$preselected_id = $_GET['promise_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token']);
    
    try {
        $pdo->beginTransaction();
        
        $promise_id = $_POST['promise_id'];
        $desc = trim($_POST['completion_description']);
        
        // Handle Proof
        $proof_link = trim($_POST['proof_link'] ?? '');
        $proof_file = $_FILES['proof_file'] ?? null;
        if (empty($proof_link) && empty($proof_file['name'])) {
            throw new Exception("You must provide either a proof link or upload a file.");
        }
        
        $proof_id = handle_proof_upload($proof_file, $proof_link, $pdo, $_SESSION['user_id']);
        
        // Insert Completion Post
        $stmt = $pdo->prepare("INSERT INTO completion_posts (user_id, promise_id, completion_description, proof_id, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $promise_id, $desc, $proof_id]);
        
        $pdo->commit();
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Completion evidence submitted and pending moderator approval.'];
        header("Location: index.php");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-card p-5">
            <h2 class="font-playfair fw-bold mb-4 text-success"><i class="fa-solid fa-check-circle me-2"></i>Log a Fulfillled Promise</h2>
            <p class="text-muted mb-4">Did a politician actually deliver? Log the proof here so we can update their Oil Meter.</p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Select the Promise *</label>
                    <select name="promise_id" class="form-select" required>
                        <option value="">-- Search and Select a Promise --</option>
                        <?php foreach($promises as $p): ?>
                            <option value="<?= $p['post_id'] ?>" <?= $preselected_id == $p['post_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Completion Details *</label>
                    <textarea name="completion_description" class="form-control" rows="4" required placeholder="Describe how and when this promise was fulfilled..."></textarea>
                </div>
                
                <div class="p-3 border rounded mb-4 border-success">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-link text-muted me-2"></i>Evidence (Required)</h5>
                    <p class="small text-muted">Provide a link to a news article/video, OR upload a screenshot/video (Max 20MB) showing the completion.</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Proof Link (URL)</label>
                        <input type="url" name="proof_link" class="form-control" placeholder="https://...">
                    </div>
                    
                    <div class="mb-2 text-center text-muted fw-bold">OR</div>
                    
                    <div>
                        <label class="form-label">Upload File</label>
                        <input type="file" name="proof_file" class="form-control" accept="image/*,video/mp4,video/webm">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success fw-bold py-3 w-100 fs-5 rounded-pill shadow">Submit Evidence</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
