<?php
/**
 * ==========================================================================
 * YANZA WELLNESS ADMIN - FEED MODERATION
 * Review, dismiss, or delete reported community stories.
 * ==========================================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

// Access Control: Block anyone who is not an administrator
if (!isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

$page_theme = 'directory'; // Uses directory theme
$page_title = 'Moderate Stories';

require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$feedback = '';
$feedback_type = '';

// 1. Process Actions via GET requests (Dismiss or Delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // ACTION A: DISMISS FLAG (UNFLAG)
    if ($_GET['action'] === 'dismiss_flag') {
        try {
            $stmt = $db->prepare("UPDATE community_feed SET is_flagged = 0 WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $feedback = 'Flag dismissed. The post is restored to the public circles feed.';
            $feedback_type = 'success';
        } catch (PDOException $e) {
            error_log("Admin Post Dismiss Fail: " . $e->getMessage());
            $feedback = 'Failed to dismiss flag.';
            $feedback_type = 'danger';
        }
    }
    
    // ACTION B: DELETE POST
    if ($_GET['action'] === 'delete_post') {
        try {
            $stmt = $db->prepare("DELETE FROM community_feed WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $feedback = 'Post permanently deleted from the database.';
            $feedback_type = 'success';
        } catch (PDOException $e) {
            error_log("Admin Post Delete Fail: " . $e->getMessage());
            $feedback = 'Failed to delete post.';
            $feedback_type = 'danger';
        }
    }
}

// 2. Fetch all flagged posts
try {
    $stmt = $db->query("SELECT * FROM community_feed WHERE is_flagged = 1 ORDER BY created_at DESC");
    $flagged_posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $flagged_posts = [];
}
?>

<div class="directory-layout" style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem;">
    <!-- Back Navigation -->
    <div style="margin-bottom: 2rem;">
        <a href="dashboard.php" style="color: var(--color-sea-pine); font-weight: 600; text-decoration: none;">&larr; Back to Admin Dashboard</a>
    </div>

    <!-- Title Block -->
    <div class="directory-title-block" style="text-align: left; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; color: var(--color-sea-pine);">Feed Moderation Queue</h1>
        <p style="color: var(--color-text-muted); font-size: 1.1rem; margin-top: 0.5rem;">
            Review stories that have been reported by users. Restores posts by dismissing flags or permanently purges violating content.
        </p>

        <!-- Feedback alerts -->
        <?php if (!empty($feedback)): ?>
            <div class="alert-banner alert-banner-<?php echo $feedback_type; ?>" style="margin-top: 1.5rem; text-align: left;">
                <span><?php echo sanitize($feedback); ?></span>
                <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Moderation List Grid -->
    <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft);">
        <h2 style="font-size: 1.5rem; color: var(--color-sea-pine); margin-bottom: 2rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Pending Flagged Stories</h2>
        
        <?php if (empty($flagged_posts)): ?>
            <div style="text-align: center; padding: 4rem 1rem;">
                <span style="font-size: 3rem;">🎉</span>
                <p style="color: var(--color-text-muted); font-size: 1.1rem; margin-top: 1rem;">The moderation queue is completely empty. Great job!</p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 2rem;">
                <?php foreach ($flagged_posts as $post): ?>
                    <div style="border: 1px solid var(--color-crisis-red); border-left: 6px solid var(--color-crisis-red); border-radius: 8px; padding: 1.5rem; background-color: var(--color-cream-light); display: flex; flex-direction: column; gap: 1rem;">
                        
                        <!-- Header metadata -->
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                            <span style="font-weight: 700; color: var(--color-teal-dark);">👥 Author Alias: <?php echo sanitize($post['username_alias']); ?></span>
                            <span style="font-size: 0.85rem; color: var(--color-text-muted);">Reported &bull; <?php echo date('H:i, M d, Y', strtotime($post['created_at'])); ?></span>
                        </div>

                        <!-- Trigger warnings or titles -->
                        <div>
                            <?php if (!empty($post['trigger_warning'])): ?>
                                <span class="tag tw-tag" style="background-color: rgba(217, 56, 58, 0.1); color: var(--color-crisis-red); border-color: rgba(217, 56, 58, 0.2); margin-bottom: 0.5rem; display: inline-block;">
                                    ⚠️ <?php echo sanitize($post['trigger_warning']); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($post['title'])): ?>
                                <h3 style="color: var(--color-teal-dark); font-size: 1.25rem; margin-bottom: 0.5rem;"><?php echo sanitize($post['title']); ?></h3>
                            <?php endif; ?>

                            <p style="line-height: 1.6; color: var(--color-text-dark); background-color: white; padding: 1rem; border-radius: 6px; border: 1px solid var(--border-color);">
                                <?php echo nl2br(sanitize($post['content'])); ?>
                            </p>
                        </div>

                        <!-- Bottom Controls -->
                        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 0.5rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                            <a href="manage_posts.php?action=dismiss_flag&id=<?php echo urlencode($post['id']); ?>" 
                               class="btn btn-secondary" 
                               style="padding: 0.5rem 1.2rem; font-size: 0.85rem; border-color: var(--color-sea-pine); color: var(--color-sea-pine); background-color: white;"
                               onclick="return confirm('Do you want to dismiss the flag and keep this story on the public timeline?');">
                                ✅ Dismiss Report (Keep Post)
                            </a>
                            
                            <a href="manage_posts.php?action=delete_post&id=<?php echo urlencode($post['id']); ?>" 
                               class="btn btn-primary" 
                               style="padding: 0.5rem 1.2rem; font-size: 0.85rem; background-color: var(--color-crisis-red); border-color: var(--color-crisis-red);"
                               onclick="return confirm('Are you sure you want to permanently delete this story from the database?');">
                                🗑️ Delete Story (Purge)
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
