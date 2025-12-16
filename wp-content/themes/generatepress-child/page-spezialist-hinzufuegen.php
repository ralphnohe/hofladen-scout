<?php
/**
 * Template Name: Spezialist hinzufügen
 * Template for the Add Specialist page with hero header
 *
 * @package GeneratePress Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div class="sd-submit-wrapper">
    <!-- Hero Section -->
    <section class="sd-contact-hero">
        <div class="sd-contact-hero-inner">
            <div class="sd-contact-hero-content">
                <h1 class="sd-contact-hero-title">Spezialist hinzufügen</h1>
                <p class="sd-contact-hero-subtitle">
                    Trage Dein Unternehmen ein<br>
                    und werde von Kunden gefunden.
                </p>
            </div>
        </div>
        <div class="sd-contact-hero-accent"></div>
    </section>

    <!-- Main Content -->
    <div class="sd-submit-container">
        <?php echo do_shortcode('[spezialist_submit]'); ?>
    </div>
</div>

<?php get_footer(); ?>
