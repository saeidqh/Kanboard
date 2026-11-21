<li>
    <?= $this->modal->large(
        'plus',
        t('New Request'),
        'RequestController',
        'create',
        array('plugin' => 'RequestPlugin')
    ) ?>
</li>
