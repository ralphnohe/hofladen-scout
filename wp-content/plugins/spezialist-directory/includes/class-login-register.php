<?php
/**
 * Login/Register System
 *
 * Handles custom login and registration forms with social login
 *
 * @package Spezialist_Directory
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SD_Login_Register Class
 */
class SD_Login_Register {

    /**
     * Single instance
     *
     * @var SD_Login_Register
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SD_Login_Register
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
        // Register shortcode
        add_shortcode( 'spezialist_login', array( $this, 'render_login_page' ) );

        // AJAX handlers for login/register (only for non-logged in users)
        add_action( 'wp_ajax_nopriv_sd_auth_login', array( $this, 'handle_login' ) );
        add_action( 'wp_ajax_nopriv_sd_auth_register', array( $this, 'handle_register' ) );
        add_action( 'wp_ajax_nopriv_sd_auth_forgot_password', array( $this, 'handle_forgot_password' ) );

        // Google OAuth callback handler
        add_action( 'init', array( $this, 'handle_google_callback' ) );

        // Redirect already logged in users away from login page
        add_action( 'template_redirect', array( $this, 'redirect_logged_in_users' ) );

        // Add admin menu for Google OAuth settings
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Enqueue login-specific assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_login_assets' ) );
    }

    /**
     * Render login page shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_login_page( $atts ) {
        $atts = shortcode_atts( array(
            'redirect' => '',
        ), $atts, 'spezialist_login' );

        // If user is already logged in, show message
        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            return '<div class="sd-auth-wrapper">' .
                   '<div class="sd-auth-card">' .
                   '<div class="sd-auth-header">' .
                   '<h2 class="sd-auth-title">' . __( 'Bereits angemeldet', 'spezialist-directory' ) . '</h2>' .
                   '<p class="sd-auth-subtitle">' . sprintf( __( 'Willkommen, %s!', 'spezialist-directory' ), esc_html( $current_user->display_name ) ) . '</p>' .
                   '</div>' .
                   '<div class="sd-auth-actions">' .
                   '<a href="' . esc_url( sd_get_page_url( 'mein-dashboard/' ) ) . '" class="sd-button sd-button-primary sd-button-full">' . __( 'Zum Dashboard', 'spezialist-directory' ) . '</a>' .
                   '<a href="' . esc_url( wp_logout_url( home_url() ) ) . '" class="sd-auth-logout-link">' . __( 'Abmelden', 'spezialist-directory' ) . '</a>' .
                   '</div>' .
                   '</div>' .
                   '</div>';
        }

        ob_start();
        include SD_PLUGIN_DIR . 'templates/login-register.php';
        return ob_get_clean();
    }

    /**
     * Handle login via AJAX
     */
    public function handle_login() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_auth_login' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen. Bitte lade die Seite neu.', 'spezialist-directory' )
            ) );
        }

        // Validate email
        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Bitte gib eine gültige E-Mail-Adresse ein.', 'spezialist-directory' )
            ) );
        }

        // Validate password
        $password = isset( $_POST['password'] ) ? $_POST['password'] : '';
        if ( empty( $password ) ) {
            wp_send_json_error( array(
                'message' => __( 'Bitte gib dein Passwort ein.', 'spezialist-directory' )
            ) );
        }

        // Check if user exists by email
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            // Generic error message to prevent user enumeration
            wp_send_json_error( array(
                'message' => __( 'E-Mail-Adresse oder Passwort ist falsch.', 'spezialist-directory' )
            ) );
        }

        // Attempt login
        $remember = isset( $_POST['remember'] ) && $_POST['remember'] == '1';
        $creds = array(
            'user_login'    => $user->user_login,
            'user_password' => $password,
            'remember'      => $remember,
        );

        $login_result = wp_signon( $creds, is_ssl() );

        if ( is_wp_error( $login_result ) ) {
            wp_send_json_error( array(
                'message' => __( 'E-Mail-Adresse oder Passwort ist falsch.', 'spezialist-directory' )
            ) );
        }

        // Determine redirect URL
        $redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : '';
        if ( empty( $redirect_to ) ) {
            $redirect_to = sd_get_page_url( 'mein-dashboard/' );
        }

        wp_send_json_success( array(
            'message'  => __( 'Erfolgreich angemeldet!', 'spezialist-directory' ),
            'redirect' => $redirect_to,
        ) );
    }

    /**
     * Handle registration via AJAX
     */
    public function handle_register() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_auth_register' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen. Bitte lade die Seite neu.', 'spezialist-directory' )
            ) );
        }

        // Check if registration is enabled
        if ( ! get_option( 'users_can_register' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Die Registrierung ist derzeit deaktiviert.', 'spezialist-directory' )
            ) );
        }

        // Validate first name
        $firstname = isset( $_POST['firstname'] ) ? sanitize_text_field( $_POST['firstname'] ) : '';
        if ( empty( $firstname ) || strlen( $firstname ) < 2 ) {
            wp_send_json_error( array(
                'message' => __( 'Bitte gib deinen Vornamen ein.', 'spezialist-directory' )
            ) );
        }

        // Validate last name
        $lastname = isset( $_POST['lastname'] ) ? sanitize_text_field( $_POST['lastname'] ) : '';
        if ( empty( $lastname ) || strlen( $lastname ) < 2 ) {
            wp_send_json_error( array(
                'message' => __( 'Bitte gib deinen Nachnamen ein.', 'spezialist-directory' )
            ) );
        }

        // Validate email
        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Bitte gib eine gültige E-Mail-Adresse ein.', 'spezialist-directory' )
            ) );
        }

        // Check if email already exists
        if ( email_exists( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Diese E-Mail-Adresse ist bereits registriert.', 'spezialist-directory' )
            ) );
        }

        // Validate password
        $password = isset( $_POST['password'] ) ? $_POST['password'] : '';
        if ( empty( $password ) || strlen( $password ) < 8 ) {
            wp_send_json_error( array(
                'message' => __( 'Das Passwort muss mindestens 8 Zeichen lang sein.', 'spezialist-directory' )
            ) );
        }

        // Generate username from email
        $username = sanitize_user( current( explode( '@', $email ) ), true );
        $base_username = $username;
        $counter = 1;
        while ( username_exists( $username ) ) {
            $username = $base_username . $counter;
            $counter++;
        }

        // Create user
        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Fehler bei der Registrierung. Bitte versuche es erneut.', 'spezialist-directory' )
            ) );
        }

        // Update user meta
        wp_update_user( array(
            'ID'           => $user_id,
            'first_name'   => $firstname,
            'last_name'    => $lastname,
            'display_name' => $firstname . ' ' . $lastname,
        ) );

        // Determine redirect URL
        $redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : '';

        // Check if this is a claim flow (redirect_to contains claim=1)
        $is_claim_flow = ! empty( $redirect_to ) && strpos( $redirect_to, 'claim=1' ) !== false;

        if ( $is_claim_flow ) {
            // Claim flow: Do NOT auto-login, redirect to login page with the original redirect_to
            // This ensures user goes through: Register → Login → Claim Form
            $login_url = add_query_arg( array(
                'redirect_to' => urlencode( $redirect_to ),
                'registered'  => '1', // Flag to show success message on login page
            ), sd_get_page_url( 'anmelden/' ) );

            wp_send_json_success( array(
                'message'  => __( 'Konto erfolgreich erstellt! Bitte melde dich jetzt an.', 'spezialist-directory' ),
                'redirect' => $login_url,
            ) );
        } else {
            // Normal flow: Auto-login the new user
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id, true );

            if ( empty( $redirect_to ) ) {
                $redirect_to = sd_get_page_url( 'mein-dashboard/' );
            }

            wp_send_json_success( array(
                'message'  => __( 'Konto erfolgreich erstellt! Du wirst weitergeleitet...', 'spezialist-directory' ),
                'redirect' => $redirect_to,
            ) );
        }
    }

    /**
     * Handle forgot password via AJAX
     */
    public function handle_forgot_password() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_auth_forgot' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Bitte gib eine gültige E-Mail-Adresse ein.', 'spezialist-directory' )
            ) );
        }

        // Check if user exists
        $user = get_user_by( 'email', $email );

        // Always return success message for security (don't reveal if email exists)
        if ( $user ) {
            // Generate password reset link
            $reset_key = get_password_reset_key( $user );

            if ( ! is_wp_error( $reset_key ) ) {
                $reset_link = network_site_url( "wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode( $user->user_login ), 'login' );

                // Send email
                $subject = sprintf( __( '[%s] Passwort zurücksetzen', 'spezialist-directory' ), get_bloginfo( 'name' ) );
                $message = sprintf(
                    __( "Hallo %s,\n\nJemand hat das Zurücksetzen des Passworts für dein Konto angefordert.\n\nWenn du diese Anfrage gestellt hast, klicke auf den folgenden Link:\n%s\n\nWenn du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren.\n\nDiesen Link kannst du nur einmal verwenden und er ist 24 Stunden lang gültig.", 'spezialist-directory' ),
                    $user->display_name,
                    $reset_link
                );

                wp_mail( $email, $subject, $message );
            }
        }

        // Always return success for security
        wp_send_json_success( array(
            'message' => __( 'Falls ein Konto mit dieser E-Mail existiert, haben wir dir einen Link zum Zurücksetzen gesendet.', 'spezialist-directory' )
        ) );
    }

    /**
     * Get Google OAuth URL
     *
     * @param string $redirect_to URL to redirect after login
     * @return string
     */
    public function get_google_auth_url( $redirect_to = '' ) {
        $client_id = get_option( 'sd_google_client_id' );
        if ( empty( $client_id ) ) {
            return '';
        }

        $redirect_uri = home_url( '/?sd-google-callback=1' );
        $state = wp_create_nonce( 'sd_google_auth' ) . '|' . urlencode( $redirect_to );

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( array(
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => 'email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ) );
    }

    /**
     * Handle Google OAuth callback
     */
    public function handle_google_callback() {
        if ( ! isset( $_GET['sd-google-callback'] ) || ! isset( $_GET['code'] ) ) {
            return;
        }

        // Verify state/nonce
        $state = isset( $_GET['state'] ) ? $_GET['state'] : '';
        $state_parts = explode( '|', $state );

        if ( empty( $state_parts[0] ) || ! wp_verify_nonce( $state_parts[0], 'sd_google_auth' ) ) {
            wp_die( __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' ) );
        }

        // Security fix: Validate redirect URL to prevent open redirect attacks
        $redirect_to = isset( $state_parts[1] ) ? urldecode( $state_parts[1] ) : home_url( '/mein-dashboard/' );
        $redirect_to = wp_validate_redirect( $redirect_to, home_url( '/mein-dashboard/' ) );

        // Exchange code for token
        $token_response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
            'body' => array(
                'code'          => sanitize_text_field( $_GET['code'] ),
                'client_id'     => get_option( 'sd_google_client_id' ),
                'client_secret' => get_option( 'sd_google_client_secret' ),
                'redirect_uri'  => home_url( '/?sd-google-callback=1' ),
                'grant_type'    => 'authorization_code',
            ),
        ) );

        if ( is_wp_error( $token_response ) ) {
            wp_redirect( home_url( '/anmelden/?error=google_auth_failed' ) );
            exit;
        }

        $token_data = json_decode( wp_remote_retrieve_body( $token_response ), true );

        if ( empty( $token_data['access_token'] ) ) {
            wp_redirect( home_url( '/anmelden/?error=google_token_failed' ) );
            exit;
        }

        // Get user info from Google
        $user_response = wp_remote_get( 'https://www.googleapis.com/oauth2/v2/userinfo', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token_data['access_token'],
            ),
        ) );

        if ( is_wp_error( $user_response ) ) {
            wp_redirect( home_url( '/anmelden/?error=google_userinfo_failed' ) );
            exit;
        }

        $user_data = json_decode( wp_remote_retrieve_body( $user_response ), true );

        if ( empty( $user_data['email'] ) ) {
            wp_redirect( home_url( '/anmelden/?error=google_email_missing' ) );
            exit;
        }

        // Check if user exists
        $existing_user = get_user_by( 'email', $user_data['email'] );

        if ( $existing_user ) {
            // Log in existing user
            wp_set_current_user( $existing_user->ID );
            wp_set_auth_cookie( $existing_user->ID, true );
        } else {
            // Check if registration is enabled
            if ( ! get_option( 'users_can_register' ) ) {
                wp_redirect( home_url( '/anmelden/?error=registration_disabled' ) );
                exit;
            }

            // Create new user
            $username = sanitize_user( current( explode( '@', $user_data['email'] ) ), true );
            $base_username = $username;
            $counter = 1;
            while ( username_exists( $username ) ) {
                $username = $base_username . $counter;
                $counter++;
            }

            $random_password = wp_generate_password( 16 );
            $user_id = wp_create_user( $username, $random_password, $user_data['email'] );

            if ( is_wp_error( $user_id ) ) {
                wp_redirect( home_url( '/anmelden/?error=user_creation_failed' ) );
                exit;
            }

            // Update user meta
            $firstname = isset( $user_data['given_name'] ) ? sanitize_text_field( $user_data['given_name'] ) : '';
            $lastname = isset( $user_data['family_name'] ) ? sanitize_text_field( $user_data['family_name'] ) : '';

            wp_update_user( array(
                'ID'           => $user_id,
                'first_name'   => $firstname,
                'last_name'    => $lastname,
                'display_name' => trim( $firstname . ' ' . $lastname ) ?: $username,
            ) );

            // Store Google ID for future reference
            update_user_meta( $user_id, '_sd_google_id', sanitize_text_field( $user_data['id'] ) );

            // Log in new user
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id, true );
        }

        // Security fix: Final redirect validation before redirecting
        $redirect_to = wp_validate_redirect( $redirect_to, home_url( '/mein-dashboard/' ) );
        wp_safe_redirect( $redirect_to );
        exit;
    }

    /**
     * Redirect logged in users away from login page
     */
    public function redirect_logged_in_users() {
        if ( is_user_logged_in() && is_page() ) {
            $post = get_post();
            if ( $post && has_shortcode( $post->post_content, 'spezialist_login' ) ) {
                // Don't redirect, the shortcode will handle showing appropriate content
                return;
            }
        }
    }

    /**
     * Enqueue login-specific assets
     */
    public function enqueue_login_assets() {
        if ( ! is_page() ) {
            return;
        }

        $post = get_post();
        if ( ! $post || ! has_shortcode( $post->post_content, 'spezialist_login' ) ) {
            return;
        }

        // Enqueue CSS (use main frontend styles)
        wp_enqueue_style(
            'spezialist-directory-frontend',
            SD_PLUGIN_URL . 'assets/css/frontend-styles.css',
            array(),
            SD_VERSION
        );

        // Enqueue auth JavaScript
        wp_enqueue_script(
            'spezialist-auth',
            SD_PLUGIN_URL . 'assets/js/auth-interactions.js',
            array( 'jquery' ),
            SD_VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'spezialist-auth', 'sdAuthAjax', array(
            'ajaxurl'        => admin_url( 'admin-ajax.php' ),
            'googleAuthUrl'  => $this->get_google_auth_url( isset( $_GET['redirect_to'] ) ? esc_url( $_GET['redirect_to'] ) : '' ),
            'dashboardUrl'   => sd_get_page_url( 'mein-dashboard/' ),
        ) );
    }

    /**
     * Add admin menu for Google OAuth settings
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=hofladen',
            __( 'Google OAuth Einstellungen', 'spezialist-directory' ),
            __( 'Google OAuth', 'spezialist-directory' ),
            'manage_options',
            'sd-google-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'sd_google_settings', 'sd_google_client_id', 'sanitize_text_field' );
        register_setting( 'sd_google_settings', 'sd_google_client_secret', array( $this, 'sanitize_client_secret' ) );
    }

    /**
     * Sanitize client secret - don't overwrite with empty value
     *
     * @param string $new_value
     * @return string
     */
    public function sanitize_client_secret( $new_value ) {
        $new_value = sanitize_text_field( $new_value );

        // If empty, keep the existing value
        if ( empty( $new_value ) ) {
            return get_option( 'sd_google_client_secret' );
        }

        return $new_value;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Google OAuth Einstellungen', 'spezialist-directory' ); ?></h1>

            <p><?php _e( 'Konfigurieren Sie Google OAuth für die Anmeldung mit Google-Konto.', 'spezialist-directory' ); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields( 'sd_google_settings' ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="sd_google_client_id"><?php _e( 'Google Client ID', 'spezialist-directory' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="sd_google_client_id" name="sd_google_client_id"
                                   value="<?php echo esc_attr( get_option( 'sd_google_client_id' ) ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sd_google_client_secret"><?php _e( 'Google Client Secret', 'spezialist-directory' ); ?></label>
                        </th>
                        <td>
                            <?php $has_secret = ! empty( get_option( 'sd_google_client_secret' ) ); ?>
                            <input type="password" id="sd_google_client_secret" name="sd_google_client_secret"
                                   value=""
                                   class="regular-text"
                                   placeholder="<?php echo $has_secret ? '••••••••••••••••' : ''; ?>">
                            <?php if ( $has_secret ) : ?>
                                <p class="description"><?php _e( 'Secret ist gespeichert. Lassen Sie das Feld leer, um es beizubehalten, oder geben Sie einen neuen Wert ein.', 'spezialist-directory' ); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Callback URL', 'spezialist-directory' ); ?></th>
                        <td>
                            <code><?php echo esc_html( home_url( '/?sd-google-callback=1' ) ); ?></code>
                            <p class="description">
                                <?php _e( 'Fügen Sie diese URL in der Google Cloud Console als autorisierte Weiterleitungs-URI hinzu.', 'spezialist-directory' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php _e( 'Einrichtungsanleitung', 'spezialist-directory' ); ?></h2>
                <ol>
                    <li><?php _e( 'Gehen Sie zur <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>', 'spezialist-directory' ); ?></li>
                    <li><?php _e( 'Erstellen Sie ein neues Projekt oder wählen Sie ein bestehendes', 'spezialist-directory' ); ?></li>
                    <li><?php _e( 'Aktivieren Sie die "Google+ API" unter APIs & Dienste', 'spezialist-directory' ); ?></li>
                    <li><?php _e( 'Erstellen Sie unter "Anmeldedaten" eine "OAuth 2.0-Client-ID"', 'spezialist-directory' ); ?></li>
                    <li><?php _e( 'Wählen Sie "Webanwendung" als Anwendungstyp', 'spezialist-directory' ); ?></li>
                    <li><?php printf( __( 'Fügen Sie %s als autorisierte Weiterleitungs-URI hinzu', 'spezialist-directory' ), '<code>' . esc_html( home_url( '/?sd-google-callback=1' ) ) . '</code>' ); ?></li>
                    <li><?php _e( 'Kopieren Sie Client-ID und Client-Secret hierher', 'spezialist-directory' ); ?></li>
                </ol>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get login page URL
     *
     * @param string $redirect_to URL to redirect after login
     * @return string
     */
    public static function get_login_url( $redirect_to = '' ) {
        $login_url = home_url( '/anmelden/' );
        if ( ! empty( $redirect_to ) ) {
            $login_url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $login_url );
        }
        return $login_url;
    }

    /**
     * Get register page URL
     *
     * @param string $redirect_to URL to redirect after registration
     * @return string
     */
    public static function get_register_url( $redirect_to = '' ) {
        $register_url = home_url( '/anmelden/' ) . '#register';
        if ( ! empty( $redirect_to ) ) {
            $register_url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), home_url( '/anmelden/' ) ) . '#register';
        }
        return $register_url;
    }
}
