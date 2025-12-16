<?php
/**
 * Single Spezialist Template
 *
 * Template for displaying single spezialist posts
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="main" class="site-main">
    <?php
    while ( have_posts() ) :
        the_post();

        // Include the detailed listing template
        include( SD_PLUGIN_DIR . 'templates/listing-detail.php' );

    endwhile; // End of the loop.
    ?>
</main>

<?php
get_footer();
