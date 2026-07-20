<?php
/**
 * ==========================================================================
 * YANZA WELLNESS FOOTER TEMPLATE
 * Closes structural tags, renders links, and shows the medical disclaimer.
 * ==========================================================================
 */
?>

<footer>
    <div class="footer-container">
        <!-- Brand / Identity description column -->
        <div>
            <div class="footer-logo">
                <!-- White vector outline variation of our interlocking heart logo -->
                <svg class="logo-icon" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 28px; height: 28px;">
                    <path d="M50 85C50 85 85 62 85 38C85 22 72 10 56 10C48 10 42 14 38 20C34 14 28 10 20 10C4 10 -9 22 -9 38C-9 62 26 85 26 85" stroke="#FAF6F0" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M50 85C50 85 15 62 15 38C15 22 28 10 44 10C52 10 58 14 62 20C66 14 72 10 80 10C96 10 109 22 109 38C109 62 74 85 74 85" stroke="#E8D4BE" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" opacity="0.8"/>
                </svg>
                <span style="color: var(--color-white);">Yanza Wellness</span>
            </div>
            <p style="font-size: 0.9rem; margin-bottom: 1.5rem; color: rgba(232, 212, 190, 0.75);">
                Based on care, empathy, and support. We provide a safe, secure platform for mental health resources in Uganda.
            </p>
        </div>
        
        <!-- Navigation Link Index Column -->
        <div class="footer-links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Home Page</a></li>
                <li><a href="connect.php">Circles & Resources</a></li>
                <li><a href="directory.php">Counselors Directory</a></li>
                <li><a href="#crisis-modal">Crisis Helpline Numbers</a></li>
            </ul>
        </div>
        
        <!-- 
            MANDATORY MEDICAL DISCLAIMER COLUMN
            This blocks clinical liability and establishes that the site is an 
            educational/directory site, not a replacement for medical psychiatric care.
        -->
        <div class="footer-disclaimer-col">
            <h4 style="color: var(--color-white);">Medical Disclaimer</h4>
            <div class="footer-disclaimer-box">
                <strong>Important Notice:</strong> Yanza Wellness is an educational resource library, peer circle, and professional directory. We do not provide clinical therapy, psychiatric diagnostic reviews, or medical evaluations. If you are experiencing severe psychological symptoms, please consult a licensed medical clinician.
            </div>
        </div>
    </div>
    
    <!-- Footnote copyright line -->
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Yanza Wellness (yanzawellness.site). All rights reserved.</p>
        <p>Embracing Support &bullet; Handcrafted with care</p>
    </div>
</footer>

</body>
</html>
