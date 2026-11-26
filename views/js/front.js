/**
 * Flaveo Page Banner
 * @author Loris Pinna <https://lorispinna.com>
 *
 * Dispatches a "flaveo/pagebanner" CustomEvent on document when a banner is
 * closed, so the theme can recompute its layout.
 */
(function () {
    'use strict';

    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function setCookie(name, days) {
        var cookie = name + '=closed;path=/;SameSite=Lax';
        if (days > 0) {
            var expire = new Date();
            expire.setTime(expire.getTime() + days * 24 * 3600 * 1000);
            cookie += ';expires=' + expire.toUTCString();
        }
        if (window.location.protocol === 'https:') {
            cookie += ';Secure';
        }
        document.cookie = cookie;
    }

    /**
     * Exposes the total banners height as --flaveo-banner-height on <body>
     * (0px when there is no banner), so the theme can offset its elements.
     */
    var lastHeight = null;

    function updateHeight() {
        var container = document.getElementById('flaveo-pagebanners');
        var height = container ? Math.round(container.getBoundingClientRect().height) : 0;
        if (height === lastHeight || !document.body) {
            return;
        }
        lastHeight = height;
        document.body.style.setProperty('--flaveo-banner-height', height + 'px');
    }

    function notify(banner, status) {
        updateHeight();

        var detail = { id: banner ? banner.id : null, status: status };
        var event;
        try {
            event = new CustomEvent('flaveo/pagebanner', { detail: detail });
        } catch (e) {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('flaveo/pagebanner', false, false, detail);
        }
        document.dispatchEvent(event);

        // meublez-vous theme: recompute the menu position after a height change
        if (typeof window.calculateMenuPosition === 'function') {
            window.calculateMenuPosition();
        }
    }

    function initClose(banner) {
        var button = banner.querySelector('.flaveo-pagebanner__close');
        if (!button) {
            return;
        }
        button.addEventListener('click', function (e) {
            e.preventDefault();
            setCookie(banner.getAttribute('data-cookie'), parseInt(banner.getAttribute('data-cookie-days'), 10) || 0);
            banner.parentNode.removeChild(banner);
            notify(banner, 'close');
        });
    }

    /**
     * Builds a seamless loop: the track holds two identical groups, each at
     * least as wide as the viewport, and slides by -50%.
     */
    function buildMarquee(banner) {
        var viewport = banner.querySelector('.flaveo-pagebanner__marquee');
        var track = banner.querySelector('.flaveo-pagebanner__track');
        if (!viewport || !track) {
            return;
        }
        if (!banner._fpbItem) {
            banner._fpbItem = track.querySelector('.flaveo-pagebanner__item').cloneNode(true);
        }

        banner.classList.remove('is-running');
        track.innerHTML = '';

        var source = banner._fpbItem;
        var first = source.cloneNode(true);
        track.appendChild(first);

        var itemWidth = first.getBoundingClientRect().width || 1;
        var available = viewport.getBoundingClientRect().width;
        var perGroup = Math.max(1, Math.ceil(available / itemWidth));
        var i;

        for (i = 1; i < perGroup * 2; i++) {
            var clone = source.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true');
            Array.prototype.forEach.call(clone.querySelectorAll('a, button'), function (el) {
                el.setAttribute('tabindex', '-1');
            });
            track.appendChild(clone);
        }

        if (reducedMotion) {
            return;
        }

        var speed = parseInt(banner.getAttribute('data-speed'), 10) || 60;
        var duration = (itemWidth * perGroup) / speed;
        banner.style.setProperty('--fpb-duration', duration.toFixed(2) + 's');
        banner.classList.add('is-running');
    }

    function init() {
        var banners = document.querySelectorAll('.flaveo-pagebanner');
        var marquees = [];
        var container = document.getElementById('flaveo-pagebanners');

        updateHeight();
        if (container) {
            if (window.ResizeObserver) {
                new ResizeObserver(updateHeight).observe(container);
            } else {
                window.addEventListener('resize', updateHeight);
                window.addEventListener('load', updateHeight);
            }
        }

        Array.prototype.forEach.call(banners, function (banner) {
            initClose(banner);
            if (banner.classList.contains('flaveo-pagebanner--marquee')) {
                marquees.push(banner);
                buildMarquee(banner);
            }
        });

        if (marquees.length) {
            var lastWidth = window.innerWidth;
            var timer;
            window.addEventListener('resize', function () {
                if (window.innerWidth === lastWidth) {
                    return;
                }
                lastWidth = window.innerWidth;
                clearTimeout(timer);
                timer = setTimeout(function () {
                    marquees.forEach(function (banner) {
                        if (banner.parentNode) {
                            buildMarquee(banner);
                        }
                    });
                }, 200);
            });

            // Web fonts may change the text width once loaded
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(function () {
                    marquees.forEach(buildMarquee);
                });
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
