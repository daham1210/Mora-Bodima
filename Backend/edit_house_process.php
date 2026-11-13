<?php
// backend/edit_house_process.php
// Update Listing & Delete Photo Handler
//   GET  ?id=X                      -> outputs the pre-filled edit form (fetched by app.js)
//   GET  ?delete_img=Y&id=X         -> deletes one photo, redirects back to edit_house.html
//   POST (hidden house_id + fields) -> updates listing, adds new photo, redirects to view_house.html

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

$base_url = $base_url ?? '..';

// Redirect helper (used by delete / update actions)
function go($page, $key, $msg, $house_id = 0) {
    global $base_url;
    $idPart = $house_id ? "id=" . (int)$house_id . "&" : "";
    header("Location: $base_url/frontend/$page?{$idPart}$key=" . urlencode($msg));
    exit;
}

// Catch uploads that exceed PHP's post_max_size limit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    go('index.html', 'err', "Uploaded file exceeds the server's maximum size limit.");
}

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['delete_img'])) {
        echo '<div class="alert alert-error">Please log in to edit a boarding house.</div>';
        exit;
    }
    header("Location: $base_url/frontend/login.html?err=" . urlencode("Please log in first."));
    exit;
}

// 1. Determine which house we are working on
$is_post  = ($_SERVER['REQUEST_METHOD'] === 'POST');
$house_id = $is_post ? (int)($_POST['house_id'] ?? 0) : (int)($_GET['id'] ?? 0);

if ($house_id <= 0) {
    echo '<div class="alert alert-error">Invalid boarding house.</div>';
    exit;
}

// 2. Fetch the house
$stmt = $pdo->prepare("SELECT * FROM boarding_houses WHERE house_id = ?");
$stmt->execute([$house_id]);
$house = $stmt->fetch();

if (!$house) {
    echo '<div class="alert alert-error">Boarding house not found.</div>';
    exit;
}

// 3. Ownership authorization: owner of this house OR admin
if ((int)$_SESSION['user_id'] !== (int)$house['owner_id'] && ($_SESSION['role'] ?? '') !== 'admin') {
    if ($is_post || isset($_GET['delete_img'])) {
        go('index.html', 'err', "You are not allowed to edit this listing.");
    }
    echo '<div class="alert alert-error">You are not allowed to edit this listing.</div>';
    exit;
}

// 4. DELETE a single photo (GET ?delete_img=IMAGE_ID&id=HOUSE_ID)
if (isset($_GET['delete_img'])) {
    $image_id = (int)$_GET['delete_img'];

    // Prevent deleting the last remaining photo
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM house_images WHERE house_id = ?");
    $countStmt->execute([$house_id]);
    if ((int)$countStmt->fetchColumn() <= 1) {
        go('edit_house.html', 'err', "You must keep at least one photo. Upload a new photo before deleting this one.", $house_id);
    }

    // Look up the file name first so we can also remove it from /uploads/
    $imgStmt = $pdo->prepare("SELECT image_path FROM house_images WHERE image_id = ? AND house_id = ?");
    $imgStmt->execute([$image_id, $house_id]);
    $img = $imgStmt->fetch();

    if ($img) {
        $del = $pdo->prepare("DELETE FROM house_images WHERE image_id = ? AND house_id = ?");
        $del->execute([$image_id, $house_id]);

        $file = __DIR__ . '/../uploads/' . basename($img['image_path']);
        if (is_file($file)) {
            unlink($file);
        }
        go('edit_house.html', 'msg', "Photo deleted.", $house_id);
    }
    go('edit_house.html', 'err', "Photo not found.", $house_id);
}

// 5. UPDATE the listing (POST)
if ($is_post) {
    $title            = trim($_POST['title'] ?? '');
    $address          = trim($_POST['address'] ?? '');
    $google_maps_link = trim($_POST['google_maps_link'] ?? '');
    $distance_km      = $_POST['distance_km'] ?? '';
    $monthly_rent     = $_POST['monthly_rent'] ?? '';
    $gender_type      = strtolower(trim($_POST['gender_type'] ?? ''));
    $description      = trim($_POST['description'] ?? '');

    $amenities_arr = isset($_POST['amenities']) && is_array($_POST['amenities']) ? $_POST['amenities'] : [];
    $amenities     = implode(',', array_filter(array_map('trim', $amenities_arr)));

    // Validation
    if ($title === '' || $address === '') {
        go('edit_house.html', 'err', "Title and address are required.", $house_id);
    }
    if ($google_maps_link !== '') {
        if (!filter_var($google_maps_link, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $google_maps_link)) {
            go('edit_house.html', 'err', "Please enter a valid http:// or https:// Google Maps link.", $house_id);
        }
    }
    if (!is_numeric($distance_km) || $distance_km < 0) {
        go('edit_house.html', 'err', "Please enter a valid distance.", $house_id);
    }
    if (!is_numeric($monthly_rent) || $monthly_rent <= 0) {
        go('edit_house.html', 'err', "Please enter a valid monthly rent.", $house_id);
    }
    if (!in_array($gender_type, ['male', 'female', 'mixed'], true)) {
        go('edit_house.html', 'err', "Please select a valid gender type.", $house_id);
    }

    try {
        $upd = $pdo->prepare(
            "UPDATE boarding_houses
                SET title = ?, address = ?, google_maps_link = ?, distance_km = ?,
                    monthly_rent = ?, gender_type = ?, amenities = ?, description = ?
              WHERE house_id = ?"
        );
        $upd->execute([
            $title, $address, $google_maps_link, $distance_km,
            $monthly_rent, $gender_type, $amenities, $description, $house_id
        ]);

        // Optional: add a newly uploaded photo (field name "image" or "photo")
        $fileKey = isset($_FILES['image']) ? 'image' : (isset($_FILES['photo']) ? 'photo' : null);

        if ($fileKey !== null && $_FILES[$fileKey]['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
                go('edit_house.html', 'err', "Details saved, but the photo failed to upload (file may exceed server limit).", $house_id);
            }

            $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            $originalName = basename($_FILES[$fileKey]['name']);
            $ext  = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (function_exists('mime_content_type')) {
                $mime = mime_content_type($_FILES[$fileKey]['tmp_name']);
            } else {
                $imgInfo = @getimagesize($_FILES[$fileKey]['tmp_name']);
                $mime = $imgInfo['mime'] ?? '';
            }

            if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
                go('edit_house.html', 'err', "Details saved, but the photo must be a JPG, PNG, GIF or WEBP image.", $house_id);
            }
            if ($_FILES[$fileKey]['size'] > 5 * 1024 * 1024) {
                go('edit_house.html', 'err', "Details saved, but the photo is too large (max 5 MB).", $house_id);
            }

            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $safeName    = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
            $newFileName = time() . '_' . $safeName;

            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadDir . $newFileName)) {
                $ins = $pdo->prepare("INSERT INTO house_images (house_id, image_path) VALUES (?, ?)");
                $ins->execute([$house_id, $newFileName]);
            } else {
                go('edit_house.html', 'err', "Details saved, but the photo could not be moved to uploads.", $house_id);
            }
        }

        go('view_house.html', 'msg', "Listing updated successfully!", $house_id);

    } catch (PDOException $e) {
        error_log("Edit house error: " . $e->getMessage());
        go('edit_house.html', 'err', "Could not update the listing. Please try again.", $house_id);
    }
}

// 6. Otherwise (GET ?id=): output the pre-filled edit form HTML
header('Content-Type: text/html; charset=UTF-8');

$imgStmt = $pdo->prepare("SELECT image_id, image_path FROM house_images WHERE house_id = ? ORDER BY image_id ASC");
$imgStmt->execute([$house_id]);
$images = $imgStmt->fetchAll();

$all_amenities = ['WiFi', 'Attached Bathroom', 'AC', 'Parking', 'Furnished', 'Kitchen', 'Hot Water', 'CCTV', 'Laundry'];
$selected      = array_filter(array_map('trim', explode(',', (string)$house['amenities'])));
$e             = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$cleanRent     = (float)$house['monthly_rent'];
?>
<form action="<?= $e($base_url) ?>/backend/edit_house_process.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="house_id" value="<?= (int)$house['house_id'] ?>">

    <div class="form-group">
        <label for="title">Boarding House Title</label>
        <input type="text" id="title" name="title" required value="<?= $e($house['title']) ?>">
    </div>

    <div class="form-group">
        <label for="address">Address</label>
        <input type="text" id="address" name="address" required value="<?= $e($house['address']) ?>">
    </div>

    <div class="form-group">
        <label for="google_maps_link">Google Maps Location Link</label>
        <input type="url" id="google_maps_link" name="google_maps_link" value="<?= $e($house['google_maps_link']) ?>">
    </div>

    <div class="form-row" style="display: flex; gap: 15px;">
        <div class="form-group" style="flex: 1;">
            <label for="distance_km">Distance from Uni (km)</label>
            <input type="number" step="0.1" id="distance_km" name="distance_km" required value="<?= $e($house['distance_km']) ?>">
        </div>
        <div class="form-group" style="flex: 1;">
            <label for="monthly_rent">Monthly Rent (Rs.)</label>
            <input type="number" step="any" id="monthly_rent" name="monthly_rent" required value="<?= $e($cleanRent) ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="gender_type">Preferred Gender / Tenant Type</label>
        <select id="gender_type" name="gender_type" required>
            <?php foreach (['male' => 'Male', 'female' => 'Female', 'mixed' => 'Mixed'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $house['gender_type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Amenities (Select all that apply)</label>
        <div class="checkbox-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 5px;">
            <?php foreach ($all_amenities as $a): ?>
                <label>
                    <input type="checkbox" name="amenities[]" value="<?= $e($a) ?>" <?= in_array($a, $selected, true) ? 'checked' : '' ?>>
                    <?= $e($a) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?= $e($house['description']) ?></textarea>
    </div>

    <div class="form-group">
        <label>Current Photos</label>
        <?php if (count($images) === 0): ?>
            <p style="color:#64748b;">No photos uploaded yet.</p>
        <?php else: ?>
            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 6px;">
                <?php foreach ($images as $img): ?>
                    <div style="text-align:center;">
                        <img src="<?= $e($base_url . '/uploads/' . rawurlencode(basename($img['image_path']))) ?>"
                             alt="House photo" style="width:120px; height:90px; object-fit:cover; border-radius:6px;">
                        <br>
                        <a href="<?= $e($base_url) ?>/backend/edit_house_process.php?delete_img=<?= (int)$img['image_id'] ?>&id=<?= (int)$house['house_id'] ?>"
                           style="color:#dc2626; font-size:13px;"
                           onclick="return confirm('Delete this photo?');">🗑 Delete</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="image">Add a New Photo (optional)</label>
        <input type="file" id="image" name="image" accept="image/*">
    </div>

    <button type="submit" class="btn-primary" style="width: 100%; margin-top: 15px;">Save Changes</button>
</form>
