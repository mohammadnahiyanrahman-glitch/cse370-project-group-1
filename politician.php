<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<div class='container mt-5 alert alert-danger'>Politician not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM politicians WHERE politician_id = ?");
$stmt->execute([$id]);
$politician = $stmt->fetch();

if (!$politician) {
    echo "<div class='container mt-5 alert alert-danger'>Politician not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Calculate Oil Meter
$stats = calculate_oil_meter($pdo, $id);

// Handle Rating Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rate_politician'])) {
    requireLogin();
    verifyCsrfToken($_POST['csrf_token']);
    $rating = (int)$_POST['rating_value'];
    
    if ($rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare("
            INSERT INTO ratings (user_id, politician_id, rating_value) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE rating_value = VALUES(rating_value)
        ");
        $stmt->execute([$_SESSION['user_id'], $id, $rating]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Rating saved!'];
        header("Location: politician.php?id=$id");
        exit;
    }
}

// Handle Profile Edit Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    requireLogin();
    verifyCsrfToken($_POST['csrf_token']);
    
    // Photo Upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $max_size = 4 * 1024 * 1024; // 4MB
        if ($file['size'] <= $max_size) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (in_array($mime, $allowed_mimes)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_name = 'pol_' . $id . '_' . time() . '.' . $ext;
                $upload_dir = __DIR__ . '/uploads/politicians/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                    $stmt = $pdo->prepare("INSERT INTO politician_edit_log (politician_id, edited_by, field_changed, old_value, new_value) VALUES (?, ?, 'photo', ?, ?)");
                    $stmt->execute([$id, $_SESSION['user_id'], $politician['photo'] ?? '', 'politicians/' . $new_name]);
                }
            }
        }
    }
    $fields = ['party', 'position', 'region', 'description'];
    foreach ($fields as $f) {
        if (isset($_POST[$f]) && $_POST[$f] !== $politician[$f]) {
            $stmt = $pdo->prepare("INSERT INTO politician_edit_log (politician_id, edited_by, field_changed, old_value, new_value) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $_SESSION['user_id'], $f, $politician[$f], $_POST[$f]]);
        }
    }
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Edit requests submitted for moderator review.'];
    header("Location: politician.php?id=$id");
    exit;
}

// Fetch Promises
$stmt = $pdo->prepare("SELECT pp.*, c.name as category_name,
       (SELECT COUNT(*) FROM completion_posts WHERE promise_id = pp.post_id AND status = 'approved') as is_completed
       FROM promise_posts pp 
       LEFT JOIN categories c ON pp.category_id = c.category_id
       WHERE pp.politician_id = ? AND pp.status = 'approved' ORDER BY pp.promise_date DESC");
$stmt->execute([$id]);
$promises = $stmt->fetchAll();

$photo = $politician['photo'] ? '/polititrack/uploads/' . htmlspecialchars($politician['photo']) : 'https://ui-avatars.com/api/?name='.urlencode($politician['name']).'&background=FFC107&color=000';

// User's current rating
$user_rating = null;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT rating_value FROM ratings WHERE user_id = ? AND politician_id = ?");
    $stmt->execute([$_SESSION['user_id'], $id]);
    $user_rating = $stmt->fetchColumn();
}
?>

<div class="row">
    <!-- Profile Sidebar -->
    <div class="col-lg-4 mb-4">
        <div class="glass-card p-4 text-center">
            <img src="<?= $photo ?>" alt="Photo" class="avatar-circle-lg mb-3 mx-auto d-block">
            <h2 class="font-playfair fw-bold"><?= htmlspecialchars($politician['name']) ?></h2>
            <p class="text-muted mb-2">
                <i class="fa-solid fa-briefcase me-1"></i> <?= htmlspecialchars($politician['position'] ?? 'N/A') ?><br>
                <i class="fa-solid fa-users me-1"></i> <?= htmlspecialchars($politician['party'] ?? 'N/A') ?><br>
                <i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($politician['region'] ?? 'N/A') ?>
            </p>
            <p class="text-secondary small text-start mt-3"><?= nl2br(htmlspecialchars($politician['description'] ?? 'No description available.')) ?></p>
            
            <?php if (isLoggedIn()): ?>
                <button class="btn btn-outline-secondary btn-sm mt-3 w-100" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fa-solid fa-pen me-1"></i> Suggest Edit
                </button>
            <?php endif; ?>
        </div>

        <!-- Rating Box -->
        <div class="glass-card p-4 mt-4 text-center">
            <h4 class="font-playfair mb-3">Public Rating</h4>
            <div class="display-4 text-gold mb-2 fw-bold">
                <?= number_format($stats['avg_rating'], 1) ?>
                <small class="fs-4 text-muted">/ 5</small>
            </div>
            <div class="small text-muted mb-3">(<?= $stats['rating_count'] ?> rating<?= $stats['rating_count'] != 1 ? 's' : '' ?>)</div>
            
            <?php if (isLoggedIn()): ?>
                <form method="POST" class="mt-3 border-top pt-3">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="rate_politician" value="1">
                    <label class="form-label fw-bold">Your Rating:</label>
                    <div class="d-flex justify-content-center mb-3">
                        <input type="range" class="form-range w-75" name="rating_value" min="1" max="5" step="1" value="<?= $user_rating ?? 3 ?>" oninput="this.nextElementSibling.value = this.value">
                        <output class="ms-2 fw-bold"><?= $user_rating ?? 3 ?></output>
                    </div>
                    <button type="submit" class="btn btn-gold btn-sm rounded-pill w-100 fw-bold">Submit Rating</button>
                </form>
            <?php else: ?>
                <p class="small text-muted mt-3 border-top pt-3"><a href="login.php">Login</a> to rate.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- OIL METER -->
        <div class="glass-card p-4 mb-4 border-start border-5 border-gold">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="font-playfair fw-bold mb-0">The Oil Meter <i class="fa-solid fa-droplet ms-1"></i></h3>
                <i class="fa-solid fa-circle-info text-muted" data-bs-toggle="tooltip" title="High oil means high popularity but low follow-through on promises."></i>
            </div>
            <p class="text-muted small mb-3">Popularity vs. Delivery Gap</p>
            
            <div class="oil-meter-container mb-2">
                <div class="oil-meter-bar" data-value="<?= min(100, max(0, $stats['oil_level'])) ?>"></div>
            </div>
            <div class="d-flex justify-content-between text-muted small fw-bold">
                <span>0% (Delivering)</span>
                <span class="fs-5"><?= number_format($stats['oil_level'], 1) ?>% Oil</span>
                <span>100% (All Talk)</span>
            </div>
            
            <div class="row text-center mt-4 border-top pt-3">
                <div class="col-4">
                    <h4 class="fw-bold text-navy"><?= $stats['total_promises'] ?></h4>
                    <span class="small text-muted text-uppercase">Promises</span>
                </div>
                <div class="col-4">
                    <h4 class="fw-bold text-success"><?= $stats['completed'] ?></h4>
                    <span class="small text-muted text-uppercase">Completed</span>
                </div>
                <div class="col-4">
                    <h4 class="fw-bold"><?= number_format($stats['completion_pct'], 1) ?>%</h4>
                    <span class="small text-muted text-uppercase">Completion Rate</span>
                </div>
            </div>
        </div>

        <!-- Promises List -->
        <h4 class="font-playfair fw-bold border-bottom pb-2 mb-4">Tracked Promises</h4>
        <?php if (empty($promises)): ?>
            <div class="alert alert-light text-center">No approved promises tracked yet.</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($promises as $p): ?>
                    <a href="post.php?id=<?= $p['post_id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3 mb-2 rounded border">
                        <div>
                            <h5 class="mb-1 fw-bold"><?= htmlspecialchars($p['title']) ?></h5>
                            <small class="text-muted">Made on: <?= date('M d, Y', strtotime($p['promise_date'])) ?> • <?= htmlspecialchars($p['category_name']) ?></small>
                        </div>
                        <?php if ($p['is_completed'] > 0): ?>
                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-check me-1"></i> Fulfilled</span>
                        <?php else: ?>
                            <span class="badge bg-warning rounded-pill px-3 py-2"><i class="fa-solid fa-clock me-1"></i> Pending</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Profile Modal -->
<?php if (isLoggedIn()): ?>
<div class="modal fade" id="editProfileModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title font-playfair fw-bold">Suggest Edit</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="edit_profile" value="1">
            <p class="small text-muted mb-3">Your suggested edits will be reviewed by a moderator.</p>
            
            <div class="mb-4 text-center">
                <img src="<?= $photo ?>" id="editPhotoPreview" class="avatar-circle-lg mb-2 mx-auto d-block" style="object-fit: cover;">
                <label for="editPhotoUpload" class="btn btn-outline-primary btn-sm rounded-pill mt-2">
                    <i class="fa-solid fa-camera me-1"></i> Change Photo
                </label>
                <input type="file" id="editPhotoUpload" name="photo" class="d-none" accept="image/jpeg,image/png,image/webp" onchange="document.getElementById('editPhotoPreview').src = window.URL.createObjectURL(this.files[0])">
                <div class="small text-muted mt-1">JPG, PNG, WEBP (Max 4MB)</div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Party</label>
                <input type="text" name="party" class="form-control" value="<?= htmlspecialchars($politician['party'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Position</label>
                <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($politician['position'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Region</label>
                <input type="text" name="region" class="form-control" value="<?= htmlspecialchars($politician['region'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($politician['description'] ?? '') ?></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-navy bg-navy text-white w-100 fw-bold">Submit Suggestions</button>
          </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
