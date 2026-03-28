/* global dislike404Bar, jQuery */
(function ($) {
    'use strict';

    const BAR_SHOW_RESULT_FOR_SECONDS = 60;

    $(function () {
        const $node = $('#wp-admin-bar-dislike404-scan');

        if (!$node.length) {
            return;
        }

        const SESSION_KEY = 'dislike404_scan_' + dislike404Bar.uuid;

        // On page load: restore from sessionStorage, or fetch once from API.
        const saved = loadSession();
        if (saved) {
            renderLabel(saved);
            if (saved.status === 'initiating' || saved.status === 'running') {
                pollStatus();
            }
        } else {
            // No session — show default label, no API request.
            // Polling starts only when the user clicks the button.
            renderLabel({ status: 'default' });
        }

        $node.find('a').on('click', function (e) {
            e.stopPropagation();

            // If showing an error result, let the browser follow the link,
            // then clean up on the next tick.
            var session = loadSession();
            if (session && (session.status === 'error_auth' || session.status === 'running' || session.status === 'initiating')) {
                return;
            } else if (session && session.status === 'errors_found') {
                setTimeout(function () {
                    clearSession();
                    renderLabel({ status: 'default' });
                }, 1000);
                return;
            }

            e.preventDefault();
            clearSession();
            renderLabel({ status: 'initiating' });

            $.post(dislike404Bar.ajax_url, {
                action: 'dislike404_trigger_scan',
                nonce: dislike404Bar.nonce,
                uuid: dislike404Bar.uuid,
            })
                .done(function (response) {
                    if (response.success) {
                        pollStatus();
                    } else {
                        handleError(response.data)
                    }
                })
                .fail(function () {
                    handleError(null);
                });
        });

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

        function pollStatus() {
            const FAST_INTERVAL = 5000;
            const SLOW_INTERVAL = 30000;
            const FAST_PHASE_MS = 2 * 60 * 1000;
            const TIMEOUT_MS = 2 * 60 * 60 * 1000;
            const startTime = Date.now();

            function scheduleNext() {
                const elapsed = Date.now() - startTime;
                if (elapsed >= TIMEOUT_MS) {
                    handleError(null);
                    return;
                }
                setTimeout(doPoll, elapsed < FAST_PHASE_MS ? FAST_INTERVAL : SLOW_INTERVAL);
            }

            function doPoll() {
                $.post(dislike404Bar.ajax_url, {
                    action: 'dislike404_scan_status',
                    nonce: dislike404Bar.poll_nonce,
                    uuid: dislike404Bar.uuid,
                })
                    .done(function (response) {
                        if (!response.success) {
                            handleError(response.data);
                            return;
                        }

                        const data = response.data;
                        saveSession(data);
                        renderLabel(data);

                        if (data.status === 'initiating' || data.status === 'running') {
                            scheduleNext();
                        }
                    })
                    .fail(function () {
                        scheduleNext();
                    });
            }
            doPoll();
        }

        function renderLabel(data) {
            let text = dislike404Bar.i18n.default;
            let tooltip = dislike404Bar.i18n.tooltip_default;
            let link = { href: "#", target: '_self', rel: '' };

            if (data.status === 'initiating') {
                text = dislike404Bar.i18n.initiating;
                tooltip = dislike404Bar.i18n.tooltip_initiating;
            } else if (data.status === 'running') {
                text = dislike404Bar.i18n.running + data.detected_errors + '/' + data.crawled_pages;
                tooltip = dislike404Bar.i18n.tooltip_running + data.detected_errors + dislike404Bar.i18n.tooltip_running_crawled_pages + data.crawled_pages;
            } else if (data.status === 'errors_found' || data.status === 'no_errors') {
                // Errors stay visible for 10 minutes, no-error result for 60 seconds.
                const hasErrors = data.detected_errors > 0;
                const showForSec = hasErrors ? 600 : BAR_SHOW_RESULT_FOR_SECONDS;
                const secondsAgo = data.end_time
                    ? Math.floor((Date.now() - new Date(data.end_time).getTime()) / 1000)
                    : showForSec + 1;

                if (secondsAgo > showForSec) {
                    text = dislike404Bar.i18n.default;
                    tooltip = dislike404Bar.i18n.tooltip_default;
                    clearSession();
                } else {
                    text = hasErrors
                        ? '\u26a0 ' + data.detected_errors + ' ' + dislike404Bar.i18n.errors_found
                        : '\u2713 ' + dislike404Bar.i18n.no_errors;
                    tooltip = hasErrors ? dislike404Bar.i18n.tooltip_errors_found : dislike404Bar.i18n.tooltip_no_errors;
                    if (hasErrors && isValidUuid(data.url_uuid) && isValidUuid(data.run_uuid)) {
                        const reportUrl = 'https://dislike404.com/scan-details/' + data.url_uuid + '/' + data.run_uuid;
                        link = { href: reportUrl, target: '_blank', rel: 'noopener noreferrer' };
                    }
                }

            } else if (data.status === 'error_auth') {
                text = dislike404Bar.i18n.error_auth;
                tooltip = dislike404Bar.i18n.tooltip_error_auth;
                link = { href: dislike404Bar.settings_url };
            } else if (data.status === 'error') {
                text = dislike404Bar.i18n.error;
                tooltip = dislike404Bar.i18n.tooltip_error;
            }
            $node.find('.dislike404-bar-label').text(text);
            $node.children('a').attr('title', tooltip);
            $node.children('a').attr(link);
        }

        function saveSession(data) {
            try {
                sessionStorage.setItem(SESSION_KEY, JSON.stringify(data));
            } catch (e) { }
        }

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

})(jQuery);