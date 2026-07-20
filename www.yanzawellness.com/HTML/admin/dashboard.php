<?php
/**
 * ==========================================================================
 * YANZA WELLNESS ADMIN DASHBOARD
 * Central overview panel for administrators to monitor and manage resources,
 * practitioners, and moderated content.
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

$page_theme = 'directory'; // Uses clean Sea-Pine theme styling
$page_title = 'Admin Dashboard';

require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();

$stats = [
    'users' => 0,
    'counselors' => 0,
    'resources' => 0,
    'flagged' => 0
];

try {
    // 1. Fetch counts
    $stats['users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['counselors'] = $db->query("SELECT COUNT(*) FROM counselors")->fetchColumn();
    $stats['resources'] = $db->query("SELECT COUNT(*) FROM resources")->fetchColumn();
    $stats['flagged'] = $db->query("SELECT COUNT(*) FROM community_feed WHERE is_flagged = 1")->fetchColumn();
} catch (PDOException $e) {
    error_log("Admin Stats Query Failure: " . $e->getMessage());
}
?>

<div class="directory-layout" style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem;">
    <!-- Title Block -->
    <div class="directory-title-block" style="text-align: left; margin-bottom: 3rem;">
        <span style="font-size: 0.9rem; font-weight: 700; color: var(--color-cta-terracotta); text-transform: uppercase; letter-spacing: 0.1em;">Control Center</span>
        <h1 style="font-size: 2.5rem; color: var(--color-sea-pine); margin-top: 0.25rem;">Administrator Control Panel</h1>
        <p style="color: var(--color-text-muted); font-size: 1.1rem; margin-top: 0.5rem;">
            Hello, <strong><?php echo sanitize($_SESSION['username']); ?></strong>. Use the modules below to maintain counselors, educational materials, and review peer circle posts.
        </p>
    </div>

    <!-- Quick Stats Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 4rem;">
        <!-- Card 1: Users -->
        <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">👥</div>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--color-sea-pine);"><?php echo $stats['users']; ?></div>
            <div style="color: var(--color-text-muted); font-size: 0.95rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Registered Users</div>
        </div>

        <!-- Card 2: Counselors -->
        <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🤝</div>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--color-sea-pine);"><?php echo $stats['counselors']; ?></div>
            <div style="color: var(--color-text-muted); font-size: 0.95rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Vetted Counselors</div>
        </div>

        <!-- Card 3: Resources -->
        <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📚</div>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--color-sea-pine);"><?php echo $stats['resources']; ?></div>
            <div style="color: var(--color-text-muted); font-size: 0.95rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Library Articles</div>
        </div>

        <!-- Card 4: Moderation Queue -->
        <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft); position: relative;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🚨</div>
            <div style="font-size: 1.8rem; font-weight: 700; color: <?php echo $stats['flagged'] > 0 ? 'var(--color-crisis-red)' : 'var(--color-sea-pine)'; ?>;">
                <?php echo $stats['flagged']; ?>
            </div>
            <div style="color: var(--color-text-muted); font-size: 0.95rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Flagged Stories</div>
            <?php if ($stats['flagged'] > 0): ?>
                <span style="position: absolute; top: 1rem; right: 1rem; background-color: var(--color-crisis-red); width: 12px; height: 12px; border-radius: 50%; display: inline-block;"></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Management Modules Grid -->
    <div style="margin-bottom: 4rem;">
        <h2 style="font-size: 1.8rem; color: var(--color-sea-pine); margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Management Modules</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <!-- Module 1: Counselors -->
            <div style="background: var(--bg-card); border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft); overflow: hidden; display: flex; flex-direction: column; transition: var(--transition-smooth);" onmouseover="this.style.boxShadow='var(--shadow-hover)'" onmouseout="this.style.boxShadow='var(--shadow-soft)'">
                <div style="background: linear-gradient(135deg, var(--color-sea-pine) 0%, var(--color-pine-dark) 100%); padding: 1.5rem; color: white;">
                    <h3 style="font-size: 1.35rem; font-weight: 600;">Practitioner Directory</h3>
                </div>
                <div style="padding: 1.5rem; display: flex; flex-direction: column; flex-grow: 1; gap: 1rem;">
                    <p style="font-size: 0.95rem; color: var(--color-text-muted); line-height: 1.5;">
                        Control listed doctors, therapists, and peer counselors. You can add new vetted profiles, edit details, adjust availability states, or delete listings.
                    </p>
                    <a href="manage_counselors.php" class="btn btn-primary" style="margin-top: auto; width: 100%; text-align: center; justify-content: center; background-color: var(--color-sea-pine);">Manage Counselors &rarr;</a>
                </div>
            </div>

            <!-- Module 2: Articles / Resources -->
            <div style="background: var(--bg-card); border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft); overflow: hidden; display: flex; flex-direction: column; transition: var(--transition-smooth);" onmouseover="this.style.boxShadow='var(--shadow-hover)'" onmouseout="this.style.boxShadow='var(--shadow-soft)'">
                <div style="background: linear-gradient(135deg, var(--color-sea-pine) 0%, var(--color-pine-dark) 100%); padding: 1.5rem; color: white;">
                    <h3 style="font-size: 1.35rem; font-weight: 600;">Educational Library</h3>
                </div>
                <div style="padding: 1.5rem; display: flex; flex-direction: column; flex-grow: 1; gap: 1rem;">
                    <p style="font-size: 0.95rem; color: var(--color-text-muted); line-height: 1.5;">
                        Publish and curate self-guided wellness guides, research articles, or mental health FAQs for Ugandan readers. Easily write new pieces or remove old files.
                    </p>
                    <a href="manage_resources.php" class="btn btn-primary" style="margin-top: auto; width: 100%; text-align: center; justify-content: center; background-color: var(--color-sea-pine);">Manage Resources &rarr;</a>
                </div>
            </div>

            <!-- Module 3: Moderation -->
            <div style="background: var(--bg-card); border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft); overflow: hidden; display: flex; flex-direction: column; transition: var(--transition-smooth);" onmouseover="this.style.boxShadow='var(--shadow-hover)'" onmouseout="this.style.boxShadow='var(--shadow-soft)'">
                <div style="background: linear-gradient(135deg, <?php echo $stats['flagged'] > 0 ? 'var(--color-crisis-red) 0%, var(--color-crisis-hover) 100%' : 'var(--color-sea-pine) 0%, var(--color-pine-dark) 100%'; ?>); padding: 1.5rem; color: white;">
                    <h3 style="font-size: 1.35rem; font-weight: 600;">Feed Moderation Queue</h3>
                </div>
                <div style="padding: 1.5rem; display: flex; flex-direction: column; flex-grow: 1; gap: 1rem;">
                    <p style="font-size: 0.95rem; color: var(--color-text-muted); line-height: 1.5;">
                        Audit anonymous peer posts that have been safety-flagged by readers. Review stories, dismiss reports, or permanently delete posts.
                    </p>
                    <a href="manage_posts.php" class="btn btn-primary" style="margin-top: auto; width: 100%; text-align: center; justify-content: center; background-color: <?php echo $stats['flagged'] > 0 ? 'var(--color-crisis-red)' : 'var(--color-sea-pine)'; ?>;">
                        Moderate Feed (<?php echo $stats['flagged']; ?> Flagged) &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
