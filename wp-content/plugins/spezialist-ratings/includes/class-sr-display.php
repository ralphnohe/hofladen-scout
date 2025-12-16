<?php
/**
 * SR_Display Class
 *
 * Handles frontend display via hooks
 *
 * @package Spezialist_Ratings
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SR_Display Class
 */
class SR_Display {

    /**
     * Single instance
     *
     * @var SR_Display
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SR_Display
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into existing templates
        add_action( 'sd_listing_card_rating', array( $this, 'render_card_badge' ), 10, 1 );
        add_action( 'sd_detail_hero_rating', array( $this, 'render_hero_badge' ), 10, 1 );
        add_action( 'sd_detail_tabs_after', array( $this, 'render_tab_button' ), 10, 1 );
        add_action( 'sd_detail_tab_content_after', array( $this, 'render_tab_content' ), 10, 1 );

        // Register shortcode for recent reviews feed
        add_shortcode( 'spezialist_recent_reviews', array( $this, 'render_recent_reviews_shortcode' ) );
    }

    /**
     * Render rating badge on listing cards
     *
     * @param int $post_id
     */
    public function render_card_badge( $post_id ) {
        $average = SR_Ratings::get_average( $post_id );
        $count = SR_Ratings::get_count( $post_id );
        $has_ratings = $count > 0;

        $badge_class = $has_ratings ? 'sr-rating-badge sr-filled' : 'sr-rating-badge sr-empty';
        $link_url = get_permalink( $post_id ) . '?tab=bewertungen';
        ?>
        <a href="<?php echo esc_url( $link_url ); ?>" class="<?php echo esc_attr( $badge_class ); ?>" title="<?php echo $has_ratings ? sprintf( esc_attr__( '%s von 5 Sternen (%d Bewertungen)', 'spezialist-ratings' ), $average, $count ) : esc_attr__( 'Noch keine Bewertungen', 'spezialist-ratings' ); ?>">
            <?php if ( $has_ratings ) : ?>
                <svg class="sr-star-icon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                </svg>
                <span class="sr-value"><?php echo esc_html( $average ); ?></span>
            <?php else : ?>
                <svg class="sr-star-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                </svg>
            <?php endif; ?>
        </a>
        <?php
    }

    /**
     * Render rating badge in detail page hero section
     *
     * @param int $post_id
     */
    public function render_hero_badge( $post_id ) {
        $average = SR_Ratings::get_average( $post_id );
        $count = SR_Ratings::get_count( $post_id );

        if ( $count > 0 ) :
        ?>
        <div class="sr-hero-rating">
            <svg class="sr-star-icon" width="18" height="18" viewBox="0 0 24 24" fill="#F59E0B">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
            </svg>
            <span class="sr-rating-value"><?php echo esc_html( $average ); ?></span>
            <span class="sr-rating-count">(<?php echo esc_html( $count ); ?>)</span>
        </div>
        <?php
        endif;
    }

    /**
     * Render Bewertungen tab button
     *
     * @param int $post_id
     */
    public function render_tab_button( $post_id ) {
        $count = SR_Ratings::get_count( $post_id );
        ?>
        <button type="button" class="sd-tab" data-tab="bewertungen">
            <?php _e( 'Bewertungen', 'spezialist-ratings' ); ?><?php if ( $count > 0 ) : ?> (<?php echo esc_html( $count ); ?>)<?php endif; ?>
        </button>
        <?php
    }

    /**
     * Render Bewertungen tab content
     *
     * @param int $post_id
     */
    public function render_tab_content( $post_id ) {
        include SR_PLUGIN_DIR . 'templates/rating-tab.php';
    }

    /**
     * Get login URL with redirect back to ratings tab
     *
     * @param int $post_id
     * @return string
     */
    public static function get_login_url( $post_id ) {
        $return_url = add_query_arg( 'tab', 'bewertungen', get_permalink( $post_id ) );

        if ( function_exists( 'sd_get_page_url' ) ) {
            return add_query_arg( 'redirect_to', urlencode( $return_url ), sd_get_page_url( 'anmelden/' ) );
        }

        return wp_login_url( $return_url );
    }

    /**
     * Render recent reviews shortcode
     *
     * Usage: [spezialist_recent_reviews limit="10" title="Aktuelle Bewertungen"]
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_recent_reviews_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 10,
            'title' => __( 'Aktuelle Bewertungen', 'spezialist-ratings' ),
            'show_title' => 'yes',
        ), $atts, 'spezialist_recent_reviews' );

        $limit = intval( $atts['limit'] );
        $reviews = SR_Ratings::get_recent_reviews( $limit );

        if ( empty( $reviews ) ) {
            return '<div class="sr-recent-reviews-empty">' .
                   '<p>' . __( 'Noch keine Bewertungen vorhanden.', 'spezialist-ratings' ) . '</p>' .
                   '</div>';
        }

        ob_start();
        ?>
        <div class="sr-recent-reviews">
            <?php if ( $atts['show_title'] === 'yes' && ! empty( $atts['title'] ) ) : ?>
                <h3 class="sr-recent-reviews-title"><?php echo esc_html( $atts['title'] ); ?></h3>
            <?php endif; ?>

            <div class="sr-reviews-feed">
                <?php foreach ( $reviews as $review ) :
                    $post_url = get_permalink( $review->post_id );
                    $time_ago = human_time_diff( strtotime( $review->created_at ), current_time( 'timestamp' ) );
                    $avatar_url = get_avatar_url( $review->user_id, array( 'size' => 48 ) );
                ?>
                    <div class="sr-review-item">
                        <div class="sr-review-header">
                            <div class="sr-review-avatar">
                                <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $review->user_name ); ?>" loading="lazy" />
                            </div>
                            <div class="sr-review-meta">
                                <div class="sr-review-user"><?php echo esc_html( $review->user_name ); ?></div>
                                <div class="sr-review-info">
                                    <?php echo SR_Ratings::render_stars( $review->rating, 14 ); ?>
                                    <span class="sr-review-time"><?php echo esc_html( sprintf( __( 'vor %s', 'spezialist-ratings' ), $time_ago ) ); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="sr-review-content">
                            <p><?php echo esc_html( wp_trim_words( $review->comment, 40, '...' ) ); ?></p>
                        </div>
                        <div class="sr-review-listing">
                            <a href="<?php echo esc_url( $post_url ); ?>?tab=bewertungen">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z" fill="currentColor"/>
                                    <path d="M7 12h2v5H7v-5zm4-3h2v8h-2V9zm4 5h2v3h-2v-3z" fill="currentColor"/>
                                </svg>
                                <?php echo esc_html( $review->post_title ); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
