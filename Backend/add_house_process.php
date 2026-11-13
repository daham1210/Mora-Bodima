<?php
// backend/add_house_process.php
// Create Listing & Image Upload Handler (receives POST from frontend/add_house.html)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

$base_url = $base_url ?? '..';

// Helper: redirect back to the form with an error message
function back_with_error($msg) {
    global $base_url;
    header("Location: $base_url/frontend/add_house.html?err=" . urlencode($msg));
    exit;
}

// 1. Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $base_url/frontend/add_house.html");
    exit;
}

// Catch uploads that exceed PHP's post_max_size limit
if (empty($_POST) && empty($_FILES) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    back_with_error("Uploaded file exceeds the server's maximum size limit.");
}

// 2. Authorization: only logged-in Owners or Admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['owner', 'admin'], true)) {
    header("Location: $base_url/frontend/login.html?err=" . urlencode("Please log in as an Owner or Admin to add a boarding house."));
    exit;
}

// 3. Read and sanitize POST fields
$owner_id         = (int)$_SESSION['user_id'];
$title            = trim($_POST['title'] ?? '');
$address          = trim($_POST['address'] ?? '');
$google_maps_link = trim($_POST['google_maps_link'] ?? '');
$distance_km      = $_POST['distance_km'] ?? '';
$monthly_rent     = $_POST['monthly_rent'] ?? '';
$gender_type      = strtolower(trim($_POST['gender_type'] ?? ''));
$description      = trim($_POST['description'] ?? '');

// amenities[] checkboxes -> comma-separated string
$amenities_arr = isset($_POST['amenities']) && is_array($_POST['amenities']) ? $_POST['amenities'] : [];
$amenities_arr = array_map('trim', $amenities_arr);
$amenities     = implode(',', array_filter($amenities_arr));

// 4. Validation
if ($title === '' || $address === '') {
    back_with_error("Title and address are required.");
}
if ($google_maps_link !== '') {
    if (!filter_var($google_maps_link, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $google_maps_link)) {
        back_with_error("Please enter a valid http:// or https:// Google Maps link.");
    }
}
if (!is_numeric($distance_km) || $distance_km < 0) {
    back_with_error("Please enter a valid distance.");
}
if (!is_numeric($monthly_rent) || $monthly_rent <= 0) {
    back_with_error("Please enter a valid monthly rent.");
}
if (!in_array($gender_type, ['male', 'female', 'mixed'], true)) {
    back_with_error("Please select a valid gender type.");
}

// Photo field: accept both "image" and "photo"
$fileKey = isset($_FILES['image']) ? 'image' : (isset($_FILES['photo']) ? 'photo' : null);

if ($fileKey === null || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
    back_with_error("Please upload a photo of the property.");
}

// Validate the image type (extension + real MIME type)
$allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

$originalName = basename($_FILES[$fileKey]['name']);
$ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (function_exists('mime_content_type')) {
    $mime = mime_content_type($_FILES[$fileKey]['tmp_name']);
} else {
    $imgInfo = @getimagesize($_FILES[$fileKey]['tmp_name']);
    $mime = $imgInfo['mime'] ?? '';
}

if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
    back_with_error("Only JPG, PNG, GIF or WEBP images are allowed.");
}
if ($_FILES[$fileKey]['size'] > 5 * 1024 * 1024) {
    back_with_error("Image is too large (max 5 MB).");
}

$destination = null;

try {
    // Start transaction so DB records only save if the image upload succeeds
    $pdo->beginTransaction();

    // 5. Insert the boarding house
    $status = ($_SESSION['role'] === 'admin') ? 'approved' : 'pending';

    $stmt = $pdo->prepare(
        "INSERT INTO boarding_houses
            (owner_id, title, address, google_maps_link, distance_km, monthly_rent,
             gender_type, amenities, description, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $owner_id, $title, $address, $google_maps_link, $distance_km, $monthly_rent,
        $gender_type, $amenities, $description, $status
    ]);

    // 6. Get the new house_id
    $house_id = (int)$pdo->lastInsertId();

    // 7. Ensure /uploads/ folder exists
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // 8. Unique, safe filename
    $safeName    = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
    $newFileName = time() . '_' . $safeName;
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($_FILES[$fileKey]['tmp_name'], $destination)) {
        throw new RuntimeException("Photo upload failed.");
    }

    $imgStmt = $pdo->prepare("INSERT INTO house_images (house_id, image_path) VALUES (?, ?)");
    $imgStmt->execute([$house_id, $newFileName]);

    // Commit both database inserts together
    $pdo->commit();

    // 9. Success
    $msg = ($status === 'approved')
        ? "Boarding house published successfully!"
        : "Boarding house submitted! It will appear after admin approval.";
    header("Location: $base_url/frontend/index.html?msg=" . urlencode($msg));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($destination !== null && file_exists($destination)) {
        unlink($destination);
    }
    error_log("Add house error: " . $e->getMessage());
    back_with_error("Could not save the boarding house. Please try again.");
}
