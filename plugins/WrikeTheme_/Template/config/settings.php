<div class="page-header">
    <h2><i class="fa fa-paint-brush"></i> WrikeTheme</h2>
</div>

<form method="post" action="<?= $this->url->href('WrikeThemeController', 'save', ['plugin' => 'WrikeTheme']) ?>">
    <?= $this->form->csrf() ?>

    <fieldset>
        <legend><?= t('Header') ?></legend>

        <div class="form-columns">
            <div class="form-column">
                <label for="wriketheme-header-color"><?= t('Background color') ?></label>
                <div class="wriketheme-color-row">
                    <input type="color"
                           id="wriketheme-header-color-picker"
                           value="<?= $this->text->e($header_color) ?>"
                           oninput="document.getElementById('wriketheme-header-color').value=this.value"
                           class="wriketheme-color-swatch">
                    <input type="text"
                           id="wriketheme-header-color"
                           name="header_color"
                           value="<?= $this->text->e($header_color) ?>"
                           maxlength="7"
                           oninput="document.getElementById('wriketheme-header-color-picker').value=this.value"
                           class="wriketheme-color-text">
                </div>
            </div>

            <div class="form-column">
                <label for="wriketheme-title-color"><?= t('Title color') ?></label>
                <div class="wriketheme-color-row">
                    <input type="color"
                           id="wriketheme-title-color-picker"
                           value="<?= $this->text->e($title_color) ?>"
                           oninput="document.getElementById('wriketheme-title-color').value=this.value"
                           class="wriketheme-color-swatch">
                    <input type="text"
                           id="wriketheme-title-color"
                           name="title_color"
                           value="<?= $this->text->e($title_color) ?>"
                           maxlength="7"
                           oninput="document.getElementById('wriketheme-title-color-picker').value=this.value"
                           class="wriketheme-color-text">
                </div>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend><?= t('Logo') ?></legend>
        <label for="wriketheme-logo"><?= t('Logo URL') ?> <span class="form-help"><?= t('Leave empty to show the default KB text logo') ?></span></label>
        <input type="text"
               id="wriketheme-logo"
               name="logo"
               value="<?= $this->text->e($logo) ?>"
               placeholder="/plugins/WrikeTheme/Assets/images/brand-logo.png"
               class="form-input-large">

        <?php if (!empty($logo)): ?>
        <div class="wriketheme-logo-preview">
            <img src="<?= $this->text->e($logo) ?>" alt="Logo preview" style="max-height:48px;margin-top:10px;">
        </div>
        <?php endif ?>
    </fieldset>

    <fieldset>
        <legend><?= t('Tasks') ?></legend>
        <label for="wriketheme-default-color"><?= t('Default task color') ?></label>
        <select id="wriketheme-default-color" name="default_color">
            <?php foreach ($colors as $color_id => $color_name): ?>
            <option value="<?= $this->text->e($color_id) ?>"
                <?= $color_id === $default_color ? 'selected' : '' ?>>
                <?= $this->text->e($color_name) ?>
            </option>
            <?php endforeach ?>
        </select>
        <p class="form-help"><?= t('Color applied by default when creating a new task.') ?></p>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-blue">
            <i class="fa fa-save"></i> <?= t('Save') ?>
        </button>
        &nbsp;
        <a href="<?= $this->url->href('WrikeThemeController', 'show', ['plugin' => 'WrikeTheme']) ?>" class="btn">
            <?= t('Cancel') ?>
        </a>
    </div>
</form>

<div class="wriketheme-preview-bar" id="wriketheme-preview-bar"
     style="background:<?= $this->text->e($header_color) ?>; color:<?= $this->text->e($title_color) ?>">
    <i class="fa fa-eye"></i> <?= t('Header preview') ?>
</div>

<style>
.wriketheme-color-row { display:flex; align-items:center; gap:8px; margin-top:4px }
.wriketheme-color-swatch { width:36px; height:28px; padding:0; border:1px solid #E6E9EF; border-radius:4px; cursor:pointer }
.wriketheme-color-text { width:90px !important }
.wriketheme-preview-bar {
    margin-top:20px; padding:12px 16px; border-radius:6px;
    font-weight:600; font-size:1.05em; transition:background 0.2s, color 0.2s
}
</style>

<script>
(function() {
    var bar = document.getElementById('wriketheme-preview-bar');
    function updatePreview() {
        var bg = document.getElementById('wriketheme-header-color').value;
        var fg = document.getElementById('wriketheme-title-color').value;
        if (bar) { bar.style.background = bg; bar.style.color = fg; }
    }
    ['wriketheme-header-color', 'wriketheme-title-color',
     'wriketheme-header-color-picker', 'wriketheme-title-color-picker'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', updatePreview);
    });
})();
</script>
