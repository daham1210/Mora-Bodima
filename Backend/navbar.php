<?php
require_once __DIR__ . '/config/db_con.php';
?>
<a href="index.html">Home & Search</a>

<?php if (isset($_SESSION['user_id'])): ?>
    
    <?php if ($_SESSION['role'] == 'owner' || $_SESSION['role'] == 'admin'): ?>
        <a href="add_house.html">+ Add Boarding</a>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == 'admin'): ?>
        <a href="admin.html">Admin Panel</a>
    <?php endif; ?>

    <span class="user-pill">
        👤 <?= htmlspecialchars($_SESSION['full_name']) ?> (<?= ucfirst($_SESSION['role']) ?>)
    </span>
    <a href="../backend/login.php?logout=1" class="btn-logout">Logout</a>
<?php else: ?>
    <a href="login.html" class="btn-nav">Login / Register</a>
<?php endif; ?>