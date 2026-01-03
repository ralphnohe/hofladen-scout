<?php
/**
 * Premium Features
 *
 * Handles premium listing features and benefits
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SD_Premium_Features Class
 */
class SD_Premium_Features {

    /**
     * Single instance
     *
     * @var SD_Premium_Features
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SD_Premium_Features
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
        add_filter( 'body_class', array( $this, 'add_premium_body_class' ) );
        add_filter( 'post_class', array( $this, 'add_premium_post_class' ), 10, 3 );
        // Schema moved to spezialist-seo plugin for ALL listings (premium and non-premium)
        // add_action( 'wp_head', array( $this, 'add_premium_structured_data' ) );

        // Cron job to check expired premium subscriptions
        add_action( 'sd_check_expired_premiums', array( $this, 'check_expired_premiums' ) );

        // Schedule cron if not scheduled
        if ( ! wp_next_scheduled( 'sd_check_expired_premiums' ) ) {
            wp_schedule_event( time(), 'daily', 'sd_check_expired_premiums' );
        }
    }

    /**
     * Check if a listing is verified
     *
     * @param int $post_id
     * @return bool
     */
    public static function is_verified( $post_id = null ) {
        if ( null === $post_id ) {
            $post_id = get_the_ID();
        }

        return '1' === get_post_meta( $post_id, '_sd_verified_listing', true );
    }

    /**
     * Get verified badge HTML
     *
     * @param int $post_id
     * @return string
     */
    public static function get_verified_badge( $post_id = null ) {
        if ( ! self::is_verified( $post_id ) ) {
            return '';
        }

        return '<span class="sd-verified-badge" title="' . esc_attr__( 'Verifizierter Eintrag', 'spezialist-directory' ) . '">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z" fill="currentColor"/>
                    </svg>
                </span>';
    }

    /**
     * Check if a listing is premium
     *
     * @param int $post_id
     * @return bool
     */
    public static function is_premium( $post_id = null ) {
        if ( null === $post_id ) {
            $post_id = get_the_ID();
        }

        $is_premium = get_post_meta( $post_id, '_sd_is_premium', true );
        $premium_until = get_post_meta( $post_id, '_sd_premium_until', true );

        // Check if premium is still valid
        if ( $is_premium && ! empty( $premium_until ) ) {
            $until_timestamp = strtotime( $premium_until );
            if ( $until_timestamp < time() ) {
                // Premium expired
                return false;
            }
        }

        return '1' === $is_premium;
    }

    /**
     * Get premium badge HTML
     *
     * @param int $post_id
     * @return string
     */
    public static function get_premium_badge( $post_id = null ) {
        if ( ! self::is_premium( $post_id ) ) {
            return '';
        }

        return '<span class="sd-premium-badge" title="' . esc_attr__( 'Empfohlener Eintrag', 'spezialist-directory' ) . '">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 15L3.5 18L5.5 11L0 6.5L7.5 6L10 0L12.5 6L20 6.5L14.5 11L16.5 18L10 15Z" fill="currentColor"/>
                    </svg>
                    ' . esc_html__( 'Empfohlen', 'spezialist-directory' ) . '
                </span>';
    }

    /**
     * Get premium benefits
     *
     * @return array
     */
    public static function get_premium_benefits() {
        return array(
            __( 'Hervorgehobene Anzeige in den Suchergebnissen', 'spezialist-directory' ),
            __( 'Erscheint ganz oben in der Liste', 'spezialist-directory' ),
            __( 'Visuell hervorgehoben mit Premium-Badge', 'spezialist-directory' ),
            __( 'Bessere Sichtbarkeit für potenzielle Kunden', 'spezialist-directory' ),
            __( 'Priorität in Kategorie-Übersichten', 'spezialist-directory' ),
        );
    }

    /**
     * Add premium body class
     *
     * @param array $classes
     * @return array
     */
    public function add_premium_body_class( $classes ) {
        if ( is_singular( 'hofladen' ) && self::is_premium() ) {
            $classes[] = 'sd-premium-listing';
        }

        return $classes;
    }

    /**
     * Add premium post class
     *
     * @param array $classes
     * @param string $class
     * @param int $post_id
     * @return array
     */
    public function add_premium_post_class( $classes, $class, $post_id ) {
        if ( 'hofladen' === get_post_type( $post_id ) && self::is_premium( $post_id ) ) {
            $classes[] = 'sd-listing-premium';
        }

        return $classes;
    }

    /**
     * Add premium structured data
     */
    public function add_premium_structured_data() {
        if ( ! is_singular( 'hofladen' ) || ! self::is_premium() ) {
            return;
        }

        $post_id = get_the_ID();

        $structured_data = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'ProfessionalService',
            'name'        => get_the_title(),
            'description' => wp_strip_all_tags( get_the_excerpt() ),
            'url'         => get_permalink(),
        );

        // Add contact information
        $phone = get_post_meta( $post_id, '_sd_phone', true );
        $email = get_post_meta( $post_id, '_sd_email', true );

        if ( $phone || $email ) {
            $structured_data['contactPoint'] = array(
                '@type' => 'ContactPoint',
            );

            if ( $phone ) {
                $structured_data['contactPoint']['telephone'] = $phone;
            }

            if ( $email ) {
                $structured_data['contactPoint']['email'] = $email;
            }
        }

        // Add address
        $address = get_post_meta( $post_id, '_sd_address', true );
        $city = get_post_meta( $post_id, '_sd_city', true );
        $zip = get_post_meta( $post_id, '_sd_zip', true );

        if ( $address && $city && $zip ) {
            $structured_data['address'] = array(
                '@type'           => 'PostalAddress',
                'streetAddress'   => $address,
                'addressLocality' => $city,
                'postalCode'      => $zip,
                'addressCountry'  => 'DE',
            );
        }

        // Add image
        if ( has_post_thumbnail() ) {
            $structured_data['image'] = get_the_post_thumbnail_url( $post_id, 'large' );
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $structured_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>';
    }

    /**
     * Check and update expired premium listings
     */
    public function check_expired_premiums() {
        global $wpdb;

        $current_time = current_time( 'mysql' );

        // Find all posts with expired premium
        $expired_posts = $wpdb->get_results( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_sd_premium_until'
            AND meta_value < %s
            AND post_id IN (
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_sd_is_premium'
                AND meta_value = '1'
            )",
            $current_time
        ) );

        foreach ( $expired_posts as $post ) {
            // Remove premium status
            update_post_meta( $post->post_id, '_sd_is_premium', '0' );

            // Notify user
            $this->send_premium_expired_notification( $post->post_id );
        }
    }

    /**
     * Send premium expired notification
     *
     * @param int $post_id
     */
    private function send_premium_expired_notification( $post_id ) {
        $post = get_post( $post_id );
        $author = get_user_by( 'id', $post->post_author );

        if ( ! $author ) {
            return;
        }

        $subject = sprintf(
            __( '[%s] Dein Premium-Listing ist abgelaufen', 'spezialist-directory' ),
            get_bloginfo( 'name' )
        );

        $message = sprintf(
            __( "Hallo %s,\n\nDein Premium-Listing für '%s' ist abgelaufen.\n\nUm weiterhin von den Vorteilen eines Premium-Listings zu profitieren, kannst du dein Abo im Dashboard erneuern:\n%s\n\nViele Grüße,\n%s", 'spezialist-directory' ),
            $author->display_name,
            $post->post_title,
            home_url( '/dashboard/' ),
            get_bloginfo( 'name' )
        );

        wp_mail( $author->user_email, $subject, $message );
    }

    /**
     * Get premium pricing display
     *
     * @return array
     */
    public static function get_pricing_options() {
        return array(
            'free' => array(
                'label'       => __( 'Standard', 'spezialist-directory' ),
                'price'       => '0€',
                'period'      => '',
                'description' => __( 'Kostenlos', 'spezialist-directory' ),
                'features'    => array(
                    __( 'Basis-Eintrag', 'spezialist-directory' ),
                    __( 'Kontaktdaten', 'spezialist-directory' ),
                    __( 'Beschreibung', 'spezialist-directory' ),
                ),
            ),
            'monthly' => array(
                'label'       => __( 'Premium Monatlich', 'spezialist-directory' ),
                'price'       => '9€',
                'period'      => __( 'pro Monat', 'spezialist-directory' ),
                'price_note'  => __( '+ MwSt.', 'spezialist-directory' ),
                'description' => __( 'Monatlich kündbar', 'spezialist-directory' ),
                'badge'       => __( 'Beliebt', 'spezialist-directory' ),
                'features'    => array(
                    __( 'Alles aus Standard', 'spezialist-directory' ),
                    __( 'Hervorgehobene Anzeige', 'spezialist-directory' ),
                    __( 'Empfohlen Badge', 'spezialist-directory' ),
                    __( 'Top-Position in Suchergebnissen', 'spezialist-directory' ),
                    __( 'Bildergalerie (bis zu 10 Bilder)', 'spezialist-directory' ),
                    __( 'Video-Upload (bis 10 MB)', 'spezialist-directory' ),
                    __( 'Angebotene Leistungen', 'spezialist-directory' ),
                    __( 'Öffnungszeiten', 'spezialist-directory' ),
                    __( 'Social Media Profile', 'spezialist-directory' ),
                    __( 'Verifizierter Eintrag (auf Wunsch)', 'spezialist-directory' ),
                ),
                'featured'    => true,
            ),
            'yearly' => array(
                'label'       => __( 'Premium Jährlich', 'spezialist-directory' ),
                'price'       => '80€',
                'period'      => __( 'pro Jahr', 'spezialist-directory' ),
                'price_note'  => __( '+ MwSt.', 'spezialist-directory' ),
                'description' => __( '2 Monate gratis!', 'spezialist-directory' ),
                'features'    => array(
                    __( 'Alles aus Premium Monatlich', 'spezialist-directory' ),
                    __( '2 Monate gespart', 'spezialist-directory' ),
                    __( 'Einmal jährlich zahlen', 'spezialist-directory' ),
                ),
            ),
        );
    }

    /**
     * Get premium stats for admin
     *
     * @return array
     */
    public static function get_premium_stats() {
        global $wpdb;

        $total_premium = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
            WHERE meta_key = '_sd_is_premium'
            AND meta_value = '1'"
        );

        $total_active_subscriptions = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
            WHERE meta_key = '_sd_stripe_subscription_id'
            AND meta_value != ''"
        );

        return array(
            'total_premium'              => intval( $total_premium ),
            'total_active_subscriptions' => intval( $total_active_subscriptions ),
        );
    }
}
