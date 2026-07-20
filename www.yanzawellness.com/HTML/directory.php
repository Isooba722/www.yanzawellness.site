<?php
/**
 * ==========================================================================
 * YANZA WELLNESS COUNSELOR DIRECTORY
 * Features Sea-Pine Teal & Mint-Cream Theme. Lists verified practitioners,
 * displays real-time availability states, and hosts session booking modals.
 * ==========================================================================
 */

// 1. Configure configuration variables for the page
$page_theme = 'directory'; // Activates Sea-Pine Teal and Mint-Cream layouts
$page_title = 'Find a Counselor';
$page_desc = 'Connect with vetted, clinical specialists and peer support counselors in Uganda. View real-time availability and schedule sessions.';

require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();
$feedback = '';
$feedback_type = '';

/**
 * A. PROCESS SESSION BOOKING FORM
 * Validates inputs and logs mock scheduling appointments.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_session') {
    $counselor_name = trim($_POST['counselor_name'] ?? '');
    $client_name = trim($_POST['client_name'] ?? '');
    $client_email = trim($_POST['client_email'] ?? '');
    $booking_date = trim($_POST['booking_date'] ?? '');
    $booking_time = trim($_POST['booking_time'] ?? '');
    $client_message = trim($_POST['client_message'] ?? ''); // Optional client message field
    
    if (empty($client_name) || empty($client_email) || empty($booking_date) || empty($booking_time)) {
        $feedback = 'Please complete all required fields to submit your scheduling request.';
        $feedback_type = 'danger';
    } else {
        // Logs booking activity on the server log
        error_log("Mock Appointment booked: {$client_name} ({$client_email}) requested a session with {$counselor_name} on {$booking_date} at {$booking_time}. Message: {$client_message}");
        
        $feedback = "Your booking request with {$counselor_name} has been submitted! Check your email ({$client_email}) for confirmation details shortly.";
        $feedback_type = 'success';
    }
}

/**
 * B. FETCH VETTED COUNSELORS
 */
try {
    $stmt = $db->query("SELECT * FROM counselors ORDER BY rating DESC");
    $counselors = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database Counselor Query Failure: " . $e->getMessage());
    $counselors = [];
}
?>

<div class="directory-layout">
    
    <!-- Title Header -->
    <div class="directory-title-block">
        <h1>Connect with Professionals</h1>
        <p style="color: var(--color-text-muted); font-size: 1.15rem; max-width: 700px; margin: 0 auto;">
            Speak with vetted psychologists, counselors, and peer support guides. Check their availability indicators and book directly.
        </p>

        <!-- Feedback Alert Banner -->
        <?php if (!empty($feedback)): ?>
            <div class="alert-banner alert-banner-<?php echo $feedback_type; ?>" style="margin-top: 1.5rem; text-align: left;">
                <span><?php echo sanitize($feedback); ?></span>
                <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Counselor Listing Grid -->
    <div class="counselors-grid">
        <?php if (empty($counselors)): ?>
            <div style="background-color: var(--bg-card); padding: 3rem; text-align: center; border-radius: var(--border-radius); border: 1px solid var(--border-color); grid-column: 1 / -1;">
                <p style="color: var(--color-text-muted); font-size: 1.1rem;">Our professional database is temporarily undergoing updates. Please try again soon.</p>
            </div>
        <?php else: ?>
            <?php foreach ($counselors as $counselor): ?>
                <div class="counselor-card">
                    <!-- Left Column: Avatar & Availability tracking dot -->
                    <div class="counselor-avatar-col">
                        <!-- Abstract profile circle placeholder -->
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: var(--color-ochre-sand); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; border: 4px solid var(--color-mint-cream); overflow: hidden; position: relative;">
                            👤
                        </div>
                        
                        <!-- Map availability statuses to CSS color codes -->
                        <?php 
                        $status_class = '';
                        if ($counselor['availability'] === 'Available') {
                            $status_class = 'active'; // Glowing green dot
                        } elseif ($counselor['availability'] === 'Busy') {
                            $status_class = 'busy';   // Yellow dot
                        }
                        ?>
                        <span class="status-dot <?php echo $status_class; ?>" title="Status: <?php echo sanitize($counselor['availability']); ?>"></span>
                    </div>

                    <!-- Right Column: Info & Booking options -->
                    <div class="counselor-info-col">
                        <h3><?php echo sanitize($counselor['name']); ?></h3>
                        <div class="counselor-credentials"><?php echo sanitize($counselor['credentials']); ?></div>
                        
                        <!-- Star rating indicators -->
                        <div class="counselor-rating">
                            <span class="star-icon">★</span>
                            <span><?php echo number_format($counselor['rating'], 1); ?></span>
                            <span style="font-weight: normal; color: var(--color-text-muted); font-size: 0.8rem;">(Vetted Member)</span>
                        </div>
                        
                        <div class="counselor-details">
                            <p><strong>Specialties:</strong> <?php echo sanitize($counselor['specialties']); ?></p>
                            <p><strong>Schedule:</strong> <?php echo sanitize($counselor['availability']); ?></p>
                        </div>
                        
                        <!-- Primary Booking buttons -->
                        <div class="counselor-actions">
                            <a href="#booking-modal-<?php echo $counselor['id']; ?>" class="btn btn-primary" style="font-size: 0.9rem; padding: 0.6rem 1.2rem; flex-grow: 1;">Book a Session</a>
                            <a href="https://cal.com/yanza-wellness/mock-profile" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="font-size: 0.9rem; padding: 0.6rem 1.2rem;">Cal.com Link</a>
                        </div>
                    </div>
                </div>

                <!-- Custom Booking Modal Overlay (Anchor hash matching logic) -->
                <div id="booking-modal-<?php echo $counselor['id']; ?>" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
                    <div class="modal-content" style="background: white; padding: 2.5rem; border-radius: 16px; max-width: 500px; width: 90%; border-top: 8px solid var(--color-sea-pine); box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                        
                        <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--color-sea-pine); text-align: center;">Book with <?php echo sanitize($counselor['name']); ?></h2>
                        
                        <form action="directory.php" method="POST">
                            <input type="hidden" name="action" value="book_session">
                            <input type="hidden" name="counselor_name" value="<?php echo sanitize($counselor['name']); ?>">
                            
                            <div class="form-group">
                                <label for="client_name_<?php echo $counselor['id']; ?>">Your Full Name</label>
                                <input type="text" id="client_name_<?php echo $counselor['id']; ?>" name="client_name" class="input-control" required value="<?php echo isset($_SESSION['username']) ? sanitize($_SESSION['username']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="client_email_<?php echo $counselor['id']; ?>">Email Address</label>
                                <input type="email" id="client_email_<?php echo $counselor['id']; ?>" name="client_email" class="input-control" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="booking_date_<?php echo $counselor['id']; ?>">Select Date</label>
                                    <input type="date" id="booking_date_<?php echo $counselor['id']; ?>" name="booking_date" class="input-control" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="booking_time_<?php echo $counselor['id']; ?>">Select Time</label>
                                    <input type="time" id="booking_time_<?php echo $counselor['id']; ?>" name="booking_time" class="input-control" required>
                                </div>
                            </div>

                            <!-- Optional Client Message textarea (As requested) -->
                            <div class="form-group">
                                <label for="client_message_<?php echo $counselor['id']; ?>">Message / Notes (Optional)</label>
                                <textarea id="client_message_<?php echo $counselor['id']; ?>" name="client_message" class="input-control" rows="3" placeholder="Tell us briefly how we can support you..."></textarea>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                                <button type="submit" class="btn btn-primary" style="flex-grow: 1;">Request Appointment</button>
                                <button type="button" onclick="window.location.hash=''" class="btn btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                // Modal display toggle listener
                function checkBookingModal_<?php echo str_replace('-', '_', $counselor['id']); ?>() {
                    var modal = document.getElementById('booking-modal-<?php echo $counselor['id']; ?>');
                    if (window.location.hash === '#booking-modal-<?php echo $counselor['id']; ?>') {
                        modal.style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                    } else {
                        modal.style.display = 'none';
                        if (window.location.hash.indexOf('booking-modal') === -1) {
                            document.body.style.overflow = '';
                        }
                    }
                }
                window.addEventListener('hashchange', checkBookingModal_<?php echo str_replace('-', '_', $counselor['id']); ?>);
                window.addEventListener('load', checkBookingModal_<?php echo str_replace('-', '_', $counselor['id']); ?>);
                </script>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- VETTING PROTOCOL INFO CONTAINER -->
    <div class="vetting-container">
        <h2>Our Three-Tier Vetting Standards</h2>
        <p style="color: var(--color-text-muted); max-width: 700px; margin: 0 auto 1.5rem; font-size: 0.95rem;">
            To ensure the highest safety guidelines and clinical trust, every counselor listed in our directory undergoes our strict verification protocol.
        </p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; text-align: left; margin-top: 2rem;">
            <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--color-sea-pine); margin-bottom: 0.5rem;">🎓 Credential Auditing</h4>
                <p style="font-size: 0.85rem; color: var(--color-text-muted);">Verification of university degrees, clinical certifications, and professional registrations with psychological licensing associations in Uganda.</p>
            </div>
            <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--color-sea-pine); margin-bottom: 0.5rem;">🕵️ Background Validation</h4>
                <p style="font-size: 0.85rem; color: var(--color-text-muted);">Verification of references and standard ethical checks to ensure clean records of ethical professional practice.</p>
            </div>
            <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--color-sea-pine); margin-bottom: 0.5rem;">💬 Cultural Alignment</h4>
                <p style="font-size: 0.85rem; color: var(--color-text-muted);">Confirming language proficiency (Luganda, Lusoga, etc.) and deep understanding of local support systems and boundaries.</p>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
