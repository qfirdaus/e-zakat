/**
 * Menu Access Management untuk kumpulan-pengguna.php
 * Handle modal akses menu dengan DataTable dan editor
 */

const MenuAccess = {
  // DOM elements
  modalEl: null,
  subEl: null,
  loadEl: null,
  errEl: null,
  cntEl: null,
  editModalEl: null,
  editErrorEl: null,
  
  // Translations
  T: null,
  restoreParentMenuModal: false,
  pendingParentRestoreAfterSave: false,

  formatText(template, replacements = {}) {
    return String(template || '').replace(/\{(\w+)\}/g, (_, key) => String(replacements[key] ?? ''));
  },

  cleanupModalArtifacts() {
    try {
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('padding-right');
      document.body.style.removeProperty('overflow');
      document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    } catch (e) { /* silent */ }
  },

  attachMultiSelectToggle(selectEl) {
    if (!selectEl || selectEl.dataset.multiToggleBound === '1') return;
    selectEl.dataset.multiToggleBound = '1';
    selectEl.addEventListener('mousedown', function(e) {
      const opt = e.target && e.target.tagName === 'OPTION' ? e.target : null;
      if (!opt) return;
      e.preventDefault();
      opt.selected = !opt.selected;
      // Fire change so dependent menu list refreshes
      selectEl.dispatchEvent(new Event('change', { bubbles: true }));
    });
  },
  
  // Helper untuk query edit modal
  $ME(sel) {
    return this.editModalEl ? this.editModalEl.querySelector(sel) : null;
  },
  
  init(translations) {
    this.T = translations;
    this.modalEl = document.getElementById('aksesMenuModal');
    this.subEl = document.getElementById('aksesMenuSub');
    this.loadEl = document.getElementById('menuLoading');
    this.errEl = document.getElementById('menuError');
    this.cntEl = document.getElementById('menuContent');
    this.editModalEl = document.getElementById('menuEditModal');
    this.editErrorEl = document.getElementById('menuEditError');
    this.editModalEl?.addEventListener('hidden.bs.modal', () => {
      this.cleanupModalArtifacts();
      if (this.pendingParentRestoreAfterSave) {
        return;
      }
      if (this.restoreParentMenuModal && this.modalEl) {
        const parentModal = GroupUtils.getModal(this.modalEl);
        if (parentModal) {
          parentModal.show();
        }
      }
      this.restoreParentMenuModal = false;
    });

    const colorPicker = document.getElementById('gc_color_picker');
    const colorInput = document.getElementById('gc_color');
    const categoryInput = document.getElementById('gc_categoryUser');
    const codeInput = document.getElementById('gc_groupKod');
    const nameInput = document.getElementById('gc_groupName');
    if (colorPicker && colorInput) {
      const syncColor = (v) => { colorInput.value = (v || '').trim(); };
      syncColor(colorPicker.value || '#50a4c1');
      colorPicker.addEventListener('input', () => syncColor(colorPicker.value));
    }

    const syncGroupPreview = () => {
      const previewCode = document.getElementById('gc_previewCode');
      const previewName = document.getElementById('gc_previewName');
      const previewCategory = document.getElementById('gc_previewCategory');
      const colorValue = (colorInput?.value || colorPicker?.value || '#50a4c1').trim() || '#50a4c1';
      const codeValue = (codeInput?.value || '').trim() || 'ADM-XX';
      const nameValue = (nameInput?.value || '').trim() || 'Nama Kumpulan';
      const categoryValue = (categoryInput?.value || 'STAF').trim() || 'STAF';

      if (previewCode) previewCode.textContent = codeValue;
      if (previewName) {
        previewName.textContent = nameValue;
        previewName.style.backgroundColor = colorValue;
      }
      if (previewCategory) {
        previewCategory.textContent = categoryValue;
        previewCategory.setAttribute('data-category', categoryValue);
      }
    };
    this.syncGroupPreview = syncGroupPreview;
    colorPicker?.addEventListener('input', syncGroupPreview);
    colorInput?.addEventListener('input', syncGroupPreview);
    categoryInput?.addEventListener('change', syncGroupPreview);
    codeInput?.addEventListener('input', syncGroupPreview);
    nameInput?.addEventListener('input', syncGroupPreview);
    syncGroupPreview();

    this.attachMultiSelectToggle(document.getElementById('gc_moduls'));
    this.attachMultiSelectToggle(document.getElementById('gc_menus'));
    
    // View menu button handler - removed to avoid conflict with main file handler
    // Handler is now in main file (kumpulan-pengguna.php)
    
    // Save button handler
    document.getElementById('menuEditSaveBtn')?.addEventListener('click', () => {
      this.handleSave();
    });
    // Group create modal save handler (global page)
    document.getElementById('groupCreateSaveBtn')?.addEventListener('click', async (e) => {
      e.preventDefault();
      const errEl = document.getElementById('groupCreateError');
      if (errEl) errEl.classList.add('d-none');
      const groupID = parseInt(document.getElementById('gc_groupID')?.value || '0', 10) || 0;
      const payload = {
        groupID,
        groupKod: (document.getElementById('gc_groupKod')?.value || '').trim(),
        groupName: (document.getElementById('gc_groupName')?.value || '').trim(),
        categoryUser: (document.getElementById('gc_categoryUser')?.value || '').trim(),
        priority: parseInt(document.getElementById('gc_priority')?.value || '0', 10) || 0,
        mod: parseInt(document.getElementById('gc_mod')?.value || '0', 10) || 0,
        color: (document.getElementById('gc_color')?.value || '').trim(),
        modulAccess: Array.from(document.getElementById('gc_moduls')?.selectedOptions || []).map(o => o.value).filter(Boolean),
        menuAccess: Array.from(document.getElementById('gc_menus')?.selectedOptions || []).map(o => o.value).filter(Boolean)
      };
      if (!payload.groupKod || !payload.groupName || !payload.categoryUser) {
        if (errEl) { errEl.textContent = this.T.err_group_code_name_required || 'Sila isi Kod & Nama Kumpulan.'; errEl.classList.remove('d-none'); }
        return;
      }

      // Export to global so page scripts can call populateCreateModal()
      try { window.MenuAccess = MenuAccess; } catch (e) { /* ignore */ }
      try {
        const resp = await fetch(GroupUtils.apiUrl('group-create.php'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': GroupUtils.getCSRF() },
          body: JSON.stringify(payload)
        });
        const j = await resp.json();
        if (!j || j.error) throw new Error(j && j.message ? j.message : (this.T.err_save_menu || 'Gagal simpan'));

        // Always reload after save (create/update) so table state/ordering stays correct.
        // This also guarantees latest module/menu access indicators are rendered from DB.
        location.reload();
        return;

        // Close modal and reset form
        const modalEl = document.getElementById('groupCreateModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.hide();
        document.getElementById('groupCreateForm')?.reset();
        if (document.getElementById('gc_groupID')) document.getElementById('gc_groupID').value = '';
        if (document.getElementById('gc_color_picker')) document.getElementById('gc_color_picker').value = '#50a4c1';
        if (document.getElementById('gc_color')) document.getElementById('gc_color').value = '#50a4c1';
        // clear selects
        try { document.getElementById('gc_moduls').innerHTML = ''; document.getElementById('gc_menus').innerHTML = ''; } catch (_) {}

      } catch (err) {
        if (errEl) { errEl.textContent = err.message || this.T.error_network || 'Ralat rangkaian'; errEl.classList.remove('d-none'); }
      }
    });

    // Edit group metadata (reuse create modal)
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('.btn-edit-group-meta');
      if (!btn) return;
      e.preventDefault();

      const modalEl = document.getElementById('groupCreateModal');
      const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      if (modalEl) {
        modalEl.classList.remove('modal-add-accent');
        modalEl.classList.add('modal-child-accent');
      }
      const titleEl = document.getElementById('groupCreateTitle');
      if (titleEl) titleEl.innerHTML = '<i class="ri-pencil-line"></i> <span>' + String(this.T.modal_group_edit_title || '') + '</span>';
      const saveTxt = document.getElementById('groupCreateSaveBtnText');
      if (saveTxt) saveTxt.textContent = String(this.T.btn_update || '');

      const gid = btn.getAttribute('data-group-id') || '';
      const kod = btn.getAttribute('data-group-kod') || '';
      const nama = btn.getAttribute('data-group-nama') || '';
      const categoryUser = btn.getAttribute('data-group-category') || 'STAF';
      const prio = btn.getAttribute('data-group-priority') || '0';
      const mod = btn.getAttribute('data-group-mod') || '0';
      const color = btn.getAttribute('data-group-color') || '#50a4c1';

      if (document.getElementById('gc_groupID')) document.getElementById('gc_groupID').value = gid;
      if (document.getElementById('gc_groupKod')) document.getElementById('gc_groupKod').value = kod;
      if (document.getElementById('gc_groupName')) document.getElementById('gc_groupName').value = nama;
      if (document.getElementById('gc_categoryUser')) document.getElementById('gc_categoryUser').value = categoryUser;
      if (document.getElementById('gc_priority')) document.getElementById('gc_priority').value = prio;
      if (document.getElementById('gc_mod')) document.getElementById('gc_mod').value = mod;
      if (document.getElementById('gc_color_picker')) document.getElementById('gc_color_picker').value = color;
      if (document.getElementById('gc_color')) document.getElementById('gc_color').value = color;
      if (typeof this.syncGroupPreview === 'function') this.syncGroupPreview();

      try {
        if (window.MenuAccess && typeof window.MenuAccess.populateCreateModal === 'function') {
          await window.MenuAccess.populateCreateModal();
        }
        // Prefill existing module/menu selections for edit mode.
        const j = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('group-perms-get.php', { groupID: gid }));
        const toList = (v) => Array.isArray(v) ? v.map(String) : String(v || '').split(',').map(s => s.trim()).filter(Boolean);
        const modulIDs = toList(j.modulIDs ?? j.f_modulAccess ?? []);
        const menuIDs = toList(j.menuIDs ?? j.f_menuAccess ?? []);
        const selMod = document.getElementById('gc_moduls');
        if (selMod) {
          Array.from(selMod.options).forEach(o => { o.selected = modulIDs.includes(String(o.value)); });
          await this.populateMenusForModules(menuIDs);
        }
      } catch (_) {}
      modal.show();
    });

    // Delete group handler
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('.btn-delete-group');
      if (!btn) return;
      e.preventDefault();

      const gid = parseInt(btn.getAttribute('data-group-id') || '0', 10) || 0;
      const gnam = btn.getAttribute('data-group-nama') || this.T.btn_group_label || 'Kumpulan';
      if (gid <= 0) return;

      const ask = await (window.GroupSwal ? GroupSwal.fire({
        icon: 'warning',
        title: this.T.confirm_title || 'Pengesahan',
        text: (this.T.confirm_delete_group_text || 'Padam kumpulan "{name}"?').replace('{name}', gnam),
        showCancelButton: true,
        confirmButtonText: this.T.confirm_yes_delete || 'Ya, Padam',
        cancelButtonText: this.T.confirm_cancel || 'Batal',
      }) : Swal.fire({
        icon: 'warning',
        title: this.T.confirm_title || 'Pengesahan',
        text: (this.T.confirm_delete_group_text || 'Padam kumpulan "{name}"?').replace('{name}', gnam),
        showCancelButton: true,
        confirmButtonText: this.T.confirm_yes_delete || 'Ya, Padam',
        cancelButtonText: this.T.confirm_cancel || 'Batal',
      }));
      if (!ask.isConfirmed) return;

      try {
        const resp = await fetch(GroupUtils.apiUrl('group-delete.php'), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': GroupUtils.getCSRF(),
            'Accept': 'application/json'
          },
          body: JSON.stringify({ groupID: gid })
        });
        const j = await resp.json();
        if (!resp.ok || !j || j.error) {
          const msg = (j && j.message) ? j.message : (this.T.delete_group_fail || 'Gagal memadam kumpulan.');
          await (window.GroupSwal ? GroupSwal.fire({
            icon: 'error',
            title: this.T.not_allowed_title || 'Tidak Dibenarkan',
            text: msg,
          }) : Swal.fire({
            icon: 'error',
            title: this.T.not_allowed_title || 'Tidak Dibenarkan',
            text: msg,
          }));
          return;
        }

        await (window.GroupSwal ? GroupSwal.fire({
          icon: 'success',
          title: this.T.done || 'Berjaya',
          text: this.T.delete_group_success || 'Kumpulan berjaya dipadam.',
          confirmButtonText: this.T.btn_ok || 'OK'
        }) : Swal.fire({
          icon: 'success',
          title: this.T.done || 'Berjaya',
          text: this.T.delete_group_success || 'Kumpulan berjaya dipadam.',
          confirmButtonText: this.T.btn_ok || 'OK'
        }));
        location.reload();
      } catch (err) {
        await (window.GroupSwal ? GroupSwal.fire({
          icon: 'error',
          title: this.T.not_allowed_title || 'Tidak Dibenarkan',
          text: err && err.message ? err.message : (this.T.delete_group_network_fail || 'Ralat rangkaian semasa memadam kumpulan.'),
        }) : Swal.fire({
          icon: 'error',
          title: this.T.not_allowed_title || 'Tidak Dibenarkan',
          text: err && err.message ? err.message : (this.T.delete_group_network_fail || 'Ralat rangkaian semasa memadam kumpulan.'),
        }));
      }
    });
    // Ensure modal UI matches current mode when shown
    if (this.editModalEl) {
      this.editModalEl.addEventListener('show.bs.modal', () => {
        const mode = this.editModalEl.dataset.mode || 'edit';
        this.updateEditModalUI(mode);
      });
    }
  },

  updateEditModalUI(mode) {
    try {
      if (this.editModalEl) {
        this.editModalEl.classList.toggle('modal-add-accent', mode === 'create');
        this.editModalEl.classList.toggle('modal-child-accent', mode !== 'create');
      }
      const titleEl = document.getElementById('menuEditTitleText');
      const saveBtnEl = document.getElementById('menuEditSaveBtn');
      const buttonLabel = mode === 'create'
        ? String(this.T.btn_save || '')
        : String(this.T.btn_update || '');
      if (titleEl) {
        if (mode === 'create') titleEl.textContent = titleEl.dataset.titleCreate || String(this.T.modal_add_menu_title || '');
        else titleEl.textContent = titleEl.dataset.titleEdit || String(this.T.modal_edit_menu_title || '');
      }
      if (saveBtnEl) {
        saveBtnEl.innerHTML = '<i class="ri-save-3-line me-1"></i> <span id="menuEditSaveBtnText">' + String(buttonLabel) + '</span>';
        saveBtnEl.setAttribute('aria-label', buttonLabel);
        saveBtnEl.setAttribute('title', buttonLabel);
      }
    } catch (e) { /* silent */ }
  },
  
  showLoading() {
    this.loadEl?.classList.remove('d-none');
    this.errEl?.classList.add('d-none');
    this.cntEl?.classList.add('d-none');
    this.cntEl.innerHTML = '';
  },
  
  showError(msg) {
    this.loadEl?.classList.add('d-none');
    if (this.errEl) {
      this.errEl.textContent = msg || this.T.error_unknown;
      this.errEl.classList.remove('d-none');
    }
  },
  
  showContent(html) {
    this.loadEl?.classList.add('d-none');
    this.errEl?.classList.add('d-none');
    if (this.cntEl) {
      if (html != null) {
        // Use safe assign to avoid executing injected scripts or on* handlers.
        try {
          if (window.DOMPurify && typeof DOMPurify.sanitize === 'function') {
            this.cntEl.innerHTML = DOMPurify.sanitize(html);
          } else {
            const doc = new DOMParser().parseFromString('<div>' + html + '</div>', 'text/html');
            doc.querySelectorAll('script').forEach(s => s.remove());
            doc.querySelectorAll('*').forEach(n => {
              Array.from(n.attributes).forEach(a => {
                if (/^on/i.test(a.name)) n.removeAttribute(a.name);
                if ((a.name === 'src' || a.name === 'href') && /^javascript:/i.test(a.value)) n.removeAttribute(a.name);
              });
            });
            this.cntEl.innerHTML = doc.body.firstChild ? doc.body.firstChild.innerHTML : '';
          }
        } catch (e) {
          this.cntEl.innerHTML = html;
        }
      }
      this.cntEl.classList.remove('d-none');
    }
  },
  
  // Parse menu helper
  parseMenu(me) {
    const id = parseInt(me.id ?? me.f_menuID, 10);
    const modulID = parseInt(me.modulID ?? me.f_modulID, 10);
    const hasFlag = Object.prototype.hasOwnProperty.call(me, 'flag') ||
                    Object.prototype.hasOwnProperty.call(me, 'f_flag') ||
                    Object.prototype.hasOwnProperty.call(me, 'active') ||
                    Object.prototype.hasOwnProperty.call(me, 'is_active');
    const rawFlag = hasFlag ? (me.flag ?? me.f_flag ?? me.active ?? me.is_active) : 1;
    const __asOn = (v) => v === 1 || v === '1' || v === true || v === 'true' || v === 'on';
    
    return {
      id,
      modulID,
      name: String(me.nama || me.menuName || me.kod || '-'),
      path: String(me.path || me.f_path || ''),
      domain: String(me.domain || me.f_domain || 'SHARED'),
      showStaffOnly: parseInt(me.show_staff_only ?? me.f_show_staff_only ?? 1, 10) === 1 ? 1 : 0,
      flag: __asOn(rawFlag) ? 1 : 0
    };
  },
  
  // Fetch all menus
  async fetchAllMenusStrict() {
    try {
      const j = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('menu-list.php', { all: 1 }));
      const raw = Array.isArray(j?.menus) ? j.menus : (Array.isArray(j?.data) ? j.data : []);
      if (raw.length) {
        return raw.map(m => this.parseMenu(m)).filter(x => Number.isInteger(x.id) && Number.isInteger(x.modulID));
      }
    } catch (_) { /* fallback */ }
    
    // Fallback: loop setiap modul
    let modulIDs = [];
    try {
      const ml = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('modul-list.php'));
      const arr = Array.isArray(ml?.moduls) ? ml.moduls : (Array.isArray(ml) ? ml : []);
      modulIDs = arr.map(m => parseInt(m.id ?? m.f_modulID, 10)).filter(Number.isInteger);
    } catch (_) {}
    
    const all = [];
    for (const mid of modulIDs) {
      try {
        const j = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('menu-list.php', { modulID: mid }));
        const raw = Array.isArray(j?.menus) ? j.menus : (Array.isArray(j?.data) ? j.data : j);
        (raw || []).forEach(r => all.push(this.parseMenu(r)));
      } catch (_) {}
    }
    return all.filter(x => Number.isInteger(x.id) && Number.isInteger(x.modulID));
  },

  // Populate module/menu selects in the group create modal
  async populateCreateModal() {
    try {
      // populate modules
      const errEl = document.getElementById('groupCreateError');
      if (errEl) errEl.classList.add('d-none');
      const selMod = document.getElementById('gc_moduls');
      if (selMod) {
        selMod.innerHTML = '<option value="">' + GroupUtils.esc(this.T.loading_short || 'Memuat…') + '</option>';
      }

      let ml;
      const url = GroupUtils.apiUrl('modul-list.php');
      // small timeout wrapper so a hung request surfaces to user
      const fetchWithTimeout = (p, ms = 7000) => Promise.race([
        p,
        new Promise((_, rej) => setTimeout(() => rej(new Error('timeout')), ms))
      ]);
      try {
        ml = await fetchWithTimeout(GroupUtils.fetchJSONSafe(url), 7000);
      } catch (e) {
        console.error('modul-list fetch failed', e, url);
        if (errEl) { errEl.textContent = this.formatText(this.T.load_modules_fail || 'Gagal memuat modul dari: {url} — {error}', { url, error: e.message || (this.T.error || 'Ralat') }); errEl.classList.remove('d-none'); }
        if (selMod) selMod.innerHTML = '';
        return;
      }

      const arr = Array.isArray(ml?.moduls) ? ml.moduls : (Array.isArray(ml) ? ml : []);
      if (!arr.length) {
        if (errEl) { errEl.textContent = this.T.no_modules_found || 'Tiada modul ditemui.'; errEl.classList.remove('d-none'); }
        if (selMod) selMod.innerHTML = '';
        return;
      }

      if (selMod) {
        selMod.innerHTML = '';
        arr.forEach(function(m) {
          var idVal = (m.id !== undefined && m.id !== null) ? m.id : (m.f_modulID || '');
          var id = String(idVal);
          var name = String(m.nama || m.modulName || id);
          var opt = document.createElement('option');
          opt.value = id; opt.textContent = name; selMod.appendChild(opt);
        });
        // attach change listener to populate menus on selection
        try {
          if (typeof selMod.removeEventListener === 'function') selMod.removeEventListener('change', MenuAccess.populateMenusForModules);
        } catch (e) { /* ignore */ }
        selMod.addEventListener('change', function() { MenuAccess.populateMenusForModules().catch(function(){/*ignore*/}); });
        this.attachMultiSelectToggle(selMod);
      }
      // initial populate menus (none selected → empty)
      await MenuAccess.populateMenusForModules();
    } catch (e) {
      // ignore populate errors
      console.warn('populateCreateModal error', e);
    }
  },

  async populateMenusForModules(preselectedMenuIds = []) {
    try {
      const selMod = document.getElementById('gc_moduls');
      const selMenu = document.getElementById('gc_menus');
      if (!selMenu) return;
      selMenu.innerHTML = '';
      this.attachMultiSelectToggle(selMenu);
      const selected = Array.from(selMod?.selectedOptions || []).map(o => o.value).filter(Boolean);
      if (!selected.length) return;
      const seen = new Set();
      for (const mid of selected) {
        try {
          const j = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('menu-list.php', { modulID: mid }));
          const raw = Array.isArray(j?.menus) ? j.menus : (Array.isArray(j?.data) ? j.data : (Array.isArray(j) ? j : []));
          for (const m of raw) {
            const id = String(m.id ?? m.f_menuID ?? m.f_menuID);
            if (!id || seen.has(id)) continue;
            seen.add(id);
            const name = String(m.nama || m.menuName || m.f_path || ('Menu ' + id));
            const opt = document.createElement('option'); opt.value = id; opt.textContent = name; selMenu.appendChild(opt);
          }
        } catch (e) { /* ignore per-module errors */ }
      }
      if (preselectedMenuIds && preselectedMenuIds.length) {
        const selectedSet = new Set(preselectedMenuIds.map(String));
        Array.from(selMenu.options).forEach(o => { o.selected = selectedSet.has(String(o.value)); });
      }
    } catch (e) {
      console.warn('populateMenusForModules error', e);
    }
  },
  
  buildMenuTable(rows) {
    const domainBadge = (domain) => {
      const safeDomain = String(domain || 'SHARED').toUpperCase();
      return '<span class="badge rounded-pill menu-domain-badge" data-domain="' + GroupUtils.esc(safeDomain) + '">' + GroupUtils.esc(safeDomain) + '</span>';
    };
    const staffOnlyBadge = (showStaffOnly) => {
      const isShown = parseInt(showStaffOnly ?? 1, 10) === 1;
      const label = isShown
        ? GroupUtils.esc(this.T.menu_staff_only_show_full || this.T.menu_staff_only_show || '')
        : GroupUtils.esc(this.T.menu_staff_only_hide_full || this.T.menu_staff_only_hide || '');
      const cls = isShown
        ? 'bg-success-subtle text-success-emphasis border-success-subtle'
        : 'bg-danger-subtle text-danger-emphasis border-danger-subtle';
      return '<span class="badge rounded-pill border ' + cls + '">' + label + '</span>';
    };
    const html =
      '<table class="table table-striped table-bordered align-middle w-100" id="menuDT">' +
      '<thead class="table-light"><tr>' +
      '<th style="width:20%" class="text-start">' + GroupUtils.esc(this.T.field_modul || '') + '</th>' +
      '<th style="width:20%" class="text-start">' + GroupUtils.esc(this.T.col_menu || '') + '</th>' +
      '<th style="width:30%" class="text-start">' + GroupUtils.esc(this.T.col_visibility || '') + '</th>' +
      '<th class="text-center col-status" style="width:15%">' + GroupUtils.esc(this.T.col_status || '') + '</th>' +
      '<th class="text-center col-actions" style="width:15%">' + GroupUtils.esc(this.T.col_actions || '') + '</th>' +
      '</tr></thead><tbody></tbody>' +
      '</table>';

    this.showContent(html);

    const tbody = this.cntEl.querySelector('#menuDT tbody');
    if (rows.length) {
      let lastModulID = null;
      tbody.innerHTML = rows.map(r => {
        const onId = 'flag_on_' + GroupUtils.esc(r.menuID);
        const offId = 'flag_off_' + GroupUtils.esc(r.menuID);
        const isOn = (parseInt(r.flag, 10) === 1);
        const showModulName = lastModulID !== r.modulID;
        const modulCellHtml = showModulName ? GroupUtils.esc(r.modulName) : '&nbsp;';
        const modulCellClass = showModulName ? 'fw-semibold align-top' : 'align-top';
        const pathTooltip = r.path
          ? '<button type="button" class="btn btn-link btn-sm p-0 ms-1 align-baseline menu-path-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="menu-path-tooltip" title="' + GroupUtils.esc(r.path) + '" aria-label="' + GroupUtils.esc(this.T.menu_path_info || 'Lihat path menu') + '"><i class="ri-information-line text-muted"></i></button>'
          : '';
        lastModulID = r.modulID;
        return '' +
          '<tr data-modul-id="' + GroupUtils.esc(r.modulID) + '" data-menu-id="' + GroupUtils.esc(r.menuID) + '">' +
          '<td class="' + modulCellClass + ' text-start">' + modulCellHtml + '</td>' +
          '<td class="text-start"><div class="fw-semibold d-inline-flex align-items-start">' + GroupUtils.esc(r.menuName) + pathTooltip + '</div></td>' +
          '<td class="text-start"><div class="d-flex flex-wrap gap-1">' +
              domainBadge(r.domain) +
              staffOnlyBadge(r.showStaffOnly) +
            '</div>' +
          '</td>' +
          '<td class="text-center col-status">' +
          '<input type="radio" class="btn-check menu-flag" name="flag-' + GroupUtils.esc(r.menuID) + '" id="' + onId + '" value="1" ' + (isOn ? 'checked' : '') + '>' +
          '<label class="btn btn-outline-success btn-sm me-1" for="' + onId + '">' + GroupUtils.esc(this.T.status_on || 'ON') + '</label>' +
          '<input type="radio" class="btn-check menu-flag" name="flag-' + GroupUtils.esc(r.menuID) + '" id="' + offId + '" value="0" ' + (!isOn ? 'checked' : '') + '>' +
          '<label class="btn btn-outline-secondary btn-sm" for="' + offId + '">' + GroupUtils.esc(this.T.status_off || 'OFF') + '</label>' +
          '</td>' +
          '<td class="text-center col-actions">' +
          '<button class="btn btn-sm btn-outline-secondary icon-btn btn-edit-menu" title="' + GroupUtils.esc(this.T.edit || 'Edit') + '" aria-label="' + GroupUtils.esc(this.T.edit || 'Edit') + '"><i class="ri-pencil-line"></i></button> ' +
          '<button class="btn btn-sm btn-outline-danger icon-btn btn-del-menu" title="' + GroupUtils.esc(this.T.delete || 'Padam') + '" aria-label="' + GroupUtils.esc(this.T.delete || 'Padam') + '"><i class="ri-delete-bin-line"></i></button>' +
          '</td>' +
          '</tr>';
      }).join('');
    } else {
      tbody.innerHTML = '';
    }

    const dt = GroupState.getMenuDataTable();
    if (dt) {
      try { dt.destroy(); } catch (e) {}
      GroupState.setMenuDataTable(null);
    }

    if (GroupUtils.hasDataTable()) {
      const table = jQuery('#menuDT').DataTable({
        pageLength: 10,
        lengthChange: false,
        ordering: false,
        columnDefs: [
          { targets: 0, className: 'text-start align-top' },
          { targets: 1, className: 'text-start align-top' },
          { targets: 2, orderable: false, searchable: false, className: 'text-start align-top' },
          { targets: 3, orderable: false, searchable: false, className: 'text-center' },
          { targets: 4, orderable: false, searchable: false, className: 'text-center' }
        ],
        dom: 'rt' + '<"dt-bottom-row mt-2 d-flex justify-content-between align-items-center"<"dt-info-left"i><"dt-paging-right d-flex justify-content-end"p>>',
        language: {
          emptyTable: this.T.no_records || 'Tiada rekod'
        }
      });
      GroupState.setMenuDataTable(table);
    }

    try {
      document.querySelectorAll('#menuDT [data-bs-toggle="tooltip"]').forEach((el) => {
        try {
          const existing = bootstrap.Tooltip.getInstance(el);
          if (existing) existing.dispose();
          new bootstrap.Tooltip(el, {
            html: false,
            container: '#aksesMenuModal',
            trigger: 'hover focus'
          });
        } catch (_) { /* ignore */ }
      });
    } catch (_) { /* ignore */ }

    // Event handlers
    jQuery('#menuDT').off('click', '.btn-edit-menu').on('click', '.btn-edit-menu', (e) => {
      e.preventDefault();
      const tr = e.currentTarget.closest('tr');
      if (!tr) return;
      this.openEditMenu(tr.getAttribute('data-menu-id'));
    });
    
    jQuery('#menuDT').off('click', '.btn-del-menu').on('click', '.btn-del-menu', (e) => {
      e.preventDefault();
      const tr = e.currentTarget.closest('tr');
      if (!tr) return;
      this.deleteMenu(tr.getAttribute('data-menu-id'), tr);
    });

    jQuery('#menuDT').off('change', '.menu-flag').on('change', '.menu-flag', async function () {
      const input = this;
      const tr = input.closest('tr');
      if (!tr) return;
      const menuId = tr.getAttribute('data-menu-id');
      const flagVal = input.value === '1' ? 1 : 0;
      try {
        const resp = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('menu-flag-toggle.php'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': GroupUtils.getCSRF() },
          body: JSON.stringify({ menuID: menuId, flag: flagVal })
        });
        if (!resp || resp.error) throw new Error((resp && resp.message) || this.T.error_update_status || 'Gagal kemas kini status.');
      } catch (e) {
        const name = 'flag-' + menuId;
        MenuAccess.cntEl.querySelectorAll('input[name="' + name + '"]').forEach(el => {
          if (el !== input) el.checked = !input.checked;
        });
        MenuAccess.showError(e.message || MenuAccess.T.error_network || 'Ralat rangkaian');
        setTimeout(() => { MenuAccess.errEl.classList.add('d-none'); }, 2500);
      }
    });
  },
  
  async openMenuEditor(groupID) {
    this.showLoading();
    try {
      // Get modul map
      let modulMap = {};
      try {
        const ml = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('modul-list.php'));
        const arr = Array.isArray(ml?.moduls) ? ml.moduls : (Array.isArray(ml) ? ml : []);
        modulMap = Object.fromEntries(
          arr.map(m => {
            const id = parseInt(m.id ?? m.f_modulID, 10);
            const nm = String(m.nama || m.modulName || ('Modul ' + (m.id || m.f_modulID)));
            return [id, nm];
          })
        );
      } catch (_) {}

      const allMenus = await this.fetchAllMenusStrict();
      const rows = allMenus.map(m => ({
        modulID: m.modulID,
        modulName: modulMap[m.modulID] || ('Modul ' + m.modulID),
        menuID: m.id,
        menuName: m.name,
        path: m.path,
        domain: m.domain,
        showStaffOnly: m.showStaffOnly,
        flag: m.flag
      }));

      rows.sort((a, b) => (a.modulID - b.modulID) || String(a.menuName).localeCompare(String(b.menuName)));
      this.buildMenuTable(rows);
    } catch (e) {
      this.showError(e.message || this.T.error_network);
    }
  },
  
  handleAddMenu() {
    const modal = GroupUtils.getModal(this.editModalEl);
    if (!modal) return;
    const parentModal = GroupUtils.getModal(this.modalEl);

    const gidFromCtx = GroupState.getMenuGroupID();
    const gidFromHidden = (() => {
      const el = document.getElementById('em_groupID');
      const v = el ? parseInt((el.value || '0'), 10) : 0;
      return Number.isFinite(v) && v > 0 ? v : null;
    })();
    const gidFromBtn = (() => {
      const btn = GroupState.getLastMenuBtn() || document.querySelector('.view-menu[data-group-id]');
      const v = btn ? parseInt(btn.getAttribute('data-group-id') || '0', 10) : 0;
      return Number.isFinite(v) && v > 0 ? v : null;
    })();

    const resolvedGroupID = gidFromCtx || gidFromHidden || gidFromBtn || null;

      if (!resolvedGroupID) {
        if (window.Swal && typeof Swal.fire === 'function') {
          (window.GroupSwal ? GroupSwal.fire({
            icon: 'warning',
            title: this.T.info_title || 'Makluman',
            text: this.T.info_select_group_first || 'Sila pilih kumpulan dahulu melalui butang Akses Menu.',
            confirmButtonText: this.T.btn_ok || 'OK'
          }) : Swal.fire({
            icon: 'warning',
            title: this.T.info_title || 'Makluman',
            text: this.T.info_select_group_first || 'Sila pilih kumpulan dahulu melalui butang Akses Menu.',
            confirmButtonText: this.T.btn_ok || 'OK'
          }));
        } else {
          alert(this.T.info_select_group_first || 'Sila pilih kumpulan dahulu melalui butang Akses Menu.');
        }
        return;
      }

    const hidEl = document.getElementById('em_groupID');
    const infoEl = document.getElementById('em_groupInfo');
    const infoWrapEl = document.getElementById('em_groupInfoWrap');
    if (hidEl) hidEl.value = String(resolvedGroupID);
    if (infoEl) {
      const src = GroupState.getLastMenuBtn() || document.querySelector('.view-menu[data-group-kod]');
      const gkod = src?.getAttribute('data-group-kod') || '';
      const gnam = src?.getAttribute('data-group-nama') || '';
      infoEl.textContent = (gkod + (gnam ? ' — ' + gnam : '')).trim();
    }
    if (infoWrapEl) infoWrapEl.classList.toggle('d-none', !(infoEl && String(infoEl.textContent || '').trim() !== ''));

    this.editErrorEl.classList.add('d-none');
    this.$ME('#em_menuID').value = '';
    this.$ME('#em_path').value = '';
    this.$ME('#em_name_ms').value = '';
    this.$ME('#em_name_en').value = '';
    this.$ME('#em_domain').value = 'SHARED';
    this.$ME('#em_show_staff_only_yes').checked = true;
    this.$ME('#em_flag_on').checked = true;

    this.populateModuls(null).then(() => {
      this.editModalEl.dataset.mode = 'create';
      this.updateEditModalUI('create');
      if (parentModal && this.modalEl?.classList.contains('show')) {
        this.restoreParentMenuModal = true;
        parentModal.hide();
      } else {
        this.restoreParentMenuModal = false;
      }
      modal.show();
    });
  },
  
  async openEditMenu(menuID) {
    const modal = GroupUtils.getModal(this.editModalEl);
    if (!modal) return;
    const parentModal = GroupUtils.getModal(this.modalEl);
    this.editErrorEl.classList.add('d-none');
    try {
      const j = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('menu-get.php', { menuID }));
      if (!j || j.error) {
        this.showError((j && j.message) || this.T.error_get_menu);
        return;
      }
      this.$ME('#em_menuID').value = j.menu.f_menuID;
      this.$ME('#em_path').value = j.menu.f_path || '';
      this.$ME('#em_name_ms').value = j.menu.f_menuName_ms || '';
      this.$ME('#em_name_en').value = j.menu.f_menuName_en || '';
      this.$ME('#em_domain').value = j.menu.f_domain || 'SHARED';
      (parseInt(j.menu.f_show_staff_only ?? 1, 10) === 1 ? (this.$ME('#em_show_staff_only_yes').checked = true) : (this.$ME('#em_show_staff_only_no').checked = true));
      (parseInt(j.menu.f_flag ?? 0, 10) === 1 ? (this.$ME('#em_flag_on').checked = true) : (this.$ME('#em_flag_off').checked = true));
      await this.populateModuls(j.menu.f_modulID);
      this.editModalEl.dataset.mode = 'edit';
      this.updateEditModalUI('edit');
      if (parentModal && this.modalEl?.classList.contains('show')) {
        this.restoreParentMenuModal = true;
        parentModal.hide();
      } else {
        this.restoreParentMenuModal = false;
      }
      modal.show();
    } catch (e) {
      this.showError(e.message || this.T.error_network);
    }
  },
  
  async handleSave() {
    const modal = GroupUtils.getModal(this.editModalEl);
    const mode = this.editModalEl.dataset.mode || 'edit';
    this.editErrorEl.classList.add('d-none');

    const gidFromCtx = GroupState.getMenuGroupID();
    const gidFromHidden = Number.parseInt(document.getElementById('em_groupID')?.value || '0', 10) || 0;
    const groupID = gidFromCtx || gidFromHidden || 0;

    const payload = {
      groupID,
      menuID: parseInt((this.$ME('#em_menuID')?.value || '0'), 10),
      modulID: parseInt((this.$ME('#em_modulID')?.value || '0'), 10),
      path: (this.$ME('#em_path')?.value || '').trim(),
      name_ms: this.$ME('#em_name_ms')?.value || '',
      name_en: this.$ME('#em_name_en')?.value || '',
      domain: this.$ME('#em_domain')?.value || 'SHARED',
      show_staff_only: this.$ME('#em_show_staff_only_yes')?.checked ? 1 : 0,
      flag: this.$ME('#em_flag_on')?.checked ? 1 : 0,
      position: document.getElementById('em_position')?.value || 'bottom'
    };

    if (!payload.path) {
      this.editErrorEl.textContent = this.T.err_path_required;
      this.editErrorEl.classList.remove('d-none');
      return;
    }
    if (payload.modulID <= 0) {
      this.editErrorEl.textContent = this.T.err_modul_required;
      this.editErrorEl.classList.remove('d-none');
      return;
    }
    if (!groupID) {
      this.editErrorEl.textContent = this.T.err_group_modul_path_required || 'Sila pilih Kumpulan, Modul dan isi Path.';
      this.editErrorEl.classList.remove('d-none');
      return;
    }

    try {
      const target = (mode === 'create') ? 'menu-create.php' : 'menu-save.php';
      const j = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl(target, { groupID }), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': GroupUtils.getCSRF() },
        body: JSON.stringify(payload)
      });

      if (!j || j.error) {
        this.editErrorEl.textContent = (j && j.message) || (mode === 'create' ? this.T.err_add_menu : this.T.err_save_menu);
        this.editErrorEl.classList.remove('d-none');
        return;
      }
      const shouldRestoreParent = this.restoreParentMenuModal && this.modalEl;
      this.pendingParentRestoreAfterSave = !!shouldRestoreParent;
      this.restoreParentMenuModal = false;
      if (modal && this.editModalEl) {
        await new Promise((resolve) => {
          const onceHidden = () => resolve();
          this.editModalEl.addEventListener('hidden.bs.modal', onceHidden, { once: true });
          modal.hide();
        });
      } else {
        this.cleanupModalArtifacts();
      }
      if (GroupState && typeof GroupState.setMenuGroupID === 'function') {
        GroupState.setMenuGroupID(String(groupID));
      }
      if (document.getElementById('em_groupID')) {
        document.getElementById('em_groupID').value = String(groupID);
      }
      if (window.Swal && typeof Swal.fire === 'function') {
        await (window.GroupSwal ? GroupSwal.fire({
          icon: 'success',
          title: mode === 'create' ? (this.T.menu_save_success_create || 'Menu berjaya ditambah') : (this.T.menu_save_success_update || 'Menu berjaya dikemaskini'),
          confirmButtonText: this.T.btn_ok || 'OK'
        }) : Swal.fire({
          icon: 'success',
          title: mode === 'create' ? (this.T.menu_save_success_create || 'Menu berjaya ditambah') : (this.T.menu_save_success_update || 'Menu berjaya dikemaskini'),
          confirmButtonText: this.T.btn_ok || 'OK'
        }));
      }
      if (shouldRestoreParent) {
        const parentModal = GroupUtils.getModal(this.modalEl);
        if (parentModal) {
          parentModal.show();
        }
      }
      this.pendingParentRestoreAfterSave = false;
      this.openMenuEditor(groupID);
    } catch (e) {
      this.pendingParentRestoreAfterSave = false;
      this.editErrorEl.textContent = e.message || this.T.error_network;
      this.editErrorEl.classList.remove('d-none');
    }
  },
  
  async deleteMenu(menuID, tr) {
    const menuName = (() => {
      try {
        if (!tr) return '';
        const el = tr.querySelector('td:nth-child(2) .fw-semibold');
        return (el?.textContent || '').trim();
      } catch (_) { return ''; }
    })();
    const prettyName = menuName || `ID ${menuID}`;

    async function askConfirm() {
      if (window.Swal && typeof Swal.fire === 'function') {
        const escapedName = GroupUtils.esc(prettyName);
        const confirmTitle = MenuAccess.formatText(MenuAccess.T.confirm_delete_menu_title || 'Padam menu "{name}"?', { name: escapedName });
        const confirmIntro = MenuAccess.formatText(MenuAccess.T.confirm_delete_menu_intro || 'Menu <strong>{name}</strong> akan <u>dipadam</u>.', { name: escapedName });
        const confirmCleanup = MenuAccess.T.confirm_delete_menu_cleanup || 'Menu ini juga akan dibersihkan daripada <em>semua kumpulan</em> yang rujuk ID ini.';
        const confirmIrreversible = MenuAccess.T.confirm_delete_menu_irreversible || 'Tindakan ini tidak boleh diundur.';
        const res = await (window.GroupSwal ? GroupSwal.fire({
          icon: 'warning',
          title: confirmTitle,
          html: `
            <div class="text-start">
              <p class="mb-2">${confirmIntro}</p>
              <ul class="mb-0">
                <li>${confirmCleanup}</li>
                <li>${confirmIrreversible}</li>
              </ul>
            </div>
          `,
          showCancelButton: true,
          confirmButtonText: MenuAccess.T.confirm_yes || 'Ya, padam',
          cancelButtonText: MenuAccess.T.confirm_cancel || 'Batal',
          reverseButtons: true,
          focusCancel: true
        }) : Swal.fire({
          icon: 'warning',
          title: confirmTitle,
          html: `
            <div class="text-start">
              <p class="mb-2">${confirmIntro}</p>
              <ul class="mb-0">
                <li>${confirmCleanup}</li>
                <li>${confirmIrreversible}</li>
              </ul>
            </div>
          `,
          showCancelButton: true,
          confirmButtonText: MenuAccess.T.confirm_yes || 'Ya, padam',
          cancelButtonText: MenuAccess.T.confirm_cancel || 'Batal',
          reverseButtons: true,
          focusCancel: true
        }));
        return res.isConfirmed;
      }
      if (window.swal && typeof window.swal === 'function') {
        return await new Promise(resolve => {
          window.swal({
            title: MenuAccess.formatText(MenuAccess.T.confirm_delete_menu_title || 'Padam menu "{name}"?', { name: prettyName }),
            text: MenuAccess.T.confirm_delete_menu_cleanup || 'Menu ini juga akan dibersihkan daripada semua kumpulan.',
            icon: 'warning',
            buttons: [MenuAccess.T.confirm_cancel || 'Batal', MenuAccess.T.confirm_yes || 'Ya, padam'],
            dangerMode: true
          }).then(val => resolve(!!val));
        });
      }
      return confirm(MenuAccess.formatText(MenuAccess.T.confirm_delete_menu_fallback || 'Padam menu "{name}"? Menu ini juga akan dibersihkan daripada semua kumpulan.', { name: prettyName }));
    }

    const ok = await askConfirm();
    if (!ok) return;

    let delBtn;
    if (tr) {
      delBtn = tr.querySelector('.btn-del-menu');
      if (delBtn) delBtn.disabled = true;
    }

    try {
      const payload = {
        menuID: Number(menuID),
        groupID: Number(GroupState.getMenuGroupID() || 0),
        hard: 1
      };

      const j = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('menu-delete.php'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': GroupUtils.getCSRF(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      });

      if (!j || j.error) throw new Error((j && j.message) || MenuAccess.T.delete_fail || 'Gagal memadam.');

      const dt = GroupState.getMenuDataTable();
      if (window.jQuery && dt && tr) {
        dt.row(jQuery(tr)).remove().draw(false);
      } else if (tr && tr.parentNode) {
        tr.parentNode.removeChild(tr);
      } else {
        this.openMenuEditor(GroupState.getMenuGroupID());
      }

      if (window.Swal && Swal.fire) {
        (window.GroupSwal ? GroupSwal.fire({
          icon: 'success',
          title: MenuAccess.T.deleted_title || 'Dipadam',
          text: MenuAccess.formatText(MenuAccess.T.delete_menu_cleanup_success || 'Menu "{name}" dibersihkan dari semua kumpulan.', { name: prettyName }),
          confirmButtonText: MenuAccess.T.btn_ok || 'OK'
        }) : Swal.fire({
          icon: 'success',
          title: MenuAccess.T.deleted_title || 'Dipadam',
          text: MenuAccess.formatText(MenuAccess.T.delete_menu_cleanup_success || 'Menu "{name}" dibersihkan dari semua kumpulan.', { name: prettyName }),
          confirmButtonText: MenuAccess.T.btn_ok || 'OK'
        }));
      }
    } catch (e) {
      if (window.Swal && Swal.fire) {
        (window.GroupSwal ? GroupSwal.fire({
          icon: 'error',
          title: MenuAccess.T.delete_failed_title || 'Gagal',
          text: e.message || MenuAccess.T.error_network || 'Ralat rangkaian',
          confirmButtonText: MenuAccess.T.btn_ok || 'OK'
        }) : Swal.fire({
          icon: 'error',
          title: MenuAccess.T.delete_failed_title || 'Gagal',
          text: e.message || MenuAccess.T.error_network || 'Ralat rangkaian',
          confirmButtonText: MenuAccess.T.btn_ok || 'OK'
        }));
      } else {
        alert(e.message || MenuAccess.T.error_network || 'Ralat rangkaian');
      }
    } finally {
      if (delBtn) delBtn.disabled = false;
    }
  },
  
  async populateModuls(selected) {
    const sel = this.$ME('#em_modulID');
    if (!sel) return;
    sel.innerHTML = '<option value="">' + GroupUtils.esc(this.T.loading_modules || 'Memuatkan modul...') + '</option>';
    let options = [];
    const normalizeModules = (rows) => (Array.isArray(rows) ? rows : []).map(m => ({
      id: parseInt(m.id ?? m.f_modulID, 10),
      nama: String(m.nama || m.modulName || m.f_modulName_ms || m.f_modulName_en || ('Modul ' + (m.id || m.f_modulID)))
    })).filter(x => Number.isInteger(x.id) && x.id > 0);
    try {
      const j = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('modul-list.php'));
      const arr = Array.isArray(j?.moduls) ? j.moduls : (Array.isArray(j) ? j : []);
      options = normalizeModules(arr);
    } catch (e) {
      console.warn('populateModuls AJAX failed, using embedded module options', e);
    }
    if (!options.length) {
      options = normalizeModules(window.GroupModuleOptions || []);
    }
    sel.innerHTML = '';
    options.forEach(m => {
      const opt = document.createElement('option');
      opt.value = String(m.id);
      opt.textContent = m.nama;
      if (selected && parseInt(selected, 10) === m.id) opt.selected = true;
      sel.appendChild(opt);
    });
    if (!options.length) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = this.T.no_modules_found || 'Tiada modul ditemui.';
      sel.appendChild(opt);
    }
  },
  
  openMenuFromBtn(btn) {
    const el = document.getElementById('aksesMenuModal');
    const gid = btn.getAttribute('data-group-id');
    const gkod = btn.getAttribute('data-group-kod') || '';
    const gnam = btn.getAttribute('data-group-nama') || '';

    GroupState.setMenuGroupID(gid);
    if (this.subEl) this.subEl.textContent = gkod + (gnam ? ' — ' + gnam : '');

    const hidEl = document.getElementById('em_groupID');
    const infoEl = document.getElementById('em_groupInfo');
    const infoWrapEl = document.getElementById('em_groupInfoWrap');
    if (hidEl) hidEl.value = String(GroupState.getMenuGroupID() || '');
    if (infoEl) infoEl.textContent = (gkod + (gnam ? ' — ' + gnam : '')).trim();
    if (infoWrapEl) infoWrapEl.classList.toggle('d-none', !(infoEl && String(infoEl.textContent || '').trim() !== ''));

    if (window.bootstrap?.Modal && el) {
      GroupUtils.ensureInBody(el);
      window.bootstrap.Modal.getOrCreateInstance(el, { backdrop: true, focus: true, keyboard: true }).show();
    } else if (el) {
      el.style.display = 'block';
      el.classList.add('show');
      el.removeAttribute('aria-hidden');
      el.setAttribute('aria-modal', 'true');
      el.setAttribute('role', 'dialog');
    } else {
      console.error('aksesMenuModal tidak ditemui');
      return;
    }

    this.openMenuEditor(gid);
  }
};

window.MenuAccess = MenuAccess;
