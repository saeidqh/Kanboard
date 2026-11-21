<style id="ahvaz-hide-fields">
label[for="form-score"],
#form-score,
input[name="score"] {
    display: none !important;
}
label[for="form-color_id"],
#form-color_id,
select[name="color_id"],
select[name="color_id"] + .select2,
select#form-color_id + span.select2,
select#form-color_id + span.select2-container {
    display: none !important;
}
.ahvaz-err {
    color: #c00 !important;
    font-size: 12px;
    margin: 4px 0 0;
}
input.ahvaz-invalid,
select.ahvaz-invalid,
textarea.ahvaz-invalid {
    border-color: #c00 !important;
    background: #fff5f5 !important;
}
</style>
<script>
(function () {
    'use strict';

    /* ---------- hide score/color (no infinite loop) ---------- */
    var hideBusy = false, hideLast = 0;
    function hide(el) {
        if (!el || el.classList.contains('ahvaz-hidden-done')) return;
        el.style.setProperty('display', 'none', 'important');
        el.classList.add('ahvaz-hidden-done');
    }
    function hideFields() {
        if (hideBusy) return;
        var now = Date.now();
        if (now - hideLast < 300) return;
        hideBusy = true;
        hideLast = now;
        try {
            document.querySelectorAll('label[for="form-score"], #form-score, input[name="score"]').forEach(hide);
            document.querySelectorAll('label[for="form-color_id"]').forEach(hide);
            document.querySelectorAll('#form-color_id, select[name="color_id"]').forEach(function (el) {
                hide(el);
                var n = el.nextElementSibling;
                if (n && n.classList && (n.classList.contains('select2') || n.classList.contains('select2-container'))) hide(n);
            });
            document.querySelectorAll('span.select2-container').forEach(function (span) {
                if (!span.classList.contains('ahvaz-hidden-done') &&
                    span.querySelector('.color-picker-option, .color-picker-square, .color-picker-label')) {
                    hide(span);
                }
            });
        } finally {
            hideBusy = false;
        }
    }

    /* ---------- required fields ---------- */
    var LABEL_MAP = {
        1: 'Document number',
        2: 'Reference',
        3: 'Letter number',
        4: 'MOM number',
        5: 'Department'
    };

    function taskForm() {
        var box = document.querySelector('.task-form-container');
        return box ? box.closest('form') : null;
    }
    function elCategory() {
        return document.getElementById('form-category_id') || document.querySelector('.task-form-container select[name="category_id"]');
    }
    function elDoD() {
        return document.getElementById('form-definition_of_done') || document.querySelector('.task-form-container textarea[name="definition_of_done"]');
    }
    function elReference() {
        var sel = document.querySelector('.task-form-container select[name="reference"]');
        if (sel) return sel;
        return document.getElementById('form-reference') || document.querySelector('.task-form-container input[name="reference"]');
    }

    function clearErr() {
        document.querySelectorAll('.ahvaz-err').forEach(function (n) { if (n.parentNode) n.parentNode.removeChild(n); });
        document.querySelectorAll('.ahvaz-invalid').forEach(function (n) { n.classList.remove('ahvaz-invalid'); });
    }
    function err(el, msg) {
        if (!el) return;
        el.classList.add('ahvaz-invalid');
        var p = document.createElement('div');
        p.className = 'ahvaz-err';
        p.textContent = msg;
        if (el.parentNode) el.parentNode.insertBefore(p, el.nextSibling);
    }
    function emptyVal(el) {
        if (!el) return true;
        return String(el.value || '').trim() === '';
    }
    function emptyCat(el) {
        if (!el) return true;
        var v = el.value;
        return v === '' || v === '0' || v == null;
    }

    function star(label) {
        if (!label || label.querySelector('.ahvaz-req')) return;
        var s = document.createElement('span');
        s.className = 'ahvaz-req';
        s.style.color = '#c00';
        s.textContent = ' *';
        label.appendChild(s);
    }

    function markStars() {
        star(document.querySelector('label[for="form-category_id"]'));
        star(document.querySelector('label[for="form-definition_of_done"]'));
        var cat = elCategory();
        var catId = cat ? parseInt(cat.value, 10) || 0 : 0;
        var lab = document.querySelector('label[for="form-reference"]');
        if (lab) {
            lab.innerHTML = (LABEL_MAP[catId] || 'Reference') + ' <span class="ahvaz-req" style="color:#c00;">*</span>';
        }
        var dod = elDoD(); if (dod) dod.setAttribute('required', 'required');
        var ref = elReference(); if (ref) ref.setAttribute('required', 'required');
        var c = elCategory(); if (c) c.setAttribute('required', 'required');
    }

    /** returns true if valid */
    function validate() {
        clearErr();
        markStars();
        var dod = elDoD(), cat = elCategory(), ref = elReference();
        var ok = true;
        if (emptyVal(dod)) { err(dod, 'Definition of Done is required.'); ok = false; }
        if (emptyCat(cat)) { err(cat, 'Category is required.'); ok = false; }
        if (emptyVal(ref)) {
            var catId = cat ? parseInt(cat.value, 10) || 0 : 0;
            err(ref, (LABEL_MAP[catId] || 'Reference') + ' is required.');
            ok = false;
        }
        if (!ok) {
            try { alert('Please fill required fields: Definition of Done, Category, Reference.'); } catch (e) {}
            if (dod && emptyVal(dod)) dod.focus();
            else if (cat && emptyCat(cat)) cat.focus();
            else if (ref) ref.focus();
        }
        return ok;
    }

    function onSubmit(e) {
        if (!taskForm()) return;
        // only for task create/edit forms
        var form = e.target;
        if (!form || !form.querySelector) return;
        if (!form.querySelector('.task-form-container, #form-definition_of_done, textarea[name="definition_of_done"]')) return;
        if (!validate()) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
            return false;
        }
    }

    function onClick(e) {
        var t = e.target;
        if (!t) return;
        // submit buttons inside task form
        var isSubmit = (t.type === 'submit') || (t.tagName === 'BUTTON' && t.type !== 'button') ||
            (t.classList && (t.classList.contains('form-submit') || t.classList.contains('btn-blue')));
        if (!isSubmit) {
            // clicked text inside button
            var btn = t.closest && t.closest('button, input[type="submit"]');
            if (btn) { t = btn; isSubmit = true; }
        }
        if (!isSubmit) return;
        var form = t.form || (t.closest && t.closest('form'));
        if (!form || !form.querySelector('.task-form-container, #form-definition_of_done, textarea[name="definition_of_done"]')) return;
        if (!validate()) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
            return false;
        }
    }

    // Department select when category=5
    // Department select when category=5
    function updateRef() {
        var cat = elCategory();
        if (!cat) return;
        var catId = parseInt(cat.value, 10) || 0;
        markStars();
        
        var refInput = document.querySelector('input[name="reference"], #form-reference');
        
        if (catId === 5 && refInput && refInput.dataset.ahvazMode !== 'select2') {
            var projectId = (document.getElementById('ahvaz-project-id') || {}).value;
            
            // بررسی دقیق‌تر برای بارگذاری کتابخانه
            if (typeof jQuery === 'undefined') {
                console.error('jQuery is not loaded');
                return;
            }
            if (!jQuery.fn.select2 && !jQuery.fn.select2) {
                console.error('Select2 is not loaded');
                return;
            }
            
            var select = document.createElement('select');
            select.name = 'reference';
            select.id = 'form-reference';
            select.required = true;
            select.style.width = '100%';
            select.dataset.ahvazMode = 'select2';
            
            // قرار دادن select قبل از input مخفی
            refInput.parentNode.insertBefore(select, refInput);
            
            // مخفی کردن input اصلی و حذف name آن
            refInput.style.display = 'none';
            refInput.removeAttribute('name');
            refInput.dataset.ahvazMode = 'hidden';
            
            // فراخوانی Select2
            jQuery(select).select2({
                placeholder: 'Select a department...',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '?controller=DepartmentController&action=json&plugin=AhvazTaskFields&project_id=' + projectId,
                    dataType: 'json', 
                    delay: 250, 
                    processResults: function (d) { 
                        return d; 
                    }, 
                    cache: true
                },
                minimumInputLength: 0
            });
        }
    }

    function bindWhenModal() {
        hideFields();
        markStars();
        var cat = elCategory();
        if (cat && cat.dataset.ahvazBound !== '1') {
            cat.dataset.ahvazBound = '1';
            cat.addEventListener('change', updateRef);
            updateRef();
        }
    }

    function boot() {
        // capture phase so we run BEFORE Kanboard AJAX handlers
        document.addEventListener('submit', onSubmit, true);
        document.addEventListener('click', onClick, true);

        document.addEventListener('click', function () {
            setTimeout(bindWhenModal, 200);
            setTimeout(hideFields, 400);
        });

        bindWhenModal();
        hideFields();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
</script>

<script>
// Extra binder for Kanboard jQuery AJAX forms
(function () {
  function run() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    $(document).off('submit.ahvazReq').on('submit.ahvazReq', 'form', function (e) {
      var $f = $(this);
      if (!$f.find('.task-form-container, #form-definition_of_done, textarea[name="definition_of_done"]').length) return;
      var dod = ($f.find('#form-definition_of_done, textarea[name="definition_of_done"]').val() || '').trim();
      var cat = $f.find('#form-category_id, select[name="category_id"]').val();
      var ref = ($f.find('select[name="reference"]').val() || $f.find('#form-reference, input[name="reference"]').val() || '').toString().trim();
      var msgs = [];
      if (!dod) msgs.push('Definition of Done');
      if (!cat || cat === '0') msgs.push('Category');
      if (!ref) msgs.push('Reference');
      if (msgs.length) {
        e.preventDefault();
        e.stopImmediatePropagation();
        alert('Required: ' + msgs.join(', '));
        return false;
      }
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
  else run();
  document.addEventListener('click', function () { setTimeout(run, 300); });
})();
</script>
