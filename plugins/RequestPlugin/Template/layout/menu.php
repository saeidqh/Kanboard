<li <?= $this->app->checkMenuSelection('RequestController') ?>>
    <?= $this->url->link(
        '<i class="fa fa-exchange fa-fw"></i> ' . t('Requests'),
        'RequestController',
        'inbox',
        array('plugin' => 'RequestPlugin')
    ) ?>
</li>
