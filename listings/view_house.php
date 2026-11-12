<?php
require_once __DIR__ . '/../includes/header.php';

$house_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT b.*, u.full_name AS owner_name, u.email AS owner_email
    FROM boarding_houses b
    JOIN users u ON b.owner_id = u.user_id
    WHERE b.house_id = ?
");
$stmt->execute([$house_id]);
$house = $stmt->fetch();

if (!$house) {
    echo '<div class="alert alert-error">Boarding house not found.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$imgStmt = $pdo->prepare("SELECT * FROM house_images WHERE house_id = ?");
$imgStmt->execute([$house_id]);
$images = $imgStmt->fetchAll();

$avgStmt = $pdo->prepare("
    SELECT AVG(overall_rating) AS avg_overall,
           AVG(cleanliness_rating) AS avg_clean,
           AVG(safety_rating) AS avg_safety
    FROM reviews WHERE house_id = ?
");
$avgStmt->execute([$house_id]);
$averages = $avgStmt->fetch();

$revStmt = $pdo->prepare("
    SELECT r.*, u.full_name
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.house_id = ?
    ORDER BY r.created_at DESC
");
$revStmt->execute([$house_id]);
$reviews = $revStmt->fetchAll();

// Determine Google Maps embed URL
$mapQuery = !empty($house['google_maps_link']) ? urlencode($house['google_maps_link']) : urlencode($house['address'] . ', Sri Lanka');
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div>
            <h1><?= htmlspecialchars($house['title']) ?></h1>
            <p class="meta-text">📍 <?= htmlspecialchars($house['address']) ?> • <?= $house['distance_km'] ?> km from university</p>
        </div>
        <div style="text-align:right;">
            <span class="badge badge-<?= htmlspecialchars($house['gender_type']) ?>"><?= ucfirst($house['gender_type']) ?></span>
            <h2 style="color:#2563eb; margin-top:6px;">Rs. <?= number_format($house['monthly_rent'], 2) ?> <small style="font-size:0.85rem;color:#64748b;">/month</small></h2>
        </div>
    </div>

    <?php if (!empty($images)): ?>
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:12px; margin: 16px 0;">
            <?php foreach ($images as $img): ?>
                <img src="<?= $house['cover_image'] ?>" class="card-img" alt="Boarding Image">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p style="margin: 14px 0;"><?= nl2br(htmlspecialchars($house['description'])) ?></p>

    <div style="margin: 12px 0;">
        <strong>Amenities:</strong>
        <?php foreach (array_filter(array_map('trim', explode(',', (string)$house['amenities']))) as $am): ?>
            <span class="amenity-tag"><?= htmlspecialchars($am) ?></span>
        <?php endforeach; ?>
    </div>

    <!-- Embedded Google Map -->
    <div style="margin: 24px 0;">
        <h3 style="margin-bottom: 10px;">🗺️ Location on Google Maps</h3>
        <div style="width: 100%; height: 320px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border);">
            <iframe 
                width="100%" 
                height="100%" 
                style="border:0;" 
                loading="lazy" 
                allowfullscreen 
                src="https://maps.google.com/maps?q=<?= $mapQuery ?>&t=&z=15&ie=UTF8&iwloc=&output=embed">
            </iframe>
        </div>
    </div>

    <p class="meta-text">Listed by: <strong><?= htmlspecialchars($house['owner_name']) ?></strong> (<?= htmlspecialchars($house['owner_email']) ?>)</p>

    <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $house['owner_id'] || $_SESSION['role'] === 'admin')): ?>
        <div style="margin-top: 12px;">
            <a href="edit_house.php?id=<?= $house_id ?>" class="btn btn-warning">✏️ Edit Listing & Photos</a>
        </div>
    <?php endif; ?>
</div>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:20px;">
    <div class="card">
        <h3>Rating Breakdown</h3>
        <div class="rating-star" style="font-size: 1.8rem; margin: 8px 0;">
            ★ <?= number_format($house['avg_rating'], 1) ?> / 5.0
            <span style="font-size:0.9rem; color:#64748b;">(<?= $house['total_reviews'] ?> reviews)</span>
        </div>

        <?php
        $metrics = [
            'Overall Experience' => (float)($averages['avg_overall'] ?? 0),
            'Cleanliness & Hygiene' => (float)($averages['avg_clean'] ?? 0),
            'Safety & Security' => (float)($averages['avg_safety'] ?? 0),
        ];
        foreach ($metrics as $label => $score):
            $pct = ($score / 5) * 100;
        ?>
            <div class="progress-row">
                <span style="width:160px; font-size:0.9rem;"><?= $label ?></span>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width: <?= $pct ?>%;"></div>
                </div>
                <strong style="width:36px; text-align:right;"><?= number_format($score, 1) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h3>Rate This Boarding House</h3>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <p class="meta-text" style="margin-top:10px;">
                Please <a href="<?= $base_url ?>/auth/login.php"><strong>log in</strong></a> to submit a rating and review.
            </p>
        <?php else: ?>
            <div id="reviewMsg" style="display:none; margin-top:10px;"></div>
            <form id="ajaxReviewForm" action="<?= $base_url ?>/reviews/submit_review.php" method="POST" style="margin-top:16px;">
                <input type="hidden" name="house_id" value="<?= $house_id ?>">
                
                <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group" style="margin:0;">
                        <label>Overall Experience</label>
                        <div class="star-rating-input">
                            <input type="radio" id="ov5" name="overall_rating" value="5" required><label for="ov5">★</label>
                            <input type="radio" id="ov4" name="overall_rating" value="4"><label for="ov4">★</label>
                            <input type="radio" id="ov3" name="overall_rating" value="3"><label for="ov3">★</label>
                            <input type="radio" id="ov2" name="overall_rating" value="2"><label for="ov2">★</label>
                            <input type="radio" id="ov1" name="overall_rating" value="1"><label for="ov1">★</label>
                        </div>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label>Cleanliness & Hygiene</label>
                        <div class="star-rating-input">
                            <input type="radio" id="cl5" name="cleanliness_rating" value="5" required><label for="cl5">★</label>
                            <input type="radio" id="cl4" name="cleanliness_rating" value="4"><label for="cl4">★</label>
                            <input type="radio" id="cl3" name="cleanliness_rating" value="3"><label for="cl3">★</label>
                            <input type="radio" id="cl2" name="cleanliness_rating" value="2"><label for="cl2">★</label>
                            <input type="radio" id="cl1" name="cleanliness_rating" value="1"><label for="cl1">★</label>
                        </div>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label>Safety & Security</label>
                        <div class="star-rating-input">
                            <input type="radio" id="sf5" name="safety_rating" value="5" required><label for="sf5">★</label>
                            <input type="radio" id="sf4" name="safety_rating" value="4"><label for="sf4">★</label>
                            <input type="radio" id="sf3" name="safety_rating" value="3"><label for="sf3">★</label>
                            <input type="radio" id="sf2" name="safety_rating" value="2"><label for="sf2">★</label>
                            <input type="radio" id="sf1" name="safety_rating" value="1"><label for="sf1">★</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Your Review & Feedback</label>
                    <textarea name="comment" rows="3" required placeholder="Share details about Wi-Fi speed, water supply, study environment, and landlord..."></textarea>
                </div>
                <button type="submit" class="btn btn-success" style="width:100%;">Submit Rating</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<h3 style="margin: 24px 0 12px;">Student Reviews (<?= count($reviews) ?>)</h3>
<?php if (empty($reviews)): ?>
    <div class="card">No reviews yet. Be the first to review this boarding house!</div>
<?php else: ?>
    <?php foreach ($reviews as $rev): ?>
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong>👤 <?= htmlspecialchars($rev['full_name']) ?></strong>
                    <span class="meta-text" style="margin-left:10px;"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
                </div>
                <div class="rating-star">
                    Overall: ★ <?= $rev['overall_rating'] ?>/5 |
                    Cleanliness: <?= $rev['cleanliness_rating'] ?>/5 |
                    Safety: <?= $rev['safety_rating'] ?>/5
                </div>
            </div>
            <p style="margin-top: 10px;"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>

            <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $rev['user_id'] || $_SESSION['role'] === 'admin')): ?>
                <form action="<?= $base_url ?>/reviews/delete_review.php" method="POST" style="margin-top:10px;">
                    <input type="hidden" name="review_id" value="<?= $rev['review_id'] ?>">
                    <input type="hidden" name="house_id" value="<?= $house_id ?>">
                    <button type="submit" class="btn btn-danger" style="padding:4px 10px; font-size:0.8rem;">Delete Review</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>