<?php
require_once __DIR__ . '/config/db_con.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function pf_e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pf_redirect($type, $msg) {
    header("Location: ../frontend/profile.html?$type=" . urlencode($msg));
    exit;
}

if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header("Location: ../frontend/login.html?err=" . urlencode("Please log in to edit your profile."));
        exit;
    }
    echo '<div class="vh-card vh-error-card">'
       . '<span>🔒</span>'
       . '<h2>Please Sign In</h2>'
       . '<p>You need to be logged in to view and edit your profile.</p>'
       . '<a class="vh-btn vh-btn-primary" href="login.html">Login / Register</a>'
       . '</div>';
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT user_id, name, email, pass, role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo '<div class="vh-card vh-error-card"><span>⚠️</span><h2>User not found</h2></div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $email === '') {
        pf_redirect('err', "Full name and email address are required.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        pf_redirect('err', "Please enter a valid email address.");
    }

    $chk = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $chk->bind_param("si", $email, $user_id);
    $chk->execute();
    $dup = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($dup) {
        pf_redirect('err', "That email address is already taken by another account.");
    }

    if ($new_password !== '') {
        if (mb_strlen($new_password) < 4) {
            pf_redirect('err', "New password must be at least 4 characters long.");
        }
        if ($new_password !== $confirm_password) {
            pf_redirect('err', "New password and confirm password do not match.");
        }

        $validCurrent = password_verify($current_password, $user['pass']) || $current_password === $user['pass'];
        if (!$validCurrent) {
            pf_redirect('err', "Your current password is incorrect.");
        }

        $hashedNew = password_hash($new_password, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET name = ?, email = ?, pass = ? WHERE user_id = ?");
        $upd->bind_param("sssi", $full_name, $email, $hashedNew, $user_id);
    } else {
        $upd = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
        $upd->bind_param("ssi", $full_name, $email, $user_id);
    }

    if ($upd->execute()) {
        $upd->close();
        $_SESSION['full_name'] = $full_name;
        pf_redirect('msg', "Profile updated successfully!");
    } else {
        $upd->close();
        pf_redirect('err', "Could not update profile. Please try again.");
    }
}

$myHouses = [];
if (in_array($user['role'], ['owner', 'admin'], true)) {
    $hStmt = $conn->prepare("SELECT house_id, title, address, monthly_rent, status, avg_rating, total_reviews
                             FROM boarding_houses WHERE owner_id = ? ORDER BY house_id DESC");
    $hStmt->bind_param("i", $user_id);
    $hStmt->execute();
    $myHouses = $hStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $hStmt->close();
}

$rStmt = $conn->prepare("SELECT r.review_id, r.house_id, r.overall_rating, r.comment, r.created_at, b.title AS house_title
                         FROM reviews r
                         JOIN boarding_houses b ON r.house_id = b.house_id
                         WHERE r.user_id = ?
                         ORDER BY r.review_id DESC");
$rStmt->bind_param("i", $user_id);
$rStmt->execute();
$myReviews = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rStmt->close();

$initial = mb_strtoupper(mb_substr($user['name'], 0, 1));
?>

<div class="vh-wrap">
    <div class="vh-hero">
        <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
            <div class="vh-avatar" style="width: 72px; height: 72px; font-size: 28px; border: 3px solid rgba(255,255,255,0.4);">
                <?= pf_e($initial) ?>
            </div>
            <div>
                <h1><?= pf_e($user['name']) ?></h1>
                <div class="vh-address">✉️ <?= pf_e($user['email']) ?></div>
                <div class="vh-hero-badges">
                    <span class="vh-badge badge-male">👤 <?= pf_e(ucfirst($user['role'])) ?> Account</span>
                    <?php if (in_array($user['role'], ['owner', 'admin'], true)): ?>
                        <span class="vh-badge" style="background:rgba(255,255,255,0.2);color:#fff;">
                            🏠 <?= count($myHouses) ?> Listing<?= count($myHouses) === 1 ? '' : 's' ?>
                        </span>
                    <?php endif; ?>
                    <span class="vh-badge" style="background:rgba(255,255,255,0.2);color:#fff;">
                        ⭐ <?= count($myReviews) ?> Review<?= count($myReviews) === 1 ? '' : 's' ?> Written
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="vh-grid">
        <div>
            <div class="vh-card">
                <h3>✏️ Modify Profile Details</h3>
                <form action="../backend/profile_process.php" method="POST">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required value="<?= pf_e($user['name']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?= pf_e($user['email']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Account Role</label>
                        <input type="text" value="<?= pf_e(ucfirst($user['role'])) ?>" disabled style="opacity: 0.7; cursor: not-allowed;">
                    </div>

                    <hr class="divider">
                    <h3 style="font-size: 1.05rem; margin-bottom: 6px;">🔒 Change Password <span class="vh-muted" style="font-weight: 500;">(Optional)</span></h3>
                    <p class="vh-muted" style="margin-bottom: 16px;">Leave these blank if you do not want to change your password.</p>

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter current password to confirm changes">
                    </div>

                    <div class="form-row" style="display: flex; gap: 14px; flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1; min-width: 180px;">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" minlength="4" placeholder="New password">
                        </div>
                        <div class="form-group" style="flex: 1; min-width: 180px;">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="4" placeholder="Confirm new password">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; margin-top: 8px;">💾 Save Profile Changes</button>
                </form>
            </div>
        </div>
        <div>
            <?php if (in_array($user['role'], ['owner', 'admin'], true)): ?>
                <div class="vh-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                        <h3 style="margin:0;">🏠 My Boarding Listings</h3>
                        <a href="add_house.html" class="vh-btn vh-btn-outline" style="padding:6px 12px; font-size:0.82rem;">+ Add New</a>
                    </div>

                    <?php if (count($myHouses) === 0): ?>
                        <p class="vh-muted">You haven't published any boarding houses yet.</p>
                    <?php else: ?>
                        <?php foreach ($myHouses as $h): ?>
                            <div class="vh-review" style="padding: 14px 0;">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                                    <div>
                                        <a href="view_house.html?id=<?= (int)$h['house_id'] ?>" style="font-weight:800; color:inherit; text-decoration:none;">
                                            <?= pf_e($h['title']) ?>
                                        </a>
                                        <div class="vh-muted">Rs. <?= number_format((float)$h['monthly_rent'], 2) ?> / month · ⭐ <?= number_format((float)$h['avg_rating'], 1) ?></div>
                                    </div>
                                    <span class="vh-badge st-<?= pf_e($h['status']) ?>"><?= pf_e(ucfirst($h['status'])) ?></span>
                                </div>
                                <div style="margin-top: 8px; display:flex; gap:8px;">
                                    <a href="view_house.html?id=<?= (int)$h['house_id'] ?>" class="vh-btn vh-btn-outline" style="padding:5px 10px; font-size:0.78rem;">👁 View</a>
                                    <a href="edit_house.html?id=<?= (int)$h['house_id'] ?>" class="vh-btn vh-btn-primary" style="padding:5px 10px; font-size:0.78rem;">✏️ Edit</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="vh-card">
                <h3>💬 My Submitted Reviews</h3>
                <?php if (count($myReviews) === 0): ?>
                    <p class="vh-muted">You haven't written any reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($myReviews as $r): ?>
                        <div class="vh-review" style="padding: 14px 0;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <a href="view_house.html?id=<?= (int)$r['house_id'] ?>" style="font-weight:800; color:inherit; text-decoration:none;">
                                    <?= pf_e($r['house_title']) ?>
                                </a>
                                <span class="vh-chip">⭐ <?= (int)$r['overall_rating'] ?>/5</span>
                            </div>
                            <p class="vh-review-text" style="font-size:0.88rem; margin-top:6px;"><?= pf_e($r['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>