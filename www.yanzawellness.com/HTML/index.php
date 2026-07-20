<?php
/**
 * ==========================================================================
 * YANZA WELLNESS LANDING PAGE
 * Configures variables and imports the shared layout templates.
 * ==========================================================================
 */

// 1. Set configuration variables for the header template
$page_theme = 'landing'; // Activates default Ugandan Cultural Theme (Terracotta/Ochre/Sage)
$page_title = 'Welcome - Embracing Support';
$page_desc = 'Yanza Wellness is a secure, culturally integrated peer support and mental health resource directory for Uganda. Find community circles, professional counselors, and private journaling resources.';

// 2. Import the shared header (includes DB connection, sessions, navigation, and Crisis Modal)
require_once __DIR__ . '/includes/header.php';
?>

<!-- 
    Hero Section
    Displays the primary message, call-to-actions, and our inline vector illustration.
-->
<section class="hero">
    <div class="hero-content">
        <!-- Subtitle matching the cultural theme -->
        <span class="hero-subtitle">Okulaba Obulamu (To See Life)</span>
        <h1>We Embrace Your Mental Wellness Journey</h1>
        <p class="hero-description">
            Inspired by the philosophy of <strong>"Yanza"</strong> (to love, care, and embrace), we bridge the gap between traditional community support circles and professional, clinical mental health resources in Uganda.
        </p>
        
        <!-- Call to Action Buttons -->
        <div class="hero-actions">
            <a href="connect.php" class="btn btn-primary">Join a Circle</a>
            <a href="directory.php" class="btn btn-secondary">Find a Counselor</a>
        </div>
    </div>
    
    <!-- Hero Vector Illustration Column -->
    <div class="hero-illustration">
        <div class="hero-image-wrapper">
            <!-- 
                Inline Vector SVG representing community support circles & Ugandan landscapes.
                Using raw math paths allows this to load instantly without server image requests.
            -->
            <svg viewBox="0 0 500 400" width="100%" height="auto" style="background-color: var(--color-ochre-sand); display: block;" xmlns="http://www.w3.org/2000/svg">
                <!-- Ground Canvas -->
                <rect width="500" height="400" fill="#FAF6F0"/>
                
                <!-- Background green hills layered with different opacities to represent depth -->
                <path d="M-50 400 Q150 250 350 400 Z" fill="#6A9085" opacity="0.6"/>
                <path d="M150 400 Q300 200 550 400 Z" fill="#6A9085" opacity="0.8"/>
                <path d="M50 400 Q250 280 450 400 Z" fill="#519390" opacity="0.4"/>
                
                <!-- Terracotta Sun representation -->
                <circle cx="250" cy="180" r="60" fill="#8C4F34" opacity="0.8"/>
                
                <!-- Support Circle grounding shadows -->
                <ellipse cx="250" cy="340" rx="160" ry="30" fill="#E8D4BE" opacity="0.9"/>
                
                <!-- Group Figures (Abstract representations of people sitting in circle) -->
                <!-- User 1 (Left) -->
                <circle cx="150" cy="300" r="18" fill="#3E2419"/>
                <path d="M130 360 C130 320 170 320 170 360 Z" fill="#8C4F34"/>
                
                <!-- User 2 (Middle Left) -->
                <circle cx="210" cy="280" r="18" fill="#3E2419"/>
                <path d="M190 350 C190 300 230 300 230 350 Z" fill="#C87A53"/>
                
                <!-- User 3 (Middle Right) -->
                <circle cx="290" cy="280" r="18" fill="#3E2419"/>
                <path d="M270 350 C270 300 310 300 310 350 Z" fill="#6A9085"/>
                
                <!-- User 4 (Right) -->
                <circle cx="350" cy="300" r="18" fill="#3E2419"/>
                <path d="M330 360 C330 320 370 320 370 360 Z" fill="#8C4F34"/>
                
                <!-- Center flame representing shared warmth & hope -->
                <path d="M250 310 Q240 330 250 340 Q260 330 250 310 Z" fill="#D4AF37"/>
                <path d="M250 315 Q245 328 250 335 Q255 328 250 315 Z" fill="#D9383A"/>
            </svg>
        </div>
    </div>
</section>

<!-- 
    Features / Modules Grid Section
    Displays details and direct links for each section of the application.
-->
<section class="features-section">
    <div style="text-align: center; margin-bottom: 3.5rem;">
        <h2 style="font-size: 2.2rem; margin-bottom: 0.75rem; color: var(--color-clay-dark);">Explore Yanza Support Elements</h2>
        <p style="color: var(--color-text-muted); max-width: 600px; margin: 0 auto; font-size: 1.05rem;">
            Whether you want to learn, talk to peers, connect with verified experts, or log your thoughts securely—we hold space for you.
        </p>
    </div>
    
    <!-- 4-Column Responsive Grid -->
    <div class="features-grid">
        <!-- Element 1: Peer Circles Feed -->
        <div class="feature-card">
            <div class="feature-icon-box">💬</div>
            <h3>Connect Circles</h3>
            <p>Share your mental health journey, speak with peers anonymously, or listen to collective stories in our community-driven, moderated forum space.</p>
            <a href="connect.php" class="btn btn-secondary" style="margin-top: auto;">Open Feed</a>
        </div>
        
        <!-- Element 2: Resources Repository -->
        <div class="feature-card">
            <div class="feature-icon-box">📚</div>
            <h3>Resource Library</h3>
            <p>Access our curated repository of self-guided toolkits, locally authored mental wellness articles, and multimedia resources to help manage stress.</p>
            <a href="connect.php#resources" class="btn btn-secondary" style="margin-top: auto;">Browse Resources</a>
        </div>
        
        <!-- Element 3: Counselor Directory -->
        <div class="feature-card">
            <div class="feature-icon-box">🤝</div>
            <h3>Vetted Counselors</h3>
            <p>Connect with vetted, licensed mental health professionals in Uganda. View real-time availability indicator dots and schedule virtual/in-person sessions.</p>
            <a href="directory.php" class="btn btn-secondary" style="margin-top: auto;">View Directory</a>
        </div>
        
        <!-- Element 4: Reflection Space Journal -->
        <div class="feature-card">
            <div class="feature-icon-box">🔒</div>
            <h3>Reflection Space</h3>
            <p>Maintain an encrypted private diary with mood scoring. Choose to encrypt entries for personal tracking or safely publish them to the circle anonymously.</p>
            <a href="journal.php" class="btn btn-secondary" style="margin-top: auto;">Start Writing</a>
        </div>
    </div>
</section>

<!-- 
    Ugandan Philosophy Banner Section
    Reinforces community connection.
-->
<section style="background-color: var(--color-ochre-sand); padding: 5rem 2rem; text-align: center; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
    <div style="max-width: 800px; margin: 0 auto;">
        <span style="font-size: 2.5rem; display: block; margin-bottom: 1rem; color: var(--color-terracotta);">"Okulaba Obulamu kwe kusiima abalala."</span>
        <blockquote style="font-size: 1.35rem; font-style: italic; font-weight: 500; color: var(--color-clay-dark); line-height: 1.6; margin-bottom: 1.5rem;">
            "To see life is to cherish and support one another in community. No one should walk through mental distress alone, isolated from the circle of care."
        </blockquote>
        <cite style="font-weight: 700; color: var(--color-terracotta); text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.9rem;">
            Ugandan Community Philosophy
        </cite>
    </div>
</section>

<?php
// 3. Import the shared footer template (closes html tags and renders disclaimer)
require_once __DIR__ . '/includes/footer.php';
?>
