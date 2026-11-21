<?php
// Load WrikeTheme config to access logo and colors
global $themeWrikeConfig;
$configPath = DATA_DIR . '/files/WrikeTheme/config.php';
if (file_exists($configPath)) {
    require_once($configPath);
}
// Fallback to DB settings if config file has no logo
$logo         = !empty($themeWrikeConfig['logo'])
    ? $themeWrikeConfig['logo']
    : $this->configModel->get('wriketheme_logo', '');
$headerColor  = !empty($themeWrikeConfig['backgroundColorHeader'])
    ? $themeWrikeConfig['backgroundColorHeader']
    : $this->configModel->get('wriketheme_header_color', '#293D52');
?>
<div class="wrike-login-brand" style="border-top:4px solid <?= $this->text->e($headerColor) ?>">
    <?php if (!empty($logo)): ?>
        <img src="<?= $this->text->e($logo) ?>" alt="Logo" class="wrike-login-logo">
    <?php else: ?>
        <div class="wrike-login-wordmark" style="color:<?= $this->text->e($headerColor) ?>">KB</div>
    <?php endif ?>
</div>
