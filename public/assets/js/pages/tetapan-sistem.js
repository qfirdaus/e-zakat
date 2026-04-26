(function () {
  'use strict';

  function getTetapanTranslator() {
    return window.__ || function (key) { return key; };
  }

  function showTetapanSystemError(message) {
    const __ = getTetapanTranslator();
    const text = message || __('config_js_module_not_ready') || 'Modul tetapan sistem belum siap dimuatkan. Sila cuba semula.';
    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({
        icon: 'error',
        title: __('config_js_system_error_title') || 'Ralat Sistem',
        text: text,
        confirmButtonText: __('config_js_btn_ok') || 'OK'
      });
    } else {
      window.alert(text);
    }
  }

  function rememberActiveTab(tabSelector) {
    if (!tabSelector) {
      return;
    }
    try {
      window.localStorage.setItem('lastActiveTab', tabSelector);
    } catch (storageError) {
      // ignore storage errors
    }
  }

  function activateTab(tabSelector) {
    if (!tabSelector) {
      return;
    }

    const trigger = document.querySelector('a[data-bs-toggle="tab"][href="' + tabSelector + '"]');
    if (!trigger) {
      return;
    }

    rememberActiveTab(tabSelector);
    if (window.bootstrap && window.bootstrap.Tab) {
      window.bootstrap.Tab.getOrCreateInstance(trigger).show();
      return;
    }

    trigger.classList.add('active');
  }

  function fallbackSetButtonLoading(button, loading) {
    const __ = getTetapanTranslator();
    if (!button) {
      return;
    }
    if (loading) {
      button.disabled = true;
      if (!button.dataset.originalHtml) {
        button.dataset.originalHtml = button.innerHTML;
      }
      button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + ((__('config_js_btn_loading_save') || 'Saving...'));
      return;
    }
    button.disabled = false;
    if (button.dataset.originalHtml) {
      button.innerHTML = button.dataset.originalHtml;
      delete button.dataset.originalHtml;
    }
  }

  function fallbackSubmitAjax(form, button) {
    const __ = getTetapanTranslator();
    if (!form) {
      showTetapanSystemError();
      return false;
    }

    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
      if (typeof form.reportValidity === 'function') {
        form.reportValidity();
      }
      return false;
    }

    fallbackSetButtonLoading(button, true);

    const formData = new FormData(form);
    formData.set('ajax', '1');

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    fetch(form.getAttribute('action') || window.location.href, {
      method: 'POST',
      body: formData,
      noLoader: true,
      headers: Object.assign({
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-No-Loader': '1'
      }, csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
    })
      .then(function (response) {
        return response.json().catch(function () {
          throw new Error(__('config_js_invalid_server_response') || 'Respons pelayan tidak sah.');
        });
      })
      .then(function (payload) {
        if (!payload || payload.success !== true) {
          showTetapanSystemError((payload && payload.message) || __('config_js_save_failed') || 'Gagal menyimpan tetapan.');
          return;
        }

        if (window.Swal && typeof window.Swal.fire === 'function') {
          window.Swal.fire({
            icon: 'success',
            title: payload.title || __('config_js_berjaya') || 'Berjaya',
            text: payload.message || __('config_js_save_success_default') || 'Tetapan berjaya disimpan.',
            confirmButtonText: __('config_js_btn_ok') || 'OK'
          });
        }
      })
      .catch(function (error) {
        showTetapanSystemError((error && error.message) || __('config_js_save_system_error') || 'Ralat sistem semasa menyimpan tetapan.');
      })
      .finally(function () {
        fallbackSetButtonLoading(button, false);
      });

    return true;
  }

  function fallbackEmailTest() {
    const __ = getTetapanTranslator();
    const form = document.getElementById('form-emel-aktif');
    const btnUji = document.getElementById('btn-uji-emel');
    if (!form || !btnUji) {
      showTetapanSystemError();
      return false;
    }

    const config = window.tetapanSistemConfig || {};
    const baseUrl = typeof config.baseUrl === 'string' ? config.baseUrl : '';
    const mailFrom = form.querySelector('input[name="mail_from_address"]')
      ? form.querySelector('input[name="mail_from_address"]').value
      : '';
    const mailUsername = form.querySelector('input[name="mail_username"]')
      ? form.querySelector('input[name="mail_username"]').value
      : '';
    const defaultEmail = mailFrom || mailUsername || '';

    if (!(window.Swal && typeof window.Swal.fire === 'function')) {
      showTetapanSystemError();
      return false;
    }

    window.Swal.fire({
      title: __('config_js_input_uji_emel'),
      input: 'email',
      inputLabel: __('config_js_label_uji_emel'),
      inputValue: defaultEmail,
      inputPlaceholder: __('config_js_placeholder_uji_emel'),
      showCancelButton: true,
      confirmButtonText: __('config_js_uji_emel_btn'),
      cancelButtonText: __('config_alert_no'),
      preConfirm: function (email) {
        if (!email) {
          window.Swal.showValidationMessage(__('config_js_valid_emel_kosong'));
          return false;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
          window.Swal.showValidationMessage(__('config_js_valid_email_full'));
          return false;
        }
        return email;
      }
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      const formData = new FormData(form);
      formData.append('uji_email', result.value);
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
      formData.append('csrf_token', csrfToken);

      btnUji.disabled = true;
      if (!btnUji.dataset.originalHtml) {
        btnUji.dataset.originalHtml = btnUji.innerHTML;
      }
      btnUji.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (__('config_js_uji_emel_btn_loading') || 'Testing...');

      fetch(baseUrl + 'ajax/uji-emel.php', {
        method: 'POST',
        body: formData,
        noLoader: true,
        headers: Object.assign({
          'X-No-Loader': '1'
        }, csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data && data.success) {
            window.Swal.fire({
              icon: 'success',
              title: __('config_js_berjaya') || 'Berjaya',
              html: data.message || __('config_js_emel_berjaya') || 'Emel berjaya dihantar.'
            });
            return;
          }

          window.Swal.fire({
            icon: 'error',
            title: __('config_js_ralat') || 'Ralat',
            text: (data && data.message) || __('config_js_emel_gagal') || 'Gagal hantar emel.'
          });
        })
        .catch(function () {
          window.Swal.fire({
            icon: 'error',
            title: __('config_js_ralat') || 'Ralat',
            text: __('config_js_ralat_sistem') || 'Ralat sistem semasa menguji sambungan.'
          });
        })
        .finally(function () {
          btnUji.disabled = false;
          btnUji.innerHTML = btnUji.dataset.originalHtml || '<i class="ri-mail-send-line me-1"></i> ' + (__('config_js_uji_emel_btn_default') || 'Uji Sambungan Emel');
        });
    });

    return false;
  }

  window.__tetapanSubmitFormWithValidation = window.__tetapanSubmitFormWithValidation || function (form, button) {
    if (typeof window.__tetapanSubmitFormWithValidationImpl === 'function') {
      return window.__tetapanSubmitFormWithValidationImpl(form, button);
    }
    return fallbackSubmitAjax(form, button);
  };

  window.__tetapanHandleEmailTest = window.__tetapanHandleEmailTest || function () {
    if (typeof window.__tetapanHandleEmailTestImpl === 'function') {
      return window.__tetapanHandleEmailTestImpl();
    }
    return fallbackEmailTest();
  };

  function initTetapanSistemPage() {
    const config = window.tetapanSistemConfig || {};
    const __ = window.__ || function (key) { return key; };
    const baseUrl = typeof config.baseUrl === 'string' ? config.baseUrl : '';
    const csrfToken = typeof config.csrfToken === 'string' ? config.csrfToken : '';
    const initialDbSelection = config.initialDbSelection || {};
    let additionalConnections = Array.isArray(config.additionalConnections) ? config.additionalConnections.slice() : [];
    const pageUiHelper = window.PageUiHelper || {};
    const formRuntimeState = new WeakMap();
    const buildAssetUrl = function (assetPath) {
      const cleanPath = String(assetPath || '').trim().replace(/^\/+/, '');
      if (!cleanPath) {
        return '';
      }
      return baseUrl + cleanPath + (cleanPath.indexOf('?') === -1 ? ('?v=' + Date.now()) : ('&v=' + Date.now()));
    };
    const applyGeneralSettings = function (generalSettings) {
      if (!generalSettings) {
        return;
      }

      const sidebarUserImage = generalSettings['branding.sidebar_user_image'];
      if (sidebarUserImage) {
        const leftbarUser = document.querySelector('.leftbar-user');
        if (leftbarUser) {
          leftbarUser.style.backgroundImage = 'url("' + buildAssetUrl(sidebarUserImage) + '")';
        }
      }

      if (generalSettings['site.title']) {
        document.title = String(generalSettings['site.title']);
      }
    };

    const getFormState = function (form) {
      if (!formRuntimeState.has(form)) {
        formRuntimeState.set(form, {
          snapshot: '',
          pending: false,
          lastState: 'idle'
        });
      }
      return formRuntimeState.get(form);
    };

    const serializeFormState = function (form) {
      const entries = [];
      const formData = new FormData(form);
      formData.forEach(function (value, key) {
        if (key === 'csrf_token' || key === 'ajax') {
          return;
        }
        if (value instanceof File) {
          entries.push([key, value.name || '']);
          return;
        }
        entries.push([key, String(value)]);
      });
      entries.sort(function (a, b) {
        if (a[0] === b[0]) {
          return a[1].localeCompare(b[1]);
        }
        return a[0].localeCompare(b[0]);
      });
      return JSON.stringify(entries);
    };

    const captureFormSnapshot = function (form) {
      const state = getFormState(form);
      state.snapshot = serializeFormState(form);
      return state.snapshot;
    };

    const getSaveFeedbackHost = function (form, button) {
      var existing = form.querySelector('[data-settings-save-feedback="1"]');
      if (existing) {
        return existing;
      }

      var actions = (button && button.closest('.general-settings-actions, .email-settings-actions, .auth-settings-actions, .db-settings-actions, .theme-settings-actions, .lang-settings-actions'))
        || form.querySelector('.general-settings-actions, .email-settings-actions, .auth-settings-actions, .db-settings-actions, .theme-settings-actions, .lang-settings-actions');
      if (!actions) {
        return null;
      }

      var feedback = document.createElement('div');
      feedback.className = 'tetapan-save-feedback d-inline-flex align-items-center gap-2 small ms-auto me-2';
      feedback.setAttribute('data-settings-save-feedback', '1');
      feedback.setAttribute('aria-live', 'polite');
      feedback.innerHTML = ''
        + '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" data-save-feedback-badge>Belum simpan</span>'
        + '<span class="text-muted" data-save-feedback-text>Belum ada perubahan.</span>';

      var buttonGroup = button && button.parentElement && button.parentElement !== actions && button.parentElement.classList.contains('d-flex')
        ? button.parentElement
        : null;

      if (buttonGroup && buttonGroup.parentElement === actions) {
        actions.insertBefore(feedback, buttonGroup);
      } else if (button && button.parentElement === actions) {
        actions.insertBefore(feedback, button);
      } else {
        actions.appendChild(feedback);
      }

      return feedback;
    };

    const setSaveFeedbackState = function (form, button, tone, message) {
      var host = getSaveFeedbackHost(form, button);
      if (!host) {
        return;
      }

      var badge = host.querySelector('[data-save-feedback-badge]');
      var text = host.querySelector('[data-save-feedback-text]');
      var badgeMap = {
        idle: {
          badge: 'Belum simpan',
          className: 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
          textClass: 'text-muted'
        },
        dirty: {
          badge: 'Perubahan',
          className: 'badge bg-warning-subtle text-warning border border-warning-subtle',
          textClass: 'text-warning-emphasis'
        },
        saving: {
          badge: 'Menyimpan',
          className: 'badge bg-primary-subtle text-primary border border-primary-subtle',
          textClass: 'text-primary-emphasis'
        },
        success: {
          badge: 'Disimpan',
          className: 'badge bg-success-subtle text-success border border-success-subtle',
          textClass: 'text-success-emphasis'
        },
        warning: {
          badge: 'Amaran',
          className: 'badge bg-warning-subtle text-warning border border-warning-subtle',
          textClass: 'text-warning-emphasis'
        },
        error: {
          badge: 'Ralat',
          className: 'badge bg-danger-subtle text-danger border border-danger-subtle',
          textClass: 'text-danger-emphasis'
        }
      };

      var next = badgeMap[tone] || badgeMap.idle;
      if (badge) {
        badge.className = next.className;
        badge.textContent = next.badge;
      }
      if (text) {
        text.className = next.textClass;
        text.textContent = message || '';
      }

      getFormState(form).lastState = tone;
    };

    const refreshDirtyIndicator = function (form, button) {
      if (!form) {
        return;
      }

      var state = getFormState(form);
      if (state.pending) {
        return;
      }

      var dirty = state.snapshot !== serializeFormState(form);
      if (dirty) {
        setSaveFeedbackState(form, button, 'dirty', 'Perubahan belum disimpan.');
        return;
      }

      if (state.lastState === 'success') {
        setSaveFeedbackState(form, button, 'success', 'Perubahan terkini sudah disimpan.');
        return;
      }

      if (state.lastState === 'warning') {
        setSaveFeedbackState(form, button, 'warning', 'Tetapan disimpan tetapi ada amaran yang perlu disemak.');
        return;
      }

      setSaveFeedbackState(form, button, 'idle', 'Belum ada perubahan baru untuk disimpan.');
    };

    const syncEmailFormState = function (form, emailSettings) {
      if (!form || !emailSettings) {
        return;
      }

      ['mail_driver', 'mail_host', 'mail_port', 'mail_username', 'mail_encryption', 'mail_from_address', 'mail_from_name'].forEach(function (name) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) {
          return;
        }
        field.value = emailSettings[name] || '';
        clearFieldValidationState(field);
        field.classList.remove('is-valid');
      });

      var passwordField = form.querySelector('[name="mail_password"]');
      if (passwordField) {
        passwordField.value = '';
        clearFieldValidationState(passwordField);
        passwordField.classList.remove('is-valid');
      }
    };

    const syncThemeFormState = function (form, themeSettings) {
      if (!form || !themeSettings) {
        return;
      }

      var mapping = {
        layoutMode: 'layout_mode',
        topbarColor: 'topbar_color',
        sidebarColor: 'sidebar_color'
      };

      Object.keys(mapping).forEach(function (key) {
        var inputName = mapping[key];
        var expectedValue = String(themeSettings[key] || '');
        if (!expectedValue) {
          return;
        }
        var target = form.querySelector('input[name="' + inputName + '"][value="' + expectedValue + '"]');
        if (target) {
          target.checked = true;
        }
      });

      if (typeof window.__tetapanSyncThemeSectionUi === 'function') {
        window.__tetapanSyncThemeSectionUi();
      }
    };

    const syncLanguageSelectionUi = function (form) {
      if (!form) {
        return;
      }

      var languageCheckboxes = Array.from(form.querySelectorAll('input[name="languages[]"]'));
      var defaultRadios = Array.from(form.querySelectorAll('input[name="default_language"]'));
      var activeCheckboxes = languageCheckboxes.filter(function (input) {
        return input.checked;
      });

      defaultRadios.forEach(function (radio) {
        var relatedCheckbox = form.querySelector('#lang_' + radio.value);
        var isActive = !!(relatedCheckbox && relatedCheckbox.checked);
        radio.disabled = !isActive;
        if (!isActive) {
          radio.checked = false;
        }

        var row = radio.closest('tr');
        if (!row) {
          return;
        }

        row.classList.toggle('table-success', isActive);
        row.classList.toggle('language-row-active', isActive);

        var badgeHost = row.querySelector('td:nth-child(4) .d-flex.align-items-center');
        if (!badgeHost) {
          return;
        }

        badgeHost.querySelectorAll('.js-language-active-badge, .js-language-default-badge').forEach(function (badge) {
          badge.remove();
        });

        if (isActive) {
          var activeBadge = document.createElement('span');
          activeBadge.className = 'badge bg-success-subtle text-success border border-success-subtle js-language-active-badge';
          activeBadge.innerHTML = '<i class="ri-checkbox-circle-fill me-1"></i> ' + ((__('config_tab_bahasa_status_aktif')) || 'Aktif');
          badgeHost.appendChild(activeBadge);
        }
      });

      var selectedDefault = form.querySelector('input[name="default_language"]:checked');
      if (!selectedDefault && activeCheckboxes.length > 0) {
        var fallbackRadio = form.querySelector('#default_lang_' + activeCheckboxes[0].value);
        if (fallbackRadio && !fallbackRadio.disabled) {
          fallbackRadio.checked = true;
          selectedDefault = fallbackRadio;
        }
      }

      if (selectedDefault) {
        var selectedRow = selectedDefault.closest('tr');
        var selectedBadgeHost = selectedRow ? selectedRow.querySelector('td:nth-child(4) .d-flex.align-items-center') : null;
        if (selectedBadgeHost) {
          var defaultBadge = document.createElement('span');
          defaultBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle ms-2 js-language-default-badge';
          defaultBadge.innerHTML = '<i class="ri-star-fill me-1"></i> ' + ((__('config_tab_bahasa_default')) || 'Bahasa Lalai');
          selectedBadgeHost.appendChild(defaultBadge);
        }
        document.documentElement.lang = selectedDefault.value;
      }
    };

    const syncLanguageFormState = function (form, languageData) {
      if (!form || !languageData) {
        return;
      }

      var active = Array.isArray(languageData.active) ? languageData.active : [];
      var defaultLanguage = String(languageData.default || '');

      form.querySelectorAll('input[name="languages[]"]').forEach(function (checkbox) {
        checkbox.checked = active.indexOf(checkbox.value) !== -1;
      });
      form.querySelectorAll('input[name="default_language"]').forEach(function (radio) {
        radio.checked = defaultLanguage !== '' && radio.value === defaultLanguage;
      });

      syncLanguageSelectionUi(form);
    };

    const syncDatabaseFormState = function (form, runtime) {
      if (!form || !runtime) {
        return;
      }

      var mainMysqlEnvironment = String(runtime.mainMysqlEnvironment || '');
      var environment = String(runtime.dbRenderEnvironment || '');
      var mode = String(runtime.dbRenderOperationalMode || '');

      if (mainMysqlEnvironment) {
        var mysqlRadio = form.querySelector('input[name="main_db_environment"][value="' + mainMysqlEnvironment + '"]');
        if (mysqlRadio) {
          mysqlRadio.checked = true;
        }
      }

      if (environment) {
        var envRadio = form.querySelector('input[name="sybase_environment"][value="' + environment + '"]');
        if (envRadio) {
          envRadio.checked = true;
        }
      }

      if (mode) {
        var modeRadio = form.querySelector('input[name="sybase_operational_mode"][value="' + mode + '"]');
        if (modeRadio) {
          modeRadio.checked = true;
        }
      }
    };

    const applyPayloadUiSync = function (payload, form) {
      if (!payload || typeof payload !== 'object') {
        return;
      }

      if (payload.tab === 'db' && payload.data && payload.data.dbRuntime) {
        updateDatabaseRuntimeSummary(payload.data.dbRuntime);
        syncDatabaseFormState(form, payload.data.dbRuntime);
      }

      if (payload.tab === 'db' && payload.data && Array.isArray(payload.data.additionalConnections)) {
        additionalConnections = payload.data.additionalConnections.slice();
        renderAdditionalConnectionsTable();
      }

      if (payload.tab === 'theme' && payload.data && payload.data.themeSettings) {
        applySavedThemeSettings(payload.data.themeSettings);
        syncThemeFormState(form, payload.data.themeSettings);
      }

      if (payload.tab === 'general' && payload.data && payload.data.generalSettings) {
        applyGeneralSettings(payload.data.generalSettings);
      }

      if (payload.tab === 'email' && payload.data && payload.data.emailSettings) {
        syncEmailFormState(form, payload.data.emailSettings);
      }

      if (payload.tab === 'lang' && payload.data && payload.data.languageData) {
        syncLanguageFormState(form, payload.data.languageData);
      }

      if (payload.tab === 'auth') {
        if (typeof window.__tetapanRefreshAuthPolicySummary === 'function') {
          window.__tetapanRefreshAuthPolicySummary();
        }
      }
    };
    const dbAdditionalTableBody = document.getElementById('db-additional-table-body');
    const dbAdditionalEmpty = document.getElementById('db-additional-empty');
    const dbAdditionalCounter = document.getElementById('db-additional-counter');
    const dbAdditionalSearch = document.getElementById('db-additional-search');
    const dbAdditionalFamilyFilter = document.getElementById('db-additional-family-filter');
    const dbAdditionalStatusFilter = document.getElementById('db-additional-status-filter');
    const dbAdditionalCreateButton = document.getElementById('btn-db-additional-create');
    const dbAdditionalRefreshButton = document.getElementById('btn-db-additional-refresh');
    const dbAdditionalModalEl = document.getElementById('db-additional-modal');
    const dbAdditionalForm = document.getElementById('form-db-additional');
    const dbAdditionalSaveButton = document.getElementById('btn-db-additional-save');
    const dbAdditionalEnvRows = document.getElementById('db-additional-env-rows');
    const dbAdditionalEnvAddButton = document.getElementById('btn-db-additional-env-add');

    const escapeHtml = function (value) {
      return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    };

    const getAdditionalConnectionFilters = function () {
      return {
        search: dbAdditionalSearch ? String(dbAdditionalSearch.value || '').trim().toLowerCase() : '',
        family: dbAdditionalFamilyFilter ? String(dbAdditionalFamilyFilter.value || '').trim().toLowerCase() : '',
        status: dbAdditionalStatusFilter ? String(dbAdditionalStatusFilter.value || '').trim().toLowerCase() : ''
      };
    };

    const flattenEnvSummary = function (envRows) {
      if (!Array.isArray(envRows) || envRows.length === 0) {
        return [];
      }

      return envRows.map(function (row) {
        var environment = String(row.f_environment || '-');
        var osFamily = String(row.f_os_family || 'any');
        var driver = String(row.f_driver || '-');
        return environment + ' / ' + osFamily + ' / ' + driver;
      });
    };

    const getLastTestSummary = function (envRows) {
      if (!Array.isArray(envRows) || envRows.length === 0) {
        return __('config_tab_db_additional_last_test_none') || 'Belum diuji';
      }

      var datedRows = envRows
        .filter(function (row) { return row && row.f_last_tested_at; })
        .sort(function (a, b) {
          return String(b.f_last_tested_at || '').localeCompare(String(a.f_last_tested_at || ''));
        });

      if (!datedRows.length) {
        return __('config_tab_db_additional_last_test_none') || 'Belum diuji';
      }

      var latest = datedRows[0];
      var status = String(latest.f_last_test_status || '').toUpperCase();
      return status + ' · ' + String(latest.f_last_tested_at || '');
    };

    const getFilteredAdditionalConnections = function () {
      var filters = getAdditionalConnectionFilters();

      return additionalConnections.filter(function (item) {
        var family = String(item.f_family || '').toLowerCase();
        var enabled = !!Number(item.f_is_enabled || 0);
        var haystack = [
          item.f_code,
          item.f_name,
          item.f_family,
          item.f_purpose,
          item.f_notes
        ].join(' ').toLowerCase();

        if (filters.search && haystack.indexOf(filters.search) === -1) {
          return false;
        }
        if (filters.family && family !== filters.family) {
          return false;
        }
        if (filters.status === 'enabled' && !enabled) {
          return false;
        }
        if (filters.status === 'disabled' && enabled) {
          return false;
        }
        return true;
      });
    };

    const renderAdditionalConnectionsTable = function () {
      if (!dbAdditionalTableBody) {
        return;
      }

      var items = getFilteredAdditionalConnections();
      if (dbAdditionalCounter) {
        dbAdditionalCounter.textContent = String(items.length);
      }

      if (!items.length) {
        dbAdditionalTableBody.innerHTML = '';
        if (dbAdditionalEmpty) {
          dbAdditionalEmpty.classList.remove('d-none');
        }
        return;
      }

      if (dbAdditionalEmpty) {
        dbAdditionalEmpty.classList.add('d-none');
      }

      dbAdditionalTableBody.innerHTML = items.map(function (item) {
        var code = String(item.f_code || '');
        var envSummary = flattenEnvSummary(item.env_rows || []);
        var enabled = !!Number(item.f_is_enabled || 0);
        var statusBadge = enabled
          ? '<span class="badge bg-success-subtle text-success">Enabled</span>'
          : '<span class="badge bg-secondary-subtle text-secondary">Disabled</span>';
        var family = escapeHtml(item.f_family || '-');
        var purpose = escapeHtml(item.f_purpose || '-');
        var name = escapeHtml(item.f_name || code || '-');
        var envHtml = envSummary.length
          ? envSummary.map(function (label) {
              return '<span class="db-additional-pill">' + escapeHtml(label) + '</span>';
            }).join('')
          : '<span class="text-muted small">No env rows</span>';
        return ''
          + '<tr data-connection-code="' + escapeHtml(code) + '">'
          +   '<td>'
          +     '<div class="db-additional-code">' + escapeHtml(code) + '</div>'
          +     '<div class="db-additional-meta"><span class="badge bg-light text-dark border">' + family + '</span></div>'
          +   '</td>'
          +   '<td>' + name + '</td>'
          +   '<td><span class="badge bg-info-subtle text-info">' + family.toUpperCase() + '</span></td>'
          +   '<td>' + purpose + '</td>'
          +   '<td><div class="db-additional-meta">' + envHtml + '</div></td>'
          +   '<td>' + statusBadge + '</td>'
          +   '<td><div class="db-additional-test-result">' + escapeHtml(getLastTestSummary(item.env_rows || [])) + '</div></td>'
          +   '<td class="text-end">'
          +     '<div class="db-additional-actions">'
          +       '<button type="button" class="btn btn-sm btn-outline-secondary icon-btn" title="Schema Preview" aria-label="Schema Preview" data-db-additional-action="schema" data-code="' + escapeHtml(code) + '"><i class="ri-table-line"></i></button>'
          +       '<button type="button" class="btn btn-sm btn-outline-info icon-btn" title="' + escapeHtml((__('config_tab_db_additional_inspect_title')) || 'Additional Connection Details') + '" aria-label="' + escapeHtml((__('config_tab_db_additional_inspect_title')) || 'Additional Connection Details') + '" data-db-additional-action="inspect" data-code="' + escapeHtml(code) + '"><i class="ri-eye-line"></i></button>'
          +       '<button type="button" class="btn btn-sm btn-outline-primary icon-btn" title="Edit" aria-label="Edit" data-db-additional-action="edit" data-code="' + escapeHtml(code) + '"><i class="ri-edit-line"></i></button>'
          +       '<button type="button" class="btn btn-sm btn-outline-success icon-btn" title="Test Connection" aria-label="Test Connection" data-db-additional-action="test" data-code="' + escapeHtml(code) + '"><i class="ri-plug-line"></i></button>'
          +       '<button type="button" class="btn btn-sm ' + (enabled ? 'btn-outline-warning' : 'btn-outline-secondary') + ' icon-btn" title="' + (enabled ? 'Disable' : 'Enable') + '" aria-label="' + (enabled ? 'Disable' : 'Enable') + '" data-db-additional-action="toggle" data-code="' + escapeHtml(code) + '" data-enabled="' + (enabled ? '1' : '0') + '"><i class="ri-power-line"></i></button>'
          +     '</div>'
          +   '</td>'
          + '</tr>';
      }).join('');
    };

    const showAdditionalConnectionProbe = function (probe) {
      if (!(window.Swal && typeof window.Swal.fire === 'function')) {
        return;
      }

      var value = function (key) {
        return escapeHtml(probe && probe[key] != null && probe[key] !== '' ? probe[key] : '-');
      };

      var html = ''
        + '<div class="text-start">'
        +   '<table class="table table-sm align-middle mb-0">'
        +     '<tbody>'
        +       '<tr><th style="width:180px">Code</th><td><code>' + value('connection_code') + '</code></td></tr>'
        +       '<tr><th>Name</th><td>' + value('connection_name') + '</td></tr>'
        +       '<tr><th>Family</th><td>' + value('family') + '</td></tr>'
        +       '<tr><th>Purpose</th><td>' + value('purpose') + '</td></tr>'
        +       '<tr><th>Environment</th><td>' + value('environment') + '</td></tr>'
        +       '<tr><th>OS Family</th><td>' + value('os_family') + '</td></tr>'
        +       '<tr><th>' + ((__('config_tab_db_additional_configured_driver')) || 'Configured Driver') + '</th><td>' + value('configured_driver') + '</td></tr>'
        +       '<tr><th>' + ((__('config_tab_db_additional_active_driver')) || 'Active Driver') + '</th><td>' + value('active_driver') + '</td></tr>'
        +       '<tr><th>Host</th><td>' + value('host') + '</td></tr>'
        +       '<tr><th>Port</th><td>' + value('port') + '</td></tr>'
        +       '<tr><th>' + ((__('config_tab_db_additional_database')) || 'Database') + '</th><td>' + value('database_name') + '</td></tr>'
        +       '<tr><th>' + ((__('config_tab_db_additional_current_db')) || 'Current Database') + '</th><td>' + value('current_database') + '</td></tr>'
        +       '<tr><th>' + ((__('config_tab_db_additional_current_user')) || 'Current User') + '</th><td>' + value('current_user') + '</td></tr>'
        +       '<tr><th>' + ((__('config_tab_db_additional_server_time')) || 'Server Time') + '</th><td>' + value('server_time') + '</td></tr>'
        +       '<tr><th>' + ((__('config_tab_db_additional_server_version')) || 'Server Version') + '</th><td>' + value('server_version') + '</td></tr>'
        +       '<tr><th>' + ((__('config_tab_db_additional_ping')) || 'Ping') + '</th><td>' + value('ping') + '</td></tr>'
        +     '</tbody>'
        +   '</table>'
        + '</div>';

      window.Swal.fire({
        icon: 'info',
        title: __('config_tab_db_additional_inspect_title') || 'Additional Connection Details',
        html: html,
        width: 760,
        confirmButtonText: __('config_js_btn_ok') || 'OK'
      });
    };

    const showAdditionalConnectionSchemaPreview = function (schemaPreview) {
      if (!(window.Swal && typeof window.Swal.fire === 'function')) {
        return;
      }

      var objects = Array.isArray(schemaPreview && schemaPreview.objects) ? schemaPreview.objects : [];
      var rowsHtml = objects.length
        ? objects.map(function (item) {
            var code = encodeURIComponent(String(schemaPreview.connection_code || ''));
            var objectName = encodeURIComponent(String(item.object_name || ''));
            var environment = encodeURIComponent(String(schemaPreview.environment || 'production'));
            var osFamily = encodeURIComponent(String(schemaPreview.os_family || 'any'));
            var driver = encodeURIComponent(String(schemaPreview.driver || ''));
            return '<tr><td>' + escapeHtml(item.object_name || '-') + '</td><td>' + escapeHtml(item.object_type || '-') + '</td><td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary" onclick="return window.__tetapanDataPreviewAdditionalConnection && window.__tetapanDataPreviewAdditionalConnection(\'' + code + '\', \'' + objectName + '\', \'' + environment + '\', \'' + osFamily + '\', \'' + driver + '\', this)"><i class="ri-file-search-line"></i></button></td></tr>';
          }).join('')
        : '<tr><td colspan="3" class="text-muted text-center py-3">' + ((__('config_tab_db_additional_no_objects')) || 'No objects found.') + '</td></tr>';

      var html = ''
        + '<div class="text-start mb-3">'
        +   '<div><strong>Code:</strong> <code>' + escapeHtml(schemaPreview.connection_code || '-') + '</code></div>'
        +   '<div><strong>Family:</strong> ' + escapeHtml(schemaPreview.family || '-') + '</div>'
        +   '<div><strong>Environment:</strong> ' + escapeHtml(schemaPreview.environment || '-') + '</div>'
        +   '<div><strong>Database:</strong> ' + escapeHtml(schemaPreview.database_name || '-') + '</div>'
        + '</div>'
        + '<div class="table-responsive">'
        +   '<table class="table table-sm align-middle mb-0">'
        +     '<thead><tr><th>' + ((__('config_tab_db_additional_object_name')) || 'Object Name') + '</th><th style="width:140px">' + ((__('config_tab_db_additional_object_type')) || 'Type') + '</th><th class="text-end" style="width:96px">' + ((__('config_tab_db_additional_preview_action')) || 'Preview') + '</th></tr></thead>'
        +     '<tbody>' + rowsHtml + '</tbody>'
        +   '</table>'
        + '</div>';

      window.Swal.fire({
        icon: 'info',
        title: __('config_tab_db_additional_schema_title') || 'Schema Preview',
        html: html,
        width: 820,
        confirmButtonText: __('config_js_btn_ok') || 'OK'
      });
    };

    window.__tetapanDataPreviewAdditionalConnection = function (encodedCode, encodedObjectName, encodedEnvironment, encodedOsFamily, encodedDriver, buttonEl) {
      var code = decodeURIComponent(String(encodedCode || ''));
      var objectName = decodeURIComponent(String(encodedObjectName || ''));
      var environment = decodeURIComponent(String(encodedEnvironment || 'production'));
      var osFamily = decodeURIComponent(String(encodedOsFamily || 'any'));
      var driver = decodeURIComponent(String(encodedDriver || ''));

      postAdditionalConnectionAction('db_additional_object_preview', {
        connection_code: code,
        object_name: objectName,
        environment: environment,
        os_family: osFamily,
        driver: driver
      }, buttonEl)
        .then(function (payload) {
          if (!payload || payload.success !== true || !payload.data || !payload.data.objectPreview) {
            throw new Error((payload && payload.message) || 'Gagal memuatkan data preview sambungan tambahan.');
          }

          var preview = payload.data.objectPreview;
          var columns = Array.isArray(preview.columns) ? preview.columns : [];
          var rows = Array.isArray(preview.rows) ? preview.rows : [];
          var headerHtml = columns.map(function (column) {
            return '<th>' + escapeHtml(column) + '</th>';
          }).join('');
          var bodyHtml = rows.length
            ? rows.map(function (row) {
                return '<tr>' + columns.map(function (column) {
                  var value = row && row[column] != null ? String(row[column]) : '';
                  return '<td>' + escapeHtml(value) + '</td>';
                }).join('') + '</tr>';
              }).join('')
            : '<tr><td colspan="' + Math.max(columns.length, 1) + '" class="text-muted text-center py-3">' + ((__('config_tab_db_additional_no_rows')) || 'No rows found.') + '</td></tr>';

          var html = ''
            + '<div class="text-start mb-3">'
            + '<div><strong>Code:</strong> <code>' + escapeHtml(preview.connection_code || '-') + '</code></div>'
            + '<div><strong>Object:</strong> ' + escapeHtml(preview.object_name || '-') + '</div>'
            + '<div><strong>Environment:</strong> ' + escapeHtml(preview.environment || '-') + '</div>'
            + '<div><strong>Database:</strong> ' + escapeHtml(preview.database_name || '-') + '</div>'
            + '</div>'
            + '<div class="table-responsive"><table class="table table-sm align-middle mb-0">'
            + '<thead><tr>' + headerHtml + '</tr></thead>'
            + '<tbody>' + bodyHtml + '</tbody>'
            + '</table></div>';

          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              icon: 'info',
              title: __('config_tab_db_additional_data_preview_title') || 'Data Preview',
              html: html,
              width: 960,
              confirmButtonText: __('config_js_btn_ok') || 'OK'
            });
          }
        })
        .catch(function (error) {
          showTetapanSystemError(error && error.message ? error.message : 'Gagal memuatkan data preview sambungan tambahan.');
        });

      return false;
    };

    const ensureAdditionalModalMountedToBody = function () {
      if (!dbAdditionalModalEl) {
        return null;
      }

      if (dbAdditionalModalEl.parentElement !== document.body) {
        document.body.appendChild(dbAdditionalModalEl);
      }

      return dbAdditionalModalEl;
    };

    const getAdditionalModalInstance = function () {
      const mountedModalEl = ensureAdditionalModalMountedToBody();
      if (!mountedModalEl || !(window.bootstrap && window.bootstrap.Modal)) {
        return null;
      }
      return window.bootstrap.Modal.getOrCreateInstance(mountedModalEl);
    };

    ensureAdditionalModalMountedToBody();

    const buildEnvRowMarkup = function (row, index) {
      var safe = Object.assign({
        f_environment: 'production',
        f_os_family: 'any',
        f_driver: 'mysql',
        f_host: '',
        f_port: '',
        f_database_name: '',
        f_dsn_name: '',
        f_username: '',
        f_password_ciphertext: '',
        f_charset: 'utf8mb4',
        f_is_active: true
      }, row || {});

      return ''
        + '<div class="db-additional-env-row" data-env-row>'
        +   '<div class="db-additional-env-row-header">'
        +     '<div>'
        +       '<div class="db-additional-env-row-index">Env Row ' + (index + 1) + '</div>'
        +       '<div class="db-additional-inline-help">Setiap row mewakili satu kombinasi environment, OS, dan driver.</div>'
        +     '</div>'
        +     '<button type="button" class="btn btn-sm btn-outline-danger" data-env-row-remove><i class="ri-delete-bin-line me-1"></i>Remove</button>'
        +   '</div>'
        +   '<div class="row g-3">'
        +     '<div class="col-md-3"><label class="form-label">Environment</label><select class="form-select" data-env-field="f_environment"><option value="production"' + (safe.f_environment === 'production' ? ' selected' : '') + '>Production</option><option value="development"' + (safe.f_environment === 'development' ? ' selected' : '') + '>Development</option></select></div>'
        +     '<div class="col-md-3"><label class="form-label">OS Family</label><select class="form-select" data-env-field="f_os_family"><option value="any"' + (safe.f_os_family === 'any' ? ' selected' : '') + '>Any</option><option value="windows"' + (safe.f_os_family === 'windows' ? ' selected' : '') + '>Windows</option><option value="linux"' + (safe.f_os_family === 'linux' ? ' selected' : '') + '>Linux</option></select></div>'
        +     '<div class="col-md-3"><label class="form-label">Driver</label><select class="form-select" data-env-field="f_driver"><option value="mysql"' + (safe.f_driver === 'mysql' ? ' selected' : '') + '>mysql</option><option value="odbc"' + (safe.f_driver === 'odbc' ? ' selected' : '') + '>odbc</option><option value="dblib"' + (safe.f_driver === 'dblib' ? ' selected' : '') + '>dblib</option><option value="sqlsrv"' + (safe.f_driver === 'sqlsrv' ? ' selected' : '') + '>sqlsrv</option></select></div>'
        +     '<div class="col-md-3"><label class="form-label">Active</label><div class="form-check form-switch pt-2"><input class="form-check-input" type="checkbox" data-env-field="f_is_active"' + (safe.f_is_active ? ' checked' : '') + '></div></div>'
        +     '<div class="col-md-4"><label class="form-label">Host</label><input type="text" class="form-control" data-env-field="f_host" value="' + escapeHtml(safe.f_host) + '"></div>'
        +     '<div class="col-md-2"><label class="form-label">Port</label><input type="text" class="form-control" data-env-field="f_port" value="' + escapeHtml(safe.f_port) + '"></div>'
        +     '<div class="col-md-3"><label class="form-label">Database</label><input type="text" class="form-control" data-env-field="f_database_name" value="' + escapeHtml(safe.f_database_name) + '"></div>'
        +     '<div class="col-md-3"><label class="form-label">DSN</label><input type="text" class="form-control" data-env-field="f_dsn_name" value="' + escapeHtml(safe.f_dsn_name) + '"></div>'
        +     '<div class="col-md-4"><label class="form-label">Username</label><input type="text" class="form-control" data-env-field="f_username" value="' + escapeHtml(safe.f_username) + '"></div>'
        +     '<div class="col-md-4"><label class="form-label">Password</label><input type="password" class="form-control" data-env-field="f_password_ciphertext" value="' + escapeHtml(safe.f_password_ciphertext) + '"></div>'
        +     '<div class="col-md-4"><label class="form-label">Charset</label><input type="text" class="form-control" data-env-field="f_charset" value="' + escapeHtml(safe.f_charset) + '"></div>'
        +   '</div>'
        + '</div>';
    };

    const reindexEnvRows = function () {
      if (!dbAdditionalEnvRows) {
        return;
      }
      Array.from(dbAdditionalEnvRows.querySelectorAll('[data-env-row]')).forEach(function (row, index) {
        var label = row.querySelector('.db-additional-env-row-index');
        if (label) {
          label.textContent = 'Env Row ' + (index + 1);
        }
      });
    };

    const appendEnvRow = function (row) {
      if (!dbAdditionalEnvRows) {
        return;
      }
      var wrapper = document.createElement('div');
      wrapper.innerHTML = buildEnvRowMarkup(row, dbAdditionalEnvRows.querySelectorAll('[data-env-row]').length);
      var child = wrapper.firstElementChild;
      if (child) {
        dbAdditionalEnvRows.appendChild(child);
        reindexEnvRows();
      }
    };

    const resetAdditionalConnectionForm = function () {
      if (!dbAdditionalForm) {
        return;
      }
      dbAdditionalForm.reset();
      document.getElementById('db-additional-form-type').value = 'db_additional_create';
      document.getElementById('db-additional-existing-code').value = '';
      document.getElementById('db-additional-code').readOnly = false;
      document.getElementById('db-additional-enabled').checked = true;
      document.getElementById('db-additional-supports-prod').checked = true;
      document.getElementById('db-additional-supports-dev').checked = false;
      var titleEl = document.getElementById('db-additional-modal-title');
      if (titleEl) {
        titleEl.textContent = __('config_tab_db_additional_modal_add') || 'Add Additional Connection';
      }
      if (dbAdditionalEnvRows) {
        dbAdditionalEnvRows.innerHTML = '';
      }
      appendEnvRow({ f_environment: 'production', f_os_family: 'any', f_driver: 'mysql', f_charset: 'utf8mb4', f_is_active: true });
    };

    const openAdditionalConnectionModal = function (connection) {
      if (!dbAdditionalForm) {
        return;
      }

      ensureAdditionalModalMountedToBody();
      resetAdditionalConnectionForm();

      if (connection) {
        document.getElementById('db-additional-form-type').value = 'db_additional_update';
        document.getElementById('db-additional-existing-code').value = String(connection.f_code || '');
        document.getElementById('db-additional-code').value = String(connection.f_code || '');
        document.getElementById('db-additional-code').readOnly = true;
        document.getElementById('db-additional-name').value = String(connection.f_name || '');
        document.getElementById('db-additional-purpose').value = String(connection.f_purpose || '');
        document.getElementById('db-additional-family').value = String(connection.f_family || 'mysql');
        document.getElementById('db-additional-driver-mode').value = String(connection.f_driver_mode || 'auto');
        document.getElementById('db-additional-notes').value = String(connection.f_notes || '');
        document.getElementById('db-additional-enabled').checked = !!Number(connection.f_is_enabled || 0);
        document.getElementById('db-additional-supports-prod').checked = !!Number(connection.f_supports_prod || 0);
        document.getElementById('db-additional-supports-dev').checked = !!Number(connection.f_supports_dev || 0);
        if (dbAdditionalEnvRows) {
          dbAdditionalEnvRows.innerHTML = '';
        }
        (Array.isArray(connection.env_rows) && connection.env_rows.length ? connection.env_rows : []).forEach(function (row) {
          appendEnvRow(row);
        });
        if (!dbAdditionalEnvRows.querySelector('[data-env-row]')) {
          appendEnvRow({ f_environment: 'production', f_os_family: 'any', f_driver: 'mysql', f_charset: 'utf8mb4', f_is_active: true });
        }
        var titleEl = document.getElementById('db-additional-modal-title');
        if (titleEl) {
          titleEl.textContent = __('config_tab_db_additional_modal_edit') || 'Edit Additional Connection';
        }
      }

      var modal = getAdditionalModalInstance();
      if (modal) {
        modal.show();
        return;
      }

      if (dbAdditionalModalEl) {
        dbAdditionalModalEl.style.display = 'block';
        dbAdditionalModalEl.classList.add('show');
        dbAdditionalModalEl.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
      }
    };

    window.__tetapanOpenAdditionalConnectionModal = function (code) {
      if (code) {
        var existing = additionalConnections.find(function (item) {
          return String(item.f_code || '') === String(code);
        }) || null;
        openAdditionalConnectionModal(existing);
        return false;
      }

      openAdditionalConnectionModal(null);
      return false;
    };

    const serializeAdditionalEnvRows = function () {
      if (!dbAdditionalEnvRows) {
        return [];
      }

      return Array.from(dbAdditionalEnvRows.querySelectorAll('[data-env-row]')).map(function (row) {
        var payload = {};
        row.querySelectorAll('[data-env-field]').forEach(function (field) {
          var key = field.getAttribute('data-env-field');
          if (!key) {
            return;
          }
          if (field.type === 'checkbox') {
            payload[key] = !!field.checked;
            return;
          }
          payload[key] = String(field.value || '').trim();
        });
        return payload;
      });
    };

    const postAdditionalConnectionAction = function (formType, extraData, button) {
      var payload = new FormData();
      payload.set('ajax', '1');
      payload.set('csrf_token', csrfToken);
      payload.set('form_type', formType);

      Object.keys(extraData || {}).forEach(function (key) {
        payload.set(key, extraData[key]);
      });

      fallbackSetButtonLoading(button, true);

      return fetch(window.location.href, {
        method: 'POST',
        body: payload,
        noLoader: true,
        headers: Object.assign({
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-No-Loader': '1'
        }, csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
      }).then(function (response) {
        return response.json().catch(function () {
          throw new Error(__('config_js_invalid_server_response') || 'Respons pelayan tidak sah.');
        });
      }).finally(function () {
        fallbackSetButtonLoading(button, false);
      });
    };

    window.__tetapanRefreshAdditionalConnections = function (buttonEl) {
      postAdditionalConnectionAction('db_additional_list', {}, buttonEl || dbAdditionalRefreshButton)
        .then(function (payload) {
          if (!payload || payload.success !== true) {
            throw new Error((payload && payload.message) || 'Gagal memuat semula sambungan tambahan.');
          }
          if (payload.data && Array.isArray(payload.data.additionalConnections)) {
            additionalConnections = payload.data.additionalConnections.slice();
            renderAdditionalConnectionsTable();
          }
        })
        .catch(function (error) {
          showTetapanSystemError(error && error.message ? error.message : 'Gagal memuat semula sambungan tambahan.');
        });
      return false;
    };

    window.__tetapanSaveAdditionalConnection = function (buttonEl) {
      if (!dbAdditionalForm) {
        showTetapanSystemError('Borang sambungan tambahan tidak tersedia.');
        return false;
      }

      var formTypeField = document.getElementById('db-additional-form-type');
      var currentFormType = formTypeField ? String(formTypeField.value || 'db_additional_create') : 'db_additional_create';
      var extraData = {
        f_code: document.getElementById('db-additional-code').value,
        f_name: document.getElementById('db-additional-name').value,
        f_purpose: document.getElementById('db-additional-purpose').value,
        f_family: document.getElementById('db-additional-family').value,
        f_driver_mode: document.getElementById('db-additional-driver-mode').value,
        f_notes: document.getElementById('db-additional-notes').value,
        f_is_enabled: document.getElementById('db-additional-enabled').checked ? '1' : '0',
        f_supports_prod: document.getElementById('db-additional-supports-prod').checked ? '1' : '0',
        f_supports_dev: document.getElementById('db-additional-supports-dev').checked ? '1' : '0',
        existing_code: document.getElementById('db-additional-existing-code').value,
        env_rows: JSON.stringify(serializeAdditionalEnvRows())
      };

      postAdditionalConnectionAction(currentFormType, extraData, buttonEl || dbAdditionalSaveButton)
        .then(function (payload) {
          if (!payload || payload.success !== true) {
            throw new Error((payload && payload.message) || 'Gagal menyimpan sambungan tambahan.');
          }
          applyPayloadUiSync(payload, formDB);
          var modal = getAdditionalModalInstance();
          if (modal) {
            modal.hide();
          }
          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              icon: 'success',
              title: payload.title || 'Berjaya',
              text: payload.message || 'Sambungan tambahan berjaya disimpan.',
              confirmButtonText: __('config_js_btn_ok') || 'OK'
            });
          }
        })
        .catch(function (error) {
          showTetapanSystemError(error && error.message ? error.message : 'Gagal menyimpan sambungan tambahan.');
        });

      return false;
    };
    const cleanupOrphanedBackdrops = function () {
      const hasOpenModal = document.querySelector('.modal.show');
      const hasOpenOffcanvas = document.querySelector('.offcanvas.show');

      if (!hasOpenModal) {
        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
          backdrop.remove();
        });
      }

      if (!hasOpenOffcanvas) {
        document.querySelectorAll('.offcanvas-backdrop').forEach(function (backdrop) {
          backdrop.remove();
        });
      }

      if (!hasOpenModal && !hasOpenOffcanvas) {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
      }
    };
    const setButtonLoading = typeof pageUiHelper.setButtonLoading === 'function'
      ? function (button, loading) {
          pageUiHelper.setButtonLoading(button, loading, {
            loadingText: __('config_js_btn_loading_save')
          });
        }
      : function (button, loading) {
          if (!button) {
            return;
          }
          if (loading) {
            button.disabled = true;
            button.dataset.originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + __('config_js_btn_loading_save');
            return;
          }
          button.disabled = false;
          if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }
      };

    const bindBootstrapTabs = function () {
      if (!window.bootstrap || !window.bootstrap.Tab) {
        return;
      }

      document.querySelectorAll(
        '.container-fluid [data-bs-toggle="tab"], .container-fluid [data-bs-toggle="pill"]'
      ).forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
          if (trigger.tagName === 'A') {
            event.preventDefault();
          }
          window.bootstrap.Tab.getOrCreateInstance(trigger).show();
        });
      });
    };

    function getFieldLabel(field) {
      if (!field) {
        return __('config_js_field_fallback_label') || 'Ruangan';
      }

      const group = field.closest('.general-form-group, .email-settings-card, .db-settings-card, .theme-settings-card, .lang-settings-card, .form-group, .col-12, .col-sm-6, .col-md-6, .col-lg-6');
      const scopedLabel = group ? group.querySelector('label.form-label') : null;
      const directLabel = scopedLabel || document.querySelector('label[for="' + field.id + '"]');
      const text = directLabel ? directLabel.textContent : (field.getAttribute('aria-label') || field.name || __('config_js_field_fallback_label') || 'Ruangan');
      return String(text || __('config_js_field_fallback_label') || 'Ruangan').replace(/\s+/g, ' ').trim();
    }

    function clearFieldValidationState(field) {
      if (!field) {
        return;
      }

      field.classList.remove('is-invalid');
      const container = field.parentElement;
      if (!container) {
        return;
      }

      container.querySelectorAll('.tetapan-invalid-feedback').forEach(function (feedback) {
        feedback.remove();
      });
    }

    function clearSubtabErrorMarkers(form) {
      if (!form) {
        return;
      }

      form.querySelectorAll('.nav-link.has-validation-error').forEach(function (tab) {
        tab.classList.remove('has-validation-error');
        tab.removeAttribute('data-validation-error');
      });
    }

    function markFieldInvalid(field, message) {
      if (!field) {
        return;
      }

      clearFieldValidationState(field);
      field.classList.add('is-invalid');

      const container = field.parentElement;
      if (!container) {
        return;
      }

      const feedback = document.createElement('div');
      feedback.className = 'invalid-feedback tetapan-invalid-feedback d-block';
      feedback.textContent = message || field.validationMessage || __('config_js_invalid_input') || 'Input tidak sah.';
      container.appendChild(feedback);
    }

    function getFieldSubtabTrigger(field, form) {
      if (!field || !form) {
        return null;
      }

      const pane = field.closest('.tab-pane');
      if (!pane || !pane.id) {
        return null;
      }

      return form.querySelector('[data-bs-target="#' + pane.id + '"]');
    }

    function markSubtabError(field, form) {
      const trigger = getFieldSubtabTrigger(field, form);
      if (!trigger) {
        return;
      }

      trigger.classList.add('has-validation-error');
      trigger.setAttribute('data-validation-error', '1');
    }

    function showValidationAlert(form) {
      if (!form || typeof form.checkValidity !== 'function' || form.checkValidity()) {
        return false;
      }

      const invalidFields = Array.from(form.querySelectorAll(':invalid'));
      if (!invalidFields.length) {
        return false;
      }

      clearSubtabErrorMarkers(form);

      const errorItems = invalidFields.map(function (field) {
        const fieldLabel = getFieldLabel(field);
        const trigger = getFieldSubtabTrigger(field, form);
        const tabLabel = trigger ? String(trigger.textContent || '').replace(/\s+/g, ' ').trim() : '';
        const message = field.validationMessage
          ? (fieldLabel + ': ' + field.validationMessage)
          : (fieldLabel + ': ' + ((__('config_js_invalid_input')) || 'Input tidak sah.'));

        markFieldInvalid(field, message);
        markSubtabError(field, form);

        return {
          tabLabel: tabLabel,
          message: message
        };
      });

      const firstInvalidField = invalidFields[0];
      if (firstInvalidField && typeof firstInvalidField.focus === 'function') {
        firstInvalidField.focus({ preventScroll: false });
      }

      if (window.Swal && typeof window.Swal.fire === 'function') {
        const html = errorItems.map(function (item) {
          const prefix = item.tabLabel ? ('<strong>' + item.tabLabel + ':</strong> ') : '';
          return '<div class="text-start mb-2">' + prefix + item.message + '</div>';
        }).join('');

        window.Swal.fire({
          icon: 'warning',
          title: __('config_general_validation_title') || 'Semakan Diperlukan',
          html: html,
          confirmButtonText: __('config_js_btn_ok') || 'OK'
        });
      } else {
        window.alert(errorItems.map(function (item) {
          return (item.tabLabel ? (item.tabLabel + ': ') : '') + item.message;
        }).join('\n'));
      }

      return true;
    }

    function submitFormDirect(form, button) {
      if (!form) {
        return false;
      }

      var state = getFormState(form);
      if (state.pending) {
        return false;
      }

      if (showValidationAlert(form)) {
        setSaveFeedbackState(form, button, 'error', 'Semak semula input yang ditanda sebelum menyimpan.');
        return false;
      }

      if (button) {
        setButtonLoading(button, true);
      }
      state.pending = true;
      setSaveFeedbackState(form, button, 'saving', 'Sistem sedang menyimpan perubahan anda...');

      const formData = new FormData(form);
      formData.set('ajax', '1');
      const requestedTab = formData.get('form_type') === 'auth_settings' ? '#auth-tab' : null;
      if (requestedTab) {
        rememberActiveTab(requestedTab);
      }

      const csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        : '';

      fetch(form.getAttribute('action') || window.location.href, {
        method: 'POST',
        body: formData,
        noLoader: true,
        headers: Object.assign({
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-No-Loader': '1'
        }, csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
      })
        .then(function (response) {
          return response.json().catch(function () {
            throw new Error(__('config_js_invalid_server_response') || 'Respons pelayan tidak sah.');
          });
        })
        .then(function (payload) {
          if (!payload || payload.success !== true) {
            const title = payload && payload.title ? payload.title : __('config_js_ralat') || 'Ralat';
            const errors = payload && Array.isArray(payload.errors) ? payload.errors : [];
            const message = payload && payload.message ? payload.message : __('config_js_save_failed') || 'Gagal menyimpan tetapan.';
            const html = errors.length
              ? errors.map(function (item) {
                  return '<div class="text-start mb-2">' + item + '</div>';
                }).join('')
              : '<div class="text-start">' + message + '</div>';

            if (window.Swal && typeof window.Swal.fire === 'function') {
              window.Swal.fire({
                icon: 'error',
                title: title,
                html: html,
                confirmButtonText: __('config_js_btn_ok') || 'OK'
              });
            } else {
              window.alert(message);
            }
            setSaveFeedbackState(form, button, 'error', message);
            return;
          }

          applyPayloadUiSync(payload, form);

          if (payload.tab) {
            activateTab('#' + payload.tab + '-tab');
          } else if (requestedTab) {
            activateTab(requestedTab);
          }

          captureFormSnapshot(form);

          var warnings = payload && Array.isArray(payload.warnings) ? payload.warnings : [];
          if (warnings.length > 0) {
            setSaveFeedbackState(form, button, 'warning', payload.message || 'Tetapan disimpan dengan amaran.');
            return;
          }

          setSaveFeedbackState(form, button, 'success', payload.message || __('config_js_save_success_default') || 'Tetapan berjaya disimpan.');
        })
        .catch(function (error) {
          const message = error && error.message ? error.message : __('config_js_save_system_error') || 'Ralat sistem semasa menyimpan tetapan.';
          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              icon: 'error',
              title: __('config_js_system_error_title') || 'Ralat Sistem',
              text: message,
              confirmButtonText: __('config_js_btn_ok') || 'OK'
            });
          } else {
            window.alert(message);
          }
          setSaveFeedbackState(form, button, 'error', message);
        })
        .finally(function () {
          state.pending = false;
          if (button) {
            setButtonLoading(button, false);
          }
          refreshDirtyIndicator(form, button);
        });

      return true;
    }

    window.__tetapanSubmitFormWithValidationImpl = function (form, button) {
      return submitFormDirect(form, button);
    };

    window.__tetapanSubmitAuthForm = function (event, form, buttonId) {
      if (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }

      const activeForm = typeof form === 'string' ? document.getElementById(form) : form;
      const button = document.getElementById(buttonId || 'btn-simpan-auth');
      if (!activeForm) {
        showTetapanSystemError();
        return false;
      }

      if (showValidationAlert(activeForm)) {
        return false;
      }

      if (!(window.Swal && typeof window.Swal.fire === 'function')) {
        return submitFormDirect(activeForm, button);
      }

      window.Swal.fire({
        icon: 'question',
        title: __('config_tab_auth') || 'Login Policy',
        text: __('config_js_confirm_auth') || 'Are you sure you want to save this login policy?',
        showCancelButton: true,
        confirmButtonText: __('config_js_btn_ya_simpan') || 'Yes, Save',
        cancelButtonText: __('config_alert_no') || 'Cancel'
      }).then(function (result) {
        if (!result.isConfirmed) {
          return;
        }
        submitFormDirect(activeForm, button);
      });

      return false;
    };

    window.__tetapanBeforeLanguageSubmit = function (activeForm) {
      if (!activeForm) {
        return false;
      }
      const checked = activeForm.querySelectorAll('input[name="languages[]"]:checked');
      if (checked.length === 0) {
        Swal.fire({ icon: 'warning', title: __('config_js_tiada_bahasa'), text: __('config_js_pilih_bahasa'), confirmButtonText: __('config_js_btn_ok') });
        return false;
      }
      const defaultLang = activeForm.querySelector('input[name="default_language"]:checked');
      if (!defaultLang) {
        Swal.fire({ icon: 'warning', title: __('config_js_tiada_bahasa_default'), text: __('config_js_pilih_bahasa_default'), confirmButtonText: __('config_js_btn_ok') });
        return false;
      }
      return true;
    };

    function updateDatabaseRuntimeSummary(runtime) {
      if (!runtime) {
        return;
      }

      const staffEl = document.getElementById('db-runtime-staff');
      const studentCell = document.getElementById('db-runtime-student-cell');
      const environmentEl = document.getElementById('db-runtime-environment');
      const modeEl = document.getElementById('db-runtime-mode');

      if (staffEl && typeof runtime.runtimeStaffBase === 'string') {
        staffEl.textContent = runtime.runtimeStaffBase;
      }

      if (studentCell && typeof runtime.studentRuntimeLabel === 'string') {
        if (runtime.dbRenderOperationalMode === 'staff_student') {
          studentCell.innerHTML = '<code class="text-primary" id="db-runtime-student"></code>';
        } else {
          studentCell.innerHTML = '<span class="badge bg-secondary-subtle text-secondary" id="db-runtime-student"></span>';
        }
        const studentEl = document.getElementById('db-runtime-student');
        if (studentEl) {
          studentEl.textContent = runtime.studentRuntimeLabel;
        }
      }

      if (environmentEl) {
        environmentEl.textContent = runtime.dbRenderEnvironment === 'development'
          ? (__('config_tab_db_environment_development') || 'Development')
          : (__('config_tab_db_environment_production') || 'Production');
      }

      if (modeEl) {
        modeEl.textContent = runtime.dbRenderOperationalMode === 'staff_student'
          ? (__('config_tab_db_mode_staff_student') || 'Staff + Student')
          : (__('config_tab_db_mode_staff_only') || 'Staff Only');
      }
    }

    function applySavedThemeSettings(themeSettings) {
      if (!themeSettings) {
        return;
      }

      if (themeSettings.layoutMode) {
        document.documentElement.setAttribute('data-bs-theme', themeSettings.layoutMode);
        document.body.setAttribute('data-bs-theme', themeSettings.layoutMode);
      }

      if (themeSettings.topbarColor) {
        document.body.setAttribute('data-topbar-color', themeSettings.topbarColor);
      }

      if (themeSettings.sidebarColor) {
        document.body.setAttribute('data-menu-color', themeSettings.sidebarColor);
      }

      if (typeof window.__tetapanSyncThemeSectionUi === 'function') {
        window.__tetapanSyncThemeSectionUi();
      }
    }

    function initThemeSectionInteractions(form) {
      if (!form || form.dataset.themeSectionsInitialized === '1') {
        return;
      }

      const storageKey = 'tetapan-sistem.theme-sections';
      const sections = Array.from(form.querySelectorAll('[data-theme-section]'));
      if (!sections.length) {
        return;
      }

      let storedState = {};
      try {
        storedState = JSON.parse(window.sessionStorage.getItem(storageKey) || '{}') || {};
      } catch (storageError) {
        storedState = {};
      }

      const persistState = function () {
        const nextState = {};
        sections.forEach(function (section) {
          const key = section.getAttribute('data-theme-section') || '';
          const toggle = section.querySelector('[data-theme-toggle]');
          if (key && toggle) {
            nextState[key] = toggle.getAttribute('aria-expanded') === 'true';
          }
        });
        try {
          window.sessionStorage.setItem(storageKey, JSON.stringify(nextState));
        } catch (storageError) {
          // ignore storage errors
        }
      };

      const setExpanded = function (section, expanded) {
        const toggle = section.querySelector('[data-theme-toggle]');
        const panel = section.querySelector('.theme-settings-panel');
        if (!toggle || !panel) {
          return;
        }

        section.classList.toggle('is-expanded', !!expanded);
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        panel.hidden = !expanded;
      };

      const syncSectionSummary = function (section) {
        const checkedInput = section.querySelector('input[type="radio"]:checked');
        const summaryLabel = section.querySelector('[data-theme-summary-label]');
        const summaryPreview = section.querySelector('[data-theme-summary-preview]');
        if (!checkedInput || !summaryLabel || !summaryPreview) {
          return;
        }

        const activeOption = checkedInput.closest('.theme-option');
        if (!activeOption) {
          return;
        }

        const nextLabel = activeOption.getAttribute('data-theme-label') || checkedInput.value || '';
        const nextPreview = activeOption.getAttribute('data-theme-preview') || '';

        summaryLabel.textContent = nextLabel;
        summaryPreview.style.cssText = nextPreview;

        section.querySelectorAll('.theme-option').forEach(function (option) {
          option.classList.toggle('active', option === activeOption);
        });
      };

      window.__tetapanSetThemeSectionExpanded = function (section, expanded) {
        if (!section) {
          return;
        }
        setExpanded(section, expanded);
        persistState();
      };

      window.__tetapanToggleThemeSection = function (toggleEl) {
        const button = toggleEl && toggleEl.closest ? toggleEl.closest('[data-theme-toggle]') : null;
        if (!button) {
          return false;
        }
        const section = button.closest('[data-theme-section]');
        if (!section) {
          return false;
        }
        const expanded = button.getAttribute('aria-expanded') === 'true';
        window.__tetapanSetThemeSectionExpanded(section, !expanded);
        return false;
      };

      sections.forEach(function (section) {
        const key = section.getAttribute('data-theme-section') || '';
        const toggle = section.querySelector('[data-theme-toggle]');
        const radios = Array.from(section.querySelectorAll('input[type="radio"]'));
        const shouldExpand = storedState[key] === true;

        setExpanded(section, shouldExpand);
        syncSectionSummary(section);

        radios.forEach(function (radio) {
          radio.addEventListener('change', function () {
            syncSectionSummary(section);
          });
        });
      });

      window.__tetapanSyncThemeSectionUi = function () {
        sections.forEach(syncSectionSummary);
      };

      form.dataset.themeSectionsInitialized = '1';
      persistState();
    }

    function validateField(field) {
      const name = field.name;
      const value = field.value.trim();
      let isValid = true;
      let message = '';

      const existingFeedback = field.parentElement.querySelector('.invalid-feedback');
      if (existingFeedback) {
        existingFeedback.remove();
      }
      field.classList.remove('is-invalid', 'is-valid');

      if (!value) {
        return;
      }

      if (name === 'mail_port') {
        const port = parseInt(value, 10);
        if (isNaN(port) || port < 1 || port > 65535) {
          isValid = false;
          message = __('config_js_valid_port_range');
        }
      }

      if (name === 'mail_host') {
        const domainRegex = /^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
        const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
        if (!domainRegex.test(value) && !ipRegex.test(value)) {
          isValid = false;
          message = __('config_js_valid_host_format');
        }
      }

      if (name === 'mail_username' || name === 'mail_from_address') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
          isValid = false;
          message = __('config_js_valid_email_format');
        }
      }

      if (isValid) {
        field.classList.add('is-valid');
      } else {
        field.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        field.parentElement.appendChild(feedback);
      }
    }

    function setBadgeState(element, active, activeText, inactiveText, activeClass, inactiveClass) {
      if (!element) {
        return;
      }
      element.className = 'badge bg-' + (active ? activeClass : inactiveClass) + '-subtle text-' + (active ? activeClass : inactiveClass);
      element.textContent = active ? activeText : inactiveText;
    }

    function renderListItems(target, items) {
      if (!target) {
        return;
      }
      target.innerHTML = '';
      (items || []).forEach(function (item) {
        var li = document.createElement('li');
        li.textContent = item;
        target.appendChild(li);
      });
    }

    function initAuthPolicyInteractions() {
      var form = document.getElementById('form-auth-aktif');
      if (!form) {
        return;
      }

      if (form.dataset.authPolicyInitialized === '1' && typeof window.__tetapanRefreshAuthPolicySummary === 'function') {
        window.__tetapanRefreshAuthPolicySummary();
        return;
      }

      var maintenanceInput = document.getElementById('auth_maintenance_mode');
      var staffInput = document.getElementById('auth_login_enable_staf');
      var studentInput = document.getElementById('auth_login_enable_pelajar');
      var publicInput = document.getElementById('auth_login_enable_umum');
      var ssoEnabledInput = document.getElementById('auth_sso_enabled');
      var ssoModeInput = document.getElementById('auth_sso_mode');
      var maintenanceBadge = document.getElementById('auth-maintenance-state');
      var staffBadge = document.getElementById('auth-category-state-auth_login_enable_staf');
      var studentBadge = document.getElementById('auth-category-state-auth_login_enable_pelajar');
      var publicBadge = document.getElementById('auth-category-state-auth_login_enable_umum');
        var ssoEnabledBadge = document.getElementById('auth-sso-enabled-state');
        var ssoSiteIdInput = document.getElementById('auth_sso_site_id');
        var ssoIdpDomainInput = document.getElementById('auth_sso_idp_domain');
        var ssoSiteIdRequiredMark = document.getElementById('auth-sso-site-id-required');
        var ssoIdpDomainRequiredMark = document.getElementById('auth-sso-idp-domain-required');
        var summarySiteId = document.getElementById('auth-summary-site-id');
        var summaryIdpDomain = document.getElementById('auth-summary-idp-domain');
        var modeNote = document.getElementById('auth-sso-mode-note');
      var hybridBlock = document.getElementById('auth-hybrid-block');
      var statusBadge = document.getElementById('auth-summary-status-badge');
      var statusText = document.getElementById('auth-summary-status-text');
      var effectiveList = document.getElementById('auth-summary-effective-list');
      var warningBox = document.getElementById('auth-summary-warning-box');
      var warningList = document.getElementById('auth-summary-warning-list');
      var hasServerError = !!form.querySelector('.auth-summary-box-error');

      function refreshAuthPolicySummary() {
        if (!maintenanceInput || !staffInput || !studentInput || !publicInput || !ssoEnabledInput || !ssoModeInput) {
          return;
        }

        var maintenanceOn = !!maintenanceInput.checked;
        var staffEnabled = !!staffInput.checked;
        var studentEnabled = !!studentInput.checked;
        var publicEnabled = !!publicInput.checked;
        var ssoEnabled = !!ssoEnabledInput.checked;
        var ssoMode = String(ssoModeInput.value || 'MANUAL').toUpperCase();
        var warnings = [];
        var effectiveSummary = [];

        setBadgeState(maintenanceBadge, maintenanceOn, __('config_auth_enabled') || 'Enabled', __('config_auth_disabled') || 'Disabled', 'danger', 'secondary');
        setBadgeState(staffBadge, staffEnabled, __('config_auth_allowed') || 'Allowed', __('config_auth_blocked') || 'Blocked', 'success', 'secondary');
        setBadgeState(studentBadge, studentEnabled, __('config_auth_allowed') || 'Allowed', __('config_auth_blocked') || 'Blocked', 'success', 'secondary');
        setBadgeState(publicBadge, publicEnabled, __('config_auth_allowed') || 'Allowed', __('config_auth_blocked') || 'Blocked', 'success', 'secondary');
          setBadgeState(ssoEnabledBadge, ssoEnabled, __('config_auth_enabled') || 'Enabled', __('config_auth_disabled') || 'Disabled', 'success', 'secondary');
          if (ssoSiteIdInput) {
            ssoSiteIdInput.required = ssoEnabled;
            ssoSiteIdInput.setAttribute('aria-required', ssoEnabled ? 'true' : 'false');
          }
          if (ssoIdpDomainInput) {
            ssoIdpDomainInput.required = ssoEnabled;
            ssoIdpDomainInput.setAttribute('aria-required', ssoEnabled ? 'true' : 'false');
          }
          if (ssoSiteIdRequiredMark) {
            ssoSiteIdRequiredMark.classList.toggle('d-none', !ssoEnabled);
          }
          if (ssoIdpDomainRequiredMark) {
            ssoIdpDomainRequiredMark.classList.toggle('d-none', !ssoEnabled);
          }
          if (summarySiteId && ssoSiteIdInput) {
            summarySiteId.textContent = String(ssoSiteIdInput.value || '').trim() || (__('config_auth_summary_not_configured') || 'Not configured');
          }
          if (summaryIdpDomain && ssoIdpDomainInput) {
            summaryIdpDomain.textContent = String(ssoIdpDomainInput.value || '').trim() || (__('config_auth_summary_not_configured') || 'Not configured');
          }

        if (modeNote) {
          if (ssoMode === 'ALL') {
            modeNote.innerHTML = '<i class="ri-information-line me-1"></i>' + ((__('config_auth_sso_mode_all_note')) || 'In ALL mode, Staff and Student users must use SSO. Public users may still log in manually.');
          } else if (ssoMode === 'HYBRID') {
            modeNote.innerHTML = '<i class="ri-information-line me-1"></i>' + ((__('config_auth_sso_mode_hybrid_note')) || 'In HYBRID mode, each category follows its own configured login method.');
          } else {
            modeNote.innerHTML = '<i class="ri-information-line me-1"></i>' + ((__('config_auth_sso_mode_manual_note')) || 'In MANUAL mode, all allowed categories use manual login.');
          }
        }

        if (hybridBlock) {
          hybridBlock.classList.toggle('auth-hybrid-block-muted', ssoMode !== 'HYBRID');
        }

        effectiveSummary.push(maintenanceOn
          ? (__('config_auth_summary_maintenance_on') || 'Maintenance mode is enabled. Only Super Admin can log in.')
          : (__('config_auth_summary_maintenance_off') || 'Maintenance mode is disabled. Normal policy evaluation applies.'));
        effectiveSummary.push(staffEnabled
          ? (__('config_auth_summary_staff_enabled') || 'Staff login is enabled.')
          : (__('config_auth_summary_staff_disabled') || 'Staff login is disabled.'));
        effectiveSummary.push(studentEnabled
          ? (__('config_auth_summary_student_enabled') || 'Student login is enabled.')
          : (__('config_auth_summary_student_disabled') || 'Student login is disabled.'));
        effectiveSummary.push(publicEnabled
          ? (__('config_auth_summary_public_enabled') || 'Public login is enabled.')
          : (__('config_auth_summary_public_disabled') || 'Public login is disabled.'));
        effectiveSummary.push(ssoEnabled
          ? ((__('config_auth_summary_sso_enabled') || 'SSO is enabled in %s mode.').replace('%s', ssoMode))
          : (__('config_auth_summary_sso_disabled') || 'SSO is disabled. All allowed categories use manual login.'));

        if (!ssoEnabled && ssoMode !== 'MANUAL') {
          warnings.push((__('config_auth_warning_sso_disabled_mode')) || 'SSO mode is configured but SSO is currently disabled.');
        }
        if (!staffEnabled && !studentEnabled && !publicEnabled) {
          warnings.push((__('config_auth_warning_all_categories_blocked')) || 'All login categories are blocked. Only Super Admin will remain able to log in.');
        }

        renderListItems(effectiveList, effectiveSummary);
        renderListItems(warningList, warnings);

        if (warningBox) {
          warningBox.classList.toggle('d-none', warnings.length === 0);
        }

        if (!hasServerError) {
          var hasWarnings = warnings.length > 0;
          if (statusBadge) {
            statusBadge.className = 'badge bg-' + (hasWarnings ? 'warning' : 'success') + '-subtle text-' + (hasWarnings ? 'warning' : 'success') + ' px-3 py-2';
            statusBadge.textContent = hasWarnings
              ? (__('config_auth_status_warning') || 'Valid with Warning')
              : (__('config_auth_status_valid') || 'Valid');
          }
          if (statusText) {
            statusText.className = (hasWarnings ? 'text-warning' : 'text-success') + ' small fw-semibold';
            statusText.textContent = hasWarnings
              ? ((__('config_auth_summary_warnings')) || 'Warnings') + ': ' + warnings[0]
              : (__('config_auth_summary_status_ok') || 'Policy snapshot is ready for runtime use.');
          }
        }
      }

      window.__tetapanRefreshAuthPolicySummary = refreshAuthPolicySummary;

      function handleAuthPolicyFieldEvent(event) {
        var field = event && event.target ? event.target : null;
        if (!field || !field.name) {
          return;
        }

        if (
          field.name === 'auth_maintenance_mode' ||
          field.name === 'auth_login_enable_staf' ||
          field.name === 'auth_login_enable_pelajar' ||
          field.name === 'auth_login_enable_umum' ||
            field.name === 'auth_sso_enabled' ||
            field.name === 'auth_sso_site_id' ||
            field.name === 'auth_sso_idp_domain' ||
            field.name === 'auth_sso_mode' ||
          field.name === 'auth_sso_hybrid_staf' ||
          field.name === 'auth_sso_hybrid_pelajar' ||
          field.name === 'auth_sso_hybrid_umum'
        ) {
          refreshAuthPolicySummary();
        }
      }

      form.addEventListener('change', handleAuthPolicyFieldEvent);
      form.addEventListener('input', handleAuthPolicyFieldEvent);
      form.dataset.authPolicyInitialized = '1';

      refreshAuthPolicySummary();
    }

    document.querySelectorAll('input[name="mail_host"], input[name="mail_port"], input[name="mail_username"], input[name="mail_from_address"]').forEach(function (input) {
      input.addEventListener('blur', function () {
        validateField(this);
      });
      input.addEventListener('input', function () {
        this.classList.remove('is-invalid', 'is-valid');
        clearFieldValidationState(this);
      });
    });

    document.querySelectorAll('#form-general-aktif input, #form-general-aktif textarea, #form-general-aktif select, #form-auth-aktif input, #form-auth-aktif textarea, #form-auth-aktif select, #form-emel-aktif input, #form-emel-aktif textarea, #form-emel-aktif select, #form-db-aktif input, #form-db-aktif textarea, #form-db-aktif select, #form-tema-aktif input, #form-tema-aktif textarea, #form-tema-aktif select, #form-bahasa input, #form-bahasa textarea, #form-bahasa select').forEach(function (field) {
      field.addEventListener('input', function () {
        clearFieldValidationState(field);
        const form = field.form;
        if (form) {
          clearSubtabErrorMarkers(form);
        }
      });
      field.addEventListener('change', function () {
        clearFieldValidationState(field);
        const form = field.form;
        if (form) {
          clearSubtabErrorMarkers(form);
        }
      });
    });

    const formGeneral = document.getElementById('form-general-aktif');
    const btnGeneral = document.getElementById('btn-simpan-general');
    if (formGeneral && btnGeneral) {
      captureFormSnapshot(formGeneral);
      refreshDirtyIndicator(formGeneral, btnGeneral);
    }

    const formAuth = document.getElementById('form-auth-aktif');
    const btnAuth = document.getElementById('btn-simpan-auth');
    if (formAuth && btnAuth) {
      captureFormSnapshot(formAuth);
      refreshDirtyIndicator(formAuth, btnAuth);
    }

    initAuthPolicyInteractions();

    const formEmel = document.getElementById('form-emel-aktif');
    const btnEmel = document.getElementById('btn-simpan-emel');
    if (formEmel && btnEmel) {
      captureFormSnapshot(formEmel);
      refreshDirtyIndicator(formEmel, btnEmel);
    }

    window.__tetapanHandleEmailTestImpl = function () {
      const btnUji = document.getElementById('btn-uji-emel');
      if (!btnUji) {
        return;
      }
      const form = document.getElementById('form-emel-aktif');
      const mailFrom = form && form.querySelector('input[name="mail_from_address"]')
        ? form.querySelector('input[name="mail_from_address"]').value
        : '';
      const mailUsername = form && form.querySelector('input[name="mail_username"]')
        ? form.querySelector('input[name="mail_username"]').value
        : '';
      const defaultEmail = mailFrom || mailUsername || '';

      Swal.fire({
        title: __('config_js_input_uji_emel'),
        input: 'email',
        inputLabel: __('config_js_label_uji_emel'),
        inputValue: defaultEmail,
        inputPlaceholder: __('config_js_placeholder_uji_emel'),
        showCancelButton: true,
        confirmButtonText: __('config_js_uji_emel_btn'),
        cancelButtonText: __('config_alert_no'),
        preConfirm: function (email) {
          if (!email) {
            Swal.showValidationMessage(__('config_js_valid_emel_kosong'));
            return false;
          }
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRegex.test(email)) {
            Swal.showValidationMessage(__('config_js_valid_email_full'));
            return false;
          }
          return email;
        }
      }).then(function (result) {
        if (!result.isConfirmed) {
          return;
        }

        const formData = new FormData(form);
        formData.append('uji_email', result.value);
        btnUji.disabled = true;
        btnUji.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + __('config_js_uji_emel_btn_loading');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')
          ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          : '';
        formData.append('csrf_token', csrfToken);

        fetch(baseUrl + 'ajax/uji-emel.php', {
          method: 'POST',
          body: formData,
          noLoader: true,
          headers: Object.assign({
            'X-No-Loader': '1'
          }, csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data.success) {
              const title = __('config_js_berjaya');
              const finalTitle = (title && title !== 'config_js_berjaya') ? title : 'Berjaya';
              Swal.fire({
                icon: 'success',
                title: finalTitle,
                html: data.message || __('config_js_emel_berjaya') || 'Emel berjaya dihantar.'
              });
              return;
            }

            const errorTitle = __('config_js_ralat');
            const finalErrorTitle = (errorTitle && errorTitle !== 'config_js_ralat') ? errorTitle : 'Ralat';
            Swal.fire({
              icon: 'error',
              title: finalErrorTitle,
              text: data.message || __('config_js_emel_gagal') || 'Gagal hantar emel.'
            });
          })
          .catch(function () {
            Swal.fire({ icon: 'error', title: __('config_js_ralat'), text: __('config_js_ralat_sistem') });
          })
          .finally(function () {
            btnUji.disabled = false;
            btnUji.innerHTML = '<i class="ri-mail-send-line me-1"></i> ' + __('config_js_uji_emel_btn_default');
          });
      });
    };

    try {
      cleanupOrphanedBackdrops();
      document.addEventListener('hidden.bs.modal', cleanupOrphanedBackdrops);
      document.addEventListener('hidden.bs.offcanvas', cleanupOrphanedBackdrops);
    } catch (error) {
      // optional cleanup should not block page actions
    }

    try {
      bindBootstrapTabs();
    } catch (error) {
      // tab enhancement is optional for save flow
    }

    [
      'form-general-aktif',
      'form-auth-aktif',
      'form-emel-aktif',
      'form-db-aktif',
      'form-tema-aktif',
      'form-bahasa'
    ].forEach(function (formId) {
      const form = document.getElementById(formId);
      if (form) {
        form.noValidate = true;
      }
    });

    try {
      if (typeof pageUiHelper.persistBootstrapTabs === 'function') {
        pageUiHelper.persistBootstrapTabs({
          storageKey: 'lastActiveTab',
          defaultTab: '#general-tab',
          tabSelector: 'a[data-bs-toggle="tab"]'
        });
      } else if (window.bootstrap && window.bootstrap.Tab) {
        (function () {
          let storedTab = null;
          try {
            storedTab = window.localStorage.getItem('lastActiveTab');
          } catch (storageError) {
            storedTab = null;
          }

          const urlTab = new URLSearchParams(location.search).get('tab');
          const wanted = urlTab
            ? ('#' + urlTab + '-tab')
            : (window.location.hash || storedTab || '#general-tab');
          const el = document.querySelector('a[href="' + wanted + '"]');
          if (el) {
            window.bootstrap.Tab.getOrCreateInstance(el).show();
          }

          document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function (e) {
              try {
                window.localStorage.setItem('lastActiveTab', e.target.getAttribute('href'));
              } catch (storageError) {
                // ignore storage errors
              }
            });
          });
        })();
      }
    } catch (error) {
      // tab persistence is optional for save flow
    }

    const btnUji = document.getElementById('btn-uji-emel');
    if (btnUji) {
      btnUji.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        window.__tetapanHandleEmailTest();
      });
    }

    const formDB = document.getElementById('form-db-aktif');
    const btnDB = document.getElementById('btn-simpan-db');
    if (formDB && btnDB) {
      const dbOptionRows = Array.from(formDB.querySelectorAll('.db-option-row[data-db-radio]'));

      const syncDbOptionRows = function () {
        const groups = {};
        dbOptionRows.forEach(function (row) {
          const selector = row.getAttribute('data-db-radio');
          const input = selector ? formDB.querySelector(selector) : null;
          if (!input) {
            return;
          }
          const name = input.getAttribute('name') || '';
          if (!groups[name]) {
            groups[name] = [];
          }
          groups[name].push({ row: row, input: input });
        });

        Object.values(groups).forEach(function (items) {
          items.forEach(function (item) {
            item.row.classList.toggle('is-selected', !!item.input.checked);
            item.row.classList.toggle('table-primary', !!item.input.checked);
          });
        });
      };

      Object.entries(initialDbSelection).forEach(function (entry) {
        const name = entry[0];
        const value = entry[1];
        const radios = Array.from(formDB.querySelectorAll('input[name="' + name + '"]'));
        radios.forEach(function (radio) {
          radio.checked = radio.value === value;
        });
      });

      dbOptionRows.forEach(function (row) {
        row.addEventListener('click', function () {
          const selector = row.getAttribute('data-db-radio');
          const input = selector ? formDB.querySelector(selector) : null;
          if (!input) {
            return;
          }
          if (!input.checked) {
            input.checked = true;
            input.dispatchEvent(new Event('change', { bubbles: true }));
          } else {
            syncDbOptionRows();
          }
        });
      });

      formDB.querySelectorAll('input[name="main_db_environment"], input[name="sybase_environment"], input[name="sybase_operational_mode"]').forEach(function (input) {
        input.addEventListener('change', syncDbOptionRows);
      });

      syncDbOptionRows();
      captureFormSnapshot(formDB);
      refreshDirtyIndicator(formDB, btnDB);
    }

    if (dbAdditionalEnvAddButton) {
      dbAdditionalEnvAddButton.addEventListener('click', function () {
        appendEnvRow({ f_environment: 'production', f_os_family: 'any', f_driver: 'mysql', f_charset: 'utf8mb4', f_is_active: true });
      });
    }

    if (dbAdditionalEnvRows) {
      dbAdditionalEnvRows.addEventListener('click', function (event) {
        var removeButton = event.target.closest('[data-env-row-remove]');
        if (!removeButton) {
          return;
        }
        event.preventDefault();
        var row = removeButton.closest('[data-env-row]');
        if (!row) {
          return;
        }
        row.remove();
        if (!dbAdditionalEnvRows.querySelector('[data-env-row]')) {
          appendEnvRow({ f_environment: 'production', f_os_family: 'any', f_driver: 'mysql', f_charset: 'utf8mb4', f_is_active: true });
        } else {
          reindexEnvRows();
        }
      });
    }

    if (dbAdditionalSearch) {
      dbAdditionalSearch.addEventListener('input', renderAdditionalConnectionsTable);
    }
    if (dbAdditionalFamilyFilter) {
      dbAdditionalFamilyFilter.addEventListener('change', renderAdditionalConnectionsTable);
    }
    if (dbAdditionalStatusFilter) {
      dbAdditionalStatusFilter.addEventListener('change', renderAdditionalConnectionsTable);
    }

    if (dbAdditionalTableBody) {
      dbAdditionalTableBody.addEventListener('click', function (event) {
        var actionButton = event.target.closest('[data-db-additional-action]');
        if (!actionButton) {
          return;
        }

        var action = actionButton.getAttribute('data-db-additional-action') || '';
        var code = actionButton.getAttribute('data-code') || '';
        var connection = additionalConnections.find(function (item) {
          return String(item.f_code || '') === code;
        }) || null;

        if (action === 'edit') {
          openAdditionalConnectionModal(connection);
          return;
        }

        if (action === 'inspect') {
          var inspectEnv = connection && Array.isArray(connection.env_rows) && connection.env_rows.length
            ? (connection.env_rows.find(function (row) { return !!Number(row.f_is_active || 0); }) || connection.env_rows[0])
            : null;
          postAdditionalConnectionAction('db_additional_inspect', {
            connection_code: code,
            environment: inspectEnv ? String(inspectEnv.f_environment || 'production') : 'production',
            os_family: inspectEnv ? String(inspectEnv.f_os_family || 'any') : 'any',
            driver: inspectEnv ? String(inspectEnv.f_driver || '') : ''
          }, actionButton)
            .then(function (payload) {
              if (!payload || payload.success !== true || !payload.data || !payload.data.probe) {
                throw new Error((payload && payload.message) || 'Gagal memuatkan butiran sambungan tambahan.');
              }
              showAdditionalConnectionProbe(payload.data.probe);
            })
            .catch(function (error) {
              showTetapanSystemError(error && error.message ? error.message : 'Gagal memuatkan butiran sambungan tambahan.');
            });
          return;
        }

        if (action === 'schema') {
          var schemaEnv = connection && Array.isArray(connection.env_rows) && connection.env_rows.length
            ? (connection.env_rows.find(function (row) { return !!Number(row.f_is_active || 0); }) || connection.env_rows[0])
            : null;
          postAdditionalConnectionAction('db_additional_schema_preview', {
            connection_code: code,
            environment: schemaEnv ? String(schemaEnv.f_environment || 'production') : 'production',
            os_family: schemaEnv ? String(schemaEnv.f_os_family || 'any') : 'any',
            driver: schemaEnv ? String(schemaEnv.f_driver || '') : ''
          }, actionButton)
            .then(function (payload) {
              if (!payload || payload.success !== true || !payload.data || !payload.data.schemaPreview) {
                throw new Error((payload && payload.message) || 'Gagal memuatkan schema preview sambungan tambahan.');
              }
              showAdditionalConnectionSchemaPreview(payload.data.schemaPreview);
            })
            .catch(function (error) {
              showTetapanSystemError(error && error.message ? error.message : 'Gagal memuatkan schema preview sambungan tambahan.');
            });
          return;
        }

        if (action === 'toggle') {
          var nextEnabled = actionButton.getAttribute('data-enabled') !== '1';
          postAdditionalConnectionAction('db_additional_toggle', {
            connection_code: code,
            enabled: nextEnabled ? '1' : '0'
          }, actionButton)
            .then(function (payload) {
              if (!payload || payload.success !== true) {
                throw new Error((payload && payload.message) || 'Gagal mengemas kini status sambungan tambahan.');
              }
              applyPayloadUiSync(payload, formDB);
            })
            .catch(function (error) {
              showTetapanSystemError(error && error.message ? error.message : 'Gagal mengemas kini status sambungan tambahan.');
            });
          return;
        }

        if (action === 'test') {
          var firstEnv = connection && Array.isArray(connection.env_rows) && connection.env_rows.length
            ? (connection.env_rows.find(function (row) { return !!Number(row.f_is_active || 0); }) || connection.env_rows[0])
            : null;
          postAdditionalConnectionAction('db_additional_test', {
            connection_code: code,
            environment: firstEnv ? String(firstEnv.f_environment || 'production') : 'production',
            os_family: firstEnv ? String(firstEnv.f_os_family || 'any') : 'any',
            driver: firstEnv ? String(firstEnv.f_driver || '') : ''
          }, actionButton)
            .then(function (payload) {
              if (!payload || payload.success !== true) {
                throw new Error((payload && payload.message) || 'Ujian sambungan tambahan gagal.');
              }
              if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                  icon: 'success',
                  title: payload.title || 'Berjaya',
                  text: payload.message || 'Ujian sambungan tambahan berjaya.',
                  confirmButtonText: __('config_js_btn_ok') || 'OK'
                });
              }
              return postAdditionalConnectionAction('db_additional_list', {}, dbAdditionalRefreshButton || actionButton);
            })
            .then(function (listPayload) {
              if (listPayload && listPayload.success && listPayload.data && Array.isArray(listPayload.data.additionalConnections)) {
                additionalConnections = listPayload.data.additionalConnections.slice();
                renderAdditionalConnectionsTable();
              }
            })
            .catch(function (error) {
              showTetapanSystemError(error && error.message ? error.message : 'Ujian sambungan tambahan gagal.');
            });
        }
      });
    }

    renderAdditionalConnectionsTable();

    const formBahasa = document.getElementById('form-bahasa');
    const btnBahasa = document.getElementById('btn-simpan-bahasa');
    if (formBahasa && btnBahasa) {
      syncLanguageSelectionUi(formBahasa);
      captureFormSnapshot(formBahasa);
      refreshDirtyIndicator(formBahasa, btnBahasa);
    }

    const formTema = document.getElementById('form-tema-aktif');
    const btnTema = document.getElementById('btn-simpan-tema');
    if (formTema && btnTema) {
      initThemeSectionInteractions(formTema);
      captureFormSnapshot(formTema);
      refreshDirtyIndicator(formTema, btnTema);
    }

    [
      [formGeneral, btnGeneral],
      [formAuth, btnAuth],
      [formEmel, btnEmel],
      [formDB, btnDB],
      [formBahasa, btnBahasa],
      [formTema, btnTema]
    ].forEach(function (entry) {
      var form = entry[0];
      var button = entry[1];
      if (!form || !button) {
        return;
      }

      form.addEventListener('input', function () {
        refreshDirtyIndicator(form, button);
      });
      form.addEventListener('change', function () {
        if (form === formBahasa) {
          syncLanguageSelectionUi(formBahasa);
        }
        refreshDirtyIndicator(form, button);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTetapanSistemPage);
  } else {
    initTetapanSistemPage();
  }
})();
