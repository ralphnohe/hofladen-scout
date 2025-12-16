/**
 * Spezialist Directory - Authentication Page Interactions
 *
 * Handles tab switching, form validation, AJAX submissions,
 * password strength indicator, and forgot password flow.
 *
 * @package Spezialist_Directory
 * @since 1.1.0
 */

(function($) {
    'use strict';

    const SDAuth = {

        // Configuration
        config: {
            minPasswordLength: 8,
            passwordStrengthLevels: ['Sehr schwach', 'Schwach', 'Mittel', 'Stark']
        },

        /**
         * Initialize authentication page
         */
        init: function() {
            if (!$('.sd-auth-wrapper').length) {
                return;
            }

            this.bindTabSwitching();
            this.bindFormSubmissions();
            this.bindPasswordToggle();
            this.bindPasswordStrength();
            this.bindForgotPassword();
            this.handleURLHash();
        },

        /**
         * Tab Switching (Login <-> Register)
         */
        bindTabSwitching: function() {
            const self = this;

            $('.sd-auth-tab').on('click', function() {
                const tab = $(this).data('tab');
                self.switchTab(tab);

                // Update URL hash without scrolling
                history.replaceState(null, null, '#' + tab);
            });
        },

        /**
         * Switch between tabs
         */
        switchTab: function(tab) {
            // Update tabs
            $('.sd-auth-tab').removeClass('active').attr('aria-selected', 'false');
            $('.sd-auth-tab[data-tab="' + tab + '"]').addClass('active').attr('aria-selected', 'true');

            // Update panels
            $('.sd-auth-panel').removeClass('active').attr('hidden', true);

            if (tab === 'login') {
                $('#sd-login-panel').addClass('active').removeAttr('hidden');
            } else if (tab === 'register') {
                $('#sd-register-panel').addClass('active').removeAttr('hidden');
            }

            // Show tabs and divider (might be hidden during forgot password flow)
            $('.sd-auth-tabs').show();
            $('#sd-auth-divider').show();
            $('#sd-auth-social').show();

            // Restore header text
            $('.sd-auth-header .sd-auth-title').text('Willkommen');
            $('.sd-auth-header .sd-auth-subtitle').text('Melde dich an oder erstelle ein Konto');

            // Clear any errors
            this.clearNotice();

            // Focus first input
            setTimeout(function() {
                $('.sd-auth-panel.active .sd-auth-input:first').focus();
            }, 100);
        },

        /**
         * Handle URL hash for direct linking
         */
        handleURLHash: function() {
            const hash = window.location.hash.substring(1);
            if (hash === 'register' || hash === 'registrieren') {
                this.switchTab('register');
            } else if (hash === 'forgot' || hash === 'passwort-vergessen') {
                this.showForgotPassword();
            }
        },

        /**
         * Form Submissions via AJAX
         */
        bindFormSubmissions: function() {
            const self = this;

            // Login form
            $('#sd-login-form').on('submit', function(e) {
                e.preventDefault();
                self.handleLogin($(this));
            });

            // Register form
            $('#sd-register-form').on('submit', function(e) {
                e.preventDefault();
                self.handleRegister($(this));
            });

            // Forgot password form
            $('#sd-forgot-form').on('submit', function(e) {
                e.preventDefault();
                self.handleForgotPassword($(this));
            });
        },

        /**
         * Handle Login
         */
        handleLogin: function($form) {
            const self = this;

            // Validate
            const email = $form.find('[name="email"]').val().trim();
            const password = $form.find('[name="password"]').val();

            if (!this.isValidEmail(email)) {
                this.showNotice('Bitte gib eine gültige E-Mail-Adresse ein.', 'error');
                return;
            }

            if (!password) {
                this.showNotice('Bitte gib dein Passwort ein.', 'error');
                return;
            }

            // Show loading state
            this.setLoadingState($form, true);
            this.clearNotice();

            // AJAX request
            $.ajax({
                url: sdAuthAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_auth_login',
                    nonce: $form.find('[name="sd_login_nonce"]').val(),
                    email: email,
                    password: password,
                    remember: $form.find('[name="remember"]').is(':checked') ? 1 : 0,
                    redirect_to: $form.find('[name="redirect_to"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Erfolgreich angemeldet! Weiterleitung...', 'success');

                        // Redirect
                        setTimeout(function() {
                            window.location.href = response.data.redirect || (typeof sdAuthAjax !== 'undefined' && sdAuthAjax.dashboardUrl ? sdAuthAjax.dashboardUrl : '/mein-dashboard/');
                        }, 1000);
                    } else {
                        self.showNotice(response.data.message || 'Anmeldung fehlgeschlagen.', 'error');
                        self.setLoadingState($form, false);
                    }
                },
                error: function() {
                    self.showNotice('Ein Fehler ist aufgetreten. Bitte versuche es erneut.', 'error');
                    self.setLoadingState($form, false);
                }
            });
        },

        /**
         * Handle Registration
         */
        handleRegister: function($form) {
            const self = this;

            // Validate
            const firstname = $form.find('[name="firstname"]').val().trim();
            const lastname = $form.find('[name="lastname"]').val().trim();
            const email = $form.find('[name="email"]').val().trim();
            const password = $form.find('[name="password"]').val();
            const terms = $form.find('[name="terms"]').is(':checked');

            if (firstname.length < 2) {
                this.showNotice('Bitte gib deinen Vornamen ein.', 'error');
                return;
            }

            if (lastname.length < 2) {
                this.showNotice('Bitte gib deinen Nachnamen ein.', 'error');
                return;
            }

            if (!this.isValidEmail(email)) {
                this.showNotice('Bitte gib eine gültige E-Mail-Adresse ein.', 'error');
                return;
            }

            if (password.length < this.config.minPasswordLength) {
                this.showNotice('Das Passwort muss mindestens ' + this.config.minPasswordLength + ' Zeichen lang sein.', 'error');
                return;
            }

            if (!terms) {
                this.showNotice('Bitte akzeptiere die AGB und Datenschutzerklärung.', 'error');
                return;
            }

            // Show loading state
            this.setLoadingState($form, true);
            this.clearNotice();

            // AJAX request
            $.ajax({
                url: sdAuthAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_auth_register',
                    nonce: $form.find('[name="sd_register_nonce"]').val(),
                    firstname: firstname,
                    lastname: lastname,
                    email: email,
                    password: password,
                    redirect_to: $form.find('[name="redirect_to"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Konto erfolgreich erstellt! Du wirst weitergeleitet...', 'success');

                        setTimeout(function() {
                            window.location.href = response.data.redirect || (typeof sdAuthAjax !== 'undefined' && sdAuthAjax.dashboardUrl ? sdAuthAjax.dashboardUrl : '/mein-dashboard/');
                        }, 1500);
                    } else {
                        self.showNotice(response.data.message || 'Registrierung fehlgeschlagen.', 'error');
                        self.setLoadingState($form, false);
                    }
                },
                error: function() {
                    self.showNotice('Ein Fehler ist aufgetreten. Bitte versuche es erneut.', 'error');
                    self.setLoadingState($form, false);
                }
            });
        },

        /**
         * Handle Forgot Password
         */
        handleForgotPassword: function($form) {
            const self = this;

            const email = $form.find('[name="email"]').val().trim();

            if (!this.isValidEmail(email)) {
                this.showNotice('Bitte gib eine gültige E-Mail-Adresse ein.', 'error');
                return;
            }

            this.setLoadingState($form, true);
            this.clearNotice();

            $.ajax({
                url: sdAuthAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_auth_forgot_password',
                    nonce: $form.find('[name="sd_forgot_nonce"]').val(),
                    email: email
                },
                success: function(response) {
                    self.setLoadingState($form, false);
                    self.showNotice('Falls ein Konto mit dieser E-Mail existiert, haben wir dir einen Link zum Zurücksetzen gesendet.', 'success');
                    $form[0].reset();
                },
                error: function() {
                    self.showNotice('Ein Fehler ist aufgetreten.', 'error');
                    self.setLoadingState($form, false);
                }
            });
        },

        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        /**
         * Password Toggle (Show/Hide)
         */
        bindPasswordToggle: function() {
            $('.sd-auth-toggle-password').on('click', function() {
                const $wrapper = $(this).closest('.sd-auth-input-wrapper');
                const $input = $wrapper.find('.sd-auth-input');
                const $iconEye = $(this).find('.sd-icon-eye');
                const $iconEyeOff = $(this).find('.sd-icon-eye-off');

                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $iconEye.hide();
                    $iconEyeOff.show();
                } else {
                    $input.attr('type', 'password');
                    $iconEye.show();
                    $iconEyeOff.hide();
                }
            });
        },

        /**
         * Password Strength Indicator
         */
        bindPasswordStrength: function() {
            const self = this;

            $('#sd-register-password').on('input', function() {
                const password = $(this).val();
                const strength = self.calculatePasswordStrength(password);

                $('.sd-auth-strength-fill').attr('data-strength', strength);
                $('.sd-auth-strength-text').text(
                    password.length > 0 ? self.config.passwordStrengthLevels[strength - 1] || '' : ''
                );
            });
        },

        /**
         * Calculate password strength (1-4)
         */
        calculatePasswordStrength: function(password) {
            let strength = 0;

            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            return Math.min(4, strength);
        },

        /**
         * Forgot Password Flow
         */
        bindForgotPassword: function() {
            const self = this;

            $('#sd-forgot-password-link').on('click', function(e) {
                e.preventDefault();
                self.showForgotPassword();
            });

            $('#sd-back-to-login').on('click', function(e) {
                e.preventDefault();
                self.switchTab('login');
            });
        },

        /**
         * Show Forgot Password Panel
         */
        showForgotPassword: function() {
            // Hide tabs and social buttons
            $('.sd-auth-tabs').hide();
            $('#sd-auth-divider').hide();
            $('#sd-auth-social').hide();

            // Hide other panels
            $('.sd-auth-panel').removeClass('active').attr('hidden', true);

            // Show forgot panel
            $('#sd-forgot-panel').addClass('active').removeAttr('hidden');

            // Clear notice
            this.clearNotice();

            // Focus email input
            setTimeout(function() {
                $('#sd-forgot-email').focus();
            }, 100);
        },

        /**
         * Set Loading State
         */
        setLoadingState: function($form, isLoading) {
            const $btn = $form.find('.sd-auth-submit');

            if (isLoading) {
                $btn.prop('disabled', true);
                $btn.find('.sd-auth-btn-text').hide();
                $btn.find('.sd-auth-btn-loading').show();
            } else {
                $btn.prop('disabled', false);
                $btn.find('.sd-auth-btn-text').show();
                $btn.find('.sd-auth-btn-loading').hide();
            }
        },

        /**
         * Show Notice
         */
        showNotice: function(message, type) {
            const $notice = $('#sd-auth-notice');

            $notice
                .removeClass('sd-auth-notice-success sd-auth-notice-error')
                .addClass('sd-auth-notice-' + type)
                .html('<p>' + message + '</p>')
                .addClass('show')
                .show();

            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 300);

            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $notice.removeClass('show').hide();
                }, 5000);
            }
        },

        /**
         * Clear Notice
         */
        clearNotice: function() {
            $('#sd-auth-notice').removeClass('show sd-auth-notice-success sd-auth-notice-error').hide();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SDAuth.init();
    });

})(jQuery);
