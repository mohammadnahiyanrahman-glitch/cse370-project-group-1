<?php
require_once '../config/db.php';
require_once '../includes/header.php';
requireModerator();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrfToken($_POST['csrf_token']);
    $edit_id = $_POST['edit_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("SELECT * FROM politician_edit_log WHERE edit_id = ?");
        $stmt->execute([$edit_id]);
        $edit = $stmt->fetch();
        
        if ($edit) {
            // Update politician
            $field = $edit['field_changed']; // Validated fields on input, safe here as column name if strictly 'party', 'position', 'region', 'description'
            $stmt_upd = $pdo->prepare("UPDATE politicians SET `$field` = ?, last_edited_by = ? WHERE politician_id = ?");
            $stmt_upd->execute([$edit['new_value'], $edit['edited_by'], $edit['politician_id']]);
            
            $pdo->prepare("UPDATE politician_edit_log SET status = 'approved' WHERE edit_id = ?")->execute([$edit_id]);
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Edit approved and applied.'];
        }
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE politician_edit_log SET status = 'rejected' WHERE edit_id = ?")->execute([$edit_id]);
        $_SESSION['toast'] = ['type' => 'info', 'message' => 'Edit rejected.'];
    }
    
    header("Location: edits.php");
    exit;
}

$stmt = $pdo->query("
    SELECT e.*, p.name as politician_name, u.username as edited_by_name
    FROM politician_edit_log e
    JOIN politicians p ON e.politician_id = p.politician_id
    JOIN users u ON e.edited_by = u.user_id
    WHERE e.status = 'pending'
    ORDER BY e.edit_time ASC
");
$edits = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="index.php" class="list-group-item list-group-item-action">Dashboard</a>
            <a href="posts.php" class="list-group-item list-group-item-action">Promises</a>
            <a href="completions.php" class="list-group-item list-group-item-action">Completions</a>
            <a href="reports.php" class="list-group-item list-group-item-action">Reports</a>
            <a href="edits.php" class="list-group-item list-group-item-action active bg-navy border-navy">Profile Edits</a>
            <a href="users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="proofs.php" class="list-group-item list-group-item-action">Verify Proofs</a>
        </div>
    </div>
    
    <div class="col-md-9">
        <h3 class="font-playfair fw-bold border-bottom pb-2 mb-4">Pending Profile Edits (<?= count($edits) ?>)</h3>
        
        <?php if (empty($edits)): ?>
            <div class="alert alert-success">No pending profile edits.</div>
        <?php else: ?>
            <div class="table-responsive glass-card p-3">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Politician</th>
                            <th>Field</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                            <th>Suggested By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($edits as $e): ?>
                            <tr>
                                <td><a href="../politician.php?id=<?= $e['politician_id'] ?>" target="_blank"><?= htmlspecialchars($e['politician_name']) ?></a></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($e['field_changed'])) ?></span></td>
                                <td>
                                    <?php if ($e['field_changed'] === 'photo'): ?>
                                        <?php if ($e['old_value']): ?>
                                            <img src="/polititrack/uploads/<?= htmlspecialchars($e['old_value']) ?>" style="width: 40px; height: 40px; object-fit: cover;" class="rounded">
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <del class="text-danger"><?= htmlspecialchars($e['old_value']) ?></del>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($e['field_changed'] === 'photo'): ?>
                                        <img src="/polititrack/uploads/<?= htmlspecialchars($e['new_value']) ?>" style="width: 40px; height: 40px; object-fit: cover;" class="rounded border border-success">
                                    <?php else: ?>
                                        <ins class="text-success"><?= htmlspecialchars($e['new_value']) ?></ins>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($e['edited_by_name']) ?></td>
                                <td>
                                    <form method="POST" class="d-flex gap-1">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="edit_id" value="<?= $e['edit_id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i></button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
