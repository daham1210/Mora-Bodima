<?php
require_once __DIR__ . '/config/db_con.php';

$q          = trim($_GET['q'] ?? '');
$gender     = $_GET['gender'] ?? '';
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

if ($q != '') {
    $sql .= " AND (b.title LIKE ? OR b.address LIKE ? OR b.amenities LIKE ?)";
    $like = "%$q%";
    array_push($params, $like, $like, $like);
}
if ($gender != '') {
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

$sql .= " ORDER BY b.avg_rating DESC, b.total_reviews DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$result = $stmt->get_result();
$houses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($houses)) {
    echo '<div class="card">No approved boarding houses match your criteria.</div>';
    exit;
}

foreach ($houses as $house):
?>
    <div class="card" style="overflow: hidden;">
        <?php if (!empty($house['cover_image'])): ?>
            <div style="overflow: hidden; border-radius: 8px; margin-bottom: 16px;">
                <img src="<?= $house['cover_image'] ?>" class="card-img" alt="Boarding Image">
            </div>
        <?php else: ?>
            <div class="card-img" style="display:flex;align-items:center;justify-content:center;color:#475569;font-weight:600;">
                🏠 No Photo Uploaded
            </div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; align-items:start; gap:8px;">
            <h3><?= htmlspecialchars($house['title']) ?></h3>
            <span class="badge badge-<?= htmlspecialchars($house['gender_type']) ?>">
                <?= ucfirst($house['gender_type']) ?>
            </span>
        </div>

        <div class="rating-star" style="margin: 8px 0;">
            ★ <?= number_format($house['avg_rating'], 1) ?> / 5.0
            <span style="color:#64748b; font-size:0.85rem; font-weight:normal;">
                (<?= $house['total_reviews'] ?> reviews)
            </span>
        </div>

        <p class="meta-text">📍 <?= htmlspecialchars($house['address']) ?> (<?= $house['distance_km'] ?> km)</p>
        <p class="meta-text">💰 <strong>Rs. <?= number_format($house['monthly_rent'], 2) ?></strong> / month</p>

        <div style="margin: 12px 0;">
            <?php
            $amenities = array_filter(array_map('trim', explode(',', (string)$house['amenities'])));
            foreach (array_slice($amenities, 0, 4) as $am):
            ?>
                <span class="amenity-tag"><?= htmlspecialchars($am) ?></span>
            <?php endforeach; ?>
        </div>

        <a href="view_house.html?id=<?= $house['house_id'] ?>" class="btn" style="width:100%; text-align:center; margin-top:12px;">
            View Details & Reviews
        </a>
    </div>
<?php endforeach; ?>