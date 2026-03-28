/* global dislike404Ajax, jQuery */
(function ($) {
    'use strict';

    $(function () {

        const $btn = $('#dislike404-trigger-scan-btn');
        const $status = $('#dislike404-scan-status');
        if (!$btn.length) {
            return;
        }

        const uuid = $btn.data('uuid');
        const SESSION_KEY = 'dislike404_scan_' + uuid;
        const $lastScan = $('#dislike404-last-scan');

        // Hide last scan block until we have something to show.
        $lastScan.hide();

        // If the admin bar is present it handles all polling — we just read from session.
        const adminBarPresent = $('#wp-admin-bar-dislike404-scan').length > 0;

        // On page load: restore from sessionStorage, or fetch last scan from API.
        const saved = loadSession();
        if (saved) {

            renderLabel(saved);
            if (saved.status === 'running' || saved.status === 'initiating') {
                if (!adminBarPresent) {
                    $btn.prop('disabled', true);
                    pollStatus();
                } else {
                    // Admin bar is polling — watch the session for updates every 2 seconds.
                    $btn.prop('disabled', true);
                    watchSession();
                }
            }
        } else {

            // Nothing in session — fetch the last known scan result once (no polling).
            fetchLastScan();
        }
        function handleError(responseData) {
            let data = null;
            if (responseData && responseData.status_code === 401) {
                data = { status: 'error_auth' };
            } else {
                data = { status: 'error' };
            }
            saveSession(data);
            renderLabel(data);
        }

        $btn.on('click', function () {
            clearSession();
            $lastScan.hide();

            renderLabel({ status: 'initiating' });

            $.post(dislike404Ajax.ajax_url, {
                action: 'dislike404_trigger_scan',
                nonce: dislike404Ajax.nonce,
                uuid: uuid,
            })
                .done(function (response) {
                    if (response.success) {
                        pollStatus();
                    } else {
                        handleError(response.data);
                        $btn.prop('disabled', false);
                    }
                })
                .fail(function () {
                    const data = { status: 'error' };
                    saveSession(data);
                    renderLabel(data);
                    $btn.prop('disabled', false);
                });
        });

        function pollStatus() {
            const FAST_INTERVAL = 5000;
            const SLOW_INTERVAL = 30000;
            const FAST_PHASE_MS = 2 * 60 * 1000;
            const TIMEOUT_MS = 2 * 60 * 60 * 1000;
            const startTime = Date.now();

            function scheduleNext() {
                const elapsed = Date.now() - startTime;
                if (elapsed >= TIMEOUT_MS) {
                    const data = { status: 'error' };
                    saveSession(data);
                    renderLabel(data);
                    $btn.prop('disabled', false);
                    return;
                }
                setTimeout(doPoll, elapsed < FAST_PHASE_MS ? FAST_INTERVAL : SLOW_INTERVAL);
            }

            function doPoll() {
                $.post(dislike404Ajax.ajax_url, {
                    action: 'dislike404_scan_status',
                    nonce: dislike404Ajax.poll_nonce,
                    uuid: uuid,
                })
                    .done(function (response) {
                        if (!response.success) {
                            console.warn('dislike404:', response.data);
                            const data = { status: 'failed', status_code: response.data.status_code || 0 };
                            saveSession(data);
                            renderLabel(data);
                            $btn.prop('disabled', false);
                            return;
                        }

                        const data = response.data;
                        saveSession(data);
                        renderLabel(data);

                        if (data.status === 'initiating' || data.status === 'running') {
                            scheduleNext();
                            return;
                        }

                        $btn.prop('disabled', false);
                    })
                    .fail(function () {
                        scheduleNext();
                    });
            }

            doPoll();
        }

        /**
         * Fetch the last scan result once on page load — no polling, no side effects.
         * Only called when sessionStorage is empty (e.g. fresh login).
         */
        function fetchLastScan() {
            $.post(dislike404Ajax.ajax_url, {
                action: 'dislike404_scan_status',
                nonce: dislike404Ajax.poll_nonce,
                uuid: uuid,
            })
                .done(function (response) {
                    if (!response.success) return;
                    const data = response.data;
                    saveSession(data);
                    renderLabel(data);
                    if (data.status === 'initiating' || data.status === 'running') {
                        $btn.prop('disabled', true);
                        adminBarPresent ? watchSession() : pollStatus();
                    }
                });
        }

        /**
         * When the admin bar is handling polling, watch sessionStorage for updates
         * and re-render when the status changes. No API requests.
         */
        function watchSession() {
            const interval = setInterval(function () {
                const current = loadSession();
                if (!current) return;
                renderLabel(current);
                if (current.status !== 'initiating' && current.status !== 'running') {
                    $btn.prop('disabled', false);
                    clearInterval(interval);
                }
            }, 2000);
        }

        /**
         * Validate and sanitize data before storing it in sessionStorage.
         */
        function isValidUuid(value) {
            return typeof value === 'string' && /^[0-9a-f\-]{36}$/i.test(value);
        }

        function validateSessionData(data) {
            if (!data || typeof data !== 'object') return null;

            const validStatuses = ['idle', 'not_found', 'initiating', 'running', 'errors_found', 'no_errors', 'aborted', 'failed', 'error', 'error_auth'];
            if (!validStatuses.includes(data.status)) return null;

            if (data.detected_errors !== undefined) {
                data.detected_errors = Math.max(0, parseInt(data.detected_errors, 10) || 0);
            }
            if (data.crawled_pages !== undefined) {
                data.crawled_pages = Math.max(0, parseInt(data.crawled_pages, 10) || 0);
            }
            if (data.end_time !== undefined && typeof data.end_time !== 'string') {
                delete data.end_time;
            }
            if (data.run_uuid !== undefined && !isValidUuid(data.run_uuid)) {
                delete data.run_uuid;
            }
            if (data.url_uuid !== undefined && !isValidUuid(data.url_uuid)) {
                delete data.url_uuid;
            }

            return data;
        }

        function renderLabel(data) {
            $lastScan.show();
            $status.empty().css('color', '');
            if (data.status === 'initiating') {
                $status.text(dislike404Ajax.i18n.initiating).css('color', '#666');
                $btn.prop('disabled', true);
            } else if (data.status === 'running') {
                $status.text(dislike404Ajax.i18n.running + ' ' + data.detected_errors + ' ' + dislike404Ajax.i18n.errors_found + ' \u2014 ' + data.crawled_pages + ' ' + dislike404Ajax.i18n.pages_crawled).css('color', '#666');
                $btn.prop('disabled', true);
            } else if (data.status === 'errors_found' || data.status === 'no_errors' || data.status === 'aborted') {
                const hasErrors = data.detected_errors > 0;
                const color = hasErrors ? '#d97706' : '#00a32a';
                const errorText = hasErrors
                    ? '\u26a0 ' + data.detected_errors + ' ' + dislike404Ajax.i18n.errors_found
                    : '\u2713 ' + dislike404Ajax.i18n.no_errors;

                const $summary = $('<span>')
                    .text(errorText + ' \u2014 ' + data.crawled_pages + ' ' + dislike404Ajax.i18n.pages_crawled)
                    .css({ color: color, fontWeight: '600' });

                $status.append($summary);

                if (data.end_time) {
                    const $time = $('<span>')
                        .text(' \u2014 ' + timeAgo(data.end_time))
                        .css('color', '#999');
                    $status.append($time);
                }

                if (isValidUuid(data.url_uuid) && isValidUuid(data.run_uuid)) {
                    const reportUrl = 'https://dislike404.com/scan-details/' + data.url_uuid + '/' + data.run_uuid;
                    $status.append(' — ');
                    const $link = $('<a>')
                        .attr({ href: reportUrl, target: '_blank', rel: 'noopener noreferrer' })
                        .text(dislike404Ajax.i18n.view_report + ' →');
                    $status.append($link);
                }
                $btn.prop('disabled', false);
            } else if (data.status === 'error_auth') {
                $status.text(dislike404Ajax.i18n.error_auth).css('color', '#d63638');
                $btn.prop('disabled', false);
            } else if (data.status === 'error') {
                $status.text(dislike404Ajax.i18n.error).css('color', '#d63638');
                $btn.prop('disabled', false);
            } else {
                $status.text(dislike404Ajax.i18n.idle).css('color', '#d63638');
                $btn.prop('disabled', false);
            }
        }

        // clear every session data if user clicks on the "Save Settings" to prevent having old data in the session
        $('form').on('submit', function () {
            Object.keys(sessionStorage)
                .filter(key => key.startsWith('dislike404_scan_'))
                .forEach(key => sessionStorage.removeItem(key));
        });

        function saveSession(data) {
            try {
                sessionStorage.setItem(SESSION_KEY, JSON.stringify(data));
            } catch (e) { }
        }

        function loadSession() {
            try {
                const raw = sessionStorage.getItem(SESSION_KEY);
                if (!raw) return null;
                return validateSessionData(JSON.parse(raw));
            } catch (e) {
                return null;
            }
        }

        function clearSession() {
            try {
                sessionStorage.removeItem(SESSION_KEY);
            } catch (e) { }
        }
    });

    /**
     * Convert an ISO-8601 timestamp to a human-readable "X ago" string.
     * Calculated client-side so it stays accurate after page reloads.
     */
    function timeAgo(isoString) {
        const seconds = Math.floor((Date.now() - new Date(isoString).getTime()) / 1000);

        if (seconds < 60) return seconds + ' seconds ago';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
        return Math.floor(seconds / 86400) + ' days ago';
    }

})(jQuery);