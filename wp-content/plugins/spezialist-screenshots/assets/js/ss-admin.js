/**
 * Spezialist Screenshots - Admin JavaScript
 */

(function($) {
    'use strict';

    var SSAdmin = {
        isRunning: false,
        isRepairMode: false,
        posts: [],
        currentIndex: 0,
        processed: 0,
        success: 0,
        failed: 0,
        skipped: 0,

        init: function() {
            this.bindEvents();
            this.loadStats();
        },

        bindEvents: function() {
            $('#ss-start').on('click', this.start.bind(this));
            $('#ss-stop').on('click', this.stop.bind(this));
            $('#ss-save-api-key').on('click', this.saveApiKey.bind(this));
            $('#ss-repair').on('click', this.startRepair.bind(this));
            $('#ss-regenerate-sizes').on('click', this.regenerateSizes.bind(this));
        },

        saveApiKey: function() {
            var self = this;
            var apiKey = $('#ss-api-key').val();
            var $status = $('#ss-api-save-status');

            $status.text('Speichern...').css('color', '#666');

            $.ajax({
                url: ssAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ss_save_api_key',
                    nonce: ssAdmin.nonce,
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        $status.text('Gespeichert!').css('color', '#00a32a');
                        // Enable start button if API key is set
                        if (apiKey) {
                            $('#ss-start').prop('disabled', false);
                        }
                        setTimeout(function() {
                            $status.text('');
                        }, 3000);
                    } else {
                        $status.text('Fehler!').css('color', '#d63638');
                    }
                },
                error: function() {
                    $status.text('Fehler!').css('color', '#d63638');
                }
            });
        },

        loadStats: function() {
            $.ajax({
                url: ssAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ss_get_stats',
                    nonce: ssAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#ss-total-posts').text(response.data.total);
                        $('#ss-without-image').text(response.data.without_image);
                        $('#ss-eligible').text(response.data.eligible);
                        $('#ss-missing-base').text(response.data.missing_base);
                        $('#ss-missing-sizes').text(response.data.missing_sizes);

                        // Update repair button text
                        if (response.data.missing_base > 0) {
                            $('#ss-repair').text('Fehlende Basisbilder reparieren (' + response.data.missing_base + ')');
                        } else {
                            $('#ss-repair').text('Fehlende Basisbilder reparieren (0)').prop('disabled', true);
                        }

                        // Update regenerate button text
                        if (response.data.missing_sizes > 0) {
                            $('#ss-regenerate-sizes').text('Größen regenerieren (' + response.data.missing_sizes + ')');
                        } else {
                            $('#ss-regenerate-sizes').text('Größen regenerieren (0)').prop('disabled', true);
                        }
                    }
                }
            });
        },

        start: function() {
            var self = this;

            // Reset counters
            this.isRunning = true;
            this.isRepairMode = false;
            this.posts = [];
            this.currentIndex = 0;
            this.processed = 0;
            this.success = 0;
            this.failed = 0;
            this.skipped = 0;

            // Update UI
            $('#ss-start').prop('disabled', true);
            $('#ss-stop').prop('disabled', false);
            $('#ss-progress-container').show();
            $('#ss-summary').hide();
            $('#ss-log').empty();

            this.addLog('Lade zu verarbeitende Einträge...', 'processing');

            // Get eligible posts
            $.ajax({
                url: ssAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ss_get_eligible_posts',
                    nonce: ssAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        self.posts = response.data;
                        self.addLog('Gefunden: ' + self.posts.length + ' Einträge zu verarbeiten', 'success');
                        self.updateProgress();
                        self.processNext();
                    } else {
                        self.addLog('Keine Einträge zu verarbeiten gefunden', 'skipped');
                        self.complete();
                    }
                },
                error: function() {
                    self.addLog('Fehler beim Laden der Einträge', 'error');
                    self.complete();
                }
            });
        },

        stop: function() {
            this.isRunning = false;
            this.addLog('Verarbeitung wird gestoppt...', 'skipped');
            $('#ss-stop').prop('disabled', true);
        },

        startRepair: function() {
            var self = this;

            // Reset counters
            this.isRunning = true;
            this.isRepairMode = true;
            this.posts = [];
            this.currentIndex = 0;
            this.processed = 0;
            this.success = 0;
            this.failed = 0;
            this.skipped = 0;

            // Update UI
            $('#ss-start').prop('disabled', true);
            $('#ss-repair').prop('disabled', true);
            $('#ss-regenerate-sizes').prop('disabled', true);
            $('#ss-stop').prop('disabled', false);
            $('#ss-progress-container').show();
            $('#ss-summary').hide();
            $('#ss-log').empty();

            this.addLog('Lade Einträge mit fehlenden Basisbildern...', 'processing');

            // Get posts with missing base images
            $.ajax({
                url: ssAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ss_get_repair_posts',
                    nonce: ssAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        self.posts = response.data;
                        self.addLog('Gefunden: ' + self.posts.length + ' Einträge mit fehlenden Basisbildern', 'success');
                        self.updateProgress();
                        self.processNext();
                    } else {
                        self.addLog('Keine Einträge mit fehlenden Basisbildern gefunden', 'skipped');
                        self.complete();
                    }
                },
                error: function() {
                    self.addLog('Fehler beim Laden der Einträge', 'error');
                    self.complete();
                }
            });
        },

        regenerateSizes: function() {
            var self = this;

            $('#ss-regenerate-sizes').prop('disabled', true).text('Regeneriere...');
            this.addLog('Starte Regenerierung fehlender Größen...', 'processing');

            $.ajax({
                url: ssAdmin.ajaxUrl,
                type: 'POST',
                timeout: 300000, // 5 minutes timeout for batch processing
                data: {
                    action: 'ss_regenerate_sizes',
                    nonce: ssAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.addLog('Größen regeneriert: ' + response.data.processed + ' erfolgreich, ' + response.data.errors + ' Fehler', 'success');
                        self.loadStats();
                    } else {
                        self.addLog('Fehler bei der Regenerierung: ' + (response.data || 'Unbekannter Fehler'), 'error');
                    }
                    $('#ss-regenerate-sizes').prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    self.addLog('Fehler bei der Regenerierung: ' + (status === 'timeout' ? 'Zeitüberschreitung' : error), 'error');
                    $('#ss-regenerate-sizes').prop('disabled', false);
                }
            });
        },

        processNext: function() {
            var self = this;

            if (!this.isRunning || this.currentIndex >= this.posts.length) {
                this.complete();
                return;
            }

            var post = this.posts[this.currentIndex];
            $('#ss-current-item').text('Verarbeite: ' + post.title);
            this.addLog('Verarbeite: ' + post.title + ' (ID: ' + post.id + ')', 'processing');

            $.ajax({
                url: ssAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ss_process_single',
                    nonce: ssAdmin.nonce,
                    post_id: post.id,
                    force_repair: self.isRepairMode ? 'true' : 'false'
                },
                timeout: 60000, // 60 seconds timeout for screenshot
                success: function(response) {
                    self.processed++;
                    self.currentIndex++;

                    if (response.success) {
                        var data = response.data;
                        if (data.status === 'success') {
                            self.success++;
                            self.addLog('Erfolg: ' + post.title, 'success');
                        } else if (data.status === 'skipped') {
                            self.skipped++;
                            self.addLog('Übersprungen: ' + post.title + ' - ' + data.message, 'skipped');
                        } else {
                            self.failed++;
                            self.addLog('Fehler: ' + post.title + ' - ' + data.message, 'error');
                        }
                    } else {
                        self.failed++;
                        self.addLog('Fehler: ' + post.title + ' - ' + (response.data || 'Unbekannter Fehler'), 'error');
                    }

                    self.updateProgress();

                    // Small delay between requests
                    setTimeout(function() {
                        self.processNext();
                    }, 500);
                },
                error: function(xhr, status, error) {
                    self.processed++;
                    self.currentIndex++;
                    self.failed++;

                    var errorMsg = status === 'timeout' ? 'Zeitüberschreitung' : error;
                    self.addLog('Fehler: ' + post.title + ' - ' + errorMsg, 'error');

                    // Bei Timeout: Listing pausieren
                    if (status === 'timeout') {
                        $.ajax({
                            url: ssAdmin.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'ss_pause_post',
                                nonce: ssAdmin.nonce,
                                post_id: post.id,
                                reason: 'Zeitüberschreitung beim Screenshot'
                            },
                            success: function(response) {
                                if (response.success) {
                                    self.addLog('→ ' + post.title + ' wurde pausiert', 'warning');
                                }
                            }
                        });
                    }

                    self.updateProgress();

                    setTimeout(function() {
                        self.processNext();
                    }, 500);
                }
            });
        },

        updateProgress: function() {
            var total = this.posts.length;
            var percent = total > 0 ? Math.round((this.processed / total) * 100) : 0;

            $('#ss-progress-bar').css('width', percent + '%');
            $('#ss-progress-text').text(this.processed + ' von ' + total + ' (' + percent + '%)');
        },

        complete: function() {
            this.isRunning = false;
            this.isRepairMode = false;

            $('#ss-start').prop('disabled', false);
            $('#ss-repair').prop('disabled', false);
            $('#ss-regenerate-sizes').prop('disabled', false);
            $('#ss-stop').prop('disabled', true);
            $('#ss-current-item').text('Verarbeitung abgeschlossen');

            // Update summary
            $('#ss-success-count').text(this.success);
            $('#ss-error-count').text(this.failed);
            $('#ss-skipped-count').text(this.skipped);
            $('#ss-summary').show();

            this.addLog('=== Verarbeitung abgeschlossen ===', 'success');
            this.addLog('Erfolgreich: ' + this.success + ' | Fehler: ' + this.failed + ' | Übersprungen: ' + this.skipped, 'processing');

            // Reload stats
            this.loadStats();
        },

        addLog: function(message, type) {
            var time = new Date().toLocaleTimeString('de-DE');
            var entry = $('<div class="ss-log-entry ss-log-' + type + '">')
                .html('<span class="ss-log-time">[' + time + ']</span> ' + this.escapeHtml(message));

            $('#ss-log').append(entry);

            // Auto-scroll to bottom
            var log = document.getElementById('ss-log');
            log.scrollTop = log.scrollHeight;
        },

        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        if ($('.ss-admin-page').length) {
            SSAdmin.init();
        }
    });

})(jQuery);
