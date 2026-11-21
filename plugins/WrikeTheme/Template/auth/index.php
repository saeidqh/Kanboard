<?php
// Custom split-layout login for WrikeTheme (inspired by modern organizational UIs)
global $themeWrikeConfig;
$configPath = DATA_DIR . '/files/WrikeTheme/config.php';
if (file_exists($configPath)) {
    require_once($configPath);
}
$logo = !empty($themeWrikeConfig['logo'])
    ? $themeWrikeConfig['logo']
    : $this->configModel->get('wriketheme_logo', '');
$appName = $this->configModel->get('application_name', 'Kanboard');
?>
<div class="wrike-login-page">
    <div class="wrike-login-shell">

        <!-- LEFT: Form panel -->
        <div class="wrike-login-left">
            <div class="wrike-login-left-inner">

                <div class="wrike-login-badge">
                    <?php if (!empty($logo)): ?>
                        <img src="<?= $this->text->e($logo) ?>" alt="Logo" class="wrike-login-badge-logo">
                    <?php endif ?>
                    <span><?= $this->text->e($appName) ?></span>
                </div>

                <h1 class="wrike-login-title">Welcome Back</h1>
                <p class="wrike-login-subtitle">Please enter your credentials to log in</p>

                <?php if (isset($errors['login'])): ?>
                    <p class="alert alert-error wrike-login-alert"><?= $this->text->e($errors['login']) ?></p>
                <?php endif ?>

                <?php if (! HIDE_LOGIN_FORM): ?>
                <form method="post" action="<?= $this->url->href('AuthController', 'check') ?>" class="wrike-login-form" autocomplete="off">

                    <?= $this->form->csrf() ?>

                    <div class="wrike-field">
                        <label for="form-username">Username</label>
                        <?= $this->form->text('username', $values, $errors, array(
                            'autofocus',
                            'required',
                            'autocomplete="username"',
                            'placeholder="Enter your username"',
                            'id="form-username"'
                        )) ?>
                    </div>

                    <div class="wrike-field">
                        <label for="form-password">Password</label>
                        <div class="wrike-password-wrap">
                            <?= $this->form->password('password', $values, $errors, array(
                                'required',
                                'autocomplete="current-password"',
                                'placeholder="••••••••"',
                                'id="form-password"'
                            )) ?>
                            <button type="button" class="wrike-password-toggle" aria-label="Toggle password" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <?php if (isset($captcha) && $captcha): ?>
                    <div class="wrike-field wrike-captcha-row">
                        <label>Security Code</label>
                        <div class="wrike-captcha-box">
                            <img src="<?= $this->url->href('CaptchaController', 'image') ?>" alt="Captcha" class="wrike-captcha-img">
                            <?= $this->form->text('captcha', array(), $errors, array(
                                'required',
                                'placeholder="Enter security code"',
                                'autocomplete="off"'
                            )) ?>
                        </div>
                    </div>
                    <?php endif ?>

                    <?php if (REMEMBER_ME_AUTH): ?>
                    <div class="wrike-field wrike-remember">
                        <?= $this->form->checkbox('remember_me', t('Remember Me'), 1, true) ?>
                    </div>
                    <?php endif ?>

                    <div class="wrike-form-actions">
                        <button type="submit" class="wrike-btn-signin"><?= t('Sign in') ?></button>
                    </div>

                    <?php if ($this->app->config('password_reset') == 1): ?>
                    <div class="wrike-login-footer">
                        <span></span>
                        <?= $this->url->link(t('Forgot password?'), 'PasswordResetController', 'create', array(), false, 'wrike-link') ?>
                    </div>
                    <?php endif ?>
                </form>
                <?php endif ?>

                <?= $this->hook->render('template:auth:login-form:after') ?>
            </div>
        </div>

        <!-- RIGHT: Visual panel -->
        <div class="wrike-login-right">
            <div class="wrike-login-right-visual">
                <!-- Floating event card (top) -->
                <div class="wrike-float-card wrike-float-top">
                    <strong>Project Status Review</strong>
                    <span>09:30am – 10:00am</span>
                </div>

                <!-- Floating calendar strip -->
                <div class="wrike-float-card wrike-float-calendar">
                    <div class="wrike-cal-days">
                        <span>Sun</span><span>Mon</span><span>Tue</span><span class="active">Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                    </div>
                    <div class="wrike-cal-dates">
                        <span>22</span><span>23</span><span>24</span><span class="active">25</span><span>26</span><span>27</span><span>28</span>
                    </div>
                </div>

                <!-- Floating meeting card (bottom) -->
                <div class="wrike-float-card wrike-float-bottom">
                    <strong>Daily Standup Meeting</strong>
                    <span>12:00pm – 01:00pm</span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    var btn = document.querySelector('.wrike-password-toggle');
    var input = document.getElementById('form-password');
    if (btn && input) {
        btn.addEventListener('click', function () {
            var isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            btn.classList.toggle('is-visible', isPass);
        });
    }
})();
</script>
