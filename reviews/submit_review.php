<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in first.']);
    exit;
}

$house_id    = (int)($_POST['house_id'] ?? 0);
$user_id     = (int)$_SESSION['user_id'];
$overall     = max(1, min(5, (int)($_POST['overall_rating'] ?? 5)));
$cleanliness = max(1, min(5, (int)($_POST['cleanliness_rating'] ?? 5)));
$safety      = max(1, min(5, (int)($_POST['safety_rating'] ?? 5)));
$comment     = trim($_POST['comment'] ?? '');

if ($house_id <= 0 || $comment === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a review comment.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $check = $pdo->prepare("SELECT review_id FROM reviews WHERE house_id = ? AND user_id = ?");
    $check->execute([$house_id, $user_id]);
    $existing = $check->fetch();

    if ($existing) {
        $upd = $pdo->prepare("
            UPDATE reviews
            SET overall_rating = ?, cleanliness_rating = ?, safety_rating = ?, comment = ?, created_at = CURRENT_TIMESTAMP
            WHERE review_id = ?
        ");
        $upd->execute([$overall, $cleanliness, $safety, $comment, $existing['review_id']]);
    } else {
        $ins = $pdo->prepare("
            INSERT INTO reviews (house_id, user_id, overall_rating, cleanliness_rating, safety_rating, comment)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$house_id, $user_id, $overall, $cleanliness, $safety, $comment]);
    }

    $recalc = $pdo->prepare("
        UPDATE boarding_houses
        SET avg_rating = COALESCE((SELECT AVG(overall_rating) FROM reviews WHERE house_id = ?), 0),
            total_reviews = (SELECT COUNT(*) FROM reviews WHERE house_id = ?)
        WHERE house_id = ?
    ");
    $recalc->execute([$house_id, $house_id, $house_id]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Review saved! Updating ratings...']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Could not save review.']);
}
?>