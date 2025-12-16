/**
 * Spezialist Ratings - Frontend JavaScript
 *
 * @package Spezialist_Ratings
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Ratings Module
     */
    const SRRatings = {

        /**
         * Rating text labels
         */
        ratingLabels: {
            1: 'Schlecht',
            2: 'Ausreichend',
            3: 'Befriedigend',
            4: 'Gut',
            5: 'Ausgezeichnet'
        },

        /**
         * Initialize
         */
        init: function() {
            this.bindStarInput();
            this.bindFormSubmit();
            this.handleTabRedirect();
            this.bindBadgeClicks();
        },

        /**
         * Handle tab redirect from login
         */
        handleTabRedirect: function() {
            // Check URL for ?tab=bewertungen parameter
            const params = new URLSearchParams(window.location.search);
            const tabParam = params.get('tab');

            if (tabParam === 'bewertungen') {
                // Wait for DOM to be ready and tab system to initialize
                setTimeout(function() {
                    const $tab = $('.sd-tab[data-tab="bewertungen"]');
                    if ($tab.length) {
                        $tab.trigger('click');

                        // Scroll to tab section after a short delay
                        setTimeout(function() {
                            const $tabContent = $('.sd-tab-content[data-tab="bewertungen"]');
                            if ($tabContent.length && $tabContent.is(':visible')) {
                                $('html, body').animate({
                                    scrollTop: $tabContent.offset().top - 100
                                }, 300);
                            }
                        }, 100);
                    }
                }, 100);
            }
        },

        /**
         * Bind star input interactions
         */
        bindStarInput: function() {
            const self = this;

            // Star click
            $(document).on('click', '.sr-star-btn', function(e) {
                e.preventDefault();

                const $btn = $(this);
                const value = parseInt($btn.data('value'), 10);
                const $container = $btn.closest('.sr-star-input');

                // Update visual state
                $container.find('.sr-star-btn').each(function() {
                    const btnValue = parseInt($(this).data('value'), 10);
                    if (btnValue <= value) {
                        $(this).addClass('active');
                    } else {
                        $(this).removeClass('active');
                    }
                });

                // Update hidden input
                $('#sr-rating-value').val(value);
                $container.attr('data-rating', value);

                // Update rating text
                const $ratingText = $container.siblings('.sr-rating-text');
                if ($ratingText.length) {
                    $ratingText.text(self.ratingLabels[value] || '');
                }
            });

            // Star hover
            $(document).on('mouseenter', '.sr-star-btn', function() {
                const $btn = $(this);
                const value = parseInt($btn.data('value'), 10);
                const $container = $btn.closest('.sr-star-input');
                const currentRating = parseInt($container.attr('data-rating'), 10) || 0;

                // Only show hover effect if no rating selected or hovering different
                $container.find('.sr-star-btn').each(function() {
                    const btnValue = parseInt($(this).data('value'), 10);
                    if (btnValue <= value) {
                        $(this).addClass('hover');
                    } else {
                        $(this).removeClass('hover');
                    }
                });
            });

            $(document).on('mouseleave', '.sr-star-input', function() {
                $(this).find('.sr-star-btn').removeClass('hover');
            });
        },

        /**
         * Bind form submission
         */
        bindFormSubmit: function() {
            const self = this;

            $(document).on('submit', '#sr-rating-form', function(e) {
                e.preventDefault();
                self.submitRating($(this));
            });
        },

        /**
         * Submit rating via AJAX
         *
         * @param {jQuery} $form
         */
        submitRating: function($form) {
            const self = this;
            const $submitBtn = $form.find('.sr-submit-btn');
            const $btnText = $submitBtn.find('.sr-btn-text');
            const $btnLoading = $submitBtn.find('.sr-btn-loading');

            // Get rating value
            const rating = $('#sr-rating-value').val();

            // Validate rating
            if (!rating || rating < 1 || rating > 5) {
                alert(srRatings.strings.selectRating);
                return;
            }

            // Create FormData for file upload support
            const formData = new FormData($form[0]);
            formData.append('action', 'sr_submit_rating');
            formData.append('nonce', srRatings.nonce);

            // Show loading state
            $submitBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();

            // Submit via AJAX with FormData
            $.ajax({
                url: srRatings.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        const $formContainer = $form.closest('.sr-rating-form-container');
                        const $successMsg = $('#sr-success-message');

                        // Update success message text
                        $successMsg.find('.sr-success-text').text(response.data.message);

                        // Hide form, show success
                        $form.fadeOut(200, function() {
                            $successMsg.fadeIn(200);
                        });

                        // Update rating displays on page
                        self.updateRatingDisplays(response.data.new_average, response.data.new_count);

                        // If needs approval, add note
                        if (response.data.needs_approval) {
                            $successMsg.find('.sr-success-text').text(
                                srRatings.strings.thankYou + ' ' + srRatings.strings.pendingApproval
                            );
                        }

                        // Reload page after delay to show updated ratings list
                        setTimeout(function() {
                            location.reload();
                        }, 2500);

                    } else {
                        alert(response.data.message || srRatings.strings.error);
                        // Reset button
                        $submitBtn.prop('disabled', false);
                        $btnText.show();
                        $btnLoading.hide();
                    }
                },
                error: function() {
                    alert(srRatings.strings.error);
                    // Reset button
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoading.hide();
                }
            });
        },

        /**
         * Update rating displays on the page
         *
         * @param {number} newAverage
         * @param {number} newCount
         */
        updateRatingDisplays: function(newAverage, newCount) {
            // Update hero rating
            const $heroRating = $('.sr-hero-rating');
            if ($heroRating.length) {
                $heroRating.find('.sr-rating-value').text(newAverage);
                $heroRating.find('.sr-rating-count').text('(' + newCount + ')');
            }

            // Update summary
            const $summary = $('.sr-rating-large');
            if ($summary.length) {
                $summary.find('.sr-rating-number').text(newAverage);
            }

            // Update tab badge
            const $tabBadge = $('.sr-tab-badge');
            if ($tabBadge.length) {
                $tabBadge.text(newAverage);
            } else {
                // Create badge if doesn't exist
                const $bewertungenTab = $('.sd-tab[data-tab="bewertungen"]');
                if ($bewertungenTab.length && newCount > 0) {
                    $bewertungenTab.append('<span class="sr-tab-badge">' + newAverage + '</span>');
                }
            }
        },

        /**
         * Bind clicks on rating badges for non-logged-in users
         */
        bindBadgeClicks: function() {
            // If user is not logged in, redirect badge clicks to login
            if (!srRatings.isLoggedIn) {
                $(document).on('click', '.sr-rating-badge', function(e) {
                    // Don't prevent default - let the link work
                    // But if clicking the empty badge, redirect to login with the ratings tab URL
                    const $badge = $(this);
                    if ($badge.hasClass('sr-empty')) {
                        e.preventDefault();
                        const targetUrl = $badge.attr('href');
                        window.location.href = srRatings.loginUrl + '?redirect_to=' + encodeURIComponent(targetUrl);
                    }
                });
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        SRRatings.init();
    });

})(jQuery);
