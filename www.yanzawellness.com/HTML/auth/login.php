<?php
/**
 * ==========================================================================
 * YANZA WELLNESS USER LOGIN
 * Validates logins, queries databases securely, creates safe PHP sessions.
 * ==========================================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect them to the home page immediately
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Load database connection settings and helper utilities
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

$error = '';

// Check if the user has clicked "Log In" (submitted the form via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 1. Validation checks
    if (empty($login_input) || empty($password)) {
        $error = 'Please fill in both fields.';
    } else {
        try {
            $db = getDBConnection();
            
            // 2. Query user by Username OR Email using Prepared Statements (blocking SQL injection)
            $stmt = $db->prepare("SELECT id, username, password_hash, role FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $login_input, 'email' => $login_input]);
            $user = $stmt->fetch();
            
            // 3. Match the user and mathematically verify the BCRYPT password hash
            if ($user && password_verify($password, $user['password_hash'])) {
                
                // 4. SECURITY BEST PRACTICE: Regenerate session ID on login.
                // This replaces the session identifier in the user cookie, blocking Session Fixation attacks.
                session_regenerate_id(true);
                
                // 5. Store authentication tags inside the server session array
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // 6. REDIRECT: Go back to landing page (Option A, as requested)
                header('Location: ../index.php');
                exit;
            } else {
                $error = 'Invalid credentials. Please verify details and try again.';
            }
        } catch (PDOException $e) {
            error_log("Database Login Error: " . $e->getMessage());
            $error = 'A database communication failure occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In | Yanza Wellness</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="background-color: var(--color-warm-white);">

<div class="auth-layout">
    <!-- Home Navigation Logo Link -->
    <div style="text-align: center; margin-bottom: 2rem;">
        <a href="../index.php" style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 1.5rem; font-family: var(--font-heading); font-weight: 700; color: var(--color-slate-teal);">
            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 32px; height: 32px;">
                <path d="M50 85C50 85 85 62 85 38C85 22 72 10 56 10C48 10 42 14 38 20C34 14 28 10 20 10C4 10 -9 22 -9 38C-9 62 26 85 26 85" stroke="#2A5953" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M50 85C50 85 15 62 15 38C15 22 28 10 44 10C52 10 58 14 62 20C66 14 72 10 80 10C96 10 109 22 109 38C109 62 74 85 74 85" stroke="#6A9085" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" opacity="0.8"/>
            </svg>
            Yanza Wellness
        </a>
    </div>

    <!-- Login Panel -->
    <div class="auth-box">
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p style="color: var(--color-text-muted);">Log in to access your dashboard</p>
        </div>

        <!-- Render error logs if validation or matching failed -->
        <?php if (!empty($error)): ?>
            <div class="alert-banner alert-banner-danger">
                <span><?php echo sanitize($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="login_input">Username or Email</label>
                <input type="text" id="login_input" name="login_input" class="input-control" required value="<?php echo isset($login_input) ? sanitize($login_input) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="input-control" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Log In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php" style="font-weight: 600;">Sign Up</a>
        </div>
    </div>
</div>

</body>
</html>
