<?php
require_once __DIR__ . '/config/db_con.php';

// 1. Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../frontend/index.html?msg=Logged+out+successfully");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 2. Handle Login
    if ($action == 'login') {
        $email    = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE user = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['pass']) || $password == $user['pass'])) {
            $_SESSION['user_id']   = $user['user'];
            $_SESSION['full_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];

            header("Location: ../frontend/index.html?msg=Welcome+back!");
            exit;
        } else {
            header("Location: ../frontend/login.html?err=Invalid+email+or+password");
            exit;
        }
    }

    // 3. Handle Registration
    if ($action == 'register') {
        $full_name = trim($_POST['full_name']);
        $email     = trim($_POST['email']);
        $role      = ($_POST['role'] == 'owner') ? 'owner' : 'student';
        $hashed    = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            header("Location: ../frontend/register.html?err=Email+already+registered");
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $hashed, $role]);

        header("Location: ../frontend/login.html?msg=Account+created!+Please+sign+in.");
        exit;
    }
}
?>