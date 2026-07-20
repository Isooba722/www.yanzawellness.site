<?php
/**
 * ==========================================================================
 * YANZA WELLNESS ADMIN - MANAGE COUNSELORS
 * Add, edit, or delete practitioners in the directory.
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

$page_theme = 'directory'; // Sea-Pine theme
$page_title = 'Manage Counselors';

require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$feedback = '';
$feedback_type = '';

// 1. Process Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ACTION A: ADD COUNSELOR
    if ($_POST['action'] === 'add_counselor') {
        $name = trim($_POST['name'] ?? '');
        $credentials = trim($_POST['credentials'] ?? '');
        $specialties = trim($_POST['specialties'] ?? '');
        $availability = trim($_POST['availability'] ?? 'Available');
        $rating = floatval($_POST['rating'] ?? 5.0);
        
        if (empty($name) || empty($credentials) || empty($specialties)) {
            $feedback = 'Please fill out all required fields to add a counselor.';
            $feedback_type = 'danger';
        } else {
            try {
                $uuid = generateUUIDv4();
                $stmt = $db->prepare("INSERT INTO counselors (id, name, credentials, specialties, availability, rating) VALUES (:id, :name, :credentials, :specialties, :availability, :rating)");
                $stmt->execute([
                    'id' => $uuid,
                    'name' => $name,
                    'credentials' => $credentials,
                    'specialties' => $specialties,
                    'availability' => $availability,
                    'rating' => $rating
                ]);
                $feedback = 'New counselor profile created successfully!';
                $feedback_type = 'success';
            } catch (PDOException $e) {
                error_log("Admin Counselor Insert Fail: " . $e->getMessage());
                $feedback = 'Failed to create counselor profile. Please check inputs.';
                $feedback_type = 'danger';
            }
        }
    }
    
    // ACTION B: UPDATE STATUS
    if ($_POST['action'] === 'update_status') {
        $id = $_POST['id'] ?? '';
        $availability = $_POST['availability'] ?? 'Available';
        
        try {
            $stmt = $db->prepare("UPDATE counselors SET availability = :availability WHERE id = :id");
            $stmt->execute([
                'availability' => $availability,
                'id' => $id
            ]);
            $feedback = 'Counselor availability updated!';
            $feedback_type = 'success';
        } catch (PDOException $e) {
            error_log("Admin Counselor Update Fail: " . $e->getMessage());
            $feedback = 'Failed to update counselor status.';
            $feedback_type = 'danger';
        }
    }
}

// 2. Process GET Requests (e.g. Delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete_counselor' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM counselors WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $feedback = 'Counselor profile deleted successfully.';
        $feedback_type = 'success';
    } catch (PDOException $e) {
        error_log("Admin Counselor Delete Fail: " . $e->getMessage());
        $feedback = 'Failed to delete counselor profile.';
        $feedback_type = 'danger';
    }
}

// 3. Fetch Counselors for the display table
try {
    $stmt = $db->query("SELECT * FROM counselors ORDER BY created_at DESC");
    $counselors = $stmt->fetchAll();
} catch (PDOException $e) {
    $counselors = [];
}
?>

<div class="directory-layout" style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem;">
    <!-- Navigation Back Link -->
    <div style="margin-bottom: 2rem;">
        <a href="dashboard.php" style="color: var(--color-sea-pine); font-weight: 600; text-decoration: none;">&larr; Back to Admin Dashboard</a>
    </div>

    <!-- Title Header -->
    <div class="directory-title-block" style="text-align: left; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; color: var(--color-sea-pine);">Manage Vetted Counselors</h1>
        <p style="color: var(--color-text-muted); font-size: 1.1rem; margin-top: 0.5rem;">
            Add new licensed therapists or edit existing member profiles in the public counselors directory.
        </p>

        <!-- Feedback alert banners -->
        <?php if (!empty($feedback)): ?>
            <div class="alert-banner alert-banner-<?php echo $feedback_type; ?>" style="margin-top: 1.5rem; text-align: left;">
                <span><?php echo sanitize($feedback); ?></span>
                <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content Layout (Form left, Table right) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2.5rem;">
        <!-- Left Column: Add Counselor Form -->
        <div>
            <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft);">
                <h2 style="font-size: 1.5rem; color: var(--color-sea-pine); margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Add New Counselor</h2>
                
                <form action="manage_counselors.php" method="POST">
                    <input type="hidden" name="action" value="add_counselor">
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="input-control" required placeholder="e.g. Dr. Shanya Khesooba">
                    </div>
                    
                    <div class="form-group">
                        <label for="credentials">Credentials / Qualifications</label>
                        <input type="text" id="credentials" name="credentials" class="input-control" required placeholder="e.g. Clinical Psychologist (M.Sc), Therapist">
                    </div>

                    <div class="form-group">
                        <label for="specialties">Specialties (Comma separated)</label>
                        <input type="text" id="specialties" name="specialties" class="input-control" required placeholder="e.g. Anxiety, Grief, Youth Mentorship">
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="availability">Availability</label>
                            <select id="availability" name="availability" class="input-control">
                                <option value="Available">Available</option>
                                <option value="Busy">Busy</option>
                                <option value="Offline">Offline</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="rating">Initial Rating (1-5)</label>
                            <input type="number" id="rating" name="rating" class="input-control" min="1" max="5" step="0.1" value="5.0">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="background-color: var(--color-sea-pine); width: 100%; margin-top: 1.5rem; justify-content: center; border-radius: 8px;">Create Profile</button>
                </form>
            </div>
        </div>

        <!-- Right Column: Counselors Directory Table -->
        <div style="flex-grow: 2; overflow-x: auto;">
            <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft);">
                <h2 style="font-size: 1.5rem; color: var(--color-sea-pine); margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Listed Counselors</h2>
                
                <?php if (empty($counselors)): ?>
                    <p style="color: var(--color-text-muted); text-align: center; padding: 2rem 0;">No counselors listed in the database directory.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color); color: var(--color-sea-pine); font-weight: 700;">
                                <th style="padding: 0.75rem 0.5rem;">Name</th>
                                <th style="padding: 0.75rem 0.5rem;">Credentials</th>
                                <th style="padding: 0.75rem 0.5rem;">Status</th>
                                <th style="padding: 0.75rem 0.5rem; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($counselors as $counselor): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 1rem 0.5rem; font-weight: 600; color: var(--color-sea-pine);">
                                        <?php echo sanitize($counselor['name']); ?>
                                    </td>
                                    <td style="padding: 1rem 0.5rem; color: var(--color-text-muted);">
                                        <?php echo sanitize($counselor['credentials']); ?>
                                    </td>
                                    <td style="padding: 1rem 0.5rem;">
                                        <!-- Quick Status Form -->
                                        <form action="manage_counselors.php" method="POST" style="margin: 0; display: inline-block;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo $counselor['id']; ?>">
                                            <select name="availability" onchange="this.form.submit()" style="padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid var(--border-color); font-size: 0.8rem; background-color: var(--color-mint-cream); color: var(--color-sea-pine); font-weight: 600; cursor: pointer;">
                                                <option value="Available" <?php echo $counselor['availability'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                                <option value="Busy" <?php echo $counselor['availability'] === 'Busy' ? 'selected' : ''; ?>>Busy</option>
                                                <option value="Offline" <?php echo $counselor['availability'] === 'Offline' ? 'selected' : ''; ?>>Offline</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td style="padding: 1rem 0.5rem; text-align: right;">
                                        <a href="manage_counselors.php?action=delete_counselor&id=<?php echo urlencode($counselor['id']); ?>" 
                                           style="color: var(--color-crisis-red); font-weight: 700; text-decoration: underline; margin-left: 1rem; font-size: 0.85rem;"
                                           onclick="return confirm('Are you sure you want to permanently delete this counselor profile?');">
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
