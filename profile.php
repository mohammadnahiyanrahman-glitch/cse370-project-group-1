<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<div class='container mt-5 alert alert-danger'>User not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='container mt-5 alert alert-danger'>User not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Handle ban request (Moderator only)
if (isModerator() && isset($_POST['ban_user']) && $id != $_SESSION['user_id']) {
    verifyCsrfToken($_POST['csrf_token']);
    $new_status = $user['is_banned'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET is_banned = ? WHERE user_id = ?");
    $stmt->execute([$new_status, $id]);
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'User ban status updated.'];
    header("Location: profile.php?id=$id");
    exit;
}

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM promise_posts WHERE user_id = ?");
$stmt->execute([$id]);
$post_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$stmt->execute([$id]);
$comment_count = $stmt->fetchColumn();
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="glass-card p-4 text-center">
            <?php $avatar = $user['profile_picture'] ? '/polititrack/uploads/avatars/' . htmlspecialchars($user['profile_picture']) : 'https://ui-avatars.com/api/?name='.urlencode($user['username']).'&background=0B192C&color=fff'; ?>
            
            <?php if (isLoggedIn() && $_SESSION['user_id'] == $id): ?>
                <div class="avatar-edit-wrapper mb-3 mx-auto" style="width: 100px;">
                    <img src="<?= $avatar ?>" id="profileAvatarImg" class="avatar-circle-lg" alt="Avatar">
                    <label for="avatarUpload" class="avatar-edit-overlay">
                        <i class="fa-solid fa-camera"></i>
                    </label>
                    <input type="file" id="avatarUpload" class="d-none" accept="image/jpeg,image/png,image/webp">
                </div>
            <?php else: ?>
                <img src="<?= $avatar ?>" class="avatar-circle-lg mx-auto d-block mb-3" alt="Avatar">
            <?php endif; ?>
            <h3 class="font-playfair fw-bold"><?= htmlspecialchars($user['username']) ?></h3>
            <p class="text-muted">Joined <?= date('M Y', strtotime($user['join_date'])) ?></p>
            
            <?php if ($user['role'] === 'moderator'): ?>
                <span class="badge bg-gold mb-3"><i class="fa-solid fa-shield-halved me-1"></i> Moderator</span>
            <?php endif; ?>
            <?php if ($user['is_banned']): ?>
                <span class="badge bg-danger mb-3"><i class="fa-solid fa-ban me-1"></i> Banned</span>
            <?php endif; ?>
            
            <p class="text-secondary mt-2 border-top pt-3 text-start"><?= nl2br(htmlspecialchars($user['bio'] ?? 'No bio provided.')) ?></p>
            
            <?php if (isLoggedIn() && $_SESSION['user_id'] == $id): ?>
                <!-- We could add edit profile functionality here in the future -->
                <button class="btn btn-outline-secondary btn-sm w-100"><i class="fa-solid fa-gear me-1"></i> Edit Settings</button>
            <?php endif; ?>

            <?php if (isModerator() && $_SESSION['user_id'] != $id): ?>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="ban_user" value="1">
                    <button type="submit" class="btn btn-<?= $user['is_banned'] ? 'success' : 'danger' ?> btn-sm w-100">
                        <?= $user['is_banned'] ? 'Unban User' : 'Ban User' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="glass-card p-4">
            <h4 class="font-playfair border-bottom pb-2 mb-4">Activity Overview</h4>
            <div class="row text-center mb-4">
                <div class="col-6 border-end">
                    <h2 class="text-navy fw-bold"><?= $post_count ?></h2>
                    <span class="text-muted text-uppercase small fw-bold">Promises Posted</span>
                </div>
                <div class="col-6">
                    <h2 class="text-gold fw-bold"><?= $comment_count ?></h2>
                    <span class="text-muted text-uppercase small fw-bold">Comments</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<?php if (isLoggedIn() && $_SESSION['user_id'] == $id): ?>
<script>
document.getElementById('avatarUpload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Preview
    const img = document.getElementById('profileAvatarImg');
    const oldSrc = img.src;
    img.src = window.URL.createObjectURL(file);

    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('csrf_token', '<?= generateCsrfToken() ?>');

    fetch('/polititrack/api/update_avatar.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Update navbar if exists
            const navImg = document.getElementById('navAvatarImg');
            if (navImg) navImg.src = data.url;
            
            // Show toast
            const toastHtml = `
            <div class="toast align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
              <div class="d-flex">
                <div class="toast-body">Avatar updated successfully.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
              </div>
            </div>`;
            document.querySelector('.toast-container').innerHTML += toastHtml;
        } else {
            alert(data.error || 'Failed to upload avatar.');
            img.src = oldSrc; // Revert
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred during upload.');
        img.src = oldSrc;
    });
});
</script>
<?php endif; ?>
