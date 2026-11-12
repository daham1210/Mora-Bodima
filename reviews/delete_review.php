<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $review_id = (int)($_POST['review_id'] ?? 0);
    $house_id  = (int)($_POST['house_id'] ?? 0);

    $stmt = $pdo->prepare("SELECT user_id FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $rev = $stmt->fetch();

    if ($rev && ($rev['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] === 'admin')) {
        $del = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
        $del->execute([$review_id]);

        $recalc = $pdo->prepare("
            UPDATE boarding_houses
            SET avg_rating = COALESCE((SELECT AVG(overall_rating) FROM reviews WHERE house_id = ?), 0),
                total_reviews = (SELECT COUNT(*) FROM reviews WHERE house_id = ?)
            WHERE house_id = ?
        ");
        $recalc->execute([$house_id, $house_id, $house_id]);
    }

    header("Location: $base_url/listings/view_house.php?id=$house_id");
    exit;
}
header("Location: $base_url/index.php");
exit;
?>