(function () {
    var THEMES = ['dark', 'light', 'acrylic', 'acrylic-midnight', 'acrylic-ember', 'acrylic-forest'];
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
        var url = customBackgrounds[name] ? 'url("' + customBackgrounds[name] + '")' : '';
        
        // Apply to both html and body to be absolutely sure
        document.documentElement.style.setProperty('--theme-bg', url);
        if (document.body) {
            document.body.style.setProperty('--theme-bg', url);
        }
    }

    function applyTheme(name) {
        if (THEMES.indexOf(name) === -1) name = DEFAULT;
        var link = document.getElementById('theme-stylesheet');
        if (link) link.href = 'css/themes/' + name + '.css';
        
        if (document.body) {
            document.body.className = getLangClass() + ' theme-' + name;
        }
        
        applyBackground(name);
        setCookieTheme(name);
        
        var els = document.querySelectorAll('[data-theme]');
        for (var i = 0; i < els.length; i++) {
            els[i].classList.toggle('active', els[i].getAttribute('data-theme') === name);
        }
    }

    // 1. Start fetching custom backgrounds immediately
    if (window.fetch) {
        fetch('api/theme-backgrounds.php')
            .then(function (r) { return r.ok ? r.json() : {}; })
            .then(function (data) {
                customBackgrounds = data || {};
                applyBackground(getCookieTheme() || DEFAULT);
            })
            .catch(function () {});
    }

    // 2. Apply theme immediately to prevent FOUC
    applyTheme(getCookieTheme() || DEFAULT);

    // 3. Setup event listeners
    document.addEventListener('DOMContentLoaded', function () {
        var els = document.querySelectorAll('[data-theme]');
        for (var i = 0; i < els.length; i++) {
            (function (el) {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    applyTheme(el.getAttribute('data-theme'));
                });
            })(els[i]);
        }
        applyTheme(getCookieTheme() || DEFAULT);
    });
})();
