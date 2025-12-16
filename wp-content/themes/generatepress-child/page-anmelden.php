<?php
/**
 * Template Name: Anmelden
 * Template for the Login/Register page with hero header
 *
 * @package GeneratePress Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div class="sd-auth-wrapper-full">
    <!-- Hero Section -->
    <section class="sd-contact-hero">
        <div class="sd-contact-hero-inner">
            <div class="sd-contact-hero-content">
                <h1 class="sd-contact-hero-title">Anmelden</h1>
                <p class="sd-contact-hero-subtitle">
                    Melde dich an oder erstelle ein Konto<br>
                    um alle Funktionen zu nutzen.
                </p>
            </div>
        </div>
        <div class="sd-contact-hero-accent"></div>
    </section>

    <!-- Main Content -->
    <div class="sd-auth-container">
        <?php echo do_shortcode('[spezialist_login]'); ?>
    </div>
</div>

<?php get_footer(); ?>
