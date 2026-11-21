      </div><!-- #wrike-login-left-inner -->
    </div><!-- .wrike-login-left -->

    <div class="wrike-login-right">
      <div class="wrike-login-right-visual">
        <div class="wrike-float-card wrike-float-top">
          <strong>Project Status Review</strong>
          <span>09:30am – 10:00am</span>
        </div>
        <div class="wrike-float-card wrike-float-calendar">
          <div class="wrike-cal-days">
            <span>Sun</span><span>Mon</span><span>Tue</span><span class="active">Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
          </div>
          <div class="wrike-cal-dates">
            <span>22</span><span>23</span><span>24</span><span class="active">25</span><span>26</span><span>27</span><span>28</span>
          </div>
        </div>
        <div class="wrike-float-card wrike-float-bottom">
          <strong>Daily Standup Meeting</strong>
          <span>12:00pm – 01:00pm</span>
        </div>
      </div>
    </div>
  </div><!-- .wrike-login-shell -->
</div><!-- #wrike-login-root -->

<script>
(function () {
  function build() {
    var root = document.getElementById('wrike-login-root');
    var leftInner = document.getElementById('wrike-login-left-inner');
    var formLogin = document.querySelector('.form-login');
    if (!root || !leftInner || !formLogin) return;

    // Move the original form inside left panel
    if (formLogin.parentNode !== leftInner) {
      leftInner.appendChild(formLogin);
    }

    // Hide default heading if present
    var h2 = formLogin.querySelector('h2');
    if (h2) h2.style.display = 'none';

    // Password field: wrap input + put eye button on the RIGHT
    var pass = formLogin.querySelector('input[name="password"], input[type="password"]');
    if (pass) {
      var existingWrap = pass.closest('.wrike-password-wrap');
      if (existingWrap) {
        // clean re-structure
        existingWrap.querySelectorAll('.wrike-password-toggle').forEach(function (b) { b.remove(); });
      } else {
        var wrap = document.createElement('div');
        wrap.className = 'wrike-password-wrap';
        pass.parentNode.insertBefore(wrap, pass);
        wrap.appendChild(pass);
        existingWrap = wrap;
      }

      // Ensure only input + button inside wrap
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'wrike-password-toggle';
      btn.setAttribute('aria-label', 'Show password');
      btn.setAttribute('tabindex', '-1');
      btn.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
      existingWrap.appendChild(btn);

      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var show = pass.type === 'password';
        pass.type = show ? 'text' : 'password';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      });
    }

    // Make submit button full-width yellow
    var submit = formLogin.querySelector('button[type="submit"], input[type="submit"], .btn-blue');
    if (submit) {
      submit.classList.add('wrike-btn-signin');
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', build);
  } else {
    build();
  }
})();
</script>
