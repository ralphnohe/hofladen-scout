/**
 * Spezialist Directory - Minimal JavaScript Interactions
 *
 * SSR-first approach with minimal client-side JavaScript
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Guard against multiple script execution
    if (window.sdDirectoryLoaded) {
        return;
    }
    window.sdDirectoryLoaded = true;

    /**
     * Show notice message
     */
    function showNotice(message, type = 'info') {
        const noticeClass = 'sd-notice-' + type;
        const $notice = $('.sd-notice:first');

        $notice
            .removeClass('sd-notice-success sd-notice-error sd-notice-warning sd-notice-info')
            .addClass(noticeClass)
            .html('<p>' + message + '</p>')
            .fadeIn();

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
        }

        // Scroll to notice
        $('html, body').animate({
            scrollTop: $notice.offset().top - 100
        }, 300);
    }

    /**
     * Show dashboard notice message
     */
    function showDashboardNotice(message, type = 'info') {
        const noticeClass = 'sd-notice-' + type;
        const $notice = $('.sd-dashboard-notice');

        if ($notice.length === 0) {
            return showNotice(message, type);
        }

        $notice
            .removeClass('sd-notice-success sd-notice-error sd-notice-warning sd-notice-info')
            .addClass(noticeClass)
            .html('<p>' + message + '</p>')
            .fadeIn();

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
        }

        // Scroll to notice
        $('html, body').animate({
            scrollTop: $notice.offset().top - 100
        }, 300);
    }

    /**
     * TinyMCE Yellow Background
     * Sets yellow background color for the editor iframe
     */
    function setTinyMCEYellowBackground() {
        const iframe = document.getElementById('sd_description_ifr');
        if (iframe && iframe.contentDocument && iframe.contentDocument.body) {
            iframe.contentDocument.body.style.backgroundColor = '#fffae2';
        }
    }

    // Initialize TinyMCE yellow background using multiple strategies
    $(document).ready(function() {
        // Strategy 1: Poll for iframe (handles delayed loading)
        const checkEditor = setInterval(function() {
            const iframe = document.getElementById('sd_description_ifr');
            if (iframe && iframe.contentDocument && iframe.contentDocument.body) {
                iframe.contentDocument.body.style.backgroundColor = '#fffae2';
                clearInterval(checkEditor);
            }
        }, 200);

        // Clear after 10 seconds to prevent infinite loop
        setTimeout(function() {
            clearInterval(checkEditor);
        }, 10000);

        // Strategy 2: Use TinyMCE init event if available
        if (typeof tinymce !== 'undefined') {
            tinymce.on('AddEditor', function(e) {
                e.editor.on('init', function() {
                    if (this.id === 'sd_description') {
                        this.getBody().style.backgroundColor = '#fffae2';
                    }
                });
            });
        }

        // Strategy 3: Also try window load event
        $(window).on('load', function() {
            setTimeout(setTinyMCEYellowBackground, 500);
        });
    });

    /**
     * Premium Field Monitoring for Submission Form
     * Shows plan selection when any premium field is filled
     */
    var SDPremiumFormMonitor = {
        premiumFieldsFilled: false,

        init: function() {
            if ($('#sd-submission-form').length === 0) return;

            var self = this;

            // Monitor gallery file input
            $(document).on('change', '#sd-submission-form input[name="gallery[]"]', function() {
                self.checkPremiumFields();
            });

            // Monitor video file input
            $(document).on('change', '#sd-submission-form input[name="video"]', function() {
                self.checkPremiumFields();
            });

            // Monitor services - check when inputs change or are added/removed
            $(document).on('input change', '#sd-submission-form input[name="services[]"]', function() {
                self.checkPremiumFields();
            });

            // Monitor business hours checkboxes
            $(document).on('change', '#sd-submission-form input[name^="business_hours"][name$="[open]"]', function() {
                self.checkPremiumFields();
            });

            // Monitor social media fields
            $(document).on('input', '#sd-submission-form input[name="facebook"], #sd-submission-form input[name="twitter"], #sd-submission-form input[name="instagram"], #sd-submission-form input[name="linkedin"], #sd-submission-form input[name="youtube"], #sd-submission-form input[name="xing"]', function() {
                self.checkPremiumFields();
            });

            // Also check when service items are added/removed
            $(document).on('click', '.sd-add-service, .sd-remove-service', function() {
                setTimeout(function() { self.checkPremiumFields(); }, 100);
            });

            // Initial check
            this.checkPremiumFields();
        },

        checkPremiumFields: function() {
            var hasPremium = false;
            var $form = $('#sd-submission-form');

            // Check gallery files (use SDGalleryPreview.pendingFiles if available)
            if (typeof SDGalleryPreview !== 'undefined' && SDGalleryPreview.hasPendingFiles()) {
                hasPremium = true;
            } else {
                // Fallback to checking input directly
                var $gallery = $form.find('input[name="gallery[]"]');
                if ($gallery.length && $gallery[0].files && $gallery[0].files.length > 0) {
                    hasPremium = true;
                }
            }

            // Check video file (use SDVideoPreview.pendingFile if available)
            if (typeof SDVideoPreview !== 'undefined' && SDVideoPreview.hasPendingVideo()) {
                hasPremium = true;
            } else {
                // Fallback to checking input directly
                var $video = $form.find('input[name="video"]');
                if ($video.length && $video[0].files && $video[0].files.length > 0) {
                    hasPremium = true;
                }
            }

            // Check services (any non-empty service input)
            $form.find('input[name="services[]"]').each(function() {
                if ($(this).val().trim() !== '') {
                    hasPremium = true;
                }
            });

            // Check business hours (any day marked as open)
            $form.find('input[name^="business_hours"][name$="[open]"]:checked').each(function() {
                hasPremium = true;
            });

            // Check social media fields
            var socialFields = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'xing'];
            socialFields.forEach(function(field) {
                var $field = $form.find('input[name="' + field + '"]');
                if ($field.length && $field.val().trim() !== '') {
                    hasPremium = true;
                }
            });

            this.premiumFieldsFilled = hasPremium;
            this.updateUI(hasPremium);
        },

        updateUI: function(hasPremium) {
            var $planSection = $('#sd-premium-plan-selection');
            var $submitBtn = $('#sd-submit-button');
            var $submitText = $submitBtn.find('.sd-button-text');

            if (hasPremium) {
                $planSection.slideDown(300);
                $submitText.text('Einreichen & zur Zahlung');
            } else {
                $planSection.slideUp(300);
                $submitText.text('Eintrag einreichen');
            }
        },

        showCheckoutOverlay: function() {
            $('#sd-checkout-overlay').fadeIn(200);
        },

        hideCheckoutOverlay: function() {
            $('#sd-checkout-overlay').fadeOut(200);
        },

        getSelectedPlan: function() {
            return $('input[name="premium_plan"]:checked').val() || 'monthly';
        }
    };

    // Initialize premium form monitor
    $(document).ready(function() {
        SDPremiumFormMonitor.init();
    });

    /**
     * All-Day Checkbox Handler for Business Hours
     * Sets from/to fields to 00:00-24:00 when checked
     */
    $(document).ready(function() {
        if ($('#sd-submission-form').length === 0) return;

        // Handle all-day checkbox changes
        $(document).on('change', '.sd-allday-toggle', function() {
            var day = $(this).data('day');
            var $fromInput = $('input.sd-time-from[data-day="' + day + '"]');
            var $toInput = $('input.sd-time-to[data-day="' + day + '"]');

            if ($(this).is(':checked')) {
                $fromInput.val('00:00');
                $toInput.val('23:59');
            } else {
                $fromInput.val('');
                $toInput.val('');
            }
        });

        // Add placeholder inside TinyMCE editor
        var descriptionPlaceholder = 'Beschreibe hier möglichst detailliert Deinen Hofladen, Standort, Ausrichtung / Schwerpunkte, angebotene Produkte, Parkmöglichkeiten, akzeptierte Zahlungsmittel, usw.';

        function setupDescriptionPlaceholder() {
            if (typeof tinymce === 'undefined') return;

            var editor = tinymce.get('sd_description');
            if (!editor) return;

            var $body = $(editor.getBody());
            if (!$body.length) return;

            // Add placeholder styles to editor iframe
            var placeholderCSS =
                'body.sd-placeholder-visible::before {' +
                '  content: "' + descriptionPlaceholder + '";' +
                '  color: #9CA3AF;' +
                '  font-style: italic;' +
                '  pointer-events: none;' +
                '  position: absolute;' +
                '  top: 0;' +
                '  left: 0;' +
                '  right: 0;' +
                '}' +
                'body.sd-placeholder-visible {' +
                '  position: relative;' +
                '}';

            // Inject CSS into editor iframe
            var $head = $(editor.getDoc()).find('head');
            if ($head.find('#sd-placeholder-style').length === 0) {
                $head.append('<style id="sd-placeholder-style">' + placeholderCSS + '</style>');
            }

            function updatePlaceholder() {
                var content = editor.getContent({ format: 'text' }).trim();
                if (content === '') {
                    $body.addClass('sd-placeholder-visible');
                } else {
                    $body.removeClass('sd-placeholder-visible');
                }
            }

            // Bind events
            editor.on('keyup change SetContent init', updatePlaceholder);
            editor.on('focus', function() {
                // Keep placeholder visible until typing starts
            });

            // Initial check
            updatePlaceholder();
        }

        // Try multiple strategies to setup placeholder
        function trySetupPlaceholder() {
            setupDescriptionPlaceholder();
        }

        setTimeout(trySetupPlaceholder, 1000);
        setTimeout(trySetupPlaceholder, 2000);
        setTimeout(trySetupPlaceholder, 3000);

        $(window).on('load', function() {
            setTimeout(trySetupPlaceholder, 500);
            setTimeout(trySetupPlaceholder, 1500);
        });

        if (typeof tinymce !== 'undefined') {
            tinymce.on('AddEditor', function(e) {
                if (e.editor.id === 'sd_description') {
                    e.editor.on('init', function() {
                        setTimeout(trySetupPlaceholder, 100);
                    });
                }
            });
        }
    });

    /**
     * Social Media URL Helper
     * Converts usernames to full URLs for social media fields
     */
    var SDSocialMediaHelper = {
        prefixes: {
            facebook: 'https://facebook.com/',
            twitter: 'https://x.com/',
            instagram: 'https://instagram.com/',
            linkedin: 'https://linkedin.com/in/',
            youtube: 'https://youtube.com/@',
            xing: 'https://xing.com/profile/'
        },

        // Convert username to full URL
        buildUrl: function(username, platform) {
            if (!username) return '';
            username = username.trim();
            // If user entered a full URL, return as-is
            if (username.indexOf('://') !== -1) {
                return username;
            }
            // Remove @ prefix if present (common mistake)
            if (username.charAt(0) === '@') {
                username = username.substring(1);
            }
            return this.prefixes[platform] + username;
        },

        // Extract username from URL (for editing)
        extractUsername: function(url, platform) {
            if (!url) return '';
            var prefix = this.prefixes[platform];
            // Handle both http and https
            var prefixVariants = [
                prefix,
                prefix.replace('https://', 'http://'),
                prefix.replace('https://', 'https://www.'),
                prefix.replace('https://', 'http://www.')
            ];
            for (var i = 0; i < prefixVariants.length; i++) {
                if (url.indexOf(prefixVariants[i]) === 0) {
                    return url.substring(prefixVariants[i].length);
                }
            }
            // If no prefix matches and it's not a URL, return as-is
            if (url.indexOf('://') === -1) {
                return url;
            }
            return url;
        },

        // Convert all social inputs to URLs before form submit
        convertInputsToUrls: function($form) {
            var self = this;
            var fields = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'xing'];
            fields.forEach(function(field) {
                var $input = $form.find('input[name="' + field + '"]');
                if ($input.length && $input.val().trim()) {
                    $input.val(self.buildUrl($input.val(), field));
                }
            });
        }
    };

    /**
     * Form Submission
     */
    $('#sd-submission-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $('#sd-submit-button');

        // Convert social media usernames to full URLs before creating FormData
        SDSocialMediaHelper.convertInputsToUrls($form);

        const formData = new FormData(this);
        const hasPremiumFields = SDPremiumFormMonitor.premiumFieldsFilled;
        const selectedPlan = SDPremiumFormMonitor.getSelectedPlan();

        // Replace gallery files with pending files from SDGalleryPreview
        formData.delete('gallery[]');
        if (typeof SDGalleryPreview !== 'undefined' && SDGalleryPreview.pendingFiles.length > 0) {
            SDGalleryPreview.pendingFiles.forEach(file => {
                formData.append('gallery[]', file);
            });
        }

        // Get editor content
        if (typeof tinyMCE !== 'undefined') {
            const editor = tinyMCE.get('sd_description');
            if (editor) {
                formData.set('description', editor.getContent());
            }
        }

        formData.append('action', 'sd_submit_spezialist');
        formData.append('nonce', $form.find('[name="sd_submission_nonce"]').val());

        // Add selected plan if premium fields are filled
        if (hasPremiumFields) {
            formData.append('premium_plan', selectedPlan);
        }

        // Show loading state
        $submitBtn.prop('disabled', true);
        $submitBtn.find('.sd-button-text').hide();
        $submitBtn.find('.sd-button-loading').show();

        // Show checkout overlay if premium fields are filled
        if (hasPremiumFields) {
            SDPremiumFormMonitor.showCheckoutOverlay();
        }

        $.ajax({
            url: sdAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $form[0].reset();

                    // Clear gallery preview and pending files
                    if (typeof SDGalleryPreview !== 'undefined') {
                        SDGalleryPreview.clearPendingFiles();
                        $('#sd-gallery-preview').empty();
                    }

                    // Check if checkout is needed (premium fields filled)
                    if (response.data.needs_checkout && response.data.post_id) {
                        // Keep overlay visible, trigger Stripe checkout for the new submission
                        SDPremium.createSubmissionCheckout(
                            response.data.post_id,
                            response.data.checkout_plan || selectedPlan || 'monthly'
                        );
                    } else if (response.data.redirect) {
                        // Hide overlay and redirect
                        SDPremiumFormMonitor.hideCheckoutOverlay();
                        // Standard redirect after 2 seconds
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 2000);
                    }
                } else {
                    SDPremiumFormMonitor.hideCheckoutOverlay();
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                SDPremiumFormMonitor.hideCheckoutOverlay();
                showNotice('Ein Fehler ist aufgetreten. Bitte versuche es erneut.', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
                $submitBtn.find('.sd-button-text').show();
                $submitBtn.find('.sd-button-loading').hide();
                // Reset button text based on current premium field state
                SDPremiumFormMonitor.checkPremiumFields();
            }
        });
    });

    /**
     * Delete Listing
     */
    $(document).on('click', '.sd-delete-listing', function(e) {
        e.preventDefault();

        if (!confirm('Bist du sicher, dass du diesen Eintrag löschen möchtest?')) {
            return;
        }

        const $button = $(this);
        const postId = $button.data('post-id');

        $.ajax({
            url: sdAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sd_delete_listing',
                post_id: postId,
                nonce: sdAjax.deleteNonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    // Check if this is a premium listing before removing
                    var $card = $button.closest('.sd-listing-card-item');
                    var isPremium = $card.find('.sd-premium-badge-small').length > 0;

                    // Remove the listing card with animation
                    $card.slideUp(300, function() {
                        $(this).remove();

                        // Update the "Ihre Einträge" stat counter
                        var $statBox = $('#sd-stat-listings .sd-stat-number');
                        if ($statBox.length) {
                            var currentCount = parseInt($statBox.text(), 10) || 0;
                            $statBox.text(Math.max(0, currentCount - 1));
                        }

                        // Update the "Premium Einträge" stat counter if it was a premium listing
                        if (isPremium) {
                            var $premiumStatBox = $('#sd-stat-premium .sd-stat-number');
                            if ($premiumStatBox.length) {
                                var currentPremiumCount = parseInt($premiumStatBox.text(), 10) || 0;
                                $premiumStatBox.text(Math.max(0, currentPremiumCount - 1));
                            }
                        }
                    });
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('Ein Fehler ist aufgetreten.', 'error');
            }
        });
    });

    /**
     * Duplicate Listing
     */
    $(document).on('click', '.sd-duplicate-listing', function(e) {
        e.preventDefault();

        const $button = $(this);
        const postId = $button.data('post-id');

        if (!confirm('Möchtest du diesen Eintrag duplizieren? Die Kopie wird als Entwurf gespeichert.')) {
            return;
        }

        $button.prop('disabled', true);

        $.ajax({
            url: sdAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sd_duplicate_listing',
                post_id: postId,
                nonce: sdAjax.duplicateNonce
            },
            success: function(response) {
                if (response.success) {
                    showDashboardNotice(response.data.message, 'success');

                    // Reload page to show the new duplicate listing
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showDashboardNotice(response.data.message, 'error');
                }
            },
            error: function() {
                showDashboardNotice('Ein Fehler ist aufgetreten.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    /**
     * Pause/Unpause Listing
     */
    $(document).on('click', '.sd-pause-listing', function(e) {
        e.preventDefault();

        const $button = $(this);
        const postId = $button.data('post-id');
        const $card = $button.closest('.sd-listing-card-item');
        const isPaused = $card.data('paused') === 1 || $card.data('paused') === '1';
        const confirmMsg = isPaused
            ? 'Möchtest du diesen Eintrag wieder aktivieren?'
            : 'Möchtest du diesen Eintrag pausieren? Er wird dann in der Suche nicht mehr angezeigt.';

        if (!confirm(confirmMsg)) {
            return;
        }

        $button.prop('disabled', true);

        $.ajax({
            url: sdAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sd_toggle_pause_listing',
                post_id: postId,
                nonce: sdAjax.togglePauseNonce
            },
            success: function(response) {
                if (response.success) {
                    showDashboardNotice(response.data.message, 'success');

                    // Update UI
                    const newPausedState = response.data.is_paused;
                    $card.data('paused', newPausedState ? '1' : '0');

                    if (newPausedState) {
                        $card.addClass('sd-listing-paused');
                        $button.addClass('sd-action-paused');
                        $button.attr('title', 'Aktivieren');
                        // Switch icon to play
                        $button.html('<svg class="sd-icon-play" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 5v14l11-7z" fill="currentColor"/></svg>');
                        // Add paused badge if not exists
                        const $titleRow = $card.find('.sd-listing-row-title');
                        if (!$titleRow.find('.sd-paused-badge').length) {
                            $titleRow.append('<span class="sd-paused-badge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" fill="currentColor"/></svg> Pausiert</span>');
                        }
                    } else {
                        $card.removeClass('sd-listing-paused');
                        $button.removeClass('sd-action-paused');
                        $button.attr('title', 'Pausieren');
                        // Switch icon to pause
                        $button.html('<svg class="sd-icon-pause" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" fill="currentColor"/></svg>');
                        // Remove paused badge
                        $card.find('.sd-paused-badge').remove();
                    }
                } else {
                    showDashboardNotice(response.data.message, 'error');
                }
            },
            error: function() {
                showDashboardNotice('Ein Fehler ist aufgetreten.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    /**
     * Claim Listing - Show inline form for logged-in users
     */
    $(document).on('click', '.sd-show-claim-form-btn', function(e) {
        e.preventDefault();

        const $button = $(this);
        const $inlineForm = $('#sd-inline-claim-form');

        // Hide the button and show the form
        $button.slideUp(200, function() {
            $inlineForm.slideDown(300, function() {
                // Focus on the textarea
                $inlineForm.find('textarea').focus();
            });
        });
    });

    /**
     * Claim Listing - Submit claim form via AJAX
     */
    $(document).on('submit', '#sd-claim-form', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('.sd-claim-submit-btn');
        const $btnText = $submitBtn.find('.sd-btn-text');
        const $btnLoading = $submitBtn.find('.sd-btn-loading');
        const $successDiv = $form.siblings('.sd-claim-success');

        const postId = $form.find('input[name="post_id"]').val();
        const message = $form.find('textarea[name="message"]').val().trim();
        const nonce = $form.find('#sd_claim_nonce').val();

        // Validate message
        if (!message) {
            $form.find('textarea').addClass('sd-input-error').focus();
            return;
        }

        // Show loading state
        $submitBtn.prop('disabled', true);
        $btnText.hide();
        $btnLoading.show();

        $.ajax({
            url: sdAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sd_claim_listing',
                post_id: postId,
                message: message,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Hide form and show success message
                    $form.slideUp(200, function() {
                        $successDiv.slideDown(300);
                    });
                } else {
                    // Show error
                    alert(response.data.message);
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoading.hide();
                }
            },
            error: function() {
                alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
                $submitBtn.prop('disabled', false);
                $btnText.show();
                $btnLoading.hide();
            }
        });
    });

    /**
     * Claim form textarea validation - remove error class on input
     */
    $(document).on('input', '#sd-claim-form textarea', function() {
        $(this).removeClass('sd-input-error');
    });

    /**
     * Edit Listing Modal
     */
    const SDEditModal = {
        $modal: null,
        $form: null,
        $loading: null,
        currentPostId: null,

        init: function() {
            this.$modal = $('#sd-edit-modal');
            this.$form = $('#sd-edit-form');
            this.$loading = $('#sd-edit-loading');

            if (!this.$modal.length) return;

            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Open modal on edit click
            $(document).on('click', '.sd-edit-listing', function(e) {
                e.preventDefault();
                const postId = $(this).data('post-id');
                self.openModal(postId);
            });

            // Form submission
            this.$form.on('submit', function(e) {
                e.preventDefault();
                self.submitForm();
            });

            // Gallery image removal
            $(document).on('click', '.sd-gallery-remove', function(e) {
                e.preventDefault();
                const imgId = $(this).data('id');
                const $item = $(this).closest('.sd-gallery-edit-item');

                // Track removed image IDs
                if (!self.removedGalleryIds) {
                    self.removedGalleryIds = [];
                }
                self.removedGalleryIds.push(imgId);

                // Remove from UI with animation
                $item.fadeOut(200, function() {
                    $(this).remove();
                    // Show "no images" message if all removed
                    if ($('#sd-edit-gallery-current .sd-gallery-edit-item').length === 0) {
                        $('#sd-edit-gallery-current').append('<p class="sd-no-gallery">' + (sdAjax.i18n?.noGalleryImages || 'Keine Bilder in der Galerie.') + '</p>');
                    }
                });
            });

            // Video removal
            $(document).on('click', '#sd-edit-video-remove', function(e) {
                e.preventDefault();
                const $videoPreview = $(this).closest('.sd-video-preview');

                // Mark video for removal
                self.removeVideo = true;

                // Remove from UI with animation
                $videoPreview.fadeOut(200, function() {
                    $(this).replaceWith('<p class="sd-no-video">' + (sdAjax.i18n?.noVideo || 'Kein Video vorhanden.') + '</p>');
                });
            });
        },

        openModal: function(postId) {
            this.currentPostId = postId;
            this.$modal.fadeIn(200);
            this.$form.hide();
            this.$loading.show();
            this.loadListingData(postId);
        },

        closeModal: function() {
            this.$modal.fadeOut(200);
            this.currentPostId = null;
            this.$form[0].reset();
        },

        loadListingData: function(postId) {
            const self = this;

            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_get_listing_data',
                    post_id: postId,
                    nonce: sdAjax.editNonce
                },
                success: function(response) {
                    if (response.success) {
                        self.populateForm(response.data);
                        self.$loading.hide();
                        self.$form.fadeIn(200);
                    } else {
                        alert(response.data.message || 'Fehler beim Laden der Daten.');
                        self.closeModal();
                    }
                },
                error: function() {
                    alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
                    self.closeModal();
                }
            });
        },

        populateForm: function(data) {
            // Hidden ID
            $('#sd-edit-post-id').val(data.id);

            // Basic info
            $('#sd-edit-title').val(data.title || '');
            $('#sd-edit-description').val(data.description || '');

            // Contact info
            $('#sd-edit-phone').val(data.phone || '');
            $('#sd-edit-email').val(data.email || '');
            $('#sd-edit-website').val(data.website || '');

            // Address
            $('#sd-edit-address').val(data.address || '');
            $('#sd-edit-zip').val(data.zip || '');
            $('#sd-edit-city').val(data.city || '');

            // Social media - extract username from URL for display
            $('#sd-edit-facebook').val(SDSocialMediaHelper.extractUsername(data.facebook || '', 'facebook'));
            $('#sd-edit-instagram').val(SDSocialMediaHelper.extractUsername(data.instagram || '', 'instagram'));
            $('#sd-edit-linkedin').val(SDSocialMediaHelper.extractUsername(data.linkedin || '', 'linkedin'));
            $('#sd-edit-xing').val(SDSocialMediaHelper.extractUsername(data.xing || '', 'xing'));
            $('#sd-edit-twitter').val(SDSocialMediaHelper.extractUsername(data.twitter || '', 'twitter'));
            $('#sd-edit-youtube').val(SDSocialMediaHelper.extractUsername(data.youtube || '', 'youtube'));

            // Populate category dropdown
            const $categorySelect = $('#sd-edit-category');
            $categorySelect.empty();
            if (data.all_categories && data.all_categories.length) {
                data.all_categories.forEach(function(cat) {
                    const selected = data.categories && data.categories.includes(cat.id) ? 'selected' : '';
                    $categorySelect.append('<option value="' + cat.id + '" ' + selected + '>' + cat.name + '</option>');
                });
            }

            // Populate location dropdown
            const $locationSelect = $('#sd-edit-location');
            $locationSelect.empty();
            if (data.all_locations && data.all_locations.length) {
                data.all_locations.forEach(function(loc) {
                    const selected = data.locations && data.locations.includes(loc.id) ? 'selected' : '';
                    $locationSelect.append('<option value="' + loc.id + '" ' + selected + '>' + loc.name + '</option>');
                });
            }

            // Business hours
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            days.forEach(function(day) {
                const dayData = data.business_hours && data.business_hours[day] ? data.business_hours[day] : {};
                $('#sd-edit-hours-' + day + '-open').prop('checked', dayData.open || false);
                $('#sd-edit-hours-' + day + '-from').val(dayData.from || '');
                $('#sd-edit-hours-' + day + '-to').val(dayData.to || '');
                $('#sd-edit-hours-' + day + '-break-from').val(dayData.break_from || '');
                $('#sd-edit-hours-' + day + '-break-to').val(dayData.break_to || '');
            });

            // Services
            const $servicesList = $('#sd-edit-services-list');
            $servicesList.empty();
            if (data.services && data.services.length) {
                data.services.forEach(function(service) {
                    $servicesList.append(SDServices.createServiceItem(service, 'edit'));
                });
            }

            // Gallery section (Premium only)
            const $gallerySection = $('#sd-edit-gallery-section');
            const $galleryContainer = $('.sd-gallery-edit-container');
            const $galleryNotPremium = $('#sd-edit-gallery-not-premium');
            const $galleryCurrent = $('#sd-edit-gallery-current');

            // Show gallery section
            $gallerySection.show();

            if (data.is_premium) {
                // Show gallery editing for premium users
                $galleryContainer.show();
                $galleryNotPremium.hide();

                // Populate existing gallery images
                $galleryCurrent.empty();
                if (data.gallery_images && data.gallery_images.length) {
                    data.gallery_images.forEach(function(img) {
                        $galleryCurrent.append(
                            '<div class="sd-gallery-edit-item" data-id="' + img.id + '">' +
                                '<img src="' + img.thumbnail + '" alt="">' +
                                '<button type="button" class="sd-gallery-remove" data-id="' + img.id + '" title="Entfernen">&times;</button>' +
                            '</div>'
                        );
                    });
                } else {
                    $galleryCurrent.append('<p class="sd-no-gallery">' + (sdAjax.i18n?.noGalleryImages || 'Keine Bilder in der Galerie.') + '</p>');
                }

                // Store original gallery IDs for tracking removed images
                self.originalGalleryIds = data.gallery_images ? data.gallery_images.map(img => img.id) : [];
                self.removedGalleryIds = [];
            } else {
                // Show upgrade prompt for non-premium users
                $galleryContainer.hide();
                $galleryNotPremium.show();
                $galleryCurrent.empty();
                self.originalGalleryIds = [];
                self.removedGalleryIds = [];
            }

            // Clear file input
            $('#sd-edit-gallery').val('');

            // Profile image preview
            const $profileImageContainer = $('#sd-edit-current-profile-image');
            $profileImageContainer.empty();
            if (data.featured_image) {
                $profileImageContainer.append(
                    '<img src="' + data.featured_image + '" alt="Aktuelles Profilbild" class="sd-profile-image-preview">'
                );
            } else {
                $profileImageContainer.append('<p class="sd-no-image">' + (sdAjax.i18n?.noProfileImage || 'Kein Profilbild vorhanden.') + '</p>');
            }
            // Clear profile image file input
            $('#sd-edit-profile-image').val('');

            // Video section (Premium only)
            const $videoSection = $('#sd-edit-video-section');
            const $videoContainer = $('.sd-video-edit-container');
            const $videoNotPremium = $('#sd-edit-video-not-premium');
            const $videoCurrent = $('#sd-edit-video-current');

            if (data.is_premium) {
                // Show video section for premium users
                $videoSection.show();
                $videoContainer.show();
                $videoNotPremium.hide();

                // Show current video if exists
                $videoCurrent.empty();
                if (data.video_url) {
                    $videoCurrent.append(
                        '<div class="sd-video-preview">' +
                            '<video controls preload="metadata" style="max-width: 100%; max-height: 200px;">' +
                                '<source src="' + data.video_url + '" type="video/mp4">' +
                                'Dein Browser unterstützt keine Video-Wiedergabe.' +
                            '</video>' +
                            '<button type="button" class="sd-video-remove" id="sd-edit-video-remove" title="Video entfernen">&times; Video entfernen</button>' +
                        '</div>'
                    );
                    self.removeVideo = false;
                } else {
                    $videoCurrent.append('<p class="sd-no-video">' + (sdAjax.i18n?.noVideo || 'Kein Video vorhanden.') + '</p>');
                    self.removeVideo = false;
                }
            } else {
                // Show upgrade prompt for non-premium users
                $videoSection.show();
                $videoContainer.hide();
                $videoNotPremium.show();
                $videoCurrent.empty();
                self.removeVideo = false;
            }

            // Clear video file input
            $('#sd-edit-video').val('');

            // Trigger custom event for location picker with data including coordinates
            $(document).trigger('sd-edit-form-populated', [data]);
        },

        submitForm: function() {
            const self = this;
            const $submitBtn = $('#sd-edit-submit');
            const $btnText = $submitBtn.find('.sd-btn-text');
            const $btnLoading = $submitBtn.find('.sd-btn-loading');

            // Use FormData for file upload support
            const formData = new FormData();
            formData.append('action', 'sd_update_listing');
            formData.append('nonce', sdAjax.updateNonce);
            formData.append('post_id', $('#sd-edit-post-id').val());
            formData.append('title', $('#sd-edit-title').val());
            formData.append('description', $('#sd-edit-description').val());
            formData.append('phone', $('#sd-edit-phone').val());
            formData.append('email', $('#sd-edit-email').val());
            formData.append('website', $('#sd-edit-website').val());
            formData.append('address', $('#sd-edit-address').val());
            formData.append('zip', $('#sd-edit-zip').val());
            formData.append('city', $('#sd-edit-city').val());
            // Include coordinates from location picker
            formData.append('latitude', $('#sd-edit-latitude').val());
            formData.append('longitude', $('#sd-edit-longitude').val());
            // Convert social media usernames to full URLs
            formData.append('facebook', SDSocialMediaHelper.buildUrl($('#sd-edit-facebook').val(), 'facebook'));
            formData.append('instagram', SDSocialMediaHelper.buildUrl($('#sd-edit-instagram').val(), 'instagram'));
            formData.append('linkedin', SDSocialMediaHelper.buildUrl($('#sd-edit-linkedin').val(), 'linkedin'));
            formData.append('xing', SDSocialMediaHelper.buildUrl($('#sd-edit-xing').val(), 'xing'));
            formData.append('twitter', SDSocialMediaHelper.buildUrl($('#sd-edit-twitter').val(), 'twitter'));
            formData.append('youtube', SDSocialMediaHelper.buildUrl($('#sd-edit-youtube').val(), 'youtube'));

            // Categories and locations
            const categories = $('#sd-edit-category').val() || [];
            const locations = $('#sd-edit-location').val() || [];
            categories.forEach(cat => formData.append('category[]', cat));
            locations.forEach(loc => formData.append('location[]', loc));

            // Collect services data
            $('#sd-edit-services-list .sd-service-item').each(function() {
                const service = $(this).find('.sd-service-text').text().trim();
                if (service) {
                    formData.append('services[]', service);
                }
            });

            // Collect business hours data
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            days.forEach(function(day) {
                formData.append('business_hours[' + day + '][open]', $('#sd-edit-hours-' + day + '-open').is(':checked') ? '1' : '');
                formData.append('business_hours[' + day + '][from]', $('#sd-edit-hours-' + day + '-from').val());
                formData.append('business_hours[' + day + '][to]', $('#sd-edit-hours-' + day + '-to').val());
                formData.append('business_hours[' + day + '][break_from]', $('#sd-edit-hours-' + day + '-break-from').val());
                formData.append('business_hours[' + day + '][break_to]', $('#sd-edit-hours-' + day + '-break-to').val());
            });

            // Gallery: removed image IDs
            if (self.removedGalleryIds && self.removedGalleryIds.length) {
                self.removedGalleryIds.forEach(id => formData.append('removed_gallery_ids[]', id));
            }

            // Gallery: new image files
            const galleryInput = document.getElementById('sd-edit-gallery');
            if (galleryInput && galleryInput.files.length > 0) {
                for (let i = 0; i < galleryInput.files.length; i++) {
                    formData.append('gallery[]', galleryInput.files[i]);
                }
            }

            // Profile image: new file
            const profileImageInput = document.getElementById('sd-edit-profile-image');
            if (profileImageInput && profileImageInput.files.length > 0) {
                formData.append('profile_image', profileImageInput.files[0]);
            }

            // Video: removal flag
            if (self.removeVideo) {
                formData.append('remove_video', '1');
            }

            // Video: new file
            const videoInput = document.getElementById('sd-edit-video');
            if (videoInput && videoInput.files.length > 0) {
                formData.append('video', videoInput.files[0]);
            }

            // Show loading state
            $submitBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();

            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showNotice(response.data.message, 'success');
                        self.closeModal();

                        // Update the listing title in the table if visible
                        const $row = $('tr[data-post-id="' + formData.post_id + '"]');
                        if ($row.length) {
                            $row.find('.sd-listing-title-text strong').text(formData.title);
                        }
                    } else {
                        alert(response.data.message || 'Fehler beim Speichern.');
                    }
                },
                error: function() {
                    alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoading.hide();
                }
            });
        }
    };

    // Initialize Edit Modal on document ready
    $(document).ready(function() {
        SDEditModal.init();
    });

    /**
     * SDLocationPicker - Draggable Map Pin for Precise Location Setting
     * Used in submission form and dashboard edit modal
     */
    const SDLocationPicker = {
        map: null,
        marker: null,
        defaultCenter: [51.1657, 10.4515], // Germany center
        defaultZoom: 6,
        maxZoom: 18,
        context: null, // 'submission' or 'edit'
        debounceTimer: null,

        init: function(context) {
            if (typeof L === 'undefined') {
                return; // Leaflet not loaded
            }

            this.context = context;
            const self = this;

            if (context === 'submission') {
                this.initSubmissionForm();
            } else if (context === 'edit') {
                this.initEditForm();
            }
        },

        initSubmissionForm: function() {
            const self = this;
            const $container = $('#sd-location-picker-container');
            const $mapDiv = $('#sd-submission-map');
            const $latInput = $('#sd-latitude');
            const $lngInput = $('#sd-longitude');
            const $addressInput = $('#sd_address');
            const $zipInput = $('#sd_zip');
            const $cityInput = $('#sd_city');

            if (!$container.length || !$mapDiv.length) return;

            // Listen for address field changes (with debounce)
            $addressInput.add($zipInput).add($cityInput).on('blur', function() {
                clearTimeout(self.debounceTimer);
                self.debounceTimer = setTimeout(function() {
                    self.tryGeocodeAndShowMap('submission');
                }, 500);
            });

            // Listen for manual coordinate input changes
            $latInput.add($lngInput).on('change', function() {
                self.updateMarkerFromInputs('submission');
            });
        },

        initEditForm: function() {
            const self = this;

            // Watch for modal opening - the form gets populated
            $(document).on('sd-edit-form-populated', function(e, data) {
                self.initEditMapWithData(data);
            });

            // Listen for manual coordinate input changes in edit form
            $(document).on('change', '#sd-edit-latitude, #sd-edit-longitude', function() {
                self.updateMarkerFromInputs('edit');
            });

            // Listen for address field changes in edit form
            $(document).on('blur', '#sd-edit-address, #sd-edit-zip, #sd-edit-city', function() {
                clearTimeout(self.debounceTimer);
                self.debounceTimer = setTimeout(function() {
                    self.tryGeocodeAndShowMap('edit');
                }, 500);
            });
        },

        initEditMapWithData: function(data) {
            const self = this;
            const $container = $('#sd-edit-location-picker-container');
            const $mapDiv = $('#sd-edit-map');
            const $latInput = $('#sd-edit-latitude');
            const $lngInput = $('#sd-edit-longitude');

            if (!$container.length || !$mapDiv.length) return;

            // Show container
            $container.show();

            // Populate coordinate inputs from data
            if (data.latitude && data.longitude) {
                $latInput.val(data.latitude);
                $lngInput.val(data.longitude);
            }

            // Destroy previous map if exists
            if (this.map) {
                this.map.remove();
                this.map = null;
                this.marker = null;
            }

            // Delay to ensure modal is visible before creating map
            setTimeout(function() {
                if (data.latitude && data.longitude) {
                    // Use existing coordinates
                    self.createMap('edit', parseFloat(data.latitude), parseFloat(data.longitude));
                } else {
                    // Try geocoding from address fields
                    self.tryGeocodeAndShowMap('edit');
                }
            }, 300);
        },

        tryGeocodeAndShowMap: function(context) {
            const self = this;
            let address, zip, city, $container, $mapDiv;

            if (context === 'submission') {
                address = $('#sd_address').val();
                zip = $('#sd_zip').val();
                city = $('#sd_city').val();
                $container = $('#sd-location-picker-container');
                $mapDiv = $('#sd-submission-map');
            } else {
                address = $('#sd-edit-address').val();
                zip = $('#sd-edit-zip').val();
                city = $('#sd-edit-city').val();
                $container = $('#sd-edit-location-picker-container');
                $mapDiv = $('#sd-edit-map');
            }

            // Need at least city or zip to geocode
            if (!city && !zip) {
                return;
            }

            // Build full address
            const fullAddress = [address, zip, city, 'Deutschland'].filter(Boolean).join(', ');

            // Show container
            $container.show();

            // Geocode via Nominatim
            this.geocodeAddress(fullAddress, function(result) {
                if (result) {
                    self.createMap(context, result.lat, result.lng);
                    self.updateInputs(context, result.lat, result.lng);
                } else {
                    // Geocoding failed - show map at Germany center
                    self.createMap(context, self.defaultCenter[0], self.defaultCenter[1], true);
                }
            });
        },

        geocodeAddress: function(address, callback) {
            const url = 'https://nominatim.openstreetmap.org/search?' +
                'q=' + encodeURIComponent(address) +
                '&format=json&limit=1&addressdetails=1';

            fetch(url, {
                headers: {
                    'User-Agent': 'Hofladen-Scout.de/1.0'
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data && data.length > 0) {
                    callback({
                        lat: parseFloat(data[0].lat),
                        lng: parseFloat(data[0].lon)
                    });
                } else {
                    callback(null);
                }
            })
            .catch(function() {
                callback(null);
            });
        },

        createMap: function(context, lat, lng, isFallback) {
            const self = this;
            let mapId = context === 'submission' ? 'sd-submission-map' : 'sd-edit-map';
            const $mapDiv = $('#' + mapId);

            if (!$mapDiv.length) return;

            // Destroy previous map if exists
            if (this.map) {
                this.map.remove();
            }

            // Create map
            this.map = L.map(mapId).setView([lat, lng], isFallback ? this.defaultZoom : this.maxZoom);

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19
            }).addTo(this.map);

            // Create custom draggable marker icon
            const markerHtml = '<div class="sd-location-picker-marker"><svg viewBox="0 0 24 24" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="#f1c232"/><circle cx="12" cy="9" r="3" fill="#fff"/></svg></div>';

            const markerIcon = L.divIcon({
                html: markerHtml,
                className: 'sd-location-picker-marker-wrapper',
                iconSize: [40, 40],
                iconAnchor: [20, 40]
            });

            // Create draggable marker
            this.marker = L.marker([lat, lng], {
                icon: markerIcon,
                draggable: true
            }).addTo(this.map);

            // Update coordinates on drag end
            this.marker.on('dragend', function(e) {
                const position = e.target.getLatLng();
                self.updateInputs(context, position.lat, position.lng);
            });

            // Also allow clicking on map to move marker
            this.map.on('click', function(e) {
                self.marker.setLatLng(e.latlng);
                self.updateInputs(context, e.latlng.lat, e.latlng.lng);
            });

            // Invalidate size after a short delay (for modals)
            setTimeout(function() {
                self.map.invalidateSize();
            }, 100);
        },

        updateInputs: function(context, lat, lng) {
            let $latInput, $lngInput;

            if (context === 'submission') {
                $latInput = $('#sd-latitude');
                $lngInput = $('#sd-longitude');
            } else {
                $latInput = $('#sd-edit-latitude');
                $lngInput = $('#sd-edit-longitude');
            }

            $latInput.val(lat.toFixed(7));
            $lngInput.val(lng.toFixed(7));
        },

        updateMarkerFromInputs: function(context) {
            let $latInput, $lngInput;

            if (context === 'submission') {
                $latInput = $('#sd-latitude');
                $lngInput = $('#sd-longitude');
            } else {
                $latInput = $('#sd-edit-latitude');
                $lngInput = $('#sd-edit-longitude');
            }

            const lat = parseFloat($latInput.val());
            const lng = parseFloat($lngInput.val());

            if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                if (this.marker && this.map) {
                    this.marker.setLatLng([lat, lng]);
                    this.map.setView([lat, lng], this.maxZoom);
                } else {
                    // Create map if not exists
                    this.tryGeocodeAndShowMap(context);
                }
            }
        },

        destroy: function() {
            if (this.map) {
                this.map.remove();
                this.map = null;
                this.marker = null;
            }
        }
    };

    // Initialize SDLocationPicker on document ready
    $(document).ready(function() {
        // For submission form
        if ($('#sd-location-picker-container').length) {
            SDLocationPicker.init('submission');
        }

        // For edit form - initialized via custom event when form is populated
        if ($('#sd-edit-location-picker-container').length) {
            SDLocationPicker.init('edit');
        }
    });

    /**
     * Profile Edit Modal
     * Handles user profile editing from the dashboard
     */
    const SDProfileModal = {
        $modal: null,
        $form: null,

        init: function() {
            this.$modal = $('#sd-profile-modal');
            this.$form = $('#sd-profile-form');

            if (!this.$modal.length) return;

            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Open modal on edit profile button click
            $(document).on('click', '.sd-edit-profile', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const firstName = $btn.data('first-name') || '';
                const lastName = $btn.data('last-name') || '';
                const email = $btn.data('email') || '';
                self.openModal(firstName, lastName, email);
            });

            // Form submission
            this.$form.on('submit', function(e) {
                e.preventDefault();
                self.submitForm();
            });
        },

        openModal: function(firstName, lastName, email) {
            // Populate form with current values
            $('#sd-profile-first-name').val(firstName);
            $('#sd-profile-last-name').val(lastName);
            $('#sd-profile-email-input').val(email);

            this.$modal.fadeIn(200);
            $('#sd-profile-first-name').focus();
        },

        closeModal: function() {
            this.$modal.fadeOut(200);
            this.$form[0].reset();
        },

        submitForm: function() {
            const self = this;
            const $submitBtn = $('#sd-profile-submit');
            const $btnText = $submitBtn.find('.sd-btn-text');
            const $btnLoading = $submitBtn.find('.sd-btn-loading');

            const firstName = $('#sd-profile-first-name').val().trim();
            const lastName = $('#sd-profile-last-name').val().trim();
            const email = $('#sd-profile-email-input').val().trim();

            // Basic validation
            if (!firstName) {
                alert('Bitte gib deinen Vornamen ein.');
                $('#sd-profile-first-name').focus();
                return;
            }

            if (!email) {
                alert('Bitte gib deine E-Mail-Adresse ein.');
                $('#sd-profile-email-input').focus();
                return;
            }

            // Show loading state
            $submitBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();

            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_update_user_profile',
                    nonce: sdAjax.profileNonce,
                    first_name: firstName,
                    last_name: lastName,
                    email: email
                },
                success: function(response) {
                    if (response.success) {
                        // Update displayed values in dashboard
                        $('#sd-profile-display-name').text(response.data.display_name);
                        $('#sd-profile-email').text(response.data.email);

                        // Update welcome message if exists
                        const $welcomeHeader = $('.sd-dashboard-header h2');
                        if ($welcomeHeader.length) {
                            $welcomeHeader.text('Willkommen, ' + response.data.display_name + '!');
                        }

                        // Update the button data attributes for next edit
                        $('.sd-edit-profile')
                            .data('first-name', firstName)
                            .data('last-name', lastName)
                            .data('email', email);

                        showNotice(response.data.message, 'success');
                        self.closeModal();
                    } else {
                        alert(response.data.message || 'Fehler beim Speichern.');
                    }
                },
                error: function() {
                    alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoading.hide();
                }
            });
        }
    };

    // Initialize Profile Modal on document ready
    $(document).ready(function() {
        SDProfileModal.init();
    });

    /**
     * Premium Subscription Management (SDPremium)
     * Handles dashboard tabs, Stripe checkout, subscription management
     *
     * New UX Flow (2025):
     * 1. User sees all their listings first
     * 2. User clicks a listing to select it
     * 3. Pricing table shows with correct "Aktueller Plan" based on selected listing
     * 4. Only upgrades are shown (no downgrade allowed)
     */
    const SDPremium = {
        selectedPlan: null,
        selectedListingId: null,
        selectedListingCurrentPlan: null,

        // Plan hierarchy: higher index = better plan
        planHierarchy: ['free', 'monthly', 'yearly'],

        init: function() {
            if (!$('.sd-dashboard-tabs').length) return;

            this.bindEvents();
            this.checkURLParameters();
        },

        bindEvents: function() {
            const self = this;

            // Tab switching
            $(document).on('click', '.sd-tab-btn', function(e) {
                e.preventDefault();
                const tabId = $(this).data('tab');
                self.switchTab(tabId);
            });

            // NEW: Listing selection from listing selector grid (Step 1)
            $(document).on('click', '.sd-listing-select-item', function(e) {
                const $item = $(this);

                // Ignore clicks on disabled (already premium) items
                if ($item.hasClass('sd-listing-disabled')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }

                const postId = $item.data('post-id');
                const currentPlan = $item.data('current-plan') || 'free';
                const title = $item.data('listing-title');

                // Store selection
                self.selectedListingId = postId;
                self.selectedListingCurrentPlan = currentPlan;

                // Update pricing table dynamically
                self.updatePricingTable(currentPlan);

                // Update selected listing info bar
                $('#sd-selected-listing-title').text(title);

                // Show pricing table, hide listing selector
                $('#sd-listing-selector').slideUp(200, function() {
                    $('#sd-pricing-table-container').slideDown(200);
                });

                // Scroll to pricing table
                setTimeout(function() {
                    $('html, body').animate({
                        scrollTop: $('#sd-pricing-table-container').offset().top - 100
                    }, 300);
                }, 250);
            });

            // NEW: "Anderes Listing wählen" button - go back to listing selector
            $(document).on('click', '.sd-change-listing', function(e) {
                e.preventDefault();
                self.resetToListingSelector();
            });

            // Plan selection (upgrade button in pricing card)
            $(document).on('click', '.sd-upgrade-plan-btn', function(e) {
                e.preventDefault();
                const plan = $(this).data('plan');

                if (!plan || plan === 'free') return;

                // Ensure a listing is selected
                if (!self.selectedListingId) {
                    alert('Bitte wähle zuerst einen Eintrag aus.');
                    self.resetToListingSelector();
                    return;
                }

                self.selectedPlan = plan;
                self.createCheckoutSession();
            });

            // Listing selection from grid (OLD: for existing upgrade flow, keeping for compatibility)
            $(document).on('click', '.sd-upgrade-listing-item', function() {
                const $item = $(this);
                const postId = $item.data('post-id');

                // Toggle selection
                $('.sd-upgrade-listing-item').removeClass('selected');
                $item.addClass('selected');
                self.selectedListingId = postId;

                // Enable confirm button
                $('.sd-confirm-upgrade').prop('disabled', false);
            });

            // Confirm checkout (continue to payment) - OLD flow
            $(document).on('click', '.sd-confirm-upgrade', function(e) {
                e.preventDefault();
                self.createCheckoutSession();
            });

            // Cancel listing selector - OLD flow
            $(document).on('click', '.sd-cancel-upgrade', function(e) {
                e.preventDefault();
                self.hideListingSelector();
            });

            // Open billing portal
            $(document).on('click', '.sd-manage-billing', function(e) {
                e.preventDefault();
                self.openBillingPortal();
            });

            // Cancel subscription
            $(document).on('click', '.sd-cancel-sub', function(e) {
                e.preventDefault();
                const postId = $(this).data('post-id');
                self.cancelSubscription(postId);
            });

            // Upgrade from listings table (existing button)
            $(document).on('click', '.sd-upgrade-listing', function(e) {
                e.preventDefault();
                const postId = $(this).data('post-id');

                // Switch to premium tab
                self.switchTab('premium');

                // Small delay, then click on the listing in the selector
                setTimeout(function() {
                    const $selectorItem = $('.sd-listing-select-item[data-post-id="' + postId + '"]');
                    if ($selectorItem.length) {
                        $selectorItem.click(); // Trigger selection -> shows pricing table
                    }
                }, 150);
            });
        },

        /**
         * Check if target plan is an upgrade from current plan
         */
        isPlanUpgrade: function(currentPlan, targetPlan) {
            const currentIndex = this.planHierarchy.indexOf(currentPlan);
            const targetIndex = this.planHierarchy.indexOf(targetPlan);
            return targetIndex > currentIndex;
        },

        /**
         * Update pricing table based on selected listing's current plan
         * Shows "Aktueller Plan" for current, "Upgrade wählen" for upgrades, "—" for downgrades
         */
        updatePricingTable: function(currentPlan) {
            const self = this;

            $('.sd-pricing-card').each(function() {
                const $card = $(this);
                const planId = $card.data('plan');
                const $currentBadge = $card.find('.sd-pricing-current');
                const $upgradeBtn = $card.find('.sd-upgrade-plan-btn');
                const $unavailable = $card.find('.sd-pricing-unavailable');

                // Hide all first
                $currentBadge.hide();
                $upgradeBtn.hide();
                $unavailable.hide();

                if (planId === currentPlan) {
                    // This is the current plan
                    $currentBadge.show();
                } else if (self.isPlanUpgrade(currentPlan, planId)) {
                    // This is an upgrade option
                    $upgradeBtn.show();
                } else {
                    // This is a downgrade (not allowed)
                    $unavailable.show();
                }
            });
        },

        /**
         * Reset to listing selector (Step 1)
         */
        resetToListingSelector: function() {
            this.selectedListingId = null;
            this.selectedListingCurrentPlan = null;
            this.selectedPlan = null;

            $('#sd-pricing-table-container').slideUp(200, function() {
                $('#sd-listing-selector').slideDown(200);
            });
        },

        switchTab: function(tabId) {
            // Update tab buttons
            $('.sd-tab-btn').removeClass('active').attr('aria-selected', 'false');
            $('.sd-tab-btn[data-tab="' + tabId + '"]').addClass('active').attr('aria-selected', 'true');

            // Update tab panels (using data-tab-content attribute from template)
            $('.sd-tab-content').removeClass('active').attr('hidden', 'true');
            $('[data-tab-content="' + tabId + '"]').addClass('active').removeAttr('hidden');

            // Update URL with tab parameter (no hash)
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            url.hash = ''; // Remove any existing hash
            window.history.replaceState({}, '', url.toString().replace(/#$/, ''));
        },

        showListingSelector: function() {
            const $selector = $('#sd-listing-selector');
            const planLabel = this.selectedPlan === 'monthly' ? 'Premium Monatlich (9€/Monat)' : 'Premium Jährlich (80€/Jahr)';

            // Update plan label
            $('#sd-selected-plan-label').text(planLabel);

            // Reset selection state
            $('.sd-upgrade-listing-item').removeClass('selected');
            $('.sd-confirm-upgrade').prop('disabled', true);
            this.selectedListingId = null;

            // Show selector with animation
            $selector.slideDown(200);

            // Scroll to selector
            $('html, body').animate({
                scrollTop: $selector.offset().top - 100
            }, 300);
        },

        hideListingSelector: function() {
            $('#sd-listing-selector').slideUp(200);
            this.selectedPlan = null;
            this.selectedListingId = null;
            $('.sd-upgrade-listing-item').removeClass('selected');
        },

        createCheckoutSession: function() {
            const self = this;
            const $btn = $('.sd-confirm-upgrade');
            const originalText = $btn.text();

            if (!this.selectedListingId) {
                alert('Bitte wähle einen Eintrag aus.');
                return;
            }

            if (!this.selectedPlan) {
                alert('Bitte wähle einen Plan aus.');
                return;
            }

            // Show loading
            $btn.prop('disabled', true).text('Wird geladen...');

            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_create_checkout_session',
                    post_id: self.selectedListingId,
                    plan: self.selectedPlan,
                    nonce: sdAjax.checkoutNonce
                },
                success: function(response) {
                    if (response.success && response.data.checkout_url) {
                        // Redirect to Stripe Checkout
                        window.location.href = response.data.checkout_url;
                    } else {
                        alert(response.data.message || 'Ein Fehler ist aufgetreten.');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Create checkout session for new submission with premium fields
         * Called automatically when form includes premium data
         */
        createSubmissionCheckout: function(postId, plan) {
            const self = this;

            // Store for checkout
            this.selectedListingId = postId;
            this.selectedPlan = plan;

            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_create_checkout_session',
                    post_id: postId,
                    plan: plan,
                    nonce: sdAjax.checkoutNonce,
                    is_new_submission: '1'
                },
                success: function(response) {
                    if (response.success && response.data.checkout_url) {
                        // Redirect to Stripe Checkout
                        window.location.href = response.data.checkout_url;
                    } else {
                        alert(response.data.message || 'Ein Fehler ist aufgetreten beim Starten des Checkouts.');
                        // Fallback: redirect to dashboard
                        if (sdAjax.dashboardUrl) {
                            window.location.href = sdAjax.dashboardUrl + '?submission=success&post_id=' + postId;
                        }
                    }
                },
                error: function() {
                    alert('Ein Fehler ist aufgetreten. Du wirst zum Dashboard weitergeleitet.');
                    if (sdAjax.dashboardUrl) {
                        window.location.href = sdAjax.dashboardUrl + '?submission=success&post_id=' + postId;
                    }
                }
            });
        },

        openBillingPortal: function() {
            const $btn = $('.sd-manage-billing');
            const originalText = $btn.text();

            $btn.prop('disabled', true).text('Wird geladen...');

            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_billing_portal',
                    nonce: sdAjax.billingNonce
                },
                success: function(response) {
                    if (response.success && response.data.portal_url) {
                        window.location.href = response.data.portal_url;
                    } else {
                        alert(response.data.message || 'Ein Fehler ist aufgetreten.');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        cancelSubscription: function(postId) {
            if (!confirm('Möchtest du dieses Abo wirklich kündigen?\n\nDas Abo läuft bis zum Ende der aktuellen Abrechnungsperiode weiter.')) {
                return;
            }

            const $btn = $('.sd-cancel-sub[data-post-id="' + postId + '"]');
            const originalText = $btn.text();

            $btn.prop('disabled', true).text('Wird gekündigt...');

            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_cancel_subscription',
                    post_id: postId,
                    nonce: sdAjax.cancelSubNonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data.message, 'success');

                        // Update the subscription row to show cancelled state
                        const $row = $btn.closest('.sd-subscription-item');
                        $row.find('.sd-subscription-status')
                            .removeClass('sd-status-active')
                            .addClass('sd-status-cancelling')
                            .text('Läuft aus am ' + response.data.cancel_date);

                        // Remove cancel button, keep billing portal
                        $btn.remove();
                    } else {
                        alert(response.data.message || 'Ein Fehler ist aufgetreten.');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        checkURLParameters: function() {
            const urlParams = new URLSearchParams(window.location.search);

            // Handle upgrade success with optimistic UI + polling (Anforderung 3)
            if (urlParams.get('upgrade') === 'success') {
                const postId = urlParams.get('post_id');

                if (postId) {
                    // Optimistic UI update
                    this.optimisticPremiumUpdate(postId);

                    // Start polling
                    this.startPremiumPolling(postId);

                    // Show info notice
                    this.showToast('Ihr Premium-Abo wird aktiviert...', 'info');
                } else {
                    // Fallback: no post_id, just show success
                    showNotice('Vielen Dank! Ihr Premium-Abo wurde erfolgreich aktiviert.', 'success');
                }

                // Switch to listings tab (not premium tab)
                this.switchTab('listings');
                return;
            }

            // Handle new submission with premium success (Anforderung 1)
            if (urlParams.get('submission') === 'premium_success') {
                const postId = urlParams.get('post_id');

                if (postId) {
                    this.optimisticPremiumUpdate(postId);
                    this.startPremiumPolling(postId);
                    this.showToast('Ihr Eintrag wurde erstellt und Premium wird aktiviert...', 'info');
                }

                this.switchTab('listings');
                return;
            }

            // Handle standard submission success
            if (urlParams.get('submission') === 'success') {
                showNotice('Dein Eintrag wurde erfolgreich eingereicht und wartet auf Freigabe.', 'success');
                this.cleanURL(['submission', 'post_id']);
                this.switchTab('listings');
                return;
            }

            // Handle cancellation message
            if (urlParams.get('upgrade') === 'cancelled') {
                showNotice('Der Checkout wurde abgebrochen. Du kannst jederzeit erneut upgraden.', 'info');
                this.cleanURL(['upgrade']);
            }

            // Check tab parameter for tab switching (primary method)
            const tabParam = urlParams.get('tab');
            if (tabParam && ['listings', 'leads', 'reviews', 'premium', 'settings'].includes(tabParam)) {
                this.switchTab(tabParam);
                return;
            }

            // Fallback: Check hash for backwards compatibility
            const hash = window.location.hash.replace('#', '');
            if (hash && ['listings', 'premium', 'settings'].includes(hash)) {
                // Migrate from hash to tab parameter
                this.switchTab(hash);
            }
        },

        cleanURL: function(params) {
            const url = new URL(window.location.href);
            params.forEach(function(param) {
                url.searchParams.delete(param);
            });
            window.history.replaceState({}, '', url.toString());
        },

        /**
         * Optimistic UI update - immediately show as premium in listings
         */
        optimisticPremiumUpdate: function(postId) {
            const $card = $('.sd-listing-card-item[data-post-id="' + postId + '"]');
            if (!$card.length) return;

            // Find the premium badge area and update it
            const $premiumDiv = $card.find('.sd-listing-premium');
            if ($premiumDiv.length) {
                $premiumDiv.html(
                    '<span class="sd-premium-badge-small sd-premium-optimistic">' +
                    '<svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M10 15L3.5 18L5.5 11L0 6.5L7.5 6L10 0L12.5 6L20 6.5L14.5 11L16.5 18L10 15Z" fill="currentColor"/></svg> ' +
                    'Premium' +
                    '<span class="sd-activating-spinner"></span>' +
                    '</span>'
                );
            }

            // Add visual highlight to card
            $card.addClass('sd-premium-activating');

            // Update status badge if it shows "Standard"
            const $standardBadge = $card.find('.sd-status-standard');
            if ($standardBadge.length) {
                $standardBadge.remove();
            }
        },

        /**
         * Start polling to confirm premium status from webhook
         */
        startPremiumPolling: function(postId) {
            const self = this;
            let attempts = 0;
            const maxAttempts = 15; // 15 * 2s = 30s max
            const pollInterval = 2000; // 2 seconds
            const stripeVerifyAfter = 3; // After 3 attempts, verify directly with Stripe API

            const pollTimer = setInterval(function() {
                attempts++;

                // After X attempts, enable direct Stripe API verification as fallback
                const verifyStripe = attempts > stripeVerifyAfter ? '1' : '0';

                $.ajax({
                    url: sdAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sd_check_premium_status',
                        post_id: postId,
                        nonce: sdAjax.checkPremiumNonce,
                        verify_stripe: verifyStripe
                    },
                    success: function(response) {
                        if (response.success && response.data.is_premium) {
                            // Premium confirmed!
                            clearInterval(pollTimer);
                            self.confirmPremiumActivation(postId);
                        } else if (attempts >= maxAttempts) {
                            // Timeout - reload page as fallback
                            clearInterval(pollTimer);
                            self.premiumPollingTimeout();
                        }
                    },
                    error: function() {
                        if (attempts >= maxAttempts) {
                            clearInterval(pollTimer);
                            self.premiumPollingTimeout();
                        }
                    }
                });
            }, pollInterval);
        },

        /**
         * Premium activation confirmed by webhook
         */
        confirmPremiumActivation: function(postId) {
            const $card = $('.sd-listing-card-item[data-post-id="' + postId + '"]');

            // Remove optimistic styling
            $card.removeClass('sd-premium-activating');

            // Update badge to final state
            const $premiumDiv = $card.find('.sd-listing-premium');
            if ($premiumDiv.length) {
                $premiumDiv.html(
                    '<span class="sd-premium-badge-small">' +
                    '<svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M10 15L3.5 18L5.5 11L0 6.5L7.5 6L10 0L12.5 6L20 6.5L14.5 11L16.5 18L10 15Z" fill="currentColor"/></svg> ' +
                    'Premium' +
                    '</span>'
                );
            }

            // Remove pending premium badge if exists (for new submissions)
            $card.find('.sd-status-pending-premium-type').remove();

            // Update premium stats counter in dashboard header
            var $premiumStatBox = $('#sd-stat-premium .sd-stat-number');
            if ($premiumStatBox.length) {
                var currentCount = parseInt($premiumStatBox.text(), 10) || 0;
                $premiumStatBox.text(currentCount + 1);
            }

            // Show success toast
            this.showToast('Premium wurde erfolgreich aktiviert!', 'success');

            // Clean URL
            this.cleanURL(['upgrade', 'session_id', 'submission', 'post_id']);
        },

        /**
         * Polling timeout - reload as fallback
         */
        premiumPollingTimeout: function() {
            this.showToast('Premium-Aktivierung dauert länger als erwartet. Seite wird neu geladen...', 'info');

            setTimeout(function() {
                window.location.reload();
            }, 2000);
        },

        /**
         * Show toast notification
         */
        showToast: function(message, type) {
            // Remove existing toasts
            $('.sd-toast').remove();

            const $toast = $('<div class="sd-toast sd-toast-' + type + '">' + message + '</div>');
            $('body').append($toast);

            // Animate in
            setTimeout(function() {
                $toast.addClass('sd-toast-visible');
            }, 10);

            // Auto-remove after 5s
            setTimeout(function() {
                $toast.removeClass('sd-toast-visible');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 5000);
        }
    };

    // Initialize Premium module on document ready
    $(document).ready(function() {
        SDPremium.init();
    });

    /**
     * Modal Close
     */
    $(document).on('click', '.sd-modal-close, .sd-modal-cancel, .sd-modal-backdrop', function() {
        $('.sd-modal').fadeOut();
    });

    /**
     * Image Preview for File Upload
     */
    $('#sd_image').on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Create or update preview
                let $preview = $('#sd-image-preview');
                if (!$preview.length) {
                    $preview = $('<div id="sd-image-preview" style="margin-top: 10px;"></div>');
                    $('#sd_image').after($preview);
                }
                $preview.html('<img src="' + e.target.result + '" style="max-width: 200px; border-radius: 8px;">');
            };
            reader.readAsDataURL(file);
        }
    });

    /**
     * Auto-dismiss success notices
     */
    setTimeout(function() {
        $('.sd-notice-success').fadeOut();
    }, 5000);

    /**
     * Auto-submit form when filter or sorting options change
     */
    $(document).on('change', '.sd-auto-submit', function() {
        const $form = $(this).closest('form');
        if ($form.length) {
            $form.submit();
        }
    });

    /**
     * View Toggle (Grid/List/Map)
     * Stores preference in localStorage for persistence
     */
    const VIEW_STORAGE_KEY = 'sd_listings_view';

    function initViewToggle() {
        const $grid = $('#sd-listings-grid');
        const $toggleBtns = $('.sd-view-btn');
        const $layout = $('#sd-listings-layout');

        if (!$grid.length || !$toggleBtns.length) return;

        // Restore saved preference
        const savedView = localStorage.getItem(VIEW_STORAGE_KEY);
        const isMobile = window.innerWidth <= 768;
        const isHomePage = window.location.pathname === '/' || window.location.pathname === '';

        if (savedView === 'list') {
            setView('list');
        } else if (savedView === 'map') {
            setView('map');
        } else if (isMobile && isHomePage) {
            // Default to list view on mobile for homepage (no localStorage save)
            setView('list');
        }

        // Handle toggle click
        $toggleBtns.on('click', function() {
            const view = $(this).data('view');
            setView(view);
            localStorage.setItem(VIEW_STORAGE_KEY, view);
        });

        function setView(view) {
            const $grid = $('#sd-listings-grid');
            const $layout = $('#sd-listings-layout');
            const $gridBtn = $('.sd-view-btn-grid');
            const $listBtn = $('.sd-view-btn-list');
            const $mapBtn = $('.sd-view-btn-map');

            // Reset all states
            $grid.removeClass('sd-view-list');
            $layout.removeClass('sd-map-view');
            $toggleBtns.removeClass('active').attr('aria-pressed', 'false');

            if (view === 'list') {
                $grid.addClass('sd-view-list');
                $listBtn.addClass('active').attr('aria-pressed', 'true');
            } else if (view === 'map') {
                $layout.addClass('sd-map-view');
                $mapBtn.addClass('active').attr('aria-pressed', 'true');
                // Initialize map when view is activated
                SDMap.init();
            } else {
                // Grid view (default)
                $gridBtn.addClass('active').attr('aria-pressed', 'true');
            }
        }
    }

    /**
     * Sidebar Map Integration with Leaflet
     */
    const SDMap = {
        map: null,
        markers: [],
        markerLayer: null,
        isInitialized: false,
        defaultCenter: [51.1657, 10.4515], // Germany center
        defaultZoom: 6,

        init: function() {
            // Only initialize if Leaflet is loaded and container exists
            if (typeof L === 'undefined') {
                console.warn('Leaflet not loaded');
                return;
            }

            const $container = $('#sd-map');
            if (!$container.length) return;

            // Don't reinitialize
            if (this.isInitialized && this.map) {
                this.map.invalidateSize();
                return;
            }

            this.createMap();
            this.addMarkers();
            this.bindEvents();
            this.isInitialized = true;
        },

        createMap: function() {
            const self = this;

            // Create map
            this.map = L.map('sd-map', {
                center: this.defaultCenter,
                zoom: this.defaultZoom,
                zoomControl: false, // We use custom controls
                attributionControl: true
            });

            // Add tile layer (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19
            }).addTo(this.map);

            // Create marker layer group
            this.markerLayer = L.layerGroup().addTo(this.map);

            // Hide loading overlay
            $('#sd-map-loading').removeClass('active');
        },

        addMarkers: function() {
            const self = this;
            this.clearMarkers();

            // Collect listings with coordinates
            const bounds = [];
            $('#sd-listings-grid .sd-listing-card').each(function() {
                const $card = $(this);
                const lat = parseFloat($card.data('lat'));
                const lng = parseFloat($card.data('lng'));
                const postId = $card.data('post-id');

                if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                    const isPremium = $card.hasClass('sd-listing-premium');
                    const title = $card.find('.sd-listing-title a').text();
                    const category = $card.find('.sd-listing-meta-category').text();
                    const city = $card.find('.sd-listing-meta-location').text().trim();
                    const permalink = $card.find('.sd-listing-title a').attr('href');
                    const image = $card.find('.sd-listing-thumbnail').attr('src');

                    // Create custom icon
                    const icon = self.createMarkerIcon(isPremium);

                    // Create marker
                    const marker = L.marker([lat, lng], { icon: icon })
                        .bindPopup(self.createPopupContent(title, category, city, permalink, image, isPremium))
                        .on('mouseover', function() {
                            self.highlightCard(postId);
                            this.openPopup();
                        })
                        .on('mouseout', function() {
                            self.unhighlightCard(postId);
                        })
                        .on('click', function() {
                            // Keep popup open on click
                        });

                    marker.postId = postId;
                    self.markers.push(marker);
                    self.markerLayer.addLayer(marker);
                    bounds.push([lat, lng]);
                }
            });

            // Fit map to markers
            if (bounds.length > 0) {
                if (bounds.length === 1) {
                    this.map.setView(bounds[0], 14);
                } else {
                    this.map.fitBounds(bounds, { padding: [30, 30] });
                }
            }
        },

        createMarkerIcon: function(isPremium) {
            const markerHtml = `
                <div class="sd-map-marker ${isPremium ? 'premium' : ''}">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="currentColor"/>
                    </svg>
                </div>
            `;

            return L.divIcon({
                html: markerHtml,
                className: 'sd-marker-wrapper',
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -32]
            });
        },

        createPopupContent: function(title, category, city, permalink, image, isPremium) {
            let html = '<div class="sd-map-popup">';

            if (image) {
                html += `<img src="${image}" alt="${title}" class="sd-map-popup-image" />`;
            }

            html += `
                <h4 class="sd-map-popup-title">
                    <a href="${permalink}">${title}</a>
                </h4>
            `;

            if (category || city) {
                html += '<div class="sd-map-popup-meta">';
                if (category) {
                    html += `<span>${category}</span>`;
                }
                if (category && city) {
                    html += '<span>·</span>';
                }
                if (city) {
                    html += `
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="currentColor"/>
                        </svg>
                        <span>${city}</span>
                    `;
                }
                html += '</div>';
            }

            html += `
                <a href="${permalink}" class="sd-map-popup-link">
                    Details ansehen
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" fill="currentColor"/>
                    </svg>
                </a>
            `;

            html += '</div>';
            return html;
        },

        clearMarkers: function() {
            if (this.markerLayer) {
                this.markerLayer.clearLayers();
            }
            this.markers = [];
        },

        highlightCard: function(postId) {
            $('[data-post-id="' + postId + '"]').addClass('sd-map-highlight');
        },

        unhighlightCard: function(postId) {
            $('[data-post-id="' + postId + '"]').removeClass('sd-map-highlight');
        },

        highlightMarker: function(postId) {
            this.markers.forEach(function(marker) {
                if (marker.postId === postId) {
                    $(marker._icon).find('.sd-map-marker').addClass('active');
                    marker.openPopup();
                }
            });
        },

        unhighlightMarker: function(postId) {
            this.markers.forEach(function(marker) {
                if (marker.postId === postId) {
                    $(marker._icon).find('.sd-map-marker').removeClass('active');
                    // Don't close popup on card mouseleave
                }
            });
        },

        fitAllMarkers: function() {
            if (this.markers.length === 0) return;

            const bounds = [];
            this.markers.forEach(function(marker) {
                bounds.push(marker.getLatLng());
            });

            if (bounds.length === 1) {
                this.map.setView(bounds[0], 14);
            } else {
                this.map.fitBounds(bounds, { padding: [30, 30] });
            }
        },

        bindEvents: function() {
            const self = this;

            // Custom zoom controls
            $('.sd-map-zoom-in').on('click', function() {
                self.map.zoomIn();
            });

            $('.sd-map-zoom-out').on('click', function() {
                self.map.zoomOut();
            });

            $('.sd-map-fit').on('click', function() {
                self.fitAllMarkers();
            });

            // Card hover -> highlight marker
            $(document).on('mouseenter', '.sd-listing-card[data-lat][data-lng]', function() {
                const postId = $(this).data('post-id');
                self.highlightMarker(postId);
            });

            $(document).on('mouseleave', '.sd-listing-card[data-lat][data-lng]', function() {
                const postId = $(this).data('post-id');
                self.unhighlightMarker(postId);
            });

            // Update markers when listings change (AJAX filter)
            $(document).on('sd:listingsUpdated', function() {
                if (self.isInitialized) {
                    // Small delay to let DOM update
                    setTimeout(function() {
                        self.addMarkers();
                    }, 100);
                }
            });
        }
    };

    /**
     * AJAX Filtering System
     * Provides real-time filtering without page reload
     */
    const SDFilter = {
        isLoading: false,
        currentPage: 1,
        currentTag: '',
        debounceTimer: null,
        isInitialized: false, // Flag to prevent auto-fetch during page load

        init: function() {
            const self = this;
            const $grid = $('#sd-listings-grid');
            const $form = $('#sd-filter-form');

            if (!$grid.length) return;

            // Prevent form submission (use AJAX instead)
            $form.on('submit', function(e) {
                e.preventDefault();
                self.currentPage = 1;
                self.fetchListings();
            });

            // Search input with debounce
            $('#sd_search').on('input', function() {
                clearTimeout(self.debounceTimer);
                self.debounceTimer = setTimeout(function() {
                    // Skip filter if autocomplete dropdown is visible (check DOM directly)
                    var $autocomplete = $('#sd-autocomplete');
                    if ($autocomplete.length && $autocomplete.is(':visible')) {
                        return;
                    }
                    self.currentPage = 1;
                    self.fetchListings();
                }, 400);
            });

            // Bundesland/Category dropdown
            // Trigger AJAX on change and show/hide category tag
            $('#sd_category_dropdown').on('change', function(e) {
                e.preventDefault();
                self.currentPage = 1;

                // Show/hide category tag based on selection
                var selectedVal = $(this).val();
                var selectedText = $(this).find('option:selected').text().trim();

                if (selectedVal) {
                    // Use SDAutocomplete's showCategoryTag method
                    if (typeof SDAutocomplete !== 'undefined' && SDAutocomplete.showCategoryTag) {
                        SDAutocomplete.showCategoryTag(selectedVal, selectedText);
                    }
                } else {
                    $('#sd-category-tag').remove();
                    $('#sd_search').removeClass('has-category-tag').attr('placeholder', 'Hofladen, Produkt...');
                }

                self.fetchListings();
            });

            // Sort dropdown
            $('#sd_orderby').on('change', function() {
                self.currentPage = 1;
                self.fetchListings();
            });

            // Per page dropdown
            $('#sd_per_page').on('change', function() {
                self.currentPage = 1;
                self.fetchListings();
            });

            // Premium toggle
            $('.sd-toggle-input[name="sd_premium"]').on('change', function() {
                self.currentPage = 1;
                self.fetchListings();
            });

            // Tag chips (quick filter chips on frontpage)
            $(document).on('click', '.sd-chip', function(e) {
                e.preventDefault();
                const $chip = $(this);
                const href = $chip.attr('href');
                const urlParams = new URLSearchParams(href.split('?')[1] || '');
                const tag = urlParams.get('sd_tag') || '';

                // Toggle: If chip is already active, clear tag filter
                if ($chip.hasClass('active')) {
                    // Clear tag and reload
                    self.currentTag = '';
                    $('.sd-chip').removeClass('active');
                    self.currentPage = 1;
                    self.fetchListings();
                } else {
                    // Set tag filter
                    self.currentTag = tag;
                    $('.sd-chip').removeClass('active');
                    $chip.addClass('active');
                    self.currentPage = 1;
                    self.fetchListings();
                }
            });

            // Pagination clicks
            $(document).on('click', '.sd-pagination a', function(e) {
                e.preventDefault();
                let page = $(this).data('page');

                // If no data-page attribute, extract from href
                if (!page) {
                    const href = $(this).attr('href');
                    if (href) {
                        const match = href.match(/paged=(\d+)/);
                        if (match) {
                            page = match[1];
                        }
                    }
                }

                self.currentPage = parseInt(page) || 1;
                self.fetchListings(true); // true = scroll after load
            });

            // Clear filters
            $(document).on('click', '.sd-clear-filters', function(e) {
                e.preventDefault();
                self.clearFilters();
            });

            // Remove individual filter chip
            $(document).on('click', '.sd-chip-remove', function(e) {
                const filterType = $(this).data('filter');

                // Only handle AJAX chips (those with data-filter attribute)
                // Server-side rendered chips use href for navigation - let them work normally
                if (!filterType) {
                    // No data-filter attribute = server-side chip, allow normal navigation
                    return;
                }

                e.preventDefault();

                // Determine which filter to clear based on data-filter attribute
                if (filterType === 'search') {
                    $('#sd_search').val('');
                } else if (filterType === 'category') {
                    $('#sd_category_dropdown').prop('selectedIndex', 0);
                    // Remove category tag from input field (sync with sd-active-chip)
                    $('#sd-category-tag').remove();
                    $('#sd_search').removeClass('has-category-tag');
                    $('#sd_search').attr('placeholder', 'Hofladen, Produkt...');
                } else if (filterType === 'tag') {
                    self.currentTag = '';
                    $('.sd-chip').removeClass('active');
                    // Remove tag from input field if shown there
                    $('#sd-tag-tag').remove();
                    $('#sd_search').removeClass('has-tag');
                }

                self.currentPage = 1;
                self.fetchListings();
            });

            // Initialize dropdowns from URL parameters
            this.initFromURL();

            // Mark as initialized after a delay to prevent browser autofill from triggering AJAX
            // This ensures server-rendered content is preserved on initial page load
            setTimeout(function() {
                self.isInitialized = true;
                console.log('SDFilter initialized - AJAX now enabled');
            }, 1000);
        },

        initFromURL: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const urlCategory = urlParams.get('sd_category') || '';
            const urlTag = urlParams.get('sd_tag') || '';
            const urlPaged = urlParams.get('paged') || '';

            // Sync page number with URL
            if (urlPaged) {
                this.currentPage = parseInt(urlPaged) || 1;
            }

            // Sync tag with URL
            if (urlTag) {
                this.currentTag = urlTag;
                // Mark the correct chip as active
                $('.sd-chip').each(function() {
                    const href = $(this).attr('href');
                    if (href && href.includes('sd_tag=' + urlTag)) {
                        $(this).addClass('active');
                        return false;
                    }
                });
            }

            // Sync category (Bundesland) dropdown with URL
            if (urlCategory) {
                $('#sd_category_dropdown').val(urlCategory);
                $('#sd-drawer-category').val(urlCategory);
            }
        },

        getFilters: function() {
            // Get category (Bundesland) from hero dropdown or mobile drawer
            var category = $('#sd_category_dropdown').val() || $('#sd-drawer-category').val() || '';

            return {
                search: $('#sd_search').val() || '',
                category: category,
                tag: this.currentTag || '',
                location: '', // Location filter removed, using category for Bundesland
                premium: $('.sd-toggle-input[name="sd_premium"]').is(':checked') ? '1' : '',
                min_rating: $('#sd_min_rating').val() || '',
                orderby: $('#sd_orderby').val() || 'date_desc',
                per_page: $('#sd_per_page').val() || 12,
                paged: this.currentPage
            };
        },

        clearFilters: function() {
            $('#sd_search').val('');
            $('#sd_category_dropdown').prop('selectedIndex', 0); // Hero Bundesland dropdown
            $('#sd-drawer-category').prop('selectedIndex', 0); // Mobile Bundesland dropdown
            $('#sd_orderby').val('date_desc');
            $('#sd_min_rating').prop('selectedIndex', 0);
            $('.sd-toggle-input[name="sd_premium"]').prop('checked', false);
            $('.sd-chip').removeClass('active');
            // Remove category tag from input field (sync with sd-active-chip)
            $('#sd-category-tag').remove();
            $('#sd_search').removeClass('has-category-tag');
            $('#sd_search').attr('placeholder', 'Spezialist, Kategorie...');
            this.currentTag = '';
            this.currentPage = 1;
            this.fetchListings();
        },

        showLoading: function() {
            const $grid = $('#sd-listings-grid');
            const skeletonCount = 6;
            let skeletons = '';

            for (let i = 0; i < skeletonCount; i++) {
                skeletons += `
                    <article class="sd-listing-card sd-skeleton-card">
                        <div class="sd-listing-image">
                            <div class="sd-skeleton sd-skeleton-image"></div>
                        </div>
                        <div class="sd-listing-content">
                            <div class="sd-skeleton sd-skeleton-title"></div>
                            <div class="sd-skeleton sd-skeleton-meta"></div>
                            <div class="sd-skeleton sd-skeleton-text"></div>
                            <div class="sd-skeleton sd-skeleton-text sd-skeleton-text-short"></div>
                            <div class="sd-listing-actions">
                                <div class="sd-skeleton sd-skeleton-btn"></div>
                                <div class="sd-skeleton sd-skeleton-btn"></div>
                                <div class="sd-skeleton sd-skeleton-btn"></div>
                            </div>
                        </div>
                    </article>
                `;
            }

            $grid.addClass('sd-loading').html(skeletons);
            this.isLoading = true;
        },

        hideLoading: function() {
            $('#sd-listings-grid').removeClass('sd-loading');
            this.isLoading = false;
        },

        updateResultsCount: function(count) {
            const text = count === 1
                ? count + ' Hofladen gefunden'
                : count + ' Hofläden gefunden';
            $('.sd-results-count').text(text);
        },

        updateActiveFilters: function(filters) {
            let html = '';

            if (filters.search) {
                html += `<span class="sd-active-chip">"${filters.search}" <a href="#" class="sd-chip-remove" data-filter="search">×</a></span>`;
            }

            if (filters.category) {
                const categoryName = $('#sd_category_dropdown option:selected').text().replace(/\s*\(\d+\)$/, '');
                if (categoryName && categoryName !== 'Alle Kategorien') {
                    html += `<span class="sd-active-chip">${categoryName} <a href="#" class="sd-chip-remove" data-filter="category">×</a></span>`;
                }
            }

            if (filters.location) {
                const locationName = $('#sd_location option:selected').text();
                if (locationName && locationName !== 'Ganz Deutschland') {
                    html += `<span class="sd-active-chip">${locationName} <a href="#" class="sd-chip-remove" data-filter="location">×</a></span>`;
                }
            }

            if (filters.tag) {
                // Get tag name from active chip
                const $activeChip = $('.sd-chip.active');
                const tagName = $activeChip.length ? $activeChip.text().trim() : filters.tag;
                html += `<span class="sd-active-chip">${tagName} <a href="#" class="sd-chip-remove" data-filter="tag">×</a></span>`;
            }

            if (html) {
                html += '<a href="#" class="sd-clear-filters">Alle löschen</a>';
            }

            // Update or create active filters container
            let $container = $('.sd-active-filters');
            if (!$container.length && html) {
                $container = $('<div class="sd-active-filters"></div>');
                $('.sd-filter-bar-right').append($container);
            }

            if ($container.length) {
                $container.html(html);
                if (!html) {
                    $container.remove();
                }
            }
        },

        fetchListings: function(scrollAfterLoad, forceLoad) {
            // Prevent AJAX calls during initial page load (preserve server-rendered content)
            if (!this.isInitialized && !forceLoad) {
                return;
            }

            if (this.isLoading) return;

            const self = this;
            const filters = this.getFilters();
            const shouldScroll = scrollAfterLoad === true;

            this.showLoading();
            this.updateActiveFilters(filters);

            // Update URL without page reload (for bookmarking/sharing)
            this.updateURL(filters);

            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_filter_listings',
                    nonce: sdAjax.filterNonce,
                    search: filters.search,
                    category: filters.category,
                    tag: filters.tag,
                    location: filters.location,
                    premium: filters.premium,
                    min_rating: filters.min_rating,
                    orderby: filters.orderby,
                    paged: filters.paged
                },
                success: function(response) {
                    if (response.success) {
                        const $grid = $('#sd-listings-grid');

                        if (response.data.html) {
                            $grid.html(response.data.html);
                        } else {
                            $grid.html(`
                                <div class="sd-no-results-ajax">
                                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" fill="currentColor"/>
                                    </svg>
                                    <h3>Keine Hofläden gefunden</h3>
                                    <p>Versuche es mit anderen Suchbegriffen oder Filtern.</p>
                                </div>
                            `);
                        }

                        // Update pagination
                        // Insert after sd-listings-layout (not $grid) to preserve 2-column grid for map view
                        const $pagination = $('.sd-pagination');
                        if (response.data.pagination) {
                            if ($pagination.length) {
                                $pagination.html(response.data.pagination);
                            } else {
                                $('#sd-listings-layout').after('<div class="sd-pagination">' + response.data.pagination + '</div>');
                            }
                        } else {
                            $pagination.remove();
                        }

                        // Update results count
                        self.updateResultsCount(response.data.found_posts);

                        // Restore view preference
                        const savedView = localStorage.getItem(VIEW_STORAGE_KEY);
                        if (savedView === 'list') {
                            $grid.addClass('sd-view-list');
                        } else if (savedView === 'map') {
                            $('#sd-listings-layout').addClass('sd-map-view');
                        }

                        // Trigger event for map update
                        $(document).trigger('sd:listingsUpdated', [response.data]);

                        // Scroll to results after pagination click
                        if (shouldScroll) {
                            $('html, body').animate({
                                scrollTop: $('.sd-results-bar').offset().top - 100
                            }, 300);
                        }
                    }
                },
                error: function() {
                    console.error('Filter request failed');
                },
                complete: function() {
                    self.hideLoading();
                }
            });
        },

        updateURL: function(filters) {
            const url = new URL(window.location.href);

            // Clear existing params
            ['sd_search', 'sd_category', 'sd_tag', 'sd_location', 'sd_premium', 'sd_min_rating', 'sd_orderby', 'sd_per_page', 'paged'].forEach(function(param) {
                url.searchParams.delete(param);
            });

            // Set new params
            if (filters.search) url.searchParams.set('sd_search', filters.search);
            if (filters.category) url.searchParams.set('sd_category', filters.category);
            if (filters.tag) url.searchParams.set('sd_tag', filters.tag);
            if (filters.location) url.searchParams.set('sd_location', filters.location);
            if (filters.premium) url.searchParams.set('sd_premium', filters.premium);
            if (filters.min_rating) url.searchParams.set('sd_min_rating', filters.min_rating);
            if (filters.orderby && filters.orderby !== 'date_desc') url.searchParams.set('sd_orderby', filters.orderby);
            if (filters.per_page && filters.per_page != 12) url.searchParams.set('sd_per_page', filters.per_page);
            if (filters.paged > 1) url.searchParams.set('paged', filters.paged);

            // Update URL without reload
            window.history.replaceState({}, '', url.toString());
        }
    };

    /**
     * Mobile Filter Drawer
     * Provides a mobile-friendly bottom sheet for filtering
     */
    const SDMobileDrawer = {
        $drawer: null,
        $backdrop: null,
        $btn: null,
        scrollPosition: 0,

        init: function() {
            this.$drawer = $('#sd-filter-drawer');
            this.$backdrop = $('#sd-filter-drawer-backdrop');
            this.$btn = $('#sd-mobile-filter-btn');

            if (!this.$drawer.length) return;

            this.bindEvents();

            // Update filter count badge on page load (for URL parameters)
            this.updateFilterCount();
        },

        bindEvents: function() {
            const self = this;

            // Open drawer
            this.$btn.on('click', function() {
                self.openDrawer();
            });

            // Close drawer
            $('#sd-drawer-close').on('click', function() {
                self.closeDrawer();
            });

            // Close on backdrop click
            this.$backdrop.on('click', function() {
                self.closeDrawer();
            });

            // Apply filters
            $('#sd-drawer-apply').on('click', function() {
                self.applyFilters();
            });

            // Reset filters
            $('#sd-drawer-reset').on('click', function() {
                self.resetFilters();
            });

            // Close on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.$drawer.hasClass('sd-open')) {
                    self.closeDrawer();
                }
            });

            // Prevent body scroll when drawer is open (touch devices)
            this.$drawer.on('touchmove', function(e) {
                e.stopPropagation();
            });
        },

        openDrawer: function() {
            // Save scroll position
            this.scrollPosition = window.pageYOffset;

            // Lock body scroll
            $('body').addClass('sd-drawer-open');
            $('body').css('top', -this.scrollPosition + 'px');

            // Show drawer and backdrop
            this.$backdrop.addClass('sd-open');
            this.$drawer.addClass('sd-open');

            // Focus first input for accessibility
            this.$drawer.find('select, button').first().focus();
        },

        closeDrawer: function() {
            // Hide drawer and backdrop
            this.$drawer.removeClass('sd-open');
            this.$backdrop.removeClass('sd-open');

            // Unlock body scroll
            $('body').removeClass('sd-drawer-open');
            $('body').css('top', '');

            // Restore scroll position
            window.scrollTo(0, this.scrollPosition);

            // Return focus to trigger button
            this.$btn.focus();
        },

        applyFilters: function() {
            const category = $('#sd-drawer-category').val();
            const location = $('#sd-drawer-location').val();
            const orderby = $('#sd-drawer-orderby').val();
            const premium = $('#sd-drawer-premium').is(':checked') ? '1' : '';

            // Get search value from hero search if exists
            const search = $('#sd_search').val() || '';

            // If AJAX filtering is active, use it
            if (typeof SDFilter !== 'undefined' && SDFilter.enabled) {
                const filters = {
                    search: search,
                    category: category,
                    location: location,
                    premium: premium,
                    orderby: orderby,
                    paged: 1
                };

                // Sync desktop filter elements
                $('#sd_category_dropdown option').filter(function() {
                    return $(this).text().includes(category) || $(this).attr('value').includes('sd_category=' + category);
                }).prop('selected', true);

                $('#sd_orderby').val(orderby);
                $('input[name="sd_premium"]').prop('checked', premium === '1');

                // Trigger AJAX filter
                SDFilter.submitFilter(filters);
                SDFilter.updateURL(filters);

                this.closeDrawer();
                this.updateFilterCount();
            } else {
                // Fallback: Build URL and navigate
                const url = new URL(window.location.href);
                url.search = '';

                if (search) url.searchParams.set('sd_search', search);
                if (category) url.searchParams.set('sd_category', category);
                if (location) url.searchParams.set('sd_location', location);
                if (premium) url.searchParams.set('sd_premium', premium);
                if (orderby && orderby !== 'date_desc') url.searchParams.set('sd_orderby', orderby);

                window.location.href = url.toString();
            }
        },

        resetFilters: function() {
            // Reset drawer form elements
            $('#sd-drawer-category').val('');
            $('#sd-drawer-location').val('');
            $('#sd-drawer-orderby').val('date_desc');
            $('#sd-drawer-premium').prop('checked', false);

            // Also reset hero search
            $('#sd_search').val('');

            // If AJAX filtering, trigger with empty filters
            if (typeof SDFilter !== 'undefined' && SDFilter.enabled) {
                const filters = {
                    search: '',
                    category: '',
                    location: '',
                    premium: '',
                    orderby: 'date_desc',
                    paged: 1
                };

                // Reset desktop filters too
                $('#sd_category_dropdown').val($('#sd_category_dropdown option:first').val());
                $('#sd_orderby').val('date_desc');
                $('input[name="sd_premium"]').prop('checked', false);

                SDFilter.submitFilter(filters);
                SDFilter.updateURL(filters);

                this.closeDrawer();
                this.updateFilterCount();
            } else {
                // Navigate to base URL
                window.location.href = window.location.pathname;
            }
        },

        updateFilterCount: function() {
            let count = 0;

            if ($('#sd_search').val()) count++;
            if ($('#sd-drawer-category').val()) count++;
            if ($('#sd-drawer-location').val()) count++;
            if ($('#sd-drawer-premium').is(':checked')) count++;
            if ($('#sd-drawer-orderby').val() !== 'date_desc') count++;

            const $countBadge = this.$btn.find('.sd-filter-count');

            if (count > 0) {
                if ($countBadge.length) {
                    $countBadge.text(count);
                } else {
                    this.$btn.append('<span class="sd-filter-count">' + count + '</span>');
                }
            } else {
                $countBadge.remove();
            }
        }
    };

    /**
     * Search Autocomplete System
     * Shows suggestions for categories and listings
     */
    const SDAutocomplete = {
        minChars: 4,
        debounceTimer: null,
        debounceDelay: 300,
        $input: null,
        $dropdown: null,
        selectedIndex: -1,
        results: { categories: [], listings: [] },
        isFetching: false,

        init: function() {
            this.$input = $('#sd_search');
            this.$dropdown = $('#sd-autocomplete');

            if (!this.$input.length || !this.$dropdown.length) return;

            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Input event with debounce
            this.$input.on('input', function() {
                clearTimeout(self.debounceTimer);
                const term = $(this).val().trim();

                if (term.length < self.minChars) {
                    self.hide();
                    return;
                }

                self.debounceTimer = setTimeout(function() {
                    self.fetch(term);
                }, self.debounceDelay);
            });

            // Keyboard navigation
            this.$input.on('keydown', function(e) {
                if (!self.isVisible()) return;

                const $items = self.$dropdown.find('.sd-autocomplete-item');
                const totalItems = $items.length;

                switch (e.keyCode) {
                    case 40: // Arrow Down
                        e.preventDefault();
                        self.selectedIndex = Math.min(self.selectedIndex + 1, totalItems - 1);
                        self.highlightItem($items);
                        break;

                    case 38: // Arrow Up
                        e.preventDefault();
                        self.selectedIndex = Math.max(self.selectedIndex - 1, 0);
                        self.highlightItem($items);
                        break;

                    case 13: // Enter
                        if (self.selectedIndex >= 0 && $items.length > 0) {
                            e.preventDefault();
                            $items.eq(self.selectedIndex).trigger('click');
                        }
                        break;

                    case 27: // Escape
                        self.hide();
                        break;
                }
            });

            // Click on item
            $(document).on('click', '.sd-autocomplete-item', function(e) {
                e.preventDefault();
                const $item = $(this);
                const type = $item.data('type');

                if (type === 'category') {
                    // Set category filter and clear search
                    const slug = $item.data('slug');
                    self.selectCategory(slug);
                } else if (type === 'listing') {
                    // Set title in search and submit
                    const title = $item.data('title');
                    self.$input.val(title);
                    self.hide();
                    self.submitForm();
                }
            });

            // Close on click outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.sd-autocomplete-wrapper').length) {
                    self.hide();
                }
            });

            // Close on blur with delay (allow click on item)
            this.$input.on('blur', function() {
                setTimeout(function() {
                    // Don't hide if still fetching autocomplete results
                    if (self.isFetching) {
                        return;
                    }
                    if (!self.$dropdown.is(':hover')) {
                        self.hide();
                    }
                }, 400); // Increased from 200ms to allow AJAX to complete
            });

            // Show on focus if has value
            this.$input.on('focus', function() {
                const term = $(this).val().trim();
                if (term.length >= self.minChars && self.hasResults()) {
                    self.show();
                }
            });
        },

        fetch: function(term) {
            const self = this;
            this.isFetching = true;

            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_autocomplete',
                    nonce: sdAjax.filterNonce,
                    term: term
                },
                success: function(response) {
                    if (response.success) {
                        self.results = response.data;
                        self.render();
                    }
                },
                error: function() {
                    self.hide();
                },
                complete: function() {
                    self.isFetching = false;
                }
            });
        },

        render: function() {
            const self = this;
            let html = '';

            // Categories section
            if (this.results.categories && this.results.categories.length > 0) {
                html += '<div class="sd-autocomplete-section">';
                html += '<div class="sd-autocomplete-section-title">Kategorien</div>';
                this.results.categories.forEach(function(cat) {
                    html += '<div class="sd-autocomplete-item" data-type="category" data-slug="' + self.escapeHtml(cat.slug) + '">';
                    html += '<span class="sd-autocomplete-item-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z" fill="currentColor"/></svg></span>';
                    html += '<span class="sd-autocomplete-item-text">' + self.escapeHtml(cat.name) + '</span>';
                    html += '</div>';
                });
                html += '</div>';
            }

            // Listings section
            if (this.results.listings && this.results.listings.length > 0) {
                html += '<div class="sd-autocomplete-section">';
                html += '<div class="sd-autocomplete-section-title">Hofläden</div>';
                this.results.listings.forEach(function(listing) {
                    html += '<div class="sd-autocomplete-item" data-type="listing" data-id="' + listing.id + '" data-title="' + self.escapeHtml(listing.title) + '">';
                    html += '<span class="sd-autocomplete-item-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/></svg></span>';
                    html += '<span class="sd-autocomplete-item-text">' + self.escapeHtml(listing.title) + '</span>';
                    html += '</div>';
                });
                html += '</div>';
            }

            // No results
            if (!this.hasResults()) {
                html = '<div class="sd-autocomplete-empty">Keine Treffer gefunden</div>';
            }

            this.$dropdown.html(html);
            this.selectedIndex = -1;
            this.show();
        },

        highlightItem: function($items) {
            $items.removeClass('active');
            if (this.selectedIndex >= 0) {
                $items.eq(this.selectedIndex).addClass('active');
            }
        },

        selectCategory: function(slug) {
            // Find and update category dropdown
            const $dropdown = $('#sd_category_dropdown');
            let categoryName = '';

            $dropdown.find('option').each(function() {
                const url = $(this).val();
                if (url && url.includes('sd_category=' + slug)) {
                    $dropdown.val(url);
                    // Extract category name from option text (remove count in parentheses)
                    categoryName = $(this).text().replace(/\s*\(\d+\)\s*$/, '').trim();
                    return false;
                }
            });

            // Also update chip active state
            $('.sd-chip').removeClass('active');
            $('.sd-chip[href*="sd_category=' + slug + '"]').addClass('active');

            // Sync mobile drawer category dropdown
            $('#sd-drawer-category').val(slug);

            // WICHTIG: Suchfeld ZUERST leeren, DANN count updaten!
            // Sonst zählt updateFilterCount() den getippten Suchtext mit
            this.$input.val('');

            // Update mobile filter badge count (jetzt ist #sd_search leer)
            SDMobileDrawer.updateFilterCount();

            // Show category tag in WAS field
            this.showCategoryTag(slug, categoryName);

            this.hide();

            // Submit form
            this.submitForm();
        },

        showCategoryTag: function(slug, name) {
            // Remove existing tag if present
            $('#sd-category-tag').remove();

            // Create new tag
            const removeUrl = window.location.pathname;
            const tagHtml = '<div class="sd-category-tag" id="sd-category-tag">' +
                '<span class="sd-category-tag-text">' + this.escapeHtml(name) + '</span>' +
                '<a href="' + removeUrl + '" class="sd-category-tag-remove" title="Kategorie entfernen">×</a>' +
                '</div>';

            // Insert tag before input field
            const $input = $('#sd_search');
            $input.before(tagHtml);
            $input.addClass('has-category-tag');
            $input.attr('placeholder', '');

            // Event handler for remove button
            const self = this;
            $('#sd-category-tag .sd-category-tag-remove').on('click', function(e) {
                e.preventDefault();
                self.removeCategoryTag();
            });
        },

        removeCategoryTag: function() {
            $('#sd-category-tag').remove();
            const $input = $('#sd_search');
            $input.removeClass('has-category-tag');
            $input.attr('placeholder', 'Hofladen, Produkt...');

            // Reset dropdowns
            $('#sd_category_dropdown').val($('#sd_category_dropdown option:first').val());
            $('#sd-drawer-category').val('');
            $('.sd-chip').removeClass('active');
            $('.sd-chip').first().addClass('active');

            // Update mobile filter badge count
            // Note: SDMobileDrawer is a const in the same IIFE scope, so we can call it directly
            SDMobileDrawer.updateFilterCount();

            // Reload without category filter
            SDFilter.currentPage = 1;
            SDFilter.fetchListings();
        },

        submitForm: function() {
            // Trigger AJAX filter if SDFilter is available
            if (typeof SDFilter !== 'undefined' && SDFilter.fetchListings) {
                SDFilter.currentPage = 1;
                SDFilter.fetchListings();
            } else {
                // Fallback: submit form
                $('#sd-filter-form').trigger('submit');
            }
        },

        hasResults: function() {
            return (this.results.categories && this.results.categories.length > 0) ||
                   (this.results.listings && this.results.listings.length > 0);
        },

        isVisible: function() {
            return this.$dropdown.is(':visible');
        },

        show: function() {
            this.$dropdown.show();
        },

        hide: function() {
            this.$dropdown.hide();
            this.selectedIndex = -1;
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * Favorites/Merkliste System
     * Stores favorites in localStorage (no login required)
     */
    const SDFavorites = {
        STORAGE_KEY: 'sd_favorites',
        compareList: [],
        maxCompare: 4,
        listingsData: [], // Store fetched listings data for compare

        init: function() {
            this.bindEvents();
            this.updateAllButtons();
        },

        bindEvents: function() {
            const self = this;

            // Toggle favorite on bookmark button click
            $(document).on('click', '.sd-bookmark-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const $btn = $(this);
                const postId = $btn.data('post-id');

                if (self.isFavorite(postId)) {
                    self.removeFavorite(postId);
                    self.updateButtonState($btn, false);
                    self.showToast('Von Merkliste entfernt');
                } else {
                    self.addFavorite(postId);
                    self.updateButtonState($btn, true);
                    self.showToast('Zur Merkliste hinzugefügt');
                }
            });

            // Remove from favorites page
            $(document).on('click', '.sd-favorites-remove', function(e) {
                e.preventDefault();
                const postId = $(this).data('post-id');
                self.removeFavorite(postId);
                $(this).closest('.sd-favorites-item').fadeOut(300, function() {
                    $(this).remove();
                    self.updateFavoritesPage();
                });
            });

            // Clear all favorites
            $(document).on('click', '.sd-clear-favorites', function(e) {
                e.preventDefault();
                if (confirm('Möchtest du wirklich alle Einträge von deiner Merkliste entfernen?')) {
                    self.clearAll();
                    self.updateFavoritesPage();
                    self.showToast('Merkliste geleert');
                }
            });

            // Compare checkbox toggle
            $(document).on('change', '.sd-compare-checkbox', function() {
                const postId = parseInt($(this).data('post-id'), 10);
                self.toggleCompare(postId);
            });

            // Compare button click
            $(document).on('click', '.sd-compare-btn', function(e) {
                e.preventDefault();
                if (self.compareList.length >= 2) {
                    self.renderCompareSection();
                    // Smooth scroll to compare section
                    setTimeout(function() {
                        const $section = $('.sd-compare-section');
                        if ($section.length) {
                            $('html, body').animate({
                                scrollTop: $section.offset().top - 20
                            }, 500);
                        }
                    }, 100);
                }
            });

            // Close compare section
            $(document).on('click', '.sd-compare-close', function(e) {
                e.preventDefault();
                $('.sd-compare-section').slideUp(300, function() {
                    $(this).remove();
                });
            });

            // Remove from compare in table
            $(document).on('click', '.sd-compare-remove', function(e) {
                e.preventDefault();
                const postId = parseInt($(this).data('post-id'), 10);
                self.toggleCompare(postId);
                // Re-render if still has 2+ items
                if (self.compareList.length >= 2) {
                    self.renderCompareSection();
                } else {
                    $('.sd-compare-section').slideUp(300, function() {
                        $(this).remove();
                    });
                }
            });
        },

        getFavorites: function() {
            try {
                const stored = localStorage.getItem(this.STORAGE_KEY);
                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                console.error('Error reading favorites from localStorage:', e);
                return [];
            }
        },

        saveFavorites: function(favorites) {
            try {
                localStorage.setItem(this.STORAGE_KEY, JSON.stringify(favorites));
            } catch (e) {
                console.error('Error saving favorites to localStorage:', e);
            }
        },

        addFavorite: function(postId) {
            postId = parseInt(postId, 10);
            const favorites = this.getFavorites();
            if (!favorites.includes(postId)) {
                favorites.push(postId);
                this.saveFavorites(favorites);
            }
        },

        removeFavorite: function(postId) {
            postId = parseInt(postId, 10);
            let favorites = this.getFavorites();
            favorites = favorites.filter(function(id) {
                return id !== postId;
            });
            this.saveFavorites(favorites);
        },

        isFavorite: function(postId) {
            postId = parseInt(postId, 10);
            return this.getFavorites().includes(postId);
        },

        clearAll: function() {
            this.saveFavorites([]);
            // Update all buttons on page
            $('.sd-bookmark-btn').removeClass('is-favorited');
        },

        updateButtonState: function($btn, isFavorited) {
            if (isFavorited) {
                $btn.addClass('is-favorited');
                $btn.attr('title', 'Von Merkliste entfernen');
                $btn.find('.sd-bookmark-text').text('Gemerkt');
            } else {
                $btn.removeClass('is-favorited');
                $btn.attr('title', 'Zur Merkliste hinzufügen');
                $btn.find('.sd-bookmark-text').text('Merken');
            }
        },

        updateAllButtons: function() {
            const self = this;
            $('.sd-bookmark-btn').each(function() {
                const $btn = $(this);
                const postId = $btn.data('post-id');
                const isFavorited = self.isFavorite(postId);
                self.updateButtonState($btn, isFavorited);
            });
        },

        showToast: function(message) {
            // Remove existing toast
            $('.sd-toast').remove();

            const $toast = $('<div class="sd-toast">' + message + '</div>');
            $('body').append($toast);

            // Trigger animation
            setTimeout(function() {
                $toast.addClass('sd-toast-visible');
            }, 10);

            // Auto-hide after 2 seconds
            setTimeout(function() {
                $toast.removeClass('sd-toast-visible');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 2000);
        },

        // Render favorites page content
        renderFavoritesPage: function() {
            const self = this;
            const $container = $('.sd-favorites-list');
            const $empty = $('.sd-favorites-empty');
            const $header = $('.sd-favorites-header');

            if (!$container.length) return;

            const favorites = this.getFavorites();

            if (favorites.length === 0) {
                $container.hide();
                $header.find('.sd-clear-favorites').hide();
                $empty.show();
                return;
            }

            // Show loading state
            $container.html('<div class="sd-favorites-loading"><div class="sd-spinner"></div> Lade Merkliste...</div>');
            $empty.hide();
            $header.find('.sd-clear-favorites').show();

            // Fetch listing data via AJAX
            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_get_favorites_data',
                    nonce: sdAjax.nonce,
                    post_ids: favorites
                },
                success: function(response) {
                    if (response.success && response.data.listings) {
                        self.renderFavoritesTable(response.data.listings);
                    } else {
                        $container.html('<p class="sd-favorites-error">Fehler beim Laden der Merkliste.</p>');
                    }
                },
                error: function() {
                    $container.html('<p class="sd-favorites-error">Fehler beim Laden der Merkliste.</p>');
                }
            });
        },

        renderFavoritesTable: function(listings) {
            const self = this;
            const $container = $('.sd-favorites-list');
            const $empty = $('.sd-favorites-empty');
            const $header = $('.sd-favorites-header');

            // Store listings data for compare feature
            this.listingsData = listings;
            // Reset compare list when favorites are reloaded
            this.compareList = [];

            if (listings.length === 0) {
                $container.hide();
                $header.find('.sd-clear-favorites').hide();
                $empty.show();
                return;
            }

            // Compare controls (only show if 2+ favorites)
            let html = '';
            if (listings.length >= 2) {
                html += '<div class="sd-compare-controls">';
                html += '<span class="sd-compare-counter">0/' + this.maxCompare + ' ausgewählt</span>';
                html += '<button type="button" class="sd-compare-btn sd-btn sd-btn-primary disabled" disabled>';
                html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M10 3H4a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1V4a1 1 0 00-1-1zM9 9H5V5h4v4zm11-6h-6a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1V4a1 1 0 00-1-1zm-1 6h-4V5h4v4zm-9 4H4a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1v-6a1 1 0 00-1-1zm-1 6H5v-4h4v4zm11-6h-6a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1v-6a1 1 0 00-1-1zm-1 6h-4v-4h4v4z" fill="currentColor"/></svg>';
                html += ' Vergleichen (<span class="sd-compare-count">0</span>)';
                html += '</button>';
                html += '</div>';
            }

            html += '<div class="sd-favorites-table">';

            listings.forEach(function(listing) {
                const isInCompare = self.isInCompare(listing.id);
                html += `
                    <div class="sd-favorites-item" data-post-id="${listing.id}">
                        ${listings.length >= 2 ? `
                        <div class="sd-favorites-checkbox">
                            <label class="sd-checkbox-label">
                                <input type="checkbox" class="sd-compare-checkbox" data-post-id="${listing.id}" ${isInCompare ? 'checked' : ''}>
                                <span class="sd-checkbox-custom"></span>
                            </label>
                        </div>
                        ` : ''}
                        <div class="sd-favorites-thumb">
                            <a href="${listing.url}">
                                <img src="${listing.thumbnail}" alt="${listing.title}" loading="lazy" />
                            </a>
                        </div>
                        <div class="sd-favorites-info">
                            <h3 class="sd-favorites-title">
                                <a href="${listing.url}">${listing.title}</a>
                            </h3>
                            <div class="sd-favorites-meta">
                                ${listing.category ? '<span class="sd-favorites-category">' + listing.category + '</span>' : ''}
                                ${listing.city ? '<span class="sd-favorites-location"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13 15.87 2 12 2ZM12 11.5C10.62 11.5 9.5 10.38 9.5 9C9.5 7.62 10.62 6.5 12 6.5C13.38 6.5 14.5 7.62 14.5 9C14.5 10.38 13.38 11.5 12 11.5Z" fill="currentColor"/></svg> ' + listing.city + '</span>' : ''}
                            </div>
                        </div>
                        <div class="sd-favorites-actions">
                            <a href="${listing.url}" class="sd-btn sd-btn-primary sd-btn-sm">Ansehen</a>
                            <button type="button" class="sd-btn sd-btn-outline sd-btn-sm sd-favorites-remove" data-post-id="${listing.id}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" fill="currentColor"/></svg>
                            </button>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            $container.html(html).show();
        },

        updateFavoritesPage: function() {
            const favorites = this.getFavorites();
            const $container = $('.sd-favorites-list');
            const $empty = $('.sd-favorites-empty');
            const $header = $('.sd-favorites-header');

            if (favorites.length === 0) {
                $container.hide();
                $header.find('.sd-clear-favorites').hide();
                $empty.show();
            }
        },

        // Compare functionality
        toggleCompare: function(postId) {
            postId = parseInt(postId, 10);
            const index = this.compareList.indexOf(postId);

            if (index > -1) {
                // Remove from compare list
                this.compareList.splice(index, 1);
            } else if (this.compareList.length < this.maxCompare) {
                // Add to compare list
                this.compareList.push(postId);
            } else {
                // Max reached
                this.showToast('Maximal ' + this.maxCompare + ' Einträge vergleichbar');
                // Uncheck the checkbox
                $('.sd-compare-checkbox[data-post-id="' + postId + '"]').prop('checked', false);
                return;
            }

            this.updateCompareUI();
        },

        isInCompare: function(postId) {
            return this.compareList.indexOf(parseInt(postId, 10)) > -1;
        },

        updateCompareUI: function() {
            const self = this;
            const count = this.compareList.length;

            // Update all checkboxes
            $('.sd-compare-checkbox').each(function() {
                const postId = parseInt($(this).data('post-id'), 10);
                $(this).prop('checked', self.isInCompare(postId));

                // Disable unchecked checkboxes if max reached
                if (count >= self.maxCompare && !self.isInCompare(postId)) {
                    $(this).prop('disabled', true);
                } else {
                    $(this).prop('disabled', false);
                }
            });

            // Update compare button
            const $btn = $('.sd-compare-btn');
            if (count >= 2) {
                $btn.prop('disabled', false).removeClass('disabled');
            } else {
                $btn.prop('disabled', true).addClass('disabled');
            }
            $btn.find('.sd-compare-count').text(count);

            // Update counter text
            $('.sd-compare-counter').text(count + '/' + this.maxCompare + ' ausgewählt');
        },

        renderCompareSection: function() {
            const self = this;

            // Filter listings data for compare
            const compareListings = this.listingsData.filter(function(listing) {
                return self.compareList.indexOf(listing.id) > -1;
            });

            if (compareListings.length < 2) return;

            // Remove existing compare section
            $('.sd-compare-section').remove();

            // Generate compare table HTML
            const html = this.generateCompareTable(compareListings);

            // Insert after favorites list
            const $section = $('<div class="sd-compare-section">' + html + '</div>');
            $('.sd-favorites-list').after($section);

            // Animate in
            $section.hide().slideDown(300);
        },

        generateCompareTable: function(listings) {
            const self = this;

            let html = '<div class="sd-compare-header">';
            html += '<h3>Vergleich</h3>';
            html += '<button type="button" class="sd-compare-close" title="Schließen">';
            html += '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" fill="currentColor"/></svg>';
            html += '</button>';
            html += '</div>';

            html += '<div class="sd-compare-table-wrapper">';
            html += '<table class="sd-compare-table">';

            // Header row with thumbnails and titles
            html += '<thead><tr>';
            html += '<th class="sd-compare-label"></th>';
            listings.forEach(function(listing) {
                html += '<th class="sd-compare-cell sd-compare-cell-header">';
                html += '<button type="button" class="sd-compare-remove" data-post-id="' + listing.id + '" title="Aus Vergleich entfernen">';
                html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" fill="currentColor"/></svg>';
                html += '</button>';
                html += '<a href="' + listing.url + '" class="sd-compare-thumb">';
                html += '<img src="' + listing.thumbnail + '" alt="' + self.decodeHtml(listing.title) + '" />';
                html += '</a>';
                html += '<a href="' + listing.url + '" class="sd-compare-title">' + self.decodeHtml(listing.title) + '</a>';
                if (listing.verified) {
                    html += '<span class="sd-compare-badge sd-badge-verified" title="Verifiziert"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z" fill="#22c55e"/></svg></span>';
                }
                if (listing.premium) {
                    html += '<span class="sd-compare-badge sd-badge-premium" title="Premium"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="#f59e0b"/></svg></span>';
                }
                html += '</th>';
            });
            html += '</tr></thead>';

            html += '<tbody>';

            // Rating row
            html += '<tr class="sd-compare-row">';
            html += '<td class="sd-compare-label">Bewertung</td>';
            listings.forEach(function(listing) {
                html += '<td class="sd-compare-cell">';
                if (listing.rating && listing.rating_count > 0) {
                    html += self.renderStars(listing.rating);
                    html += '<span class="sd-compare-rating-value">' + parseFloat(listing.rating).toFixed(1) + '</span>';
                    html += '<span class="sd-compare-rating-count">(' + listing.rating_count + ')</span>';
                } else {
                    html += '<span class="sd-compare-na">Keine Bewertungen</span>';
                }
                html += '</td>';
            });
            html += '</tr>';

            // Categories row
            html += '<tr class="sd-compare-row">';
            html += '<td class="sd-compare-label">Kategorien</td>';
            listings.forEach(function(listing) {
                html += '<td class="sd-compare-cell">';
                if (listing.categories && listing.categories.length > 0) {
                    html += listing.categories.join(', ');
                } else if (listing.category) {
                    html += listing.category;
                } else {
                    html += '<span class="sd-compare-na">-</span>';
                }
                html += '</td>';
            });
            html += '</tr>';

            // Location row
            html += '<tr class="sd-compare-row">';
            html += '<td class="sd-compare-label">Standort</td>';
            listings.forEach(function(listing) {
                html += '<td class="sd-compare-cell">';
                let location = [];
                if (listing.address) location.push(listing.address);
                if (listing.zip && listing.city) {
                    location.push(listing.zip + ' ' + listing.city);
                } else if (listing.city) {
                    location.push(listing.city);
                }
                html += location.length > 0 ? location.join('<br>') : '<span class="sd-compare-na">-</span>';
                html += '</td>';
            });
            html += '</tr>';

            // Description row (description contains sanitized HTML from WordPress)
            html += '<tr class="sd-compare-row">';
            html += '<td class="sd-compare-label">Beschreibung</td>';
            listings.forEach(function(listing) {
                html += '<td class="sd-compare-cell sd-compare-description">';
                html += listing.description ? listing.description : '<span class="sd-compare-na">-</span>';
                html += '</td>';
            });
            html += '</tr>';

            // Services row
            html += '<tr class="sd-compare-row">';
            html += '<td class="sd-compare-label">Leistungen</td>';
            listings.forEach(function(listing) {
                html += '<td class="sd-compare-cell">';
                if (listing.services && listing.services.length > 0) {
                    html += '<ul class="sd-compare-services">';
                    listing.services.forEach(function(service) {
                        html += '<li>' + self.escapeHtml(service) + '</li>';
                    });
                    html += '</ul>';
                } else {
                    html += '<span class="sd-compare-na">-</span>';
                }
                html += '</td>';
            });
            html += '</tr>';

            // Phone row
            html += '<tr class="sd-compare-row">';
            html += '<td class="sd-compare-label">Telefon</td>';
            listings.forEach(function(listing) {
                html += '<td class="sd-compare-cell">';
                if (listing.phone) {
                    html += '<a href="tel:' + listing.phone + '">' + self.escapeHtml(listing.phone) + '</a>';
                } else {
                    html += '<span class="sd-compare-na">-</span>';
                }
                html += '</td>';
            });
            html += '</tr>';

            html += '</tbody>';

            // Footer with action buttons
            html += '<tfoot><tr>';
            html += '<td class="sd-compare-label"></td>';
            listings.forEach(function(listing) {
                html += '<td class="sd-compare-cell sd-compare-actions">';
                html += '<a href="' + listing.url + '" class="sd-btn sd-btn-primary sd-btn-sm">Ansehen <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z" fill="currentColor"/></svg></a>';
                html += '</td>';
            });
            html += '</tr></tfoot>';

            html += '</table>';
            html += '</div>';

            return html;
        },

        renderStars: function(rating) {
            rating = parseFloat(rating);
            let html = '<span class="sd-compare-stars">';
            for (let i = 1; i <= 5; i++) {
                if (rating >= i) {
                    html += '<svg class="sd-star sd-star-full" width="14" height="14" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="#F59E0B"/></svg>';
                } else if (rating >= i - 0.5) {
                    html += '<svg class="sd-star sd-star-half" width="14" height="14" viewBox="0 0 24 24"><defs><linearGradient id="half"><stop offset="50%" stop-color="#F59E0B"/><stop offset="50%" stop-color="#D1D5DB"/></linearGradient></defs><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="url(#half)"/></svg>';
                } else {
                    html += '<svg class="sd-star sd-star-empty" width="14" height="14" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="#D1D5DB"/></svg>';
                }
            }
            html += '</span>';
            return html;
        },

        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        decodeHtml: function(text) {
            if (!text) return '';
            // Safe HTML entity decoding using DOMParser (no script execution)
            const parser = new DOMParser();
            const doc = parser.parseFromString(text, 'text/html');
            return doc.documentElement.textContent || '';
        }
    };

    /**
     * Services/Offerings Module
     * Handles add/remove services for submission form and edit modal
     */
    const SDServices = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Add service - Submission form
            $(document).on('click', '.sd-add-service', function(e) {
                e.preventDefault();
                const $container = $(this).closest('.sd-services-form');
                const $input = $container.find('input[type="text"]').first();
                const value = $input.val().trim();

                if (value) {
                    const $list = $container.find('.sd-services-list');
                    $list.append(self.createServiceItem(value, 'submit'));
                    $input.val('').focus();
                }
            });

            // Add service - Edit modal
            $(document).on('click', '.sd-add-service-edit', function(e) {
                e.preventDefault();
                const $input = $('#sd-edit-new-service');
                const value = $input.val().trim();

                if (value) {
                    $('#sd-edit-services-list').append(self.createServiceItem(value, 'edit'));
                    $input.val('').focus();
                }
            });

            // Add service on Enter key
            $(document).on('keypress', '#sd-new-service, #sd-edit-new-service', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $(this).closest('.sd-service-add-row').find('button').click();
                }
            });

            // Remove service
            $(document).on('click', '.sd-remove-service', function(e) {
                e.preventDefault();
                $(this).closest('.sd-service-item').fadeOut(200, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Create a service item element
         * @param {string} value - The service name
         * @param {string} context - 'submit' or 'edit'
         * @returns {jQuery} The service item element
         */
        createServiceItem: function(value, context) {
            const inputName = context === 'edit' ? '' : 'services[]';
            const escapedValue = $('<div>').text(value).html();

            return $(`
                <div class="sd-service-item">
                    ${inputName ? '<input type="hidden" name="' + inputName + '" value="' + escapedValue + '">' : ''}
                    <span class="sd-service-text">${escapedValue}</span>
                    <button type="button" class="sd-remove-service" title="Entfernen">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
            `);
        },

        /**
         * Get all services from a container
         * @param {string} containerId - The container selector
         * @returns {Array} Array of service names
         */
        getServices: function(containerId) {
            const services = [];
            $(containerId).find('.sd-service-item').each(function() {
                const text = $(this).find('.sd-service-text').text().trim();
                if (text) {
                    services.push(text);
                }
            });
            return services;
        }
    };

    /**
     * Tags Module
     * Handles add/remove custom tags for submission form
     */
    const SDTags = {
        newTags: [],

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Add tag on button click
            $(document).on('click', '.sd-add-tag', function(e) {
                e.preventDefault();
                const $input = $('#sd-new-tag');
                const value = $input.val().trim();

                if (value && !self.tagExists(value)) {
                    self.addTag(value);
                    $input.val('').focus();
                }
            });

            // Add tag on Enter key
            $(document).on('keypress', '#sd-new-tag', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('.sd-add-tag').click();
                }
            });

            // Remove tag
            $(document).on('click', '.sd-remove-tag', function(e) {
                e.preventDefault();
                const $item = $(this).closest('.sd-tag-item');
                const tagName = $item.find('.sd-tag-text').text().trim();

                // Remove from array
                self.newTags = self.newTags.filter(t => t !== tagName);
                self.updateHiddenField();

                $item.fadeOut(200, function() {
                    $(this).remove();
                });
            });
        },

        tagExists: function(value) {
            // Check if already in newTags array
            if (this.newTags.includes(value)) {
                return true;
            }
            // Check if already selected in dropdown
            const selectedOptions = $('#sd_tags').val() || [];
            const existingNames = [];
            $('#sd_tags option:selected').each(function() {
                existingNames.push($(this).text().toLowerCase());
            });
            return existingNames.includes(value.toLowerCase());
        },

        addTag: function(value) {
            this.newTags.push(value);
            this.updateHiddenField();

            const escapedValue = $('<div>').text(value).html();
            const $item = $(`
                <span class="sd-tag-item">
                    <span class="sd-tag-text">${escapedValue}</span>
                    <button type="button" class="sd-remove-tag" title="Entfernen">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                        </svg>
                    </button>
                </span>
            `);

            $('#sd-new-tags-list').append($item);
        },

        updateHiddenField: function() {
            $('#sd-new-tags-hidden').val(this.newTags.join(','));
        }
    };

    /**
     * Review Response Handlers (Dashboard)
     */
    const SDReviewResponses = {
        init: function() {
            // Show response form
            $(document).on('click', '.sd-show-response-form', function() {
                const $container = $(this).closest('.sd-response-form-container');
                $(this).hide();
                $container.find('.sd-response-form').slideDown();
            });

            // Cancel response
            $(document).on('click', '.sd-cancel-response', function() {
                const $container = $(this).closest('.sd-response-form-container');
                $container.find('.sd-response-form').slideUp(function() {
                    $container.find('.sd-show-response-form').show();
                    $container.find('textarea').val('');
                });
            });

            // Submit response
            $(document).on('submit', '.sd-response-form', function(e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $form.find('.sd-submit-response');
                const $reviewItem = $form.closest('.sd-review-item');
                const ratingId = $form.find('input[name="rating_id"]').val();
                const response = $form.find('textarea[name="response"]').val();

                if (!response.trim()) {
                    return;
                }

                // Show loading
                $btn.prop('disabled', true);
                $btn.find('.sd-btn-text').hide();
                $btn.find('.sd-btn-loading').show();

                $.ajax({
                    url: sdAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sr_submit_response',
                        rating_id: ratingId,
                        response: response,
                        nonce: typeof srRatings !== 'undefined' ? srRatings.nonce : ''
                    },
                    success: function(res) {
                        if (res.success) {
                            // Replace form with response display
                            const responseHtml = `
                                <div class="sd-owner-response">
                                    <div class="sd-response-header">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z" fill="currentColor"/>
                                        </svg>
                                        <span>Deine Antwort</span>
                                        <span class="sd-response-date">Gerade eben</span>
                                    </div>
                                    <div class="sd-response-content">
                                        <p>${SDReviewResponses.escapeHtml(response)}</p>
                                    </div>
                                </div>
                            `;
                            $form.closest('.sd-response-form-container').replaceWith(responseHtml);
                            showDashboardNotice(res.data.message, 'success');
                        } else {
                            showDashboardNotice(res.data.message || 'Ein Fehler ist aufgetreten.', 'error');
                            $btn.prop('disabled', false);
                            $btn.find('.sd-btn-text').show();
                            $btn.find('.sd-btn-loading').hide();
                        }
                    },
                    error: function() {
                        showDashboardNotice('Ein Fehler ist aufgetreten.', 'error');
                        $btn.prop('disabled', false);
                        $btn.find('.sd-btn-text').show();
                        $btn.find('.sd-btn-loading').hide();
                    }
                });
            });
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * Lightbox Module for Gallery
     */
    const SDLightbox = {
        currentIndex: 0,
        images: [],
        $overlay: null,

        init: function() {
            const self = this;

            // Only init if gallery items exist
            if (!$('.sd-gallery-item').length) return;

            // Create lightbox overlay
            this.createOverlay();

            // Bind click events to gallery items
            $(document).on('click', '.sd-gallery-item[data-lightbox]', function(e) {
                e.preventDefault();
                self.collectImages();
                const index = $('.sd-gallery-item[data-lightbox]').index(this);
                self.open(index);
            });

            // Close on backdrop click
            this.$overlay.on('click', function(e) {
                if ($(e.target).hasClass('sd-lightbox-overlay') || $(e.target).hasClass('sd-lightbox-close')) {
                    self.close();
                }
            });

            // Navigation
            this.$overlay.find('.sd-lightbox-prev').on('click', function() {
                self.prev();
            });

            this.$overlay.find('.sd-lightbox-next').on('click', function() {
                self.next();
            });

            // Keyboard navigation
            $(document).on('keydown', function(e) {
                if (!self.$overlay.hasClass('active')) return;

                if (e.key === 'Escape') self.close();
                if (e.key === 'ArrowLeft') self.prev();
                if (e.key === 'ArrowRight') self.next();
            });
        },

        createOverlay: function() {
            const html = `
                <div class="sd-lightbox-overlay">
                    <div class="sd-lightbox-content">
                        <button type="button" class="sd-lightbox-close">&times;</button>
                        <img src="" alt="">
                        <button type="button" class="sd-lightbox-nav sd-lightbox-prev">&#10094;</button>
                        <button type="button" class="sd-lightbox-nav sd-lightbox-next">&#10095;</button>
                        <div class="sd-lightbox-counter"></div>
                    </div>
                </div>
            `;
            $('body').append(html);
            this.$overlay = $('.sd-lightbox-overlay');
        },

        collectImages: function() {
            this.images = [];
            $('.sd-gallery-item[data-lightbox]').each((i, el) => {
                this.images.push({
                    src: $(el).attr('href'),
                    alt: $(el).data('alt') || ''
                });
            });
        },

        open: function(index) {
            this.currentIndex = index;
            this.showImage();
            this.$overlay.addClass('active');
            $('body').css('overflow', 'hidden');
        },

        close: function() {
            this.$overlay.removeClass('active');
            $('body').css('overflow', '');
        },

        showImage: function() {
            const img = this.images[this.currentIndex];
            this.$overlay.find('img').attr('src', img.src).attr('alt', img.alt);
            this.$overlay.find('.sd-lightbox-counter').text((this.currentIndex + 1) + ' / ' + this.images.length);

            // Hide/show nav buttons
            this.$overlay.find('.sd-lightbox-prev').toggle(this.images.length > 1);
            this.$overlay.find('.sd-lightbox-next').toggle(this.images.length > 1);
        },

        prev: function() {
            this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
            this.showImage();
        },

        next: function() {
            this.currentIndex = (this.currentIndex + 1) % this.images.length;
            this.showImage();
        }
    };

    /**
     * Gallery Preview for Forms
     * Supports cumulative file selection and individual file removal
     */
    var SDGalleryPreview = {
        pendingFiles: [],  // Stores selected files cumulatively
        maxFiles: 10,

        init: function() {
            const self = this;

            // Submission form gallery preview
            $('#sd_gallery').on('change', function() {
                self.handleFileSelect(this, '#sd-gallery-preview');
            });

            // Edit form gallery preview (uses different logic - immediate upload)
            $('#sd-edit-gallery').on('change', function() {
                self.handleEditFileSelect(this, '#sd-edit-gallery-preview');
            });

            // Remove button click handler
            $(document).on('click', '.sd-gallery-preview-remove', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const index = parseInt($(this).data('index'), 10);
                self.removeFile(index);
            });
        },

        handleFileSelect: function(input, previewSelector) {
            const self = this;
            const files = input.files;

            if (!files || files.length === 0) return;

            // Add new files to pending array (cumulative)
            Array.from(files).forEach(file => {
                if (!file.type.startsWith('image/')) return;

                // Check max limit
                if (self.pendingFiles.length >= self.maxFiles) {
                    return;
                }

                self.pendingFiles.push(file);
            });

            // Check if over limit after adding
            if (self.pendingFiles.length > self.maxFiles) {
                alert('Maximal ' + self.maxFiles + ' Bilder erlaubt. Überzählige Bilder wurden nicht hinzugefügt.');
                self.pendingFiles = self.pendingFiles.slice(0, self.maxFiles);
            }

            // Clear the input to allow re-selecting same files
            $(input).val('');

            // Render preview
            this.renderPreview(previewSelector);

            // Trigger premium fields check
            if (typeof SDPremiumFormMonitor !== 'undefined') {
                SDPremiumFormMonitor.checkPremiumFields();
            }
        },

        renderPreview: function(previewSelector) {
            const self = this;
            const $preview = $(previewSelector);

            if (!$preview.length) return;

            $preview.empty();

            self.pendingFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const $item = $(`
                        <div class="sd-gallery-preview-item" data-index="${index}">
                            <img src="${e.target.result}" alt="">
                            <button type="button" class="sd-gallery-preview-remove" data-index="${index}" title="Entfernen">&times;</button>
                        </div>
                    `);
                    $preview.append($item);
                };
                reader.readAsDataURL(file);
            });
        },

        removeFile: function(index) {
            // Remove file from pending array
            this.pendingFiles.splice(index, 1);

            // Re-render preview
            this.renderPreview('#sd-gallery-preview');

            // Trigger premium fields check
            if (typeof SDPremiumFormMonitor !== 'undefined') {
                SDPremiumFormMonitor.checkPremiumFields();
            }
        },

        // Get pending files for form submission
        getPendingFiles: function() {
            return this.pendingFiles;
        },

        // Check if there are pending files (for premium field detection)
        hasPendingFiles: function() {
            return this.pendingFiles.length > 0;
        },

        // Clear pending files (e.g., after successful submission)
        clearPendingFiles: function() {
            this.pendingFiles = [];
        },

        // Edit form uses different logic (files uploaded immediately)
        handleEditFileSelect: function(input, previewSelector) {
            const $preview = $(previewSelector);
            const files = input.files;

            if (!$preview.length || !files || files.length === 0) return;

            // For edit form, just show preview of new files to be uploaded
            $preview.empty();

            Array.from(files).forEach((file, index) => {
                if (!file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    const $item = $(`
                        <div class="sd-gallery-preview-item" data-index="${index}">
                            <img src="${e.target.result}" alt="">
                        </div>
                    `);
                    $preview.append($item);
                };
                reader.readAsDataURL(file);
            });
        }
    };

    /**
     * Video Preview Handler
     * Handles video file preview with size validation
     */
    const SDVideoPreview = {
        maxFileSize: 10 * 1024 * 1024, // 10MB in bytes
        pendingFile: null,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Video upload preview
            $('#sd_video').on('change', function() {
                self.handleVideoSelect(this);
            });
        },

        handleVideoSelect: function(input) {
            const self = this;
            const $preview = $('#sd-video-preview');
            const file = input.files && input.files[0];

            // Clear existing preview using safe DOM method
            $preview.empty();

            if (!file) {
                self.pendingFile = null;
                return;
            }

            // Check file size (10MB max)
            if (file.size > self.maxFileSize) {
                alert('Das Video darf maximal 10 MB groß sein.');
                $(input).val('');
                self.pendingFile = null;
                return;
            }

            // Check file type
            const allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime'];
            if (!allowedTypes.includes(file.type)) {
                alert('Nur MP4, WebM oder MOV Videos sind erlaubt.');
                $(input).val('');
                self.pendingFile = null;
                return;
            }

            // Store pending file
            self.pendingFile = file;

            // Create video preview using safe DOM methods
            const video = document.createElement('video');
            video.controls = true;
            video.src = URL.createObjectURL(file);
            $preview.append(video);

            // Trigger premium fields check
            if (typeof SDPremiumFormMonitor !== 'undefined') {
                SDPremiumFormMonitor.checkPremiumFields();
            }
        },

        // Check if there is a pending video (for premium field detection)
        hasPendingVideo: function() {
            return this.pendingFile !== null;
        },

        // Clear pending video (e.g., after successful submission)
        clearPendingVideo: function() {
            this.pendingFile = null;
            $('#sd-video-preview').empty();
        }
    };

    /**
     * Quote Request Modal
     * Handles quote/inquiry requests from potential customers
     */
    const SDQuoteModal = {
        $modal: null,
        $form: null,
        currentListingId: null,

        init: function() {
            this.$modal = $('#sd-quote-modal');
            this.$form = $('#sd-quote-form');

            if (!this.$modal.length) return;

            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Open modal on quote button click
            $(document).on('click', '.sd-quote-request-btn', function(e) {
                e.preventDefault();
                const postId = $(this).data('post-id');
                self.openModal(postId);
            });

            // Close modal handlers
            this.$modal.on('click', '.sd-modal-close, .sd-modal-cancel, .sd-modal-close-btn', function() {
                self.closeModal();
            });

            this.$modal.on('click', '.sd-modal-backdrop', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });

            // Form submission
            this.$form.on('submit', function(e) {
                e.preventDefault();
                self.submitForm();
            });

            // ESC key to close
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.$modal.is(':visible')) {
                    self.closeModal();
                }
            });
        },

        openModal: function(postId) {
            this.currentListingId = postId;
            this.$form.show();
            $('#sd-quote-success').hide();
            this.$form[0].reset();
            this.$modal.fadeIn(200);
            $('#sd-quote-name').focus();
        },

        closeModal: function() {
            this.$modal.fadeOut(200);
            this.currentListingId = null;
        },

        submitForm: function() {
            const self = this;
            const $submitBtn = $('#sd-quote-submit');
            const $btnText = $submitBtn.find('.sd-btn-text');
            const $btnLoading = $submitBtn.find('.sd-btn-loading');

            // Show loading state
            $submitBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();

            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_submit_quote_request',
                    nonce: sdAjax.quoteNonce,
                    listing_id: this.$form.find('[name="listing_id"]').val(),
                    name: this.$form.find('[name="name"]').val(),
                    email: this.$form.find('[name="email"]').val(),
                    phone: this.$form.find('[name="phone"]').val(),
                    service: this.$form.find('[name="service"]').val(),
                    message: this.$form.find('[name="message"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        self.$form.hide();
                        $('#sd-quote-success').fadeIn(200);
                    } else {
                        alert(response.data.message || 'Ein Fehler ist aufgetreten.');
                    }
                },
                error: function() {
                    alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoading.hide();
                }
            });
        }
    };

    /**
     * SDChipsScroll - Mobile horizontal scroll for category chips
     * Transform-basiertes Scrolling mit Flexbox-Layout
     */
    var SDChipsScroll = {
        scrollPosition: 0,
        maxScroll: 0,
        scrollStep: 150,

        init: function() {
            var self = this;
            this.$container = $('.sd-chips-scroll-container');

            if (!this.$container.length) return;

            this.$viewport = this.$container.find('.sd-chips-viewport');
            this.$chips = this.$container.find('.sd-category-chips');
            this.$leftChevron = this.$container.find('.sd-chips-chevron--left');
            this.$rightChevron = this.$container.find('.sd-chips-chevron--right');

            if (!this.$chips.length || !this.$viewport.length) return;

            // Click-Handler für Chevrons
            this.$leftChevron.on('click', function(e) {
                e.preventDefault();
                self.scrollBy(-self.scrollStep);
            });

            this.$rightChevron.on('click', function(e) {
                e.preventDefault();
                self.scrollBy(self.scrollStep);
            });

            // Initial berechnen
            setTimeout(function() {
                self.calculateMaxScroll();
                self.updateChevronState();
            }, 100);

            // Bei Resize neu berechnen
            $(window).on('resize', function() {
                self.scrollPosition = 0;
                self.$chips.css('transform', 'translateX(0)');
                self.calculateMaxScroll();
                self.updateChevronState();
            });
        },

        calculateMaxScroll: function() {
            var viewportWidth = this.$viewport.width();
            var chipsWidth = this.$chips[0].scrollWidth;
            this.maxScroll = Math.max(0, chipsWidth - viewportWidth);
        },

        scrollBy: function(delta) {
            this.scrollPosition += delta;

            // Begrenzen auf 0 bis maxScroll
            if (this.scrollPosition < 0) {
                this.scrollPosition = 0;
            }
            if (this.scrollPosition > this.maxScroll) {
                this.scrollPosition = this.maxScroll;
            }

            // Transform anwenden
            this.$chips.css('transform', 'translateX(-' + this.scrollPosition + 'px)');

            this.updateChevronState();
        },

        updateChevronState: function() {
            // Keine Scrolling nötig
            if (this.maxScroll <= 0) {
                this.$leftChevron.addClass('hidden');
                this.$rightChevron.addClass('hidden');
                return;
            }

            // Links: versteckt wenn am Anfang
            if (this.scrollPosition <= 0) {
                this.$leftChevron.addClass('hidden');
            } else {
                this.$leftChevron.removeClass('hidden');
            }

            // Rechts: versteckt wenn am Ende
            if (this.scrollPosition >= this.maxScroll - 5) {
                this.$rightChevron.addClass('hidden');
            } else {
                this.$rightChevron.removeClass('hidden');
            }
        }
    };

    /**
     * SDKartensuche - Full-Screen Map Search Experience
     * Dedicated map-first search page functionality for /kartensuche/
     */
    const SDKartensuche = {
        map: null,
        markers: [],
        markerLayer: null,
        isListVisible: false,
        debounceTimer: null,
        isLoading: false,
        defaultCenter: [50.1109, 8.6821], // Frankfurt am Main (Innenstadt) as fallback
        defaultZoom: 10, // ~50km radius
        isInitialLoad: true, // Flag to prevent fitBounds on initial load
        userLocationMarker: null, // Blue dot for user's location
        mapMoveTimer: null, // Timer for debouncing map movement
        isLocationSearch: false, // Track if current search is location-based
        locationSearchTerm: null, // Store the location term for display
        isGlobalSearchResult: false, // Prevent moveend handler during global search result display
        hasActiveGlobalSearch: false, // Track if current view shows global search results (persists until search is cleared)

        init: function() {
            const $mapContainer = $('#sd-kartensuche-map');
            if (!$mapContainer.length) return;

            // Check if Leaflet is loaded
            if (typeof L === 'undefined') {
                console.error('Leaflet not loaded for Kartensuche');
                return;
            }

            const self = this;

            // Check if geolocation is available and we're in a secure context
            const canUseGeolocation = navigator.geolocation &&
                (window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost');

            if (canUseGeolocation) {
                // Use Permissions API to check status first (if available)
                if (navigator.permissions && navigator.permissions.query) {
                    navigator.permissions.query({ name: 'geolocation' }).then(function(result) {
                        console.log('Geolocation permission status:', result.state);

                        if (result.state === 'granted') {
                            // Permission already granted, get location immediately
                            self.requestGeolocation();
                        } else if (result.state === 'prompt') {
                            // Permission not yet requested, request it
                            // Small delay to ensure page is fully loaded (helps on iOS)
                            setTimeout(function() {
                                self.requestGeolocation();
                            }, 100);
                        } else {
                            // Permission denied, use default
                            console.log('Geolocation permission denied, using Frankfurt as default');
                            self.createMap();
                            self.bindEvents();
                            self.loadInitialListings();
                        }
                    }).catch(function(err) {
                        // Permissions API failed (e.g., Safari), try geolocation directly
                        console.log('Permissions API not available, trying geolocation directly');
                        setTimeout(function() {
                            self.requestGeolocation();
                        }, 100);
                    });
                } else {
                    // No Permissions API (Safari/iOS), try geolocation directly with delay
                    console.log('No Permissions API, trying geolocation directly');
                    setTimeout(function() {
                        self.requestGeolocation();
                    }, 100);
                }
            } else {
                // Geolocation not available or not secure context
                console.log('Geolocation not available (secure context:', window.isSecureContext, ')');
                this.createMap();
                this.bindEvents();
                this.loadInitialListings();
            }
        },

        requestGeolocation: function() {
            const self = this;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // Success: Use user's location
                    console.log('Geolocation success:', position.coords.latitude, position.coords.longitude);
                    self.defaultCenter = [position.coords.latitude, position.coords.longitude];
                    self.createMap();
                    self.addUserLocationMarker(position.coords.latitude, position.coords.longitude);
                    self.bindEvents();
                    self.loadInitialListings();
                },
                function(error) {
                    // Error or denied: Use Frankfurt am Main
                    console.log('Geolocation error:', error.code, error.message);
                    self.createMap();
                    self.bindEvents();
                    self.loadInitialListings();
                },
                {
                    enableHighAccuracy: false,
                    timeout: 10000, // Increased timeout for mobile
                    maximumAge: 300000 // 5 minutes cache
                }
            );
        },

        addUserLocationMarker: function(lat, lng) {
            // Remove existing marker if any
            if (this.userLocationMarker) {
                this.map.removeLayer(this.userLocationMarker);
                this.userLocationMarker = null;
            }

            // Create a blue pulsing dot for user's location
            const userIcon = L.divIcon({
                className: 'sd-user-marker-wrapper',
                html: '<div class="sd-user-location-marker"><div class="sd-user-location-pulse"></div></div>',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });

            this.userLocationMarker = L.marker([lat, lng], {
                icon: userIcon,
                zIndexOffset: 1000 // Above other markers
            }).addTo(this.map);

            this.userLocationMarker.bindPopup('Dein Standort', {
                className: 'sd-user-location-popup'
            });
        },

        createMap: function() {
            const self = this;

            // Create full-screen map
            this.map = L.map('sd-kartensuche-map', {
                center: this.defaultCenter,
                zoom: this.defaultZoom,
                zoomControl: false, // Use custom controls
                scrollWheelZoom: false, // Disable scroll zoom
                attributionControl: true
            });

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19
            }).addTo(this.map);

            // Create marker layer group
            this.markerLayer = L.layerGroup().addTo(this.map);

            // Handle window resize
            $(window).on('resize', function() {
                if (self.map) {
                    self.map.invalidateSize();
                }
            });
        },

        bindEvents: function() {
            const self = this;

            // Search input with debounce - global search for listings, then geocoding as fallback
            $('#sd-kartensuche-search').on('input', function() {
                clearTimeout(self.debounceTimer);

                const searchTerm = $(this).val().trim();
                const $clearBtn = $('.sd-kartensuche-clear');

                // Show/hide clear button based on input content
                if (searchTerm.length > 0) {
                    $clearBtn.addClass('visible');
                } else {
                    $clearBtn.removeClass('visible');
                }

                self.debounceTimer = setTimeout(function() {
                    if (searchTerm.length >= 2) {
                        // Show loading indicator
                        self.showLoading();

                        // First, try global search (searches all listings by name, ignoring map bounds)
                        self.searchGlobally(searchTerm).then(function(results) {
                            if (results) {
                                // Found listings matching the search term - fit map to show them
                                self.hideLoading();
                                self.fitMapToResults(results);
                            } else {
                                // No listings found - try geocoding as fallback (for location names)
                                self.geocodeLocation(searchTerm).then(function(location) {
                                    self.hideLoading();
                                    if (location) {
                                        // It's a location - center map on it
                                        self.centerOnLocation(location);
                                    } else {
                                        // Neither listing nor location found
                                        self.showNoResultsFeedback();
                                    }
                                });
                            }
                        });
                    } else if (searchTerm.length === 0) {
                        // Empty search - reset and fetch all in current view
                        self.isLocationSearch = false;
                        self.locationSearchTerm = null;
                        self.isGlobalSearchResult = false;
                        self.hasActiveGlobalSearch = false; // Allow moveend to work again
                        self.fetchListings();
                    }
                }, 400);
            });

            // Clear button click handler
            $('.sd-kartensuche-clear').on('click', function() {
                $('#sd-kartensuche-search').val('').trigger('input').focus();
            });

            // Initial visibility of clear button on page load
            if ($('#sd-kartensuche-search').val().trim().length > 0) {
                $('.sd-kartensuche-clear').addClass('visible');
            }

            // Category dropdown
            $('#sd-kartensuche-category').on('change', function() {
                self.fetchListings();
            });

            // Premium toggle
            $('.sd-kartensuche-premium-toggle input').on('change', function() {
                self.fetchListings();
            });

            // Form submit
            $('#sd-kartensuche-form').on('submit', function(e) {
                e.preventDefault();
                self.fetchListings();
            });

            // Custom zoom controls
            $('#sd-karte-zoom-in').on('click', function() {
                self.map.zoomIn();
            });

            $('#sd-karte-zoom-out').on('click', function() {
                self.map.zoomOut();
            });

            $('#sd-karte-fit-all').on('click', function() {
                self.fitAllMarkers();
            });

            // Geolocation button
            $('#sd-karte-location').on('click', function() {
                self.goToUserLocation();
            });

            // List toggle
            $('#sd-list-toggle').on('click', function() {
                self.toggleListPanel();
            });

            // Back to map
            $('#sd-back-to-map').on('click', function() {
                self.hideListPanel();
            });

            // Drag handle - swipe down to close list panel (mobile/tablet only)
            this.initDragHandle();

            // Card hover -> highlight marker
            $(document).on('mouseenter', '#sd-list-content .sd-listing-card[data-lat]', function() {
                const postId = $(this).data('post-id');
                self.highlightMarker(postId);
            });

            $(document).on('mouseleave', '#sd-list-content .sd-listing-card[data-lat]', function() {
                const postId = $(this).data('post-id');
                self.unhighlightMarker(postId);
            });

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                if (!$('#sd-kartensuche-map').length) return;

                // Escape to close list panel
                if (e.key === 'Escape' && self.isListVisible) {
                    self.hideListPanel();
                }
            });

            // Map move/zoom events - update markers based on visible bounds
            this.map.on('moveend', function() {
                // Don't fetch if showing global search results (handled separately)
                if (self.isGlobalSearchResult) {
                    return;
                }
                // Don't fetch if we have active global search results (prevents overriding name search results)
                if (self.hasActiveGlobalSearch) {
                    return;
                }
                // Don't fetch if a popup is currently open (prevents popup closing on marker click)
                if (self.map._popup && self.map._popup.isOpen()) {
                    return;
                }
                // Debounce map movement to avoid too many requests
                clearTimeout(self.mapMoveTimer);
                self.mapMoveTimer = setTimeout(function() {
                    // Double-check states after debounce
                    if (self.isGlobalSearchResult || self.hasActiveGlobalSearch) {
                        return;
                    }
                    if (self.map._popup && self.map._popup.isOpen()) {
                        return;
                    }
                    self.fetchListings();
                }, 300);
            });
        },

        /**
         * Geocode a search term using Nominatim API
         * @param {string} term - The search term to geocode
         * @returns {Promise<object|null>} - Location data or null
         */
        geocodeLocation: function(term) {
            return new Promise(function(resolve, reject) {
                if (!term || term.length < 2) {
                    resolve(null);
                    return;
                }

                const encodedTerm = encodeURIComponent(term);
                const url = 'https://nominatim.openstreetmap.org/search?' +
                    'q=' + encodedTerm + ',Deutschland' +
                    '&format=json' +
                    '&countrycodes=de' +
                    '&limit=1' +
                    '&addressdetails=1';

                fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data && data.length > 0) {
                        const result = data[0];

                        // Check if this is a valid location type
                        const validClasses = ['place', 'boundary'];
                        const validTypes = [
                            'city', 'town', 'village', 'hamlet',
                            'municipality', 'administrative',
                            'suburb', 'neighbourhood', 'quarter',
                            'county', 'state', 'region', 'district'
                        ];

                        const isValidLocation = validClasses.includes(result.class) &&
                            (validTypes.includes(result.type) || result.type === 'administrative');

                        if (isValidLocation) {
                            resolve({
                                lat: parseFloat(result.lat),
                                lng: parseFloat(result.lon),
                                displayName: result.display_name,
                                shortName: result.address?.city ||
                                           result.address?.town ||
                                           result.address?.village ||
                                           result.address?.municipality ||
                                           result.address?.county ||
                                           result.address?.state ||
                                           term,
                                type: result.type,
                                boundingbox: result.boundingbox
                            });
                        } else {
                            resolve(null); // Not a location, treat as product search
                        }
                    } else {
                        resolve(null);
                    }
                })
                .catch(function(error) {
                    console.warn('Geocoding error:', error);
                    resolve(null); // Fail gracefully
                });
            });
        },

        /**
         * Center map on a location with appropriate zoom
         * @param {object} location - Location data from geocodeLocation
         */
        centerOnLocation: function(location) {
            const self = this;

            // Determine zoom level based on location type
            let zoom = 10; // Default ~50km radius

            if (location.type === 'state') {
                zoom = 7; // Bundesland level
            } else if (location.type === 'county' || location.type === 'district') {
                zoom = 9; // Landkreis level
            } else if (location.type === 'suburb' || location.type === 'neighbourhood' || location.type === 'quarter') {
                zoom = 13; // Neighborhood level
            }

            // Use flyTo for smooth animation
            this.map.flyTo([location.lat, location.lng], zoom, {
                duration: 0.8
            });

            // Track that this is a location search
            this.isLocationSearch = true;
            this.locationSearchTerm = location.shortName;

            // Show visual feedback
            this.showLocationFeedback(location.shortName);

            // After map animation completes, fetch listings will happen via moveend event
        },

        /**
         * Show brief notification that map centered on location
         * @param {string} locationName - The location name to display
         */
        showLocationFeedback: function(locationName) {
            // Remove any existing feedback
            $('.sd-location-feedback').remove();

            const self = this;
            const escapedName = $('<div>').text(locationName).html(); // Escape HTML

            const $feedback = $('<div class="sd-location-feedback">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none">' +
                '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="currentColor"/>' +
                '</svg>' +
                '<span>Kartenansicht: <strong>' + escapedName + '</strong></span>' +
                '</div>');

            $('.sd-kartensuche-wrapper').append($feedback);

            // Animate in
            setTimeout(function() {
                $feedback.addClass('active');
            }, 10);

            // Remove after 3 seconds
            setTimeout(function() {
                $feedback.removeClass('active');
                setTimeout(function() {
                    $feedback.remove();
                }, 300);
            }, 3000);
        },

        getFilters: function() {
            const filters = {
                // If this is a location search, don't pass the location name as search term
                search: this.isLocationSearch ? '' : ($('#sd-kartensuche-search').val() || ''),
                category: $('#sd-kartensuche-category').val() || '',
                premium: $('.sd-kartensuche-premium-toggle input').is(':checked') ? '1' : '',
                orderby: 'date_desc',
                per_page: 200 // Load more for map view
            };

            // Use frozen bounds if list is visible (prevents count change when map resizes)
            // Otherwise use current map bounds
            if (this.frozenBounds && this.isListVisible) {
                filters.bounds_north = this.frozenBounds.getNorth();
                filters.bounds_south = this.frozenBounds.getSouth();
                filters.bounds_east = this.frozenBounds.getEast();
                filters.bounds_west = this.frozenBounds.getWest();
            } else if (this.map) {
                const bounds = this.map.getBounds();
                filters.bounds_north = bounds.getNorth();
                filters.bounds_south = bounds.getSouth();
                filters.bounds_east = bounds.getEast();
                filters.bounds_west = bounds.getWest();
            }

            return filters;
        },

        fetchListings: function() {
            if (this.isLoading) return;

            const self = this;
            const filters = this.getFilters();

            this.showLoading();
            this.updateURL(filters);

            $.ajax({
                url: sdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sd_filter_listings',
                    nonce: sdAjax.filterNonce,
                    search: filters.search,
                    category: filters.category,
                    premium: filters.premium,
                    orderby: filters.orderby,
                    per_page: filters.per_page,
                    paged: 1,
                    bounds_north: filters.bounds_north,
                    bounds_south: filters.bounds_south,
                    bounds_east: filters.bounds_east,
                    bounds_west: filters.bounds_west
                },
                success: function(response) {
                    if (response.success) {
                        self.updateMarkers(response.data.html);
                        self.updateListContent(response.data.html);
                        self.updateCounts(response.data.found_posts);
                    }
                    self.hideLoading();
                },
                error: function() {
                    self.hideLoading();
                    console.error('Kartensuche: AJAX error');
                }
            });
        },

        loadInitialListings: function() {
            this.fetchListings();
        },

        updateMarkers: function(html) {
            const self = this;
            this.clearMarkers();

            if (!html || html.trim() === '') {
                return;
            }

            // Parse HTML to extract lat/lng from cards
            const $tempContainer = $('<div>').html(html);
            const bounds = [];

            $tempContainer.find('.sd-listing-card[data-lat][data-lng]').each(function() {
                const $card = $(this);
                const lat = parseFloat($card.data('lat'));
                const lng = parseFloat($card.data('lng'));
                const postId = $card.data('post-id');

                if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                    const isPremium = $card.hasClass('sd-listing-premium');
                    const title = $card.find('.sd-listing-title a').text().trim();
                    const category = $card.find('.sd-listing-meta-category').text().trim() ||
                                    $card.find('.sd-category-badge').first().text().trim();
                    const city = $card.find('.sd-listing-meta-location').text().trim();
                    const permalink = $card.find('.sd-listing-title a').attr('href');
                    const image = $card.find('.sd-listing-thumbnail').attr('src');

                    // Create marker with custom icon
                    const icon = self.createMarkerIcon(isPremium);
                    const marker = L.marker([lat, lng], { icon: icon })
                        .bindPopup(self.createPopupContent(title, category, city, permalink, image, isPremium), {
                            maxWidth: 280,
                            className: 'sd-kartensuche-popup'
                        })
                        .on('mouseover', function() {
                            // Only highlight list card on hover, don't open popup
                            self.highlightListCard(postId);
                        })
                        .on('mouseout', function() {
                            self.unhighlightListCard(postId);
                        })
                        .on('click', function(e) {
                            // When list panel is open, center map on clicked marker
                            if (self.isListVisible) {
                                const markerLatLng = this.getLatLng();
                                self.map.setView(markerLatLng, self.map.getZoom(), { animate: true });

                                // On mobile (≤768px), disable popup when list is open
                                if (window.innerWidth <= 768) {
                                    e.originalEvent.preventDefault();
                                    e.originalEvent.stopPropagation();
                                    this.closePopup();
                                    return false;
                                }
                            }
                        });

                    marker.postId = postId;
                    self.markers.push(marker);
                    self.markerLayer.addLayer(marker);
                }
            });

            // Note: We don't fitBounds anymore - markers are loaded based on current map view
            // The user controls the map position, markers update dynamically
        },

        createMarkerIcon: function(isPremium) {
            const markerHtml = `
                <div class="sd-map-marker ${isPremium ? 'premium' : ''}">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="currentColor"/>
                    </svg>
                </div>
            `;

            return L.divIcon({
                html: markerHtml,
                className: 'sd-marker-wrapper',
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -32]
            });
        },

        createPopupContent: function(title, category, city, permalink, image, isPremium) {
            let html = '<div class="sd-map-popup">';

            if (image) {
                html += `<img src="${image}" alt="${this.escapeHtml(title)}" class="sd-map-popup-image" loading="lazy" />`;
            }

            if (isPremium) {
                html += '<span class="sd-map-popup-premium">Premium</span>';
            }

            html += `<h4 class="sd-map-popup-title"><a href="${permalink}">${this.escapeHtml(title)}</a></h4>`;

            if (category || city) {
                html += '<div class="sd-map-popup-meta">';
                if (category) html += `<span class="sd-map-popup-category">${this.escapeHtml(category)}</span>`;
                if (category && city) html += '<span class="sd-map-popup-sep">·</span>';
                if (city) {
                    html += `
                        <span class="sd-map-popup-location">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="currentColor"/>
                            </svg>
                            ${this.escapeHtml(city)}
                        </span>
                    `;
                }
                html += '</div>';
            }

            html += `
                <a href="${permalink}" class="sd-map-popup-link">
                    Details ansehen
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                        <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" fill="currentColor"/>
                    </svg>
                </a>
            `;

            html += '</div>';
            return html;
        },

        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        clearMarkers: function() {
            if (this.markerLayer) {
                this.markerLayer.clearLayers();
            }
            this.markers = [];
        },

        fitAllMarkers: function() {
            if (this.markers.length === 0) return;

            const bounds = this.markers.map(m => m.getLatLng());
            if (bounds.length === 1) {
                this.map.setView(bounds[0], 14);
            } else {
                this.map.fitBounds(bounds, { padding: [50, 50] });
            }
        },

        goToUserLocation: function() {
            const self = this;
            const $btn = $('#sd-karte-location');

            // Check if geolocation is supported
            if (!navigator.geolocation) {
                alert('Standortbestimmung wird von Ihrem Browser nicht unterstützt.');
                return;
            }

            // Visual feedback - show loading state
            $btn.addClass('loading');
            $btn.prop('disabled', true);

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // Success
                    $btn.removeClass('loading');
                    $btn.prop('disabled', false);

                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    // Smooth fly to location
                    self.map.flyTo([lat, lng], 12, { duration: 0.8 });

                    // Remove any existing user location markers first
                    if (self.userLocationMarker) {
                        self.map.removeLayer(self.userLocationMarker);
                        self.userLocationMarker = null;
                    }

                    // Create user location marker
                    const userIcon = L.divIcon({
                        html: '<div class="sd-user-location-marker"><div class="sd-user-location-pulse"></div></div>',
                        className: 'sd-user-marker-wrapper',
                        iconSize: [40, 40],
                        iconAnchor: [20, 20]
                    });

                    self.userLocationMarker = L.marker([lat, lng], {
                        icon: userIcon,
                        zIndexOffset: 1000
                    })
                        .addTo(self.map)
                        .bindPopup('Dein Standort', {
                            className: 'sd-user-location-popup'
                        })
                        .openPopup();
                },
                function(error) {
                    // Error handling with user feedback
                    $btn.removeClass('loading');
                    $btn.prop('disabled', false);

                    let errorMsg = 'Standort konnte nicht ermittelt werden.';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = 'Bitte erlauben Sie den Zugriff auf Ihren Standort in den Browser-Einstellungen.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = 'Standortinformationen sind nicht verfügbar.';
                            break;
                        case error.TIMEOUT:
                            errorMsg = 'Die Standortabfrage hat zu lange gedauert. Bitte versuchen Sie es erneut.';
                            break;
                    }
                    alert(errorMsg);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0  // Always request fresh position
                }
            );
        },

        updateListContent: function(html) {
            const $content = $('#sd-list-content');
            if (html && html.trim() !== '') {
                $content.html(html);
            } else {
                $content.html(`
                    <div class="sd-kartensuche-no-results">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" fill="currentColor"/>
                        </svg>
                        <h3>Keine Hofläden gefunden</h3>
                        <p>Versuche es mit anderen Suchbegriffen oder wähle ein anderes Bundesland.</p>
                    </div>
                `);
            }
        },

        updateCounts: function(count) {
            const text = count || 0;
            $('#sd-kartensuche-count').text(text);
            $('#sd-list-count').text(text);
            $('#sd-toggle-count').text(text);
        },

        toggleListPanel: function() {
            if (this.isListVisible) {
                this.hideListPanel();
            } else {
                this.showListPanel();
            }
        },

        // Initialize drag handle for swipe-to-close on mobile/tablet
        initDragHandle: function() {
            const self = this;
            const $dragHandle = $('.sd-list-drag-handle');
            const $panel = $('#sd-list-panel');

            if (!$dragHandle.length) return;

            let startY = 0;
            let currentY = 0;
            let isDragging = false;
            const threshold = 80; // Minimum drag distance to trigger close

            // Touch events for mobile - passive: false to allow preventDefault/stopPropagation
            $dragHandle[0].addEventListener('touchstart', function(e) {
                if (window.innerWidth > 1024) return; // Only on tablet/mobile
                e.stopPropagation(); // Prevent event from reaching map
                isDragging = true;
                startY = e.touches[0].clientY;
                $panel.css('transition', 'none');
                // Visual feedback - highlight both lines
                $dragHandle.addClass('dragging');
            }, { passive: false });

            $dragHandle[0].addEventListener('touchmove', function(e) {
                if (!isDragging || window.innerWidth > 1024) return;
                e.preventDefault(); // Prevent map panning
                e.stopPropagation();
                currentY = e.touches[0].clientY;
                const deltaY = currentY - startY;

                // Only allow dragging down (positive deltaY)
                if (deltaY > 0) {
                    $panel.css('transform', 'translateY(' + deltaY + 'px)');
                }
            }, { passive: false });

            $dragHandle[0].addEventListener('touchend', function(e) {
                if (!isDragging || window.innerWidth > 1024) return;
                e.stopPropagation();
                isDragging = false;
                const deltaY = currentY - startY;

                // Reset styles
                $panel.css({
                    'transition': '',
                    'transform': ''
                });
                $dragHandle.removeClass('dragging');

                // Close panel if dragged past threshold
                if (deltaY > threshold) {
                    self.hideListPanel();
                }

                startY = 0;
                currentY = 0;
            }, { passive: false });

            // Mouse events for desktop testing
            $dragHandle.on('mousedown', function(e) {
                if (window.innerWidth > 1024) return;
                isDragging = true;
                startY = e.clientY;
                $panel.css('transition', 'none');
                $dragHandle.addClass('dragging');
                e.preventDefault();
            });

            $(document).on('mousemove.draghandle', function(e) {
                if (!isDragging || window.innerWidth > 1024) return;
                currentY = e.clientY;
                const deltaY = currentY - startY;

                if (deltaY > 0) {
                    $panel.css('transform', 'translateY(' + deltaY + 'px)');
                }
            });

            $(document).on('mouseup.draghandle', function(e) {
                if (!isDragging || window.innerWidth > 1024) return;
                isDragging = false;
                const deltaY = currentY - startY;

                $panel.css({
                    'transition': '',
                    'transform': ''
                });
                $dragHandle.removeClass('dragging');

                if (deltaY > threshold) {
                    self.hideListPanel();
                }

                startY = 0;
                currentY = 0;
            });
        },

        showListPanel: function() {
            const self = this;
            const $panel = $('#sd-list-panel');
            const $mapContainer = $('#sd-kartensuche-map-container');
            const $wrapper = $('.sd-kartensuche-wrapper');
            const $toggle = $('#sd-list-toggle');

            // Freeze current bounds BEFORE opening the list panel
            // This prevents the marker count from changing when the map resizes
            if (this.map) {
                this.frozenBounds = this.map.getBounds();
            }

            $panel.addClass('active').attr('aria-hidden', 'false');
            $mapContainer.addClass('list-open');
            $wrapper.addClass('list-open');
            $toggle.find('.sd-toggle-text').text('Zur Karte');
            $toggle.addClass('active');
            this.isListVisible = true;

            // Invalidate map size after animation
            setTimeout(function() {
                if (self.map) {
                    self.map.invalidateSize();
                }
            }, 400);
        },

        hideListPanel: function() {
            const self = this;
            const $panel = $('#sd-list-panel');
            const $mapContainer = $('#sd-kartensuche-map-container');
            const $wrapper = $('.sd-kartensuche-wrapper');
            const $toggle = $('#sd-list-toggle');

            // Clear frozen bounds when closing the list
            this.frozenBounds = null;

            $panel.removeClass('active').attr('aria-hidden', 'true');
            $mapContainer.removeClass('list-open');
            $wrapper.removeClass('list-open');
            $toggle.find('.sd-toggle-text').text('Listenansicht');
            $toggle.removeClass('active');
            this.isListVisible = false;

            // Invalidate map size after animation
            setTimeout(function() {
                if (self.map) {
                    self.map.invalidateSize();
                }
            }, 400);

            // Scroll to top of page
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        highlightMarker: function(postId) {
            this.markers.forEach(function(marker) {
                if (marker.postId === postId) {
                    // Add highlighted class (red background, white icon)
                    $(marker._icon).find('.sd-map-marker').addClass('highlighted');
                    // Don't open popup on hover, don't pan map
                }
            });
        },

        unhighlightMarker: function(postId) {
            this.markers.forEach(function(marker) {
                if (marker.postId === postId) {
                    $(marker._icon).find('.sd-map-marker').removeClass('highlighted');
                    // Don't close popup on unhover
                }
            });
        },

        highlightListCard: function(postId) {
            $('#sd-list-content [data-post-id="' + postId + '"]').addClass('sd-map-highlight');
        },

        unhighlightListCard: function(postId) {
            $('#sd-list-content [data-post-id="' + postId + '"]').removeClass('sd-map-highlight');
        },

        updateURL: function(filters) {
            const url = new URL(window.location);

            // Update URL params
            if (filters.search) {
                url.searchParams.set('sd_search', filters.search);
            } else {
                url.searchParams.delete('sd_search');
            }

            if (filters.category) {
                url.searchParams.set('sd_category', filters.category);
            } else {
                url.searchParams.delete('sd_category');
            }

            if (filters.premium === '1') {
                url.searchParams.set('sd_premium', '1');
            } else {
                url.searchParams.delete('sd_premium');
            }

            window.history.replaceState({}, '', url);
        },

        showLoading: function() {
            $('#sd-kartensuche-loading').addClass('active');
            this.isLoading = true;
        },

        hideLoading: function() {
            $('#sd-kartensuche-loading').removeClass('active');
            this.isLoading = false;
        },

        // Global search without bounds filter - finds listings by name anywhere
        searchGlobally: function(term) {
            const self = this;
            return new Promise(function(resolve) {
                $.ajax({
                    url: sdAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sd_filter_listings',
                        nonce: sdAjax.filterNonce,
                        search: term,
                        category: $('#sd-kartensuche-category').val() || '',
                        premium: $('.sd-kartensuche-premium-toggle input').is(':checked') ? '1' : '',
                        global_search: '1',
                        per_page: 500
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            const coords = self.extractCoordinates(response.data.html);
                            resolve(coords.length > 0 ? {
                                coords: coords,
                                html: response.data.html,
                                count: response.data.found_posts
                            } : null);
                        } else {
                            resolve(null);
                        }
                    },
                    error: function() {
                        resolve(null);
                    }
                });
            });
        },

        // Extract coordinates from response HTML
        extractCoordinates: function(html) {
            const coords = [];
            const $temp = $('<div>').html(html);
            const $cards = $temp.find('.sd-listing-card');
            $cards.each(function() {
                const $card = $(this);
                const hasLat = $card.attr('data-lat');
                const hasLng = $card.attr('data-lng');
                if (hasLat && hasLng) {
                    const lat = parseFloat(hasLat);
                    const lng = parseFloat(hasLng);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        coords.push([lat, lng]);
                    }
                }
            });
            return coords;
        },

        // Fit map to show all search results
        fitMapToResults: function(results) {
            const self = this;

            // Temporarily disable moveend handler to prevent double fetch
            this.isGlobalSearchResult = true;
            // Mark that we have active global search results (prevents moveend from overriding)
            this.hasActiveGlobalSearch = true;

            // Update markers and list IMMEDIATELY - don't wait for moveend
            // This fixes the bug where flyTo might not trigger moveend if map doesn't need to move much
            this.updateMarkers(results.html);
            this.updateListContent(results.html);
            this.updateCounts(results.count);

            // Show feedback
            const countText = results.count === 1 ? '1 Hofladen gefunden' : results.count + ' Hofläden gefunden';
            this.showLocationFeedback(countText);

            if (results.coords.length === 1) {
                // Single result - zoom directly to it
                this.map.flyTo(results.coords[0], 14, { duration: 0.8 });
            } else {
                // Multiple results - fit all in view
                const bounds = L.latLngBounds(results.coords);
                this.map.flyToBounds(bounds, { padding: [50, 50], duration: 0.8 });
            }

            // Re-enable moveend handler after animation completes
            this.map.once('moveend', function() {
                // Re-enable moveend handler after a short delay
                setTimeout(function() {
                    self.isGlobalSearchResult = false;
                }, 100);
            });

            // Fallback: re-enable handler even if moveend doesn't fire (e.g., if map already at position)
            setTimeout(function() {
                self.isGlobalSearchResult = false;
            }, 1500);
        },

        // Show no results feedback
        showNoResultsFeedback: function() {
            this.showLocationFeedback('Keine Ergebnisse gefunden');
            this.clearMarkers();
            this.updateListContent('');
            this.updateCounts(0);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        initViewToggle();
        SDFilter.init();
        SDAutocomplete.init();
        SDMobileDrawer.init();
        SDChipsScroll.init();
        SDFavorites.init();
        SDServices.init();
        SDTags.init();
        SDReviewResponses.init();
        SDLightbox.init();
        SDGalleryPreview.init();
        SDVideoPreview.init();
        SDQuoteModal.init();
        SDKartensuche.init();

        // Render favorites page if on that page
        if ($('.sd-favorites-page').length) {
            SDFavorites.renderFavoritesPage();
        }
    });

})(jQuery);
