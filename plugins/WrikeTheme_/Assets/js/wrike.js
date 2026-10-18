/* WrikeTheme JS */

/* --- Back to top ---------------------------------------- */
$(window).scroll(function() {
    var height = $(window).scrollTop();
    if (height > 200) {
        $('#backToTop').fadeIn();
    } else {
        $('#backToTop').fadeOut();
    }
});

$(document).ready(function() {
    $("#backToTop").click(function(event) {
        event.preventDefault();
        $("html, body").animate({ scrollTop: 0 }, "slow");
        return false;
    });

    /* --- Night mode ----------------------------------------
     * Persistence hierarchy (most to least reliable):
     *
     *   1. Cookie (COOKIE_NAME) — JS sets it on this browser when the user
     *      clicks the toggle.  PHP reads it on every subsequent request in
     *      layout.php, so the correct mode is applied server-side with NO
     *      flash.  Works even when the DB write fails.
     *
     *   2. DB (userMetadataModel) — for cross-device sync.  Written via AJAX
     *      on toggle; read by layout.php when no cookie is present (e.g. first
     *      load on a new browser/device after setting the preference elsewhere).
     *
     *   3. localStorage — kept only as a quick client-side read after toggle.
     *      NOT used to restore state on page load (the server handles that).
     *
     * Bug history: if we blindly sync localStorage FROM the server value on
     * every page load, a failed DB write would set server→'0' which would
     * overwrite the user's localStorage '1', breaking the fallback entirely.
     * The cookie approach avoids this: the server reads the cookie (set by JS)
     * and always renders the correct class without depending on the DB.
     * ------------------------------------------------------- */
    var STORAGE_KEY = 'wrikeThemeNightMode';
    var COOKIE_NAME = 'wrikeThemeNightMode';
    var toggleUrl   = $('body').data('night-toggle-url') || '';
    var serverNight = $('body').data('night-mode');   // '1' or '0' from layout.php

    /* helpers */
    function getNightCookie() {
        var m = document.cookie.match(/(?:^|;\s*)wrikeThemeNightMode=([^;]*)/);
        return m ? m[1] : null;
    }

    function setNightCookie(val) {
        var exp = new Date();
        exp.setFullYear(exp.getFullYear() + 1);
        document.cookie = COOKIE_NAME + '=' + val +
            '; expires=' + exp.toUTCString() + '; path=/; SameSite=Lax';
    }

    function applyMode(night) {
        if (night) {
            $('body').addClass('night-mode');
            $('#wrike-night-toggle')
                .attr('title', 'Light mode')
                .find('i').removeClass('fa-moon-o').addClass('fa-sun-o');
        } else {
            $('body').removeClass('night-mode');
            $('#wrike-night-toggle')
                .attr('title', 'Night mode')
                .find('i').removeClass('fa-sun-o').addClass('fa-moon-o');
        }
    }

    // Sync localStorage with the authoritative server value ONLY when there is
    // no cookie yet (meaning this browser has never toggled before).
    // If a cookie exists the user already toggled on this browser — the cookie
    // IS the truth; overwriting localStorage with a possibly-stale server value
    // (from a failed DB write) would silently break the fallback.
    if (getNightCookie() === null && serverNight !== undefined && serverNight !== '') {
        localStorage.setItem(STORAGE_KEY, serverNight);
    }

    // Toggle button click
    $(document).on('click', '#wrike-night-toggle', function(e) {
        e.preventDefault();
        var isNight  = $('body').hasClass('night-mode');
        var newNight = !isNight;
        var newVal   = newNight ? '1' : '0';
        var oldVal   = isNight  ? '1' : '0';

        // 1. Optimistic UI update — instant, no wait
        applyMode(newNight);
        localStorage.setItem(STORAGE_KEY, newVal);

        // 2. Cookie — written immediately; PHP reads it on the next page load.
        //    This makes persistence work regardless of whether the DB write
        //    succeeds or not.
        setNightCookie(newVal);

        // 3. DB — best-effort AJAX for cross-device sync.
        //    Failure here is OK: cookie keeps the preference on this browser.
        if (toggleUrl) {
            $.post(toggleUrl, { csrf_token: $('[name=csrf_token]').val() || '' })
             .fail(function() {
                 // Network/server error: roll back UI and cookie
                 applyMode(isNight);
                 localStorage.setItem(STORAGE_KEY, oldVal);
                 setNightCookie(oldVal);
             });
        }
    });

    // OS dark-mode preference: only apply if the user has no saved preference
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!toggleUrl && localStorage.getItem(STORAGE_KEY) === null) {
                applyMode(e.matches);
            }
        });
    }
});
