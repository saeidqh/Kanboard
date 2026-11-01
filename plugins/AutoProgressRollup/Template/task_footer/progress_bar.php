<?php
/**
 * Smart Progress Bar - نمایش روی کارت بورد
 * کلید متادیتا: smart_progress
 */

$smartProgress = $this->task->taskMetadataModel->get($task['id'], 'smart_progress', null);
$source        = $this->task->taskMetadataModel->get($task['id'], 'smart_progress_source', '');

// اگر هنوز محاسبه نشده، صفر در نظر بگیر
$progressValue = ($smartProgress !== null && $smartProgress !== '') 
    ? (float) $smartProgress 
    : 0.0;

// محدود کردن بین ۰ تا ۱۰۰ برای نمایش
$displayProgress = max(0, min(100, $progressValue));

// انتخاب رنگ بر اساس درصد
$barColor = '#3498db'; // آبی پیش‌فرض

if ($displayProgress >= 100) {
    $barColor = '#27ae60'; // سبز کامل (Done / Closed)
} elseif ($displayProgress >= 75) {
    $barColor = '#2ecc71'; // سبز روشن
} elseif ($displayProgress >= 40) {
    $barColor = '#f39c12'; // نارنجی
} elseif ($displayProgress > 0) {
    $barColor = '#e67e22'; // نارنجی تیره‌تر
} else {
    $barColor = '#bdc3c7'; // خاکستری برای صفر
}
?>

<div class="smart-progress-wrapper" 
     style="margin-top: 6px; width: 100%;"
     title="Smart Progress: <?= number_format($displayProgress, 1) ?>%<?= $source ? ' ('.$source.')' : '' ?>">
    
    <div class="smart-progress-track" 
         style="background: #ecf0f1; 
                border-radius: 4px; 
                height: 6px; 
                overflow: hidden; 
                width: 100%;
                box-shadow: inset 0 1px 2px rgba(0,0,0,0.08);">
        
        <div class="smart-progress-fill" 
             style="width: <?= $displayProgress ?>%; 
                    background-color: <?= $barColor ?>; 
                    height: 100%; 
                    border-radius: 4px;
                    transition: width 0.35s ease, background-color 0.3s ease;">
        </div>
    </div>
</div>