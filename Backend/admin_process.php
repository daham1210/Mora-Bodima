<?php
// backend/admin_process.php
// Admin Moderation & Metrics Controller (MySQLi)

require_once __DIR__ . '/config/db_con.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function adm_e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function adm_redirect($type, $text)
{
    header("Location: ../frontend/admin.html?$type=" . urlencode($text));
    exit;
}

// Helper: run a COUNT(*) query and return the integer result
function adm_count($conn, $sql)
{
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_row() : null;
    return (int)($row[0] ?? 0);
}

// Helper: return key => value pairs from a 2-column GROUP BY query
function adm_key_pair($conn, $sql)
{
    $pairs = [];
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_row()) {
            $pairs[$row[0]] = (int)$row[1];
        }
    }
    return $pairs;
}

$isAdmin = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';

/* ============================================================================
 *  PART 1 — POST: Approve, reject or delete a listing
 * ========================================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$isAdmin) {
        header("Location: ../frontend/login.html?err=" . urlencode("Admin access only. Please log in as an administrator."));
        exit;
    }

    $house_id = isset($_POST['house_id']) ? (int)$_POST['house_id'] : 0;
    $action   = $_POST['action'] ?? '';

    // Make sure the listing exists before changing it
    $stmt = $conn->prepare("SELECT house_id FROM boarding_houses WHERE house_id = ?");
    $stmt->bind_param("i", $house_id);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exists) {
        adm_redirect('err', "Listing #$house_id was not found. It may already be deleted.");
    }

    try {
        if ($action === 'approved' || $action === 'rejected') {
            $stmt = $conn->prepare("UPDATE boarding_houses SET status = ? WHERE house_id = ?");
            $stmt->bind_param("si", $action, $house_id);
            $stmt->execute();
            $stmt->close();
            adm_redirect('msg', "Listing #$house_id has been " . $action . ".");

        } elseif ($action === 'delete') {
            // Remove uploaded image files from disk before deleting the house row
            $imgStmt = $conn->prepare("SELECT image_path FROM house_images WHERE house_id = ?");
            $imgStmt->bind_param("i", $house_id);
            $imgStmt->execute();
            $imgs = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $imgStmt->close();

            foreach ($imgs as $img) {
                $path = (string)$img['image_path'];
                if (!preg_match('#^(data:image/|https?://)#i', $path)) {
                    $file = __DIR__ . '/../uploads/' . basename($path);
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }

            // ON DELETE CASCADE removes its house_images and reviews rows automatically
            $stmt = $conn->prepare("DELETE FROM boarding_houses WHERE house_id = ?");
            $stmt->bind_param("i", $house_id);
            $stmt->execute();
            $stmt->close();
            adm_redirect('msg', "Listing #$house_id and all its photos and reviews were permanently deleted.");

        } else {
            adm_redirect('err', "Unknown admin action.");
        }
    } catch (Throwable $e) {
        adm_redirect('err', "Database error. The action could not be completed.");
    }
}

/* ============================================================================
 *  PART 2 — GET: Dashboard (KPI cards + moderation table)
 * ========================================================================= */

if (!$isAdmin) {
    echo '<div style="background:#fff;border-radius:16px;padding:44px 20px;text-align:center;color:#0f172a;box-shadow:0 10px 30px rgba(0,0,0,.25)">'
       . '<div style="font-size:50px;margin-bottom:10px">🔒</div>'
       . '<h2 style="font-size:24px;margin-bottom:8px">Access Denied</h2>'
       . '<p style="color:#64748b;margin-bottom:18px">Only administrators can open the Admin Panel.</p>'
       . '<a href="login.html" style="display:inline-block;background:#2563eb;color:#fff;padding:11px 20px;border-radius:10px;font-weight:700;text-decoration:none">Login as Admin</a>'
       . '</div>';
    exit;
}

/* ---------- Summary metrics ---------- */
$totalUsers   = adm_count($conn, "SELECT COUNT(*) FROM users");
$totalHouses  = adm_count($conn, "SELECT COUNT(*) FROM boarding_houses");
$totalReviews = adm_count($conn, "SELECT COUNT(*) FROM reviews");

$statusCounts = adm_key_pair($conn, "SELECT status, COUNT(*) FROM boarding_houses GROUP BY status");
$roleCounts   = adm_key_pair($conn, "SELECT role, COUNT(*) FROM users GROUP BY role");
$pendingCount = (int)($statusCounts['pending'] ?? 0);

/* ---------- All boarding houses + owner details (pending ones first) ---------- */
$resHouses = $conn->query("SELECT b.house_id, b.title, b.address, b.monthly_rent, b.gender_type,
                                  b.avg_rating, b.total_reviews, b.status,
                                  u.name AS owner_name, u.email AS owner_email
                           FROM boarding_houses b
                           JOIN users u ON b.owner_id = u.user_id
                           ORDER BY FIELD(b.status, 'pending', 'approved', 'rejected'), b.house_id DESC");
$houses = $resHouses ? $resHouses->fetch_all(MYSQLI_ASSOC) : [];
?>
<style>
    .adm-wrap{color:#0f172a}
    .adm-hero{background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);color:#fff;border-radius:16px;padding:30px 34px;box-shadow:0 12px 32px rgba(37,99,235,.28);margin-bottom:26px}
    .adm-hero h1{font-size:30px;font-weight:800;margin-bottom:8px}
    .adm-hero p{opacity:.92;font-size:16px}
    .adm-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-bottom:10px}
    .adm-kpi{background:#fff;border-radius:16px;padding:22px 24px;box-shadow:0 10px 30px rgba(0,0,0,.25);display:flex;align-items:center;gap:16px}
    .adm-kpi-icon{width:54px;height:54px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0}
    .adm-kpi-num{font-size:32px;font-weight:800;line-height:1.1}
    .adm-kpi-label{font-weight:700;color:#334155;font-size:15px}
    .adm-kpi-sub{color:#64748b;font-size:13px;margin-top:2px}
    .adm-section-title{color:#fff;font-size:26px;font-weight:800;margin:34px 0 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .adm-pill{font-size:14px;padding:4px 12px;border-radius:999px;font-weight:700}
    .adm-pill-warn{background:#fef3c7;color:#92400e}
    .adm-pill-ok{background:#1e293b;color:#93c5fd}
    .adm-card{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.25);overflow:hidden}
    .adm-table-wrap{overflow-x:auto}
    .adm-table{width:100%;border-collapse:collapse;font-size:14px;min-width:900px}
    .adm-table th{background:#f8fafc;text-align:left;padding:14px 16px;font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:800;border-bottom:1px solid #e2e8f0}
    .adm-table td{padding:15px 16px;border-bottom:1px solid #eef2f7;vertical-align:middle}
    .adm-table tr:last-child td{border-bottom:none}
    .adm-table tbody tr:hover{background:#f8fafc}
    .adm-table tr.adm-row-pending{background:#fffbeb}
    .adm-title{font-weight:800;color:#0f172a;text-decoration:none}
    .adm-title:hover{color:#2563eb}
    .adm-muted{color:#64748b;font-size:13px}
    .adm-badge{display:inline-block;padding:4px 11px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap}
    .adm-badge.badge-male{background:#dbeafe;color:#1d4ed8}
    .adm-badge.badge-female{background:#fce7f3;color:#be185d}
    .adm-badge.badge-mixed{background:#ede9fe;color:#6d28d9}
    .adm-badge.badge-approved{background:#dcfce7;color:#166534}
    .adm-badge.badge-pending{background:#fef3c7;color:#92400e}
    .adm-badge.badge-rejected{background:#fee2e2;color:#991b1b}
    .adm-actions{display:flex;gap:6px;flex-wrap:wrap}
    .adm-actions form{margin:0}
    .adm-btn{border:none;border-radius:8px;padding:7px 11px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-block;white-space:nowrap;transition:filter .15s,transform .15s}
    .adm-btn:hover{filter:brightness(.95);transform:translateY(-1px)}
    .adm-btn-view{background:#eff6ff;color:#1d4ed8}
    .adm-btn-approve{background:#16a34a;color:#fff}
    .adm-btn-reject{background:#f59e0b;color:#fff}
    .adm-btn-delete{background:#fee2e2;color:#b91c1c}
    .adm-empty{text-align:center;padding:46px 20px;color:#64748b}
    .adm-empty span{font-size:44px;display:block;margin-bottom:8px}
</style>

<div class="adm-wrap">

    <!-- ===================== HEADER BANNER ===================== -->
    <div class="adm-hero">
        <h1>🛡️ Admin Moderation Dashboard</h1>
        <p>Review new boarding house submissions, approve or reject listings, and keep MoraBodima trustworthy for students.</p>
    </div>

    <!-- ===================== 3 KPI SUMMARY CARDS ===================== -->
    <div class="adm-kpis">
        <div class="adm-kpi">
            <div class="adm-kpi-icon" style="background:#dbeafe">👥</div>
            <div>
                <div class="adm-kpi-num"><?= number_format($totalUsers) ?></div>
                <div class="adm-kpi-label">Total Users</div>
                <div class="adm-kpi-sub">
                    <?php
                    $s = (int)($roleCounts['student'] ?? 0);
                    $o = (int)($roleCounts['owner'] ?? 0);
                    $a = (int)($roleCounts['admin'] ?? 0);
                    echo $s . ' student' . ($s === 1 ? '' : 's') . ' · '
                       . $o . ' owner' . ($o === 1 ? '' : 's') . ' · '
                       . $a . ' admin' . ($a === 1 ? '' : 's');
                    ?>
                </div>
            </div>
        </div>
        <div class="adm-kpi">
            <div class="adm-kpi-icon" style="background:#dcfce7">🏠</div>
            <div>
                <div class="adm-kpi-num"><?= number_format($totalHouses) ?></div>
                <div class="adm-kpi-label">Total Listings</div>
                <div class="adm-kpi-sub">
                    <?= (int)($statusCounts['approved'] ?? 0) ?> approved ·
                    <?= $pendingCount ?> pending ·
                    <?= (int)($statusCounts['rejected'] ?? 0) ?> rejected
                </div>
            </div>
        </div>
        <div class="adm-kpi">
            <div class="adm-kpi-icon" style="background:#fef3c7">⭐</div>
            <div>
                <div class="adm-kpi-num"><?= number_format($totalReviews) ?></div>
                <div class="adm-kpi-label">Total Reviews</div>
                <div class="adm-kpi-sub">Submitted by students</div>
            </div>
        </div>
    </div>

    <!-- ===================== MODERATION TABLE ===================== -->
    <h2 class="adm-section-title">
        📋 Manage Boarding Listings
        <?php if ($pendingCount > 0): ?>
            <span class="adm-pill adm-pill-warn">⏳ <?= $pendingCount ?> waiting for approval</span>
        <?php else: ?>
            <span class="adm-pill adm-pill-ok">✓ All caught up</span>
        <?php endif; ?>
    </h2>

    <div class="adm-card">
        <?php if (count($houses) === 0): ?>
            <div class="adm-empty"><span>📭</span>No boarding houses have been submitted yet.</div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Boarding House</th>
                            <th>Owner</th>
                            <th>Rent (Rs.)</th>
                            <th>Gender</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($houses as $h): ?>
                            <?php
                            $id     = (int)$h['house_id'];
                            $status = (string)$h['status'];

                            $g = strtolower((string)$h['gender_type']);
                            if (strpos($g, 'female') !== false || strpos($g, 'girl') !== false) {
                                $genderClass = 'badge-female';
                            } elseif (strpos($g, 'male') !== false || strpos($g, 'boy') !== false) {
                                $genderClass = 'badge-male';
                            } else {
                                $genderClass = 'badge-mixed';
                            }
                            ?>
                            <tr class="<?= $status === 'pending' ? 'adm-row-pending' : '' ?>">
                                <td class="adm-muted"><?= $id ?></td>
                                <td>
                                    <a class="adm-title" href="view_house.html?id=<?= $id ?>"><?= adm_e($h['title']) ?></a>
                                    <div class="adm-muted">📍 <?= adm_e($h['address']) ?></div>
                                </td>
                                <td>
                                    <div style="font-weight:700"><?= adm_e($h['owner_name']) ?></div>
                                    <div class="adm-muted"><?= adm_e($h['owner_email']) ?></div>
                                </td>
                                <td style="font-weight:700;white-space:nowrap"><?= number_format((float)$h['monthly_rent'], 2) ?></td>
                                <td><span class="adm-badge <?= $genderClass ?>"><?= adm_e(ucfirst((string)$h['gender_type'])) ?></span></td>
                                <td style="white-space:nowrap">
                                    ⭐ <strong><?= number_format((float)$h['avg_rating'], 1) ?></strong>
                                    <span class="adm-muted">(<?= (int)$h['total_reviews'] ?>)</span>
                                </td>
                                <td><span class="adm-badge badge-<?= adm_e($status) ?>"><?= adm_e(ucfirst($status)) ?></span></td>
                                <td>
                                    <div class="adm-actions">
                                        <a class="adm-btn adm-btn-view" href="view_house.html?id=<?= $id ?>">👁 View</a>

                                        <?php if ($status !== 'approved'): ?>
                                            <form action="../backend/admin_process.php" method="POST">
                                                <input type="hidden" name="house_id" value="<?= $id ?>">
                                                <input type="hidden" name="action" value="approved">
                                                <button type="submit" class="adm-btn adm-btn-approve">✓ Approve</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($status !== 'rejected'): ?>
                                            <form action="../backend/admin_process.php" method="POST">
                                                <input type="hidden" name="house_id" value="<?= $id ?>">
                                                <input type="hidden" name="action" value="rejected">
                                                <button type="submit" class="adm-btn adm-btn-reject">✕ Reject</button>
                                            </form>
                                        <?php endif; ?>

                                        <form action="../backend/admin_process.php" method="POST"
                                              onsubmit="return confirm('Permanently delete this listing? Its photos and reviews will also be deleted.');">
                                            <input type="hidden" name="house_id" value="<?= $id ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="adm-btn adm-btn-delete">🗑 Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>