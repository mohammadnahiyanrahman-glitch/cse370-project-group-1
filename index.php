<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// Fetch recent approved promises
$stmt = $pdo->query("
    SELECT pp.*, p.name AS politician_name, p.photo AS politician_photo, c.name AS category_name,
           (SELECT COUNT(*) FROM comments WHERE post_id = pp.post_id AND is_removed = 0) as comment_count,
           (SELECT AVG(rating_value) FROM ratings WHERE politician_id = p.politician_id) as avg_rating
    FROM promise_posts pp
    JOIN politicians p ON pp.politician_id = p.politician_id
    LEFT JOIN categories c ON pp.category_id = c.category_id
    WHERE pp.status = 'approved'
    ORDER BY pp.post_date DESC
    LIMIT 10
");
$recent_promises = $stmt->fetchAll();

// Top rated politicians
$stmt_top = $pdo->query("
    SELECT p.politician_id, p.name, p.position, AVG(r.rating_value) as avg_rating, COUNT(r.rating_id) as rating_count
    FROM politicians p
    JOIN ratings r ON p.politician_id = r.politician_id
    GROUP BY p.politician_id
    ORDER BY avg_rating DESC
    LIMIT 5
");
$top_politicians = $stmt_top->fetchAll();

// Most pending promises
$stmt_pending = $pdo->query("
    SELECT p.politician_id, p.name, COUNT(pp.post_id) AS pending_count
    FROM politicians p
    JOIN promise_posts pp ON pp.politician_id = p.politician_id
    LEFT JOIN completion_posts cp ON cp.promise_id = pp.post_id AND cp.status = 'approved'
    WHERE pp.status = 'approved' AND cp.completion_id IS NULL
    GROUP BY p.politician_id, p.name
    ORDER BY pending_count DESC
    LIMIT 5
");
$pending_promises_politicians = $stmt_pending->fetchAll();
?>

<!-- Hero Section -->
<div class="hero-section text-center shadow-lg">
    <div class="container position-relative" style="z-index: 2;">
        <h1 class="display-3 font-playfair fw-bold text-gold mb-3"><i class="fa-solid fa-landmark me-3"></i>PolitiTrack</h1>
        <p class="lead mb-4 fs-4">Holding power accountable. Track promises, demand transparency.</p>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form action="/polititrack/search.php" method="GET" class="d-flex bg-white rounded-pill p-1 shadow">
                    <input type="text" name="q" class="form-control border-0 rounded-pill ms-2" placeholder="Search politician or promise..." required>
                    <button type="submit" class="btn btn-gold rounded-pill px-4"><i class="fa-solid fa-search"></i> Search</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Main Feed -->
    <div class="col-lg-8">
        <h3 class="font-playfair border-bottom border-2 border-warning pb-2 mb-4">Recent Promises</h3>
        
        <?php if (empty($recent_promises)): ?>
            <div class="glass-card p-4 text-center text-muted">
                <i class="fa-solid fa-inbox fa-3x mb-3 text-light"></i>
                <p>No approved promises found. Be the first to post!</p>
            </div>
        <?php else: ?>
            <?php foreach ($recent_promises as $post): ?>
                <div class="glass-card mb-4 p-4 position-relative">
                    <div class="d-flex align-items-center mb-3">
                        <?php 
                            $photo = $post['politician_photo'] ? '/polititrack/uploads/' . htmlspecialchars($post['politician_photo']) : 'https://ui-avatars.com/api/?name='.urlencode($post['politician_name']).'&background=FFC107&color=000';
                        ?>
                        <img src="<?= $photo ?>" alt="Avatar" class="avatar-circle me-3 shadow-sm">
                        <div>
                            <h5 class="mb-0 fw-bold"><a href="politician.php?id=<?= $post['politician_id'] ?>" class="text-decoration-none text-navy"><?= htmlspecialchars($post['politician_name']) ?></a></h5>
                            <small class="text-muted">
                                <?php if ($post['avg_rating']): ?>
                                    <span class="text-gold"><i class="fa-solid fa-star"></i> <?= number_format($post['avg_rating'], 1) ?>/5</span> • 
                                <?php endif; ?>
                                <?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?> • Promise Date: <?= date('M d, Y', strtotime($post['promise_date'])) ?>
                            </small>
                        </div>
                    </div>
                    <h4 class="font-playfair"><a href="post.php?id=<?= $post['post_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($post['title']) ?></a></h4>
                    <p class="text-secondary"><?= nl2br(htmlspecialchars(substr($post['promise_description'], 0, 150))) ?>...</p>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <span class="badge bg-secondary status-badge"><i class="fa-solid fa-comments me-1"></i> <?= $post['comment_count'] ?></span>
                            <!-- We can also show completion status if desired -->
                        </div>
                        <a href="post.php?id=<?= $post['post_id'] ?>" class="btn btn-outline-gold btn-sm rounded-pill px-3 fw-bold">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Top Rated -->
        <div class="glass-card p-4 mb-4 border-top border-4 border-success">
            <h5 class="font-playfair mb-3"><i class="fa-solid fa-ranking-star text-success me-2"></i>Top Rated Politicians</h5>
            <ul class="list-group list-group-flush">
                <?php foreach ($top_politicians as $tp): ?>
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
                        <div>
                            <a href="politician.php?id=<?= $tp['politician_id'] ?>" class="text-decoration-none fw-bold"><?= htmlspecialchars($tp['name']) ?></a><br>
                            <small class="text-muted"><?= htmlspecialchars($tp['position']) ?></small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success rounded-pill"><i class="fa-solid fa-star text-warning"></i> <?= number_format($tp['avg_rating'], 1) ?></span><br>
                            <small class="text-muted" style="font-size: 0.75rem;"><?= $tp['rating_count'] ?> rating<?= $tp['rating_count'] != 1 ? 's' : '' ?></small>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php if(empty($top_politicians)) echo "<li class='list-group-item bg-transparent px-0'>No ratings yet.</li>"; ?>
            </ul>
        </div>
        
        <!-- Most Pending Promises -->
        <div class="glass-card p-4 mb-4 border-top border-4 border-warning">
            <h5 class="font-playfair mb-3"><i class="fa-solid fa-clock text-warning me-2"></i>Most Pending Promises</h5>
            <ul class="list-group list-group-flush">
                <?php foreach ($pending_promises_politicians as $bp): ?>
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
                        <a href="politician.php?id=<?= $bp['politician_id'] ?>" class="text-decoration-none fw-bold"><?= htmlspecialchars($bp['name']) ?></a>
                        <span class="badge bg-warning rounded-pill" data-bs-toggle="tooltip" title="Politicians with the most unresolved approved promises">
                            <?= $bp['pending_count'] ?> Pending
                        </span>
                    </li>
                <?php endforeach; ?>
                <?php if(empty($pending_promises_politicians)) echo "<li class='list-group-item bg-transparent px-0'>All promises are kept!</li>"; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
