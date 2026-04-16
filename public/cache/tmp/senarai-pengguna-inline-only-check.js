
// Load Select2 synchronously to ensure it's available
(function() {
  var script = document.createElement('script');
  script.src = '0?v=0';
  script.onload = function() {
    window.__select2ScriptLoaded = true;
  };
  document.head.appendChild(script);
})();



(function(){
  if (!window.bootstrap || !bootstrap.Modal) {
    return;
  }
  const hasDT = () => !!(window.jQuery && jQuery.fn && jQuery.fn.DataTable);
  const CSRF  = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const table = document;

  // ==================== CONFIGURATION CONSTANTS ====================
  const CONFIG = {
    HIGHLIGHT_DURATION: 20000,        // 20 seconds
    ANIMATION_DELAY: 300,             // 300ms
    SELECT2_RETRY_DELAY: 50,         // 50ms
    SELECT2_MAX_RETRIES: 100,        // Max 5 seconds
    RATE_LIMIT_DELAY: 1000,          // 1 second between requests
    RETRY_MAX_ATTEMPTS: 3,            // Max retry attempts
    RETRY_BASE_DELAY: 1000,           // Base delay for exponential backoff
    DEBUG: false,                     // Debug mode
    STUDENT_MODE_ENABLED: 0,
    GROUP_UI_BY_ID: 0,
    GROUP_UI_BY_CODE: 0,
    COLORS: {
      GROUP_ADM_SA: '#ffe8e8',
      GROUP_ADM_HR: '#fffef0',
      HIGHLIGHT_SUCCESS: '#d4edda'
    }
  };

  // Global variable untuk DataTable instance
  let dtInstance = null;
  
  // Request cancellation controller
  let currentRequestController = null;
  
  // Rate limiting tracker
  const rateLimitTracker = new Map();

  // Permission check
  const currentUserGroup = '0';
  const currentUserIdentity = {
    userID: '0',
    stafID: '0',
    nopekerja: '0'
  };
  const isSuperAdmin = 0;
  const protectedStaffIds = 0;

  // ==================== HELPER FUNCTIONS ====================
  
  /**
   * Sanitize error messages untuk prevent exposing system details
   */
  function sanitizeError(error) {
    if (!error) return '0';
    const msg = error.message || error.toString() || '0';
    // Remove technical details
    return msg
      .replace(/in \/.*?\.php:\d+/g, '')
      .replace(/SQLSTATE\[.*?\]/g, '')
      .replace(/PDOException:/g, '')
      .replace(/Exception:/g, '')
      .substring(0, 200); // Limit length
  }

  /**
   * Check permission dengan user-friendly error
   */
  /**
   * Rate limiting untuk prevent spam clicks
   */
  function checkRateLimit(key, delay = CONFIG.RATE_LIMIT_DELAY) {
    const now = Date.now();
    const lastCall = rateLimitTracker.get(key) || 0;
    
    if (now - lastCall < delay) {
      return false;
    }
    
    rateLimitTracker.set(key, now);
    return true;
  }

  /**
   * Create rate-limited handler
   */
  function createRateLimitedHandler(handler, delay = CONFIG.RATE_LIMIT_DELAY) {
    return async function(...args) {
      const handlerKey = handler.name || 'anonymous';
      if (!checkRateLimit(handlerKey, delay)) {
        await Swal.fire({
          icon: 'warning',
          title: '0',
          text: '0',
          timer: 2000,
          timerProgressBar: true,
          confirmButtonText: '0'
        });
        return;
      }
      return handler.apply(this, args);
    };
  }

  /**
   * Input validation functions
   */
  function validateStafID(stafID) {
    if (!stafID || stafID.trim() === '') return false;
    // Format: XXXX-XX atau 6 digits
    const normalized = stafID.replace(/-/g, '');
    return /^\d{6}$/.test(normalized);
  }

    function validateGroupId(groupId) {
      if (groupId === null || groupId === undefined) return false;
      const n = parseInt(String(groupId), 10);
      return Number.isFinite(n) && n > 0;
    }

    function validateEmailAddress(email) {
      const normalized = String(email || '').trim();
      if (normalized === '') return false;
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized);
    }

  function isCurrentLoggedInUserTarget(userID, stafID, nopekerja = '') {
    const normalize = (value) => String(value || '').replace(/-/g, '').trim();
    const targetUserID = String(userID || '').trim();
    if (currentUserIdentity.userID && targetUserID && currentUserIdentity.userID === targetUserID) return true;
    if (currentUserIdentity.stafID && normalize(stafID) && currentUserIdentity.stafID === normalize(stafID)) return true;
    if (currentUserIdentity.nopekerja && normalize(nopekerja) && currentUserIdentity.nopekerja === normalize(nopekerja)) return true;
    return false;
  }

  function isProtectedStaffAccountClient(stafID) {
    const normalize = (value) => String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').trim();
    const target = normalize(stafID);
    if (!target) return false;
    return protectedStaffIds.some((candidate) => normalize(candidate) === target);
  }

  /**
   * Fetch with retry mechanism (exponential backoff)
   */
  async function fetchWithRetry(url, options = {}, maxRetries = CONFIG.RETRY_MAX_ATTEMPTS) {
    for (let i = 0; i < maxRetries; i++) {
      try {
        const response = await fetch(url, options);
        if (response.ok) return response;
        
        // Retry on 5xx errors only
        if (i < maxRetries - 1 && response.status >= 500) {
          const delay = Math.pow(2, i) * CONFIG.RETRY_BASE_DELAY; // 1s, 2s, 4s
          await new Promise(resolve => setTimeout(resolve, delay));
          continue;
        }
        
        throw new Error(`0 ${response.status}`);
      } catch (e) {
        if (i === maxRetries - 1) throw e;
        // Network errors - retry with backoff
        if (e.name !== 'AbortError') {
          const delay = Math.pow(2, i) * CONFIG.RETRY_BASE_DELAY;
          await new Promise(resolve => setTimeout(resolve, delay));
        } else {
          throw e; // Don't retry aborted requests
        }
      }
    }
  }

  /**
   * Loading overlay management
   */
  function showLoading(message = '0') {
    hideLoading(); // Remove existing if any
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.className = 'loading-overlay';
    overlay.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    `;
    overlay.innerHTML = `
      <div class="loading-spinner text-center" style="background: white; padding: 2rem; border-radius: 8px;">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">0</span>
        </div>
        <p class="mt-3 mb-0">${message}</p>
      </div>
    `;
    document.body.appendChild(overlay);
  }

  function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.remove();
  }

  // Select2 loading is handled inline where needed; remove unused helper to keep bundle small.

  /**
   * Get badge class berdasarkan group ID
   */
  function normalizeGroupCode(code) {
    return String(code || '').toUpperCase().replace(/[^A-Z0-9]+/g, '');
  }

  function getGroupStyle(groupId, groupKod = '') {
    const idKey = String(parseInt(groupId || 0, 10) || 0);
    const codeKey = normalizeGroupCode(groupKod);
    const style = CONFIG.GROUP_UI_BY_ID[idKey] || (codeKey !== '' ? CONFIG.GROUP_UI_BY_CODE[codeKey] : null) || {};
    return {
      badgeClass: String(style.badgeClass || 'bg-secondary').trim() || 'bg-secondary',
      rowClass: String(style.rowClass || '').trim(),
      rowColor: String(style.rowColor || '').trim()
    };
  }

  function getBadgeClass(groupId, groupKod = '') {
    return getGroupStyle(groupId, groupKod).badgeClass;
  }

  function getBadgeInlineStyle(groupId, groupKod = '') {
    const style = getGroupStyle(groupId, groupKod);
    if (!style.rowColor) return '';
    return `background-color:${style.rowColor};color:${getReadableTextColor(style.rowColor)};`;
  }

  function getReadableTextColor(color) {
    const value = String(color || '').trim();
    const match = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
    if (!match) return '#ffffff';
    let hex = match[1];
    if (hex.length === 3) {
      hex = hex.split('').map((ch) => ch + ch).join('');
    }
    const r = parseInt(hex.slice(0, 2), 16);
    const g = parseInt(hex.slice(2, 4), 16);
    const b = parseInt(hex.slice(4, 6), 16);
    const luminance = ((r * 299) + (g * 587) + (b * 114)) / 1000;
    return luminance >= 160 ? '#1e293b' : '#ffffff';
  }

  /**
   * Get row class berdasarkan group ID
   */
  function getRowClass(groupId, groupKod = '') {
    return getGroupStyle(groupId, groupKod).rowClass;
  }

  function isValidCssColor(value) {
    const v = String(value || '').trim();
    if (!v) return false;
    return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v) || /^[a-zA-Z]+$/.test(v);
  }

  function toSoftRowBg(color) {
    const v = String(color || '').trim();
    const m3 = /^#([0-9a-f]{3})$/i.exec(v);
    if (m3) {
      const h = m3[1];
      const r = parseInt(h[0] + h[0], 16);
      const g = parseInt(h[1] + h[1], 16);
      const b = parseInt(h[2] + h[2], 16);
      return `rgba(${r}, ${g}, ${b}, 0.18)`;
    }
    const m6 = /^#([0-9a-f]{6})$/i.exec(v);
    if (m6) {
      const h = m6[1];
      const r = parseInt(h.slice(0, 2), 16);
      const g = parseInt(h.slice(2, 4), 16);
      const b = parseInt(h.slice(4, 6), 16);
      return `rgba(${r}, ${g}, ${b}, 0.18)`;
    }
    return v;
  }

  function applyRowClass($row) {
    const groupId = parseInt($row.attr('data-group-id') || '0', 10);
    const groupKod = String($row.attr('data-group-kod') || '');
    const mapStyle = getGroupStyle(groupId, groupKod);
    const nextClass = String(mapStyle.rowClass || '').trim();
    const oldClass = String($row.attr('data-row-class') || '').trim();
    if (oldClass) {
      $row.removeClass(oldClass);
    }
    const finalClass = nextClass || oldClass;
    if (finalClass) $row.addClass(finalClass);
    const trEl = $row.get(0);
    if (trEl && trEl.style) {
      trEl.style.removeProperty('background-color');
    }
    $row.find('td').each(function() {
      if (this && this.style) {
        this.style.removeProperty('background-color');
        this.style.removeProperty('background-image');
      }
    });
    $row.attr('data-row-class', finalClass);
  }

  /**
   * Render extra roles tooltip on info icon
   */
  function renderExtraRolesInfo(iconEl, roles) {
    if (!iconEl) return;
    const list = Array.isArray(roles) ? roles : [];
      const title = list.length ? list.join(', ') : '0';
    iconEl.setAttribute('data-bs-toggle', 'tooltip');
    iconEl.setAttribute('data-bs-placement', 'top');
    iconEl.setAttribute('title', title);
  }

  /**
   * Init tooltips safely
   */
  function initTooltips(root = document) {
    if (!window.bootstrap || !bootstrap.Tooltip) return;
    root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      try { bootstrap.Tooltip.getInstance(el)?.dispose(); } catch(e) {}
      new bootstrap.Tooltip(el, {
        customClass: 'userlist-cell-tooltip'
      });
    });
  }

  /**
   * Track event untuk analytics/debugging
   */
  function trackEvent(eventName, data = {}) {
    if (CONFIG.DEBUG) {
      console.log('[Event]', eventName, data);
    }
    // Send to server for audit (optional, non-blocking)
    try {
      fetch('0', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          event: eventName, 
          data, 
          timestamp: Date.now(),
          userGroup: currentUserGroup
        })
      }).catch(() => {}); // Ignore errors
    } catch (e) {
      // Ignore tracking errors
    }
  }

  /**
   * Update user row in-place (optimized)
   */
  function updateUserRow(userID, newData) {
    const $row = $(`.user-access-table tbody tr[data-user-id="${userID}"]`).first();
    if ($row.length === 0) {
      // Row not visible, trigger full reload
      return reloadUserTable(userID);
    }
    
    // Update row attributes
    if (newData.groupID) {
      $row.attr('data-group-id', newData.groupID);
      const rowGroupKod = (newData.groupKod !== undefined) ? newData.groupKod : $row.attr('data-group-kod');
      $row.attr('data-group-kod', rowGroupKod || '');
      applyRowClass($row);
    }
    if (newData.groupKod) {
      $row.attr('data-group-kod', newData.groupKod);
      applyRowClass($row);
    }
    if (Array.isArray(newData.extraRoles)) {
      const listText = newData.extraRoles.join(', ');
      $row.attr('data-extra-roles', listText);
      $row.attr('data-extra-count', String(newData.extraRoles.length));
      const $info = $row.find('.extra-roles-info');
      $info.attr('data-has-extra', newData.extraRoles.length > 0 ? '1' : '0');
      renderExtraRolesInfo($info[0], newData.extraRoles);
      initTooltips($info[0] || document);
    }
    if (newData.flag !== undefined) {
      $row.attr('data-flag', newData.flag);
    }
    
    // Update group badge
    if (newData.groupID && newData.groupName) {
      const $badge = $row.find('.col-group .group-chip');
      const groupKod = newData.groupKod || $row.attr('data-group-kod') || '';
      const badgeInlineStyle = getBadgeInlineStyle(newData.groupID, groupKod);
      $badge.text(newData.groupName);
      if (badgeInlineStyle) {
        $badge.attr('style', badgeInlineStyle);
      } else {
        $badge.removeAttr('style');
      }
    }
    
    // Update access badge
    if (newData.flag !== undefined) {
      const $accessBadge = $row.find('.col-akses .access-chip');
      if (newData.flag == 1) {
        $accessBadge
          .removeClass('is-blocked')
          .addClass('is-allowed')
          .text('0');
      } else {
        $accessBadge
          .removeClass('is-allowed')
          .addClass('is-blocked')
          .text('0');
      }
    }
    
    // Update button data attributes if needed
    if (isSuperAdmin) {
      if (newData.name !== undefined || newData.loginID !== undefined || newData.email !== undefined) {
        const currentName = String(newData.name !== undefined ? newData.name : ($row.find('.btn-edit-group').attr('data-nama') || ''));
        const currentLoginID = String(newData.loginID !== undefined ? newData.loginID : ($row.find('.btn-edit-group').attr('data-loginid') || ''));
        const currentStafID = String($row.find('.btn-edit-group').attr('data-stafid') || '');
        const scope = String($row.find('.btn-edit-group').attr('data-scope') || '').toLowerCase();
        const currentDisplayId = scope === 'public' ? (currentLoginID || currentStafID) : currentStafID;
        const nameText = currentName + (currentDisplayId ? (' (' + currentDisplayId + ')') : '');
        const $nameSpan = $row.find('.col-nama .cell-tooltip-text');
        $nameSpan.text(nameText).attr('title', nameText);
        $row.find('.btn-edit-group')
          .attr('data-nama', currentName)
          .attr('data-loginid', currentLoginID)
          .attr('data-displayid', currentDisplayId);
        $row.find('.btn-delete-user').attr('data-displayid', currentDisplayId);
      }
      if (newData.nickname !== undefined) {
        $row.find('.btn-edit-group').attr('data-nickname', newData.nickname);
      }
      if (newData.email !== undefined) {
        $row.find('.btn-edit-group').attr('data-email', newData.email);
      }
      if (newData.phone !== undefined) {
        $row.find('.btn-edit-group').attr('data-phone', newData.phone);
      }
      if (newData.university !== undefined) {
        $row.find('.btn-edit-group').attr('data-university', newData.university);
      }
      if (newData.nokp !== undefined) {
        $row.find('.btn-edit-group').attr('data-nokp', newData.nokp);
      }
      if (newData.jabatan !== undefined) {
        const jab = String(newData.jabatan || '');
        const $jabatanSpan = $row.find('.col-jabatan .cell-tooltip-text');
        $jabatanSpan.text(jab).attr('title', jab);
        $row.find('.btn-edit-group').attr('data-jabatan', jab);
      }
      if (newData.groupID) {
        $row.find('.btn-edit-group').attr('data-group-id', newData.groupID);
      }
      if (newData.groupKod) {
        $row.find('.btn-edit-group').attr('data-group-kod', newData.groupKod);
        if (newData.groupName) {
          $row.find('.btn-edit-group').attr('data-group-name', newData.groupName);
        }
      }
    }
    
    // Highlight row
    $row.addClass('row-updated-highlight');
    setTimeout(() => {
      $row.removeClass('row-updated-highlight');
    }, CONFIG.HIGHLIGHT_DURATION);
    
    // Scroll to row if not visible
    const rowOffset = $row.offset();
    if (rowOffset) {
      const windowTop = $(window).scrollTop();
      const windowBottom = windowTop + $(window).height();
      const rowTop = rowOffset.top;
      const rowBottom = rowTop + $row.outerHeight();
      
      if (rowTop < windowTop || rowBottom > windowBottom) {
        $('html, body').animate({
          scrollTop: rowTop - 100
        }, 500);
      }
    }
  }

  /**
   * Build a <tr> DOM node from a structured row object returned by server.
   * This avoids injecting raw HTML from server and improves XSS safety.
   */
  function buildRowFromData(r) {
    // Normalise possible server keys
    const userID = String(r.f_userID || r.userID || r.id || '');
    const nama = String(r.f_nama || r.nama || r.name || '');
    const loginID = String(r.f_loginID || r.loginID || r.login_id || '');
    const stafID = String(r.f_stafID || r.stafID || r.staf_id || '');
    const nickname = String(r.f_nickname || r.nickname || '');
    const email = String(r.f_email || r.email || '');
    const phone = String(r.f_handphone || r.phone || '');
    const university = String(r.f_namajabatan || r.university || r.jabatan || r.department || '');
    const nokp = String(r.f_nokp || r.nokp || '');
    const categoryUser = String(r.f_categoryUser || r.categoryUser || r.user_category || '').trim().toUpperCase();
    const jabatan = university;
    const jawatan = String(r.f_jawatan || r.jawatan || r.position || '');
    const gId  = parseInt(r.f_groupID || r.groupID || r.group_id || 0, 10);
    const gKod = String(r.f_groupKod || r.groupKod || r.group_kod || r.group || '');
    const gName = String(r.f_groupName || r.groupName || r.group_name || gKod);
    const explicitBadgeClass = String(r.f_badge_class || r.badgeClass || '').trim();
    const explicitRowClass = String(r.f_row_class || r.rowClass || '').trim();
    const explicitRowColor = String(r.f_row_color || r.rowColor || '').trim();
    const extraRoles = Array.isArray(r.extra_roles) ? r.extra_roles : (Array.isArray(r.extraRoles) ? r.extraRoles : []);
    const flag = (typeof r.f_flag !== 'undefined') ? r.f_flag : (typeof r.flag !== 'undefined' ? r.flag : 1);
    const nopekerja = String(r.f_nopekerja || r.nopekerja || '');
    const avatarUrl = String(r.avatarUrl || r.avatar || '');
    const isProtectedAccount = (typeof r.is_protected_account !== 'undefined')
      ? !!r.is_protected_account
      : isProtectedStaffAccountClient(stafID);
    const canEditGroup = (typeof r.can_edit_group !== 'undefined')
      ? !!r.can_edit_group
      : ((typeof r.canEditGroup !== 'undefined') ? !!r.canEditGroup : isSuperAdmin);
    const canDeleteUser = (typeof r.can_delete_user !== 'undefined')
      ? !!r.can_delete_user
      : ((typeof r.canDeleteUser !== 'undefined')
        ? !!r.canDeleteUser
        : (!isCurrentLoggedInUserTarget(userID, stafID, nopekerja) && !isProtectedAccount));

    // Create row element using jQuery to avoid unsafe innerHTML with server HTML
    const $tr = $('<tr>')
      .attr('data-user-id', userID)
      .attr('data-group-id', String(gId || ''))
      .attr('data-group-kod', gKod)
      .attr('data-row-color', explicitRowColor || getGroupStyle(gId, gKod).rowColor || '')
      .attr('data-row-class', explicitRowClass || getRowClass(gId, gKod))
      .attr('data-flag', String(flag))
      .attr('data-extra-count', String(extraRoles.length))
      .attr('data-extra-roles', extraRoles.join(', '))
      .addClass(explicitRowClass || getRowClass(gId, gKod));

    // Column: bil (filled by DataTable rowCallback)
    $tr.append($('<td>').addClass('col-bil'));

    // Column: nama (with stafID)
    const visibleIdentifier = categoryUser === 'UMUM' ? (loginID || stafID) : stafID;
    const nameText = nama + (visibleIdentifier ? (' (' + visibleIdentifier + ')') : '');
    const $nameShell = $('<div>').addClass('user-name-shell');
    $nameShell.append(
      $('<span>')
        .addClass('truncate-1line cell-tooltip-text')
        .attr('data-bs-toggle', 'tooltip')
        .attr('data-bs-placement', 'top')
        .attr('title', nameText)
        .text(nameText)
    );
    if (isProtectedAccount) {
      $nameShell.append(
        $('<span>')
          .addClass('protected-account-badge')
          .attr('data-bs-toggle', 'tooltip')
          .attr('data-bs-placement', 'top')
          .attr('title', 'Akaun ini dilindungi oleh sistem dan tidak boleh dipadam atau diubah aksesnya.')
          .text('Protected Account')
      );
    }
    $tr.append(
      $('<td>').addClass('col-nama').append($nameShell)
    );

    // Column: jabatan
    $tr.append(
      $('<td>').addClass('col-jabatan').append(
        $('<span>')
          .addClass('truncate-1line cell-tooltip-text')
          .attr('data-bs-toggle', 'tooltip')
          .attr('data-bs-placement', 'top')
          .attr('title', jabatan || '')
          .text(jabatan)
      )
    );

    // Column: group badge
    const $groupTd = $('<td>').addClass('col-group');
    const $cellInline = $('<span>').addClass('cell-inline');
    const $badge = $('<span>')
      .addClass('group-chip cell-tooltip-text')
      .attr('data-bs-toggle', 'tooltip')
      .attr('data-bs-placement', 'top')
      .attr('title', gName || '')
      .text(gName);
    const inlineBadgeStyle = explicitRowColor
      ? `background-color:${explicitRowColor};color:${getReadableTextColor(explicitRowColor)};`
      : getBadgeInlineStyle(gId, gKod);
    if (inlineBadgeStyle) {
      $badge.attr('style', inlineBadgeStyle);
    }
    const $info = $('<i>')
      .addClass('ri-information-line text-muted extra-roles-info')
      .attr('data-has-extra', extraRoles.length > 0 ? '1' : '0')
      .attr('data-bs-toggle', 'tooltip')
      .attr('data-bs-placement', 'top');
    renderExtraRolesInfo($info[0], extraRoles);
    $cellInline.append($badge).append($info);
    $groupTd.append($cellInline);
    $tr.append($groupTd);

    // Column: akses badge
    const $aksesTd = $('<td>').addClass('col-akses');
    const $aksesBadge = $('<span>').addClass('access-chip');
    if (parseInt(flag, 10) === 1) {
      $aksesBadge
        .addClass('is-allowed cell-tooltip-text')
        .attr('data-bs-toggle', 'tooltip')
        .attr('data-bs-placement', 'top')
        .attr('title', '0')
        .text('0');
    } else {
      $aksesBadge
        .addClass('is-blocked cell-tooltip-text')
        .attr('data-bs-toggle', 'tooltip')
        .attr('data-bs-placement', 'top')
        .attr('title', '0')
        .text('0');
    }
    $aksesTd.append($aksesBadge);
    $tr.append($aksesTd);

    // Column: actions
    const $actionsTd = $('<td>').addClass('col-actions');
    if (canEditGroup) {
      const $editBtn = $('<button>').attr('type','button').addClass('btn btn-outline-primary btn-sm icon-btn btn-edit-group')
        .attr('title', '0')
        .attr('data-user-id', userID)
        .attr('data-nama', nama)
        .attr('data-stafid', stafID)
        .attr('data-loginid', loginID)
        .attr('data-nickname', nickname)
        .attr('data-email', email)
        .attr('data-phone', phone)
        .attr('data-university', university)
        .attr('data-nokp', nokp)
        .attr('data-displayid', visibleIdentifier)
        .attr('data-nopekerja', nopekerja)
        .attr('data-avatar-url', avatarUrl)
        .attr('data-jabatan', jabatan)
        .attr('data-group-id', String(gId || ''))
        .attr('data-group-kod', gKod)
        .attr('data-group-name', gName)
        .attr('data-scope', categoryUser === 'PELAJAR' ? 'student' : (categoryUser === 'UMUM' ? 'public' : 'staff'))
        .attr('data-flag', String(flag))
        .html('<i class="ri-pencil-line"></i>');

      $actionsTd.append($editBtn);
      if (canDeleteUser) {
        const $delBtn = $('<button>').attr('type','button').addClass('btn btn-outline-danger btn-sm icon-btn btn-delete-user ms-1')
          .attr('title', '0')
          .attr('data-user-id', userID)
          .attr('data-nama', nama)
          .attr('data-stafid', stafID)
          .attr('data-displayid', visibleIdentifier)
          .html('<i class="ri-delete-bin-line"></i>');
        $actionsTd.append($delBtn);
      }
    }
    $tr.append($actionsTd);

    return $tr;
  }

  // Function untuk reload table via AJAX (tanpa refresh page)
  async function reloadUserTable(highlightUserID = null) {
    // Cancel previous request if exists
    if (currentRequestController) {
      currentRequestController.abort();
    }
    
    currentRequestController = new AbortController();
    
    showLoading('0');
    
    try {
      trackEvent('user_list_reload', { highlightUserID });
      
      const r = await fetchWithRetry('0', {
        headers: { 'Accept': 'application/json' },
        signal: currentRequestController.signal
      });
      
      if (!r.ok) {
        let errorText = '0 ' + r.status;
        try {
          const errorData = await r.text();
          try {
            const errorJson = JSON.parse(errorData);
            errorText = errorJson.message || errorText;
          } catch (e) {
            errorText = errorData.substring(0, 200);
          }
        } catch (e) {
          // Ignore
        }
        throw new Error(errorText);
      }
      
      const contentType = r.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        const text = await r.text();
        throw new Error('0');
      }
      
      const j = await r.json();
      if (j.error) throw new Error(j.message || '0');
      
      // Jika DataTable sudah wujud, update dengan destroy(false) dan re-init untuk maintain layout
      if ($.fn.DataTable.isDataTable('#userDT')) {
        // Ensure a global safe HTML setter is available
        if (typeof window.setSafeInnerHTML !== 'function') {
          window.setSafeInnerHTML = function(el, html) {
            if (!el) return;
            if (!html) { el.innerHTML = ''; return; }
            if (window.DOMPurify && typeof DOMPurify.sanitize === 'function') {
              el.innerHTML = DOMPurify.sanitize(html);
              return;
            }
            try {
              var doc = new DOMParser().parseFromString('<div>' + html + '</div>', 'text/html');
              doc.querySelectorAll('script').forEach(function(s){ s.remove(); });
              doc.querySelectorAll('*').forEach(function(n){
                Array.from(n.attributes).forEach(function(a){
                  if (/^on/i.test(a.name)) n.removeAttribute(a.name);
                  if ((a.name === 'src' || a.name === 'href') && /^javascript:/i.test(a.value)) n.removeAttribute(a.name);
                });
              });
              el.innerHTML = doc.body.firstChild ? doc.body.firstChild.innerHTML : '';
            } catch (e) {
              el.innerHTML = html;
            }
          };
        }

        const dt = $('#userDT').DataTable();
        
        // Preserve current state
        const currentPage = dt.page();
        const currentSearch = dt.search();
        const currentOrder = dt.order();
        const currentLength = dt.page.len();
        
        const rowData = Array.isArray(j.rows) ? j.rows : [];
        const $newRows = $(rowData.map(r => buildRowFromData(r).get(0)));
        
        // Clear existing rows (tanpa destroy untuk maintain layout)
        dt.clear();
        
        const expectedColumnCount = $('#userDT thead th').length || 6;

        // Add new rows - pastikan rows match dengan table structure semasa
        if ($newRows.length > 0) {
          const rowsArray = [];
          $newRows.each(function() {
            const $row = $(this);
            // Pastikan row ada semua columns yang diperlukan ikut layout semasa
            const tdCount = $row.find('td').length;
            if (tdCount === expectedColumnCount) {
              rowsArray.push(this);
            }
          });
          
          if (rowsArray.length > 0) {
            try {
              dt.rows.add(rowsArray);
            } catch (e) {
              // Fallback: destroy dan re-init
              dt.destroy();
              const $tbody = $('#userDT tbody');
              const nodes = rowData.map(r => buildRowFromData(r).get(0));
              $tbody.html('');
              $tbody.append($(nodes));
              dtInstance = $('#userDT').DataTable({
                pageLength: currentLength || 10,
                lengthChange: true,
                lengthMenu: [10, 25, 50, 100, 200],
                ordering: true,
                order: currentOrder.length > 0 ? currentOrder : [[1,'asc']],
                autoWidth: false,
                scrollX: false,
                dom: '<"row mb-2"<"col-sm-12 col-md-6 dt-top-left"l><"col-sm-12 col-md-6 d-flex justify-content-md-end dt-top-right"f>>' +
                  't' +
                  '<"dt-bottom-row mt-2 d-flex justify-content-between align-items-center"<"dt-info-left"i><"dt-paging-right d-flex justify-content-end"p>>',
                // ✅ Pastikan length selector tidak wrap
                lengthMenu: [10, 25, 50, 100, 200],
                language: {
                  lengthMenu: "0",
                  search: "",
                  info: "0",
                  infoEmpty: "0",
                  paginate: { previous: "0", next: "0"},
                  zeroRecords: "0"
                },
                columnDefs: [
                  { targets: 0, orderable:false, searchable:false, width: 56 },
                  { targets: expectedColumnCount - 1, orderable:false, searchable:false, width: 110 }
                ],
                rowCallback: function(row, data, displayIndex){
                  const api  = this.api();
                  const info = api.page.info();
                  $('td:eq(0)', row).text(info.start + displayIndex + 1);
                  
                  const $row = $(row);
                  applyRowClass($row);
                },
                initComplete: function() {
                  setupTableControls();
                  try {
                    const _lbl = 0;
                    const _ph = String(_lbl).replace(/[:：\s]+$/, '').trim();
                    $('#userDT_filter input').attr('placeholder', _ph);
                  } catch(e) { /* ignore */ }
                }
              });
              dt = dtInstance;
              if (currentSearch) {
                dt.search(currentSearch);
              }
              if (currentLength) {
                dt.page.len(currentLength);
              }
              const pageInfo = dt.page.info();
              const targetPage = Math.min(currentPage, Math.max(0, pageInfo.pages - 1));
              if (targetPage >= 0 && targetPage < pageInfo.pages) {
                dt.page(targetPage);
              }
              dt.draw();
              return; // Exit early
            }
          }
        }
        
        // Restore state
        dt.order(currentOrder);
        dt.search(currentSearch);
        if (currentLength) {
          dt.page.len(currentLength);
        }
        
        // Restore page position
        const pageInfo = dt.page.info();
        const targetPage = Math.min(currentPage, Math.max(0, pageInfo.pages - 1));
        if (targetPage >= 0 && targetPage < pageInfo.pages) {
          dt.page(targetPage);
        }
        
        // Draw dengan false untuk avoid full redraw dan maintain layout
        dt.draw(false);
        
        // Update row numbers dan highlighting (tanpa trigger layout change)
        // Re-get pageInfo selepas draw untuk accurate row numbers
        const currentPageInfo = dt.page.info();
        dt.rows().every(function() {
          const row = this.node();
          const displayIndex = this.index();
          $('td:eq(0)', row).text(currentPageInfo.start + displayIndex + 1);
          
          const $row = $(row);
          applyRowClass($row);
        });
        
        // Highlight row jika ada userID yang perlu di-highlight
        if (highlightUserID) {
          setTimeout(() => {
            // Cari row di semua halaman (termasuk yang filtered)
            const $targetRow = $(`#userDT tbody tr[data-user-id="${highlightUserID}"]`);
            if ($targetRow.length > 0) {
              // Pastikan row visible (jika filtered, navigate ke page yang betul)
              const rowIndex = dt.rows({ search: 'applied' }).nodes().indexOf($targetRow[0]);
              if (rowIndex >= 0) {
                const pageInfo = dt.page.info();
                const targetPage = Math.floor(rowIndex / pageInfo.length);
                if (targetPage !== pageInfo.page) {
                  dt.page(targetPage).draw(false);
                }
              }
              
              // Add highlight class
              $targetRow.addClass('row-updated-highlight');
              
              // Remove highlight after configured duration
              setTimeout(() => {
                $targetRow.removeClass('row-updated-highlight');
              }, CONFIG.HIGHLIGHT_DURATION);
            }
          }, CONFIG.ANIMATION_DELAY);
        }
        
        // Update dtInstance reference
        dtInstance = dt;
        
        // Re-setup table controls
        setupTableControls();
        initTooltips(document);
        
        hideLoading();
        return;
      }
      
      // Fallback: jika DataTable belum wujud, init seperti biasa
      const $tbody = $('#userDT tbody');
      const nodes = rowData.map(r => buildRowFromData(r).get(0));
      $tbody.html('');
      $tbody.append($(nodes));
      
      dtInstance = $('#userDT').DataTable({
        pageLength: 10,
        lengthChange: true,
        lengthMenu: [10, 25, 50, 100, 200],
        ordering: true,
        order: [[1,'asc']],
        autoWidth: false,
        scrollX: false,
        dom: '<"row mb-2"<"col-sm-12 col-md-6 dt-top-left"l><"col-sm-12 col-md-6 d-flex justify-content-md-end dt-top-right"f>>' +
          't' +
          '<"dt-bottom-row mt-2 d-flex justify-content-between align-items-center"<"dt-info-left"i><"dt-paging-right d-flex justify-content-end"p>>',
        language: {
          lengthMenu: "0",
          search: "",
          info: "0",
          infoEmpty: "0",
          paginate: { previous: "0", next: "0"},
          zeroRecords: "0"
        },
        columnDefs: [
          { targets: 0, orderable:false, searchable:false, width: 56 },
          { targets: 5, orderable:false, searchable:false, width: 110 }
        ],
        rowCallback: function(row, data, displayIndex){
          const api  = this.api();
          const info = api.page.info();
          $('td:eq(0)', row).text(info.start + displayIndex + 1);
        },
        initComplete: function() {
          setupTableControls();
          initTooltips(document);
          
          // Highlight row jika ada userID yang perlu di-highlight (fallback case)
          if (highlightUserID) {
            setTimeout(() => {
              const $targetRow = $(`#userDT tbody tr[data-user-id="${highlightUserID}"]`);
              if ($targetRow.length > 0) {
                // Scroll to row jika tidak visible
                const rowOffset = $targetRow.offset();
                if (rowOffset) {
                  $('html, body').animate({
                    scrollTop: rowOffset.top - 100
                  }, 500);
                }
                
                // Add highlight class
                $targetRow.addClass('row-updated-highlight');
                
                // Remove highlight after configured duration
                setTimeout(() => {
                  $targetRow.removeClass('row-updated-highlight');
                }, CONFIG.HIGHLIGHT_DURATION);
              }
            }, CONFIG.ANIMATION_DELAY);
          }
        }
      });
      
      hideLoading();
      
    } catch (e) {
      hideLoading();
      
      // Handle abort error gracefully
      if (e.name === 'AbortError') {
        console.log('Request cancelled');
        return;
      }
      
      // Show user-friendly error
      const errorMsg = sanitizeError(e);
      await Swal.fire({
        icon: 'error',
        title: '0',
        text: errorMsg || '0',
        confirmButtonText: '0',
        confirmButtonColor: '#dc3545'
      });
      
      trackEvent('user_list_reload_error', { error: errorMsg });
      throw e;
    }
  }

  // Function untuk setup table controls (buttons, filters, etc)
  function setupTableControls() {
    if (window.DataTableStandard && typeof window.DataTableStandard.decorate === 'function') {
      window.DataTableStandard.decorate('#userDT', {
        searchPlaceholder: 0
      });
    }
    // Styling
    // ✅ Removed form-select-sm untuk besarkan saiz dropdown
    $('#userDT_length select').addClass('form-select w-auto');
    $('#userDT_length label').addClass('mb-0');
    const $topLeft  = $('#userDT_wrapper .dt-top-left').addClass('d-flex align-items-center gap-2 flex-nowrap');
    const $topRight = $('#userDT_wrapper .dt-top-right').addClass('align-items-center gap-2 flex-nowrap');
    
    // Remove existing buttons jika ada
    $('#btnSyncSybase').remove();
    $('#btnAddUser').remove();
    
    // Button Sync
    if (!document.getElementById('btnSyncSybase')) {
      const $syncBtn = $('<button type="button" id="btnSyncSybase" class="btn btn-primary">' +
          '<i class="ri-refresh-line me-1"></i> 0' +
        '</button>');
      
      // Append button ke akhir topRight container (kanan sekali)
      if ($topRight.length) {
        $topRight.append($syncBtn);
      } else {
        // Fallback: append ke filter jika topRight tidak wujud
        const $filter = $('#userDT_filter');
        if ($filter.length) {
          $filter.append($syncBtn);
        }
      }
      
      $syncBtn.on('click', createRateLimitedHandler(async function(e){
        e.preventDefault();
        
          const $btn = $(this);
          const originalHtml = $btn.html();
          const originalDisabled = $btn.prop('disabled');
          
          $btn.prop('disabled', true);
          $btn.html('<i class="ri-loader-4-line ri-spin me-1"></i> 0');
          
          try {
            trackEvent('user_sync_sybase', {});
            
            const r = await fetchWithRetry('0', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF,
                'Accept': 'application/json'
              }
            });
            
            if (!r.ok) throw new Error('0 ' + r.status);
            const j = await r.json();
            if (j.error) throw new Error(j.message || '0');
            
            trackEvent('user_sync_sybase_success', { updated: j.updated || 0 });
            
            await Swal.fire({
              icon: 'success',
              title: '0',
              html:
                '<div class="sync-swal-wrap">' +
                  '<div class="sync-swal-banner">' +
                    '<div class="sync-swal-banner-icon"><i class="ri-checkbox-circle-line"></i></div>' +
                    '<div>' +
                      '<div class="sync-swal-banner-title">0</div>' +
                      '<div class="sync-swal-banner-text">' + (j.message || '0') + '</div>' +
                    '</div>' +
                  '</div>' +
                  '<div class="sync-swal-card">' +
                    '<div class="sync-swal-card-title"><i class="ri-bar-chart-box-line"></i>0</div>' +
                    '<div class="sync-swal-stats">' +
                      '<div class="sync-swal-stat">' +
                        '<div class="sync-swal-stat-label">0</div>' +
                        '<div class="sync-swal-stat-value is-success">' + (j.updated || 0) + '</div>' +
                      '</div>' +
                      '<div class="sync-swal-stat">' +
                        '<div class="sync-swal-stat-label">0</div>' +
                        '<div class="sync-swal-stat-value is-warning">' + (j.skipped || 0) + '</div>' +
                      '</div>' +
                      '<div class="sync-swal-stat">' +
                        '<div class="sync-swal-stat-label">0</div>' +
                        '<div class="sync-swal-stat-value is-danger">' + (j.errors || 0) + '</div>' +
                      '</div>' +
                      '<div class="sync-swal-stat">' +
                        '<div class="sync-swal-stat-label">0</div>' +
                        '<div class="sync-swal-stat-value is-primary">' + (j.total || 0) + '</div>' +
                      '</div>' +
                    '</div>' +
                  '</div>' +
                '</div>',
              confirmButtonText: '<i class="ri-check-line me-1"></i>0',
              confirmButtonColor: '#198754',
              buttonsStyling: true,
              allowOutsideClick: false,
              allowEscapeKey: false,
              showCloseButton: false,
              width: '480px',
              customClass: {
                popup: 'swal2-popup-custom',
                title: 'swal2-title-custom',
                confirmButton: 'swal2-confirm-custom'
              }
            });
            
            await reloadUserTable();
          } catch (e) {
            const errorMsg = sanitizeError(e);
            trackEvent('user_sync_sybase_error', { error: errorMsg });
            
            await Swal.fire({
              icon: 'error',
              title: '0',
              text: errorMsg || '0',
              confirmButtonText: '0',
              confirmButtonColor: '#dc3545'
            });
          } finally {
            $btn.prop('disabled', originalDisabled);
            $btn.html(originalHtml);
          }
      }, 2000));
    }
    
    // Button Tambah Pengguna (Super Admin sahaja)
    if (isSuperAdmin && !document.getElementById('btnAddUser')) {
      const $addBtn = $('<button type="button" id="btnAddUser" class="btn btn-success" onclick="return window.userListOpenAdd ? window.userListOpenAdd(\'staff\') : false;">' +
          '<i class="ri-user-add-line me-1"></i> 0' +
        '</button>');
      $addBtn.attr('data-modal-bound', '1');
      
      // Append button ke akhir topRight container (kanan sekali, selepas btnSyncSybase jika ada)
      if ($topRight.length) {
        if (document.getElementById('btnSyncSybase')) {
          $('#btnSyncSybase').after($addBtn);
        } else {
          $topRight.append($addBtn);
        }
      } else {
        // Fallback: append ke filter jika topRight tidak wujud
        const $filter = $('#userDT_filter');
        if ($filter.length) {
          if (document.getElementById('btnSyncSybase')) {
            $('#btnSyncSybase').after($addBtn);
          } else {
            $filter.append($addBtn);
          }
        }
      }
      
      $addBtn.on('click', async function(e){
        e.preventDefault();
        if (window.userListOpenAdd) {
          await window.userListOpenAdd('staff');
        }
      });
    }
    // Ensure search input has placeholder from translation (strip trailing colon)
    try {
      const _lbl = 0;
      const _ph = String(_lbl).replace(/[:：\s]+$/, '').trim();
      const $inp = $('#userDT_filter input');
      if ($inp.length) $inp.attr('placeholder', _ph);
    } catch(e) { /* ignore */ }
  }

  // Helper: auto-size select ikut teks option yang terpilih
  function fitSelectWidth(sel){
    if (!sel) return;
    if (sel.classList && sel.classList.contains('dt-group-filter')) {
      sel.style.width = '210px';
      sel.style.minWidth = '210px';
      sel.style.maxWidth = '210px';
      return;
    }
    sel.style.width = 'auto';
    const span = document.createElement('span');
    span.style.visibility = 'hidden';
    span.style.position   = 'fixed';
    span.style.whiteSpace = 'pre';
    const cs = window.getComputedStyle(sel);
    span.style.font = cs.font || `${cs.fontSize} ${cs.fontFamily}`;
    span.style.fontSize   = cs.fontSize;
    span.style.fontFamily = cs.fontFamily;
    span.textContent = sel.options[sel.selectedIndex]?.text || sel.value || '';
    document.body.appendChild(span);
    const padX = 28;
    const w = Math.ceil(span.getBoundingClientRect().width) + padX;
    document.body.removeChild(span);
    sel.style.width = w + 'px';
  }

  function getScopeMeta(scope) {
    const normalized = String(scope || 'staff').trim().toLowerCase();
    if (normalized === 'student' || normalized === 'pelajar') {
      if (!CONFIG.STUDENT_MODE_ENABLED) {
        return {
          scope: 'staff',
          tableId: 'userDT',
          filterId: 'dtGroupFilter',
          syncButtonId: 'btnSyncSybase',
          addButtonId: 'btnAddUser',
          addLabel: '0'
        };
      }
      return {
        scope: 'student',
        tableId: 'userDTStudent',
        filterId: 'dtGroupFilterStudent',
        syncButtonId: 'btnSyncStudent',
        addButtonId: 'btnAddUserStudent',
        addLabel: '0'
      };
    }
    if (normalized === 'public' || normalized === 'umum') {
      return {
        scope: 'public',
        tableId: 'userDTPublic',
        filterId: 'dtGroupFilterPublic',
        syncButtonId: 'btnSyncPublic',
        addButtonId: 'btnAddUserPublic',
        addLabel: '0'
      };
    }
    return {
      scope: 'staff',
      tableId: 'userDT',
      filterId: 'dtGroupFilter',
      syncButtonId: 'btnSyncSybase',
      addButtonId: 'btnAddUser',
      addLabel: '0'
    };
  }

  function drawTableRowNumbers(dt) {
    if (!dt || typeof dt.rows !== 'function') return;
    const pageInfo = dt.page.info();
    dt.rows({ page: 'current' }).every(function(displayIndex) {
      $('td:eq(0)', this.node()).text(pageInfo.start + displayIndex + 1);
      applyRowClass($(this.node()));
    });
  }

  async function populateSelectGroupsForScope(selectEl, scope, selectedId = '') {
    if (!selectEl) return;
    try {
      const safeScope = String(scope || 'staff').trim().toLowerCase() || 'staff';
      const r = await fetch(`0?scope=${encodeURIComponent(safeScope)}`, { headers:{'Accept':'application/json'} });
      const j = await r.json();
      selectEl.innerHTML = '';
      let optionCount = 0;
      (j.groups || []).forEach(g => {
        const id = g.id || g.f_groupID || '';
        const kod = g.kod || g.f_groupKod || '';
        const name = g.nama || g.f_groupName || kod;
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = name;
        if (selectedId && String(selectedId) === String(id)) opt.selected = true;
        selectEl.appendChild(opt);
        if (id) optionCount++;
      });
      if (!selectedId && optionCount === 1 && safeScope !== 'staff' && selectEl.options[0]) {
        selectEl.value = selectEl.options[0].value;
      }
    } catch (e) { /* ignore */ }
  }

  function removeUserRowFromTable(tableId, userID) {
    const selector = `#${tableId}`;
    const $row = $(`${selector} tbody tr[data-user-id="${userID}"]`);
    if (!$row.length) return;
    if ($.fn.DataTable.isDataTable(selector)) {
      const dt = $(selector).DataTable();
      dt.row($row).remove().draw(false);
      drawTableRowNumbers(dt);
      return;
    }
    $row.remove();
  }

  function setupScopedTableControls(tableId, scope, options = {}) {
    const selector = `#${tableId}`;
    const wrapperSelector = `${selector}_wrapper`;
    const meta = getScopeMeta(scope);
    const addLabel = options.addLabel || meta.addLabel;

    if (window.DataTableStandard && typeof window.DataTableStandard.decorate === 'function') {
      window.DataTableStandard.decorate(selector, {
        searchPlaceholder: 0
      });
    }

    $(`${wrapperSelector} .dataTables_length select`).addClass('form-select w-auto');
    $(`${wrapperSelector} .dataTables_length label`).addClass('mb-0');
    const $topLeft = $(`${wrapperSelector} .dt-top-left`).addClass('d-flex align-items-center gap-2 flex-nowrap');
    const $topRight = $(`${wrapperSelector} .dt-top-right`).addClass('align-items-center gap-2 flex-nowrap');

    $(`#${meta.filterId}`).remove();
    $(`#${meta.addButtonId}`).remove();

    const $grp = $(`<select id="${meta.filterId}" class="form-select dt-group-filter"><option value="">0</option></select>`);
    const $filter = $(`${selector}_filter`);
    if ($filter.length) {
      $filter.after($grp);
    } else {
      $topRight.append($grp);
    }

    (async () => {
      try {
        const res = await fetch(`0?scope=${encodeURIComponent(meta.scope)}`, { headers: { 'Accept':'application/json' } });
        const j = await res.json();
        let optionCount = 0;
        (j.groups || []).forEach(g => {
          const id = g.id || g.f_groupID || '';
          const name = g.nama || g.f_groupName || g.kod || g.f_groupKod || '';
          if (!id || !name) return;
          $grp.append(new Option(name, String(id)));
          optionCount++;
        });
        if (meta.scope !== 'staff' && optionCount === 1) {
          $grp.val($grp.find('option:last').val());
          $grp.trigger('change');
        }
      } catch (e) { /* ignore */ }
      fitSelectWidth($grp[0]);
    })();

    const dt = $.fn.DataTable.isDataTable(selector) ? $(selector).DataTable() : null;
    let groupFilterId = '';
    $grp.off('change').on('change', function() {
      groupFilterId = this.value || '';
      if (dt) {
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
          if (!settings || settings.nTable?.id !== tableId) return true;
          if (!groupFilterId) return true;
          const rowNode = dt.row(dataIndex).node();
          const gid = rowNode ? (rowNode.getAttribute('data-group-id') || '') : '';
          return String(gid) === String(groupFilterId);
        });
        dt.draw();
      }
      fitSelectWidth(this);
    });

    $(`#${meta.syncButtonId}`).remove();
    if (meta.scope === 'student' && !document.getElementById(meta.syncButtonId)) {
      const syncLabel = meta.scope === 'student'
        ? '0'
        : '0';
      const $syncBtn = $(`<button type="button" id="${meta.syncButtonId}" class="btn btn-primary"><i class="ri-refresh-line me-1"></i> ${syncLabel}</button>`);
      $topRight.append($syncBtn);
      $syncBtn.on('click', async function(e) {
        e.preventDefault();
        if (window.Swal) {
          await Swal.fire({
            icon: 'info',
            title: '0',
            text: '0',
            confirmButtonText: '0',
            confirmButtonColor: '#0d6efd'
          });
        }
      });
    }

    if (isSuperAdmin && !document.getElementById(meta.addButtonId)) {
      const $addBtn = $(`<button type="button" id="${meta.addButtonId}" class="btn btn-success" onclick="return window.userListOpenAdd ? window.userListOpenAdd('${meta.scope}') : false;"><i class="ri-user-add-line me-1"></i> ${addLabel}</button>`);
      $addBtn.attr('data-modal-bound', '1');
      $topRight.append($addBtn);
      $addBtn.on('click', async function(e) {
        e.preventDefault();
        if (window.userListOpenAdd) {
          await window.userListOpenAdd(meta.scope);
        }
      });
    }

    try {
      const _lbl = 0;
      const _ph = String(_lbl).replace(/[:：\s]+$/, '').trim();
      const $inp = $(`${selector}_filter input`);
      if ($inp.length) $inp.attr('placeholder', _ph);
    } catch(e) { /* ignore */ }
  }

  function initScopedDataTable(tableId, scope) {
    const tableSelector = `#${tableId}`;
    if (!document.querySelector(tableSelector)) return null;
    const expectedColumnCount = $(`${tableSelector} thead th`).length || 6;
    const lastColumnIndex = Math.max(0, expectedColumnCount - 1);
    if ($.fn.DataTable.isDataTable(tableSelector)) {
      const existing = $(tableSelector).DataTable();
      const addLabel = scope === 'student'
        ? '0'
        : '0';
      setupScopedTableControls(tableId, scope, { addLabel });
      existing.columns.adjust().draw(false);
      return existing;
    }

    const dt = $(tableSelector).DataTable({
      pageLength: 10,
      lengthChange: true,
      lengthMenu: [10, 25, 50, 100, 200],
      ordering: true,
      order: [[1,'asc']],
      autoWidth: false,
      scrollX: false,
      dom:
        '<"row mb-2"<"col-sm-12 col-md-6 dt-top-left"l><"col-sm-12 col-md-6 d-flex justify-content-md-end dt-top-right"f>>' +
        't' +
        '<"dt-bottom-row mt-2 d-flex justify-content-between align-items-center"<"dt-info-left"i><"dt-paging-right d-flex justify-content-end"p>>',
      language: {
        lengthMenu: "0",
        search: "",
        info: "0",
        infoEmpty: "0",
        emptyTable: "0",
        paginate: { previous: "0", next: "0"},
        zeroRecords: "0"
      },
      columnDefs: [
        { targets: 0, orderable:false, searchable:false, width: 56 },
        { targets: lastColumnIndex, orderable:false, searchable:false, width: 110 }
      ],
      rowCallback: function(row, data, displayIndex){
        const api = this.api();
        const info = api.page.info();
        $('td:eq(0)', row).text(info.start + displayIndex + 1);
        applyRowClass($(row));
      },
      initComplete: function() {
        const addLabel = scope === 'student'
          ? '0'
          : '0';
        setupScopedTableControls(tableId, scope, { addLabel });
        initTooltips(document.querySelector(tableSelector) || document);
      },
      drawCallback: function() {
        drawTableRowNumbers(this.api());
        initTooltips(document.querySelector(tableSelector) || document);
      }
    });
    return dt;
  }

  document.addEventListener('DOMContentLoaded', function(){
    if (!hasDT()) { return; }

    // Re-init guard
    if ($.fn.DataTable.isDataTable('#userDT')) {
      $('#userDT').DataTable().destroy();
    }

    const dt = $('#userDT').DataTable({
      pageLength: 10,
      lengthChange: true,
      lengthMenu: [10, 25, 50, 100, 200],
      ordering: true,
      order: [[1,'asc']],                 // ikut kolum Nama (StafID)
      autoWidth: false,
      scrollX: false,
      dom:
        '<"row mb-2"<"col-sm-12 col-md-6 dt-top-left"l><"col-sm-12 col-md-6 d-flex justify-content-md-end dt-top-right"f>>' +
        't' +
        '<"dt-bottom-row mt-2 d-flex justify-content-between align-items-center"<"dt-info-left"i><"dt-paging-right d-flex justify-content-end"p>>',
      language: {
        lengthMenu: "0",
        search: "",
        info: "0",
        infoEmpty: "0",
        emptyTable: "0",
        paginate: { previous: "0", next: "0"},
        zeroRecords: "0"
      },
      columnDefs: [
        { targets: 0, orderable:false, searchable:false, width: 56 },  // Bil
        { targets: Math.max(0, ($('#userDT thead th').length || 6) - 1), orderable:false, searchable:false, width: 110 }  // Tindakan (ikon)
      ],
        initComplete: function() {
          try {
            const _lbl = 0;
            const _ph = String(_lbl).replace(/[:：\s]+$/, '').trim();
            $('#userDT_filter input').attr('placeholder', _ph);
          } catch(e) { /* ignore */ }
        },
      rowCallback: function(row, data, displayIndex){
        const api  = this.api();
        const info = api.page.info();
        $('td:eq(0)', row).text(info.start + displayIndex + 1);
        
        // Apply row highlighting based on group (if not already applied from server-side)
        const $row = $(row);
        applyRowClass($row);
      },
      initComplete: function() {
        setupTableControls();
        initTooltips(document.querySelector('#userDT') || document);
      },
      drawCallback: function() {
        drawTableRowNumbers(this.api());
        initTooltips(document.querySelector('#userDT') || document);
      }
    });
    
    // Set dtInstance untuk digunakan dalam functions lain
    dtInstance = dt;

    // === Styling & susun kiri/kanan (sebaris, tak berbalut) ===
    // ✅ Removed form-select-sm untuk besarkan saiz dropdown
    $('#userDT_length select')
      .addClass('form-select w-auto');

    $('#userDT_length label').addClass('mb-0');
    const $topLeft  = $('#userDT_wrapper .dt-top-left').addClass('d-flex align-items-center gap-2 flex-nowrap');
    const $topRight = $('#userDT_wrapper .dt-top-right').addClass('align-items-center gap-2 flex-nowrap');

    // === Dropdown Filter Kumpulan (auto width) — duduk di sebelah carian, sebelum button ===
    const $grp = $(`
      <select id="dtGroupFilter" class="form-select">
        <option value="">0</option>
      </select>
    `);
    // Append ke topRight selepas search box tapi sebelum button
    // Search box adalah #userDT_filter, jadi kita append selepas filter
    const $filter = $('#userDT_filter');
    if ($filter.length) {
      $filter.after($grp);
    } else {
      // Fallback: append ke topRight (akan duduk sebelum button kerana button di-append selepas)
      $topRight.append($grp);
    }

    // Ambil senarai kumpulan & populate option (guna ID untuk penapisan tepat)
    (async () => {
      try {
        const res = await fetch('0', { headers: { 'Accept':'application/json' } });
        const j = await res.json();
        (j.groups || []).forEach(g => {
          const id = g.id || g.f_groupID || '';
          const name = g.nama || g.f_groupName || g.kod || g.f_groupKod || '';
          if (!id || !name) return;
          $grp.append(new Option(name, String(id)));
        });
      } catch (e) { }
      fitSelectWidth($grp[0]);
    })();

    // Helper: escape regex
    function escRx(s){ return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

    // Tapis ikut kumpulan berdasarkan data-group-id + auto-size bila berubah
    let groupFilterId = '';
    if (!window.__userDTGroupFilterAdded) {
      $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!settings || settings.nTable?.id !== 'userDT') return true;
        if (!groupFilterId) return true;
        const rowNode = dt.row(dataIndex).node();
        const gid = rowNode ? (rowNode.getAttribute('data-group-id') || '') : '';
        return String(gid) === String(groupFilterId);
      });
      window.__userDTGroupFilterAdded = true;
    }
    $('#dtGroupFilter').on('change', function(){
      groupFilterId = this.value || '';
      dt.draw();
      fitSelectWidth(this);
    });

    // Resize semula bila window berubah (optional)
    window.addEventListener('resize', () => fitSelectWidth(document.getElementById('dtGroupFilter')));

    // Setup table controls (buttons, filters) - ini akan handle semua termasuk dropdown filter
    setupTableControls();

    const userAccessTabs = document.getElementById('userAccessTabs');
    if (userAccessTabs) {
      userAccessTabs.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(tabBtn) {
        tabBtn.addEventListener('click', function(e) {
          const targetSelector = e.currentTarget?.getAttribute('data-bs-target') || '';
          window.setTimeout(function() {
            if (targetSelector === '#tab-student-access') {
              if (!CONFIG.STUDENT_MODE_ENABLED) return;
              initScopedDataTable('userDTStudent', 'student');
            } else if (targetSelector === '#tab-public-access') {
              initScopedDataTable('userDTPublic', 'public');
            }
          }, 0);
        });
        tabBtn.addEventListener('shown.bs.tab', function(e) {
          const targetSelector = e.target?.getAttribute('data-bs-target') || '';
          try {
            if (targetSelector) {
              if (!CONFIG.STUDENT_MODE_ENABLED && targetSelector === '#tab-student-access') {
                sessionStorage.setItem('userListActiveTab', '#tab-staff-access');
              } else {
              sessionStorage.setItem('userListActiveTab', targetSelector);
              }
            }
          } catch (err) { /* ignore */ }
          if (targetSelector === '#tab-student-access') {
            if (!CONFIG.STUDENT_MODE_ENABLED) return;
            initScopedDataTable('userDTStudent', 'student');
          } else if (targetSelector === '#tab-public-access') {
            initScopedDataTable('userDTPublic', 'public');
          } else if (targetSelector === '#tab-staff-access' && $.fn.DataTable.isDataTable('#userDT')) {
            $('#userDT').DataTable().columns.adjust().draw(false);
          }
        });
      });
    }

    try {
      const savedActiveTab = sessionStorage.getItem('userListActiveTab') || '';
      if (!CONFIG.STUDENT_MODE_ENABLED && savedActiveTab === '#tab-student-access') {
        sessionStorage.setItem('userListActiveTab', '#tab-staff-access');
      }
      const savedTabBtn = savedActiveTab
        ? userAccessTabs?.querySelector(`[data-bs-target="${savedActiveTab}"]`)
        : null;
      if (savedTabBtn && window.bootstrap && bootstrap.Tab) {
        bootstrap.Tab.getOrCreateInstance(savedTabBtn).show();
      }
    } catch (e) { /* ignore */ }

    window.setTimeout(function() {
      if (CONFIG.STUDENT_MODE_ENABLED) {
        initScopedDataTable('userDTStudent', 'student');
      }
      initScopedDataTable('userDTPublic', 'public');
    }, 120);

    // ===== Modal Tukar Kumpulan =====
    const modalEl = document.getElementById('userGroupModal');
    const modal   = modalEl ? new bootstrap.Modal(modalEl) : null;
    const errEl   = document.getElementById('ug_error');
    const roleModalEl = document.getElementById('roleExtraModal');
    let roleModal = roleModalEl ? new bootstrap.Modal(roleModalEl) : null;
    let restoreParentUserGroupModal = false;
    let restoreParentAfterRoleAlert = false;
    let currentPrimaryRoleName = '';
    let currentUserScope = 'staff';
    let currentAddScope = 'staff';
    const roleListEl = document.getElementById('roleExtraList');
    const roleErrEl = document.getElementById('roleExtraError');

    function showRoleErr(msg){ if(!roleErrEl) return; roleErrEl.textContent = msg || '0'; roleErrEl.classList.remove('d-none'); }
    function hideRoleErr(){ if(!roleErrEl) return; roleErrEl.classList.add('d-none'); }
    function showErr(msg){ if(!errEl) return; errEl.textContent = msg || '0'; errEl.classList.remove('d-none'); }
    function hideErr(){ if(!errEl) return; errEl.classList.add('d-none'); }
    function resetPublicEditFields() {
      ['ug_publicName','ug_publicNickname','ug_publicEmail','ug_publicPhone','ug_publicUniversity','ug_publicNoKp','ug_publicPassword','ug_publicPasswordConfirm']
        .forEach(function(id) {
          const el = document.getElementById(id);
          if (el) {
            el.value = '';
            el.classList.remove('field-invalid');
          }
        });
    }
    function resetEditPasswordFields() {
      ['ug_resetPassword', 'ug_resetPasswordConfirm'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) {
          el.value = '';
          el.classList.remove('field-invalid');
        }
      });
    }
    function configureEditModalForScope(scope) {
      const normalized = String(scope || 'staff').trim().toLowerCase() || 'staff';
      const publicSection = document.getElementById('ug_publicSection');
      const publicTabWrap = document.getElementById('ug-tab-public-wrap');
      const publicTabBtn = document.getElementById('ug-tab-public');
      const passwordSection = document.getElementById('ug_passwordSection');
      const userInfoTabBtn = document.getElementById('ug-tab-userinfo');
      const addRoleBtn = document.getElementById('ug_addRoleBtn');
      const modalTitle = document.getElementById('userGroupTitle');
      const jabatanLabel = document.querySelector('#userGroupModal .info-item:nth-child(2) .info-label');
      const saveBtn = document.getElementById('ug_saveBtn');
      document.getElementById('ug_scope').value = normalized;
      resetPublicEditFields();
      resetEditPasswordFields();
      if (publicSection) publicSection.classList.toggle('d-none', normalized !== 'public');
      if (publicTabWrap) publicTabWrap.classList.toggle('d-none', normalized !== 'public');
      if (passwordSection) passwordSection.classList.toggle('d-none', normalized === 'public');
      if (addRoleBtn) addRoleBtn.classList.toggle('d-none', normalized === 'public');
      if (modalTitle) {
        modalTitle.innerHTML = normalized === 'public'
          ? '<i class="ri-user-settings-line me-2"></i> 0'
          : '<i class="ri-user-settings-line me-2"></i> 0';
      }
      if (jabatanLabel) {
        jabatanLabel.textContent = normalized === 'public'
          ? '0'
          : '0';
      }
      if (saveBtn) {
        saveBtn.innerHTML = normalized === 'public'
          ? '<i class="ri-save-3-line me-1"></i> 0'
          : '<i class="ri-save-3-line me-1"></i> 0';
      }
      if (window.bootstrap && bootstrap.Tab && userInfoTabBtn) {
        bootstrap.Tab.getOrCreateInstance(userInfoTabBtn).show();
      } else if (publicTabBtn) {
        publicTabBtn.classList.remove('active');
      }
      hideErr();
    }

    function setRoleButton(count, list) {
      const btn = document.getElementById('ug_addRoleBtn');
      if (!btn) return;
      const label = '0';
      const cleanLabel = String(label).replace(/^\+\s*/, '').trim();
      const c = (typeof count === 'number') ? count : 0;
      btn.setAttribute('type', 'button');
      btn.innerHTML = `<i class="ri-add-line me-1"></i> ${cleanLabel} (${c})`;
      const title = Array.isArray(list) && list.length ? list.join(', ') : '0';
      btn.setAttribute('data-bs-toggle', 'tooltip');
      btn.setAttribute('data-bs-placement', 'top');
      btn.setAttribute('title', title);
      initTooltips(btn);
    }

    async function loadExtraRoles(userID){
      if (!roleListEl) return;
      roleListEl.innerHTML = '';
      try {
        const r = await fetch('0', {
          method: 'POST',
          headers: {'Content-Type':'application/json','X-CSRF-Token': CSRF, 'Accept':'application/json'},
          body: JSON.stringify({ action: 'get', userID, scope: currentUserScope })
        });
        const j = await r.json();
        if (!r.ok || !j || j.error) throw new Error((j && j.message) || '0');
        const roles = j.roles || [];
        if (!roles.length) {
          roleListEl.innerHTML = '<div class="text-muted">0</div>';
          setRoleButton(0, []);
          return;
        }
        const checkedNames = [];
        roles.forEach(role => {
          const rid = role.id || role.f_groupID;
          const rname = role.name || role.f_groupName || '';
          const checked = role.checked ? 'checked' : '';
          if (role.checked) checkedNames.push(rname);
          const item = document.createElement('label');
          item.className = 'role-item';
          item.innerHTML = `
            <input type="checkbox" value="${rid}" ${checked}>
            <span class="role-label">${rname}</span>
          `;
          roleListEl.appendChild(item);
        });
        setRoleButton(checkedNames.length, checkedNames);
      } catch (e) {
        showRoleErr(e.message || '0');
      }
    }

    function getPrimaryRoleNameFromSelect() {
      const sel = document.getElementById('ug_groupKod');
      if (!sel) return '';
      const opt = sel.selectedOptions && sel.selectedOptions[0] ? sel.selectedOptions[0] : null;
      if (!opt) return '';
      return (opt.textContent || '').trim();
    }

    async function populateGroups(selectedId, scope = 'staff'){
      try{
        const safeScope = String(scope || 'staff').trim().toLowerCase() || 'staff';
        const r = await fetch(`0?scope=${encodeURIComponent(safeScope)}`, { headers:{'Accept':'application/json'} });
        const j = await r.json();
        const sel = document.getElementById('ug_groupKod'); if (!sel) return;
        sel.innerHTML = '';
        (j.groups || []).forEach(g=>{
          const id   = g.id || g.f_groupID || '';
          const kod  = g.kod || g.f_groupKod || '';
          const name = g.nama || g.f_groupName || kod;
          const opt = document.createElement('option');
          opt.value = id; opt.textContent = name;
          if (selectedId && String(selectedId) === String(id)) opt.selected = true;
          sel.appendChild(opt);
        });
      }catch(e){ }
    }

    if (table){
      table.addEventListener('click', async function(e){
        // Handle delete button click
        const deleteBtn = e.target.closest('.btn-delete-user');
        if (deleteBtn) {
          e.preventDefault();
          if (!isSuperAdmin) {
            await Swal.fire({
              icon: 'info',
              title: '0',
              text: '0',
              confirmButtonText: '0',
              confirmButtonColor: '#6c757d'
            });
            return;
          }
          
          // Rate limiting check
          if (!checkRateLimit('user_delete', 2000)) {
            await Swal.fire({
              icon: 'warning',
              title: '0',
              text: '0',
              timer: 2000,
              timerProgressBar: true,
              confirmButtonText: '0'
            });
            return;
          }
          
          const userID = deleteBtn.getAttribute('data-user-id');
          const nama = deleteBtn.getAttribute('data-nama') || '0';
          const stafID = deleteBtn.getAttribute('data-stafid') || '';
          const displayId = deleteBtn.getAttribute('data-displayid') || stafID;
          const sourceTableId = deleteBtn.closest('table')?.id || 'userDT';
          if (isProtectedStaffAccountClient(stafID)) {
            await Swal.fire({
              icon: 'info',
              title: '0',
              text: 'Akaun pengguna ini dilindungi oleh sistem dan tidak boleh dipadam.',
              confirmButtonText: '0',
              confirmButtonColor: '#0d6efd'
            });
            return;
          }
          if (isCurrentLoggedInUserTarget(userID, stafID)) {
            await Swal.fire({
              icon: 'info',
              title: '0',
              text: 'Anda tidak boleh memadam akaun yang sedang anda gunakan sekarang.',
              confirmButtonText: '0',
              confirmButtonColor: '#0d6efd'
            });
            return;
          }
          
          // Confirmation dialog
          const result = await Swal.fire({
            icon: 'warning',
            title: '0',
            html: `<p>0</p>
                   <p><strong>${nama}</strong> (${displayId})</p>
                   <p class="text-danger"><small>0</small></p>`,
            showCancelButton: true,
            confirmButtonText: '0',
            cancelButtonText: '0',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
          });
          
          if (!result.isConfirmed) return;
          
          trackEvent('user_delete', { userID, nama, stafID: displayId });
          
          // Disable button during request
          deleteBtn.disabled = true;
          const originalHTML = deleteBtn.innerHTML;
          deleteBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i>';
          
          try {
            const r = await fetchWithRetry('0', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
              },
              body: JSON.stringify({
                userID: userID,
                csrf_token: CSRF
              })
            });
            
            // Read response once
            let responseText = '';
            let j = null;
            
            try {
              responseText = await r.text();
              j = JSON.parse(responseText);
            } catch (e) {
              throw new Error(`0 (${r.status}).`);
            }
            
            if (!r.ok) {
              let errorMsg = '0';
              if (j && j.message) {
                errorMsg = j.message;
              } else {
            errorMsg = `0 ${r.status}: ${r.statusText || '0'}`;
              }
              throw new Error(errorMsg);
            }
            
            if (!j || j.error) {
              throw new Error((j && j.message) || '0');
            }
            
            trackEvent('user_delete_success', { userID });
            
            if (sourceTableId === 'userDT') {
              await reloadUserTable();
              setupTableControls();
              await refreshStafDropdown();
            } else {
              removeUserRowFromTable(sourceTableId, userID);
              const sourceScope = sourceTableId === 'userDTStudent' ? 'student' : 'public';
              setupScopedTableControls(
                sourceTableId,
                sourceScope,
                { addLabel: sourceScope === 'student' ? '0' : '0' }
              );
            }
            
            // Show success message
            await Swal.fire({
              icon: 'success',
              title: '0',
              text: (j.message || '0'),
              confirmButtonText: '0',
              confirmButtonColor: '#28a745',
              timer: 2000,
              timerProgressBar: true
            });
          } catch (e) {
            const errorMsg = sanitizeError(e);
            trackEvent('user_delete_error', { userID, error: errorMsg });
            
            await Swal.fire({
              icon: 'error',
              title: '0',
              text: errorMsg || '0',
              confirmButtonText: '0',
              confirmButtonColor: '#dc3545'
            });
          } finally {
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = originalHTML;
          }
          
          return;
        }
        
        // Handle edit button click
        const btn = e.target.closest('.btn-edit-group'); 
        if (!btn || !modal) return;
        if (!isSuperAdmin) {
          await Swal.fire({
            icon: 'info',
            title: '0',
            text: '0',
            confirmButtonText: '0',
            confirmButtonColor: '#6c757d'
          });
          return;
        }

        hideErr();
        const userID  = btn.getAttribute('data-user-id');
        const nama    = btn.getAttribute('data-nama') || '-';
        const stafid  = btn.getAttribute('data-stafid') || '-';
        const displayId = btn.getAttribute('data-displayid') || stafid;
        const nopekerja = btn.getAttribute('data-nopekerja') || '';
        if (isProtectedStaffAccountClient(stafid)) {
          await Swal.fire({
            icon: 'info',
            title: '0',
            text: 'Akaun pengguna ini dilindungi oleh sistem dan tidak boleh diubah aksesnya.',
            confirmButtonText: '0',
            confirmButtonColor: '#0d6efd'
          });
          return;
        }
        const avatarUrl = btn.getAttribute('data-avatar-url') || '';
        const jabatan = btn.getAttribute('data-jabatan') || '-';
        const gId     = btn.getAttribute('data-group-id') || '';
        const gKod    = btn.getAttribute('data-group-kod') || '';
        const scope   = (btn.getAttribute('data-scope') || 'staff').toLowerCase();
        const flag    = btn.getAttribute('data-flag') || '1';
        currentUserScope = scope || 'staff';
        configureEditModalForScope(currentUserScope);

        trackEvent('user_edit_group_open', { userID, currentGroupId: gId, currentGroup: gKod });

        document.getElementById('ug_userID').value = userID;
        document.getElementById('ug_scope').value = currentUserScope;
        document.getElementById('ug_userID').setAttribute('data-target-stafid', stafid);
        const $row = btn.closest('tr');
        const extraCount = parseInt($row?.getAttribute('data-extra-count') || '0', 10);
        const extraList = String($row?.getAttribute('data-extra-roles') || '').split(',').map(s=>s.trim()).filter(Boolean);
        setRoleButton(extraCount, extraList);
        document.getElementById('ug_nopekerja').value = nopekerja;
        currentPrimaryRoleName = (btn.getAttribute('data-group-name') || '').trim();
        
        // Store original values for comparison
        document.getElementById('ug_userID').setAttribute('data-original-group', gId);
        document.getElementById('ug_userID').setAttribute('data-original-flag', flag);
        
        const namaEl = document.getElementById('ug_nama');
        const jabatanEl = document.getElementById('ug_jabatan');
        const avatarEl = document.getElementById('ug_avatar');
        const flagEl = document.getElementById('ug_flag');
        const publicNameEl = document.getElementById('ug_publicName');
        const publicNicknameEl = document.getElementById('ug_publicNickname');
        const publicEmailEl = document.getElementById('ug_publicEmail');
        const publicPhoneEl = document.getElementById('ug_publicPhone');
        const publicUniversityEl = document.getElementById('ug_publicUniversity');
        const publicNoKpEl = document.getElementById('ug_publicNoKp');
        const publicPasswordEl = document.getElementById('ug_publicPassword');
        const publicPasswordConfirmEl = document.getElementById('ug_publicPasswordConfirm');

        if (namaEl) namaEl.textContent = `${nama} (${displayId})`;
        if (jabatanEl) jabatanEl.textContent = scope === 'public'
          ? (btn.getAttribute('data-email') || btn.getAttribute('data-loginid') || '-')
          : (jabatan || '-');
        if (flagEl) flagEl.value = flag;
        if (scope === 'public') {
          if (publicNameEl) publicNameEl.value = nama;
          if (publicNicknameEl) publicNicknameEl.value = btn.getAttribute('data-nickname') || '';
          if (publicEmailEl) publicEmailEl.value = btn.getAttribute('data-email') || btn.getAttribute('data-loginid') || '';
          if (publicPhoneEl) publicPhoneEl.value = btn.getAttribute('data-phone') || '';
          if (publicUniversityEl) publicUniversityEl.value = btn.getAttribute('data-university') || btn.getAttribute('data-jabatan') || '';
          if (publicNoKpEl) publicNoKpEl.value = btn.getAttribute('data-nokp') || '';
          if (publicPasswordEl) publicPasswordEl.value = '';
          if (publicPasswordConfirmEl) publicPasswordConfirmEl.value = '';
        }
        
        // Set avatar URL - guna URL dari User::getAvatarUrl() (PHP)
        if (avatarEl) {
          avatarEl.src = avatarUrl || '0';
        }

        await populateGroups(gId, currentUserScope);
        if (!currentPrimaryRoleName) {
          currentPrimaryRoleName = getPrimaryRoleNameFromSelect();
        }
        modal.show();
      });
    }

    // Open extra role modal
    document.getElementById('ug_addRoleBtn')?.addEventListener('click', async function(e){
      e.preventDefault();
      const userID = parseInt(document.getElementById('ug_userID').value || '0', 10);
      const currentStafId = document.getElementById('ug_userID')?.getAttribute('data-target-stafid') || '';
      if (isProtectedStaffAccountClient(currentStafId)) {
        showRoleErr('Akaun pengguna ini dilindungi oleh sistem dan tidak boleh diubah peranan tambahannya.');
        return;
      }
      if (!userID) {
        showErr('0');
        return;
      }
      if (!roleModal && roleModalEl && window.bootstrap && bootstrap.Modal) {
        roleModal = new bootstrap.Modal(roleModalEl);
      }
      hideRoleErr();
      document.getElementById('re_userID').value = String(userID);
      const primaryName = currentPrimaryRoleName || getPrimaryRoleNameFromSelect() || '0';
      const primEl = document.getElementById('re_primaryRole');
      if (primEl) primEl.textContent = primaryName;
      await loadExtraRoles(userID);
      restoreParentUserGroupModal = !!(modalEl && modalEl.classList.contains('show'));
      if (restoreParentUserGroupModal && modal) {
        modalEl.addEventListener('hidden.bs.modal', function handleParentHiddenForRoleModal() {
          modalEl.removeEventListener('hidden.bs.modal', handleParentHiddenForRoleModal);
          roleModal?.show();
        }, { once: true });
        modal.hide();
      } else {
        roleModal?.show();
      }
    });

    // Save extra roles
    document.getElementById('roleExtraSaveBtn')?.addEventListener('click', createRateLimitedHandler(async function(){
      hideRoleErr();
      const userID = parseInt(document.getElementById('re_userID').value || '0', 10);
      if (!userID) {
        showRoleErr('0');
        return;
      }
      const selected = Array.from(roleListEl?.querySelectorAll('input[type="checkbox"]:checked') || [])
        .map(el => parseInt(el.value || '0', 10))
        .filter(v => v > 0);

      const saveBtn = document.getElementById('roleExtraSaveBtn');
      const originalText = saveBtn.innerHTML;
      saveBtn.disabled = true;
      saveBtn.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i> 0';

      try {
        const r = await fetch('0', {
          method: 'POST',
          headers: {'Content-Type':'application/json','X-CSRF-Token': CSRF, 'Accept':'application/json'},
          body: JSON.stringify({ action: 'save', userID, roles: selected, scope: currentUserScope })
        });
        const j = await r.json();
        if (!r.ok || !j || j.error) throw new Error((j && j.message) || '0');
        // Update row + button with current selections
        const selectedNames = Array.from(roleListEl?.querySelectorAll('input[type="checkbox"]:checked') || [])
          .map(el => el.parentElement?.querySelector('.role-label')?.textContent?.trim() || '')
          .filter(Boolean);
        updateUserRow(userID, { extraRoles: selectedNames });
        setRoleButton(selectedNames.length, selectedNames);
        restoreParentAfterRoleAlert = restoreParentUserGroupModal;
        restoreParentUserGroupModal = false;
        roleModal?.hide();
        if (window.Swal) {
          await Swal.fire({
            icon: 'success',
            title: '0',
            text: j.message || '0',
            confirmButtonText: '0',
            confirmButtonColor: '#198754'
          });
        }
        if (restoreParentAfterRoleAlert && modal) {
          restoreParentAfterRoleAlert = false;
          modal.show();
        }
      } catch (e) {
        showRoleErr(e.message || '0');
      } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
      }
    }, 1000));

    if (roleModalEl) {
      roleModalEl.addEventListener('hidden.bs.modal', function() {
        if (restoreParentUserGroupModal && modalEl && modal) {
          restoreParentUserGroupModal = false;
          modal.show();
        }
        restoreParentUserGroupModal = false;
      });
    }

    // Helper function untuk validation dengan blink effect (modal edit)
    function validateFieldEdit(fieldElement, isValid) {
      if (!fieldElement) return;
      
      // Remove existing invalid class
      fieldElement.classList.remove('field-invalid');
      
      // If invalid, add blink effect
      if (!isValid) {
        fieldElement.classList.add('field-invalid');
        
        // Scroll to field if not visible
        setTimeout(() => {
          fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
        
        // Remove class after animation
        setTimeout(() => {
          fieldElement.classList.remove('field-invalid');
        }, 1500);
      }
    }
    
    document.getElementById('ug_saveBtn')?.addEventListener('click', createRateLimitedHandler(async function(){
      hideErr();
      
      // Remove all invalid classes first
      document.querySelectorAll('#userGroupModal .field-invalid').forEach(el => {
        el.classList.remove('field-invalid');
      });
      
      const userID   = parseInt(document.getElementById('ug_userID').value || '0', 10);
      const targetStafId = document.getElementById('ug_userID').getAttribute('data-target-stafid') || '';
      const groupID = document.getElementById('ug_groupKod').value || '';
      const flag     = parseInt(document.getElementById('ug_flag').value || '1', 10);
      const editScope = String(document.getElementById('ug_scope')?.value || currentUserScope || 'staff').toLowerCase();
      if (isProtectedStaffAccountClient(targetStafId)) {
        showErr('Akaun pengguna ini dilindungi oleh sistem dan tidak boleh diubah aksesnya.');
        return;
      }
      
      // Validation dengan blink effect
      let isValid = true;
      
      // Validate userID
      if (!userID) {
        showErr('0');
        return;
      }
      
      // Validate Group dengan validateGroupId function
      const groupSelect = document.getElementById('ug_groupKod');
      if (!groupID || groupID === '' || !validateGroupId(groupID)) {
        validateFieldEdit(groupSelect, false);
        isValid = false;
      } else {
        groupSelect.classList.remove('field-invalid');
      }
      
      if (!isValid) {
        showErr(editScope === 'public' ? '0' : '0');
        return; // Stop submission if validation fails
      }
      
      // Get original values
      const originalGroup = document.getElementById('ug_userID').getAttribute('data-original-group') || '';
      const originalFlag = parseInt(document.getElementById('ug_userID').getAttribute('data-original-flag') || '1', 10);
      
      // Check if anything changed
      const groupChanged = (String(groupID) !== String(originalGroup));
      const flagChanged = (flag !== originalFlag);

      // Disable button during request
      const saveBtn = document.getElementById('ug_saveBtn');
      const originalText = saveBtn.innerHTML;
      saveBtn.disabled = true;
      saveBtn.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i> 0';

      try{
        let url = '0';
        let requestBody = { userID };
        if (groupID) {
          requestBody.groupID = parseInt(groupID, 10);
        }
        requestBody.flag = flag;

        if (editScope === 'public') {
          const publicNameEl = document.getElementById('ug_publicName');
          const publicNicknameEl = document.getElementById('ug_publicNickname');
          const publicEmailEl = document.getElementById('ug_publicEmail');
          const publicPhoneEl = document.getElementById('ug_publicPhone');
          const publicUniversityEl = document.getElementById('ug_publicUniversity');
          const publicNoKpEl = document.getElementById('ug_publicNoKp');
          const publicPasswordEl = document.getElementById('ug_publicPassword');
          const publicPasswordConfirmEl = document.getElementById('ug_publicPasswordConfirm');

          const publicName = String(publicNameEl?.value || '').trim();
          const publicNickname = String(publicNicknameEl?.value || '').trim();
          const publicEmail = String(publicEmailEl?.value || '').trim().toLowerCase();
          const publicPhone = String(publicPhoneEl?.value || '').trim();
          const publicUniversity = String(publicUniversityEl?.value || '').trim();
          const publicNoKp = String(publicNoKpEl?.value || '').trim();
          const publicPassword = String(publicPasswordEl?.value || '');
          const publicPasswordConfirm = String(publicPasswordConfirmEl?.value || '');

          let publicValid = true;
          if (!publicName) {
            validateFieldEdit(publicNameEl, false);
            publicValid = false;
          }
          if (!validateEmailAddress(publicEmail)) {
            validateFieldEdit(publicEmailEl, false);
            publicValid = false;
          }
          if (publicPassword !== '' && publicPassword.length < 6) {
            validateFieldEdit(publicPasswordEl, false);
            publicValid = false;
          }
          if (publicPassword !== '' || publicPasswordConfirm !== '') {
            if (publicPasswordConfirm !== publicPassword) {
              validateFieldEdit(publicPasswordConfirmEl, false);
              publicValid = false;
            }
          }
          if (!publicValid) {
            showErr('0');
            return;
          }

          url = '0';
          requestBody = {
            userID,
            groupID: groupID ? parseInt(groupID, 10) : 0,
            flag,
            name: publicName,
            nickname: publicNickname,
            email: publicEmail,
            phone: publicPhone,
            university: publicUniversity,
            nokp: publicNoKp,
            password: publicPassword,
            password_confirm: publicPasswordConfirm
          };
        } else {
          const resetPasswordEl = document.getElementById('ug_resetPassword');
          const resetPasswordConfirmEl = document.getElementById('ug_resetPasswordConfirm');
          const resetPassword = String(resetPasswordEl?.value || '');
          const resetPasswordConfirm = String(resetPasswordConfirmEl?.value || '');

          let passwordValid = true;
          if (resetPassword !== '' && resetPassword.length < 6) {
            validateFieldEdit(resetPasswordEl, false);
            passwordValid = false;
          }
          if (resetPassword !== '' || resetPasswordConfirm !== '') {
            if (resetPasswordConfirm !== resetPassword) {
              validateFieldEdit(resetPasswordConfirmEl, false);
              passwordValid = false;
            }
          }
          if (!passwordValid) {
            showErr('0');
            return;
          }

          requestBody.password = resetPassword;
          requestBody.password_confirm = resetPasswordConfirm;
        }

        trackEvent(editScope === 'public' ? 'user_edit_public_save' : 'user_edit_group_save', { userID, groupID: parseInt(groupID, 10), flag });
        
        const r = await fetchWithRetry(url, {
          method: 'POST',
          headers: {'Content-Type':'application/json','X-CSRF-Token': CSRF, 'Accept':'application/json'},
          body: JSON.stringify(requestBody)
        });
        
        // Check if response is OK
        if (!r.ok) {
          let errorMsg = '0';
          try {
            const errorData = await r.json();
            if (errorData && errorData.message) {
              errorMsg = errorData.message;
            }
          } catch (e) {
            // If JSON parsing fails, use status text
            errorMsg = `0 ${r.status}: ${r.statusText || '0'}`;
          }
          throw new Error(errorMsg);
        }
        
        // Parse JSON response
        let j;
        try {
          j = await r.json();
        } catch (e) {
          throw new Error('0');
        }
        
        if (!j || j.error){
          throw new Error((j && j.message) || (editScope === 'public' ? '0' : '0'));
        }

        trackEvent(editScope === 'public' ? 'user_edit_public_success' : 'user_edit_group_success', { userID, groupID: parseInt(groupID, 10), flag });

        // Close modal first
        modal?.hide();
        
        // Try to update row in-place first (optimized)
        try {
          // Extract groupName from response - check both j.groupName and j.group.nama
          const groupIdResp = j.group && (j.group.id || j.group.f_groupID) ? (j.group.id || j.group.f_groupID) : parseInt(groupID, 10);
          const groupKodResp = j.group && (j.group.kod || j.group.f_groupKod) ? (j.group.kod || j.group.f_groupKod) : '';
          const groupName = j.groupName || (j.group && j.group.nama) || groupKodResp || groupID;
          const rowUpdateData = {
            groupID: groupIdResp,
            groupKod: groupKodResp,
            groupName: groupName,
            flag: flag
          };
          if (editScope === 'public') {
            rowUpdateData.name = j.user?.name || requestBody.name || '';
            rowUpdateData.loginID = j.user?.loginID || requestBody.email || '';
            rowUpdateData.nickname = j.user?.nickname || requestBody.nickname || '';
            rowUpdateData.email = j.user?.email || requestBody.email || '';
            rowUpdateData.phone = j.user?.phone || requestBody.phone || '';
            rowUpdateData.university = j.user?.university || requestBody.university || '';
            rowUpdateData.nokp = j.user?.nokp || requestBody.nokp || '';
            rowUpdateData.jabatan = j.user?.university || requestBody.university || '';
          }
          updateUserRow(userID, rowUpdateData);
        } catch (e) {
          // Fallback to full reload if in-place update fails
          await reloadUserTable(userID);
        }
        
        // Show success message with SweetAlert
        if (window.Swal) {
          await Swal.fire({
            icon: 'success',
            title: '0',
            text: (j.message || (editScope === 'public' ? '0' : '0')),
            confirmButtonText: '0',
            confirmButtonColor: '#198754',
            timer: 2000,
            timerProgressBar: true
          });
        }
      }catch(e){
        // Better error handling - sanitize error message
        const errorMsg = sanitizeError(e);
        trackEvent(editScope === 'public' ? 'user_edit_public_error' : 'user_edit_group_error', { userID, error: errorMsg });
        showErr(errorMsg);
      } finally {
        // Re-enable button
        if (saveBtn) {
          saveBtn.disabled = false;
          saveBtn.innerHTML = originalText;
        }
      }
    }, 1000));

    // ===== Modal Tambah Pengguna =====
    const addUserModalEl = document.getElementById('addUserModal');
    const auStafSelect = document.getElementById('au_stafSelect');
    const auErrorEl = document.getElementById('au_error');
    let currentStudentSelection = null;

    function resetAddModalInfoCard() {
      const jabatanEl = document.getElementById('au_jabatan');
      const jawatanEl = document.getElementById('au_jawatan');
      const extraInfo1El = document.getElementById('au_extraInfo1');
      const extraInfo2El = document.getElementById('au_extraInfo2');
      const extraInfo1Wrap = document.getElementById('au_extraInfo1Wrap');
      const extraInfo2Wrap = document.getElementById('au_extraInfo2Wrap');

      if (jabatanEl) {
        jabatanEl.textContent = '0';
        jabatanEl.className = 'info-value';
      }
      if (jawatanEl) {
        jawatanEl.textContent = '0';
        jawatanEl.className = 'info-value';
      }
      if (extraInfo1El) {
        extraInfo1El.textContent = '0';
        extraInfo1El.className = 'info-value';
      }
      if (extraInfo2El) {
        extraInfo2El.textContent = '0';
        extraInfo2El.className = 'info-value';
      }
      if (extraInfo1Wrap) extraInfo1Wrap.style.display = 'none';
      if (extraInfo2Wrap) extraInfo2Wrap.style.display = 'none';
    }

    function resetPublicFormFields() {
      const ids = [
        'au_publicName',
        'au_publicNickname',
        'au_publicEmail',
        'au_publicPhone',
        'au_publicUniversity',
        'au_publicNoKp',
        'au_publicPassword',
        'au_publicPasswordConfirm'
      ];
      ids.forEach(function(id) {
        const el = document.getElementById(id);
        if (el) {
          el.value = '';
          el.classList.remove('field-invalid');
        }
      });
    }

    function configureAddModalForScope(scope) {
      const normalized = String(scope || 'staff').trim().toLowerCase() || 'staff';
      const titleEl = document.getElementById('addUserModalTitle');
      const saveBtn = document.getElementById('au_saveBtn');
      const sectionTitleEl = document.getElementById('au_sectionTitle');
      const selectLabelEl = document.getElementById('au_selectLabel');
      const primaryInfoLabelEl = document.getElementById('au_primaryInfoLabel');
      const secondaryInfoLabelEl = document.getElementById('au_secondaryInfoLabel');
      const extraInfo1LabelEl = document.getElementById('au_extraInfo1Label');
      const extraInfo2LabelEl = document.getElementById('au_extraInfo2Label');
      const extraInfo1Wrap = document.getElementById('au_extraInfo1Wrap');
      const extraInfo2Wrap = document.getElementById('au_extraInfo2Wrap');
      const staffSelectWrap = document.getElementById('au_staffSelectWrap');
      const infoCard = document.getElementById('au_infoCard');
      const publicFormSection = document.getElementById('au_publicFormSection');

      if (staffSelectWrap) staffSelectWrap.classList.remove('d-none');
      if (infoCard) infoCard.classList.remove('d-none');
      if (publicFormSection) publicFormSection.classList.add('d-none');

      if (normalized === 'student') {
        if (titleEl) titleEl.innerHTML = '<i class="ri-user-add-line me-2"></i> 0';
        if (saveBtn) saveBtn.innerHTML = '<i class="ri-user-add-line me-1"></i> 0';
        if (sectionTitleEl) sectionTitleEl.innerHTML = '<i class="ri-user-star-line me-1"></i> 0';
        if (selectLabelEl) selectLabelEl.innerHTML = '<i class="ri-graduation-cap-line"></i> 0 <span class="text-danger">*</span>';
        if (primaryInfoLabelEl) primaryInfoLabelEl.textContent = '0';
        if (secondaryInfoLabelEl) secondaryInfoLabelEl.textContent = '0';
        if (extraInfo1LabelEl) extraInfo1LabelEl.textContent = '0';
        if (extraInfo2LabelEl) extraInfo2LabelEl.textContent = '0';
        if (extraInfo1Wrap) extraInfo1Wrap.style.display = '';
        if (extraInfo2Wrap) extraInfo2Wrap.style.display = '';
        if (auStafSelect) {
          auStafSelect.dataset.placeholder = '0';
          auStafSelect.setAttribute('data-placeholder', '0');
        }
        return;
      }

      if (normalized === 'public') {
        if (titleEl) titleEl.innerHTML = '<i class="ri-user-add-line me-2"></i> 0';
        if (saveBtn) saveBtn.innerHTML = '<i class="ri-user-add-line me-1"></i> 0';
        if (sectionTitleEl) sectionTitleEl.innerHTML = '<i class="ri-user-star-line me-1"></i> 0';
        if (staffSelectWrap) staffSelectWrap.classList.add('d-none');
        if (infoCard) infoCard.classList.add('d-none');
        if (publicFormSection) publicFormSection.classList.remove('d-none');
        resetPublicFormFields();
        return;
      }

      if (titleEl) titleEl.innerHTML = '<i class="ri-user-add-line me-2"></i> 0';
      if (saveBtn) saveBtn.innerHTML = '<i class="ri-user-add-line me-1"></i> 0';
      if (sectionTitleEl) sectionTitleEl.innerHTML = '<i class="ri-user-line me-1"></i> 0';
      if (selectLabelEl) selectLabelEl.innerHTML = '<i class="ri-user-line"></i> 0 <span class="text-danger">*</span>';
      if (primaryInfoLabelEl) primaryInfoLabelEl.textContent = '0';
      if (secondaryInfoLabelEl) secondaryInfoLabelEl.textContent = '0';
      if (extraInfo1Wrap) extraInfo1Wrap.style.display = 'none';
      if (extraInfo2Wrap) extraInfo2Wrap.style.display = 'none';
      if (auStafSelect) {
        auStafSelect.dataset.placeholder = '0';
        auStafSelect.setAttribute('data-placeholder', '0');
      }
    }

    function updateAddModalInfoFromSelection(scope, payload = null) {
      const normalized = String(scope || 'staff').trim().toLowerCase() || 'staff';
      const jabatanEl = document.getElementById('au_jabatan');
      const jawatanEl = document.getElementById('au_jawatan');
      const extraInfo1El = document.getElementById('au_extraInfo1');
      const extraInfo2El = document.getElementById('au_extraInfo2');

      if (normalized === 'student') {
        if (jabatanEl) jabatanEl.textContent = payload?.fakulti || '0';
        if (jawatanEl) jawatanEl.textContent = payload?.program || '0';
        if (extraInfo1El) extraInfo1El.textContent = payload?.tahap_pengajian || '0';
        if (extraInfo2El) extraInfo2El.textContent = payload?.statuskategori || '0';
        return;
      }

      if (jabatanEl) jabatanEl.textContent = payload?.jabatan || '0';
      if (jawatanEl) jawatanEl.textContent = payload?.jawatan || '0';
    }

    async function prepareAddUserModalForScope(scope) {
      const normalized = String(scope || 'staff').trim().toLowerCase() || 'staff';
      currentAddScope = normalized;
      const groupSelect = document.getElementById('au_groupKod');
      currentStudentSelection = null;
      configureAddModalForScope(normalized);
      resetAddModalInfoCard();
      resetPublicFormFields();
      await populateSelectGroupsForScope(groupSelect, normalized);
    }
    
    function showAuErr(msg) {
      if (!auErrorEl) return;
      auErrorEl.textContent = msg || '0';
      auErrorEl.classList.remove('d-none');
    }
    
    function hideAuErr() {
      if (!auErrorEl) return;
      auErrorEl.classList.add('d-none');
    }

    async function openAddUserModalForScope(scope) {
      const normalized = String(scope || 'staff').trim().toLowerCase() || 'staff';
      currentAddScope = normalized;
      configureAddModalForScope(normalized);
      resetAddModalInfoCard();
      resetPublicFormFields();

      const modalTarget = document.getElementById('addUserModal');
      if (!modalTarget || !window.bootstrap || !bootstrap.Modal) {
        return;
      }

      const modal = bootstrap.Modal.getOrCreateInstance(modalTarget);
      modal.show();

      window.setTimeout(function() {
        prepareAddUserModalForScope(normalized).catch(function() {
          hideAuErr();
        });
      }, 0);
    }

    window.userListOpenAdd = function(scope) {
      return openAddUserModalForScope(scope);
    };
    window.openAddUserModalForScope = openAddUserModalForScope;
    
    // Handle focus + reset when modal hides
    if (addUserModalEl) {
      // Before hiding: ensure no element inside modal keeps focus (fixes aria-hidden warning)
      addUserModalEl.addEventListener('hide.bs.modal', function() {
        try {
          const active = document.activeElement;
          if (active && addUserModalEl.contains(active)) {
            // Blur focused element inside modal so it isn't hidden from AT
            active.blur();
          }
        } catch (e) { /* ignore */ }

        try {
          // Close Select2 dropdown if open to prevent focus retention
          if (window.jQuery && auStafSelect && jQuery(auStafSelect).data('select2')) {
            jQuery(auStafSelect).select2('close');
          }
        } catch (e) { /* ignore */ }

        try {
          // Return focus to the Add User button or a sensible fallback
          const trigger =
            document.getElementById('btnAddUser') ||
            document.getElementById('btnAddUserStudent') ||
            document.getElementById('btnAddUserPublic') ||
            document.querySelector('[data-bs-target="#addUserModal"]');
          if (trigger) trigger.focus(); else document.body.focus();
        } catch (e) { /* ignore */ }
      });

      // Reset form when modal is fully hidden
      addUserModalEl.addEventListener('hidden.bs.modal', function() {
        currentAddScope = 'staff';
        currentStudentSelection = null;
        if (auStafSelect) {
          if (window.jQuery && jQuery(auStafSelect).data('select2')) {
            jQuery(auStafSelect).val(null).trigger('change');
          } else {
            auStafSelect.value = '';
          }
        }
        document.getElementById('au_groupKod').value = '';
        document.getElementById('au_flag').value = '1';
        configureAddModalForScope('staff');
        resetAddModalInfoCard();
        resetPublicFormFields();
        hideAuErr();
      });
    }
    
    // Initialize Select2 untuk dropdown staf (simple, tanpa retry loop)
    function initSelect2ForModal() {
      jQuery(function($) {
        if (typeof $.fn.select2 === 'undefined') {
          return;
        }

        // Setup Select2 dengan lazy loading staf list bila modal dibuka
        if (addUserModalEl && auStafSelect) {
          addUserModalEl.addEventListener('shown.bs.modal', async function() {
            const $sel = $(auStafSelect);
            const placeholderText = currentAddScope === 'student'
              ? '0'
              : ((currentAddScope === 'public')
                ? '0'
                : '0');

            if (currentAddScope === 'public') {
              if ($sel.data('select2')) {
                $sel.select2('destroy');
              }
              if (auStafSelect) {
                auStafSelect.innerHTML = '<option value=""></option>';
                auStafSelect.value = '';
              }
              return;
            }

            // Destroy existing instance jika ada
            if ($sel.data('select2')) {
              $sel.select2('destroy');
            }

            currentStudentSelection = null;

            // Lazy load options ikut scope semasa
            // Helper: safe innerHTML setter (prefer DOMPurify when available)
            function setSafeInnerHTML(el, html) {
              if (!el) return;
              if (!html) { el.innerHTML = ''; return; }
              if (window.DOMPurify && typeof DOMPurify.sanitize === 'function') {
                el.innerHTML = DOMPurify.sanitize(html);
                return;
              }
              try {
                var doc = new DOMParser().parseFromString('<div>' + html + '</div>', 'text/html');
                doc.querySelectorAll('script').forEach(function(s){ s.remove(); });
                doc.querySelectorAll('*').forEach(function(n){
                  Array.from(n.attributes).forEach(function(a){
                    if (/^on/i.test(a.name)) n.removeAttribute(a.name);
                    if ((a.name === 'src' || a.name === 'href') && /^javascript:/i.test(a.value)) n.removeAttribute(a.name);
                  });
                });
                el.innerHTML = doc.body.firstChild ? doc.body.firstChild.innerHTML : '';
              } catch (e) {
                el.innerHTML = html;
              }
            }

            function ensureStaffPlaceholder() {
              if (!auStafSelect) return;
              const first = auStafSelect.options[0];
              if (!first || first.value !== '') {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = placeholderText;
                auStafSelect.insertBefore(opt, auStafSelect.firstChild);
              }
            }

            if (currentAddScope === 'student') {
              auStafSelect.innerHTML = '<option value=""></option>';
              $sel.select2({
                width: '100%',
                allowClear: true,
                placeholder: placeholderText,
                minimumInputLength: 2,
                dropdownParent: $(addUserModalEl),
                ajax: {
                  url: '0',
                  type: 'POST',
                  dataType: 'json',
                  delay: 250,
                  data: function(params) {
                    return {
                      q: params.term || '',
                      page: params.page || 1,
                      csrf_token: CSRF
                    };
                  },
                  processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                      results: Array.isArray(data?.results) ? data.results : [],
                      pagination: data?.pagination || { more: false }
                    };
                  }
                }
              });
              $sel.val(null).trigger('change');
              return;
            }

            try {
              auStafSelect.innerHTML = '<option value="">0...</option>';

              const r = await fetch('0', {
                headers: { 'Accept': 'application/json' }
              });

              if (r.ok) {
                const text = await r.text();
                let j = null;
                try {
                  j = JSON.parse(text);
                } catch (pe) {
                  auStafSelect.innerHTML = '<option value="">0</option>';
                }

                if (j) {
                  if (!j.error && Array.isArray(j.options) && j.options.length > 0) {
                    auStafSelect.innerHTML = '';
                    ensureStaffPlaceholder();
                    j.options.forEach(opt => {
                      try {
                        const option = document.createElement('option');
                        option.value = opt.value || '';
                        option.setAttribute('data-idpekerja', opt.idpekerja || '');
                        option.setAttribute('data-nama', opt.nama || '');
                        option.setAttribute('data-jawatan', opt.jawatan || '');
                        option.setAttribute('data-jabatan', opt.jabatan || '');
                        if (opt.disabled) option.disabled = true;
                        option.textContent = opt.display || opt.nama || opt.value || '';
                        auStafSelect.appendChild(option);
                      } catch (e) { /* ignore malformed option */ }
                    });
                  } else if (!j.error && j.html) {
                    setSafeInnerHTML(auStafSelect, j.html || '');
                    ensureStaffPlaceholder();
                  } else {
                    auStafSelect.innerHTML = '<option value="">0</option>';
                  }
                }
              } else {
                auStafSelect.innerHTML = '<option value="">0</option>';
              }
            } catch (e) {
              auStafSelect.innerHTML = '<option value="">0</option>';
            }

            if (auStafSelect) {
              auStafSelect.value = '';
            }

            $sel.select2({
              width: '100%',
              allowClear: true,
              placeholder: placeholderText,
              dropdownParent: $(addUserModalEl)
            });
            $sel.val('').trigger('change');
          });

          $(auStafSelect).on('select2:select', function(e) {
            if (currentAddScope !== 'student') {
              return;
            }
            currentStudentSelection = e?.params?.data || null;
            updateAddModalInfoFromSelection('student', currentStudentSelection);
          });

          // Auto isi info bila pilih rekod
          $(auStafSelect).on('change', function() {
            if (currentAddScope === 'student') {
              if (!this.value) {
                currentStudentSelection = null;
                resetAddModalInfoCard();
                configureAddModalForScope('student');
                return;
              }

              if (currentStudentSelection && String(currentStudentSelection.id || '') === String(this.value || '')) {
                updateAddModalInfoFromSelection('student', currentStudentSelection);
              }
              return;
            }

            const opt = this.selectedOptions && this.selectedOptions[0]
              ? this.selectedOptions[0]
              : null;
            const jabatan = opt ? (opt.getAttribute('data-jabatan') || '') : '';
            const jawatan = opt ? (opt.getAttribute('data-jawatan') || '') : '';

            const jabatanEl = document.getElementById('au_jabatan');
            const jawatanEl = document.getElementById('au_jawatan');
            const auInfoCard = document.getElementById('au_infoCard');

            if (jabatanEl) {
              jabatanEl.textContent = jabatan || '0';
            }
            if (jawatanEl) {
              jawatanEl.textContent = jawatan || '0';
            }

            // Pastikan info card sentiasa visible
            if (auInfoCard) {
              auInfoCard.style.display = 'block';
            }
          });
        }
      });
    }
    
    // Function untuk refresh dropdown staf selepas delete/tambah user
    async function refreshStafDropdown() {
      const auStafSelect = document.getElementById('au_stafSelect');
      if (!auStafSelect) return;
      const placeholderText = auStafSelect.getAttribute('data-placeholder') || '0';

      function ensureStaffPlaceholder() {
        const first = auStafSelect.options[0];
        if (!first || first.value !== '') {
          const opt = document.createElement('option');
          opt.value = '';
          opt.textContent = placeholderText;
          auStafSelect.insertBefore(opt, auStafSelect.firstChild);
        }
      }
      
      try {
        // Fetch staf list terkini dari server dengan retry
        const r = await fetchWithRetry('0', {
          headers: { 'Accept': 'application/json' }
        });

        if (!r.ok) return;

        const text = await r.text();
        let j = null;
        try {
          j = JSON.parse(text);
        } catch (pe) {
          return; // silently ignore malformed response
        }
        if (j && j.error) return;

        // Destroy Select2 jika sudah initialized
        const $sel = jQuery(auStafSelect);
        if ($sel.data('select2')) {
          $sel.select2('destroy');
        }

        // Populate options prefer structured data
        if (Array.isArray(j.options) && j.options.length > 0) {
          auStafSelect.innerHTML = '';
          ensureStaffPlaceholder();
          j.options.forEach(opt => {
            try {
              const option = document.createElement('option');
              option.value = opt.value || '';
              option.setAttribute('data-idpekerja', opt.idpekerja || '');
              option.setAttribute('data-nama', opt.nama || '');
              option.setAttribute('data-jawatan', opt.jawatan || '');
              option.setAttribute('data-jabatan', opt.jabatan || '');
              if (opt.disabled) option.disabled = true;
              option.textContent = opt.display || opt.nama || opt.value || '';
              auStafSelect.appendChild(option);
            } catch (e) { /* ignore malformed option */ }
          });
        } else if (j.html) {
          auStafSelect.innerHTML = j.html || '';
          ensureStaffPlaceholder();
        } else {
          return;
        }
        auStafSelect.value = '';
        
        // Re-init Select2 jika modal sedang dibuka
        const addUserModalEl = document.getElementById('addUserModal');
        if (addUserModalEl && bootstrap.Modal.getInstance(addUserModalEl)?.isShown) {
          $sel.select2({
            width: '100%',
            allowClear: true,
            placeholder: placeholderText,
            dropdownParent: jQuery(addUserModalEl)
          });
          $sel.val('').trigger('change');
        }
      } catch (e) {
        // Silently ignore refresh errors to avoid noisy console in production
      }
    }
    
    // Start initialization - wait for DOM ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initSelect2ForModal);
    } else {
      // DOM already ready, start immediately
      initSelect2ForModal();
    }

    document.addEventListener('click', async function(e) {
      const addBtn = e.target.closest('#btnAddUser, #btnAddUserStudent, #btnAddUserPublic');
      if (!addBtn) return;
      if (addBtn.dataset.modalBound === '1') return;
      e.preventDefault();
      const scope = addBtn.id === 'btnAddUserStudent'
        ? 'student'
        : (addBtn.id === 'btnAddUserPublic' ? 'public' : 'staff');
      if (window.userListOpenAdd) {
        await window.userListOpenAdd(scope);
      }
    });
    
    // Helper function untuk validation dengan blink effect
    function validateField(fieldElement, isValid) {
      if (!fieldElement) return;
      
      // Remove existing invalid class
      fieldElement.classList.remove('field-invalid');
      
      // If invalid, add blink effect
      if (!isValid) {
        fieldElement.classList.add('field-invalid');
        
        // Scroll to field if not visible
        setTimeout(() => {
          fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
        
        // Remove class after animation
        setTimeout(() => {
          fieldElement.classList.remove('field-invalid');
        }, 1500);
      }
    }
    
    // Save button handler untuk Add User
    document.getElementById('au_saveBtn')?.addEventListener('click', createRateLimitedHandler(async function() {
      hideAuErr();
      
      // Remove all invalid classes first
      document.querySelectorAll('#addUserModal .field-invalid').forEach(el => {
        el.classList.remove('field-invalid');
      });
      
      const selectedIdentifier = auStafSelect ? auStafSelect.value : '';
      const groupID = document.getElementById('au_groupKod').value || '';
      const flag = parseInt(document.getElementById('au_flag').value || '1', 10);
      const publicNameEl = document.getElementById('au_publicName');
      const publicNicknameEl = document.getElementById('au_publicNickname');
      const publicEmailEl = document.getElementById('au_publicEmail');
      const publicPhoneEl = document.getElementById('au_publicPhone');
      const publicUniversityEl = document.getElementById('au_publicUniversity');
      const publicNoKpEl = document.getElementById('au_publicNoKp');
      const publicPasswordEl = document.getElementById('au_publicPassword');
      const publicPasswordConfirmEl = document.getElementById('au_publicPasswordConfirm');
      
      // Get idpekerja from selected option
      let idpekerja = '';
      let selectedOption = null;
      if (auStafSelect && auStafSelect.selectedOptions && auStafSelect.selectedOptions[0]) {
        selectedOption = auStafSelect.selectedOptions[0];
        idpekerja = selectedOption.getAttribute('data-idpekerja') || '';
      }
      
      // Validation dengan blink effect
      let isValid = true;
      
      let publicPayload = null;
      if (currentAddScope === 'public') {
        const publicName = String(publicNameEl?.value || '').trim();
        const publicNickname = String(publicNicknameEl?.value || '').trim();
        const publicEmail = String(publicEmailEl?.value || '').trim().toLowerCase();
        const publicPhone = String(publicPhoneEl?.value || '').trim();
        const publicUniversity = String(publicUniversityEl?.value || '').trim();
        const publicNoKp = String(publicNoKpEl?.value || '').trim();
        const publicPassword = String(publicPasswordEl?.value || '');
        const publicPasswordConfirm = String(publicPasswordConfirmEl?.value || '');

        if (!publicName) {
          validateField(publicNameEl, false);
          isValid = false;
        }
        if (!validateEmailAddress(publicEmail)) {
          validateField(publicEmailEl, false);
          isValid = false;
        }
        if (!publicPassword || publicPassword.length < 6) {
          validateField(publicPasswordEl, false);
          isValid = false;
        }
        if (!publicPasswordConfirm || publicPasswordConfirm !== publicPassword) {
          validateField(publicPasswordConfirmEl, false);
          isValid = false;
        }

        publicPayload = {
          name: publicName,
          nickname: publicNickname,
          email: publicEmail,
          phone: publicPhone,
          university: publicUniversity,
          nokp: publicNoKp,
          password: publicPassword,
          password_confirm: publicPasswordConfirm
        };
      } else {
        const identifierIsValid = currentAddScope === 'student'
          ? !!selectedIdentifier
          : (!!selectedIdentifier && validateStafID(selectedIdentifier));
        if (!identifierIsValid) {
          const $stafSelect2 = jQuery(auStafSelect).data('select2');
          if ($stafSelect2) {
            const $container = jQuery(auStafSelect).next('.select2-container');
            if ($container.length) {
              validateField($container[0], false);
            }
          } else {
            validateField(auStafSelect, false);
          }
          isValid = false;
        } else {
          const $stafSelect2 = jQuery(auStafSelect).data('select2');
          if ($stafSelect2) {
            const $container = jQuery(auStafSelect).next('.select2-container');
            if ($container.length) {
              $container[0].classList.remove('field-invalid');
            }
          } else {
            auStafSelect.classList.remove('field-invalid');
          }
        }
        
        if ((selectedOption && selectedOption.disabled) || (currentAddScope === 'student' && currentStudentSelection && currentStudentSelection.disabled)) {
          const $stafSelect2 = jQuery(auStafSelect).data('select2');
          if ($stafSelect2) {
            const $container = jQuery(auStafSelect).next('.select2-container');
            if ($container.length) {
              validateField($container[0], false);
            }
          } else {
            validateField(auStafSelect, false);
          }
          isValid = false;
        }
      }
      
      // Validate Group dengan validateGroupId function
      const groupSelect = document.getElementById('au_groupKod');
      if (!groupID || groupID === '' || !validateGroupId(groupID)) {
        validateField(groupSelect, false);
        isValid = false;
      } else {
        groupSelect.classList.remove('field-invalid');
      }
      
      if (!isValid) {
        if (currentAddScope === 'public') {
          showAuErr('Sila lengkapkan semua maklumat wajib dan pastikan emel serta kata laluan adalah sah.');
        }
        return; // Stop submission if validation fails
      }
      
      const saveBtn = this;
      const originalText = saveBtn.innerHTML;
      saveBtn.disabled = true;
      saveBtn.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i> 0';
      
      try {
        const isStudentScope = currentAddScope === 'student';
        const isPublicScope = currentAddScope === 'public';
        const url = isStudentScope
          ? '0'
          : (isPublicScope ? '0' : '0');
        const requestBody = isStudentScope
          ? {
              scope: currentAddScope,
              matrik: selectedIdentifier || '',
              groupID: parseInt(groupID, 10),
              flag: flag,
              csrf_token: CSRF
            }
          : (isPublicScope
            ? {
                scope: currentAddScope,
                groupID: parseInt(groupID, 10),
                flag: flag,
                csrf_token: CSRF,
                ...publicPayload
              }
            : {
              scope: currentAddScope,
              nopekerja: selectedIdentifier || '',
              idpekerja: idpekerja,
              groupID: parseInt(groupID, 10),
              flag: flag,
              csrf_token: CSRF
            });

        trackEvent('user_add', { scope: currentAddScope, identifier: isPublicScope ? (publicPayload?.email || '') : selectedIdentifier, groupID: parseInt(groupID, 10), flag });
        
        const r = await fetchWithRetry(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(requestBody)
        });
        
        // Read response once
        let responseText = '';
        let j = null;
        
        try {
          responseText = await r.text();
          j = JSON.parse(responseText);
        } catch (e) {
          throw new Error(`0 (${r.status}).`);
        }
        
        if (!r.ok) {
          let errorMsg = '0';
          if (j && j.message) {
            errorMsg = j.message;
          } else {
            errorMsg = `0 ${r.status}: ${r.statusText || '0'}`;
          }
          throw new Error(errorMsg);
        }
        
        if (!j || j.error) {
          throw new Error((j && j.message) || '0');
        }
        
        trackEvent('user_add_success', { scope: currentAddScope, userID: j.userID, identifier: isPublicScope ? (publicPayload?.email || '') : selectedIdentifier, groupID: parseInt(groupID, 10) });
        
        // Close modal
        const addUserModal = bootstrap.Modal.getInstance(addUserModalEl);
        if (addUserModal) {
          addUserModal.hide();
        }
        
        if (isStudentScope) {
          try {
            sessionStorage.setItem('userListActiveTab', '#tab-student-access');
          } catch (e) { /* ignore */ }
        } else if (isPublicScope) {
          try {
            sessionStorage.setItem('userListActiveTab', '#tab-public-access');
          } catch (e) { /* ignore */ }
        } else {
          await reloadUserTable(j.userID || null);
          await refreshStafDropdown();
        }
        
        // Show success message
        await Swal.fire({
          icon: 'success',
          title: '0',
          text: (j.message || (isStudentScope ? '0' : '0')),
          confirmButtonText: '0',
          confirmButtonColor: '#28a745',
          timer: 2000,
          timerProgressBar: true
        });
        if (isStudentScope || isPublicScope) {
          window.location.reload();
        }
      } catch (e) {
        const errorMsg = sanitizeError(e);
        trackEvent('user_add_error', { scope: currentAddScope, identifier: currentAddScope === 'public' ? (publicPayload?.email || '') : selectedIdentifier, error: errorMsg });
        showAuErr(errorMsg || '0');
      } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
      }
    }, 1000));

  });
})();
