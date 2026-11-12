<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'student';

    if (!in_array($role, ['student', 'owner', 'admin'], true)) {
        $role = 'student';
    }

    if ($full_name === '' || $email === '' || strlen($password) < 4) {
        $error = 'Please fill all fields (password must be at least 4 characters).';
    } else {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $hash, $role]);
            $success = 'Account created! You can now log in.';
        }
    }
}
?>

<div class="form-box">
    <h2 style="margin-bottom: 16px;">Create an Account</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?> <a href="login.php"><strong>Login here</strong></a>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required placeholder="e.g., Kasun Perera">
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="you@university.lk">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Minimum 4 characters">
        </div>
        <div class="form-group">
            <label>Account Type</label>
            <select name="role">
                <option value="student">Student / Tenant (Write Reviews)</option>
                <option value="owner">Boarding Owner (List Properties)</option>
                <option value="admin">Admin (Moderation & Approvals)</option>
            </select>
        </div>
        <button type="submit" class="btn" style="width:100%;">Register</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>