// ===== Anti double-init (kalau script ter-include dua kali) =====
if (window.__AppLoaderInit) { /* already initialized */ 
  // keluar awal, jangan pasang event handler dua kali
  // penting untuk elak activeCount bercelaru & loader flicker
  throw new Error('AppLoader already initialized');
}
window.__AppLoaderInit = true;

(function () {
  const loader = document.getElementById('global-loader');
  if (!loader) return;

  let activeCount = 0;
  let forceHideTimer = null;

  // ===== Cooldown selepas hide pertama =====
  let lastHideAt = 0;
  const MIN_RESHOW_GAP = 600; // ms – elak "re-show" dalam ~0.6s lepas hide

  const canShow = () => (Date.now() - lastHideAt) > MIN_RESHOW_GAP;

  const startForceHide = () => {
    clearTimeout(forceHideTimer);
    forceHideTimer = setTimeout(() => {
      activeCount = 0;
      hide();
    }, 12000); // failsafe 12s
  };

  const show = () => {
    // block re-show terlalu cepat lepas hide pertama (kurangkan "dua kali" effect)
    if (!canShow()) return;

    loader.style.display = 'flex';
    void loader.offsetWidth; // force reflow utk transition
    loader.classList.remove('loader-hidden');
    loader.setAttribute('aria-busy', 'true');
    startForceHide();
  };

  const hide = () => {
    if (activeCount > 0) return;
    loader.classList.add('loader-hidden');
    loader.setAttribute('aria-busy', 'false');
    clearTimeout(forceHideTimer);
    lastHideAt = Date.now(); // rekod masa hide untuk cooldown
    setTimeout(() => {
      loader.style.display = 'none';
    }, 400);
  };

  const markStart = () => { activeCount++; show(); };
  const markEnd   = () => { if (activeCount > 0) activeCount--; if (activeCount === 0) hide(); };

  // Hide segera jika script load selepas DOM ready
  if (document.readyState !== 'loading') {
    setTimeout(hide, 0);
  } else {
    document.addEventListener('DOMContentLoaded', hide, { once: true });
  }
  window.addEventListener('load', hide, { once: true });
  window.addEventListener('pageshow', (e) => { if (e.persisted) { activeCount = 0; hide(); } });

  // Link click → show (kecuali pengecualian)
  document.addEventListener('click', function (e) {
    const a = e.target.closest('a');
    if (!a) return;
    const href   = a.getAttribute('href') || '';
    const target = a.getAttribute('target');
    const skip =
      target === '_blank' ||
      href.startsWith('#') ||
      href.startsWith('javascript:') ||
      a.hasAttribute('download') ||
      a.dataset.noLoader !== undefined ||
      e.metaKey || e.ctrlKey || e.shiftKey || e.altKey;
    if (skip) return;
    markStart();
  }, true);

  // Form submit → show
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form && form.dataset.noLoader === undefined) {
      markStart();
    }
  }, true);

  // Wrap fetch dengan counter + opt-out
  const _fetch = window.fetch;
  window.fetch = function (input, init = {}) {
    // Allow opt-out: fetch(url, { headers: { 'X-No-Loader': '1' } })
    const headers = init && (init.headers || {});
    const noLoader =
      (headers && (headers['X-No-Loader'] === '1')) ||
      (headers && headers.get && headers.get('X-No-Loader') === '1') ||
      init.noLoader === true;

    if (!noLoader) markStart();
    return _fetch(input, init)
      .finally(() => { if (!noLoader) markEnd(); });
  };

  // jQuery AJAX
  if (window.jQuery) {
    jQuery(document).on('ajaxSend', () => markStart());
    jQuery(document).on('ajaxComplete', () => markEnd());
  }

  // API manual
  window.AppLoader = {
    show: () => { markStart(); },
    hide: () => { activeCount = 0; hide(); }
  };
})();
