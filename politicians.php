<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// Fetch all politicians with stats
$stmt = $pdo->query("
    SELECT p.*,
           (SELECT AVG(rating_value) FROM ratings WHERE politician_id = p.politician_id) as avg_rating,
           (SELECT COUNT(rating_id) FROM ratings WHERE politician_id = p.politician_id) as rating_count,
           (SELECT COUNT(*) FROM promise_posts WHERE politician_id = p.politician_id AND status = 'approved') as total_promises,
           (SELECT COUNT(*) FROM completion_posts cp JOIN promise_posts pp ON cp.promise_id = pp.post_id WHERE pp.politician_id = p.politician_id AND cp.status = 'approved') as completed
    FROM politicians p
    ORDER BY name ASC
");
$politicians = $stmt->fetchAll();
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h2 class="font-playfair fw-bold"><i class="fa-solid fa-users text-gold me-2"></i>Politicians</h2>
        <p class="text-muted">Browse and track all politicians on the platform.</p>
    </div>
</div>

<div class="row row-cols-1 row-cols-md-3 g-4">
    <?php foreach ($politicians as $p): 
        $pct = ($p['total_promises'] > 0) ? ($p['completed'] / $p['total_promises']) * 100 : 0;
        $photo = $p['photo'] ? '/polititrack/uploads/' . htmlspecialchars($p['photo']) : 'https://ui-avatars.com/api/?name='.urlencode($p['name']).'&background=FFC107&color=000';
    ?>
        <div class="col">
            <div class="glass-card h-100 p-4 text-center">
                <img src="<?= $photo ?>" alt="Photo" class="avatar-circle-lg mb-3 mx-auto d-block">
                <h4 class="font-playfair fw-bold mb-1"><a href="politician.php?id=<?= $p['politician_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($p['name']) ?></a></h4>
                <p class="text-muted small mb-2"><?= htmlspecialchars($p['party'] ?? 'Unknown Party') ?> • <?= htmlspecialchars($p['position'] ?? 'Unknown Position') ?></p>
                
                <div class="d-flex justify-content-center align-items-center mb-3">
                    <?php if ($p['avg_rating']): ?>
                        <div class="text-end me-3">
                            <span class="badge bg-navy text-gold"><i class="fa-solid fa-star"></i> <?= number_format($p['avg_rating'], 1) ?> / 5</span>
                            <div class="small text-muted" style="font-size: 0.7rem;"><?= $p['rating_count'] ?> rating<?= $p['rating_count'] != 1 ? 's' : '' ?></div>
                        </div>
                    <?php endif; ?>
                    <span class="badge bg-success mb-2">Completion: <?= number_format($pct, 1) ?>%</span>
                </div>
                
                <a href="politician.php?id=<?= $p['politician_id'] ?>" class="btn btn-outline-gold rounded-pill px-4 w-100">View Profile</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
