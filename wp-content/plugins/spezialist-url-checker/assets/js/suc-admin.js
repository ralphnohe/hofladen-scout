/**
 * Spezialist URL Checker - Admin JavaScript
 */

(function($) {
    'use strict';

    var SUCAdmin = {
        isRunning: false,
        posts: [],
        currentIndex: 0,
        checked: 0,
        errors: 0,
        errorList: [],

        init: function() {
            this.bindEvents();
            this.loadStats();
        },

        bindEvents: function() {
            $('#suc-start').on('click', this.start.bind(this));
            $('#suc-stop').on('click', this.stop.bind(this));
            $('#suc-results-body').on('click', '.suc-pause', this.pausePost.bind(this));
            $('#suc-results-body').on('click', '.suc-delete', this.deletePost.bind(this));
            $('#suc-results-body').on('click', '.suc-ignore', this.ignoreEntry.bind(this));
        },

        loadStats: function() {
            var self = this;

            $.ajax({
                url: sucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'suc_get_stats',
                    nonce: sucAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#suc-total-posts').text(response.data.total);
                        $('#suc-with-url').text(response.data.with_url);
                    }
                }
            });
        },

        start: function() {
            var self = this;

            // Reset state
            this.isRunning = true;
            this.posts = [];
            this.currentIndex = 0;
            this.checked = 0;
            this.errors = 0;
            this.errorList = [];

            // Update UI
            $('#suc-start').prop('disabled', true);
            $('#suc-stop').prop('disabled', false);
            $('#suc-progress-container').show();
            $('#suc-results-container').hide();
            $('#suc-results-body').empty();
            $('#suc-log').empty();
            $('#suc-checked').text('0');
            $('#suc-errors').text('0');
            $('#suc-error-count').text('0');

            this.addLog('Lade Einträge...', 'processing');

            // Load posts
            $.ajax({
                url: sucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'suc_get_posts',
                    nonce: sucAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        self.posts = response.data;
                        self.addLog('Gefunden: ' + self.posts.length + ' Einträge mit Website', 'success');
                        self.updateProgress();
                        self.processNext();
                    } else {
                        self.addLog('Keine Einträge mit Website gefunden', 'skipped');
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
            this.addLog('Prüfung wird gestoppt...', 'skipped');
            $('#suc-stop').prop('disabled', true);
        },

        processNext: function() {
            var self = this;

            if (!this.isRunning || this.currentIndex >= this.posts.length) {
                this.complete();
                return;
            }

            var post = this.posts[this.currentIndex];
            $('#suc-current-item').text('Prüfe: ' + post.title);

            $.ajax({
                url: sucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'suc_check_url',
                    nonce: sucAdmin.nonce,
                    post_id: post.id,
                    url: post.website
                },
                timeout: 15000,
                success: function(response) {
                    self.checked++;
                    self.currentIndex++;

                    if (response.success) {
                        var data = response.data;
                        var status = data.status;

                        // Check for errors (0 = connection error, 4xx, 5xx)
                        if (status === 0 || status >= 400) {
                            self.errors++;
                            self.errorList.push({
                                id: post.id,
                                title: post.title,
                                url: post.website,
                                status: status,
                                error: data.error
                            });
                            self.addLog('Fehler: ' + post.title + ' - ' + (status === 0 ? data.error : 'HTTP ' + status), 'error');
                            self.addErrorRow(post, status, data.error);
                        } else {
                            // Successful
                            self.addLog('OK: ' + post.title + ' (' + status + ')', 'success');
                        }
                    } else {
                        self.errors++;
                        self.addLog('Fehler: ' + post.title + ' - ' + (response.data || 'Unbekannter Fehler'), 'error');
                    }

                    self.updateProgress();
                    self.updateStats();

                    // Small delay between requests
                    setTimeout(function() {
                        self.processNext();
                    }, 100);
                },
                error: function(xhr, status, error) {
                    self.checked++;
                    self.currentIndex++;
                    self.errors++;

                    var errorMsg = status === 'timeout' ? 'Zeitüberschreitung' : error;
                    self.addLog('Fehler: ' + post.title + ' - ' + errorMsg, 'error');
                    self.errorList.push({
                        id: post.id,
                        title: post.title,
                        url: post.website,
                        status: 0,
                        error: errorMsg
                    });
                    self.addErrorRow(post, 0, errorMsg);

                    self.updateProgress();
                    self.updateStats();

                    setTimeout(function() {
                        self.processNext();
                    }, 100);
                }
            });
        },

        updateProgress: function() {
            var total = this.posts.length;
            var percent = total > 0 ? Math.round((this.checked / total) * 100) : 0;

            $('#suc-progress-bar').css('width', percent + '%');
            $('#suc-progress-text').text(this.checked + ' von ' + total + ' (' + percent + '%)');
        },

        updateStats: function() {
            $('#suc-checked').text(this.checked);
            $('#suc-errors').text(this.errors);
            $('#suc-error-count').text(this.errors);
        },

        addErrorRow: function(post, status, error) {
            var statusText = status === 0 ? 'Fehler' : status;
            var statusClass = status === 0 ? 'suc-status-error' : 'suc-status-' + status;

            var row = $('<tr data-post-id="' + post.id + '">' +
                '<td class="suc-col-title">' + this.escapeHtml(post.title) + '</td>' +
                '<td class="suc-col-url"><a href="' + this.escapeHtml(post.website) + '" target="_blank">' + this.escapeHtml(post.website) + '</a></td>' +
                '<td class="suc-col-status"><span class="suc-status-badge ' + statusClass + '">' + statusText + '</span></td>' +
                '<td class="suc-col-actions">' +
                    '<a href="/wp-admin/post.php?post=' + post.id + '&action=edit" target="_blank" class="button button-small">Bearbeiten</a> ' +
                    '<button class="button button-small suc-ignore">Ignorieren</button> ' +
                    '<button class="button button-small suc-pause">Pausieren</button> ' +
                    '<button class="button button-small suc-delete">Löschen</button>' +
                '</td>' +
            '</tr>');

            $('#suc-results-body').append(row);
            $('#suc-results-container').show();
        },

        pausePost: function(e) {
            var self = this;
            var $btn = $(e.target);
            var $row = $btn.closest('tr');
            var postId = $row.data('post-id');

            $btn.prop('disabled', true).text('...');

            $.ajax({
                url: sucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'suc_pause_post',
                    nonce: sucAdmin.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        $row.addClass('suc-row-paused');
                        $btn.text('Pausiert').addClass('button-disabled');
                        self.addLog('Pausiert: Post ID ' + postId, 'success');
                    } else {
                        $btn.prop('disabled', false).text('Pausieren');
                        self.addLog('Fehler beim Pausieren: ' + (response.data || 'Unbekannt'), 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Pausieren');
                    self.addLog('Fehler beim Pausieren von Post ID ' + postId, 'error');
                }
            });
        },

        deletePost: function(e) {
            var self = this;
            var $btn = $(e.target);
            var $row = $btn.closest('tr');
            var postId = $row.data('post-id');

            if (!confirm('Eintrag wirklich in den Papierkorb verschieben?')) {
                return;
            }

            $btn.prop('disabled', true).text('...');

            $.ajax({
                url: sucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'suc_delete_post',
                    nonce: sucAdmin.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            self.errors--;
                            self.updateStats();
                        });
                        self.addLog('Gelöscht: Post ID ' + postId, 'success');
                    } else {
                        $btn.prop('disabled', false).text('Löschen');
                        self.addLog('Fehler beim Löschen: ' + (response.data || 'Unbekannt'), 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Löschen');
                    self.addLog('Fehler beim Löschen von Post ID ' + postId, 'error');
                }
            });
        },

        ignoreEntry: function(e) {
            var self = this;
            var $btn = $(e.target);
            var $row = $btn.closest('tr');
            var postId = $row.data('post-id');

            $row.fadeOut(300, function() {
                $(this).remove();
                self.errors--;
                self.updateStats();
            });

            this.addLog('Ignoriert: Post ID ' + postId, 'skipped');
        },

        complete: function() {
            this.isRunning = false;

            $('#suc-start').prop('disabled', false);
            $('#suc-stop').prop('disabled', true);
            $('#suc-current-item').text('Prüfung abgeschlossen');

            this.addLog('=== Prüfung abgeschlossen ===', 'success');
            this.addLog('Geprüft: ' + this.checked + ' | Fehler: ' + this.errors, 'processing');
        },

        addLog: function(message, type) {
            var time = new Date().toLocaleTimeString('de-DE');
            var entry = $('<div class="suc-log-entry suc-log-' + type + '">')
                .html('<span class="suc-log-time">[' + time + ']</span> ' + this.escapeHtml(message));

            $('#suc-log').append(entry);

            // Auto-scroll
            var log = document.getElementById('suc-log');
            if (log) {
                log.scrollTop = log.scrollHeight;
            }
        },

        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        if ($('.suc-admin-page').length) {
            SUCAdmin.init();
        }
    });

})(jQuery);
