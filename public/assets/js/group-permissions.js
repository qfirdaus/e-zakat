/**
 * Group Permissions Management untuk kumpulan-pengguna.php
 * Handle modal akses kumpulan dengan modul/menu picker
 */

const GroupPermissions = {
  // DOM elements
  modalEl: null,
  subEl: null,
  loadEl: null,
  errEl: null,
  cntEl: null,
  ringModalEl: null,
  ringLoadEl: null,
  ringErrEl: null,
  ringCntEl: null,
  pickModalEl: null,
  pickLoadEl: null,
  pickErrEl: null,
  pickCntEl: null,
  pickSubEl: null,
  currentGroupSubtitle: '',
  restoreParentModalFromRing: false,
  restoreParentModalFromPick: false,
  
  init() {
    this.modalEl = document.getElementById('aksesGroupModal');
    this.subEl = document.getElementById('aksesGroupSub');
    this.loadEl = document.getElementById('grpLoading');
    this.errEl = document.getElementById('grpError');
    this.cntEl = document.getElementById('grpCnt');
    this.ringModalEl = document.getElementById('ringkasanModal');
    this.ringLoadEl = document.getElementById('ringkasanLoading');
    this.ringErrEl = document.getElementById('ringkasanError');
    this.ringCntEl = document.getElementById('ringkasanContent');
    this.pickModalEl = document.getElementById('menuPickModal');
    this.pickLoadEl = document.getElementById('menuPickLoading');
    this.pickErrEl = document.getElementById('menuPickError');
    this.pickCntEl = document.getElementById('menuPickContent');
    this.pickSubEl = document.getElementById('menuPickSub');

    this.ringModalEl?.addEventListener('hidden.bs.modal', () => {
      if (!this.restoreParentModalFromRing || !this.modalEl) return;
      this.restoreParentModalFromRing = false;
      const parentModal = GroupUtils.getModal(this.modalEl);
      parentModal?.show();
    });

    this.pickModalEl?.addEventListener('hidden.bs.modal', () => {
      if (!this.restoreParentModalFromPick || !this.modalEl) return;
      this.restoreParentModalFromPick = false;
      const parentModal = GroupUtils.getModal(this.modalEl);
      parentModal?.show();
    });
    
    // View group perms button handler
    const self = this;
    document.addEventListener('click', function(e) {
      const btn = e.target.closest('.view-group-perms');
      if (btn) {
        e.preventDefault();
        self.openGroupPermsFromBtn(btn);
      }
    });
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
      this.errEl.textContent = msg || 'Ralat tidak diketahui';
      this.errEl.classList.remove('d-none');
    }
  },
  
  // Minimal safe innerHTML helper used by this module.
  showContent_safeAssign(el, html) {
    if (!el) return;
    if (!html) { el.innerHTML = ''; return; }
    if (window.DOMPurify && typeof DOMPurify.sanitize === 'function') {
      el.innerHTML = DOMPurify.sanitize(html);
      return;
    }
    try {
      const doc = new DOMParser().parseFromString('<div>' + html + '</div>', 'text/html');
      doc.querySelectorAll('script').forEach(s => s.remove());
      doc.querySelectorAll('*').forEach(n => {
        Array.from(n.attributes).forEach(a => {
          if (/^on/i.test(a.name)) n.removeAttribute(a.name);
          if ((a.name === 'src' || a.name === 'href') && /^javascript:/i.test(a.value)) n.removeAttribute(a.name);
        });
      });
      el.innerHTML = doc.body.firstChild ? doc.body.firstChild.innerHTML : '';
    } catch (e) {
      el.innerHTML = html;
    }
  },

  showContent(html) {
    this.loadEl?.classList.add('d-none');
    this.errEl?.classList.add('d-none');
    if (this.cntEl) {
      this.showContent_safeAssign(this.cntEl, html);
      this.cntEl.classList.remove('d-none');
    }
  },

  getSummaryLabel() {
    return (window.T && window.T.userGroup_modal_summary_title) || 'Ringkasan';
  },

  renderSubtitleBar(text) {
    this.currentGroupSubtitle = String(text || '');
    if (!this.subEl) return;

    this.subEl.innerHTML = '';

    const wrap = document.createElement('div');
    wrap.className = 'd-flex align-items-center justify-content-between gap-3 flex-wrap w-100';

    const title = document.createElement('span');
    title.className = 'me-auto';
    title.textContent = this.currentGroupSubtitle;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'btnRingkasanGlobal';
    btn.className = 'btn btn-sm btn-outline-secondary';
    btn.innerHTML = '<i class="ri-file-list-3-line"></i> ' + GroupUtils.esc(this.getSummaryLabel());

    wrap.appendChild(title);
    wrap.appendChild(btn);
    this.subEl.appendChild(wrap);

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      this.openRingkasanModal();
    });
  },
  
  buildGroupPermsDT() {
    const modulesRaw = GroupState.getModulesRaw();
    const modulIDs = GroupState.getModulIDs();
    const rows = modulesRaw.map(m => {
      const checked = modulIDs.includes(parseInt(m.id, 10));
      return { id: parseInt(m.id, 10), nama: String(m.nama || ('Modul ' + m.id)), checked };
    });

    const html =
      '<table class="table table-striped table-bordered align-middle w-100" id="groupPermsDT">' +
      '<thead class="table-light"><tr>' +
      '<th class="col-check">#</th>' +
      '<th class="col-modul">Modul</th>' +
      '<th class="col-menu text-center">Menu</th>' +
      '</tr></thead><tbody></tbody>' +
      '</table>';

    this.showContent(html);

    const tbody = this.cntEl.querySelector('#groupPermsDT tbody');
    tbody.innerHTML = rows.map(r => {
      return '' +
        '<tr data-modul-id="' + GroupUtils.esc(r.id) + '" data-modul-nama="' + GroupUtils.esc(r.nama) + '">' +
        '<td class="text-center col-check">' +
        '<input class="form-check-input modul-check" type="checkbox" ' + (r.checked ? 'checked' : '') + ' aria-label="Pilih modul">' +
        '</td>' +
        '<td class="col-modul"><span class="fw-semibold">' + GroupUtils.esc(r.nama) + '</span></td>' +
        '<td class="text-center col-menu">' +
        '<button type="button" class="btn btn-sm btn-outline-success pick-menu"><i class="ri-list-check-2"></i> Menu</button>' +
        '</td>' +
        '</tr>';
    }).join('');

    if (GroupUtils.hasDataTable()) {
      const dt = jQuery('#groupPermsDT').DataTable({
        autoWidth: false,
        pageLength: 10,
        lengthChange: false,
        ordering: true,
        order: [[1, 'asc']],
        columns: [
          { width: '60px', orderable: false, searchable: false, className: 'text-center col-check' },
          { orderable: true, searchable: true, className: 'col-modul' },
          { width: '140px', orderable: false, searchable: false, className: 'text-center col-menu' }
        ],
        dom: 'rt' + '<"dt-bottom-row mt-2 d-flex justify-content-between align-items-center"<"dt-info-left"i><"dt-paging-right d-flex justify-content-end"p>>',
        language: { search: "", searchPlaceholder: "Cari…" },
        initComplete: () => {}
      });

      dt.columns.adjust().draw(false);

      jQuery('#aksesGroupModal')
        .off('shown.bs.modal.dtfix')
        .on('shown.bs.modal.dtfix', () => {
          if (jQuery.fn.dataTable.isDataTable('#groupPermsDT')) {
            jQuery('#groupPermsDT').DataTable().columns.adjust().draw(false);
          }
        });

      // Handlers
      jQuery('#groupPermsDT').off('click', '.pick-menu').on('click', '.pick-menu', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        const tr = e.currentTarget.closest('tr');
        if (!tr) return;
        const modulID = parseInt(tr.getAttribute('data-modul-id'), 10);
        const modulName = tr.getAttribute('data-modul-nama') || '';
        const modulIDs = GroupState.getModulIDs();
        if (!modulIDs.includes(modulID)) {
          const newModulIDs = [...modulIDs, modulID].sort((a, b) => a - b);
          GroupState.setModulIDs(newModulIDs);
          const chk = tr.querySelector('.modul-check');
          if (chk) chk.checked = true;
          await this.saveGroupPerms(false);
        }
        this.openMenuPickerModal(modulID, modulName);
      });

      jQuery('#groupPermsDT').off('change', '.modul-check').on('change', '.modul-check', function () {
        const tr = this.closest('tr');
        if (!tr) return;
        const modulID = parseInt(tr.getAttribute('data-modul-id'), 10);
        const checked = this.checked;
        const modulIDs = GroupState.getModulIDs();
        const set = new Set(modulIDs);
        if (checked) set.add(modulID);
        else set.delete(modulID);
        GroupState.setModulIDs(Array.from(set).sort((a, b) => a - b));

        if (!checked && Array.isArray(GroupState.getMenusByModul()[modulID])) {
          const delSet = new Set(GroupState.getMenusByModul()[modulID].map(x => parseInt(x.id, 10)));
          const menuIDs = GroupState.getMenuIDs().filter(id => !delSet.has(parseInt(id, 10)));
          GroupState.setMenuIDs(menuIDs);
        }
        GroupPermissions.saveGroupPerms(false);
      });
    }
  },
  
  async openMenuPickerModal(modulID, modulName) {
    const parentModal = GroupUtils.getModal(this.modalEl);
    const shouldRestoreParent = !!(parentModal && this.modalEl?.classList.contains('show'));
    this.restoreParentModalFromPick = shouldRestoreParent;
    if (shouldRestoreParent) parentModal.hide();
    if (!GroupUtils.showModalSafe(this.pickModalEl)) {
      this.restoreParentModalFromPick = false;
      if (shouldRestoreParent) parentModal?.show();
      return;
    }

    this.pickCntEl.classList.add('d-none');
    this.pickErrEl.classList.add('d-none');
    this.pickLoadEl.classList.remove('d-none');
    this.pickSubEl.textContent = modulName ? (' — ' + modulName) : '';

    try {
      const allMenus = await MenuAccess.fetchAllMenusStrict();
      const list = allMenus.filter(m => m.modulID === parseInt(modulID, 10));

      const cur = new Set(GroupState.getMenuIDs().map(id => parseInt(id, 10)));

      let html = '';
      if (!list.length) {
        html = '<div class="text-muted">Tiada menu aktif untuk modul ini.</div>';
      } else {
        html = '<div class="list-group">';
        list.forEach(m => {
          const on = cur.has(m.id);
          html += '<a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center menu-toggle" data-menu-id="' + GroupUtils.esc(m.id) + '">';
          html += '<span><i class="ri-pages-line me-2"></i>' + GroupUtils.esc(m.name) + (m.path ? ' <span class="menu-path">(' + GroupUtils.esc(m.path) + ')</span>' : '') + '</span>';
          html += '<span class="badge ' + (on ? 'bg-success' : 'bg-secondary') + '">' + (on ? 'ON' : 'OFF') + '</span>';
          html += '</a>';
        });
        html += '</div>';
      }

      this.showContent_safeAssign(this.pickCntEl, html);
      this.pickLoadEl.classList.add('d-none');
      this.pickCntEl.classList.remove('d-none');

      this.pickCntEl.querySelectorAll('.menu-toggle').forEach(a => {
        a.addEventListener('click', function (ev) {
          ev.preventDefault();
          const id = parseInt(this.getAttribute('data-menu-id'), 10);
          const b = this.querySelector('.badge');
          const menuIDs = GroupState.getMenuIDs().map(id => parseInt(id, 10));
          const cur = new Set(menuIDs);
          if (cur.has(id)) {
            cur.delete(id);
            b.className = 'badge bg-secondary';
            b.textContent = 'OFF';
          } else {
            cur.add(id);
            b.className = 'badge bg-success';
            b.textContent = 'ON';
          }
          GroupState.setMenuIDs(Array.from(cur));
          GroupPermissions.saveGroupPerms(false);
        });
      });
    } catch (e) {
      this.pickLoadEl.classList.add('d-none');
      this.pickErrEl.textContent = e.message || 'Ralat rangkaian';
      this.pickErrEl.classList.remove('d-none');
    }
  },
  
  async openRingkasanModal() {
    const parentModal = GroupUtils.getModal(this.modalEl);
    const shouldRestoreParent = !!(parentModal && this.modalEl?.classList.contains('show'));
    this.restoreParentModalFromRing = shouldRestoreParent;
    if (shouldRestoreParent) parentModal.hide();
    if (!GroupUtils.showModalSafe(this.ringModalEl)) {
      this.restoreParentModalFromRing = false;
      if (shouldRestoreParent) parentModal?.show();
      return;
    }
    this.ringCntEl.classList.add('d-none');
    this.ringErrEl.classList.add('d-none');
    this.ringLoadEl.classList.remove('d-none');

    try {
      const j = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('group-access.php', { groupID: GroupState.getGroupID() }));
      if (!j || j.error) throw new Error((j && j.message) || 'Gagal memuat ringkasan');

      const modules = Array.isArray(j.modules) ? j.modules : [];

      let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
      html += '<thead><tr><th style="width:220px">Modul</th><th>Menu</th></tr></thead><tbody>';

      if (!modules.length) {
        html += '<tr><td colspan="2" class="text-center text-muted">Tiada rekod</td></tr>';
      } else {
        modules.forEach(m => {
          const modulName = m.nama || m.modulName || ('Modul ' + (m.id || m.f_modulID));
          const menus = Array.isArray(m.menus) ? m.menus : [];

          let listHtml = '';
          if (!menus.length) {
            listHtml = '<span class="text-muted small">Tiada menu</span>';
          } else {
            listHtml = '<ul class="list-unstyled mb-0">';
            menus.forEach(me => {
              const nm = me.nama || me.menuName || me.kod || '-';
              const p = me.path || me.f_path || '';
              listHtml += '<li>' + GroupUtils.esc(nm) + (p ? ' <span class="menu-path">(' + GroupUtils.esc(p) + ')</span>' : '') + '</li>';
            });
            listHtml += '</ul>';
          }

          html += '<tr><td class="fw-semibold">' + GroupUtils.esc(modulName) + '</td><td>' + listHtml + '</td></tr>';
        });
      }

      html += '</tbody></table></div>';

      this.showContent_safeAssign(this.ringCntEl, html);
      this.ringLoadEl.classList.add('d-none');
      this.ringCntEl.classList.remove('d-none');
    } catch (e) {
      this.ringLoadEl.classList.add('d-none');
      this.ringErrEl.textContent = e.message || 'Ralat rangkaian';
      this.ringErrEl.classList.remove('d-none');
    }
  },
  
  async saveGroupPerms() {
    try {
      const j = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('group-perms-save.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': GroupUtils.getCSRF() },
        body: JSON.stringify({
          groupID: GroupState.getGroupID(),
          modulIDs: GroupState.getModulIDs(),
          menuIDs: GroupState.getMenuIDs()
        })
      });
      if (!j || j.error) {
        this.showError((j && j.message) || 'Gagal menyimpan');
      }
    } catch (e) {
      this.showError(e.message || 'Ralat rangkaian');
    }
  },
  
  async openGroupPermsFromBtn(btn) {
    const modal = GroupUtils.getModal(this.modalEl);
    if (!modal) return;
    const gid = btn.getAttribute('data-group-id');
    const gkod = btn.getAttribute('data-group-kod') || '';
    const gnam = btn.getAttribute('data-group-nama') || '';
    GroupState.setGroupID(gid);
    this.renderSubtitleBar(gkod + (gnam ? ' — ' + gnam : ''));
    this.showLoading();
    modal.show();

    try {
      const j = await GroupUtils.fetchJSONSafe(GroupUtils.apiUrl('group-perms-get.php', { groupID: GroupState.getGroupID() }));
      if (!j || j.error) {
        this.showError((j && j.message) || 'Gagal memuat');
        return;
      }

      const toNumArray = (v) => {
        if (Array.isArray(v)) return v.map(x => parseInt(x, 10)).filter(Number.isFinite);
        if (typeof v === 'string') return v.split(',').map(s => parseInt(String(s).trim(), 10)).filter(Number.isFinite);
        if (typeof v === 'number') return [v];
        return [];
      };

      GroupState.setModulIDs(toNumArray(j.modulIDs ?? j.f_modulAccess ?? j.modul_access));
      GroupState.setMenuIDs(toNumArray(j.menuIDs ?? j.f_menuAccess ?? j.menu_access));
      GroupState.setModulesRaw((j.modules || []).map(m => ({
        id: parseInt(m.id || m.f_modulID, 10),
        nama: String(m.nama || m.modulName || ('Modul ' + (m.id || m.f_modulID)))
      })));
      this.buildGroupPermsDT();
    } catch (e) {
      this.showError(e.message || 'Ralat rangkaian');
    }
  }
};

window.GroupPermissions = GroupPermissions;
