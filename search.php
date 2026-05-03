<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$query = $_GET['q'] ?? '';
$q = '%' . $query . '%';

// Search politicians
$stmt = $pdo->prepare("SELECT * FROM politicians WHERE name LIKE ? OR party LIKE ? OR position LIKE ?");
$stmt->execute([$q, $q, $q]);
$politicians = $stmt->fetchAll();

// Search promises
$stmt = $pdo->prepare("
    SELECT pp.*, p.name AS politician_name 
    FROM promise_posts pp 
    JOIN politicians p ON pp.politician_id = p.politician_id 
    WHERE (pp.title LIKE ? OR pp.promise_description LIKE ?) AND pp.status = 'approved'
");
$stmt->execute([$q, $q]);
$promises = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="font-playfair fw-bold">Search Results for "<?= htmlspecialchars($query) ?>"</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <h4 class="font-playfair border-bottom pb-2 mb-3">Politicians (<?= count($politicians) ?>)</h4>
        <?php if (empty($politicians)): ?>
            <p class="text-muted">No politicians found.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($politicians as $p): ?>
                    <a href="politician.php?id=<?= $p['politician_id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center">
                        <?php $photo = $p['photo'] ? '/polititrack/uploads/' . htmlspecialchars($p['photo']) : 'https://ui-avatars.com/api/?name='.urlencode($p['name']).'&background=FFC107&color=000'; ?>
                        <img src="<?= $photo ?>" class="avatar-circle me-3" style="width: 40px; height: 40px;" alt="">
                        <div>
                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($p['name']) ?></h6>
                            <small class="text-muted"><?= htmlspecialchars($p['party']) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-6 mb-4">
        <h4 class="font-playfair border-bottom pb-2 mb-3">Promises (<?= count($promises) ?>)</h4>
        <?php if (empty($promises)): ?>
            <p class="text-muted">No promises found.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($promises as $pr): ?>
                    <a href="post.php?id=<?= $pr['post_id'] ?>" class="list-group-item list-group-item-action">
                        <h6 class="mb-1 fw-bold"><?= htmlspecialchars($pr['title']) ?></h6>
                        <small class="text-muted">By <?= htmlspecialchars($pr['politician_name']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
