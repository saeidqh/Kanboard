(function () {
    'use strict';

    var LABEL_MAP = {
        1: 'Document number',
        2: 'Reference',
        3: 'Letter number',
        4: 'MOM number',
        5: 'Department'
    };

    var boundForms = false;

    function getForm() {
        var box = document.querySelector('.task-form-container');
        return box ? box.closest('form') : document.querySelector('form[action*="TaskCreation"], form[action*="TaskModification"]');
    }
    function getCategory() {
        return document.getElementById('form-category_id') || document.querySelector('select[name="category_id"]');
    }
    function getReference() {
        var sel = document.querySelector('select[name="reference"]');
        if (sel && sel.offsetParent !== null) return sel;
        return document.getElementById('form-reference') || document.querySelector('input[name="reference"]');
    }
    function getDoD() {
        return document.getElementById('form-definition_of_done') || document.querySelector('textarea[name="definition_of_done"]');
    }

    function ensureStar(labelEl) {
        if (!labelEl || labelEl.querySelector('.ahvaz-req')) return;
        var star = document.createElement('span');
        star.className = 'ahvaz-req';
        star.style.color = '#c00';
        star.textContent = ' *';
        labelEl.appendChild(star);
    }

    function setReferenceLabel(catId) {
        var lab = document.querySelector('label[for="form-reference"]');
        if (!lab) return;
        var name = LABEL_MAP[catId] || 'Reference';
        lab.innerHTML = name + ' <span class="ahvaz-req" style="color:#c00;">*</span>';
    }

    function markRequired() {
        var cat = getCategory();
        var ref = getReference();
        var dod = getDoD();
        if (cat) {
            cat.setAttribute('required', 'required');
            ensureStar(document.querySelector('label[for="form-category_id"]'));
        }
        if (ref) {
            ref.setAttribute('required', 'required');
            setReferenceLabel(parseInt(cat && cat.value, 10) || 0);
        }
        if (dod) dod.setAttribute('required', 'required');
    }

    function showError(el, msg) {
        if (!el) return;
        el.style.borderColor = '#c00';
        var id = 'ahvaz-err-' + (el.name || el.id || 'x');
        var old = document.getElementById(id);
        if (old) old.parentNode.removeChild(old);
        var p = document.createElement('p');
        p.id = id;
        p.style.color = '#c00';
        p.style.margin = '4px 0 0';
        p.style.fontSize = '12px';
        p.textContent = msg;
        if (el.parentNode) el.parentNode.insertBefore(p, el.nextSibling);
    }

    function clearErrors() {
        var nodes = document.querySelectorAll('[id^="ahvaz-err-"]');
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].parentNode) nodes[i].parentNode.removeChild(nodes[i]);
        }
    }

    function isEmptyCategory(cat) {
        if (!cat) return true;
        var v = cat.value;
        return v === '' || v === '0' || v == null;
    }
    function isEmpty(el) {
        if (!el) return true;
        return String(el.value || '').trim() === '';
    }

    function validateForm(e) {
        clearErrors();
        markRequired();
        var cat = getCategory(), ref = getReference(), dod = getDoD();
        var ok = true;
        if (isEmpty(dod)) { showError(dod, 'Definition of Done is required.'); ok = false; }
        if (isEmptyCategory(cat)) { showError(cat, 'Category is required.'); ok = false; }
        if (isEmpty(ref)) {
            var catId = cat ? parseInt(cat.value, 10) || 0 : 0;
            showError(ref, (LABEL_MAP[catId] || 'Reference') + ' is required.');
            ok = false;
        }
        if (!ok) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        return true;
    }

    function convertToDepartmentSelect(refInput) {
        if (!refInput || refInput.dataset.ahvazMode === 'select2') return;
        var projectId = (document.getElementById('ahvaz-project-id') || {}).value;
        if (!projectId || typeof jQuery === 'undefined' || !jQuery.fn.select2) return;

        var select = document.createElement('select');
        select.name = 'reference';
        select.id = 'form-reference';
        select.required = true;
        select.style.width = '100%';
        select.dataset.ahvazMode = 'select2';
        var currentVal = refInput.value || '';
        refInput.parentNode.insertBefore(select, refInput);
        refInput.style.display = 'none';
        refInput.removeAttribute('name');
        refInput.dataset.ahvazMode = 'hidden';

        jQuery(select).select2({
            placeholder: 'Select a department...',
            allowClear: true,
            width: '100%',
            ajax: {
                url: '?controller=DepartmentController&action=json&plugin=AhvazTaskFields&project_id=' + projectId,
                dataType: 'json',
                delay: 200,
                processResults: function (d) { return d; },
                cache: true
            },
            minimumInputLength: 0
        });
        jQuery.ajax({
            url: '?controller=DepartmentController&action=json&plugin=AhvazTaskFields&project_id=' + projectId,
            dataType: 'json'
        }).done(function (data) {
            (data.results || []).forEach(function (item) {
                jQuery(select).append(new Option(item.text, item.id, false, item.id === currentVal));
            });
            jQuery(select).trigger('change');
        });
    }

    function convertToNormalInput(refInput) {
        if (!refInput) return;
        var select = document.querySelector('select[name="reference"][data-ahvaz-mode="select2"]');
        if (select) {
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                try { jQuery(select).select2('destroy'); } catch (e) {}
            }
            if (select.parentNode) select.parentNode.removeChild(select);
        }
        refInput.style.display = '';
        refInput.setAttribute('name', 'reference');
        refInput.setAttribute('required', 'required');
        refInput.dataset.ahvazMode = 'text';
    }

    function updateReferenceField() {
        var cat = getCategory();
        var refInput = document.getElementById('form-reference') || document.querySelector('input[name="reference"]');
        if (!cat) return;
        var catId = parseInt(cat.value, 10) || 0;
        setReferenceLabel(catId);
        if (catId === 5) convertToDepartmentSelect(refInput);
        else convertToNormalInput(refInput);
        markRequired();
    }

    function bindForm() {
        var form = getForm();
        if (!form || form.dataset.ahvazVal === '1') return;
        form.dataset.ahvazVal = '1';
        form.addEventListener('submit', validateForm, true);
        var cat = getCategory();
        if (cat && cat.dataset.ahvazCat !== '1') {
            cat.dataset.ahvazCat = '1';
            cat.addEventListener('change', updateReferenceField);
        }
        markRequired();
        updateReferenceField();
    }

    function boot() {
        bindForm();
        document.addEventListener('click', function () {
            setTimeout(bindForm, 250);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
