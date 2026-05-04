<?php
require_once '../config/db.php';
require_once '../includes/header.php';
requireModerator();

// Get counts
$pending_posts = $pdo->query("SELECT COUNT(*) FROM promise_posts WHERE status = 'pending'")->fetchColumn();
$pending_completions = $pdo->query("SELECT COUNT(*) FROM completion_posts WHERE status = 'pending'")->fetchColumn();
$open_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$pending_edits = $pdo->query("SELECT COUNT(*) FROM politician_edit_log WHERE status = 'pending'")->fetchColumn();
$banned_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_banned = 1")->fetchColumn();
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="index.php" class="list-group-item list-group-item-action active bg-navy border-navy"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a>
            <a href="posts.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">Promises <span class="badge bg-warning rounded-pill"><?= $pending_posts ?></span></a>
            <a href="completions.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">Completions <span class="badge bg-warning rounded-pill"><?= $pending_completions ?></span></a>
            <a href="reports.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">Reports <span class="badge bg-danger rounded-pill"><?= $open_reports ?></span></a>
            <a href="edits.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">Profile Edits <span class="badge bg-info rounded-pill"><?= $pending_edits ?></span></a>
            <a href="users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="proofs.php" class="list-group-item list-group-item-action">Verify Proofs</a>
        </div>
    </div>
    
    <div class="col-md-9">
        <h2 class="font-playfair fw-bold border-bottom pb-2 mb-4"><i class="fa-solid fa-shield-halved text-warning me-2"></i>Moderator Dashboard</h2>
        
        <div class="row row-cols-1 row-cols-md-3 g-4 text-center">
            <div class="col">
                <div class="glass-card p-4 h-100 border-start border-4 border-warning">
                    <h1 class="display-4 fw-bold text-navy"><?= $pending_posts ?></h1>
                    <p class="text-muted text-uppercase small fw-bold">Pending Promises</p>
                    <a href="posts.php" class="btn btn-outline-navy btn-sm">Review Now</a>
                </div>
            </div>
            <div class="col">
                <div class="glass-card p-4 h-100 border-start border-4 border-success">
                    <h1 class="display-4 fw-bold text-navy"><?= $pending_completions ?></h1>
                    <p class="text-muted text-uppercase small fw-bold">Pending Completions</p>
                    <a href="completions.php" class="btn btn-outline-navy btn-sm">Review Now</a>
                </div>
            </div>
            <div class="col">
                <div class="glass-card p-4 h-100 border-start border-4 border-danger">
                    <h1 class="display-4 fw-bold text-danger"><?= $open_reports ?></h1>
                    <p class="text-muted text-uppercase small fw-bold">Open Reports</p>
                    <a href="reports.php" class="btn btn-outline-danger btn-sm">Review Now</a>
                </div>
            </div>
            <div class="col">
                <div class="glass-card p-4 h-100 border-start border-4 border-info">
                    <h1 class="display-4 fw-bold text-info"><?= $pending_edits ?></h1>
                    <p class="text-muted text-uppercase small fw-bold">Pending Edits</p>
                    <a href="edits.php" class="btn btn-outline-info btn-sm">Review Now</a>
                </div>
            </div>
            <div class="col">
                <div class="glass-card p-4 h-100 border-start border-4 border-dark">
                    <h1 class="display-4 fw-bold"><?= $banned_users ?></h1>
                    <p class="text-muted text-uppercase small fw-bold">Banned Users</p>
                    <a href="users.php" class="btn btn-outline-dark btn-sm">Manage</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
