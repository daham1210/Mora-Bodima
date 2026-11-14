<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $review_id = (int)($_POST['review_id'] ?? 0);
    $house_id  = (int)($_POST['house_id'] ?? 0);

    $stmt = $pdo->prepare("SELECT user_id FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $rev = $stmt->fetch();

    if ($rev && ($rev['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'admin')) {
        $pdo->prepare("DELETE FROM reviews WHERE review_id = ?")->execute([$review_id]);

        $statStmt = $pdo->prepare("SELECT AVG(overall_rating) AS avg_r, COUNT(*) AS total_r FROM reviews WHERE house_id = ?");
        $statStmt->execute([$house_id]);
        $stats = $statStmt->fetch();

        $upd = $pdo->prepare("UPDATE boarding_houses SET avg_rating = ?, total_reviews = ? WHERE house_id = ?");
        $upd->execute([$stats['avg_r'] ?? 0, $stats['total_r'], $house_id]);
    }

    header("Location: ../frontend/view_house.html?id=$house_id&msg=Review+deleted");
    exit;
}
header("Location: ../frontend/index.html");
exit;
?>