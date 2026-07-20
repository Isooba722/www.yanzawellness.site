<?php
/**
 * ==========================================================================
 * YANZA WELLNESS ADMIN - MANAGE LIBRARY RESOURCES
 * Create or delete educational articles in the library repository.
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

$page_theme = 'directory'; // Uses directory styles
$page_title = 'Manage Resources';

require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$feedback = '';
$feedback_type = '';

// 1. Process Form Submissions (Create)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'add_resource') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $read_time = trim($_POST['read_time'] ?? '5 min read');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        if (empty($title) || empty($category) || empty($summary) || empty($content)) {
            $feedback = 'Please complete all required fields to publish the article.';
            $feedback_type = 'danger';
        } else {
            try {
                $uuid = generateUUIDv4();
                
                // Prepared statement to securely save the resource
                $stmt = $db->prepare("INSERT INTO resources (id, title, summary, content, category, read_time) VALUES (:id, :title, :summary, :content, :category, :read_time)");
                $stmt->execute([
                    'id' => $uuid,
                    'title' => $title,
                    'summary' => $summary,
                    'content' => $content, // Raw HTML allowed for formatting
                    'category' => $category,
                    'read_time' => $read_time
                ]);
                
                $feedback = 'Educational article published successfully!';
                $feedback_type = 'success';
            } catch (PDOException $e) {
                error_log("Admin Resource Insert Fail: " . $e->getMessage());
                $feedback = 'Failed to publish article. Please verify inputs.';
                $feedback_type = 'danger';
            }
        }
    }
}

// 2. Process GET Actions (Delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete_resource' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM resources WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $feedback = 'Article successfully deleted from the library.';
        $feedback_type = 'success';
    } catch (PDOException $e) {
        error_log("Admin Resource Delete Fail: " . $e->getMessage());
        $feedback = 'Failed to delete the article.';
        $feedback_type = 'danger';
    }
}

// 3. Fetch resources for display
try {
    $stmt = $db->query("SELECT * FROM resources ORDER BY created_at DESC");
    $resources = $stmt->fetchAll();
} catch (PDOException $e) {
    $resources = [];
}
?>

<div class="directory-layout" style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem;">
    <!-- Navigation Link -->
    <div style="margin-bottom: 2rem;">
        <a href="dashboard.php" style="color: var(--color-sea-pine); font-weight: 600; text-decoration: none;">&larr; Back to Admin Dashboard</a>
    </div>

    <!-- Title Block -->
    <div class="directory-title-block" style="text-align: left; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; color: var(--color-sea-pine);">Curate Library Resources</h1>
        <p style="color: var(--color-text-muted); font-size: 1.1rem; margin-top: 0.5rem;">
            Write and publish self-guided toolkits, research papers, or mental wellness articles to the educational feed.
        </p>

        <!-- Feedback alerts -->
        <?php if (!empty($feedback)): ?>
            <div class="alert-banner alert-banner-<?php echo $feedback_type; ?>" style="margin-top: 1.5rem; text-align: left;">
                <span><?php echo sanitize($feedback); ?></span>
                <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Layout Grid (Form left, Table right) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2.5rem;">
        <!-- Left Column: Add Resource Form -->
        <div style="flex-grow: 1;">
            <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft);">
                <h2 style="font-size: 1.5rem; color: var(--color-sea-pine); margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Write New Article</h2>
                
                <form action="manage_resources.php" method="POST">
                    <input type="hidden" name="action" value="add_resource">
                    
                    <div class="form-group">
                        <label for="title">Article Title</label>
                        <input type="text" id="title" name="title" class="input-control" required placeholder="e.g. Managing Burnout & Stress">
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" class="input-control">
                                <option value="Mental Health A-Z">Mental Health A-Z</option>
                                <option value="Research">Research</option>
                                <option value="Self-Guided Toolkits">Self-Guided Toolkits</option>
                                <option value="Youth Support">Youth Support</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="read_time">Est. Read Time</label>
                            <input type="text" id="read_time" name="read_time" class="input-control" required placeholder="e.g. 5 min read" value="5 min read">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="summary">Brief Summary (Displays on preview cards)</label>
                        <textarea id="summary" name="summary" class="input-control" rows="3" required placeholder="A short 1-2 sentence description summarizing the article contents..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="content">Article Content (HTML allowed for layout)</label>
                        <textarea id="content" name="content" class="input-control" rows="8" required placeholder="<p>Write your detailed article body here...</p>"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="background-color: var(--color-sea-pine); width: 100%; margin-top: 1.5rem; justify-content: center; border-radius: 8px;">Publish Article</button>
                </form>
            </div>
        </div>

        <!-- Right Column: Articles Table -->
        <div style="flex-grow: 2; overflow-x: auto;">
            <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft);">
                <h2 style="font-size: 1.5rem; color: var(--color-sea-pine); margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Published Articles</h2>
                
                <?php if (empty($resources)): ?>
                    <p style="color: var(--color-text-muted); text-align: center; padding: 2rem 0;">No articles published in the library database.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color); color: var(--color-sea-pine); font-weight: 700;">
                                <th style="padding: 0.75rem 0.5rem;">Title</th>
                                <th style="padding: 0.75rem 0.5rem;">Category</th>
                                <th style="padding: 0.75rem 0.5rem;">Read Time</th>
                                <th style="padding: 0.75rem 0.5rem; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resources as $res): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 1rem 0.5rem; font-weight: 600; color: var(--color-sea-pine);">
                                        <?php echo sanitize($res['title']); ?>
                                    </td>
                                    <td style="padding: 1rem 0.5rem; color: var(--color-text-muted);">
                                        <span class="tag" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;"><?php echo sanitize($res['category']); ?></span>
                                    </td>
                                    <td style="padding: 1rem 0.5rem; color: var(--color-text-muted);">
                                        <?php echo sanitize($res['read_time']); ?>
                                    </td>
                                    <td style="padding: 1rem 0.5rem; text-align: right;">
                                        <a href="manage_resources.php?action=delete_resource&id=<?php echo urlencode($res['id']); ?>" 
                                           style="color: var(--color-crisis-red); font-weight: 700; text-decoration: underline; margin-left: 1rem; font-size: 0.85rem;"
                                           onclick="return confirm('Are you sure you want to permanently delete this article from the library?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
