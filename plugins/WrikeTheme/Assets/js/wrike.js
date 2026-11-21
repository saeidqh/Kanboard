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


/* --- Task form: light field order (Reference after Category, dates together) ---
 * Minimal, non-destructive moves only. No wrappers rebuild, no drag system.
 */
(function($) {
    'use strict';

    function normalizedLabelText(label) {
        return $.trim($(label).clone().children().remove().end().text())
            .replace(/[:：*]/g, '')
            .replace(/\s+/g, ' ')
            .toLowerCase();
    }

    function fieldName(el) {
        var n = el.getAttribute('name') || '';
        var m = n.match(/\[([^\]]+)\]$/);
        return (m ? m[1] : n).toLowerCase();
    }

    function findTaskFieldByLabel(form, wanted) {
        var found = null;
        $(form).find('label').each(function() {
            var text = normalizedLabelText(this);
            if (text === wanted || text.indexOf(wanted) === 0) {
                found = this;
                return false;
            }
        });
        if (!found) return null;

        var $label = $(found);
        var forId = $label.attr('for');
        var $control = forId ? $(form).find('#' + (window.CSS && CSS.escape ? CSS.escape(forId) : forId)) : $();

        var $wrap = $label.closest('.form-group, .form-field, .form-row, .form-item');
        if (!$wrap.length && $control.length) {
            $wrap = $control.closest('.form-group, .form-field, .form-row, .form-item');
        }
        if (!$wrap.length) {
            $wrap = $label.parent();
        }
        return $wrap.length ? $wrap : null;
    }

    function findTaskFieldByName(form, name) {
        var control = null;
        $(form).find('input, select, textarea').each(function() {
            if (this.type === 'hidden') return;
            if (fieldName(this) === name) {
                control = this;
                return false;
            }
        });
        if (!control) return null;

        var $control = $(control);
        var $wrap = $control.closest('.form-group, .form-field, .form-row, .form-item');
        if ($wrap.length) return $wrap;

        // include label[for] if sibling structure
        var label = null;
        if (control.id) {
            try {
                var safe = (window.CSS && CSS.escape) ? CSS.escape(control.id) : control.id;
                label = form.querySelector('label[for="' + safe + '"]');
            } catch (e) {}
        }
        if (label && label.parentNode === control.parentNode) {
            // return jQuery collection starting at label — move both by inserting label then control stays
            return $(label).add(control);
        }
        return $control;
    }

    function sameColumn($a, $b) {
        if (!$a || !$b || !$a.length || !$b.length) return false;
        if ($a.parent()[0] === $b.parent()[0]) return true;
        var ca = $a.closest('.task-form-main-column, .task-form-secondary-column, .form-column')[0];
        var cb = $b.closest('.task-form-main-column, .task-form-secondary-column, .form-column')[0];
        return !!(ca && cb && ca === cb);
    }

    function arrangeTaskForm(root) {
        $(root).find('form').each(function() {
            var form = this;
            var $form = $(form);
            var isTaskForm = $form.find('[name="title"], [name="category_id"], [name="date_due"], [name="date_started"]').length > 0;
            if (!isTaskForm) return;

            // 1) Reference immediately after Category (same column only)
            var $category = findTaskFieldByLabel(form, 'category') || findTaskFieldByName(form, 'category_id');
            var $reference = findTaskFieldByLabel(form, 'reference') || findTaskFieldByName(form, 'reference');
            if ($category && $reference && $category[0] !== $reference[0] && sameColumn($category, $reference)) {
                if ($reference.prev()[0] !== $category.get(-1) && $reference.prev()[0] !== $category[0]) {
                    $reference.insertAfter($category.last());
                }
            }

            // 2) Due date + Start date together (Start right after Due)
            var $due = findTaskFieldByName(form, 'date_due') || findTaskFieldByLabel(form, 'due date');
            var $start = findTaskFieldByName(form, 'date_started') || findTaskFieldByLabel(form, 'start date');
            if ($due && $start && $due[0] !== $start[0]) {
                // If they are in different columns, move Start next to Due's parent column
                if (!sameColumn($due, $start)) {
                    $start.insertAfter($due.last());
                } else if ($start.prev()[0] !== $due.get(-1) && $start.prev()[0] !== $due[0]) {
                    $start.insertAfter($due.last());
                }
                $due.addClass('wrike-date-field');
                $start.addClass('wrike-date-field');
            }
        });
    }

    $(function() {
        arrangeTaskForm(document);
        if (window.MutationObserver && document.body) {
            var timer = null;
            new MutationObserver(function() {
                clearTimeout(timer);
                timer = setTimeout(function() { arrangeTaskForm(document); }, 80);
            }).observe(document.body, { childList: true, subtree: true });
        }
        $(document).on('ajaxComplete', function() {
            setTimeout(function() { arrangeTaskForm(document); }, 60);
        });
    });
})(jQuery);


/* --- Accessible show/hide password control on the login form --- */
(function($) {
    'use strict';

    var eyeOpen = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    var eyeClosed = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15 15 0 0 1-3.1 3.8"></path><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.5 0 2.8-.4 4-1"></path></svg>';

    function addPasswordToggle(root) {
        $(root).find('.form-login input[type="password"]').each(function() {
            var input = this;
            if ($(input).data('wrikePasswordToggle')) return;
            $(input).data('wrikePasswordToggle', true);

            var $input = $(input);
            var $wrapper = $('<span class="wrike-password-wrap"></span>');
            $input.wrap($wrapper);
            var $button = $('<button type="button" class="wrike-password-toggle" aria-label="Show password" aria-pressed="false"></button>');
            $button.html(eyeOpen);
            $input.after($button);

            $button.on('click', function() {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                $button.html(show ? eyeClosed : eyeOpen);
                $button.attr('aria-label', show ? 'Hide password' : 'Show password');
                $button.attr('aria-pressed', show ? 'true' : 'false');
                input.focus();
            });
        });
    }

    $(function() {
        addPasswordToggle(document);
        if (window.MutationObserver && document.body) {
            var observer = new MutationObserver(function(records) {
                for (var i = 0; i < records.length; i++) {
                    if (records[i].addedNodes && records[i].addedNodes.length) {
                        addPasswordToggle(document);
                        break;
                    }
                }
            });
            observer.observe(document.body, { childList:true, subtree:true });
        }
    });
})(jQuery);
