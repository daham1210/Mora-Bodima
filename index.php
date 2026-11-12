<?php
require_once __DIR__ . '/includes/header.php';

$stmt = $pdo->query("
    SELECT b.*, u.full_name AS owner_name,
           (SELECT image_path FROM house_images WHERE house_id = b.house_id LIMIT 1) AS cover_image
    FROM boarding_houses b
    JOIN users u ON b.owner_id = u.user_id
    WHERE b.status = 'approved'
    ORDER BY b.avg_rating DESC, b.total_reviews DESC
    LIMIT 6
");
$houses = $stmt->fetchAll();
?>

<div class="hero">
    <div>
        <h1>Find & Rate Student Boarding Houses</h1>
        <p>Compare verified student reviews on cleanliness, security, Wi-Fi, and monthly rent near campus.</p>
    </div>
    <div>
        <a href="<?= $base_url ?>/search/index.php" class="btn" style="background:#fff; color:#1e3a8a;">🔍 Explore All Listings</a>
    </div>
</div>

<h2 style="margin-bottom: 20px;">🌟 Top Rated Boarding Houses</h2>

<?php if (empty($houses)): ?>
    <div class="card">No approved boarding houses found yet.</div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($houses as $house): ?>
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

                <a href="<?= $base_url ?>/listings/view_house.php?id=<?= $house['house_id'] ?>" class="btn" style="width:100%; text-align:center; margin-top:12px;">
                    View Details & Reviews
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

#htmlspecialchars_decode