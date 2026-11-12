<?php
require_once __DIR__ . '/../config/db.php';

$q          = trim($_GET['q'] ?? '');
$gender     = trim($_GET['gender'] ?? '');
$max_rent   = (float)($_GET['max_rent'] ?? 0);
$max_dist   = (float)($_GET['max_dist'] ?? 0);
$min_rating = (float)($_GET['min_rating'] ?? 0);

$sql = "
    SELECT b.*,
           (SELECT image_path FROM house_images WHERE house_id = b.house_id LIMIT 1) AS cover_image
    FROM boarding_houses b
    WHERE b.status = 'approved'
";
$params = [];

if ($q !== '') {
    $sql .= " AND (b.title LIKE ? OR b.address LIKE ? OR b.amenities LIKE ? OR b.description LIKE ?)";
    $like = "%$q%";
    array_push($params, $like, $like, $like, $like);
}
if ($gender !== '') {
    $sql .= " AND b.gender_type = ?";
    $params[] = $gender;
}
if ($max_rent > 0) {
    $sql .= " AND b.monthly_rent <= ?";
    $params[] = $max_rent;
}
if ($max_dist > 0) {
    $sql .= " AND b.distance_km <= ?";
    $params[] = $max_dist;
}
if ($min_rating > 0) {
    $sql .= " AND b.avg_rating >= ?";
    $params[] = $min_rating;
}

$sql .= " ORDER BY b.avg_rating DESC, b.monthly_rent ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

if (empty($results)) {
    echo '<div class="card" style="grid-column: 1 / -1;">No boarding houses match your filter criteria.</div>';
    exit;
}

foreach ($results as $house):
?>
    <div class="card">
        <?php if (!empty($house['cover_image'])): ?>
            <img src="<?= $house['cover_image'] ?>" class="card-img" alt="Boarding Image">
        <?php else: ?>
            <div class="card-img" style="display:flex;align-items:center;justify-content:center;color:#475569;font-weight:600;">
                🏠 No Photo Uploaded
            </div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; align-items:start;">
            <h3><?= htmlspecialchars($house['title']) ?></h3>
            <span class="badge badge-<?= htmlspecialchars($house['gender_type']) ?>"><?= ucfirst($house['gender_type']) ?></span>
        </div>
        <div class="rating-star" style="margin:6px 0;">
            ★ <?= number_format($house['avg_rating'], 1) ?> / 5.0 (<?= $house['total_reviews'] ?> reviews)
        </div>
        <p class="meta-text">📍 <?= htmlspecialchars($house['address']) ?> (<?= $house['distance_km'] ?> km)</p>
        <p class="meta-text">💰 <strong>Rs. <?= number_format($house['monthly_rent'], 2) ?></strong> / month</p>
        <p style="font-size:0.9rem; margin:8px 0;"><?= htmlspecialchars(substr((string)$house['description'], 0, 110)) ?>...</p>
        <a href="<?= $base_url ?>/listings/view_house.php?id=<?= $house['house_id'] ?>" class="btn" style="width:100%; text-align:center;">
            View & Rate
        </a>
    </div>
<?php endforeach; ?>