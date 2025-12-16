/**
 * OG Screenshots Admin JavaScript
 *
 * @package Spezialist_OG_Screenshots
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // State
    var OGSAdmin = {
        isProcessing: false,
        shouldStop: false,
        items: [],
        currentIndex: 0,
        stats: {
            success: 0,
            error: 0,
            skipped: 0
        }
    };

    /**
     * Initialize
     */
    function init() {
        bindEvents();
        loadStats();
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        $('#ogs-refresh-stats').on('click', loadStats);
        $('#ogs-start-processing').on('click', startProcessing);
        $('#ogs-stop-processing').on('click', stopProcessing);
    }

    /**
     * Load statistics via AJAX
     */
    function loadStats() {
        var $button = $('#ogs-refresh-stats');
        $button.prop('disabled', true).text('Laden...');

        $.ajax({
            url: ogsAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ogs_get_stats',
                nonce: ogsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatsUI(response.data);
                } else {
                    console.error('Error loading stats:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            },
            complete: function() {
                $button.prop('disabled', false).text('Statistiken aktualisieren');
            }
        });
    }

    /**
     * Update statistics UI
     */
    function updateStatsUI(data) {
        // Update per-type stats
        $.each(data.by_type, function(type, stats) {
            var $card = $('.ogs-stat-card[data-type="' + type + '"]');
            $card.find('.ogs-stat-with').text(stats.with_screenshot);
            $card.find('.ogs-stat-total').text(stats.total);
            $card.find('.ogs-stat-missing-count').text(stats.missing);
        });

        // Update totals
        $('#ogs-total-with').text(data.totals.with_screenshot);
        $('#ogs-total-total').text(data.totals.total);
        $('#ogs-total-missing').text(data.totals.missing);
    }

    /**
     * Start processing
     */
    function startProcessing() {
        if (OGSAdmin.isProcessing) return;

        // Reset state
        OGSAdmin.isProcessing = true;
        OGSAdmin.shouldStop = false;
        OGSAdmin.items = [];
        OGSAdmin.currentIndex = 0;
        OGSAdmin.stats = { success: 0, error: 0, skipped: 0 };

        // Update UI
        $('#ogs-start-processing').prop('disabled', true);
        $('#ogs-stop-processing').prop('disabled', false);
        $('#ogs-progress-container').show();
        $('#ogs-log-container').show();
        $('#ogs-summary').hide();
        $('#ogs-log').empty();

        logMessage('info', ogsAdmin.strings.starting);

        // Get selected type
        var type = $('#ogs-type-filter').val();

        // Load items to process
        $.ajax({
            url: ogsAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ogs_get_items',
                nonce: ogsAdmin.nonce,
                type: type
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    OGSAdmin.items = response.data;
                    logMessage('info', 'Gefunden: ' + OGSAdmin.items.length + ' Items zu verarbeiten');
                    processNext();
                } else {
                    logMessage('info', ogsAdmin.strings.noItems);
                    finishProcessing();
                }
            },
            error: function(xhr, status, error) {
                logMessage('error', 'Fehler beim Laden: ' + error);
                finishProcessing();
            }
        });
    }

    /**
     * Stop processing
     */
    function stopProcessing() {
        if (!OGSAdmin.isProcessing) return;

        OGSAdmin.shouldStop = true;
        logMessage('info', ogsAdmin.strings.stopped);
    }

    /**
     * Process next item in queue
     */
    function processNext() {
        // Check if should stop
        if (OGSAdmin.shouldStop || OGSAdmin.currentIndex >= OGSAdmin.items.length) {
            finishProcessing();
            return;
        }

        var item = OGSAdmin.items[OGSAdmin.currentIndex];
        updateProgress(item);

        // Process item
        $.ajax({
            url: ogsAdmin.ajaxUrl,
            type: 'POST',
            timeout: 65000, // 65 second timeout
            data: {
                action: 'ogs_process_single',
                nonce: ogsAdmin.nonce,
                id: item.id,
                entity_type: item.entity_type,
                url: item.url
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status === 'success') {
                        OGSAdmin.stats.success++;
                        logMessage('success', item.title);
                    } else {
                        OGSAdmin.stats.error++;
                        logMessage('error', item.title + ' - ' + response.data.message);
                    }
                } else {
                    OGSAdmin.stats.error++;
                    logMessage('error', item.title + ' - ' + (response.data || 'Unbekannter Fehler'));
                }
            },
            error: function(xhr, status, error) {
                OGSAdmin.stats.error++;
                var errorMsg = status === 'timeout' ? 'Timeout' : error;
                logMessage('error', item.title + ' - ' + errorMsg);
            },
            complete: function() {
                OGSAdmin.currentIndex++;

                // Delay before next request to avoid overloading
                setTimeout(processNext, 500);
            }
        });
    }

    /**
     * Update progress UI
     */
    function updateProgress(item) {
        var current = OGSAdmin.currentIndex + 1;
        var total = OGSAdmin.items.length;
        var percent = Math.round((current / total) * 100);

        $('#ogs-progress-count').text(current + ' / ' + total);
        $('#ogs-progress-bar').css('width', percent + '%');
        $('#ogs-progress-current').text('Verarbeite: ' + item.title);
        $('#ogs-progress-status').text(ogsAdmin.strings.processing + ' (' + percent + '%)');
    }

    /**
     * Finish processing
     */
    function finishProcessing() {
        OGSAdmin.isProcessing = false;
        OGSAdmin.shouldStop = false;

        // Update UI
        $('#ogs-start-processing').prop('disabled', false);
        $('#ogs-stop-processing').prop('disabled', true);
        $('#ogs-progress-status').text(ogsAdmin.strings.completed);
        $('#ogs-progress-current').text('');

        // Show summary
        $('#ogs-summary-success').text(OGSAdmin.stats.success);
        $('#ogs-summary-error').text(OGSAdmin.stats.error);
        $('#ogs-summary-skipped').text(OGSAdmin.stats.skipped);
        $('#ogs-summary').show();

        logMessage('info', ogsAdmin.strings.completed + ' - Erfolgreich: ' + OGSAdmin.stats.success + ', Fehler: ' + OGSAdmin.stats.error);

        // Reload stats
        loadStats();
    }

    /**
     * Log message to UI
     */
    function logMessage(type, message) {
        var time = new Date().toLocaleTimeString('de-DE');
        var typeClass = 'ogs-log-' + type;
        var $entry = $('<div class="ogs-log-entry">');
        $entry.append('<span class="ogs-log-time">[' + time + ']</span>');
        $entry.append('<span class="' + typeClass + '">' + escapeHtml(message) + '</span>');

        $('#ogs-log').append($entry);

        // Auto-scroll to bottom
        var $log = $('#ogs-log');
        $log.scrollTop($log[0].scrollHeight);
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
