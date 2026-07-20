<?php
/**
 * ==========================================================================
 * YANZA WELLNESS PEER CIRCLES FEED & EDUCATIONAL LIBRARY
 * Features Slate Teal/Cream Theme. Handles search filters, dynamic overlays,
 * and anonymous story sharing with Trigger Warning flags.
 * ==========================================================================
 */

// 1. Configure page variables for the header
$page_theme = 'connect'; // Swaps CSS colors to Slate Teal base & Cream Light background
$page_title = 'Circles & Resources';
$page_desc = 'Read self-guided wellness toolkits and share personal stories anonymously in our community circle feed.';

require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();
$feedback = '';
$feedback_type = '';

/**
 * A. PROCESS NEW STORY POSTS
 * Inserts user posts into the community feed securely.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'publish_post') {
    $alias = trim($_POST['alias'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $tw = trim($_POST['trigger_warning'] ?? '');
    
    // Default to Anonymous if user did not choose a custom pseudonym
    if (empty($alias)) {
        $alias = 'Anonymous';
    }
    
    if (empty($content)) {
        $feedback = 'Your story content cannot be empty.';
        $feedback_type = 'danger';
    } else {
        try {
            // Check if logged in, to privately link the user ID.
            // This satisfies the "ON DELETE CASCADE" rule (erasing posts if the user account is deleted).
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $uuid = generateUUIDv4();
            
            // Prepared statement to safely insert the post
            $stmt = $db->prepare("INSERT INTO community_feed (id, user_id, username_alias, title, content, trigger_warning) VALUES (:id, :user_id, :alias, :title, :content, :tw)");
            $stmt->execute([
                'id' => $uuid,
                'user_id' => $user_id,
                'alias' => $alias,
                'title' => empty($title) ? null : $title,
                'content' => $content,
                'tw' => $tw === 'None' ? null : $tw
            ]);
            
            $feedback = 'Your story has been shared to the circle anonymously.';
            $feedback_type = 'success';
        } catch (PDOException $e) {
            error_log("Database Feed Insert Error: " . $e->getMessage());
            $feedback = 'Unable to share post at this time. Please try again.';
            $feedback_type = 'danger';
        }
    }
}

/**
 * B. PROCESS REPORTED POSTS
 * Sets is_flagged = 1 in the database so admins can review the post.
 */
if (isset($_GET['flag'])) {
    $flag_id = $_GET['flag'];
    try {
        $stmt = $db->prepare("UPDATE community_feed SET is_flagged = 1 WHERE id = :id");
        $stmt->execute(['id' => $flag_id]);
        $feedback = 'Thank you for reporting. The post has been flagged and queued for human moderator review.';
        $feedback_type = 'success';
    } catch (PDOException $e) {
        error_log("Database Post Flagging Error: " . $e->getMessage());
        $feedback = 'Unable to flag post at this time. Please try again.';
        $feedback_type = 'danger';
    }
}

/**
 * C. SEARCH & FILTER LIBRARY ARTICLES
 */
$search_query = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');

try {
    // Start building SQL query dynamically based on search variables
    $resource_sql = "SELECT * FROM resources WHERE 1=1";
    $resource_params = [];
    
    // Add text search conditions (PDO Prepared parameters)
    if (!empty($search_query)) {
        $resource_sql .= " AND (title LIKE :search OR summary LIKE :search OR content LIKE :search)";
        $resource_params['search'] = '%' . $search_query . '%';
    }
    
    // Add category filter conditions
    if (!empty($category_filter) && $category_filter !== 'All') {
        $resource_sql .= " AND category = :category";
        $resource_params['category'] = $category_filter;
    }
    
    $resource_sql .= " ORDER BY created_at DESC";
    $resource_stmt = $db->prepare($resource_sql);
    $resource_stmt->execute($resource_params);
    $resources = $resource_stmt->fetchAll();
    
    // Fetch all active peer posts for the Story Feed column (hide flagged posts)
    $feed_stmt = $db->query("SELECT * FROM community_feed WHERE is_flagged = 0 ORDER BY created_at DESC");
    $feed_posts = $feed_stmt->fetchAll();
    
    // Query list of distinct categories for the filter select dropdown
    $cat_stmt = $db->query("SELECT DISTINCT category FROM resources");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    error_log("Database Feed/Resource Query Failure: " . $e->getMessage());
    $resources = [];
    $feed_posts = [];
    $categories = [];
}
?>

<div class="hub-layout">
    <!-- Hub Page Heading -->
    <div class="hub-title-block">
        <h1>Connect & Find Your Circle</h1>
        <p style="color: var(--color-text-muted); font-size: 1.15rem; max-width: 800px;">
            Yanza Wellness is built around peer support. Read from our library of curated resources, or share your thoughts in our peer circle. Everything here can be read and written anonymously.
        </p>
        
        <!-- Feedback Alert Banner (for success or errors) -->
        <?php if (!empty($feedback)): ?>
            <div class="alert-banner alert-banner-<?php echo $feedback_type; ?>" style="margin-top: 1.5rem;">
                <span><?php echo sanitize($feedback); ?></span>
                <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- MAIN COLUMN: Educational Library -->
    <div>
        <div class="section-header" id="resources">
            <h2>Educational Library</h2>
            <!-- Search & Filter Controls -->
            <form action="connect.php" method="GET" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <input type="text" name="search" placeholder="Search resources..." class="input-control" style="width: 200px; padding: 0.5rem 0.8rem;" value="<?php echo sanitize($search_query); ?>">
                
                <select name="category" class="input-control" style="width: 150px; padding: 0.5rem 0.8rem;" onchange="this.form.submit()">
                    <option value="All">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo sanitize($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                            <?php echo sanitize($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem; border-radius: 8px;">Filter</button>
            </form>
        </div>

        <?php if (empty($resources)): ?>
            <div style="background-color: var(--bg-card); padding: 3rem; text-align: center; border-radius: var(--border-radius); border: 1px solid var(--border-color);">
                <p style="color: var(--color-text-muted); font-size: 1.1rem;">No articles found matching your criteria. Try adjusting your filters.</p>
            </div>
        <?php else: ?>
            <div class="articles-grid">
                <?php foreach ($resources as $res): ?>
                    <article class="article-card">
                        <!-- Custom Gradient Card Header (Replacing Image 1 layout headers) -->
                        <div class="article-banner" style="background: linear-gradient(135deg, var(--color-slate-teal) 0%, var(--color-sage-green) 100%);"></div>
                        <div class="article-body">
                            <div class="tag-row">
                                <span class="tag"><?php echo sanitize($res['category']); ?></span>
                                <span class="tag" style="background-color: rgba(200, 122, 83, 0.1); color: var(--color-cta-terracotta);"><?php echo sanitize($res['read_time']); ?></span>
                            </div>
                            <h3><?php echo sanitize($res['title']); ?></h3>
                            <p><?php echo sanitize($res['summary']); ?></p>
                            
                            <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center;">
                                <a href="#article-modal-<?php echo $res['id']; ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-radius: 8px; border-color: var(--border-color);">Read Article</a>
                                
                                <!-- Read-to-Write Smart reflection hook link (displays if user is authenticated) -->
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="journal.php?article=<?php echo urlencode($res['title']); ?>" style="font-size: 0.85rem; font-weight: 600; text-decoration: underline; color: var(--color-cta-terracotta);">Reflect in Journal &rarr;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>

                    <!-- Article Detail Overlay Modal (Uses anchor hash trigger) -->
                    <div id="article-modal-<?php echo $res['id']; ?>" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
                        <div class="modal-content" style="background: white; padding: 2.5rem; border-radius: 16px; max-width: 700px; width: 95%; max-height: 90vh; overflow-y: auto; border-top: 8px solid var(--color-slate-teal); box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                            <span class="tag" style="margin-bottom: 0.5rem; display: inline-block;"><?php echo sanitize($res['category']); ?></span>
                            <h2 style="font-size: 2.2rem; margin-bottom: 1rem; color: var(--color-teal-dark);"><?php echo sanitize($res['title']); ?></h2>
                            <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 2rem;">Published &bull; <?php echo date('M d, Y', strtotime($res['created_at'])); ?></p>
                            
                            <!-- Main text block -->
                            <div style="font-size: 1.05rem; line-height: 1.7; color: var(--color-text-dark); margin-bottom: 2rem;">
                                <?php echo $res['content']; ?>
                            </div>
                            
                            <!-- Bottom Action Menu -->
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="journal.php?article=<?php echo urlencode($res['title']); ?>" class="btn btn-primary" style="background-color: var(--color-cta-terracotta);">Reflect & Write Journal</a>
                                <?php else: ?>
                                    <span style="font-size: 0.85rem; color: var(--color-text-muted);"><a href="auth/login.php" style="text-decoration: underline;">Log in</a> to write a reflection entry.</span>
                                <?php endif; ?>
                                <button onclick="window.location.hash='#resources'" class="btn btn-secondary" style="padding: 0.5rem 1.5rem;">Close Article</button>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                    // Overlay modal display script
                    function checkModalAnchor_<?php echo str_replace('-', '_', $res['id']); ?>() {
                        var modal = document.getElementById('article-modal-<?php echo $res['id']; ?>');
                        if (window.location.hash === '#article-modal-<?php echo $res['id']; ?>') {
                            modal.style.display = 'flex';
                            document.body.style.overflow = 'hidden'; // Lock page scroll
                        } else {
                            modal.style.display = 'none';
                            if (window.location.hash.indexOf('article-modal') === -1) {
                                document.body.style.overflow = ''; // Unlock scroll
                            }
                        }
                    }
                    window.addEventListener('hashchange', checkModalAnchor_<?php echo str_replace('-', '_', $res['id']); ?>);
                    window.addEventListener('load', checkModalAnchor_<?php echo str_replace('-', '_', $res['id']); ?>);
                    </script>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SIDEBAR COLUMN: Community Peer Stories Feed -->
    <div>
        <div class="feed-container">
            <h2 style="font-size: 1.6rem; margin-bottom: 1.5rem; color: var(--color-teal-dark);">Circle Stories Feed</h2>
            
            <!-- Publish Story Form -->
            <form action="connect.php" method="POST" class="feed-form">
                <input type="hidden" name="action" value="publish_post">
                
                <div class="form-group">
                    <label for="alias">Pseudonym / Alias</label>
                    <input type="text" id="alias" name="alias" class="input-control" placeholder="e.g. Sage Traveler, Quiet Mind" value="<?php echo isset($alias) && empty($success) ? sanitize($alias) : ''; ?>">
                    <span style="font-size: 0.75rem; color: var(--color-text-muted);">Leave empty to post as "Anonymous"</span>
                </div>

                <div class="form-group">
                    <label for="title">Title (Optional)</label>
                    <input type="text" id="title" name="title" class="input-control" placeholder="Summarize your story..." value="<?php echo isset($title) && empty($success) ? sanitize($title) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="trigger_warning">Trigger Warning (If needed)</label>
                    <select id="trigger_warning" name="trigger_warning" class="input-control">
                        <option value="None">No Warning Needed</option>
                        <option value="Trigger Warning: Anxiety">Anxiety / Panic</option>
                        <option value="Trigger Warning: Depression">Depression / Sadness</option>
                        <option value="Trigger Warning: Grief">Grief / Loss</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="content">What is on your mind?</label>
                    <textarea id="content" name="content" class="input-control" rows="4" placeholder="Your voice matters here. Share your feelings safely..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="background-color: var(--color-cta-terracotta); width: 100%; border-radius: 8px;">Publish to Circle</button>
            </form>

            <!-- Peer Feed Timeline -->
            <div style="max-height: 500px; overflow-y: auto; padding-right: 0.5rem;">
                <?php if (empty($feed_posts)): ?>
                    <p style="color: var(--color-text-muted); text-align: center; padding: 2rem 0;">No stories shared yet. Be the first to share.</p>
                <?php else: ?>
                    <?php foreach ($feed_posts as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <span class="post-author">👥 <?php echo sanitize($post['username_alias']); ?></span>
                                <span class="post-time"><?php echo date('H:i, M d', strtotime($post['created_at'])); ?></span>
                            </div>
                            
                            <!-- Display Trigger Warning banner if set -->
                            <?php if (!empty($post['trigger_warning'])): ?>
                                <span class="tag tw-tag" style="margin-bottom: 0.5rem; display: inline-block;">⚠️ <?php echo sanitize($post['trigger_warning']); ?></span>
                            <?php endif; ?>

                            <?php if (!empty($post['title'])): ?>
                                <h4 style="margin-bottom: 0.25rem; font-size: 1.1rem; color: var(--color-teal-dark);"><?php echo sanitize($post['title']); ?></h4>
                            <?php endif; ?>
                            
                            <p class="post-content"><?php echo nl2br(sanitize($post['content'])); ?></p>
                            
                            <!-- Safety Report Trigger Link -->
                            <div style="text-align: right; margin-top: 0.5rem;">
                                <a href="connect.php?flag=<?php echo urlencode($post['id']); ?>" style="font-size: 0.75rem; color: var(--color-crisis-red); font-weight: 600; text-decoration: underline;" onclick="return confirm('Flag this post as inappropriate for moderation review?')">🏳️ Report Post</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
