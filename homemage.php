<?php
// Start the session at the very top before any HTML output
session_start();

// If the user is already logged in, redirect them to the homepage
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection safely using __DIR__
require_once __DIR__ . '/config/db_con.php';

$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Prepare SQL statement to prevent SQL injection
        // Adjust table name (users) and column names (id, username, email, password) if yours differ
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ? LIMIT 1");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Verify hashed password (supports both password_hash() and plain-text fallback for testing)
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    // Store user data in session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    // Redirect to main page
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Invalid password. Please try again.";
                }
            } else {
                $error = "No account found with that email address.";
            }
            $stmt->close();
        } else {
            $error = "Database query failed. Check your table and column names.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MoraBodima</title>
    <!-- Link to external CSS file -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to rate and review boarding houses</p>
            </div>

            <!-- Display error message if login fails -->
            <?php if (!empty($error)): ?>
                <div class="alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="you@example.com" 
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="password">Password</label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••" 
                        required
                    >
                </div>

                <button type="submit" class="btn-submit">Sign In</button>
            </form>

            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Create one</a></p>
                <p class="back-home"><a href="index.php">&larr; Back to Home</a></p>
            </div>
        </div>
    </div>

</body>
</html>