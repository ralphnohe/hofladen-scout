<?php
/**
 * Template Name: Impressum
 * Template for the Legal Notice page with hero header
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
                <h1 class="sd-contact-hero-title">Impressum</h1>
                <p class="sd-contact-hero-subtitle">
                    Angaben gemäß § 5 TMG<br>
                    und verantwortlich für den Inhalt.
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
