<?php
/**
 * Template Name: AGB
 * Template for the Terms and Conditions page with hero header
 *
 * @package GeneratePress Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div class="sd-legal-page">
    <!-- Hero Section -->
    <section class="sd-contact-hero">
        <div class="sd-contact-hero-inner">
            <div class="sd-contact-hero-content">
                <h1 class="sd-contact-hero-title">AGB</h1>
                <p class="sd-contact-hero-subtitle">
                    Allgemeine Geschäftsbedingungen<br>
                    für die Nutzung von Hofladen-Scout.de.
                </p>
            </div>
        </div>
        <div class="sd-contact-hero-accent"></div>
    </section>

    <!-- Main Content -->
    <div class="sd-legal-container">
        <div class="sd-legal-content">
            <?php
            while ( have_posts() ) :
                the_post();
                the_content();
            endwhile;
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
