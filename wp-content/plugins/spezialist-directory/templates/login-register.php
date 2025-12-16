<?php
/**
 * Template: Login/Register Page
 *
 * Custom login and registration with tabs and social login
 * Based on BrandScanner design, adapted for Spezialist-Für.de
 *
 * @package Spezialist_Directory
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get redirect URL from query parameter
$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url( $_GET['redirect_to'] ) : sd_get_page_url( 'mein-dashboard/' );

// Determine active tab from URL hash or query param
$active_tab = isset( $_GET['tab'] ) && $_GET['tab'] === 'register' ? 'register' : 'login';

// Get Google OAuth URL
$google_auth_url = SD_Login_Register::instance()->get_google_auth_url( $redirect_to );
$has_google = ! empty( $google_auth_url );

// Check for error messages from OAuth redirect
$error_message = '';
if ( isset( $_GET['error'] ) ) {
    switch ( $_GET['error'] ) {
        case 'google_auth_failed':
            $error_message = __( 'Google-Anmeldung fehlgeschlagen. Bitte versuche es erneut.', 'spezialist-directory' );
            break;
        case 'google_token_failed':
            $error_message = __( 'Fehler beim Abrufen des Google-Tokens.', 'spezialist-directory' );
            break;
        case 'google_email_missing':
            $error_message = __( 'E-Mail-Adresse konnte nicht von Google abgerufen werden.', 'spezialist-directory' );
            break;
        case 'registration_disabled':
            $error_message = __( 'Die Registrierung ist derzeit deaktiviert.', 'spezialist-directory' );
            break;
        case 'user_creation_failed':
            $error_message = __( 'Fehler bei der Kontoerstellung.', 'spezialist-directory' );
            break;
    }
}

// Check for success messages (e.g., after registration in claim flow)
$success_message = '';
if ( isset( $_GET['registered'] ) && $_GET['registered'] == '1' ) {
    $success_message = __( 'Konto erfolgreich erstellt! Bitte melde dich jetzt mit deinen Zugangsdaten an.', 'spezialist-directory' );
    // Force login tab to be active
    $active_tab = 'login';
}
?>

<div class="sd-auth-wrapper">
    <div class="sd-auth-card">
        <!-- Logo -->
        <div class="sd-auth-logo">
            <a href="<?php echo esc_url( home_url() ); ?>">
                <?php
                $custom_logo_id = get_theme_mod( 'custom_logo' );
                if ( $custom_logo_id ) {
                    echo wp_get_attachment_image( $custom_logo_id, 'medium', false, array(
                        'class' => 'sd-auth-logo-img',
                        'alt'   => get_bloginfo( 'name' ),
                    ) );
                } else {
                    echo '<span class="sd-auth-logo-text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
                }
                ?>
            </a>
        </div>

        <!-- Header -->
        <div class="sd-auth-header">
            <h2 class="sd-auth-title"><?php _e( 'Willkommen', 'spezialist-directory' ); ?></h2>
            <p class="sd-auth-subtitle"><?php _e( 'Melde dich an oder erstelle ein Konto', 'spezialist-directory' ); ?></p>
        </div>

        <!-- Success Notice (e.g., after registration) -->
        <?php if ( ! empty( $success_message ) ) : ?>
            <div class="sd-auth-notice sd-auth-notice-success show">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#059669"/>
                </svg>
                <p><?php echo esc_html( $success_message ); ?></p>
            </div>
        <?php endif; ?>

        <!-- Error Notice -->
        <?php if ( ! empty( $error_message ) ) : ?>
            <div class="sd-auth-notice sd-auth-notice-error show">
                <p><?php echo esc_html( $error_message ); ?></p>
            </div>
        <?php endif; ?>

        <!-- Dynamic Notice Container -->
        <div class="sd-auth-notice" id="sd-auth-notice" style="display: none;"></div>

        <!-- Tab Switcher -->
        <div class="sd-auth-tabs" role="tablist">
            <button
                type="button"
                class="sd-auth-tab <?php echo $active_tab === 'login' ? 'active' : ''; ?>"
                role="tab"
                aria-selected="<?php echo $active_tab === 'login' ? 'true' : 'false'; ?>"
                aria-controls="sd-login-panel"
                id="sd-login-tab"
                data-tab="login"
            >
                <?php _e( 'Anmelden', 'spezialist-directory' ); ?>
            </button>
            <button
                type="button"
                class="sd-auth-tab <?php echo $active_tab === 'register' ? 'active' : ''; ?>"
                role="tab"
                aria-selected="<?php echo $active_tab === 'register' ? 'true' : 'false'; ?>"
                aria-controls="sd-register-panel"
                id="sd-register-tab"
                data-tab="register"
            >
                <?php _e( 'Registrieren', 'spezialist-directory' ); ?>
            </button>
        </div>

        <!-- Login Panel -->
        <div
            class="sd-auth-panel <?php echo $active_tab === 'login' ? 'active' : ''; ?>"
            id="sd-login-panel"
            role="tabpanel"
            aria-labelledby="sd-login-tab"
            <?php echo $active_tab !== 'login' ? 'hidden' : ''; ?>
        >
            <!-- Login Form -->
            <form class="sd-auth-form" id="sd-login-form">
                <?php wp_nonce_field( 'sd_auth_login', 'sd_login_nonce' ); ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">

                <!-- Email Field -->
                <div class="sd-auth-field">
                    <label for="sd-login-email"><?php _e( 'E-Mail-Adresse', 'spezialist-directory' ); ?></label>
                    <input
                        type="email"
                        id="sd-login-email"
                        name="email"
                        class="sd-auth-input"
                        placeholder="<?php esc_attr_e( 'deine@email.de', 'spezialist-directory' ); ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <!-- Password Field -->
                <div class="sd-auth-field">
                    <label for="sd-login-password"><?php _e( 'Passwort', 'spezialist-directory' ); ?></label>
                    <div class="sd-auth-input-wrapper">
                        <input
                            type="password"
                            id="sd-login-password"
                            name="password"
                            class="sd-auth-input"
                            placeholder="<?php esc_attr_e( 'Ihr Passwort', 'spezialist-directory' ); ?>"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="sd-auth-toggle-password" aria-label="<?php esc_attr_e( 'Passwort anzeigen', 'spezialist-directory' ); ?>">
                            <svg class="sd-icon-eye" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="sd-icon-eye-off" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="sd-auth-remember">
                    <label class="sd-auth-checkbox">
                        <input type="checkbox" name="remember" value="1">
                        <span><?php _e( 'Angemeldet bleiben', 'spezialist-directory' ); ?></span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="sd-button sd-button-primary sd-button-large sd-auth-submit">
                    <span class="sd-auth-btn-text"><?php _e( 'Anmelden', 'spezialist-directory' ); ?></span>
                    <span class="sd-auth-btn-loading" style="display: none;">
                        <span class="sd-spinner"></span>
                    </span>
                </button>
            </form>

            <!-- Forgot Password -->
            <div class="sd-auth-forgot">
                <a href="#" id="sd-forgot-password-link"><?php _e( 'Passwort vergessen?', 'spezialist-directory' ); ?></a>
            </div>
        </div>

        <!-- Register Panel -->
        <div
            class="sd-auth-panel <?php echo $active_tab === 'register' ? 'active' : ''; ?>"
            id="sd-register-panel"
            role="tabpanel"
            aria-labelledby="sd-register-tab"
            <?php echo $active_tab !== 'register' ? 'hidden' : ''; ?>
        >
            <?php if ( get_option( 'users_can_register' ) ) : ?>
                <!-- Registration Form -->
                <form class="sd-auth-form" id="sd-register-form">
                    <?php wp_nonce_field( 'sd_auth_register', 'sd_register_nonce' ); ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">

                    <!-- Name Fields -->
                    <div class="sd-auth-row">
                        <div class="sd-auth-field">
                            <label for="sd-register-firstname"><?php _e( 'Vorname', 'spezialist-directory' ); ?></label>
                            <input
                                type="text"
                                id="sd-register-firstname"
                                name="firstname"
                                class="sd-auth-input"
                                placeholder="<?php esc_attr_e( 'Max', 'spezialist-directory' ); ?>"
                                required
                                autocomplete="given-name"
                            >
                        </div>
                        <div class="sd-auth-field">
                            <label for="sd-register-lastname"><?php _e( 'Nachname', 'spezialist-directory' ); ?></label>
                            <input
                                type="text"
                                id="sd-register-lastname"
                                name="lastname"
                                class="sd-auth-input"
                                placeholder="<?php esc_attr_e( 'Mustermann', 'spezialist-directory' ); ?>"
                                required
                                autocomplete="family-name"
                            >
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div class="sd-auth-field">
                        <label for="sd-register-email"><?php _e( 'E-Mail-Adresse', 'spezialist-directory' ); ?></label>
                        <input
                            type="email"
                            id="sd-register-email"
                            name="email"
                            class="sd-auth-input"
                            placeholder="<?php esc_attr_e( 'deine@email.de', 'spezialist-directory' ); ?>"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <!-- Password Field -->
                    <div class="sd-auth-field">
                        <label for="sd-register-password"><?php _e( 'Passwort', 'spezialist-directory' ); ?></label>
                        <div class="sd-auth-input-wrapper">
                            <input
                                type="password"
                                id="sd-register-password"
                                name="password"
                                class="sd-auth-input"
                                placeholder="<?php esc_attr_e( 'Mindestens 8 Zeichen', 'spezialist-directory' ); ?>"
                                required
                                minlength="8"
                                autocomplete="new-password"
                            >
                            <button type="button" class="sd-auth-toggle-password" aria-label="<?php esc_attr_e( 'Passwort anzeigen', 'spezialist-directory' ); ?>">
                                <svg class="sd-icon-eye" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="sd-icon-eye-off" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                        <!-- Password Strength Indicator -->
                        <div class="sd-auth-password-strength">
                            <div class="sd-auth-strength-bar">
                                <div class="sd-auth-strength-fill" data-strength="0"></div>
                            </div>
                            <span class="sd-auth-strength-text"></span>
                        </div>
                    </div>

                    <!-- Terms Checkbox -->
                    <div class="sd-auth-terms">
                        <label class="sd-auth-checkbox">
                            <input type="checkbox" name="terms" value="1" required>
                            <span>
                                <?php
                                printf(
                                    __( 'Ich akzeptiere die %1$sAGB%2$s und %3$sDatenschutzerklärung%4$s', 'spezialist-directory' ),
                                    '<a href="' . esc_url( sd_get_page_url( 'agb/' ) ) . '" target="_blank">',
                                    '</a>',
                                    '<a href="' . esc_url( sd_get_page_url( 'datenschutzerklaerung/' ) ) . '" target="_blank">',
                                    '</a>'
                                );
                                ?>
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="sd-button sd-button-primary sd-button-large sd-auth-submit">
                        <span class="sd-auth-btn-text"><?php _e( 'Konto erstellen', 'spezialist-directory' ); ?></span>
                        <span class="sd-auth-btn-loading" style="display: none;">
                            <span class="sd-spinner"></span>
                        </span>
                    </button>
                </form>
            <?php else : ?>
                <div class="sd-auth-registration-disabled">
                    <p><?php _e( 'Die Registrierung neuer Konten ist derzeit deaktiviert.', 'spezialist-directory' ); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Forgot Password Panel -->
        <div class="sd-auth-panel" id="sd-forgot-panel" hidden>
            <div class="sd-auth-forgot-header">
                <h2 class="sd-auth-title"><?php _e( 'Passwort zurücksetzen', 'spezialist-directory' ); ?></h2>
                <p class="sd-auth-subtitle"><?php _e( 'Gib deine E-Mail-Adresse ein und wir senden dir einen Link zum Zurücksetzen.', 'spezialist-directory' ); ?></p>
            </div>

            <form class="sd-auth-form" id="sd-forgot-form">
                <?php wp_nonce_field( 'sd_auth_forgot', 'sd_forgot_nonce' ); ?>

                <div class="sd-auth-field">
                    <label for="sd-forgot-email"><?php _e( 'E-Mail-Adresse', 'spezialist-directory' ); ?></label>
                    <input
                        type="email"
                        id="sd-forgot-email"
                        name="email"
                        class="sd-auth-input"
                        placeholder="<?php esc_attr_e( 'deine@email.de', 'spezialist-directory' ); ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <button type="submit" class="sd-button sd-button-primary sd-button-large sd-auth-submit">
                    <span class="sd-auth-btn-text"><?php _e( 'Link senden', 'spezialist-directory' ); ?></span>
                    <span class="sd-auth-btn-loading" style="display: none;">
                        <span class="sd-spinner"></span>
                    </span>
                </button>
            </form>

            <div class="sd-auth-back">
                <a href="#" id="sd-back-to-login">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                    </svg>
                    <?php _e( 'Zurück zur Anmeldung', 'spezialist-directory' ); ?>
                </a>
            </div>
        </div>

        <?php if ( $has_google ) : ?>
            <!-- OR Divider -->
            <div class="sd-auth-divider" id="sd-auth-divider">
                <span><?php _e( 'ODER', 'spezialist-directory' ); ?></span>
            </div>

            <!-- Google OAuth Button -->
            <div class="sd-auth-social" id="sd-auth-social">
                <a href="<?php echo esc_url( $google_auth_url ); ?>" class="sd-auth-google">
                    <svg class="sd-auth-google-icon" viewBox="0 0 24 24" width="20" height="20">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span><?php _e( 'Mit Google fortfahren', 'spezialist-directory' ); ?></span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Footer - Back to Homepage -->
        <div class="sd-auth-footer">
            <a href="<?php echo esc_url( home_url() ); ?>" class="sd-auth-home-link">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"></path>
                </svg>
                <?php _e( 'Zurück zur Startseite', 'spezialist-directory' ); ?>
            </a>
        </div>
    </div>
</div>
