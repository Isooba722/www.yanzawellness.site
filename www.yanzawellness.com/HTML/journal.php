<?php
/**
 * ==========================================================================
 * YANZA WELLNESS MOOD JOURNAL & REFLECTION SPACE
 * Access-controlled. Encrypts logs using AES-256-GCM before DB saving.
 * Decrypts entries on-the-fly and hides them behind "Click to Reveal" triggers.
 * Includes cascading database hard-delete accounts triggers.
 * ==========================================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect guests back to login (Access Control)
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';

$db = getDBConnection();
$feedback = '';
$feedback_type = '';

// Check if a contextual article reflection prompt has been passed via URL query
$prompt_article = trim($_GET['article'] ?? '');
$default_prompt = '';
if (!empty($prompt_article)) {
    $default_prompt = "Reflections on article '{$prompt_article}': ";
}

/**
 * A. PROCESS ACCOUNT DELETION (Cascading Hard-Delete)
 * Permanently wipes user and all related records from DB using ON DELETE CASCADE.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    $confirm_username = trim($_POST['confirm_username'] ?? '');
    
    if ($confirm_username !== $_SESSION['username']) {
        $feedback = 'Confirmation username does not match. Account deletion cancelled.';
        $feedback_type = 'danger';
    } else {
        try {
            $user_id = $_SESSION['user_id'];
            
            // This statement deletes the user.
            // Database foreign keys trigger cascade deletions, erasing journals and feed posts linked to this user.
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            
            // Destroy session and redirect to home
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            
            // Redirect to home with notice
            header('Location: index.php?info=purged');
            exit;
            
        } catch (PDOException $e) {
            error_log("Database Account Wiping Error: " . $e->getMessage());
            $feedback = 'A database communication failure occurred. Please try again.';
            $feedback_type = 'danger';
        }
    }
}

/**
 * B. PROCESS NEW JOURNAL ENTRY
 * Encrypts data and inserts into database.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_journal') {
    $mood = intval($_POST['mood'] ?? 3);
    $entry_text = trim($_POST['entry_text'] ?? '');
    $privacy_mode = $_POST['privacy_mode'] ?? 'private'; // 'private' or 'shared'
    
    if (empty($entry_text)) {
        $feedback = 'Journal entry cannot be empty.';
        $feedback_type = 'danger';
    } elseif ($mood < 1 || $mood > 5) {
        $feedback = 'Please select a valid mood rating.';
        $feedback_type = 'danger';
    } else {
        try {
            $user_id = $_SESSION['user_id'];
            $journal_id = generateUUIDv4();
            $is_private = ($privacy_mode === 'private') ? 1 : 0;
            
            // Encrypt using AES-256-GCM with global key from config/passwords.php
            $encrypted_text = encryptAES256GCM($entry_text, AES_KEY);
            
            if ($encrypted_text === false) {
                throw new Exception("AES Cryptography Encryption Failure");
            }
            
            // Save encrypted log to journals table
            $stmt = $db->prepare("INSERT INTO journals (id, user_id, mood, entry_text, is_private) VALUES (:id, :user_id, :mood, :entry_text, :is_private)");
            $stmt->execute([
                'id' => $journal_id,
                'user_id' => $user_id,
                'mood' => $mood,
                'entry_text' => $encrypted_text,
                'is_private' => $is_private
            ]);
            
            // If shared, write plain copy to feed linked privately to their user ID (to support purge)
            if ($is_private === 0) {
                $feed_id = generateUUIDv4();
                $feed_stmt = $db->prepare("INSERT INTO community_feed (id, user_id, username_alias, title, content, trigger_warning) VALUES (:id, :user_id, :alias, :title, :content, :tw)");
                $feed_stmt->execute([
                    'id' => $feed_id,
                    'user_id' => $user_id,
                    'alias' => 'Anonymous Circle Member',
                    'title' => 'An anonymous journal reflection',
                    'content' => $entry_text, // Plain text for public circles reading
                    'tw' => 'Trigger Warning: Journal Reflection'
                ]);
            }
            
            $feedback = 'Your journal entry has been safely encrypted and saved' . ($is_private === 0 ? ' and shared anonymously.' : '.');
            $feedback_type = 'success';
            $default_prompt = ''; // Reset prompt text
            
        } catch (Exception $e) {
            error_log("Journal Cryptography Error: " . $e->getMessage());
            $feedback = 'Unable to encrypt and write journal entry. Please try again.';
            $feedback_type = 'danger';
        }
    }
}

/**
 * C. FETCH LOGGED-IN USER'S TIMELINE ENTRIES
 * Decrypts entries on-the-fly.
 */
try {
    $stmt = $db->prepare("SELECT * FROM journals WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $journal_rows = $stmt->fetchAll();
    
    // Decrypt cipher blocks on-the-fly
    $timeline = [];
    foreach ($journal_rows as $row) {
        $decrypted_content = decryptAES256GCM($row['entry_text'], AES_KEY);
        if ($decrypted_content !== false) {
            $row['decrypted_text'] = $decrypted_content;
        } else {
            $row['decrypted_text'] = '[ERROR: Decryption failed. Invalid encryption key or corrupt record]';
        }
        $timeline[] = $row;
    }
} catch (PDOException $e) {
    error_log("Timeline database query failure: " . $e->getMessage());
    $timeline = [];
}

// Map page variables
$page_theme = 'landing'; // Default warm tones
$page_title = 'My Journal Space';
require_once __DIR__ . '/includes/header.php';
?>

<div class="journal-layout">
    
    <!-- Title block -->
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2.5rem; text-align: center; color: var(--color-clay-dark);">The Reflection Space</h1>
        <p style="text-align: center; color: var(--color-text-muted);">
            A secure diary and mood tracker. All logs are encrypted using AES-256-GCM before writing to the database.
        </p>
        
        <!-- Feedback Alert banners -->
        <?php if (!empty($feedback)): ?>
            <div class="alert-banner alert-banner-<?php echo $feedback_type; ?>" style="margin-top: 1.5rem;">
                <span><?php echo sanitize($feedback); ?></span>
                <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Journal Form panel -->
    <div class="journal-box">
        <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; color: var(--color-clay-dark);">
            Write Entry
        </h3>
        
        <form action="journal.php" method="POST">
            <input type="hidden" name="action" value="save_journal">
            
            <!-- Mood Selector (Radio boxes styled as Emojis, as requested) -->
            <div class="form-group">
                <label style="text-align: center; display: block; margin-bottom: 1rem; font-weight: 600;">How is your spirit today?</label>
                <div class="mood-selector">
                    <label class="mood-option">
                        <input type="radio" name="mood" value="1">
                        <span class="mood-icon">😢</span>
                        <span class="mood-label">Low</span>
                    </label>
                    <label class="mood-option">
                        <input type="radio" name="mood" value="2">
                        <span class="mood-icon">😟</span>
                        <span class="mood-label">Struggling</span>
                    </label>
                    <label class="mood-option">
                        <input type="radio" name="mood" value="3" checked>
                        <span class="mood-icon">😐</span>
                        <span class="mood-label">Neutral</span>
                    </label>
                    <label class="mood-option">
                        <input type="radio" name="mood" value="4">
                        <span class="mood-icon">🙂</span>
                        <span class="mood-label">Good</span>
                    </label>
                    <label class="mood-option">
                        <input type="radio" name="mood" value="5">
                        <span class="mood-icon">☀️</span>
                        <span class="mood-label">Excellent</span>
                    </label>
                </div>
            </div>

            <!-- Pre-filled Article reflection hook notice -->
            <?php if (!empty($prompt_article)): ?>
                <div style="background-color: rgba(106, 144, 133, 0.1); border-left: 4px solid var(--color-sage-green); padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    💡 <strong>Smart Reflection Hook:</strong> You are responding to the article <em>"<?php echo sanitize($prompt_article); ?>"</em>. Write down your learnings or thoughts below.
                </div>
            <?php endif; ?>

            <!-- Journal diary input -->
            <div class="form-group">
                <label for="entry_text">Your Thoughts</label>
                <textarea id="entry_text" name="entry_text" class="input-control" rows="8" placeholder="Begin typing here..." required><?php echo sanitize($default_prompt); ?></textarea>
            </div>

            <!-- Dual-mode privacy selector toggle box -->
            <div class="form-group">
                <label style="margin-bottom: 0.5rem; display: block;">Privacy Setting</label>
                <div class="toggle-group">
                    <label class="toggle-option">
                        <input type="radio" name="privacy_mode" value="private" checked>
                        <span class="toggle-btn-label">🔒 Keep Completely Private</span>
                    </label>
                    <label class="toggle-option">
                        <input type="radio" name="privacy_mode" value="shared">
                        <span class="toggle-btn-label">👥 Share to Circle Anonymously</span>
                    </label>
                </div>
                <span style="font-size: 0.75rem; color: var(--color-text-muted); display: block; margin-top: -1.5rem; margin-bottom: 1.5rem;">
                    * Shared entries cross-post to the public feed under "Anonymous" but encrypts your local copy.
                </span>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; border-radius: 8px;">Encrypt & Save Entry</button>
        </form>
    </div>

    <!-- Private Timeline list -->
    <div class="timeline-section">
        <h3 style="font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--color-clay-dark);">My Timeline</h3>
        
        <?php if (empty($timeline)): ?>
            <div style="background-color: var(--bg-card); padding: 3rem; text-align: center; border-radius: var(--border-radius); border: 1px solid var(--border-color);">
                <p style="color: var(--color-text-muted);">No logs recorded. Save an entry above to start your timeline.</p>
            </div>
        <?php else: ?>
            <?php foreach ($timeline as $entry): ?>
                <div class="timeline-card">
                    <div class="timeline-meta">
                        <span class="timeline-mood">
                            <?php 
                            $moods = [1 => '😢 Low', 2 => '😟 Struggling', 3 => '😐 Neutral', 4 => '🙂 Good', 5 => '☀️ Excellent'];
                            echo isset($moods[$entry['mood']]) ? $moods[$entry['mood']] : '😐 Neutral';
                            ?>
                        </span>
                        
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <span class="timeline-privacy">
                                <?php echo ($entry['is_private'] === 1) ? '🔒 Private' : '👥 Shared'; ?>
                            </span>
                            <span><?php echo date('F d, Y - H:i', strtotime($entry['created_at'])); ?></span>
                        </div>
                    </div>

                    <!-- 
                        Hides the decrypted plain text inside a hidden div, 
                        implementing the Click to Reveal privacy feature (As requested)
                    -->
                    <div id="journal-text-<?php echo $entry['id']; ?>" style="display: none; white-space: pre-wrap; font-size: 1rem; line-height: 1.6; color: var(--color-text-dark); margin-top: 1rem; border-top: 1px dashed var(--border-color); padding-top: 1rem;">
                        <?php echo sanitize($entry['decrypted_text']); ?>
                    </div>
                    
                    <!-- Reveal/Hide toggle action link button -->
                    <button onclick="toggleReveal('<?php echo $entry['id']; ?>')" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem; margin-top: 1rem; border-radius: 6px;" id="reveal-btn-<?php echo $entry['id']; ?>">
                        👁️ Reveal Entry
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Security Account Purge Panel (Cascading Hard-Delete) -->
    <div style="margin-top: 5rem; padding: 2.5rem; background-color: rgba(217, 56, 58, 0.05); border: 2px solid rgba(217, 56, 58, 0.15); border-radius: var(--border-radius-lg);">
        <h3 style="color: var(--color-crisis-red); font-size: 1.5rem; margin-bottom: 0.5rem;">⚠️ Account Deletion & Permanent Purge</h3>
        <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 1.5rem;">
            Wipes your profile, encrypted timelines, and community circles logs. This cascading hard-delete cannot be undone.
        </p>
        
        <form action="journal.php" method="POST" onsubmit="return confirm('WARNING: Are you absolutely sure you want to permanently delete your account? This cannot be undone!')">
            <input type="hidden" name="action" value="delete_account">
            
            <div class="form-group" style="max-width: 350px;">
                <label for="confirm_username">Type your username (<strong><?php echo sanitize($_SESSION['username']); ?></strong>) to confirm:</label>
                <input type="text" id="confirm_username" name="confirm_username" class="input-control" required placeholder="Type username here...">
            </div>
            
            <button type="submit" class="btn crisis-btn" style="border-radius: 8px; font-size: 0.85rem; padding: 0.65rem 1.25rem;">Permanently Purge My Account</button>
        </form>
    </div>

</div>

<!-- Client-side script to toggle visibility of private journals -->
<script>
function toggleReveal(id) {
    var textDiv = document.getElementById('journal-text-' + id);
    var btn = document.getElementById('reveal-btn-' + id);
    if (textDiv.style.display === 'none') {
        textDiv.style.display = 'block';
        btn.innerHTML = '🙈 Hide Entry';
    } else {
        textDiv.style.display = 'none';
        btn.innerHTML = '👁️ Reveal Entry';
    }
}
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
