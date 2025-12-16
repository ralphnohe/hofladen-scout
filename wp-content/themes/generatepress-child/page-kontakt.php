<?php
/**
 * Template Name: Kontakt
 * Template for the Contact page - Modern, professional design
 *
 * @package GeneratePress Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div class="sd-contact-page">
    <!-- Hero Section -->
    <section class="sd-contact-hero">
        <div class="sd-contact-hero-inner">
            <div class="sd-contact-hero-content">
                <h1 class="sd-contact-hero-title">Kontakt</h1>
                <p class="sd-contact-hero-subtitle">
                    Hast Du Fragen zu Spezialist-Für.de oder benötigst Unterstützung?<br>
                    Wir freuen uns auf Deine Nachricht.
                </p>
            </div>
        </div>
        <div class="sd-contact-hero-accent"></div>
    </section>

    <!-- Main Content -->
    <div class="sd-contact-container">
        <div class="sd-contact-grid">
            <!-- Form Section -->
            <div class="sd-contact-form-section">
                <div class="sd-contact-card">
                    <div class="sd-contact-card-header">
                        <svg class="sd-contact-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <h2 class="sd-contact-card-title">Nachricht senden</h2>
                    </div>
                    <p class="sd-contact-card-desc">Füll das Formular aus und wir melden uns innerhalb von 24 Stunden bei Dir.</p>

                    <div class="sd-cf7-form">
                        <?php echo do_shortcode('[contact-form-7 id="7143" title="Spezialist Kontaktformular"]'); ?>
                    </div>
                </div>
            </div>

            <!-- Info Section -->
            <div class="sd-contact-info-section">
                <!-- Email Card -->
                <a href="mailto:info@spezialist-fuer.de" class="sd-info-card sd-info-card-clickable">
                    <div class="sd-info-card-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <div class="sd-info-card-content">
                        <span class="sd-info-card-label">E-Mail</span>
                        <span class="sd-info-card-value">info@spezialist-fuer.de</span>
                    </div>
                    <svg class="sd-info-card-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>

                <!-- Phone Card -->
                <a href="tel:+4991125257599" class="sd-info-card sd-info-card-clickable">
                    <div class="sd-info-card-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </div>
                    <div class="sd-info-card-content">
                        <span class="sd-info-card-label">Telefon</span>
                        <span class="sd-info-card-value">+49 (0) 911-2525-7599</span>
                    </div>
                    <svg class="sd-info-card-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>

                <!-- Fax Card -->
                <div class="sd-info-card">
                    <div class="sd-info-card-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                            <line x1="6" y1="11" x2="6" y2="11"/>
                            <line x1="10" y1="11" x2="10" y2="11"/>
                        </svg>
                    </div>
                    <div class="sd-info-card-content">
                        <span class="sd-info-card-label">Fax</span>
                        <span class="sd-info-card-value">+49 (0) 911 88194-0018</span>
                    </div>
                </div>

                <!-- Response Time Badge -->
                <div class="sd-contact-response-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>Durchschnittliche Antwortzeit: <strong>24 Stunden</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
