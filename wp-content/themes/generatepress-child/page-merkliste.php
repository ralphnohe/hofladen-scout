<?php
/**
 * Template Name: Merkliste
 * Template for the Favorites page with hero header
 *
 * @package GeneratePress Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div class="sd-favorites-wrapper">
    <!-- Hero Section -->
    <section class="sd-contact-hero">
        <div class="sd-contact-hero-inner">
            <div class="sd-contact-hero-content">
                <h1 class="sd-contact-hero-title">Merkliste</h1>
                <p class="sd-contact-hero-subtitle">
                    Deine gespeicherten Spezialisten<br>
                    auf einen Blick.
                </p>
            </div>
        </div>
        <div class="sd-contact-hero-accent"></div>
    </section>

    <!-- Main Content -->
    <div class="sd-favorites-container">
        <?php echo do_shortcode('[spezialist_favorites]'); ?>
    </div>
</div>

<?php get_footer(); ?>
