<?php
require_once '../config/db.php';
require_once '../includes/header.php';
requireModerator();

$search = $_GET['q'] ?? '';
$q = '%' . $search . '%';

$stmt = $pdo->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY join_date DESC");
$stmt->execute([$q, $q]);
$users = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="index.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="posts.php" class="list-group-item list-group-item-action">Promises</a>
            <a href="completions.php" class="list-group-item list-group-item-action">Completions</a>
            <a href="reports.php" class="list-group-item list-group-item-action">Reports</a>
            <a href="edits.php" class="list-group-item list-group-item-action">Profile Edits</a>
            <a href="users.php" class="list-group-item list-group-item-action active bg-navy border-navy">Manage Users</a>
            <a href="proofs.php" class="list-group-item list-group-item-action">Verify Proofs</a>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
            <h3 class="font-playfair fw-bold mb-0">Manage Users</h3>
            <form method="GET" class="d-flex">
                <input type="text" name="q" class="form-control form-control-sm me-2" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-outline-navy btn-sm">Search</button>
            </form>
        </div>
        
        <div class="table-responsive glass-card p-3">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['user_id'] ?></td>
                            <td><a href="../profile.php?id=<?= $u['user_id'] ?>" target="_blank" class="fw-bold text-decoration-none"><?= htmlspecialchars($u['username']) ?></a></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <?php if ($u['role'] === 'moderator'): ?>
                                    <span class="badge bg-gold"><i class="fa-solid fa-shield me-1"></i> Mod</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Regular</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['is_banned']): ?>
                                    <span class="badge bg-danger">Banned</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="../profile.php?id=<?= $u['user_id'] ?>" class="btn btn-outline-secondary btn-sm" target="_blank">View Profile</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
