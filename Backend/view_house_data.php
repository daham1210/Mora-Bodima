<?php
// backend/view_house_data.php
// House Details Reader (GET) & Review Submission Handler (POST)

require_once __DIR__ . '/config/db_con.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function vh_e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function vh_redirect($url) {
    header("Location: $url");
    exit;
}

function vh_image_src($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(data:image/|https?://)#i', $path)) {
        return $path;
    }
    return '../uploads/' . rawurlencode(basename($path));
}

function vh_image_exists($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return false;
    }
    if (preg_match('#^(data:image/|https?://)#i', $path)) {
        return true;
    }
    return is_file(__DIR__ . '/../uploads/' . basename($path));
}

function vh_stars($rating) {
    $full = (int)round((float)$rating);
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $full ? '<span class="vh-star on">★</span>' : '<span class="vh-star">★</span>';
    }
    return $html;
}

function vh_gender_badge($gender) {
    $g = strtolower(trim((string)$gender));
    if (strpos($g, 'female') !== false || strpos($g, 'girl') !== false) {
        return ['badge-female', '👩 Girls Only'];
    }
    if (strpos($g, 'male') !== false || strpos($g, 'boy') !== false) {
        return ['badge-male', '👨 Boys Only'];
    }
    return ['badge-mixed', '👥 Mixed'];
}

function vh_rating_value($key) {
    $v = isset($_POST[$key]) ? (int)$_POST[$key] : 0;
    return ($v >= 1 && $v <= 5) ? $v : 0;
}

function vh_error_card($icon, $title, $text) {
    echo '<div class="vh-wrap"><div class="vh-card vh-error-card"><span>' . $icon . '</span>'
       . '<h2>' . vh_e($title) . '</h2><p>' . vh_e($text) . '</p>'
       . '<a class="vh-btn vh-btn-primary" href="index.html">← Back to Search</a></div></div>';
    exit;
}

$colClean  = $conn->query("SHOW COLUMNS FROM reviews LIKE 'cleanliness_rating'");
$colSafety = $conn->query("SHOW COLUMNS FROM reviews LIKE 'safety_rating'");
$hasCategoryRatings = ($colClean && $colClean->num_rows > 0) && ($colSafety && $colSafety->num_rows > 0);

$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;
$userRole   = $isLoggedIn ? ($_SESSION['role'] ?? '') : '';

// PART 1: POST — Save or update a review
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $house_id = isset($_POST['house_id']) ? (int)$_POST['house_id'] : 0;
    $backUrl  = "../frontend/view_house.html?id=$house_id";

    if (!$isLoggedIn) {
        vh_redirect("../frontend/login.html?err=" . urlencode("Please log in to write a review."));
    }

    $stmt = $conn->prepare("SELECT house_id, owner_id, status FROM boarding_houses WHERE house_id = ?");
    $stmt->bind_param("i", $house_id);
    $stmt->execute();
    $house = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$house || $house['status'] !== 'approved') {
        vh_redirect("../frontend/index.html?err=" . urlencode("That boarding house is not available for reviews."));
    }

    if ((int)$house['owner_id'] === $userId) {
        vh_redirect($backUrl . "&err=" . urlencode("You cannot review your own boarding house."));
    }

    $overall     = vh_rating_value('overall_rating');
    $cleanliness = vh_rating_value('cleanliness_rating') ?: $overall;
    $safety      = vh_rating_value('safety_rating') ?: $overall;
    $comment     = trim($_POST['comment'] ?? '');

    if ($overall === 0) {
        vh_redirect($backUrl . "&err=" . urlencode("Please choose an overall star rating (1 to 5)."));
    }
    if (mb_strlen($comment) < 5) {
        vh_redirect($backUrl . "&err=" . urlencode("Please write a short comment (at least 5 characters)."));
    }
    if (mb_strlen($comment) > 2000) {
        $comment = mb_substr($comment, 0, 2000);
    }

    $inTransaction = false;
    try {
        $conn->begin_transaction();
        $inTransaction = true;

        $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE house_id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $house_id, $userId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $revId = (int)$existing['review_id'];
            if ($hasCategoryRatings) {
                $stmt = $conn->prepare("UPDATE reviews SET overall_rating = ?, cleanliness_rating = ?, safety_rating = ?, comment = ? WHERE review_id = ?");
                $stmt->bind_param("iiisi", $overall, $cleanliness, $safety, $comment, $revId);
            } else {
                $stmt = $conn->prepare("UPDATE reviews SET overall_rating = ?, comment = ? WHERE review_id = ?");
                $stmt->bind_param("isi", $overall, $comment, $revId);
            }
            $stmt->execute();
            $stmt->close();
            $message = "Your review has been updated. Thank you!";
        } else {
            if ($hasCategoryRatings) {
                $stmt = $conn->prepare("INSERT INTO reviews (house_id, user_id, overall_rating, cleanliness_rating, safety_rating, comment) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiiis", $house_id, $userId, $overall, $cleanliness, $safety, $comment);
            } else {
                $stmt = $conn->prepare("INSERT INTO reviews (house_id, user_id, overall_rating, comment) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $house_id, $userId, $overall, $comment);
            }
            $stmt->execute();
            $stmt->close();
            $message = "Your review was posted successfully. Thank you!";
        }

        $stmt = $conn->prepare("SELECT AVG(overall_rating) AS avg_r, COUNT(*) AS total_r FROM reviews WHERE house_id = ?");
        $stmt->bind_param("i", $house_id);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $newAvg   = round((float)($stats['avg_r'] ?? 0), 2);
        $newTotal = (int)($stats['total_r'] ?? 0);
        $stmt = $conn->prepare("UPDATE boarding_houses SET avg_rating = ?, total_reviews = ? WHERE house_id = ?");
        $stmt->bind_param("dii", $newAvg, $newTotal, $house_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $inTransaction = false;
    } catch (Throwable $e) {
        if ($inTransaction) {
            $conn->rollback();
        }
        vh_redirect($backUrl . "&err=" . urlencode("Could not save your review. Please try again."));
    }

    vh_redirect($backUrl . "&msg=" . urlencode($message));
}

// PART 2: GET — Output house details, gallery, rating summary & reviews
$house_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($house_id <= 0) {
    vh_error_card('🔎', 'No boarding house selected', 'Open a boarding house from the home page to see its details.');
}

// 1. House details + owner info
$stmt = $conn->prepare("SELECT b.*, u.name AS owner_name, u.email AS owner_email
                        FROM boarding_houses b
                        JOIN users u ON b.owner_id = u.user_id
                        WHERE b.house_id = ?");
$stmt->bind_param("i", $house_id);
$stmt->execute();
$house = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$house) {
    vh_error_card('🏚️', 'Boarding house not found', 'This listing may have been removed by the owner or an administrator.');
}

$isOwner = $isLoggedIn && (int)$house['owner_id'] === $userId;
$isAdmin = $userRole === 'admin';

if ($house['status'] !== 'approved' && !$isOwner && !$isAdmin) {
    vh_error_card('⏳', 'Listing not available', 'This boarding house is waiting for admin approval or has been rejected.');
}

// 2. Gallery photos
$stmt = $conn->prepare("SELECT image_id, image_path FROM house_images WHERE house_id = ? ORDER BY image_id ASC");
$stmt->bind_param("i", $house_id);
$stmt->execute();
$rawImages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$images = array_values(array_filter($rawImages, fn($img) => vh_image_exists($img['image_path'])));

// 3. Reviews list
$stmt = $conn->prepare("SELECT r.*, u.name AS reviewer_name, u.role AS reviewer_role
                        FROM reviews r
                        JOIN users u ON r.user_id = u.user_id
                        WHERE r.house_id = ?
                        ORDER BY r.created_at DESC, r.review_id DESC");
$stmt->bind_param("i", $house_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 4. Rating averages
$avgSql = $hasCategoryRatings
    ? "SELECT AVG(overall_rating) AS avg_overall, AVG(cleanliness_rating) AS avg_clean, AVG(safety_rating) AS avg_safety, COUNT(*) AS total FROM reviews WHERE house_id = ?"
    : "SELECT AVG(overall_rating) AS avg_overall, COUNT(*) AS total FROM reviews WHERE house_id = ?";
$stmt = $conn->prepare($avgSql);
$stmt->bind_param("i", $house_id);
$stmt->execute();
$avg = $stmt->get_result()->fetch_assoc();
$stmt->close();

$avgOverall   = (float)($avg['avg_overall'] ?? 0);
$totalReviews = (int)($avg['total'] ?? 0);
$breakdown    = ['⭐ Overall Experience' => $avgOverall];
if ($hasCategoryRatings) {
    $breakdown['🧹 Cleanliness']       = (float)($avg['avg_clean'] ?? 0);
    $breakdown['🔒 Safety & Security'] = (float)($avg['avg_safety'] ?? 0);
}

$myReview = null;
foreach ($reviews as $r) {
    if ($isLoggedIn && (int)$r['user_id'] === $userId) {
        $myReview = $r;
        break;
    }
}

$amenities = array_filter(array_map('trim', explode(',', (string)$house['amenities'])));

$mapLink  = trim((string)$house['google_maps_link']);
$embedUrl = '';
if ($mapLink !== '' && preg_match('/src=["\']([^"\']+)["\']/i', $mapLink, $m)) {
    $embedUrl = $m[1];
} elseif ($mapLink !== '' && (stripos($mapLink, 'output=embed') !== false || stripos($mapLink, '/maps/embed') !== false)) {
    $embedUrl = $mapLink;
}
if ($embedUrl === '' || !preg_match('#^https://#i', $embedUrl)) {
    $embedUrl = 'https://maps.google.com/maps?q=' . urlencode($house['address'] . ', Sri Lanka') . '&z=15&output=embed';
}
$openMapUrl = preg_match('#^https?://#i', $mapLink) && stripos($mapLink, '<iframe') === false
    ? $mapLink
    : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($house['address'] . ', Sri Lanka');

[$genderClass, $genderLabel] = vh_gender_badge($house['gender_type']);
$status = (string)$house['status'];
?>
<div class="vh-wrap">

    <?php if ($status !== 'approved'): ?>
        <div class="vh-notice">
            <?= $status === 'pending'
                ? '⏳ This listing is pending admin approval. Only you and administrators can see it right now.'
                : '⛔ This listing was rejected by an administrator and is hidden from students.' ?>
        </div>
    <?php endif; ?>

    <!-- Header Banner -->
    <div class="vh-hero">
        <div>
            <h1><?= vh_e($house['title']) ?></h1>
            <div class="vh-address">📍 <?= vh_e($house['address']) ?></div>
            <div class="vh-hero-badges">
                <span class="vh-badge <?= $genderClass ?>"><?= $genderLabel ?></span>
                <span class="vh-badge" style="background:rgba(255,255,255,.18);color:#fff">🚶 <?= number_format((float)$house['distance_km'], 1) ?> km to campus</span>
                <?php if ($isOwner || $isAdmin): ?>
                    <span class="vh-badge st-<?= vh_e($status) ?>"><?= vh_e(ucfirst($status)) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="vh-hero-right">
            <div class="vh-price">Rs. <?= number_format((float)$house['monthly_rent'], 2) ?> <small>/ month</small></div>
            <div class="vh-hero-rating">
                <?= vh_stars($avgOverall) ?>
                <strong><?= number_format($avgOverall, 1) ?></strong>
                (<?= $totalReviews ?> review<?= $totalReviews === 1 ? '' : 's' ?>)
            </div>
            <?php if ($isOwner || $isAdmin): ?>
                <div style="margin-top:14px">
                    <a class="vh-btn vh-btn-light" href="edit_house.html?id=<?= (int)$house['house_id'] ?>">✏️ Edit Listing</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Photo Gallery -->
    <?php if (count($images) > 0): ?>
        <div class="vh-gallery">
            <img id="vhMainImg" class="vh-main-img" src="<?= vh_e(vh_image_src($images[0]['image_path'])) ?>" alt="<?= vh_e($house['title']) ?> photo">
            <?php if (count($images) > 1): ?>
                <div class="vh-thumbs">
                    <?php foreach ($images as $img): ?>
                        <img src="<?= vh_e(vh_image_src($img['image_path'])) ?>" alt="Room photo"
                             onclick="document.getElementById('vhMainImg').src=this.src">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="vh-no-img"><span>🏠</span>No photos uploaded yet</div>
    <?php endif; ?>

    <!-- Details + Sidebar -->
    <div class="vh-grid">
        <div>
            <div class="vh-card">
                <h3>🏡 About this Boarding House</h3>
                <div class="vh-facts">
                    <div class="vh-fact"><div class="vh-fact-label">Monthly Rent</div><div class="vh-fact-value">Rs. <?= number_format((float)$house['monthly_rent'], 2) ?></div></div>
                    <div class="vh-fact"><div class="vh-fact-label">Distance</div><div class="vh-fact-value"><?= number_format((float)$house['distance_km'], 1) ?> km</div></div>
                    <div class="vh-fact"><div class="vh-fact-label">Suitable For</div><div class="vh-fact-value"><?= vh_e(ucfirst((string)$house['gender_type'])) ?></div></div>
                    <div class="vh-fact"><div class="vh-fact-label">Rating</div><div class="vh-fact-value">⭐ <?= number_format($avgOverall, 1) ?> / 5</div></div>
                </div>
                <div class="vh-desc">
                    <?= trim((string)$house['description']) !== ''
                        ? nl2br(vh_e($house['description']))
                        : '<span class="vh-muted">The owner has not added a description yet.</span>' ?>
                </div>
            </div>

            <div class="vh-card">
                <h3>✨ Amenities</h3>
                <?php if (count($amenities) > 0): ?>
                    <div class="vh-tags">
                        <?php foreach ($amenities as $a): ?>
                            <span class="vh-tag">✓ <?= vh_e($a) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="vh-muted">No amenities listed.</p>
                <?php endif; ?>
            </div>

            <div class="vh-card">
                <h3>🗺️ Location</h3>
                <iframe class="vh-map" src="<?= vh_e($embedUrl) ?>" loading="lazy" allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade" title="Map of <?= vh_e($house['title']) ?>"></iframe>
                <a class="vh-btn vh-btn-outline" href="<?= vh_e($openMapUrl) ?>" target="_blank" rel="noopener">📍 Open in Google Maps</a>
            </div>
        </div>

        <div>
            <div class="vh-card">
                <h3>📊 Rating Breakdown</h3>
                <div class="vh-score">
                    <div class="vh-score-num"><?= number_format($avgOverall, 1) ?></div>
                    <div>
                        <div><?= vh_stars($avgOverall) ?></div>
                        <div class="vh-muted">Based on <?= $totalReviews ?> student review<?= $totalReviews === 1 ? '' : 's' ?></div>
                    </div>
                </div>
                <?php foreach ($breakdown as $label => $score): ?>
                    <div class="vh-bar-row">
                        <div class="vh-bar-top"><span><?= $label ?></span><span><?= number_format($score, 1) ?></span></div>
                        <div class="vh-bar"><div class="vh-bar-fill" style="width:<?= round(($score / 5) * 100) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="vh-card">
                <h3>📞 Contact Owner</h3>
                <div class="vh-owner">
                    <div class="vh-avatar"><?= vh_e(mb_strtoupper(mb_substr((string)$house['owner_name'], 0, 1))) ?></div>
                    <div>
                        <div style="font-weight:800"><?= vh_e($house['owner_name']) ?></div>
                        <div class="vh-muted"><?= vh_e($house['owner_email']) ?></div>
                    </div>
                </div>
                <a class="vh-btn vh-btn-primary" style="width:100%;justify-content:center"
                   href="mailto:<?= vh_e($house['owner_email']) ?>?subject=<?= rawurlencode('Enquiry about ' . $house['title'] . ' (MoraBodima)') ?>">✉️ Email Owner</a>
            </div>
        </div>
    </div>

    <!-- Write a Review -->
    <h2 class="vh-section-title">📝 <?= $myReview ? 'Update Your Review' : 'Write a Review' ?></h2>
    <div class="vh-card">
        <?php if (!$isLoggedIn): ?>
            <div class="vh-login-box">
                <p>Stayed here? Log in to share your experience with other students.</p>
                <a class="vh-btn vh-btn-primary" href="login.html">Login / Register to Review</a>
            </div>
        <?php elseif ($isOwner): ?>
            <p class="vh-muted">🏠 This is your listing, so you can't review it. Student reviews will appear below.</p>
        <?php elseif ($status !== 'approved'): ?>
            <p class="vh-muted">Reviews open once this listing is approved.</p>
        <?php else: ?>
            <?php
            $ratingFields = ['overall_rating' => 'Overall Experience'];
            if ($hasCategoryRatings) {
                $ratingFields['cleanliness_rating'] = 'Cleanliness';
                $ratingFields['safety_rating']      = 'Safety & Security';
            }
            ?>
            <form action="../backend/view_house_data.php" method="POST">
                <input type="hidden" name="house_id" value="<?= (int)$house['house_id'] ?>">

                <div class="vh-form-grid">
                    <?php foreach ($ratingFields as $field => $label): ?>
                        <?php $current = $myReview ? (int)($myReview[$field] ?? 0) : 0; ?>
                        <div>
                            <span class="vh-label"><?= $label ?></span>
                            <div class="vh-rate">
                                <?php for ($s = 5; $s >= 1; $s--): ?>
                                    <input type="radio" id="<?= $field . $s ?>" name="<?= $field ?>" value="<?= $s ?>"
                                        <?= $current === $s ? 'checked' : '' ?> required>
                                    <label for="<?= $field . $s ?>" title="<?= $s ?> star<?= $s > 1 ? 's' : '' ?>">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <label class="vh-label" for="vhComment">Your Review</label>
                <textarea id="vhComment" class="vh-textarea" name="comment" required minlength="5" maxlength="2000"
                          placeholder="How was the room, the owner, Wi-Fi, water, noise, safety at night...?"><?= $myReview ? vh_e($myReview['comment']) : '' ?></textarea>

                <button type="submit" class="vh-btn vh-btn-primary"><?= $myReview ? '💾 Update Review' : '🚀 Post Review' ?></button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Student Reviews List -->
    <h2 class="vh-section-title">💬 Student Reviews <span class="vh-count"><?= count($reviews) ?></span></h2>
    <div class="vh-card">
        <?php if (count($reviews) === 0): ?>
            <div class="vh-empty"><span>🗨️</span>No reviews yet. Be the first to review this boarding house!</div>
        <?php else: ?>
            <?php foreach ($reviews as $r): ?>
                <?php
                $isMine    = $isLoggedIn && (int)$r['user_id'] === $userId;
                $canDelete = $isMine || $isAdmin;
                ?>
                <div class="vh-review">
                    <div class="vh-review-head">
                        <div class="vh-review-who">
                            <div class="vh-avatar"><?= vh_e(mb_strtoupper(mb_substr((string)$r['reviewer_name'], 0, 1))) ?></div>
                            <div>
                                <div class="vh-review-name">
                                    <?= vh_e($r['reviewer_name']) ?>
                                    <?php if ($isMine): ?><span class="vh-you">YOU</span><?php endif; ?>
                                </div>
                                <div class="vh-muted">
                                    <?= vh_stars($r['overall_rating']) ?>
                                    <?php if (!empty($r['created_at'])): ?>
                                        · <?= vh_e(date('d M Y', strtotime($r['created_at']))) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($canDelete): ?>
                            <form action="../backend/delete_review.php" method="POST"
                                  onsubmit="return confirm('Delete this review? This cannot be undone.');">
                                <input type="hidden" name="review_id" value="<?= (int)$r['review_id'] ?>">
                                <input type="hidden" name="house_id" value="<?= (int)$house['house_id'] ?>">
                                <button type="submit" class="vh-btn vh-btn-danger">🗑 Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if ($hasCategoryRatings): ?>
                        <div class="vh-chips">
                            <span class="vh-chip">⭐ Overall <?= (int)$r['overall_rating'] ?>/5</span>
                            <span class="vh-chip">🧹 Cleanliness <?= (int)$r['cleanliness_rating'] ?>/5</span>
                            <span class="vh-chip">🔒 Safety <?= (int)$r['safety_rating'] ?>/5</span>
                        </div>
                    <?php endif; ?>

                    <div class="vh-review-text"><?= nl2br(vh_e($r['comment'])) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>