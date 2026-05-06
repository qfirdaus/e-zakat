<?php
$version = time();
$currentPage = $currentPage ?? basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$idleTimeoutMinutes = (int)app_config('session.idle_timeout_minutes', 30);
if ($idleTimeoutMinutes <= 0) {
  $idleTimeoutMinutes = 30;
}
?>

<!-- ========== Core: jQuery (NO defer) ========== -->
<!-- Pastikan vendor.min.js TIDAK bundle jQuery. Kita nak hanya satu sumber jQuery -->
<script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>

<!-- ========== Vendor & Plugin JS (OK untuk defer) ========== -->
<!-- Bootstrap perlu dimuat secara eksplisit kerana banyak page bergantung pada window.bootstrap.* -->
<script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/js/vendor.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/daterangepicker/moment.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/daterangepicker/daterangepicker.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/apexcharts/apexcharts.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js') ?>?v=<?= $version ?>" defer></script>

<!-- ✅ DataTables JS (Bootstrap 5) -->
<script src="<?= base_url('assets/vendor/datatables.net/js/jquery.dataTables.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js') ?>?v=<?= $version ?>" defer></script>

<!-- (Optional) Add-on plugins -->
<script src="<?= base_url('assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/datatables.net-fixedcolumns/js/dataTables.fixedColumns.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/datatables.net-buttons/js/dataTables.buttons.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/vendor/datatables.net-select/js/dataTables.select.min.js') ?>?v=<?= $version ?>" defer></script>

<!-- ========== Page-Specific JS ========== -->
<?php 
// ✅ Only load demo.dashboard.js for actual demo dashboard, not prestasi dashboard
if (str_ends_with($currentPage, 'dashboard.php') && strpos($_SERVER['REQUEST_URI'] ?? '', 'prestasi') === false): ?>
  <script src="<?= base_url('assets/js/pages/demo.dashboard.js') ?>?v=<?= $version ?>" defer></script>
<?php endif; ?>

<!-- ========== App Core JS (OK defer) ========== -->
<script src="<?= base_url('assets/js/app.unmin.js') ?>?v=<?= $version ?>" defer></script>
<script src="<?= base_url('assets/js/theme-setting.js') ?>?v=<?= $version ?>" defer></script>

<!-- ========== Alpine.js (for interactive components) ========== -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- ========== SweetAlert (NO defer, sebab render_alert mungkin run segera) ========== -->
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>

<!-- Loader JS (tanpa defer, biar dia hijack fetch/klik awal-awal) -->
<script src="<?= base_url('assets/js/loader.js') ?>?v=<?= $version ?>"></script>

<!-- ========== Global JavaScript Variables ========== -->
<?php
// Expose CSRF token and BASE_URL to JavaScript
$csrfToken = $_SESSION['csrf_token'] ?? '';
$baseUrl = rtrim(base_url(''), '/');
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
<script>
  // Safe storage helper (avoid errors when storage is blocked)
  window.safeStorage = window.safeStorage || {
    get(k){ try { return localStorage.getItem(k); } catch(e){ return null; } },
    set(k,v){ try { localStorage.setItem(k,v); return true; } catch(e){ return false; } }
  };
  // Global variables for AJAX requests
  window.csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
  window.BASE_URL = <?= json_encode($baseUrl, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('assets/js/group-sidebar-sync.js') ?>?v=<?= $version ?>"></script>
<script src="<?= base_url('assets/js/access-ui-sync.js') ?>?v=<?= $version ?>"></script>
<script src="<?= base_url('assets/js/topbar-notifications.js') ?>?v=<?= $version ?>"></script>
<script>
(function () {
  'use strict';

  const SESSION_TERM_I18N = {
    title: <?= json_encode(__('session_terminated_title') ?: 'Sesi Ditamatkan', JSON_UNESCAPED_UNICODE) ?>,
    text: <?= json_encode(__('session_terminated_text') ?: 'Sesi anda telah ditamatkan oleh pentadbir. Sila log masuk semula.', JSON_UNESCAPED_UNICODE) ?>,
    ok: <?= json_encode(__('session_terminated_ok') ?: 'OK', JSON_UNESCAPED_UNICODE) ?>
  };

  const loginUrl = <?= json_encode(base_path('index.php'), JSON_UNESCAPED_UNICODE) ?>;

  function parseJsonSafely(text) {
    if (typeof text !== 'string' || text.trim() === '') {
      return null;
    }
    try {
      return JSON.parse(text);
    } catch (_) {
      return null;
    }
  }

  window.AppSessionGuard = window.AppSessionGuard || {
    handling: false,
    isSessionTerminatedPayload(payload) {
      return !!(payload && typeof payload === 'object' && payload.session_terminated === true);
    },
    async handlePayload(payload) {
      if (!this.isSessionTerminatedPayload(payload)) {
        return false;
      }

      if (this.handling) {
        return true;
      }
      this.handling = true;

      const title = payload.title || SESSION_TERM_I18N.title;
      const text = payload.message || SESSION_TERM_I18N.text;
      const redirect = payload.redirect || loginUrl;

      if (window.Swal && typeof window.Swal.fire === 'function') {
        await window.Swal.fire({
          icon: 'warning',
          title: title,
          text: text,
          confirmButtonText: SESSION_TERM_I18N.ok,
          allowOutsideClick: false,
          allowEscapeKey: false
        });
      } else {
        window.alert(text);
      }

      window.location.href = redirect;
      return true;
    },
    inspectJsonText(text) {
      return parseJsonSafely(text);
    }
  };

  if (typeof window.fetch === 'function' && !window.__SessionGuardFetchWrapped) {
    const originalFetch = window.fetch.bind(window);
    window.fetch = async function (input, init) {
      const response = await originalFetch(input, init);

      try {
        const contentType = String(response.headers.get('content-type') || '').toLowerCase();
        if (contentType.indexOf('application/json') !== -1 || contentType.indexOf('text/json') !== -1) {
          const payload = await response.clone().json();
          const handled = await window.AppSessionGuard.handlePayload(payload);
          if (handled) {
            return new Promise(function () {});
          }
        }
      } catch (_) {
        // Ignore JSON parse/read errors; caller can handle the response normally.
      }

      return response;
    };
    window.__SessionGuardFetchWrapped = true;
  }

  if (window.jQuery && !window.__SessionGuardAjaxBound) {
    jQuery(document).ajaxComplete(function (_event, xhr) {
      try {
        const contentType = String(xhr.getResponseHeader('Content-Type') || '').toLowerCase();
        if (contentType.indexOf('application/json') === -1 && contentType.indexOf('text/json') === -1) {
          return;
        }
        const payload = xhr.responseJSON || window.AppSessionGuard.inspectJsonText(xhr.responseText || '');
        void window.AppSessionGuard.handlePayload(payload);
      } catch (_) {
        // ignore
      }
    });
    window.__SessionGuardAjaxBound = true;
  }
})();
</script>

<?php
require_once __DIR__ . '/../setting/helper/alert_helper.php';
if (function_exists('render_alert')) {
    render_alert();
}
?>

<!-- ========== Navigation Fallback ========== -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('a.nav-link, a.dropdown-item').forEach(el => {
    if (!el.hasAttribute('href')) el.setAttribute('href', '#');
  });
});
</script>

<!-- ========== Session Idle Guard ========== -->
<?php
$isLoggedIn = !empty($_SESSION['f_stafID']);
?>
<?php if ($isLoggedIn): ?>
<script>
(function () {
  'use strict';

  const IDLE_LIMIT_MS = <?= (int)$idleTimeoutMinutes ?> * 60 * 1000;
  const PROMPT_MS = 60 * 1000;            // tunggu respon 1 minit
  const KEEPALIVE_URL = <?= json_encode(base_url('ajax/session-keepalive.php'), JSON_UNESCAPED_UNICODE) ?>;
  const LOGOUT_URL = <?= json_encode(base_url('logout.php'), JSON_UNESCAPED_UNICODE) ?>;

  const I18N = {
    title: <?= json_encode(__('session_idle_title') ?: 'Masih di sini?', JSON_UNESCAPED_UNICODE) ?>,
    text: <?= json_encode(sprintf((string)(__('session_idle_text') ?: 'Tiada aktiviti %d minit. Kekal log masuk?'), $idleTimeoutMinutes), JSON_UNESCAPED_UNICODE) ?>,
    stay: <?= json_encode(__('session_idle_stay_connected') ?: 'Kekal Log Masuk', JSON_UNESCAPED_UNICODE) ?>,
    logout: <?= json_encode(__('session_idle_logout_now') ?: 'Log Keluar', JSON_UNESCAPED_UNICODE) ?>,
    timeoutText: <?= json_encode(__('session_idle_timeout_text') ?: 'Auto log keluar dalam 1 minit.', JSON_UNESCAPED_UNICODE) ?>,
    timeoutTitle: <?= json_encode(__('session_idle_timeout_title') ?: 'Sesi Tamat', JSON_UNESCAPED_UNICODE) ?>,
    timeoutLogoutNow: <?= json_encode(__('session_idle_timeout_logout_now') ?: 'Tiada respons. Sistem akan log keluar sekarang.', JSON_UNESCAPED_UNICODE) ?>,
    keepaliveFailed: <?= json_encode(__('session_idle_keepalive_failed') ?: 'Sesi tidak dapat diperbaharui. Anda akan dilog keluar.', JSON_UNESCAPED_UNICODE) ?>
  };

  let lastActivityAt = Date.now();
  let promptOpen = false;
  let checking = false;

  const markActivity = () => {
    if (promptOpen) return;
    lastActivityAt = Date.now();
  };

  const forceLogout = () => {
    window.location.href = LOGOUT_URL;
  };

  const keepAlive = async () => {
    const res = await fetch(KEEPALIVE_URL, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      cache: 'no-store'
    });
    if (!res.ok) throw new Error('keepalive_failed');
    const data = await res.json();
    if (!data || data.ok !== true) throw new Error('keepalive_invalid');
  };

  const showTimeoutLogoutAlert = async () => {
    if (window.Swal && typeof Swal.fire === 'function') {
      await Swal.fire({
        icon: 'info',
        title: I18N.timeoutTitle,
        text: I18N.timeoutLogoutNow,
        confirmButtonText: 'OK',
        allowOutsideClick: false,
        allowEscapeKey: false
      });
    }
    forceLogout();
  };

  const showIdlePrompt = async () => {
    if (promptOpen) return;
    promptOpen = true;

    try {
      if (window.Swal && typeof Swal.fire === 'function') {
        const result = await Swal.fire({
          icon: 'warning',
          title: I18N.title,
          text: I18N.text,
          footer: I18N.timeoutText,
          showCancelButton: true,
          confirmButtonText: I18N.stay,
          cancelButtonText: I18N.logout,
          allowOutsideClick: false,
          allowEscapeKey: false,
          timer: PROMPT_MS,
          timerProgressBar: true
        });

        if (result.isConfirmed) {
          await keepAlive();
          lastActivityAt = Date.now();
          promptOpen = false;
          return;
        }

        if (result.dismiss === Swal.DismissReason.cancel) {
          promptOpen = false;
          forceLogout();
          return;
        }

        promptOpen = false;
        await showTimeoutLogoutAlert();
        return;
      }

      const stay = window.confirm(I18N.text);
      if (stay) {
        await keepAlive();
        lastActivityAt = Date.now();
        promptOpen = false;
      } else {
        promptOpen = false;
        forceLogout();
      }
    } catch (e) {
      promptOpen = false;
      if (window.Swal && typeof Swal.fire === 'function') {
        await Swal.fire({
          icon: 'error',
          title: I18N.timeoutTitle,
          text: I18N.keepaliveFailed,
          confirmButtonText: 'OK'
        });
      }
      forceLogout();
    }
  };

  const checkIdle = async () => {
    if (checking || promptOpen) return;
    checking = true;
    try {
      const idleFor = Date.now() - lastActivityAt;
      if (idleFor >= IDLE_LIMIT_MS) {
        await showIdlePrompt();
      }
    } finally {
      checking = false;
    }
  };

  const throttledMarkActivity = (() => {
    let last = 0;
    return () => {
      const now = Date.now();
      if (now - last < 500) return;
      last = now;
      markActivity();
    };
  })();

  ['click', 'keydown', 'mousedown', 'touchstart'].forEach(evt => {
    document.addEventListener(evt, markActivity, { passive: true });
  });
  ['mousemove', 'scroll', 'touchmove'].forEach(evt => {
    document.addEventListener(evt, throttledMarkActivity, { passive: true });
  });
  window.addEventListener('focus', markActivity, { passive: true });

  setInterval(checkIdle, 1000);
})();
</script>
<?php endif; ?>
<!-- ========== Topbar Theme Toggle ========== -->
<script>
const updateThemeIcon = isDark => {
  const icon = document.getElementById('theme-mode-icon');
  if (!icon) return;
  icon.classList.remove('ri-moon-fill', 'ri-sun-fill');
  icon.classList.add(isDark ? 'ri-sun-fill' : 'ri-moon-fill');
};

const applyTheme = mode => {
  const html = document.documentElement;
  const isDark = mode === 'dark';
  html.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
  document.body.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
  updateThemeIcon(isDark);
  safeStorage.set('theme', mode);
};

document.addEventListener('DOMContentLoaded', () => {
  const savedTheme = safeStorage.get('theme') || 'light';
  applyTheme(savedTheme);

  document.getElementById('light-dark-mode')?.addEventListener('click', () => {
    const newTheme = safeStorage.get('theme') === 'dark' ? 'light' : 'dark';
    applyTheme(newTheme);

    const formData = new FormData();
    formData.append('theme_type', 'data-bs-theme');
    formData.append('theme_value', newTheme);

    fetch('<?= base_url("/setting/set_theme.php") ?>', {
      method: 'POST',
      body: formData
    });
  });
});
</script>

<!-- ========== Fullscreen Toggle ========== -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('toggle-fullscreen');
  const icon = btn?.querySelector('i');

  btn?.addEventListener('click', function (e) {
    e.preventDefault();
    const doc = document;
    const docEl = document.documentElement;

    if (!doc.fullscreenElement) {
      docEl.requestFullscreen?.();
    } else {
      doc.exitFullscreen?.();
    }
  });

  document.addEventListener('fullscreenchange', () => {
    const isFullscreen = !!document.fullscreenElement;
    if (!icon) return;
    icon.classList.toggle('ri-fullscreen-line', !isFullscreen);
    icon.classList.toggle('ri-fullscreen-exit-line', isFullscreen);
  });
});
</script>

<!-- ========== Theme Settings Panel Sync ========== -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const topbar = document.getElementById('topbar');
  const sidebar = document.getElementById('leftside-menu');
  const offcanvas = document.getElementById('theme-settings-offcanvas');

  if (offcanvas) {
    offcanvas.addEventListener('shown.bs.offcanvas', () => {
      const saved = {
        'data-bs-theme': safeStorage.get('layout-mode') || document.documentElement.getAttribute('data-bs-theme') || 'light',
        'data-topbar-color': safeStorage.get('topbar-color') || document.body.getAttribute('data-topbar-color') || 'light',
        'data-menu-color': safeStorage.get('sidebar-color') || document.body.getAttribute('data-menu-color') || 'light',
      };

      Object.entries(saved).forEach(([type, value]) => {
        const input = document.querySelector(`input[name="${type}"][value="${value}"]`);
        if (input) {
          input.checked = true;
        } else {
          document.querySelectorAll(`input[name="${type}"]`).forEach(i => i.checked = false);
        }
      });
    });
  }

  // ✅ Theme changes handler - apply immediately when user selects theme
  document.querySelectorAll('input[name="data-bs-theme"], input[name="data-topbar-color"], input[name="data-menu-color"]').forEach(input => {
    input.addEventListener('change', () => {
      const type = input.name;
      const value = input.value;
      const keyMap = {
        'data-bs-theme': 'layout-mode',
        'data-topbar-color': 'topbar-color',
        'data-menu-color': 'sidebar-color'
      };
      const storageKey = keyMap[type];
      if (!storageKey) {
        return;
      }

      if (typeof updateThemeSetting === 'function') {
        updateThemeSetting(storageKey, value);
        return;
      }

      safeStorage.set(storageKey, value);
      if (typeof applyThemeSetting === 'function') {
        applyThemeSetting();
      } else {
        if (type === 'data-bs-theme') {
          document.documentElement.setAttribute('data-bs-theme', value);
          document.body.setAttribute('data-bs-theme', value);
        } else if (type === 'data-topbar-color') {
          document.documentElement.setAttribute('data-topbar-color', value);
          document.body.setAttribute('data-topbar-color', value);
          if (topbar) {
            topbar.className = topbar.className
              .split(' ')
              .filter(c => !c.startsWith('topbar-'))
              .join(' ')
              .trim();
            topbar.classList.add('topbar-' + value);
          }
        } else if (type === 'data-menu-color') {
          document.documentElement.setAttribute('data-menu-color', value);
          document.body.setAttribute('data-menu-color', value);
          if (sidebar) {
            sidebar.setAttribute('data-menu-color', value);
          }
        }
      }
    });
  });
});
</script>

<script src="https://unpkg.com/nprogress@0.2.0/nprogress.js"></script>
<script>
// GLOBAL LOADER
(function () {
  // ====== CONFIG BOLEH DITETAPKAN DI PAGE ======
  // <body data-loader="off|bar|overlay|both" data-loader-spinner="Memuat data…"></body>
  var body   = document.body;
  var mode   = (body.dataset.loader || 'bar').toLowerCase(); // default: bar
  var text   = body.dataset.loaderSpinner || 'Memuat data…';
  if (mode === 'off') return;

  // ====== NProgress (bar) ======
  var useBar = (mode === 'bar' || mode === 'both');
  if (useBar && window.NProgress) {
    NProgress.configure({ showSpinner:false, trickleSpeed:120 });
    window.addEventListener('beforeunload', function(){ NProgress.start(); });
    window.addEventListener('pageshow', function(){ setTimeout(function(){ try{NProgress.done();}catch(e){} },80); });
  }

  // ====== Overlay (spinner) ======
  var useOverlay = (mode === 'overlay' || mode === 'both');
  var overlay;
  function ovShow(){ if(!useOverlay) return; overlay.classList.add('show'); }
  function ovHide(){ if(!useOverlay) return; overlay.classList.remove('show'); }
  if (useOverlay) {
    overlay = document.createElement('div');
    overlay.className = 'loader-overlay';
    // Build spinner content without using string concatenation to avoid inserting untrusted HTML
    (function(){
      var inner = document.createElement('div'); inner.className = 'text-center';
      var spinner = document.createElement('div'); spinner.className = 'spinner-border'; spinner.setAttribute('role','status'); spinner.setAttribute('aria-hidden','true');
      var txt = document.createElement('div'); txt.className = 'mt-2 small text-muted'; txt.textContent = text || '';
      inner.appendChild(spinner);
      inner.appendChild(txt);
      overlay.appendChild(inner);
    })();
    document.body.appendChild(overlay);
  }

  function startAll(){ if(useBar) NProgress.start(); ovShow(); }
  function stopAll(){  if(useBar) NProgress.done(); ovHide(); }

  // ====== TRIGGER: LINK CLICK ======
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href]');
    if (!a) return;
    var href = a.getAttribute('href') || '';

    // abaikan: anchor, new tab, modifier keys, file download/export
    if (href.charAt(0) === '#') return;
    if (a.hasAttribute('download') || a.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey) return;
    if (a.dataset.noLoader !== undefined) return;
    if (/download=excel/i.test(href)) return; // elak “terkunci” bila hanya trigger download

    startAll();
  }, true);

  // ====== TRIGGER: FORM SUBMIT ======
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f && f.dataset.noLoader !== undefined) return;
    startAll();
  }, true);

  // ====== jQuery AJAX (jika ada) ======
  if (window.jQuery) {
    $(document).ajaxStart(startAll);
    $(document).ajaxStop(stopAll);
    $(document).ajaxError(stopAll);
  }

  // safety stop
  window.addEventListener('pageshow', stopAll);
})();
</script>
