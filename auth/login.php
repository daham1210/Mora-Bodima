<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && (password_verify($password, $user['password_hash']) || $password === $user['password_hash'])) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];

        header("Location: $base_url/index.php");
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>

<div class="form-box">
    <h2 style="margin-bottom: 16px;">Login to MoraBodima</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required placeholder="student@uni.lk">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Enter password">
        </div>
        <button type="submit" class="btn" style="width:100%;">Login</button>
    </form>

    <div class="meta-text" style="margin-top: 16px; background:#f8fafc; padding:10px; border-radius:6px;">
        <strong>Sample Accounts from SQL:</strong><br>
        Student: <code>student@uni.lk</code> | Pass: <code>hashed_pw_2</code><br>
        Owner: <code>owner@boarding.lk</code> | Pass: <code>hashed_pw_1</code>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>