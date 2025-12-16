<?php
/**
 * Template: Taxonomy Archive
 *
 * Unified archive template for spezialist taxonomies
 * (spezialist_category, spezialist_location, spezialist_tag)
 *
 * @package Spezialist_Directory
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$term = get_queried_object();
$taxonomy = get_query_var( 'taxonomy' );

// Taxonomy-specific labels
$taxonomy_labels = array(
    'spezialist_category' => __( 'Kategorie', 'spezialist-directory' ),
    'spezialist_location' => __( 'Standort', 'spezialist-directory' ),
    'spezialist_tag'      => __( 'Schlagwort', 'spezialist-directory' ),
);
$label = isset( $taxonomy_labels[ $taxonomy ] ) ? $taxonomy_labels[ $taxonomy ] : '';

$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;
?>

<div class="sd-taxonomy-archive-wrapper">
    <!-- Hero Section with Breadcrumbs -->
    <section class="sd-contact-hero">
        <div class="sd-contact-hero-inner">
            <div class="sd-contact-hero-content">
                <?php
                // Breadcrumbs inside hero
                if ( class_exists( 'SDSEO_Breadcrumbs' ) ) {
                    SDSEO_Breadcrumbs::render();
                }
                ?>
                <h1 class="sd-contact-hero-title"><?php single_term_title(); ?></h1>
                <p class="sd-contact-hero-subtitle">
                    <?php
                    if ( $term ) {
                        printf(
                            _n( '%d Spezialist', '%d Spezialisten', $term->count, 'spezialist-directory' ),
                            $term->count
                        );
                        if ( $term->description ) {
                            echo '<br>' . esc_html( $term->description );
                        }
                    }
                    ?>
                </p>
            </div>
        </div>
        <div class="sd-contact-hero-accent"></div>
    </section>

    <!-- Main Content -->
    <div class="sd-taxonomy-archive-container">

    <?php
    // FAQ Schema Snippet for spezialist_category
    if ( $taxonomy === 'spezialist_category' && $term ) :
        $faq_snippet = get_term_meta( $term->term_id, '_sd_faq_snippet', true );
        if ( $faq_snippet ) :
    ?>
        <div class="sd-faq-schema-section">
            <h2 class="sd-faq-section-title"><?php _e( 'Häufig gestellte Fragen', 'spezialist-directory' ); ?></h2>
            <?php echo $faq_snippet; ?>
        </div>
    <?php endif; endif; ?>

    <?php if ( have_posts() ) : ?>
        <h2 class="sd-listings-section-title"><?php printf( __( '%s in der Übersicht', 'spezialist-directory' ), single_term_title( '', false ) ); ?></h2>
        <!-- Results Bar with View Toggle -->
        <div class="sd-results-bar">
            <p class="sd-results-count">
                <?php
                global $wp_query;
                printf(
                    _n( '%d Ergebnis', '%d Ergebnisse', $wp_query->found_posts, 'spezialist-directory' ),
                    $wp_query->found_posts
                );
                ?>
            </p>

            <!-- View Toggle (Grid/List only) -->
            <div class="sd-view-toggle" role="group" aria-label="<?php esc_attr_e( 'Ansicht wechseln', 'spezialist-directory' ); ?>">
                <button type="button"
                        class="sd-view-btn sd-view-btn-grid active"
                        data-view="grid"
                        aria-pressed="true"
                        title="<?php esc_attr_e( 'Rasteransicht', 'spezialist-directory' ); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 3h8v8H3V3zm0 10h8v8H3v-8zm10-10h8v8h-8V3zm0 10h8v8h-8v-8z" fill="currentColor"/>
                    </svg>
                </button>
                <button type="button"
                        class="sd-view-btn sd-view-btn-list"
                        data-view="list"
                        aria-pressed="false"
                        title="<?php esc_attr_e( 'Listenansicht', 'spezialist-directory' ); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Listings Grid -->
        <div class="sd-listings-grid" id="sd-listings-grid">
            <?php while ( have_posts() ) : the_post();
                $post_id = get_the_ID();
                $is_premium = SD_Premium_Features::is_premium( $post_id );
                $is_verified = SD_Premium_Features::is_verified( $post_id );
                $phone = get_post_meta( $post_id, '_sd_phone', true );
                $email = get_post_meta( $post_id, '_sd_email', true );
                $website = get_post_meta( $post_id, '_sd_website', true );
                $city = get_post_meta( $post_id, '_sd_city', true );
                $neighborhood = get_post_meta( $post_id, '_sd_neighborhood', true );
                $address = get_post_meta( $post_id, '_sd_address', true );
                $latitude = get_post_meta( $post_id, '_sd_latitude', true );
                $longitude = get_post_meta( $post_id, '_sd_longitude', true );
                $categories = wp_get_object_terms( $post_id, 'spezialist_category' );
                $first_category = ! empty( $categories ) ? $categories[0]->name : '';

                // SEO-optimized alt text for listing images
                $listing_alt_text = sprintf(
                    '%s - %s%s',
                    get_the_title(),
                    $first_category ? $first_category . ' in ' : '',
                    $city ?: 'Nürnberg'
                );
            ?>
                <article class="sd-listing-card <?php echo $is_premium ? 'sd-listing-premium' : ''; ?>"
                         data-post-id="<?php echo esc_attr( $post_id ); ?>"
                         <?php if ( $latitude && $longitude ) : ?>
                         data-lat="<?php echo esc_attr( $latitude ); ?>"
                         data-lng="<?php echo esc_attr( $longitude ); ?>"
                         <?php endif; ?>>
                    <?php
                    /**
                     * Hook: sd_listing_card_rating
                     * Used by spezialist-ratings plugin to display rating badge
                     *
                     * @param int $post_id The listing post ID
                     */
                    do_action( 'sd_listing_card_rating', $post_id );
                    ?>

                    <!-- Bookmark Button -->
                    <button type="button"
                            class="sd-bookmark-btn"
                            data-post-id="<?php echo esc_attr( $post_id ); ?>"
                            title="<?php esc_attr_e( 'Zur Merkliste hinzufügen', 'spezialist-directory' ); ?>">
                        <svg class="sd-bookmark-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2zm0 15l-5-2.18L7 18V5h10v13z" fill="currentColor"/>
                        </svg>
                        <svg class="sd-bookmark-icon-filled" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" fill="currentColor"/>
                        </svg>
                    </button>

                    <div class="sd-listing-image">
                        <a href="<?php the_permalink(); ?>">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium', array(
                                    'class'   => 'sd-listing-thumbnail',
                                    'loading' => 'lazy',
                                    'alt'     => esc_attr( $listing_alt_text ),
                                ) ); ?>
                            <?php else : ?>
                                <img src="<?php echo esc_url( home_url( '/wp-content/uploads/2025/11/placeholder.webp' ) ); ?>"
                                     alt="<?php echo esc_attr( $listing_alt_text ); ?>"
                                     class="sd-listing-thumbnail"
                                     loading="lazy" />
                            <?php endif; ?>
                        </a>
                    </div>

                    <div class="sd-listing-content">
                        <h3 class="sd-listing-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <!-- Meta row: Neighborhood -->
                        <div class="sd-listing-meta">
                            <?php if ( $neighborhood ) :
                                $location_url = add_query_arg( 'sd_location', urlencode( $neighborhood ), home_url( '/' ) );
                            ?>
                                <a href="<?php echo esc_url( $location_url ); ?>" class="sd-listing-meta-location" title="<?php printf( esc_attr__( 'Alle Spezialisten in %s anzeigen', 'spezialist-directory' ), $neighborhood ); ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13 15.87 2 12 2ZM12 11.5C10.62 11.5 9.5 10.38 9.5 9C9.5 7.62 10.62 6.5 12 6.5C13.38 6.5 14.5 7.62 14.5 9C14.5 10.38 13.38 11.5 12 11.5Z" fill="currentColor"/>
                                    </svg>
                                    <?php echo esc_html( $neighborhood ); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ( $is_verified ) : ?>
                                <?php echo SD_Premium_Features::get_verified_badge( $post_id ); ?>
                            <?php endif; ?>
                            <?php if ( $is_premium ) : ?>
                                <div class="sd-premium-badge-container">
                                    <?php echo SD_Premium_Features::get_premium_badge( $post_id ); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- All categories row -->
                        <div class="sd-listing-categories">
                            <?php if ( ! empty( $categories ) ) : ?>
                                <?php foreach ( $categories as $category ) :
                                    $category_url = add_query_arg( 'sd_category', urlencode( $category->slug ), home_url( '/' ) );
                                ?>
                                    <a href="<?php echo esc_url( $category_url ); ?>" class="sd-category-badge" title="<?php printf( esc_attr__( 'Alle %s anzeigen', 'spezialist-directory' ), $category->name ); ?>"><?php echo esc_html( $category->name ); ?></a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="sd-listing-excerpt">
                            <?php echo wp_trim_words( get_the_excerpt(), 15 ); ?>
                        </div>

                        <!-- Quick Actions -->
                        <div class="sd-listing-actions">
                            <?php if ( $phone ) : ?>
                                <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"
                                   class="sd-action-btn"
                                   title="<?php esc_attr_e( 'Anrufen', 'spezialist-directory' ); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" fill="currentColor"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            <?php if ( $website ) : ?>
                                <a href="<?php echo esc_url( $website ); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="sd-action-btn"
                                   title="<?php esc_attr_e( 'Website', 'spezialist-directory' ); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" fill="currentColor"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            <?php if ( $address && $city ) : ?>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode( $address . ', ' . $city ); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="sd-action-btn"
                                   title="<?php esc_attr_e( 'Route', 'spezialist-directory' ); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21.71 11.29l-9-9c-.39-.39-1.02-.39-1.41 0l-9 9c-.39.39-.39 1.02 0 1.41l9 9c.39.39 1.02.39 1.41 0l9-9c.39-.38.39-1.01 0-1.41zM14 14.5V12h-4v3H8v-4c0-.55.45-1 1-1h5V7.5l3.5 3.5-3.5 3.5z" fill="currentColor"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            <a href="<?php the_permalink(); ?>" class="sd-action-btn sd-action-btn-primary" title="<?php esc_attr_e( 'Details', 'spezialist-directory' ); ?>">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" fill="currentColor"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ( $wp_query->max_num_pages > 1 ) : ?>
            <div class="sd-pagination">
                <?php
                echo paginate_links( array(
                    'total'     => $wp_query->max_num_pages,
                    'current'   => $paged,
                    'prev_text' => __( '&laquo; Zurück', 'spezialist-directory' ),
                    'next_text' => __( 'Weiter &raquo;', 'spezialist-directory' ),
                    'type'      => 'list',
                ) );
                ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <div class="sd-no-results">
            <h3><?php _e( 'Keine Spezialisten gefunden', 'spezialist-directory' ); ?></h3>
            <p><?php _e( 'In dieser Kategorie gibt es noch keine Einträge.', 'spezialist-directory' ); ?></p>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sd-button sd-button-primary">
                <?php _e( 'Alle Spezialisten anzeigen', 'spezialist-directory' ); ?>
            </a>
        </div>
    <?php endif; ?>
    </div><!-- .sd-taxonomy-archive-container -->
</div><!-- .sd-taxonomy-archive-wrapper -->

<?php get_footer(); ?>
