<li <?= $this->app->getRouterController() === 'WrikeThemeController' ? 'class="active"' : '' ?>>
    <?= $this->url->link(
        '<i class="fa fa-paint-brush fa-fw"></i> WrikeTheme',
        'WrikeThemeController',
        'show',
        ['plugin' => 'WrikeTheme'],
        false,
        '',
        t('WrikeTheme Settings')
    ) ?>
</li>
