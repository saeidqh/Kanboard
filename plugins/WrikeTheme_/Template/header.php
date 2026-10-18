<?php global $themeWrikeConfig; ?>

<?php $_title = $this->render('WrikeTheme:layout/header/title', array(
    'project' => isset($project) ? $project : null,
    'task'    => isset($task) ? $task : null,
    'description' => isset($description) ? $description : null,
    'title'   => $title,
)) ?>

<?php $_top_right_corner = implode('&nbsp;', array(
    $this->render('header/user_notifications'),
    $this->render('header/creation_dropdown'),
    $this->render('header/user_dropdown'),
)) ?>

<?php if (!isset($themeWrikeConfig['backgroundColorHeader'])): ?>
<header>
<?php else: ?>
<header style="background:<?= $themeWrikeConfig['backgroundColorHeader'] ?>">
<?php endif ?>
    <div class="title-container">
        <?= $_title ?>
    </div>
    <div class="board-selector-container">
        <?php if (! empty($board_selector)): ?>
            <?= $this->render('header/board_selector', array('board_selector' => $board_selector)) ?>
        <?php endif ?>
    </div>
    <div class="menus-container">
        <a href="#" id="wrike-night-toggle" title="Night mode" class="wrike-night-btn">
            <i class="fa fa-moon-o" aria-hidden="true"></i>
        </a>
        <?= $_top_right_corner ?>
    </div>
</header>
