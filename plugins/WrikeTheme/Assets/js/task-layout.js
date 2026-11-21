/* WrikeTheme Task Layout Manager
 * Native HTML5 drag-and-drop. Controls remain inside the original Kanboard form,
 * so names, validation, CSRF token and submit buttons are not replaced.
 */
(function ($) {
    'use strict';

    var STORAGE_KEY = 'wrikeThemeTaskLayoutV1';
    var FIELD_GROUPS = [
        { id: 'top', title: 'بالای فرم', hint: 'Title، Description و Definition of Done', keywords: ['title', 'description', 'definition of done', 'شرح', 'عنوان'] },
        { id: 'left', title: 'ستون چپ', hint: 'تاریخ‌ها', keywords: ['start date', 'due date', 'date', 'تاریخ شروع', 'تاریخ سررسید', 'سررسید'] },
        { id: 'right', title: 'ستون راست', hint: 'مسئول، دسته‌بندی و زمان', keywords: ['assignee', 'category', 'reference', 'estimate', 'time spent', 'priority', 'مسئول', 'دسته', 'ارجاع', 'زمان', 'اولویت'] },
        { id: 'extra', title: 'سایر فیلدها', hint: 'فیلدهای دیگر فرم', keywords: [] }
    ];

    function cleanText(value) {
        return String(value || '').replace(/[*:：]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function fieldLabel(control, form) {
        var label = null;
        if (control.id) {
            label = Array.prototype.find.call(form.querySelectorAll('label[for]'), function (item) {
                return item.getAttribute('for') === control.id;
            });
        }
        if (!label && control.parentElement) label = control.parentElement.querySelector('label');
        return cleanText(label ? label.textContent : (control.getAttribute('aria-label') || control.name || control.id));
    }

    function fieldWrapper(control, form, index) {
        var label = null;
        if (control.id) {
            label = Array.prototype.find.call(form.querySelectorAll('label[for]'), function (item) {
                return item.getAttribute('for') === control.id;
            });
        }
        var candidates = ['.form-group', '.form-field', '.form-row', '.form-item', '.text-editor'];
        for (var i = 0; i < candidates.length; i++) {
            var found = control.closest(candidates[i]);
            if (found && form.contains(found) && !found.matches('.form-actions')) {
                if (label && !found.contains(label)) {
                    var grouped = document.createElement('div');
                    grouped.className = 'wrike-layout-generated-field';
                    label.parentNode.insertBefore(grouped, label);
                    grouped.appendChild(label);
                    grouped.appendChild(found);
                    return grouped;
                }
                return found;
            }
        }

        // Kanboard templates can render a label and its control as adjacent
        // siblings without a wrapper. Group that pair without cloning either
        // node, preserving the actual input element and its event handlers.
        var wrapper = document.createElement('div');
        wrapper.className = 'wrike-layout-generated-field';
        var anchor = label || control;
        anchor.parentNode.insertBefore(wrapper, anchor);
        if (label && label.parentNode === anchor.parentNode) wrapper.appendChild(label);
        wrapper.appendChild(control);

        // Keep directly-associated help/error text with the field.
        var next = wrapper.nextSibling;
        while (next && next.nodeType === 1 &&
               (next.classList.contains('form-help') || next.classList.contains('help-block') ||
                next.classList.contains('form-errors') || next.classList.contains('form-error-message'))) {
            var move = next;
            next = next.nextSibling;
            wrapper.appendChild(move);
        }
        return wrapper;
    }

    function getTaskForm(root) {
        var forms = (root || document).querySelectorAll('form');
        for (var i = 0; i < forms.length; i++) {
            var f = forms[i];
            if (f.querySelector('.task-form-container') ||
                f.querySelector('[name="title"]') && (f.querySelector('[name="description"]') || f.querySelector('textarea'))) {
                return f;
            }
        }
        return null;
    }

    function loadLayout() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); }
        catch (e) { return {}; }
    }

    function saveLayout(form) {
        var state = {};
        form.querySelectorAll('.wrike-layout-zone').forEach(function (zone) {
            state[zone.getAttribute('data-zone')] = [];
            zone.querySelectorAll('.wrike-layout-field').forEach(function (field) {
                state[zone.getAttribute('data-zone')].push(field.getAttribute('data-field-key'));
            });
        });
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch (e) {}
    }

    function classify(label) {
        for (var i = 0; i < FIELD_GROUPS.length; i++) {
            var group = FIELD_GROUPS[i];
            for (var j = 0; j < group.keywords.length; j++) {
                if (label.indexOf(group.keywords[j]) !== -1) return group.id;
            }
        }
        return 'extra';
    }

    function buildManager(form) {
        if (form.querySelector('.wrike-layout-manager')) return;
        var container = form.querySelector('.task-form-container') || form;
        var controls = Array.prototype.slice.call(container.querySelectorAll('input:not([type=hidden]):not([type=submit]):not([type=button]), select, textarea'));
        var fields = [];
        var seen = [];

        controls.forEach(function (control, index) {
            if (control.closest('.form-actions, .js-submit-buttons-rendered, .wrike-layout-manager')) return;
            var wrapper = fieldWrapper(control, form, index);
            if (!wrapper || wrapper === form || wrapper.matches('.task-form-container, .task-form-main-column, .task-form-secondary-column, .task-form-bottom')) return;
            if (seen.indexOf(wrapper) !== -1) return;
            seen.push(wrapper);
            var label = fieldLabel(control, form);
            if (!label || label === 'csrf_token') label = cleanText(control.name || control.id || ('field ' + (index + 1)));
            var key = 'wrike-field-' + index;
            fields.push({ wrapper: wrapper, label: label, key: key, zone: classify(label) });
        });

        if (!fields.length) return;

        var manager = document.createElement('section');
        manager.className = 'wrike-layout-manager';
        manager.innerHTML =
            '<div class="wrike-layout-toolbar">' +
              '<div><strong>مدیریت چیدمان فرم</strong><small>فیلدها را با دستگیره بگیر و به بخش دلخواه بکش.</small></div>' +
              '<div class="wrike-layout-toolbar-actions">' +
                '<button type="button" class="wrike-layout-reset">بازگشت به پیش‌فرض</button>' +
                '<button type="button" class="wrike-layout-done">پایان تنظیم</button>' +
              '</div>' +
            '</div>' +
            '<div class="wrike-layout-zones"></div>';

        var zonesHost = manager.querySelector('.wrike-layout-zones');
        FIELD_GROUPS.forEach(function (group) {
            var zone = document.createElement('div');
            zone.className = 'wrike-layout-zone wrike-layout-zone-' + group.id;
            zone.setAttribute('data-zone', group.id);
            zone.innerHTML = '<div class="wrike-layout-zone-heading"><strong>' + group.title + '</strong><small>' + group.hint + '</small></div><div class="wrike-layout-zone-items"></div>';
            zonesHost.appendChild(zone);
        });

        // Insert the manager before the native task fields; the original form and
        // submit controls are kept intact.
        container.insertBefore(manager, container.firstChild);

        fields.forEach(function (field, index) {
            field.wrapper.classList.add('wrike-layout-field');
            field.wrapper.setAttribute('data-field-key', field.key);
            field.wrapper.setAttribute('data-field-label', field.label);
            field.wrapper.setAttribute('draggable', 'false');
            var handle = document.createElement('button');
            handle.type = 'button';
            handle.className = 'wrike-layout-drag-handle';
            handle.setAttribute('aria-label', 'جابجایی ' + field.label);
            handle.title = 'برای جابجایی بکشید';
            handle.textContent = '⠿';
            field.wrapper.insertBefore(handle, field.wrapper.firstChild);
            var initialZone = manager.querySelector('[data-zone="' + field.zone + '"] .wrike-layout-zone-items');
            initialZone.appendChild(field.wrapper);
        });

        // Collapse the original Kanboard columns only when all of their
        // editable controls have been moved into the new layout zones.
        container.querySelectorAll('.task-form-main-column, .task-form-secondary-column').forEach(function (column) {
            var remaining = column.querySelector('input:not([type=hidden]), select, textarea');
            if (!remaining) column.classList.add('wrike-layout-empty-column');
        });

        // Restore saved order only when the saved field still exists.
        var saved = loadLayout();
        Object.keys(saved).forEach(function (zoneId) {
            var zone = manager.querySelector('[data-zone="' + zoneId + '"] .wrike-layout-zone-items');
            if (!zone || !Array.isArray(saved[zoneId])) return;
            saved[zoneId].forEach(function (key) {
                var field = fields.find(function (item) { return item.key === key; });
                if (field) zone.appendChild(field.wrapper);
            });
        });

        manager.querySelectorAll('.wrike-layout-zone').forEach(function (zone) {
            var items = zone.querySelector('.wrike-layout-zone-items');
            items.addEventListener('dragover', function (event) {
                if (!manager.classList.contains('is-editing')) return;
                event.preventDefault();
                zone.classList.add('is-drop-target');
            });
            items.addEventListener('dragleave', function () { zone.classList.remove('is-drop-target'); });
            items.addEventListener('drop', function (event) {
                if (!manager.classList.contains('is-editing')) return;
                event.preventDefault();
                zone.classList.remove('is-drop-target');
                var key = event.dataTransfer.getData('text/plain');
                var moving = manager.querySelector('[data-field-key="' + window.CSS.escape(key) + '"]');
                if (moving) {
                    items.appendChild(moving);
                    saveLayout(form);
                }
            });
        });

        manager.addEventListener('dragstart', function (event) {
            var handle = event.target.closest('.wrike-layout-drag-handle');
            if (!handle || !manager.classList.contains('is-editing')) { event.preventDefault(); return; }
            var field = handle.closest('.wrike-layout-field');
            event.dataTransfer.setData('text/plain', field.getAttribute('data-field-key'));
            event.dataTransfer.effectAllowed = 'move';
            field.classList.add('is-dragging');
        });
        manager.addEventListener('dragend', function () {
            manager.querySelectorAll('.is-dragging').forEach(function (el) { el.classList.remove('is-dragging'); });
            manager.querySelectorAll('.is-drop-target').forEach(function (el) { el.classList.remove('is-drop-target'); });
        });

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'wrike-layout-open';
        toggle.innerHTML = '<span aria-hidden="true">⠿</span> تنظیم چیدمان';
        manager.parentNode.insertBefore(toggle, manager);
        toggle.addEventListener('click', function () {
            manager.classList.add('is-editing');
            toggle.hidden = true;
        });
        manager.querySelector('.wrike-layout-done').addEventListener('click', function () {
            saveLayout(form);
            manager.classList.remove('is-editing');
            toggle.hidden = false;
        });
        manager.querySelector('.wrike-layout-reset').addEventListener('click', function () {
            try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
            fields.forEach(function (field) {
                var zoneId = classify(field.label);
                var zone = manager.querySelector('[data-zone="' + zoneId + '"] .wrike-layout-zone-items');
                if (zone) zone.appendChild(field.wrapper);
            });
        });
    }

    function scan(root) {
        var form = getTaskForm(root);
        if (form) buildManager(form);
    }

    $(function () {
        scan(document);
        if (window.MutationObserver && document.body) {
            var observer = new MutationObserver(function (records) {
                for (var i = 0; i < records.length; i++) {
                    if (records[i].addedNodes && records[i].addedNodes.length) {
                        scan(document);
                        break;
                    }
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
        $(document).on('ajaxComplete', function () { scan(document); });
    });
})(jQuery);
