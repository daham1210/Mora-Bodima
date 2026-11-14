<?php
// backend/delete_review.php
require_once __DIR__ . '/config/db_con.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $review_id = (int)($_POST['review_id'] ?? 0);
    $house_id  = (int)($_POST['house_id'] ?? 0);

    // 1. Check review ownership
    $stmt = $conn->prepare("SELECT user_id FROM reviews WHERE review_id = ?");
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $rev = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($rev && ((int)$rev['user_id'] === (int)$_SESSION['user_id'] || ($_SESSION['role'] ?? '') === 'admin')) {
        // 2. Delete the review
        $delStmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
        $delStmt->bind_param("i", $review_id);
        $delStmt->execute();
        $delStmt->close();

        // 3. Recalculate average rating and review count
        $statStmt = $conn->prepare("SELECT AVG(overall_rating) AS avg_r, COUNT(*) AS total_r FROM reviews WHERE house_id = ?");
        $statStmt->bind_param("i", $house_id);
        $statStmt->execute();
        $stats = $statStmt->get_result()->fetch_assoc();
        $statStmt->close();

        // 4. Update boarding_houses summary columns
        $newAvg   = round((float)($stats['avg_r'] ?? 0), 2);
        $newTotal = (int)($stats['total_r'] ?? 0);

        $upd = $conn->prepare("UPDATE boarding_houses SET avg_rating = ?, total_reviews = ? WHERE house_id = ?");
        $upd->bind_param("dii", $newAvg, $newTotal, $house_id);
        $upd->execute();
        $upd->close();
    }

    header("Location: ../frontend/view_house.html?id=$house_id&msg=" . urlencode("Review deleted."));
    exit;
}

header("Location: ../frontend/index.html");
exit;