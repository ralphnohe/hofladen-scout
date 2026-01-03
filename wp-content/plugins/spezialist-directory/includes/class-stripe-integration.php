<?php
/**
 * Stripe Integration
 *
 * Handles Stripe subscription payments for premium listings
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SD_Stripe_Integration Class
 */
class SD_Stripe_Integration {

    /**
     * Single instance
     *
     * @var SD_Stripe_Integration
     */
    protected static $_instance = null;

    /**
     * Stripe API instance
     *
     * @var mixed
     */
    private $stripe = null;

    /**
     * Main Instance
     *
     * @return SD_Stripe_Integration
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
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'rest_api_init', array( $this, 'register_webhook_endpoint' ) );
        add_action( 'wp_ajax_sd_create_checkout_session', array( $this, 'create_checkout_session' ) );
        add_action( 'wp_ajax_sd_cancel_subscription', array( $this, 'cancel_subscription' ) );
        add_action( 'wp_ajax_sd_billing_portal', array( $this, 'create_billing_portal_session' ) );
        add_action( 'wp_ajax_sd_check_premium_status', array( $this, 'check_premium_status' ) );
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=hofladen',
            __( 'Stripe Einstellungen', 'spezialist-directory' ),
            __( 'Stripe Einstellungen', 'spezialist-directory' ),
            'manage_options',
            'sd-stripe-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'sd_stripe_settings', 'sd_stripe_test_mode' );
        register_setting( 'sd_stripe_settings', 'sd_stripe_test_publishable_key' );
        register_setting( 'sd_stripe_settings', 'sd_stripe_test_secret_key' );
        register_setting( 'sd_stripe_settings', 'sd_stripe_live_publishable_key' );
        register_setting( 'sd_stripe_settings', 'sd_stripe_live_secret_key' );
        register_setting( 'sd_stripe_settings', 'sd_premium_monthly_price_id' );
        register_setting( 'sd_stripe_settings', 'sd_premium_yearly_price_id' );
        register_setting( 'sd_stripe_settings', 'sd_stripe_webhook_secret' );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Stripe Einstellungen', 'spezialist-directory' ); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'sd_stripe_settings' ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Testmodus', 'spezialist-directory' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="sd_stripe_test_mode" value="1" <?php checked( Spezialist_Directory::get_option( 'stripe_test_mode', true ), true ); ?>>
                                <?php _e( 'Testmodus aktivieren', 'spezialist-directory' ); ?>
                            </label>
                            <p class="description"><?php _e( 'Im Testmodus werden keine echten Zahlungen verarbeitet.', 'spezialist-directory' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th colspan="2"><h2><?php _e( 'Test API Keys', 'spezialist-directory' ); ?></h2></th>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Test Publishable Key', 'spezialist-directory' ); ?></th>
                        <td>
                            <input type="text" name="sd_stripe_test_publishable_key" value="<?php echo esc_attr( Spezialist_Directory::get_option( 'stripe_test_publishable_key' ) ); ?>" class="regular-text" placeholder="pk_test_...">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Test Secret Key', 'spezialist-directory' ); ?></th>
                        <td>
                            <input type="password" name="sd_stripe_test_secret_key" value="<?php echo esc_attr( Spezialist_Directory::get_option( 'stripe_test_secret_key' ) ); ?>" class="regular-text" placeholder="sk_test_...">
                        </td>
                    </tr>

                    <tr>
                        <th colspan="2"><h2><?php _e( 'Live API Keys', 'spezialist-directory' ); ?></h2></th>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Live Publishable Key', 'spezialist-directory' ); ?></th>
                        <td>
                            <input type="text" name="sd_stripe_live_publishable_key" value="<?php echo esc_attr( Spezialist_Directory::get_option( 'stripe_live_publishable_key' ) ); ?>" class="regular-text" placeholder="pk_live_...">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Live Secret Key', 'spezialist-directory' ); ?></th>
                        <td>
                            <input type="password" name="sd_stripe_live_secret_key" value="<?php echo esc_attr( Spezialist_Directory::get_option( 'stripe_live_secret_key' ) ); ?>" class="regular-text" placeholder="sk_live_...">
                        </td>
                    </tr>

                    <tr>
                        <th colspan="2"><h2><?php _e( 'Price IDs', 'spezialist-directory' ); ?></h2></th>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Monatliches Abo Price ID', 'spezialist-directory' ); ?></th>
                        <td>
                            <input type="text" name="sd_premium_monthly_price_id" value="<?php echo esc_attr( Spezialist_Directory::get_option( 'premium_monthly_price_id' ) ); ?>" class="regular-text" placeholder="price_...">
                            <p class="description"><?php _e( 'Erstellen Sie ein wiederkehrendes Produkt in Ihrem Stripe Dashboard.', 'spezialist-directory' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Jährliches Abo Price ID', 'spezialist-directory' ); ?></th>
                        <td>
                            <input type="text" name="sd_premium_yearly_price_id" value="<?php echo esc_attr( Spezialist_Directory::get_option( 'premium_yearly_price_id' ) ); ?>" class="regular-text" placeholder="price_...">
                        </td>
                    </tr>

                    <tr>
                        <th colspan="2"><h2><?php _e( 'Webhook', 'spezialist-directory' ); ?></h2></th>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Webhook URL', 'spezialist-directory' ); ?></th>
                        <td>
                            <input type="text" value="<?php echo esc_url( rest_url( 'sd/v1/stripe-webhook' ) ); ?>" class="regular-text" readonly>
                            <p class="description">
                                <?php _e( 'Fügen Sie diese URL als Webhook-Endpunkt in Ihrem Stripe Dashboard hinzu.', 'spezialist-directory' ); ?><br>
                                <?php _e( 'Zu überwachende Events: checkout.session.completed, customer.subscription.created, customer.subscription.updated, customer.subscription.deleted, invoice.payment_failed', 'spezialist-directory' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Webhook Secret', 'spezialist-directory' ); ?></th>
                        <td>
                            <input type="password" name="sd_stripe_webhook_secret" value="<?php echo esc_attr( Spezialist_Directory::get_option( 'stripe_webhook_secret' ) ); ?>" class="regular-text" placeholder="whsec_...">
                            <p class="description">
                                <?php _e( 'Das Webhook Signing Secret aus dem Stripe Dashboard. Bei lokalem Testing mit Stripe CLI: "stripe listen" zeigt das Secret an.', 'spezialist-directory' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get Stripe secret key
     *
     * @return string
     */
    private function get_secret_key() {
        $test_mode = Spezialist_Directory::get_option( 'stripe_test_mode', true );

        if ( $test_mode ) {
            return Spezialist_Directory::get_option( 'stripe_test_secret_key' );
        }

        return Spezialist_Directory::get_option( 'stripe_live_secret_key' );
    }

    /**
     * Get Stripe publishable key
     *
     * @return string
     */
    public static function get_publishable_key() {
        $test_mode = Spezialist_Directory::get_option( 'stripe_test_mode', true );

        if ( $test_mode ) {
            return Spezialist_Directory::get_option( 'stripe_test_publishable_key' );
        }

        return Spezialist_Directory::get_option( 'stripe_live_publishable_key' );
    }

    /**
     * Create Stripe checkout session
     */
    public function create_checkout_session() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_create_checkout' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $plan = isset( $_POST['plan'] ) ? sanitize_text_field( $_POST['plan'] ) : 'monthly';

        if ( ! $post_id || 'hofladen' !== get_post_type( $post_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültiger Eintrag.', 'spezialist-directory' )
            ) );
        }

        // Check if user owns this post
        $post = get_post( $post_id );
        if ( intval( $post->post_author ) !== get_current_user_id() ) {
            wp_send_json_error( array(
                'message' => __( 'Du hast keine Berechtigung für diesen Eintrag.', 'spezialist-directory' )
            ) );
        }

        // Get price ID
        $price_id = 'yearly' === $plan
            ? Spezialist_Directory::get_option( 'premium_yearly_price_id' )
            : Spezialist_Directory::get_option( 'premium_monthly_price_id' );

        if ( empty( $price_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Stripe ist nicht konfiguriert. Bitte kontaktiere den Administrator.', 'spezialist-directory' )
            ) );
        }

        try {
            // Initialize Stripe
            \Stripe\Stripe::setApiKey( $this->get_secret_key() );

            $user_id = get_current_user_id();
            $user = get_userdata( $user_id );

            // Get or create Stripe Customer
            $customer_id = get_user_meta( $user_id, '_sd_stripe_customer_id', true );
            $customer_valid = false;

            if ( ! empty( $customer_id ) ) {
                // Validate existing customer still exists in Stripe
                try {
                    $customer = \Stripe\Customer::retrieve( $customer_id );
                    // Customer exists and is not deleted
                    if ( $customer && ! $customer->deleted ) {
                        $customer_valid = true;
                    }
                } catch ( \Stripe\Exception\InvalidRequestException $e ) {
                    // Customer doesn't exist - will create new one
                    error_log( 'Spezialist Directory: Stored Stripe customer not found, creating new one. User ID: ' . $user_id . ', Old Customer ID: ' . $customer_id );
                    $customer_valid = false;
                }
            }

            if ( ! $customer_valid ) {
                // Create new Stripe customer
                $customer = \Stripe\Customer::create( array(
                    'email' => $user->user_email,
                    'name' => $user->display_name,
                    'metadata' => array(
                        'wp_user_id' => $user_id,
                    ),
                ) );
                $customer_id = $customer->id;
                update_user_meta( $user_id, '_sd_stripe_customer_id', $customer_id );
            }

            // Determine success URL based on context (new submission vs upgrade)
            $is_new_submission = isset( $_POST['is_new_submission'] ) && $_POST['is_new_submission'];
            if ( $is_new_submission ) {
                $success_url = sd_get_page_url( 'mein-dashboard/' ) . '?tab=listings&submission=premium_success&post_id=' . $post_id . '&session_id={CHECKOUT_SESSION_ID}';
            } else {
                $success_url = sd_get_page_url( 'mein-dashboard/' ) . '?tab=listings&upgrade=success&post_id=' . $post_id . '&session_id={CHECKOUT_SESSION_ID}';
            }

            // Create Checkout Session
            $session = \Stripe\Checkout\Session::create( array(
                'customer' => $customer_id,
                'payment_method_types' => array( 'card' ),
                'line_items' => array(
                    array(
                        'price' => $price_id,
                        'quantity' => 1,
                    ),
                ),
                'mode' => 'subscription',
                'success_url' => $success_url,
                'cancel_url' => sd_get_page_url( 'mein-dashboard/' ) . '?upgrade=cancelled',
                'client_reference_id' => strval( $post_id ),
                'subscription_data' => array(
                    'metadata' => array(
                        'post_id' => $post_id,
                        'user_id' => $user_id,
                    ),
                ),
                'locale' => 'de',
                'allow_promotion_codes' => true,
                // Business-Kunden Einstellungen - per Default anzeigen
                'billing_address_collection' => 'required',
                'tax_id_collection' => array(
                    'enabled' => true,
                    'required' => 'if_supported',
                ),
                // Firmenname per default anzeigen
                'name_collection' => array(
                    'business' => array(
                        'enabled' => true,
                        'optional' => false,
                    ),
                ),
                'customer_update' => array(
                    'address' => 'auto',
                    'name' => 'auto',
                ),
                // Automatische Steuerberechnung (19% MwSt.)
                'automatic_tax' => array(
                    'enabled' => true,
                ),
            ) );

            wp_send_json_success( array(
                'session_id' => $session->id,
                'checkout_url' => $session->url,
            ) );

        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            wp_send_json_error( array(
                'message' => __( 'Stripe Fehler: ', 'spezialist-directory' ) . $e->getMessage()
            ) );
        } catch ( \Exception $e ) {
            wp_send_json_error( array(
                'message' => __( 'Ein Fehler ist aufgetreten: ', 'spezialist-directory' ) . $e->getMessage()
            ) );
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel_subscription() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_cancel_subscription' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültiger Eintrag.', 'spezialist-directory' )
            ) );
        }

        // Check if user owns this post
        $post = get_post( $post_id );
        if ( intval( $post->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Du hast keine Berechtigung für diesen Eintrag.', 'spezialist-directory' )
            ) );
        }

        // Get subscription ID
        $subscription_id = get_post_meta( $post_id, '_sd_stripe_subscription_id', true );

        if ( empty( $subscription_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Kein aktives Abonnement gefunden.', 'spezialist-directory' )
            ) );
        }

        try {
            // Initialize Stripe
            \Stripe\Stripe::setApiKey( $this->get_secret_key() );

            // Cancel at period end (subscription runs until current period ends)
            $subscription = \Stripe\Subscription::update( $subscription_id, array(
                'cancel_at_period_end' => true,
            ) );

            // Mark as pending cancellation
            update_post_meta( $post_id, '_sd_subscription_cancel_at_period_end', '1' );

            // Format the cancellation date
            $cancel_date = date_i18n( get_option( 'date_format' ), $subscription->current_period_end );

            wp_send_json_success( array(
                'message' => sprintf(
                    __( 'Ihr Abonnement wurde gekündigt. Premium bleibt aktiv bis %s.', 'spezialist-directory' ),
                    $cancel_date
                ),
                'cancel_at' => $subscription->current_period_end,
                'cancel_date' => $cancel_date,
            ) );

        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            wp_send_json_error( array(
                'message' => __( 'Stripe Fehler: ', 'spezialist-directory' ) . $e->getMessage()
            ) );
        } catch ( \Exception $e ) {
            wp_send_json_error( array(
                'message' => __( 'Ein Fehler ist aufgetreten: ', 'spezialist-directory' ) . $e->getMessage()
            ) );
        }
    }

    /**
     * Create billing portal session
     */
    public function create_billing_portal_session() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_billing_portal' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        // Get customer ID from user meta
        $customer_id = get_user_meta( get_current_user_id(), '_sd_stripe_customer_id', true );

        if ( empty( $customer_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Kein Stripe-Kunde gefunden.', 'spezialist-directory' )
            ) );
        }

        try {
            // Initialize Stripe
            \Stripe\Stripe::setApiKey( $this->get_secret_key() );

            // Create Billing Portal session
            $session = \Stripe\BillingPortal\Session::create( array(
                'customer' => $customer_id,
                'return_url' => sd_get_page_url( 'mein-dashboard/' ) . '?tab=premium',
            ) );

            wp_send_json_success( array(
                'portal_url' => $session->url,
            ) );

        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            wp_send_json_error( array(
                'message' => __( 'Stripe Fehler: ', 'spezialist-directory' ) . $e->getMessage()
            ) );
        } catch ( \Exception $e ) {
            wp_send_json_error( array(
                'message' => __( 'Ein Fehler ist aufgetreten: ', 'spezialist-directory' ) . $e->getMessage()
            ) );
        }
    }

    /**
     * Check premium status of a listing (for polling after checkout)
     */
    public function check_premium_status() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_check_premium' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not logged in' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id || 'hofladen' !== get_post_type( $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Invalid post' ) );
        }

        // Check ownership (author or claimed by user)
        $post = get_post( $post_id );
        $user_id = get_current_user_id();
        $claimed_by = get_post_meta( $post_id, '_sd_claimed_by', true );

        if ( intval( $post->post_author ) !== $user_id && intval( $claimed_by ) !== $user_id ) {
            wp_send_json_error( array( 'message' => 'Not authorized' ) );
        }

        $is_premium = get_post_meta( $post_id, '_sd_is_premium', true ) === '1';
        $subscription_plan = get_post_meta( $post_id, '_sd_subscription_plan', true );

        // Fallback: Direct Stripe API verification if webhook missed
        $verify_stripe = isset( $_POST['verify_stripe'] ) && $_POST['verify_stripe'] === '1';

        if ( $verify_stripe && ! $is_premium ) {
            $stripe_result = $this->verify_premium_via_stripe( $post_id, $user_id );
            if ( $stripe_result['activated'] ) {
                $is_premium = true;
                $subscription_plan = $stripe_result['plan'];
            }
        }

        wp_send_json_success( array(
            'is_premium'        => $is_premium,
            'subscription_plan' => $subscription_plan,
        ) );
    }

    /**
     * Verify and activate premium status directly via Stripe API
     * Used as fallback when webhook doesn't arrive
     *
     * @param int $post_id The post ID to check
     * @param int $user_id The user ID
     * @return array ['activated' => bool, 'plan' => string|null]
     */
    private function verify_premium_via_stripe( $post_id, $user_id ) {
        $result = array(
            'activated' => false,
            'plan'      => null,
        );

        // Get customer ID from user meta
        $customer_id = get_user_meta( $user_id, '_sd_stripe_customer_id', true );

        if ( empty( $customer_id ) ) {
            error_log( 'SD Stripe Fallback: No customer ID for user ' . $user_id );
            return $result;
        }

        try {
            // Initialize Stripe
            \Stripe\Stripe::setApiKey( $this->get_secret_key() );

            // Get all active subscriptions for this customer
            $subscriptions = \Stripe\Subscription::all( array(
                'customer' => $customer_id,
                'status'   => 'active',
                'limit'    => 10,
            ) );

            foreach ( $subscriptions->data as $subscription ) {
                // Check if this subscription is for the current post
                $sub_post_id = isset( $subscription->metadata->post_id ) ? intval( $subscription->metadata->post_id ) : 0;

                if ( $sub_post_id === $post_id ) {
                    // Found matching subscription - activate premium!
                    error_log( 'SD Stripe Fallback: Found active subscription ' . $subscription->id . ' for post ' . $post_id . ' - activating premium' );

                    // Update premium status (same as webhook handler)
                    update_post_meta( $post_id, '_sd_is_premium', '1' );
                    update_post_meta( $post_id, '_sd_stripe_subscription_id', $subscription->id );
                    update_post_meta( $post_id, '_sd_stripe_customer_id', $subscription->customer );

                    // Determine plan type
                    $plan_type = $this->get_plan_type_from_subscription( $subscription );
                    if ( $plan_type ) {
                        update_post_meta( $post_id, '_sd_subscription_plan', $plan_type );
                    }

                    // Set premium until date
                    if ( isset( $subscription->current_period_end ) ) {
                        $premium_until = date( 'Y-m-d H:i:s', $subscription->current_period_end );
                        update_post_meta( $post_id, '_sd_premium_until', $premium_until );
                    }

                    // Track cancellation status
                    if ( $subscription->cancel_at_period_end ) {
                        update_post_meta( $post_id, '_sd_subscription_cancel_at_period_end', '1' );
                    } else {
                        delete_post_meta( $post_id, '_sd_subscription_cancel_at_period_end' );
                    }

                    // Remove pending premium flag
                    delete_post_meta( $post_id, '_sd_pending_premium' );

                    $result['activated'] = true;
                    $result['plan']      = $plan_type;
                    break;
                }
            }

            if ( ! $result['activated'] ) {
                error_log( 'SD Stripe Fallback: No active subscription found for post ' . $post_id . ' and customer ' . $customer_id );
            }

        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            error_log( 'SD Stripe Fallback Error: ' . $e->getMessage() );
        } catch ( \Exception $e ) {
            error_log( 'SD Stripe Fallback Error: ' . $e->getMessage() );
        }

        return $result;
    }

    /**
     * Register webhook endpoint
     */
    public function register_webhook_endpoint() {
        register_rest_route( 'sd/v1', '/stripe-webhook', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_webhook' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * Handle Stripe webhook
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_webhook( $request ) {
        // Use php://input directly for raw body - more reliable for Stripe signature verification
        $payload = file_get_contents( 'php://input' );
        $sig_header = $request->get_header( 'Stripe-Signature' );

        // Debug logging
        error_log( 'SD Stripe Webhook: Payload length: ' . strlen( $payload ) );
        error_log( 'SD Stripe Webhook: Signature present: ' . ( $sig_header ? 'yes' : 'no' ) );

        // Get webhook secret
        $webhook_secret = Spezialist_Directory::get_option( 'stripe_webhook_secret' );

        if ( empty( $webhook_secret ) ) {
            error_log( 'SD Stripe Webhook: No webhook secret configured' );
            return new WP_REST_Response( array( 'error' => 'Webhook not configured' ), 500 );
        }

        try {
            // Initialize Stripe
            \Stripe\Stripe::setApiKey( $this->get_secret_key() );

            // Verify webhook signature
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $webhook_secret
            );

        } catch ( \Stripe\Exception\SignatureVerificationException $e ) {
            error_log( 'SD Stripe Webhook: Invalid signature - ' . $e->getMessage() );
            error_log( 'SD Stripe Webhook: Webhook secret starts with: ' . substr( $webhook_secret, 0, 10 ) . '...' );
            return new WP_REST_Response( array( 'error' => 'Invalid signature' ), 400 );
        } catch ( \Exception $e ) {
            error_log( 'SD Stripe Webhook: Error - ' . $e->getMessage() );
            return new WP_REST_Response( array( 'error' => $e->getMessage() ), 400 );
        }

        // Log event type for debugging
        error_log( 'SD Stripe Webhook: Received event ' . $event->type );

        // Handle the event
        switch ( $event->type ) {
            case 'checkout.session.completed':
                $this->handle_checkout_completed( $event->data->object );
                break;

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->handle_subscription_updated( $event->data->object );
                break;

            case 'customer.subscription.deleted':
                $this->handle_subscription_cancelled( $event->data->object );
                break;

            case 'invoice.payment_failed':
                $this->handle_payment_failed( $event->data->object );
                break;

            case 'invoice_payment.paid':
                // New event type in Stripe API 2025-11-17, similar to invoice.paid
                error_log( 'SD Stripe Webhook: invoice_payment.paid received (API 2025-11-17 event)' );
                break;

            case 'invoice.paid':
            case 'invoice.payment_succeeded':
                // These events confirm subscription payment - log for now
                error_log( 'SD Stripe Webhook: ' . $event->type . ' received' );
                break;

            default:
                error_log( 'SD Stripe Webhook: Unhandled event type ' . $event->type );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /**
     * Handle checkout session completed
     *
     * @param object $session
     */
    private function handle_checkout_completed( $session ) {
        // Get post ID from client_reference_id
        $post_id = isset( $session->client_reference_id ) ? intval( $session->client_reference_id ) : 0;

        if ( ! $post_id ) {
            error_log( 'SD Stripe Webhook: checkout.session.completed - No post_id in client_reference_id' );
            return;
        }

        error_log( 'SD Stripe Webhook: Checkout completed for post ' . $post_id );

        // The subscription.created webhook will handle the actual premium activation
        // This is just for logging/confirmation purposes
    }

    /**
     * Handle subscription updated
     *
     * @param object $subscription
     */
    private function handle_subscription_updated( $subscription ) {
        // Get post ID from metadata
        $post_id = isset( $subscription->metadata->post_id ) ? intval( $subscription->metadata->post_id ) : 0;

        if ( ! $post_id ) {
            error_log( 'SD Stripe Webhook: subscription updated - No post_id in metadata' );
            return;
        }

        // Check subscription status
        $status = $subscription->status;
        error_log( 'SD Stripe Webhook: Subscription ' . $subscription->id . ' updated to status: ' . $status );

        if ( in_array( $status, array( 'active', 'trialing' ), true ) ) {
            // Update premium status
            update_post_meta( $post_id, '_sd_is_premium', '1' );
            update_post_meta( $post_id, '_sd_stripe_subscription_id', $subscription->id );
            update_post_meta( $post_id, '_sd_stripe_customer_id', $subscription->customer );

            // Determine plan type (monthly or yearly) from price ID
            $plan_type = $this->get_plan_type_from_subscription( $subscription );
            if ( $plan_type ) {
                update_post_meta( $post_id, '_sd_subscription_plan', $plan_type );
            }

            // Set premium until date
            if ( isset( $subscription->current_period_end ) ) {
                $premium_until = date( 'Y-m-d H:i:s', $subscription->current_period_end );
                update_post_meta( $post_id, '_sd_premium_until', $premium_until );
            }

            // Track if subscription is set to cancel at period end
            if ( $subscription->cancel_at_period_end ) {
                update_post_meta( $post_id, '_sd_subscription_cancel_at_period_end', '1' );
            } else {
                delete_post_meta( $post_id, '_sd_subscription_cancel_at_period_end' );
            }

            // Store customer ID in user meta
            if ( isset( $subscription->metadata->user_id ) ) {
                update_user_meta( intval( $subscription->metadata->user_id ), '_sd_stripe_customer_id', $subscription->customer );
            }

            // Remove pending premium flag if exists (for new submissions with premium fields)
            delete_post_meta( $post_id, '_sd_pending_premium' );

            error_log( 'SD Stripe Webhook: Premium activated for post ' . $post_id . ' with plan: ' . $plan_type );
        } elseif ( in_array( $status, array( 'past_due', 'unpaid' ), true ) ) {
            // Keep premium status but mark as past due
            update_post_meta( $post_id, '_sd_subscription_status', $status );
            error_log( 'SD Stripe Webhook: Subscription past due for post ' . $post_id );
        }
    }

    /**
     * Get plan type (monthly or yearly) from subscription object
     *
     * @param object $subscription Stripe subscription object
     * @return string|null 'monthly', 'yearly', or null if unknown
     */
    private function get_plan_type_from_subscription( $subscription ) {
        // Get configured price IDs
        $monthly_price_id = Spezialist_Directory::get_option( 'premium_monthly_price_id' );
        $yearly_price_id  = Spezialist_Directory::get_option( 'premium_yearly_price_id' );

        // Get the price ID from subscription items
        $price_id = null;
        if ( isset( $subscription->items->data[0]->price->id ) ) {
            $price_id = $subscription->items->data[0]->price->id;
        } elseif ( isset( $subscription->plan->id ) ) {
            // Fallback for older subscription format
            $price_id = $subscription->plan->id;
        }

        if ( ! $price_id ) {
            error_log( 'SD Stripe: Could not determine price ID from subscription' );
            return null;
        }

        // Match against configured price IDs
        if ( $price_id === $monthly_price_id ) {
            return 'monthly';
        } elseif ( $price_id === $yearly_price_id ) {
            return 'yearly';
        }

        // Fallback: Check interval from subscription
        $interval = null;
        if ( isset( $subscription->items->data[0]->price->recurring->interval ) ) {
            $interval = $subscription->items->data[0]->price->recurring->interval;
        } elseif ( isset( $subscription->plan->interval ) ) {
            $interval = $subscription->plan->interval;
        }

        if ( 'month' === $interval ) {
            return 'monthly';
        } elseif ( 'year' === $interval ) {
            return 'yearly';
        }

        error_log( 'SD Stripe: Unknown price ID: ' . $price_id );
        return null;
    }

    /**
     * Handle subscription cancelled
     *
     * @param object $subscription
     */
    private function handle_subscription_cancelled( $subscription ) {
        // Get post ID from metadata
        $post_id = isset( $subscription->metadata->post_id ) ? intval( $subscription->metadata->post_id ) : 0;

        if ( ! $post_id ) {
            error_log( 'SD Stripe Webhook: subscription deleted - No post_id in metadata' );
            return;
        }

        // Remove premium status
        update_post_meta( $post_id, '_sd_is_premium', '0' );
        delete_post_meta( $post_id, '_sd_stripe_subscription_id' );
        delete_post_meta( $post_id, '_sd_premium_until' );
        delete_post_meta( $post_id, '_sd_subscription_cancel_at_period_end' );
        delete_post_meta( $post_id, '_sd_subscription_status' );
        delete_post_meta( $post_id, '_sd_subscription_plan' );

        error_log( 'SD Stripe Webhook: Premium deactivated for post ' . $post_id );

        // Send notification to user
        $post = get_post( $post_id );
        if ( $post ) {
            $user = get_user_by( 'id', $post->post_author );
            if ( $user ) {
                $this->send_subscription_ended_email( $user, $post );
            }
        }
    }

    /**
     * Handle payment failed
     *
     * @param object $invoice
     */
    private function handle_payment_failed( $invoice ) {
        // Get subscription ID
        $subscription_id = isset( $invoice->subscription ) ? $invoice->subscription : null;

        if ( ! $subscription_id ) {
            return;
        }

        // Find post with this subscription
        $posts = get_posts( array(
            'post_type' => 'hofladen',
            'meta_key' => '_sd_stripe_subscription_id',
            'meta_value' => $subscription_id,
            'posts_per_page' => 1,
        ) );

        if ( empty( $posts ) ) {
            error_log( 'SD Stripe Webhook: payment_failed - No post found for subscription ' . $subscription_id );
            return;
        }

        $post = $posts[0];
        $user = get_user_by( 'id', $post->post_author );

        if ( $user ) {
            // Send payment failed notification
            $this->send_payment_failed_email( $user, $post, $invoice );
        }

        error_log( 'SD Stripe Webhook: Payment failed for post ' . $post->ID );
    }

    /**
     * Send subscription ended email
     *
     * @param WP_User $user
     * @param WP_Post $post
     */
    private function send_subscription_ended_email( $user, $post ) {
        $subject = __( 'Dein Premium-Abonnement ist beendet', 'spezialist-directory' );
        $message = sprintf(
            __( "Hallo %s,\n\nDein Premium-Abonnement für den Eintrag \"%s\" ist nun beendet.\n\nDu kannst jederzeit ein neues Premium-Abonnement abschließen, um wieder von allen Vorteilen zu profitieren.\n\nMit freundlichen Grüßen,\nDein Spezialist-Für.de Team", 'spezialist-directory' ),
            $user->display_name,
            $post->post_title
        );

        wp_mail( $user->user_email, $subject, $message );
    }

    /**
     * Send payment failed email
     *
     * @param WP_User $user
     * @param WP_Post $post
     * @param object $invoice
     */
    private function send_payment_failed_email( $user, $post, $invoice ) {
        $subject = __( 'Zahlung fehlgeschlagen - Aktion erforderlich', 'spezialist-directory' );
        $message = sprintf(
            __( "Hallo %s,\n\nLeider konnte die Zahlung für dein Premium-Abonnement (\"%s\") nicht verarbeitet werden.\n\nBitte aktualisiere deine Zahlungsinformationen, um dein Premium-Abonnement aufrechtzuerhalten.\n\nDu kannst deine Zahlungsmethode in deinem Dashboard unter 'Premium' → 'Abrechnung verwalten' aktualisieren.\n\nMit freundlichen Grüßen,\nDein Spezialist-Für.de Team", 'spezialist-directory' ),
            $user->display_name,
            $post->post_title
        );

        wp_mail( $user->user_email, $subject, $message );
    }
}
