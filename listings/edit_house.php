<?php
require_once __DIR__ . '/../includes/header.php';

$house_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM boarding_houses WHERE house_id = ?");
$stmt->execute([$house_id]);
$house = $stmt->fetch();

if (!$house || (!isset($_SESSION['user_id']) || ($_SESSION['user_id'] != $house['owner_id'] && $_SESSION['role'] !== 'admin'))) {
    echo '<div class="alert alert-error">Unauthorized or boarding house not found.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$msg = '';
$err = '';

if (isset($_GET['delete_img'])) {
    $img_id = (int)$_GET['delete_img'];
    $pdo->prepare("DELETE FROM house_images WHERE image_id = ? AND house_id = ?")->execute([$img_id, $house_id]);
    $msg = 'Photo deleted from database.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title']);
    $address      = trim($_POST['address']);
    $google_maps  = trim($_POST['google_maps_link']);
    $distance_km  = (float)$_POST['distance_km'];
    $monthly_rent = (float)$_POST['monthly_rent'];
    $gender_type  = $_POST['gender_type'];
    $description  = trim($_POST['description']);
    $amenities    = trim($_POST['amenities']);

    $upd = $pdo->prepare("
        UPDATE boarding_houses
        SET title = ?, address = ?, google_maps_link = ?, distance_km = ?, monthly_rent = ?, gender_type = ?, description = ?, amenities = ?
        WHERE house_id = ?
    ");
    $upd->execute([$title, $address, $google_maps, $distance_km, $monthly_rent, $gender_type, $description, $amenities, $house_id]);

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $index => $name) {
            if ($_FILES['photos']['error'][$index] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (isset($allowed[$ext])) {
                    $rawBinary = file_get_contents($_FILES['photos']['tmp_name'][$index]);
                    $base64DataUri = 'data:' . $allowed[$ext] . ';base64,' . base64_encode($rawBinary);

                    $imgStmt = $pdo->prepare("INSERT INTO house_images (house_id, image_path) VALUES (?, ?)");
                    $imgStmt->execute([$house_id, $base64DataUri]);
                } else {
                    $err = 'Only JPG, JPEG, PNG, and WEBP files are allowed.';
                }
            }
        }
    }

    $stmt->execute([$house_id]);
    $house = $stmt->fetch();
    if (!$err) {
        $msg = 'Listing updated successfully!';
    }
}

$imgStmt = $pdo->prepare("SELECT * FROM house_images WHERE house_id = ?");
$imgStmt->execute([$house_id]);
$currentImages = $imgStmt->fetchAll();
?>

<div class="form-box" style="max-width: 680px;">
    <h2 style="margin-bottom: 16px;">Edit Boarding House</h2>
    <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($house['title']) ?>" required>
        </div>
        <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($house['address']) ?>" required>
        </div>
        <div class="form-group">
            <label>Google Maps Link / Embed URL</label>
            <input type="text" name="google_maps_link" value="<?= htmlspecialchars($house['google_maps_link'] ?? '') ?>" placeholder="Paste Google Maps link or embed URL">
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px;">
            <div class="form-group">
                <label>Distance (km)</label>
                <input type="number" step="0.01" name="distance_km" value="<?= $house['distance_km'] ?>" required>
            </div>
            <div class="form-group">
                <label>Monthly Rent (Rs.)</label>
                <input type="number" step="100" name="monthly_rent" value="<?= $house['monthly_rent'] ?>" required>
            </div>
            <div class="form-group">
                <label>Gender Type</label>
                <select name="gender_type">
                    <option value="male" <?= $house['gender_type'] === 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= $house['gender_type'] === 'female' ? 'selected' : '' ?>>Female</option>
                    <option value="mixed" <?= $house['gender_type'] === 'mixed' ? 'selected' : '' ?>>Mixed</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Amenities (Comma separated)</label>
            <input type="text" name="amenities" value="<?= htmlspecialchars($house['amenities']) ?>">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="4" required><?= htmlspecialchars($house['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Upload New Photos to Database (Multiple allowed)</label>
            <input type="file" name="photos[]" accept="image/*" multiple>
        </div>

        <?php if (!empty($currentImages)): ?>
            <div class="form-group">
                <label>Current Photos Stored in Database</label>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:10px;">
                    <?php foreach ($currentImages as $img): ?>
                        <div style="border:1px solid #cbd5e1; padding:6px; border-radius:6px; text-align:center;">
                            <img src="<?= $img['image_path'] ?>" style="width:100%; height:90px; object-fit:cover; border-radius:4px;">
                            <a href="edit_house.php?id=<?= $house_id ?>&delete_img=<?= $img['image_id'] ?>" 
                               style="display:block; margin-top:4px; color:#dc2626; font-size:0.8rem; text-decoration:none; font-weight:600;"
                               onclick="return confirm('Delete this photo?')">🗑 Remove</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn">Save Changes</button>
        <a href="view_house.php?id=<?= $house_id ?>" class="btn" style="background:#64748b;">Back to Listing</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>