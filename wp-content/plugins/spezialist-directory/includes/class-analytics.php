<?php
/**
 * Analytics Tracking Class
 *
 * Tracks listing views and contact actions (phone, email, website, directions)
 * Stores data in WordPress post meta for simplicity
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SD_Analytics
 *
 * Handles analytics tracking for specialist listings
 */
class SD_Analytics {

    /**
     * Singleton instance
     *
     * @var SD_Analytics
     */
    private static $instance = null;

    /**
     * Cookie name for view tracking
     */
    const VIEW_COOKIE = 'sd_viewed_listings';

    /**
     * Cookie expiration (24 hours)
     */
    const COOKIE_EXPIRY = 86400;

    /**
     * Meta keys
     */
    const META_VIEWS = '_sd_views_count';
    const META_VIEWS_WEEKLY = '_sd_views_weekly';
    const META_CONTACT_PHONE = '_sd_contact_phone';
    const META_CONTACT_EMAIL = '_sd_contact_email';
    const META_CONTACT_WEBSITE = '_sd_contact_website';
    const META_CONTACT_DIRECTIONS = '_sd_contact_directions';

    /**
     * Get singleton instance
     *
     * @return SD_Analytics
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action( 'wp_ajax_sd_log_view', array( $this, 'ajax_log_view' ) );
        add_action( 'wp_ajax_nopriv_sd_log_view', array( $this, 'ajax_log_view' ) );
        add_action( 'wp_ajax_sd_log_contact', array( $this, 'ajax_log_contact' ) );
        add_action( 'wp_ajax_nopriv_sd_log_contact', array( $this, 'ajax_log_contact' ) );

        // Daily cleanup of weekly views (via WP Cron)
        add_action( 'sd_daily_analytics_cleanup', array( $this, 'cleanup_weekly_views' ) );
        if ( ! wp_next_scheduled( 'sd_daily_analytics_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'sd_daily_analytics_cleanup' );
        }
    }

    /**
     * AJAX handler for view tracking
     */
    public function ajax_log_view() {
        // Security fix: Verify nonce to prevent CSRF attacks
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_analytics_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' ) ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $post_id || get_post_type( $post_id ) !== 'spezialist' ) {
            wp_send_json_error( array( 'message' => 'Invalid post' ) );
        }

        // Check if this view should be counted (not a duplicate)
        if ( $this->should_count_view( $post_id ) ) {
            $this->log_view( $post_id );
            $this->mark_as_viewed( $post_id );
            wp_send_json_success( array( 'counted' => true ) );
        } else {
            wp_send_json_success( array( 'counted' => false ) );
        }
    }

    /**
     * AJAX handler for contact tracking
     */
    public function ajax_log_contact() {
        // Security fix: Verify nonce to prevent CSRF attacks
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_analytics_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' ) ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';

        if ( ! $post_id || get_post_type( $post_id ) !== 'spezialist' ) {
            wp_send_json_error( array( 'message' => 'Invalid post' ) );
        }

        $valid_types = array( 'phone', 'email', 'website', 'directions' );
        if ( ! in_array( $type, $valid_types, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid contact type' ) );
        }

        $this->log_contact( $post_id, $type );
        wp_send_json_success( array( 'logged' => true ) );
    }

    /**
     * Check if view should be counted (prevent duplicates)
     *
     * @param int $post_id
     * @return bool
     */
    private function should_count_view( $post_id ) {
        // Don't count views from post authors
        $post = get_post( $post_id );
        if ( $post && is_user_logged_in() && get_current_user_id() === (int) $post->post_author ) {
            return false;
        }

        // Security fix: Safe cookie JSON handling with proper validation
        $viewed = $this->get_viewed_from_cookie();

        if ( is_array( $viewed ) && in_array( $post_id, $viewed, true ) ) {
            return false;
        }

        return true;
    }

    /**
     * Safely get viewed listings from cookie with proper JSON validation
     *
     * @return array
     */
    private function get_viewed_from_cookie() {
        if ( ! isset( $_COOKIE[ self::VIEW_COOKIE ] ) ) {
            return array();
        }

        $cookie_value = wp_unslash( $_COOKIE[ self::VIEW_COOKIE ] );

        // Validate it looks like JSON before decoding
        if ( empty( $cookie_value ) || $cookie_value[0] !== '[' ) {
            return array();
        }

        $decoded = json_decode( $cookie_value, true );

        // Check for JSON errors
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return array();
        }

        // Ensure it's an array of integers
        if ( ! is_array( $decoded ) ) {
            return array();
        }

        // Sanitize: only keep valid integer post IDs
        return array_filter( array_map( 'absint', $decoded ), function( $id ) {
            return $id > 0;
        } );
    }

    /**
     * Mark listing as viewed in cookie
     *
     * @param int $post_id
     */
    private function mark_as_viewed( $post_id ) {
        // Security fix: Use safe cookie reading method
        $viewed = $this->get_viewed_from_cookie();

        $viewed[] = absint( $post_id );

        // Keep only last 100 viewed listings
        if ( count( $viewed ) > 100 ) {
            $viewed = array_slice( $viewed, -100 );
        }

        // Set cookie (JavaScript will handle this on client side)
    }

    /**
     * Log a view for a listing
     *
     * @param int $post_id
     */
    public function log_view( $post_id ) {
        // Increment total views
        $views = (int) get_post_meta( $post_id, self::META_VIEWS, true );
        update_post_meta( $post_id, self::META_VIEWS, $views + 1 );

        // Update weekly views
        $today = date( 'Y-m-d' );
        $weekly = get_post_meta( $post_id, self::META_VIEWS_WEEKLY, true );

        if ( ! is_array( $weekly ) ) {
            $weekly = array();
        }

        if ( isset( $weekly[ $today ] ) ) {
            $weekly[ $today ]++;
        } else {
            $weekly[ $today ] = 1;
        }

        // Keep only last 7 days
        $weekly = $this->trim_weekly_data( $weekly );
        update_post_meta( $post_id, self::META_VIEWS_WEEKLY, $weekly );
    }

    /**
     * Log a contact action
     *
     * @param int $post_id
     * @param string $type phone|email|website|directions
     */
    public function log_contact( $post_id, $type ) {
        $meta_key = '';
        switch ( $type ) {
            case 'phone':
                $meta_key = self::META_CONTACT_PHONE;
                break;
            case 'email':
                $meta_key = self::META_CONTACT_EMAIL;
                break;
            case 'website':
                $meta_key = self::META_CONTACT_WEBSITE;
                break;
            case 'directions':
                $meta_key = self::META_CONTACT_DIRECTIONS;
                break;
        }

        if ( $meta_key ) {
            $count = (int) get_post_meta( $post_id, $meta_key, true );
            update_post_meta( $post_id, $meta_key, $count + 1 );
        }
    }

    /**
     * Get all stats for a listing
     *
     * @param int $post_id
     * @return array
     */
    public static function get_stats( $post_id ) {
        return array(
            'views'          => (int) get_post_meta( $post_id, self::META_VIEWS, true ),
            'views_weekly'   => self::get_weekly_views( $post_id ),
            'contact_phone'  => (int) get_post_meta( $post_id, self::META_CONTACT_PHONE, true ),
            'contact_email'  => (int) get_post_meta( $post_id, self::META_CONTACT_EMAIL, true ),
            'contact_website'=> (int) get_post_meta( $post_id, self::META_CONTACT_WEBSITE, true ),
            'contact_directions' => (int) get_post_meta( $post_id, self::META_CONTACT_DIRECTIONS, true ),
        );
    }

    /**
     * Get weekly views data for a listing
     *
     * @param int $post_id
     * @return array Array with dates as keys and view counts as values
     */
    public static function get_weekly_views( $post_id ) {
        $weekly = get_post_meta( $post_id, self::META_VIEWS_WEEKLY, true );

        if ( ! is_array( $weekly ) ) {
            $weekly = array();
        }

        // Ensure we have all 7 days
        $result = array();
        for ( $i = 6; $i >= 0; $i-- ) {
            $date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
            $result[ $date ] = isset( $weekly[ $date ] ) ? (int) $weekly[ $date ] : 0;
        }

        return $result;
    }

    /**
     * Get total contact actions for a listing
     *
     * @param int $post_id
     * @return int
     */
    public static function get_total_contacts( $post_id ) {
        $stats = self::get_stats( $post_id );
        return $stats['contact_phone'] + $stats['contact_email'] + $stats['contact_website'] + $stats['contact_directions'];
    }

    /**
     * Trim weekly data to last 7 days
     *
     * @param array $weekly
     * @return array
     */
    private function trim_weekly_data( $weekly ) {
        $cutoff = date( 'Y-m-d', strtotime( '-7 days' ) );

        foreach ( $weekly as $date => $count ) {
            if ( $date < $cutoff ) {
                unset( $weekly[ $date ] );
            }
        }

        return $weekly;
    }

    /**
     * Cleanup old weekly views data (daily cron job)
     */
    public function cleanup_weekly_views() {
        global $wpdb;

        $cutoff = date( 'Y-m-d', strtotime( '-7 days' ) );

        // Get all posts with weekly views data
        $posts = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
                self::META_VIEWS_WEEKLY
            )
        );

        foreach ( $posts as $post_id ) {
            $weekly = get_post_meta( $post_id, self::META_VIEWS_WEEKLY, true );
            if ( is_array( $weekly ) ) {
                $updated = $this->trim_weekly_data( $weekly );
                if ( $updated !== $weekly ) {
                    update_post_meta( $post_id, self::META_VIEWS_WEEKLY, $updated );
                }
            }
        }
    }

    /**
     * Get aggregated stats for multiple listings
     *
     * @param array $post_ids
     * @return array
     */
    public static function get_aggregated_stats( $post_ids ) {
        $totals = array(
            'views'          => 0,
            'contact_phone'  => 0,
            'contact_email'  => 0,
            'contact_website'=> 0,
            'contact_directions' => 0,
        );

        foreach ( $post_ids as $post_id ) {
            $stats = self::get_stats( $post_id );
            $totals['views'] += $stats['views'];
            $totals['contact_phone'] += $stats['contact_phone'];
            $totals['contact_email'] += $stats['contact_email'];
            $totals['contact_website'] += $stats['contact_website'];
            $totals['contact_directions'] += $stats['contact_directions'];
        }

        return $totals;
    }

    /**
     * Get popularity ranking for a listing
     *
     * Returns the position of a listing when sorted by views
     *
     * @param int $post_id
     * @return array [ 'position' => int, 'total' => int ]
     */
    public static function get_popularity_ranking( $post_id ) {
        global $wpdb;

        // Get the views count for this listing
        $listing_views = (int) get_post_meta( $post_id, self::META_VIEWS, true );

        // Count how many published listings have more views
        $higher_ranked = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
             WHERE p.post_type = 'spezialist'
               AND p.post_status = 'publish'
               AND (CAST(pm.meta_value AS UNSIGNED) > %d OR (pm.meta_value IS NULL AND %d > 0))",
            self::META_VIEWS,
            $listing_views,
            $listing_views
        ) );

        // Get total count of published listings
        $total = $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$wpdb->posts}
             WHERE post_type = 'spezialist'
               AND post_status = 'publish'"
        );

        return array(
            'position' => (int) $higher_ranked + 1,
            'total'    => (int) $total,
        );
    }

    /**
     * Get ranking data for multiple listings (for dashboard)
     *
     * @param array $post_ids
     * @return array Keyed by post_id
     */
    public static function get_rankings_for_listings( $post_ids ) {
        $rankings = array();

        foreach ( $post_ids as $post_id ) {
            $rankings[ $post_id ] = self::get_popularity_ranking( $post_id );
        }

        return $rankings;
    }
}
