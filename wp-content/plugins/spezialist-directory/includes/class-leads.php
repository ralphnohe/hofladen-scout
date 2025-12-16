<?php
/**
 * Leads / Quote Request Handler
 *
 * Handles quote requests from potential customers to specialists
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SD_Leads
 *
 * Manages leads and quote requests
 */
class SD_Leads {

    /**
     * Custom post type name
     */
    const POST_TYPE = 'sd_lead';

    /**
     * Lead statuses
     */
    const STATUS_NEW = 'new';
    const STATUS_READ = 'read';
    const STATUS_REPLIED = 'replied';
    const STATUS_CLOSED = 'closed';

    /**
     * Singleton instance
     *
     * @var SD_Leads
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return SD_Leads
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
        // Register custom post type
        add_action( 'init', array( $this, 'register_post_type' ) );

        // AJAX handlers
        add_action( 'wp_ajax_sd_submit_quote_request', array( $this, 'handle_quote_request' ) );
        add_action( 'wp_ajax_nopriv_sd_submit_quote_request', array( $this, 'handle_quote_request' ) );
        add_action( 'wp_ajax_sd_get_leads', array( $this, 'handle_get_leads' ) );
        add_action( 'wp_ajax_sd_update_lead_status', array( $this, 'handle_update_lead_status' ) );
        add_action( 'wp_ajax_sd_get_lead_details', array( $this, 'handle_get_lead_details' ) );
    }

    /**
     * Register custom post type for leads
     */
    public function register_post_type() {
        $labels = array(
            'name'          => __( 'Anfragen', 'spezialist-directory' ),
            'singular_name' => __( 'Anfrage', 'spezialist-directory' ),
        );

        $args = array(
            'labels'       => $labels,
            'public'       => false,
            'show_ui'      => false,
            'show_in_menu' => false,
            'supports'     => array( 'title', 'editor' ),
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Handle quote request submission
     */
    public function handle_quote_request() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_quote_request' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        // Validate required fields
        $listing_id = isset( $_POST['listing_id'] ) ? intval( $_POST['listing_id'] ) : 0;
        $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
        $service = isset( $_POST['service'] ) ? sanitize_text_field( $_POST['service'] ) : '';

        $errors = array();

        if ( ! $listing_id ) {
            $errors[] = __( 'Ungültiger Eintrag.', 'spezialist-directory' );
        }

        if ( empty( $name ) ) {
            $errors[] = __( 'Bitte gib Deinen Namen ein.', 'spezialist-directory' );
        }

        if ( empty( $email ) || ! is_email( $email ) ) {
            $errors[] = __( 'Bitte gib eine gültige E-Mail-Adresse ein.', 'spezialist-directory' );
        }

        if ( empty( $message ) ) {
            $errors[] = __( 'Bitte gib eine Nachricht ein.', 'spezialist-directory' );
        }

        if ( ! empty( $errors ) ) {
            wp_send_json_error( array(
                'message' => implode( '<br>', $errors )
            ) );
        }

        // Get listing info
        $listing = get_post( $listing_id );
        if ( ! $listing || 'spezialist' !== $listing->post_type ) {
            wp_send_json_error( array(
                'message' => __( 'Hofladen nicht gefunden.', 'spezialist-directory' )
            ) );
        }

        // Create lead post
        $lead_title = sprintf(
            __( 'Anfrage von %s an %s', 'spezialist-directory' ),
            $name,
            $listing->post_title
        );

        $lead_id = wp_insert_post( array(
            'post_type'    => self::POST_TYPE,
            'post_title'   => $lead_title,
            'post_content' => $message,
            'post_status'  => 'publish',
        ) );

        if ( is_wp_error( $lead_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Fehler beim Speichern der Anfrage.', 'spezialist-directory' )
            ) );
        }

        // Save lead meta
        update_post_meta( $lead_id, '_sd_lead_listing_id', $listing_id );
        update_post_meta( $lead_id, '_sd_lead_name', $name );
        update_post_meta( $lead_id, '_sd_lead_email', $email );
        update_post_meta( $lead_id, '_sd_lead_phone', $phone );
        update_post_meta( $lead_id, '_sd_lead_service', $service );
        update_post_meta( $lead_id, '_sd_lead_status', self::STATUS_NEW );
        update_post_meta( $lead_id, '_sd_lead_created', current_time( 'mysql' ) );

        // Track analytics
        if ( class_exists( 'SD_Analytics' ) ) {
            SD_Analytics::instance()->log_contact( $listing_id, 'lead' );
        }

        // Send email notification to specialist
        $this->send_lead_notification( $lead_id, $listing_id );

        // Send confirmation to customer
        $this->send_customer_confirmation( $lead_id, $listing_id );

        wp_send_json_success( array(
            'message' => __( 'Deine Anfrage wurde erfolgreich gesendet! Der Hofladen wird sich in Kürze bei Dir melden.', 'spezialist-directory' )
        ) );
    }

    /**
     * Send email notification to specialist
     *
     * @param int $lead_id
     * @param int $listing_id
     */
    private function send_lead_notification( $lead_id, $listing_id ) {
        $listing = get_post( $listing_id );
        $specialist_email = get_post_meta( $listing_id, '_sd_email', true );

        // Fallback to author email
        if ( empty( $specialist_email ) ) {
            $author = get_userdata( $listing->post_author );
            if ( $author ) {
                $specialist_email = $author->user_email;
            }
        }

        if ( empty( $specialist_email ) ) {
            return;
        }

        $lead_name = get_post_meta( $lead_id, '_sd_lead_name', true );
        $lead_email = get_post_meta( $lead_id, '_sd_lead_email', true );
        $lead_phone = get_post_meta( $lead_id, '_sd_lead_phone', true );
        $lead_service = get_post_meta( $lead_id, '_sd_lead_service', true );
        $lead_message = get_post( $lead_id )->post_content;

        $subject = sprintf(
            __( '[%s] Neue Anfrage von %s', 'spezialist-directory' ),
            get_bloginfo( 'name' ),
            $lead_name
        );

        $dashboard_url = home_url( '/mein-dashboard/?tab=leads' );
        $site_name = get_bloginfo( 'name' );
        $listing_title = $listing->post_title;

        $body = self::get_lead_notification_html(
            $lead_name,
            $lead_email,
            $lead_phone,
            $lead_service,
            $lead_message,
            $dashboard_url,
            $site_name,
            $listing_title
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $specialist_email, $subject, $body, $headers );
    }

    /**
     * Send confirmation email to customer
     *
     * @param int $lead_id
     * @param int $listing_id
     */
    private function send_customer_confirmation( $lead_id, $listing_id ) {
        $listing = get_post( $listing_id );
        $lead_email = get_post_meta( $lead_id, '_sd_lead_email', true );
        $lead_name = get_post_meta( $lead_id, '_sd_lead_name', true );

        if ( empty( $lead_email ) ) {
            return;
        }

        $subject = sprintf(
            __( 'Deine Anfrage an %s wurde gesendet', 'spezialist-directory' ),
            $listing->post_title
        );

        $body = sprintf(
            __( "Hallo %s,\n\nvielen Dank für Deine Anfrage an %s über %s.\n\nDer Hofladen wurde über Deine Anfrage informiert und wird sich in Kürze bei Dir melden.\n\nMit freundlichen Grüßen,\nDein %s Team", 'spezialist-directory' ),
            $lead_name,
            $listing->post_title,
            get_bloginfo( 'name' ),
            get_bloginfo( 'name' )
        );

        wp_mail( $lead_email, $subject, $body );
    }

    /**
     * Get leads for a user's listings
     *
     * @param int $user_id
     * @return array
     */
    public static function get_user_leads( $user_id ) {
        // Get user's listings
        $listings = get_posts( array(
            'post_type'      => 'spezialist',
            'post_status'    => array( 'publish', 'pending', 'draft' ),
            'author'         => $user_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );

        // Also include claimed listings
        $claimed = get_posts( array(
            'post_type'      => 'spezialist',
            'post_status'    => array( 'publish', 'pending', 'draft' ),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_sd_claimed_by',
                    'value' => $user_id,
                ),
            ),
        ) );

        $listing_ids = array_unique( array_merge( $listings, $claimed ) );

        if ( empty( $listing_ids ) ) {
            return array();
        }

        // Get leads for these listings
        $leads = get_posts( array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_sd_lead_listing_id',
                    'value'   => $listing_ids,
                    'compare' => 'IN',
                ),
            ),
        ) );

        $result = array();
        foreach ( $leads as $lead ) {
            $listing_id = get_post_meta( $lead->ID, '_sd_lead_listing_id', true );
            $listing = get_post( $listing_id );

            $result[] = array(
                'id'           => $lead->ID,
                'listing_id'   => $listing_id,
                'listing_name' => $listing ? $listing->post_title : __( 'Gelöscht', 'spezialist-directory' ),
                'name'         => get_post_meta( $lead->ID, '_sd_lead_name', true ),
                'email'        => get_post_meta( $lead->ID, '_sd_lead_email', true ),
                'phone'        => get_post_meta( $lead->ID, '_sd_lead_phone', true ),
                'service'      => get_post_meta( $lead->ID, '_sd_lead_service', true ),
                'message'      => $lead->post_content,
                'status'       => get_post_meta( $lead->ID, '_sd_lead_status', true ) ?: self::STATUS_NEW,
                'date'         => get_the_date( 'd.m.Y H:i', $lead->ID ),
                'date_raw'     => $lead->post_date,
            );
        }

        return $result;
    }

    /**
     * Get new leads count for user
     *
     * @param int $user_id
     * @return int
     */
    public static function get_new_leads_count( $user_id ) {
        $leads = self::get_user_leads( $user_id );
        $count = 0;

        foreach ( $leads as $lead ) {
            if ( self::STATUS_NEW === $lead['status'] ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Handle AJAX request to get leads
     */
    public function handle_get_leads() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        $leads = self::get_user_leads( get_current_user_id() );

        wp_send_json_success( array(
            'leads' => $leads
        ) );
    }

    /**
     * Handle AJAX request to update lead status
     */
    public function handle_update_lead_status() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_leads_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        $lead_id = isset( $_POST['lead_id'] ) ? intval( $_POST['lead_id'] ) : 0;
        $status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

        if ( ! $lead_id || ! in_array( $status, array( self::STATUS_NEW, self::STATUS_READ, self::STATUS_REPLIED, self::STATUS_CLOSED ), true ) ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültige Daten.', 'spezialist-directory' )
            ) );
        }

        // Verify user owns this lead's listing
        $listing_id = get_post_meta( $lead_id, '_sd_lead_listing_id', true );
        if ( ! SD_User_Dashboard::user_can_edit_listing( $listing_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Keine Berechtigung.', 'spezialist-directory' )
            ) );
        }

        update_post_meta( $lead_id, '_sd_lead_status', $status );

        wp_send_json_success( array(
            'message' => __( 'Status aktualisiert.', 'spezialist-directory' )
        ) );
    }

    /**
     * Handle AJAX request to get lead details
     */
    public function handle_get_lead_details() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        $lead_id = isset( $_POST['lead_id'] ) ? intval( $_POST['lead_id'] ) : 0;

        if ( ! $lead_id ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültige Anfrage.', 'spezialist-directory' )
            ) );
        }

        // Verify user owns this lead's listing
        $listing_id = get_post_meta( $lead_id, '_sd_lead_listing_id', true );
        if ( ! SD_User_Dashboard::user_can_edit_listing( $listing_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Keine Berechtigung.', 'spezialist-directory' )
            ) );
        }

        $lead = get_post( $lead_id );
        $listing = get_post( $listing_id );

        // Mark as read if new
        $current_status = get_post_meta( $lead_id, '_sd_lead_status', true );
        if ( self::STATUS_NEW === $current_status ) {
            update_post_meta( $lead_id, '_sd_lead_status', self::STATUS_READ );
        }

        wp_send_json_success( array(
            'lead' => array(
                'id'           => $lead->ID,
                'listing_id'   => $listing_id,
                'listing_name' => $listing ? $listing->post_title : __( 'Gelöscht', 'spezialist-directory' ),
                'name'         => get_post_meta( $lead_id, '_sd_lead_name', true ),
                'email'        => get_post_meta( $lead_id, '_sd_lead_email', true ),
                'phone'        => get_post_meta( $lead_id, '_sd_lead_phone', true ),
                'service'      => get_post_meta( $lead_id, '_sd_lead_service', true ),
                'message'      => $lead->post_content,
                'status'       => get_post_meta( $lead_id, '_sd_lead_status', true ),
                'date'         => get_the_date( 'd.m.Y H:i', $lead_id ),
            )
        ) );
    }

    /**
     * Get status label
     *
     * @param string $status
     * @return string
     */
    public static function get_status_label( $status ) {
        $labels = array(
            self::STATUS_NEW     => __( 'Neu', 'spezialist-directory' ),
            self::STATUS_READ    => __( 'Gelesen', 'spezialist-directory' ),
            self::STATUS_REPLIED => __( 'Beantwortet', 'spezialist-directory' ),
            self::STATUS_CLOSED  => __( 'Abgeschlossen', 'spezialist-directory' ),
        );

        return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
    }

    /**
     * Generate HTML email template for lead notification
     *
     * @param string $lead_name
     * @param string $lead_email
     * @param string $lead_phone
     * @param string $lead_service
     * @param string $lead_message
     * @param string $dashboard_url
     * @param string $site_name
     * @param string $listing_title
     * @return string
     */
    private static function get_lead_notification_html( $lead_name, $lead_email, $lead_phone, $lead_service, $lead_message, $dashboard_url, $site_name, $listing_title ) {
        $phone_display = $lead_phone ?: '-';
        $service_row = '';
        if ( $lead_service ) {
            $service_row = '
                <tr>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; color: #6B7280; font-size: 14px; width: 140px;">Gewünschter Service</td>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; color: #111827; font-size: 14px;">' . esc_html( $lead_service ) . '</td>
                </tr>';
        }

        return '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neue Anfrage</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #F3F4F6; line-height: 1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F3F4F6;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; width: 100%;">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f1c232 0%, #e5b32a 100%); padding: 32px 40px; border-radius: 12px 12px 0 0; text-align: center;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700;"><a href="https://www.hofladen-scout.de" style="color: #1a1a1a; text-decoration: none;">' . esc_html( $site_name ) . '</a></h1>
                            <p style="margin: 8px 0 0; color: #1a1a1a; font-size: 14px; opacity: 0.8;">Die besten Hofläden in Deiner Nähe</p>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="background-color: #FFFFFF; padding: 40px;">

                            <!-- Badge -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center" style="padding-bottom: 24px;">
                                        <span style="display: inline-block; background: #FEF3C7; color: #92400E; font-size: 12px; font-weight: 600; padding: 6px 16px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">Neue Anfrage</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Greeting -->
                            <p style="margin: 0 0 16px; color: #111827; font-size: 16px;">Hallo,</p>
                            <p style="margin: 0 0 24px; color: #4B5563; font-size: 15px;">Du hast eine neue Kundenanfrage über Deinen Eintrag <strong style="color: #111827;">' . esc_html( $listing_title ) . '</strong> erhalten.</p>

                            <!-- Contact Details Card -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #F9FAFB; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="margin: 0 0 16px; color: #111827; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Kontaktdaten</h3>
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #FFFFFF; border-radius: 6px; border: 1px solid #E5E7EB;">
                                            <tr>
                                                <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; color: #6B7280; font-size: 14px; width: 140px;">Name</td>
                                                <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; color: #111827; font-size: 14px; font-weight: 500;">' . esc_html( $lead_name ) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; color: #6B7280; font-size: 14px;">E-Mail</td>
                                                <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; color: #111827; font-size: 14px;"><a href="mailto:' . esc_attr( $lead_email ) . '" style="color: #2563EB; text-decoration: none;">' . esc_html( $lead_email ) . '</a></td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; color: #6B7280; font-size: 14px;">Telefon</td>
                                                <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; color: #111827; font-size: 14px;">' . esc_html( $phone_display ) . '</td>
                                            </tr>
                                            ' . $service_row . '
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Message Card -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #F9FAFB; border-radius: 8px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="margin: 0 0 16px; color: #111827; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Nachricht</h3>
                                        <div style="background: #FFFFFF; border-radius: 6px; border: 1px solid #E5E7EB; padding: 16px;">
                                            <p style="margin: 0; color: #374151; font-size: 15px; line-height: 1.7; white-space: pre-wrap;">' . esc_html( $lead_message ) . '</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <a href="' . esc_url( $dashboard_url ) . '" style="display: inline-block; background: #1f2937; color: #FFFFFF; font-size: 15px; font-weight: 600; text-decoration: none; padding: 14px 32px; border-radius: 8px;">Im Dashboard ansehen</a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F9FAFB; padding: 24px 40px; border-radius: 0 0 12px 12px; border-top: 1px solid #E5E7EB;">
                            <p style="margin: 0 0 8px; color: #6B7280; font-size: 13px; text-align: center;">Mit freundlichen Grüßen,<br><strong style="color: #374151;">Dein <a href="https://www.hofladen-scout.de" style="color: #374151; text-decoration: none;">' . esc_html( $site_name ) . '</a> Team</strong></p>
                            <p style="margin: 0; color: #9CA3AF; font-size: 12px; text-align: center;">Diese E-Mail wurde automatisch versendet.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Send a test lead notification email
     *
     * @param string $to_email
     * @return bool
     */
    public static function send_test_notification( $to_email ) {
        $subject = sprintf(
            __( '[%s] Neue Anfrage von %s', 'spezialist-directory' ),
            get_bloginfo( 'name' ),
            'Max Mustermann'
        );

        $dashboard_url = 'https://www.hofladen-scout.de/mein-dashboard/?tab=leads';
        $site_name = get_bloginfo( 'name' );

        $body = self::get_lead_notification_html(
            'Max Mustermann',
            'max.mustermann@example.com',
            '+49 123 456789',
            'Beratung',
            "Hallo,\n\nich interessiere mich für Ihre Dienstleistungen und würde gerne einen Termin für ein unverbindliches Beratungsgespräch vereinbaren.\n\nKönnten Sie mir bitte mitteilen, wann Sie Zeit hätten?\n\nVielen Dank im Voraus!",
            $dashboard_url,
            $site_name,
            'Testeintrag Premium All options'
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        return wp_mail( $to_email, $subject, $body, $headers );
    }
}
