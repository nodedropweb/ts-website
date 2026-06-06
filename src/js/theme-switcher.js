(function () {
    var THEMES = ['dark', 'light', 'acrylic', 'acrylic-midnight', 'acrylic-ember', 'acrylic-forest'];
    var ACRYLIC_THEMES = ['acrylic', 'acrylic-midnight', 'acrylic-ember', 'acrylic-forest'];
    var DEFAULT = 'dark';
    var COOKIE_KEY = 'tswebsite_theme';

    var customBackgrounds = {};

    function getCookieTheme() {
        var match = document.cookie.match(/(?:^|;\s*)tswebsite_theme=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : null;
    }

    function setCookieTheme(name) {
        var d = new Date();
        d.setTime(d.getTime() + 365 * 24 * 60 * 60 * 1000);
        document.cookie = COOKIE_KEY + '=' + encodeURIComponent(name) + '; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
    }

    function getLangClass() {
        var m = document.body.className.match(/\blang\s+(\S+)/);
        return m ? 'lang ' + m[1] : '';
    }

    function applyBackground(name) {
        if (customBackgrounds[name]) {
            document.documentElement.style.setProperty('--theme-bg', 'url(\'' + customBackgrounds[name] + '\')');
        } else {
            document.documentElement.style.removeProperty('--theme-bg');
        }
    }

    function applyTheme(name) {
        if (THEMES.indexOf(name) === -1) name = DEFAULT;
        var link = document.getElementById('theme-stylesheet');
        if (link) link.href = 'css/themes/' + name + '.css';
        document.body.className = getLangClass() + ' theme-' + name;
        applyBackground(name);
        setCookieTheme(name);
        var els = document.querySelectorAll('[data-theme]');
        for (var i = 0; i < els.length; i++) {
            els[i].classList.toggle('active', els[i].getAttribute('data-theme') === name);
        }
    }

    // Apply immediately (prevents FOUC)
    applyTheme(getCookieTheme() || DEFAULT);

    document.addEventListener('DOMContentLoaded', function () {
        // Wire up dropdown buttons
        var els = document.querySelectorAll('[data-theme]');
        for (var i = 0; i < els.length; i++) {
            (function (el) {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    applyTheme(el.getAttribute('data-theme'));
                });
            })(els[i]);
        }

        // Fetch custom backgrounds and re-apply if we're on an acrylic theme
        if (window.fetch) {
            fetch('api/theme-backgrounds.php')
                .then(function (r) { return r.ok ? r.json() : {}; })
                .then(function (data) {
                    customBackgrounds = data || {};
                    var current = getCookieTheme() || DEFAULT;
                    if (ACRYLIC_THEMES.indexOf(current) !== -1) {
                        applyBackground(current);
                    }
                })
                .catch(function () {});
        }

        applyTheme(getCookieTheme() || DEFAULT);
    });
})();
