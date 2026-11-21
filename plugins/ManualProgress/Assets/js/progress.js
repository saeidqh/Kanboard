/**
 * Manual Progress — prompt when a task leaves Done
 */
(function ($) {
    'use strict';

    function saveProgress(taskId, projectId, pct, csrf) {
        var url = $('body').data('status-url') || window.location.href;
        // Build plugin URL relative to kanboard root
        var base = window.location.pathname.replace(/\/+$/, '');
        // Prefer query-string form for compatibility
        var saveUrl = (base.indexOf('index.php') !== -1 ? base : (base + '/index.php')) +
            '?controller=ManualProgressController&action=save&plugin=ManualProgress&task_id=' +
            encodeURIComponent(taskId) + '&project_id=' + encodeURIComponent(projectId);

        // Kanboard pretty URLs
        if ($('a[href*="ManualProgress"]').length) {
            /* keep query form — works with both modes */
        }

        return $.ajax({
            url: saveUrl,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: {
                manual_progress: pct,
                csrf_token: csrf || ($('input[name="csrf_token"]').val() || '')
            }
        });
    }

    function askProgress($wrap) {
        var taskId = $wrap.data('task-id');
        var projectId = $wrap.data('project-id');
        if (!taskId) return;

        var current = $wrap.data('progress');
        var suggested = (current === '' || current === null || typeof current === 'undefined') ? 90 : current;
        var answer = window.prompt('Progress percentage (0–100)\nThis task left Done. Enter the progress to show on the board:', String(suggested));
        if (answer === null) return;
        var pct = parseInt(answer, 10);
        if (isNaN(pct) || pct < 0 || pct > 100) {
            window.alert('Please enter a number between 0 and 100.');
            return;
        }

        saveProgress(taskId, projectId, pct)
            .done(function () {
                $wrap.removeClass('mp-needs-prompt');
                $wrap.attr('data-needs-prompt', '0');
                $wrap.data('needs-prompt', 0);
                $wrap.data('progress', pct);
                $wrap.find('.mp-bar-fill').css('width', pct + '%');
                $wrap.find('.mp-bar-label').html('<span>' + pct + '%</span>');
                $wrap.attr('title', 'Progress: ' + pct + '%');
            })
            .fail(function () {
                // Fallback: full page form on task view
                window.location.href = '?controller=TaskViewController&action=show&task_id=' + taskId + '&project_id=' + projectId;
            });
    }

    function scanPrompts() {
        $('.mp-bar-wrap.mp-needs-prompt').each(function () {
            // Only auto-prompt once per card per page load
            var $w = $(this);
            if ($w.data('mp-asked')) return;
            $w.data('mp-asked', 1);
            // Slight delay so board finishes rendering after drag
            setTimeout(function () { askProgress($w); }, 250);
        });
    }

    $(function () {
        scanPrompts();

        $(document).on('click', '.mp-bar-wrap.mp-needs-prompt', function (e) {
            e.preventDefault();
            e.stopPropagation();
            askProgress($(this));
        });

        $(document).on('ajaxComplete', function () {
            setTimeout(scanPrompts, 120);
        });
    });
})(jQuery);
