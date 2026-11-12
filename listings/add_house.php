<?php
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'], true)) {
    echo '<div class="alert alert-error">Only Boarding Owners and Admins can add listings.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title           = trim($_POST['title'] ?? '');
    $address         = trim($_POST['address'] ?? '');
    $google_maps     = trim($_POST['google_maps_link'] ?? '');
    $distance_km     = (float) ($_POST['distance_km'] ?? 0);
    $monthly_rent    = (float) ($_POST['monthly_rent'] ?? 0);
    $gender_type     = $_POST['gender_type'] ?? 'mixed';
    $description     = trim($_POST['description'] ?? '');
    $amenities       = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
    $status          = 'approved';

    if ($title === '' || $address === '' || $monthly_rent <= 0) {
        $err = 'Please provide a valid title, address, and monthly rent.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO boarding_houses (owner_id, title, address, google_maps_link, distance_km, monthly_rent, gender_type, description, amenities, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'], $title, $address, $google_maps, $distance_km,
            $monthly_rent, $gender_type, $description, $amenities, $status
        ]);
        $house_id = (int)$pdo->lastInsertId();

        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (isset($allowed[$ext])) {
                $rawBinary = file_get_contents($_FILES['photo']['tmp_name']);
                $base64DataUri = 'data:' . $allowed[$ext] . ';base64,' . base64_encode($rawBinary);

                $imgStmt = $pdo->prepare("INSERT INTO house_images (house_id, image_path) VALUES (?, ?)");
                $imgStmt->execute([$house_id, $base64DataUri]);
            } else {
                $err = 'Only JPG, JPEG, PNG, and WEBP images are allowed.';
            }
        }

        if (!$err) {
            $msg = 'Boarding house added successfully! <a href="view_house.php?id=' . $house_id . '"><strong>View Listing</strong></a>';
        }
    }
}
?>

<div class="form-box" style="max-width: 680px;">
    <h2 style="margin-bottom: 16px;">Add New Boarding House</h2>

    <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Boarding Title</label>
            <input type="text" name="title" required placeholder="e.g., Green View Student Villa">
        </div>
        <div class="form-group">
            <label>Street Address</label>
            <input type="text" name="address" required placeholder="e.g., 124 Katubedda Road">
        </div>
        <div class="form-group">
            <label>Google Maps Link / Embed URL (Optional)</label>
            <input type="text" name="google_maps_link" placeholder="Paste Google Maps share link or embed iframe URL">
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px;">
            <div class="form-group">
                <label>Distance (km)</label>
                <input type="number" step="0.01" name="distance_km" required placeholder="0.50">
            </div>
            <div class="form-group">
                <label>Monthly Rent (Rs.)</label>
                <input type="number" step="100" name="monthly_rent" required placeholder="15000">
            </div>
            <div class="form-group">
                <label>Gender Type</label>
                <select name="gender_type">
                    <option value="male">Male Only</option>
                    <option value="female">Female Only</option>
                    <option value="mixed">Mixed</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Amenities</label>
            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:8px;">
                <?php
                $allAmenities = ['Wi-Fi', 'Attached Bath', 'Study Desk', 'CCTV Security', 'Cooking Allowed', 'Filtered Water', 'Laundry', 'Backup Power', 'Parking'];
                foreach ($allAmenities as $am):
                ?>
                    <label style="font-weight:normal;">
                        <input type="checkbox" name="amenities[]" value="<?= $am ?>" style="width:auto;"> <?= $am ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Description & Rules</label>
            <textarea name="description" rows="4" required placeholder="Describe room capacity, water/electricity bills, curfews, etc."></textarea>
        </div>

        <div class="form-group">
            <label>Upload Photo to Database (JPG, PNG, WEBP)</label>
            <input type="file" name="photo" accept="image/*">
        </div>

        <button type="submit" class="btn" style="width:100%;">Publish Boarding House</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>