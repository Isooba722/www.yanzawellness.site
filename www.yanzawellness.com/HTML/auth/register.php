<?php
/**
 * ==========================================================================
 * YANZA WELLNESS USER REGISTRATION
 * Validates entries, enforces 8-character passwords, hashes passwords.
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
$success = '';

// Check if the user has clicked "Sign Up" (submitted the form via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // 1. Validation checks
    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'All registration fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid, working email address.';
    } elseif (strlen($password) < 8) { // ENFORCING MINIMUM 8 CHARACTERS (as requested)
        $error = 'For security, your password must be at least 8 characters long.';
    } elseif ($password !== $password_confirm) {
        $error = 'Confirm password does not match. Please retype.';
    } else {
        try {
            $db = getDBConnection();
            
            // 2. Check if username or email already exists in the database
            $stmt = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $username, 'email' => $email]);
            
            if ($stmt->fetch()) {
                $error = 'This username or email is already registered.';
            } else {
                // 3. Generate a secure, unique UUID (instead of number ID)
                $uuid = generateUUIDv4();
                
                // 4. Hash the password using BCRYPT algorithm (never store plain passwords!)
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                
                // 5. Insert the user details into the database
                $insert_stmt = $db->prepare("INSERT INTO users (id, username, email, password_hash) VALUES (:id, :username, :email, :password_hash)");
                $insert_stmt->execute([
                    'id' => $uuid,
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => $password_hash
                ]);
                
                $success = 'Account created successfully! You can now log in.';
            }
        } catch (PDOException $e) {
            error_log("Database Registration Error: " . $e->getMessage());
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
    <title>Sign Up | Yanza Wellness</title>
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

    <!-- Registration Panel -->
    <div class="auth-box">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p style="color: var(--color-text-muted);">Join our secure peer-support circles</p>
        </div>

        <!-- Render errors if there are any validation failures -->
        <?php if (!empty($error)): ?>
            <div class="alert-banner alert-banner-danger">
                <span><?php echo sanitize($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Render success alert when account is created -->
        <?php if (!empty($success)): ?>
            <div class="alert-banner alert-banner-success">
                <span><?php echo sanitize($success); ?></span>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Choose a Username</label>
                <!-- We persist user inputs (except passwords) inside value="..." if registration fails -->
                <input type="text" id="username" name="username" class="input-control" required value="<?php echo isset($username) && empty($success) ? sanitize($username) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="input-control" required value="<?php echo isset($email) && empty($success) ? sanitize($email) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="input-control" required placeholder="Min 8 characters">
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="input-control" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Register Account</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php" style="font-weight: 600;">Log In</a>
        </div>
    </div>
</div>

</body>
</html>
