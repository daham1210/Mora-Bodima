<?php
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-error">Access denied. Admin privileges required.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $house_id = (int)($_POST['house_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    if (in_array($action, ['approved', 'pending', 'rejected'], true)) {
        $stmt = $pdo->prepare("UPDATE boarding_houses SET status = ? WHERE house_id = ?");
        $stmt->execute([$action, $house_id]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM boarding_houses WHERE house_id = ?");
        $stmt->execute([$house_id]);
    }
}

$houses = $pdo->query("
    SELECT b.*, u.full_name AS owner_name
    FROM boarding_houses b
    JOIN users u ON b.owner_id = u.user_id
    ORDER BY b.created_at DESC
")->fetchAll();

$totalUsers   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalHouses  = count($houses);
$totalReviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
?>

<h2 style="margin-bottom: 16px;">Admin Moderation Dashboard</h2>

<div class="grid" style="margin-bottom: 24px;">
    <div class="card"><h3>Total Users</h3><p style="font-size:1.8rem;font-weight:700;color:#2563eb;"><?= $totalUsers ?></p></div>
    <div class="card"><h3>Boarding Listings</h3><p style="font-size:1.8rem;font-weight:700;color:#16a34a;"><?= $totalHouses ?></p></div>
    <div class="card"><h3>Submitted Reviews</h3><p style="font-size:1.8rem;font-weight:700;color:#d97706;"><?= $totalReviews ?></p></div>
</div>

<div class="card" style="overflow-x:auto;">
    <h3 style="margin-bottom: 12px;">Manage Boarding House Listings</h3>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Owner</th>
                <th>Rent (Rs.)</th>
                <th>Rating</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($houses as $h): ?>
                <tr>
                    <td>#<?= $h['house_id'] ?></td>
                    <td>
                        <a href="<?= $base_url ?>/listings/view_house.php?id=<?= $h['house_id'] ?>">
                            <strong><?= htmlspecialchars($h['title']) ?></strong>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($h['owner_name']) ?></td>
                    <td><?= number_format($h['monthly_rent'], 2) ?></td>
                    <td>★ <?= number_format($h['avg_rating'], 1) ?> (<?= $h['total_reviews'] ?>)</td>
                    <td><span class="badge badge-<?= $h['status'] ?>"><?= ucfirst($h['status']) ?></span></td>
                    <td>
                        <form method="POST" style="display:flex; gap:6px; flex-wrap:wrap;">
                            <input type="hidden" name="house_id" value="<?= $h['house_id'] ?>">
                            <button name="action" value="approved" class="btn btn-success" style="padding:4px 8px;font-size:0.8rem;">Approve</button>
                            <button name="action" value="rejected" class="btn btn-warning" style="padding:4px 8px;font-size:0.8rem;">Reject</button>
                            <button name="action" value="delete" class="btn btn-danger" style="padding:4px 8px;font-size:0.8rem;" onclick="return confirm('Delete this listing?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>