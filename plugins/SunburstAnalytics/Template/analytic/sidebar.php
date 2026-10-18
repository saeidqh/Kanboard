<li <?= $this->app->checkMenuSelection('SunburstController', 'show', 'SunburstAnalytics') ?>>
    <?= $this->url->link('☀️ ' . t('Sunburst Chart (تیم و پیشرفت)'), 'SunburstController', 'show', ['plugin' => 'SunburstAnalytics', 'project_id' => $project['id']]) ?>
</li>
