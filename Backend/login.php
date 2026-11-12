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
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // 1. Use MySQLi syntax (bind_param -> execute -> get_result -> fetch_assoc)
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        // 2. Match exact column names from your SQL table: 'User', 'name', 'pass', 'role'
        if ($user && (password_verify($password, $user['pass']) || $password === $user['pass'])) {
            $_SESSION['user_id']   = $user['user_id']; // Capital 'U' to match your table!
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
        $hashed    = $_POST['password'];

        $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            header("Location: ../frontend/register.html?err=Email+already+registered");
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO users (name, email, pass, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $hashed, $role]);

        header("Location: ../frontend/login.html?msg=Account+created!+Please+sign+in.");
        exit;
    }
}
?>