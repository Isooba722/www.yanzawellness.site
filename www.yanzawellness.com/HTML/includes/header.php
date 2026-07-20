<?php
/**
 * ==========================================================================
 * YANZA WELLNESS HEADER TEMPLATE
 * Starts sessions, loads security, and handles navigation & crisis modals.
 * ==========================================================================
 */

// Start the session so we can track if a user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include our database connection and security helpers
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

// Detect the current page file name (e.g. index.php) to highlight the active menu link
$current_page = basename($_SERVER['PHP_SELF']);

// Detect if we are inside the /admin/ directory
$is_admin_dir = (basename(dirname($_SERVER['PHP_SELF'])) === 'admin');
$path_prefix = $is_admin_dir ? '../' : '';
?>
<!DOCTYPE html>
<!-- The page_theme variable changes styling based on the active template -->
<html lang="en" <?php echo isset($page_theme) ? "data-theme=\"$page_theme\"" : ""; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Dynamic Title for SEO optimization -->
    <title><?php echo isset($page_title) ? sanitize($page_title) . " | Yanza Wellness" : "Yanza Wellness - Embracing Support"; ?></title>
    
    <!-- Dynamic Meta Descriptions for Search Engines -->
    <meta name="description" content="<?php echo isset($page_desc) ? sanitize($page_desc) : "Yanza Wellness is a secure, culturally integrated peer support and mental health resource directory for Uganda."; ?>">
    <meta name="keywords" content="mental health Uganda, Yanza, counseling, journal, peer support, Kampala">
    
    <!-- Link to the central stylesheet we created in Step 3 -->
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>css/style.css">
</head>
<body>

<header>
    <div class="header-container">
        <!-- Logo Section with dynamically coded interlocking vector hearts -->
        <div class="logo-section">
            <a href="<?php echo $path_prefix; ?>index.php">
                <svg class="logo-icon" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M50 85C50 85 85 62 85 38C85 22 72 10 56 10C48 10 42 14 38 20C34 14 28 10 20 10C4 10 -9 22 -9 38C-9 62 26 85 26 85" stroke="#2A5953" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M50 85C50 85 15 62 15 38C15 22 28 10 44 10C52 10 58 14 62 20C66 14 72 10 80 10C96 10 109 22 109 38C109 62 74 85 74 85" stroke="#6A9085" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" opacity="0.8"/>
                </svg>
                <span>Yanza Wellness</span>
            </a>
        </div>
        
        <!-- Navigation Menu -->
        <nav>
            <ul class="nav-links">
                <!-- If the page name matches index.php, we add class="active" to highlight it -->
                <li><a href="<?php echo $path_prefix; ?>index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="<?php echo $path_prefix; ?>connect.php" class="<?php echo $current_page == 'connect.php' ? 'active' : ''; ?>">Circles & Hub</a></li>
                <li><a href="<?php echo $path_prefix; ?>directory.php" class="<?php echo $current_page == 'directory.php' ? 'active' : ''; ?>">Counselors</a></li>
                
                <!-- Display 'My Journal' and 'Logout' links ONLY if the user is authenticated -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo $path_prefix; ?>journal.php" class="<?php echo $current_page == 'journal.php' ? 'active' : ''; ?>">My Journal</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo $path_prefix; ?>admin/dashboard.php" class="<?php echo $is_admin_dir ? 'active' : ''; ?>">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $path_prefix; ?>auth/logout.php" style="color: var(--color-text-muted);">Logout (<?php echo sanitize($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                    <!-- If not logged in, display the standard Login button -->
                    <li><a href="<?php echo $path_prefix; ?>auth/login.php" class="<?php echo $current_page == 'login.php' ? 'active' : ''; ?>">Login</a></li>
                <?php endif; ?>
            </ul>
            
            <!-- Pinned Emergency Red Button -->
            <!-- Directs the browser URL to '#crisis-modal' to show our overlay -->
            <a href="#crisis-modal" class="crisis-btn">Crisis Help Now</a>
        </nav>
    </div>
</header>

<!-- 
    CSS & Javascript Triggered Crisis Support Modal Window.
    Stays hidden by default until the URL hash matches '#crisis-modal'.
-->
<div id="crisis-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
    <div class="modal-content" style="background: white; padding: 2.5rem; border-radius: 16px; max-width: 550px; width: 90%; position: relative; border-top: 8px solid var(--color-crisis-red); box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
        
        <h2 style="color: var(--color-crisis-red); font-size: 2rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            🚨 Emergency Crisis Help
        </h2>
        <p style="margin-bottom: 1.5rem; font-size: 1rem; color: var(--color-text-dark);">
            If you are in distress, having thoughts of self-harm, or facing an immediate mental health emergency, please reach out to these crisis hotlines immediately:
        </p>
        
        <!-- Local Ugandan Emergency Centers -->
        <div style="background: var(--color-warm-white); padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <p style="margin-bottom: 0.75rem;"><strong>🏥 Butabika National Referral Hospital (Kampala)</strong></p>
            <p style="font-size: 1.15rem; color: var(--color-crisis-red); font-weight: 700; margin-bottom: 0.75rem;">
                📞 +256 (0) 414 504 388 / +256 (0) 414 504 380
            </p>
            <p style="font-size: 0.85rem; color: var(--color-text-muted);">Available 24/7 for emergency psychiatric support, consultations, and crisis care.</p>
        </div>
        
        <!-- Global Helpline Integration -->
        <div style="background: var(--color-warm-white); padding: 1.25rem; border-radius: 8px; margin-bottom: 2rem;">
            <p style="margin-bottom: 0.5rem;"><strong>🌍 Outside Uganda?</strong></p>
            <p>You can locate free, confidential support in your local region via the global hotline registry: <a href="https://findahelpline.com" target="_blank" rel="noopener noreferrer" style="text-decoration: underline; font-weight: 600; color: var(--color-slate-teal);">Find A Helpline</a>.</p>
        </div>
        
        <!-- Close Controls -->
        <div style="display: flex; justify-content: flex-end;">
            <button onclick="closeCrisisModal()" class="btn btn-secondary" style="padding: 0.5rem 1.5rem;">Close Info</button>
        </div>
    </div>
</div>

<script>
/**
 * Listens to URL changes. If the user clicks the "Crisis Help Now" link,
 * the page hash changes to '#crisis-modal'. We show the overlay.
 */
function checkCrisisAnchor() {
    var modal = document.getElementById('crisis-modal');
    if (window.location.hash === '#crisis-modal') {
        modal.style.display = 'flex';
    } else {
        modal.style.display = 'none';
    }
}

// Triggers modal checks when the page loads, and when the hash in the URL changes
window.addEventListener('hashchange', checkCrisisAnchor);
window.addEventListener('load', checkCrisisAnchor);

// Closes the modal window by clearing the page anchor hash
function closeCrisisModal() {
    window.location.hash = '';
}
</script>
