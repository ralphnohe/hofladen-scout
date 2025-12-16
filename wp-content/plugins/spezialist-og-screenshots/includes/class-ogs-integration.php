<?php
/**
 * OG Screenshots Integration Class
 *
 * Handles integration with the Spezialist SEO plugin
 *
 * @package Spezialist_OG_Screenshots
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OGS_Integration Class
 */
class OGS_Integration {

    /**
     * Single instance
     *
     * @var OGS_Integration
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return OGS_Integration
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
        // Hook into SEO plugin after it initializes
        add_action( 'spezialist_seo_init', array( $this, 'register_filters' ), 20 );

        // Also hook early in case SEO plugin is already loaded
        add_action( 'init', array( $this, 'register_filters' ), 99 );
    }

    /**
     * Register filters for OG and Twitter images
     */
    public function register_filters() {
        // Only register once
        static $registered = false;
        if ( $registered ) {
            return;
        }
        $registered = true;

        // Add filters to SEO Open Graph class
        add_filter( 'spezialist_seo_og_image', array( $this, 'filter_og_image' ), 10, 2 );

        // Add filters to SEO Twitter Cards class
        add_filter( 'spezialist_seo_twitter_image', array( $this, 'filter_twitter_image' ), 10, 2 );
    }

    /**
     * Filter OG image URL
     *
     * @param string $image_url Current image URL.
     * @param mixed  $context   Post object, WP_Term, or null.
     * @return string Filtered image URL.
     */
    public function filter_og_image( $image_url, $context = null ) {
        $screenshot_url = $this->get_context_screenshot_url( $context );
        return $screenshot_url ? $screenshot_url : $image_url;
    }

    /**
     * Filter Twitter image URL
     *
     * @param string $image_url Current image URL.
     * @param mixed  $context   Post object, WP_Term, or null.
     * @return string Filtered image URL.
     */
    public function filter_twitter_image( $image_url, $context = null ) {
        $screenshot_url = $this->get_context_screenshot_url( $context );
        return $screenshot_url ? $screenshot_url : $image_url;
    }

    /**
     * Determine screenshot URL based on current page context
     *
     * @param mixed $context Optional context object (post or term).
     * @return string|null Screenshot URL or null if not found.
     */
    private function get_context_screenshot_url( $context = null ) {
        $screenshot = OGS_Screenshot::instance();

        // If context is a WP_Term object
        if ( $context instanceof WP_Term ) {
            return $screenshot->get_screenshot_url( $context->term_id, 'term' );
        }

        // If context is a post ID
        if ( is_numeric( $context ) && $context > 0 ) {
            return $screenshot->get_screenshot_url( (int) $context, 'post' );
        }

        // Single post/page/spezialist
        if ( is_singular() ) {
            $post_id = get_the_ID();
            if ( $post_id ) {
                return $screenshot->get_screenshot_url( $post_id, 'post' );
            }
        }

        // Taxonomy archive
        if ( is_tax( 'spezialist_category' ) || is_tax( 'spezialist_location' ) || is_tax( 'spezialist_tag' ) ) {
            $term = get_queried_object();
            if ( $term instanceof WP_Term ) {
                return $screenshot->get_screenshot_url( $term->term_id, 'term' );
            }
        }

        // Front page (static page)
        if ( is_front_page() && ! is_home() ) {
            $page_id = get_option( 'page_on_front' );
            if ( $page_id ) {
                return $screenshot->get_screenshot_url( (int) $page_id, 'post' );
            }
        }

        // Blog page
        if ( is_home() && ! is_front_page() ) {
            $page_id = get_option( 'page_for_posts' );
            if ( $page_id ) {
                return $screenshot->get_screenshot_url( (int) $page_id, 'post' );
            }
        }

        // Standard page
        if ( is_page() ) {
            $post_id = get_the_ID();
            if ( $post_id ) {
                return $screenshot->get_screenshot_url( $post_id, 'post' );
            }
        }

        // Single post
        if ( is_single() ) {
            $post_id = get_the_ID();
            if ( $post_id ) {
                return $screenshot->get_screenshot_url( $post_id, 'post' );
            }
        }

        return null;
    }
}
