<?php

function calculate_oil_meter($pdo, $politician_id) {
    // 1. Avg rating & count
    $stmt = $pdo->prepare("SELECT AVG(rating_value) as avg_rating, COUNT(rating_id) as rating_count FROM ratings WHERE politician_id = ?");
    $stmt->execute([$politician_id]);
    $row = $stmt->fetch();
    $avg_rating = (float)($row['avg_rating'] ?? 0);
    $rating_count = (int)($row['rating_count'] ?? 0);
    
    // 2. Total approved promises
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM promise_posts WHERE politician_id = ? AND status = 'approved'");
    $stmt->execute([$politician_id]);
    $total_promises = (int)$stmt->fetchColumn();
    
    // 3. Total approved completions
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM completion_posts cp
        JOIN promise_posts pp ON cp.promise_id = pp.post_id
        WHERE pp.politician_id = ? AND cp.status = 'approved'
    ");
    $stmt->execute([$politician_id]);
    $completed = (int)$stmt->fetchColumn();
    
    $completion_pct = ($total_promises > 0) ? ($completed / $total_promises) * 100 : 0;
    
    // Calculate oil
    $oil = ($avg_rating / 5) * (1 - $completion_pct / 100) * 100;
    
    return [
        'avg_rating' => $avg_rating,
        'rating_count' => $rating_count,
        'total_promises' => $total_promises,
        'completed' => $completed,
        'completion_pct' => $completion_pct,
        'oil_level' => $oil
    ];
}

function handle_proof_upload($file, $link, $pdo, $user_id) {
    if (!empty($link)) {
        // Handle link
        $stmt = $pdo->prepare("INSERT INTO proofs (uploaded_by, proof_type, file_path) VALUES (?, 'link', ?)");
        $stmt->execute([$user_id, $link]);
        return $pdo->lastInsertId();
    }
    
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $max_size = 20 * 1024 * 1024; // 20MB
        if ($file['size'] > $max_size) {
            throw new Exception("File exceeds 20MB limit.");
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm'];
        if (!in_array($mime, $allowed_mimes)) {
            throw new Exception("Invalid file type.");
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = bin2hex(random_bytes(16)) . '.' . $ext;
        $upload_dir = __DIR__ . '/../uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $dest = $upload_dir . $new_name;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $type = strpos($mime, 'video') !== false ? 'video' : 'image';
            $stmt = $pdo->prepare("INSERT INTO proofs (uploaded_by, proof_type, file_path) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $type, $new_name]);
            return $pdo->lastInsertId();
        } else {
            throw new Exception("Failed to move uploaded file.");
        }
    }
    
    throw new Exception("No valid proof provided.");
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
