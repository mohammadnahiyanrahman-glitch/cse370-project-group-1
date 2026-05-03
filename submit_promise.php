<?php
require_once 'config/db.php';
require_once 'includes/header.php';
requireLogin();

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Fetch politicians for dropdown
$stmt = $pdo->query("SELECT * FROM politicians ORDER BY name ASC");
$politicians = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token']);
    
    try {
        $pdo->beginTransaction();
        
        $politician_name = trim($_POST['politician_search']);
        $politician_id = null;
        
        // Find or create politician
        $stmt = $pdo->prepare("SELECT politician_id FROM politicians WHERE name = ?");
        $stmt->execute([$politician_name]);
        $existing = $stmt->fetchColumn();
        
        if ($existing) {
            $politician_id = $existing;
        } else {
            $stmt = $pdo->prepare("INSERT INTO politicians (name) VALUES (?)");
            $stmt->execute([$politician_name]);
            $politician_id = $pdo->lastInsertId();
            $_SESSION['toast'] = ['type' => 'info', 'message' => 'New politician profile created.'];
        }
        
        // Handle Proof
        $proof_link = trim($_POST['proof_link'] ?? '');
        $proof_file = $_FILES['proof_file'] ?? null;
        if (empty($proof_link) && empty($proof_file['name'])) {
            throw new Exception("You must provide either a proof link or upload a file.");
        }
        
        $proof_id = handle_proof_upload($proof_file, $proof_link, $pdo, $_SESSION['user_id']);
        
        // Insert Promise Post
        $title = trim($_POST['title']);
        $desc = trim($_POST['promise_description']);
        $date = $_POST['promise_date'];
        $cat_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
        
        $stmt = $pdo->prepare("INSERT INTO promise_posts (user_id, politician_id, title, promise_description, promise_date, category_id, proof_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $politician_id, $title, $desc, $date, $cat_id, $proof_id]);
        
        $pdo->commit();
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Promise post submitted and pending moderator approval.'];
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
            <h2 class="font-playfair fw-bold mb-4">Post a New Promise</h2>
            <p class="text-muted mb-4">Hold politicians accountable by logging a promise they made. Please provide valid proof.</p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Politician Name *</label>
                        <input type="text" name="politician_search" id="politician_search" class="form-control" list="politician_list" required placeholder="Type or select a name">
                        <datalist id="politician_list">
                            <?php foreach($politicians as $p): ?>
                                <option value="<?= htmlspecialchars($p['name']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <div id="new_politician_notice" class="form-text text-info d-none"><i class="fa-solid fa-info-circle"></i> A new profile will be created.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Date Promise Made *</label>
                        <input type="date" name="promise_date" class="form-control" required max="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Promise Title *</label>
                    <input type="text" name="title" class="form-control" required maxlength="255" placeholder="e.g. Fix downtown potholes">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Promise Description *</label>
                    <textarea name="promise_description" class="form-control" rows="4" required placeholder="Provide details about what was promised..."></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- Select Category --</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="p-3 border rounded mb-4">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-link text-muted me-2"></i>Proof (Required)</h5>
                    <p class="small text-muted">Provide a link to a news article/video, OR upload a screenshot/video (Max 20MB).</p>
                    
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
                
                <button type="submit" class="btn btn-navy bg-navy text-white fw-bold py-3 w-100 fs-5 rounded-pill shadow">Submit Promise for Review</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
