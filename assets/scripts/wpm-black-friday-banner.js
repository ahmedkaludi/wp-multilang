/**
 * Black Friday / Cyber Monday Promotional Banner Script
 * Replicates the countdown timer, coupon copy and AJAX dismissal
 *
 * WP Multilang
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        var $banner = $('#wpm-bf-discount-bar');
        if (!$banner.length) {
            return;
        }

        var $container = $banner.closest('.wpm-bf-banner-container').length ? $banner.closest('.wpm-bf-banner-container') : $banner;

        // Ensure banner is positioned at the very top of all notices
        var $firstNotice = $('#wpbody-content').children('.notice, .update-nag, .updated, .error, div.is-dismissible').not($container).first();
        if ($firstNotice.length && $container.index() > $firstNotice.index()) {
            $firstNotice.before($container);
        }

        var config = window.wpm_bf_banner_data || {};
        var deadline = $('#wpm-bf-coupon-expiry').val() || config.expiry_date || 'Dec 31, 2026 23:59:59 GMT+0530';
        var targetTime = Date.parse(deadline);
        var couponCode = config.coupon_code || 'BFCM2026';

        if (isNaN(targetTime) || Date.now() >= targetTime) {
            $container.hide();
            return;
        }

        var $daysSpan = $banner.find('.wpm-bf-days');
        var $hoursSpan = $banner.find('.wpm-bf-hours');
        var $minsSpan = $banner.find('.wpm-bf-minutes');
        var $secsSpan = $banner.find('.wpm-bf-seconds');

        // 1. Countdown Timer Logic matching adsforwp.com exactly
        function updateTime() {
            var total = targetTime - Date.now();
            if (total <= 0) {
                $container.slideUp(300, function () {
                    $(this).remove();
                });
                clearInterval(intervalId);
                return;
            }

            var seconds = Math.floor((total / 1000) % 60);
            var minutes = Math.floor((total / 1000 / 60) % 60);
            var hours = Math.floor((total / (1000 * 60 * 60)) % 24);
            var days = Math.floor(total / (1000 * 60 * 60 * 24));

            if ($daysSpan.length) {
                $daysSpan.text(days < 10 ? '0' + days : days);
            }
            if ($hoursSpan.length) {
                $hoursSpan.text(hours < 10 ? '0' + hours : hours);
            }
            if ($minsSpan.length) {
                $minsSpan.text(minutes < 10 ? '0' + minutes : minutes);
            }
            if ($secsSpan.length) {
                $secsSpan.text(seconds < 10 ? '0' + seconds : seconds);
            }
        }

        updateTime();
        var intervalId = setInterval(updateTime, 1000);

        // 2. Click to Copy Coupon Code
        $banner.on('click', '.wpm-bf-coupon-badge', function (e) {
            e.preventDefault();
            var $badge = $(this);

            var copySuccess = function () {
                $badge.addClass('wpm-bf-copied');
                setTimeout(function () {
                    $badge.removeClass('wpm-bf-copied');
                }, 2000);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(couponCode).then(copySuccess).catch(function () {
                    fallbackCopy(couponCode, copySuccess);
                });
            } else {
                fallbackCopy(couponCode, copySuccess);
            }
        });

        function fallbackCopy(text, callback) {
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                if (callback) {
                    callback();
                }
            } catch (err) {
                // Fallback copy failed
            }
            document.body.removeChild(textArea);
        }

        // 3. Dismiss Banner (Dismiss for 7 days via AJAX)
        $banner.on('click', '.wpm-bf-bar-close-btn', function (e) {
            e.preventDefault();
            clearInterval(intervalId);

            $banner.css('opacity', '0.5');

            $.ajax({
                url: config.ajax_url || ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: config.action || 'wpm_dismiss_black_friday_banner',
                    security: config.nonce
                },
                success: function (res) {
                    $container.slideUp(300, function () {
                        $(this).remove();
                    });
                },
                error: function (xhr, status, error) {
                    $container.slideUp(300, function () {
                        $(this).remove();
                    });
                }
            });
        });
    });
})(jQuery);
