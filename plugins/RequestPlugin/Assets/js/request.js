/**
 * Request Plugin
 * - Load project members when project select changes (works inside Kanboard modals)
 */
(function () {
    'use strict';

    function getUsersUrlTemplate() {
        var el = document.getElementById('form-assignee_id');
        if (el && el.getAttribute('data-users-url')) {
            return el.getAttribute('data-users-url');
        }
        // Fallback: classic query-string controller URL
        var base = window.location.origin + window.location.pathname.replace(/\/+$/, '');
        // pathname might be /kanboard/ or /kanboard/index.php
        if (base.indexOf('index.php') === -1) {
            // pretty URL install – still prefer query form for reliability
            base = base.replace(/\/requests.*$/, '') || base;
        }
        return (document.querySelector('body').getAttribute('data-js-base-url') || '/kanboard/') +
            '?controller=RequestController&action=users&plugin=RequestPlugin&project_id=999999';
    }

    function loadUsers(projectId) {
        var assigneeSelect = document.getElementById('form-assignee_id');
        var hint = document.getElementById('req-assignee-hint');
        if (!assigneeSelect) return;

        assigneeSelect.innerHTML = '<option value="">Loading…</option>';
        assigneeSelect.disabled = true;
        if (hint) hint.textContent = 'Loading members…';

        if (!projectId) {
            assigneeSelect.innerHTML = '<option value="">— Select project first —</option>';
            assigneeSelect.disabled = false;
            if (hint) hint.textContent = 'Members of the selected project will appear here';
            return;
        }

        var tpl = getUsersUrlTemplate();
        var url = tpl.split('999999').join(encodeURIComponent(projectId));

        fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (data) {
            var results = (data && data.results) ? data.results : [];
            var html;
            if (!results.length) {
                html = '<option value="">No members found in this project</option>';
                if (hint) hint.textContent = 'No active members found for this project';
            } else {
                html = '<option value="">— Select a person —</option>';
                results.forEach(function (u) {
                    var label = String(u.text || u.name || u.username || ('#' + u.id));
                    html += '<option value="' + u.id + '">' + label.replace(/</g, '&lt;') + '</option>';
                });
                if (hint) hint.textContent = results.length + ' member(s)';
            }
            assigneeSelect.innerHTML = html;
            assigneeSelect.disabled = false;
        })
        .catch(function (err) {
            console.error('RequestPlugin: failed to load users', err, url);
            assigneeSelect.innerHTML = '<option value="">Failed to load users</option>';
            assigneeSelect.disabled = false;
            if (hint) hint.textContent = 'Could not load members. Check Network tab.';
        });
    }

    // Event delegation — works even when form is injected into a modal later
    document.addEventListener('change', function (e) {
        var t = e.target;
        if (t && t.id === 'form-project_id') {
            loadUsers(t.value);
        }
    });

    // If form already present on page load
    function bootIfPresent() {
        var projectSelect = document.getElementById('form-project_id');
        if (projectSelect && projectSelect.value) {
            loadUsers(projectSelect.value);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootIfPresent);
    } else {
        bootIfPresent();
    }

    // Kanboard modal often inserts HTML after ajax; observe DOM
    if (window.MutationObserver) {
        var obs = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var nodes = mutations[i].addedNodes;
                for (var j = 0; j < nodes.length; j++) {
                    var n = nodes[j];
                    if (n.nodeType === 1 && (n.id === 'form-project_id' || (n.querySelector && n.querySelector('#form-project_id')))) {
                        bootIfPresent();
                        return;
                    }
                }
            }
        });
        obs.observe(document.body, { childList: true, subtree: true });
    }
})();
