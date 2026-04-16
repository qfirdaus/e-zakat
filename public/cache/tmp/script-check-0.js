
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) {
      return;
    }

    const viewModalEl = document.getElementById('sampleViewModal');
    const viewModal = (window.bootstrap && viewModalEl) ? new bootstrap.Modal(viewModalEl) : null;
    const statsModalEl = document.getElementById('statsSummaryModal');
    const statsModal = (window.bootstrap && statsModalEl) ? new bootstrap.Modal(statsModalEl) : null;
    const departmentOptions = <?= json_encode(array_values($departmentOptions), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const currentDepartment = <?= json_encode((string)$currentDepartment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const distanceLookupUrl = <?= json_encode('bdr-distance.php?distance_lookup=1') ?>;
    const distancePrefetchUrl = <?= json_encode('bdr-distance.php?distance_prefetch=1') ?>;
    const calcQueueDelayMs = 300;
    const calcQueueBatchSize = 12;
    const distanceSourceMap = <?= json_encode([
      'route' => t('bdr_distance_source_route', 'Laluan'),
      'straight' => t('bdr_distance_source_straight', 'Garis Lurus'),
      'deferred_lookup' => t('bdr_distance_source_deferred', 'Belum Disemak'),
      'not_available' => t('bdr_distance_source_unavailable', 'Tidak Tersedia'),
      'address_only' => t('bdr_distance_source_address_only', 'Alamat Sahaja'),
      'cached_failed_lookup' => t('bdr_distance_source_failed_cache', 'Cache Gagal'),
      'google' => t('bdr_distance_provider_google', 'Google'),
      'geoapify' => t('bdr_distance_provider_geoapify', 'Geoapify'),
      'tomtom' => t('bdr_distance_provider_tomtom', 'TomTom'),
      'ors' => t('bdr_distance_provider_ors', 'OpenRouteService'),
      'osrm' => t('bdr_distance_provider_osrm', 'OSRM'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const debugReasonMap = <?= json_encode([
      'deferred_lookup' => t('bdr_distance_debug_deferred', 'Belum disemak lagi'),
      'address_empty' => t('bdr_distance_debug_address_empty', 'Tiada alamat'),
      'address_status_invalid' => t('bdr_distance_debug_address_invalid', 'Alamat tidak lengkap'),
      'lookup_request_failed' => t('bdr_distance_debug_lookup_failed', 'Permintaan lookup gagal'),
      'geocode_failed' => t('bdr_distance_debug_geocode_failed', 'Geocode gagal'),
      'route_failed' => t('bdr_distance_debug_route_failed', 'Laluan gagal dijana'),
      'low_match_quality' => t('bdr_distance_debug_low_match', 'Padanan lokasi rendah'),
      'ok' => t('bdr_distance_debug_ok', 'Berjaya'),
      'loaded_from_cache' => t('bdr_distance_debug_loaded_cache', 'Dibaca dari cache'),
      'cached_failed_lookup' => t('bdr_distance_debug_cached_failed', 'Kiraan terdahulu gagal'),
      'manual_calculation_required' => t('bdr_distance_debug_manual_required', 'Perlu kiraan manual'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const cacheOriginMap = <?= json_encode([
      'db' => t('bdr_distance_origin_db', 'DB'),
      'google_live' => t('bdr_distance_origin_google_live', 'Google Live'),
      'recalculated' => t('bdr_distance_origin_recalculated', 'Recalculated'),
      'none' => t('bdr_distance_origin_none', 'Pending'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const distanceState = {
      queue: [],
      pending: false,
      memory: new Map(),
      prefetching: false,
      queuedKeys: new Set()
    };

    function labelizeDistanceMeta(value, map) {
      const key = String(value || '').trim();
      if (!key) return '-';
      return map[key] || key;
    }

    function isDistanceCalculatedText(text) {
      const value = String(text || '').trim();
      if (!value) return false;
      if (value === <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>) return false;
      if (value === <?= json_encode(t('bdr_distance_status_calculating', 'Mengira...')) ?>) return false;
      if (value === <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>) return false;
      if (value === <?= json_encode(t('bdr_distance_status_invalid', 'Alamat Tak Lengkap')) ?>) return false;
      if (value === <?= json_encode(t('bdr_distance_not_calculated', 'Belum Dikira')) ?>) return false;
      return /[0-9]/.test(value);
    }

    function measureDepartmentFilterWidth(options, placeholder) {
      const probe = document.createElement('span');
      probe.style.visibility = 'hidden';
      probe.style.position = 'fixed';
      probe.style.left = '-9999px';
      probe.style.top = '-9999px';
      probe.style.whiteSpace = 'pre';
      probe.style.fontSize = '14px';
      probe.style.fontFamily = 'inherit';
      probe.style.fontWeight = '400';
      document.body.appendChild(probe);

      let longest = String(placeholder || '');
      options.forEach(function(optionText) {
        const text = String(optionText || '');
        if (text.length > longest.length) {
          longest = text;
        }
      });

      probe.textContent = longest;
      const width = Math.ceil(probe.getBoundingClientRect().width + 86);
      probe.remove();

      return Math.max(260, Math.min(width, 520));
    }

    function applyDepartmentFilterWidth($select, widthPx) {
      if (!$select || !$select.length) return;
      $select.css({
        width: widthPx + 'px',
        minWidth: widthPx + 'px',
        maxWidth: widthPx + 'px'
      });

      const $container = $select.next('.select2-container');
      if ($container.length) {
        $container.css({
          width: widthPx + 'px',
          minWidth: widthPx + 'px',
          maxWidth: widthPx + 'px'
        });
      }
    }

    const dt = jQuery('#userDT').DataTable({
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
        lengthMenu: <?= json_encode(t('bdr_distance_dt_length_menu', 'Papar _MENU_ rekod')) ?>,
        search: '',
        info: <?= json_encode(t('bdr_distance_dt_info', 'Memaparkan _START_ hingga _END_ daripada _TOTAL_ rekod')) ?>,
        infoEmpty: <?= json_encode(t('bdr_distance_dt_info_empty', 'Memaparkan 0 hingga 0 daripada 0 rekod')) ?>,
        emptyTable: <?= json_encode(t('bdr_distance_dt_empty', 'Tiada rekod staf aktif ditemui')) ?>,
        paginate: {
          previous: <?= json_encode(t('bdr_distance_dt_previous', 'Sebelum')) ?>,
          next: <?= json_encode(t('bdr_distance_dt_next', 'Seterusnya')) ?>
        },
        zeroRecords: <?= json_encode(t('bdr_distance_dt_zero_records', 'Tiada rekod sepadan ditemui')) ?>
      },
      columns: [
        { width: '5%', orderable: false, searchable: false },
        { width: '20%' },
        { width: '10%' },
        { width: '10%' },
        { width: '35%' },
        { width: '10%' },
        { width: '10%', orderable: false, searchable: false }
      ],
      rowCallback: function(row, data, displayIndex){
        const api = this.api();
        const info = api.page.info();
        jQuery('td:eq(0)', row).text(info.start + displayIndex + 1);
      },
      initComplete: function() {
        if (window.DataTableStandard && typeof window.DataTableStandard.decorate === 'function') {
          window.DataTableStandard.decorate('#userDT', {
            searchPlaceholder: <?= json_encode(t('bdr_distance_dt_search_placeholder', 'Cari staf')) ?>
          });
        }
      }
    });

    const $topLeft = jQuery('#userDT_wrapper .dt-top-left').addClass('d-flex align-items-center gap-2 flex-nowrap');
    const $topRight = jQuery('#userDT_wrapper .dt-top-right').addClass('d-flex align-items-center justify-content-end gap-1 flex-nowrap');
    const $filter = jQuery('#userDT_filter');
    jQuery('#dtDepartmentFilter').remove();
    jQuery('#dtDistanceFilter').remove();
    const $dept = jQuery('<select id="dtDepartmentFilter" class="form-select dt-group-filter"><option value=""><?= h(t('bdr_distance_department_filter_placeholder', 'Semua Jabatan')) ?></option></select>');
    const $distance = jQuery('<select id="dtDistanceFilter" class="form-select dt-group-filter dt-distance-filter"><option value=""><?= h(t('bdr_distance_filter_distance_placeholder', 'Semua Jarak')) ?></option><option value="lt8"><?= h(t('bdr_distance_filter_distance_lt8', '< 8 KM')) ?></option><option value="eq8"><?= h(t('bdr_distance_filter_distance_eq8', '= 8 KM')) ?></option><option value="gt8"><?= h(t('bdr_distance_filter_distance_gt8', '> 8 KM')) ?></option></select>');
    const departmentPlaceholder = <?= json_encode(t('bdr_distance_department_filter_placeholder', 'Semua Jabatan')) ?>;
    const departmentFilterWidth = measureDepartmentFilterWidth(departmentOptions, departmentPlaceholder);
    const distancePlaceholder = <?= json_encode(t('bdr_distance_filter_distance_placeholder', 'Semua Jarak')) ?>;
    const distanceFilterWidth = measureDepartmentFilterWidth([
      <?= json_encode(t('bdr_distance_filter_distance_lt8', '< 8 KM')) ?>,
      <?= json_encode(t('bdr_distance_filter_distance_eq8', '= 8 KM')) ?>,
      <?= json_encode(t('bdr_distance_filter_distance_gt8', '> 8 KM')) ?>
    ], distancePlaceholder);

    departmentOptions.forEach(function(name) {
      if (!name) return;
      $dept.append(new Option(String(name), String(name)));
    });

    if ($filter.length) {
      const $searchLabel = $filter.find('label');
      if ($searchLabel.length) {
        $searchLabel.before($dept);
        $dept.after($distance);
      } else {
        $filter.prepend($dept);
        $dept.after($distance);
      }
    } else {
      $topRight.append($dept);
      $topRight.append($distance);
    }

    function parseRowPayload(btn) {
      try {
        return JSON.parse(btn.getAttribute('data-row') || '{}');
      } catch (err) {
        return {};
      }
    }

    let departmentFilterValue = '';
    let distanceFilterValue = '';
    jQuery.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
      if (!settings || settings.nTable?.id !== 'userDT') return true;
      const rowNode = dt.row(dataIndex).node();
      const rowDepartment = rowNode ? String(rowNode.getAttribute('data-department') || '') : '';
      const kmText = rowNode ? String(rowNode.querySelector('.js-distance-cell')?.dataset.distanceKm || '') : '';
      const kmValue = parseFloat(kmText);

      if (departmentFilterValue && rowDepartment !== departmentFilterValue) {
        return false;
      }

      if (!distanceFilterValue) {
        return true;
      }

      if (!Number.isFinite(kmValue)) {
        return false;
      }

      if (distanceFilterValue === 'lt8') {
        return kmValue < 8;
      }
      if (distanceFilterValue === 'eq8') {
        return kmValue === 8;
      }
      if (distanceFilterValue === 'gt8') {
        return kmValue > 8;
      }

      return true;
    });

    $dept.off('change').on('change', function() {
      departmentFilterValue = this.value || '';
      dt.draw();
    });

    $distance.off('change').on('change', function() {
      distanceFilterValue = this.value || '';
      dt.draw();
    });

    function initDepartmentSelect2() {
      if (!(window.jQuery && jQuery.fn && typeof jQuery.fn.select2 === 'function')) {
        return false;
      }

      if ($dept.data('select2')) {
        $dept.select2('destroy');
      }

      $dept.select2({
        width: 'style',
        placeholder: departmentPlaceholder,
        allowClear: true,
        minimumResultsForSearch: 0
      });

      applyDepartmentFilterWidth($dept, departmentFilterWidth);
      return true;
    }

    if (!initDepartmentSelect2()) {
      const script = document.createElement('script');
      script.src = '<?= base_url('assets/vendor/select2/js/select2.full.min.js') ?>?v=<?= h($version) ?>';
      script.onload = function() {
        initDepartmentSelect2();
      };
      document.head.appendChild(script);
    } else {
      applyDepartmentFilterWidth($dept, departmentFilterWidth);
    }
    applyDepartmentFilterWidth($distance, distanceFilterWidth);

    function resolveDefaultDepartmentValue() {
      const target = String(currentDepartment || '').trim().toLowerCase();
      if (!target) return '';
      const normalize = function(text) {
        return String(text || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
      };
      const normalizedTarget = normalize(target);

      let matched = '';
      $dept.find('option').each(function() {
        const value = String(this.value || '').trim();
        if (!value) return;
        const normalizedValue = normalize(value);
        if (value.toLowerCase() === target || normalizedValue === normalizedTarget) {
          matched = value;
          return false;
        }
        if (!matched && (value.toLowerCase().includes(target) || target.includes(value.toLowerCase()) || (normalizedValue && normalizedTarget && (normalizedValue.includes(normalizedTarget) || normalizedTarget.includes(normalizedValue))))) {
          matched = value;
        }
      });

      return matched;
    }

    const defaultDepartmentValue = resolveDefaultDepartmentValue();
    if (defaultDepartmentValue) {
      $dept.val(defaultDepartmentValue);
      departmentFilterValue = defaultDepartmentValue;
    } else {
      $dept.val('');
      departmentFilterValue = '';
    }

    if ($dept.data('select2')) {
      $dept.trigger('change.select2');
    }

    function openViewModal(data) {
      if (!viewModal) return;
      document.getElementById('sampleViewName').textContent = data.name || '-';
      document.getElementById('sampleViewStaffNo').textContent = data.staff_no || '-';
      document.getElementById('sampleViewDepartment').textContent = data.department || '-';
      const activeRow = document.querySelector('tr[data-row-index="' + String(data.id - 1) + '"]');
      const activeDistanceCell = activeRow ? activeRow.querySelector('.js-distance-cell') : null;
      const rowAddressStatus = String(data.address_status || activeRow?.getAttribute('data-address-status') || '');
      document.getElementById('sampleViewDistance').textContent = activeDistanceCell ? activeDistanceCell.textContent.trim() || '-' : (data.distance_label || '-');
      const cacheOrigin = activeDistanceCell?.dataset.cacheOrigin || activeRow?.dataset.cacheOrigin || data.cache_origin || 'none';
      const badge = document.getElementById('sampleViewDataBadge');
      if (badge) {
        badge.textContent = cacheOriginMap[cacheOrigin] || cacheOriginMap.none;
        badge.className = 'distance-origin-badge distance-origin-badge--' + String(cacheOrigin || 'none').replace(/[^a-z0-9_-]/gi, '');
      }
      document.getElementById('sampleViewMatchedQuery').textContent = activeDistanceCell?.dataset.matchedQuery || activeRow?.dataset.matchedQuery || data.matched_query || '-';
      document.getElementById('sampleViewHomeCoords').textContent = activeDistanceCell?.dataset.homeCoords || activeRow?.dataset.homeCoords || data.home_coords_label || (rowAddressStatus === 'VALID' ? <?= json_encode(t('bdr_distance_not_generated', 'Belum Dijana')) ?> : '-');
      document.getElementById('sampleViewMatchQuality').textContent = activeDistanceCell?.dataset.matchQuality || activeRow?.dataset.matchQuality || data.match_quality || (rowAddressStatus === 'VALID' ? <?= json_encode(t('bdr_distance_not_calculated', 'Belum Dikira')) ?> : '-');
      const routeProvider = activeDistanceCell?.dataset.routeProvider || activeRow?.dataset.routeProvider || data.route_provider || '';
      const distanceSource = activeDistanceCell?.dataset.distanceSource || activeRow?.dataset.distanceSource || data.distance_source || (rowAddressStatus === 'VALID' ? 'deferred_lookup' : '-');
      const debugReason = activeDistanceCell?.dataset.debugReason || activeRow?.dataset.debugReason || data.debug_reason || (rowAddressStatus === 'VALID' ? 'deferred_lookup' : '-');
      const distanceSourceLabel = labelizeDistanceMeta(distanceSource, distanceSourceMap);
      const routeProviderLabel = labelizeDistanceMeta(routeProvider, distanceSourceMap);
      document.getElementById('sampleViewDistanceSource').textContent = routeProvider ? (distanceSourceLabel + ' / ' + routeProviderLabel) : distanceSourceLabel;
      document.getElementById('sampleViewDebugReason').textContent = labelizeDistanceMeta(debugReason, debugReasonMap);
      const addressStatus = String(data.address_status || '');
      const addressText = String(data.mailing_address || '-');
      document.getElementById('sampleViewMailingAddress').textContent = addressStatus === 'INVALID'
        ? addressText + ' (<?= h(t('bdr_distance_status_incomplete', 'Alamat tidak lengkap')) ?>)'
        : (addressStatus === 'EMPTY' ? '- (<?= h(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>)' : addressText);
      let routePoints = [];
      try {
        routePoints = JSON.parse(activeDistanceCell?.dataset.routePoints || activeRow?.dataset.routePoints || '[]');
      } catch (err) {
        routePoints = [];
      }
      renderRoutePreview(routePoints);
      viewModal.show();

      const currentDistanceText = activeDistanceCell ? String(activeDistanceCell.textContent || '').trim() : String(data.distance_label || '');
      if (activeRow && rowAddressStatus === 'VALID' && routePoints.length < 2 && isDistanceCalculatedText(currentDistanceText)) {
        const previewContainer = document.getElementById('sampleRoutePreview');
        if (previewContainer) {
          previewContainer.className = 'route-preview-empty';
          previewContainer.textContent = <?= json_encode(t('bdr_distance_route_preview_generating', 'Sedang menjana visual laluan...')) ?>;
        }

        lookupDistanceForRow(activeRow, true).then(function() {
          const refreshedCell = activeRow.querySelector('.js-distance-cell');
          let refreshedPoints = [];
          try {
            refreshedPoints = JSON.parse(refreshedCell?.dataset.routePoints || activeRow?.dataset.routePoints || '[]');
          } catch (err) {
            refreshedPoints = [];
          }
          renderRoutePreview(refreshedPoints);
        });
      }
    }

    function updateStatsSummary() {
      const rows = dt.rows({ search: 'applied' }).nodes().toArray();
      const stats = {
        total: 0,
        calculated: 0,
        failed: 0,
        empty: 0,
        invalid: 0,
        pending: 0,
        near: 0,
        equal: 0,
        far: 0
      };

      rows.forEach(function(row) {
        stats.total += 1;
        const addressStatus = String(row.getAttribute('data-address-status') || '');
        const cell = row.querySelector('.js-distance-cell');
        const text = String(cell ? cell.textContent.trim() : '');
        const km = parseFloat(String(cell?.dataset.distanceKm || '').replace(/[^0-9.]/g, ''));

        if (addressStatus === 'EMPTY') {
          stats.empty += 1;
          return;
        }

        if (addressStatus === 'INVALID') {
          stats.invalid += 1;
          return;
        }

        if (text === 'Gagal Kira') {
          stats.failed += 1;
          return;
        }

        if (Number.isFinite(km)) {
          stats.calculated += 1;
          if (km < 8) {
            stats.near += 1;
          } else if (km > 8) {
            stats.far += 1;
          } else {
            stats.equal += 1;
          }
          return;
        }

        stats.pending += 1;
      });

      document.getElementById('statsTotalRecords').textContent = String(stats.total);
      document.getElementById('statsCalculated').textContent = String(stats.calculated);
      document.getElementById('statsFailed').textContent = String(stats.failed);
      document.getElementById('statsEmptyAddress').textContent = String(stats.empty);
      document.getElementById('statsInvalidAddress').textContent = String(stats.invalid);
      document.getElementById('statsPending').textContent = String(stats.pending);
      document.getElementById('statsNear').textContent = String(stats.near);
      document.getElementById('statsEqual').textContent = String(stats.equal);
      document.getElementById('statsFar').textContent = String(stats.far);
    }

    jQuery(document).on('click', '.js-view-row', function(){
      openViewModal(parseRowPayload(this));
    });

    jQuery(document).on('click', '#openStatsSummary', function(){
      updateStatsSummary();
      if (statsModal) {
        statsModal.show();
      }
    });

    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
        new bootstrap.Tooltip(element);
      });
    }

    function updateDistanceCell(cell, payload) {
      if (!cell) return;
      const title = payload.title || cell.getAttribute('title') || '';
      const matchedQuery = String(payload.matched_query || '');
      const homeCoords = String(payload.home_coords_label || '');
      const matchQuality = String(payload.match_quality || '');
      const distanceSource = String(payload.distance_source || '');
      const routeProvider = String(payload.route_provider || '');
      const debugReason = String(payload.debug_reason || '');
      const cacheOrigin = String(payload.cache_origin || 'none');
      const routePoints = Array.isArray(payload.route_points) ? payload.route_points : [];
      const numericKm = payload.distance_label ? parseFloat(String(payload.distance_label).replace(/[^0-9.]/g, '')) : NaN;
      let distanceStateClass = '';
      if (Number.isFinite(numericKm)) {
        if (numericKm > 8) {
          distanceStateClass = 'distance-far';
        } else if (numericKm < 8) {
          distanceStateClass = 'distance-near';
        }
      } else if (String(payload.distance_label || '') === <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>) {
        distanceStateClass = 'distance-failed';
      } else if (String(payload.distance_label || '') === <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>) {
        distanceStateClass = 'distance-no-address';
      }
      if (payload.distance_label && payload.direction_url) {
        cell.outerHTML = '<a href="' + String(payload.direction_url).replace(/"/g, '&quot;') + '" target="_blank" rel="noopener noreferrer" class="distance-link truncate-1line js-distance-cell ' + distanceStateClass + '" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="' + String(title).replace(/"/g, '&quot;') + '">' + String(payload.distance_label) + '</a>';
      } else {
        cell.textContent = payload.distance_label || '-';
        cell.classList.remove('distance-pending');
        cell.classList.remove('distance-near', 'distance-far', 'distance-failed');
        if (distanceStateClass) {
          cell.classList.add(distanceStateClass);
        }
        cell.setAttribute('title', title);
      }

      const target = cell.closest('td')?.querySelector('.js-distance-cell');
      const row = cell.closest('tr');
      if (target) {
        target.dataset.matchedQuery = matchedQuery;
        target.dataset.homeCoords = homeCoords;
        target.dataset.matchQuality = matchQuality;
        target.dataset.distanceSource = distanceSource;
        target.dataset.cacheOrigin = cacheOrigin;
        target.dataset.routeProvider = routeProvider;
        target.dataset.debugReason = debugReason;
        target.dataset.routePoints = JSON.stringify(routePoints);
        target.dataset.distanceKm = Number.isFinite(numericKm) ? String(numericKm) : '';
        target.classList.remove('distance-near', 'distance-far', 'distance-failed', 'distance-no-address');
        if (distanceStateClass) {
          target.classList.add(distanceStateClass);
        }
      }
      if (row) {
        row.dataset.matchedQuery = matchedQuery;
        row.dataset.homeCoords = homeCoords;
        row.dataset.matchQuality = matchQuality;
        row.dataset.distanceSource = distanceSource;
        row.dataset.cacheOrigin = cacheOrigin;
        row.dataset.routeProvider = routeProvider;
        row.dataset.debugReason = debugReason;
        row.dataset.routePoints = JSON.stringify(routePoints);
        row.dataset.distanceAttempted = payload.distance_attempted === false ? '0' : '1';
        row.classList.remove('table-distance-near-soft');
        if (Number.isFinite(numericKm) && numericKm < 8) {
          row.classList.add('table-distance-near-soft');
        }
      }

      if (window.bootstrap) {
        if (target) {
          new bootstrap.Tooltip(target);
        }
      }
    }

    function lookupDistanceForRow(row, forceLookup) {
      if (!row) return Promise.resolve();
      if (row.dataset.distanceInflight === '1') return Promise.resolve();

      const cell = row.querySelector('.js-distance-cell');
      if (!cell) return Promise.resolve();

      const address = String(row.getAttribute('data-address') || '').trim();
      const addressStatus = String(row.getAttribute('data-address-status') || '').trim();
      if (!address) {
        updateDistanceCell(cell, {
          distance_label: <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>,
          direction_url: '',
          title: <?= json_encode(t('bdr_distance_msg_no_address', 'Jarak tidak dapat dikira kerana tiada alamat')) ?>,
          matched_query: '',
          home_coords_label: '',
          match_quality: 'NONE',
          distance_source: '',
          route_provider: '',
          debug_reason: 'address_empty',
          route_points: []
        });
        return Promise.resolve();
      }

      cell.textContent = <?= json_encode(t('bdr_distance_status_calculating', 'Mengira...')) ?>;
      cell.setAttribute('title', <?= json_encode(t('bdr_distance_msg_calculating', 'Sedang mengira jarak ke UPNM')) ?>);
      row.dataset.distanceInflight = '1';

      const formData = new FormData();
      formData.append('address', address);
      formData.append('address_status', addressStatus);
      formData.append('alamat1', String(row.getAttribute('data-alamat1') || ''));
      formData.append('alamat2', String(row.getAttribute('data-alamat2') || ''));
      formData.append('alamat3', String(row.getAttribute('data-alamat3') || ''));
      formData.append('poskod', String(row.getAttribute('data-poskod') || ''));
      formData.append('negeri', String(row.getAttribute('data-negeri') || ''));
      formData.append('negara', String(row.getAttribute('data-negara') || ''));
      formData.append('f_stafID', String(row.getAttribute('data-staf-id') || ''));
      formData.append('force', forceLookup ? '1' : '0');

      return fetch(distanceLookupUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function(response) {
        return response.json();
      })
      .then(function(payload) {
        const matchQuality = String(payload.match_quality || '');
        const message = String(payload.message || '');
        let distanceLabel = String(payload.distance_label || '');
        let title = '';
        if (matchQuality === 'LOW' || message === 'low_match_quality') {
          distanceLabel = <?= json_encode(t('bdr_distance_status_review_location', 'Semak Lokasi')) ?>;
          title = <?= json_encode(t('bdr_distance_msg_low_match', 'Lokasi geocode meragukan. Sila semak alamat dan koordinat.')) ?>;
        } else if (!distanceLabel) {
          title = <?= json_encode(t('bdr_distance_msg_unknown', 'Jarak belum dapat ditentukan')) ?>;
          if (addressStatus === 'VALID') {
            distanceLabel = <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>;
          }
        } else {
          title = String(payload.distance_source || '') === 'route'
            ? <?= json_encode(t('bdr_distance_msg_route', 'Jarak laluan pemanduan terdekat ke UPNM')) ?>
            : <?= json_encode(t('bdr_distance_msg_straight', 'Anggaran jarak garis lurus ke UPNM')) ?>;
        }

        updateDistanceCell(cell, {
          distance_label: distanceLabel,
          direction_url: String(payload.direction_url || cell.getAttribute('data-direction-url') || ''),
          title: title,
          matched_query: String(payload.matched_query || ''),
          home_coords_label: String(payload.home_coords_label || ''),
          match_quality: matchQuality,
          distance_source: String(payload.distance_source || ''),
          route_provider: String(payload.route_provider || ''),
          cache_origin: String(payload.cache_origin || 'none'),
          debug_reason: String(payload.debug_reason || ''),
          route_points: Array.isArray(payload.route_points) ? payload.route_points : [],
          distance_attempted: true
        });
      })
      .catch(function() {
        updateDistanceCell(cell, {
          distance_label: <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>,
          direction_url: String(cell.getAttribute('data-direction-url') || ''),
          title: <?= json_encode(t('bdr_distance_msg_lookup_failed', 'Lookup jarak gagal')) ?>,
          matched_query: '',
          home_coords_label: '',
          match_quality: 'NONE',
          distance_source: '',
          route_provider: '',
          cache_origin: 'none',
          debug_reason: 'lookup_request_failed',
          route_points: [],
          distance_attempted: true
        });
      })
      .finally(function() {
        row.dataset.distanceInflight = '0';
      });
    }

    function renderRoutePreview(routePoints) {
      const container = document.getElementById('sampleRoutePreview');
      if (!container) return;

      if (!Array.isArray(routePoints) || routePoints.length < 2) {
        container.className = 'route-preview-empty';
        container.textContent = <?= json_encode(t('bdr_distance_route_preview_empty', 'Tiada visual laluan')) ?>;
        return;
      }

      const width = 560;
      const height = 210;
      const pad = 16;
      const lats = routePoints.map(function(p) { return Number(p.lat || 0); });
      const lons = routePoints.map(function(p) { return Number(p.lon || 0); });
      const minLat = Math.min.apply(null, lats);
      const maxLat = Math.max.apply(null, lats);
      const minLon = Math.min.apply(null, lons);
      const maxLon = Math.max.apply(null, lons);
      const latSpan = Math.max(maxLat - minLat, 0.0001);
      const lonSpan = Math.max(maxLon - minLon, 0.0001);

      const project = function(point) {
        const x = pad + ((Number(point.lon || 0) - minLon) / lonSpan) * (width - (pad * 2));
        const y = height - pad - ((Number(point.lat || 0) - minLat) / latSpan) * (height - (pad * 2));
        return { x: x, y: y };
      };

      const svgPoints = routePoints.map(function(point) {
        const projected = project(point);
        return projected.x.toFixed(2) + ',' + projected.y.toFixed(2);
      }).join(' ');

      const start = project(routePoints[0]);
      const end = project(routePoints[routePoints.length - 1]);

      container.className = 'route-preview';
      container.innerHTML =
        '<svg viewBox="0 0 ' + width + ' ' + height + '" class="route-preview-svg" role="img" aria-label="<?= h(t('bdr_distance_route_preview_aria', 'Visual laluan provider')) ?>">' +
          '<rect x="0" y="0" width="' + width + '" height="' + height + '" rx="12" ry="12" fill="#f8fafc"></rect>' +
          '<polyline points="' + svgPoints + '" fill="none" stroke="#0f766e" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></polyline>' +
          '<circle cx="' + start.x.toFixed(2) + '" cy="' + start.y.toFixed(2) + '" r="6" fill="#2563eb"></circle>' +
          '<circle cx="' + end.x.toFixed(2) + '" cy="' + end.y.toFixed(2) + '" r="6" fill="#dc2626"></circle>' +
          '<text x="' + (start.x + 10).toFixed(2) + '" y="' + (start.y - 8).toFixed(2) + '" font-size="12" fill="#1e293b"><?= h(t('bdr_distance_route_label_home', 'RUMAH')) ?></text>' +
          '<text x="' + (end.x + 10).toFixed(2) + '" y="' + (end.y - 8).toFixed(2) + '" font-size="12" fill="#1e293b"><?= h(t('bdr_distance_route_label_office', 'UPNM')) ?></text>' +
        '</svg>';
    }

    jQuery(document).on('click', '.js-calc-row', function() {
      const row = this.closest('tr');
      lookupDistanceForRow(row, true);
    });

    function collectFilteredRows() {
      return dt.rows({ search: 'applied' }).nodes().toArray();
    }

    function buildRowKey(row) {
      return [
        String(row.getAttribute('data-row-index') || ''),
        String(row.getAttribute('data-staf-id') || ''),
        String(row.getAttribute('data-address') || '')
      ].join('|');
    }

    function getRowsNeedingLookup(rows) {
      return rows.filter(function(row) {
        const addressStatus = String(row.getAttribute('data-address-status') || '');
        const attempted = String(row.getAttribute('data-distance-attempted') || '') === '1';
        const inFlight = String(row.dataset.distanceInflight || '') === '1';
        const cacheOrigin = String(row.dataset.cacheOrigin || 'none');
        const cell = row.querySelector('.js-distance-cell');
        if (!cell) return false;
        if (addressStatus !== 'VALID') return false;
        if (attempted) return false;
        if (inFlight) return false;
        if (cacheOrigin === 'db') return false;
        if (String(cell.dataset.distanceKm || '').trim() !== '') return false;
        return true;
      });
    }

    function buildDistanceTitle(payload, addressStatus) {
      const matchQuality = String(payload.match_quality || '');
      const message = String(payload.message || '');
      const distanceLabel = String(payload.distance_label || '');
      if (matchQuality === 'LOW' || message === 'low_match_quality') {
        return {
          distanceLabel: <?= json_encode(t('bdr_distance_status_review_location', 'Semak Lokasi')) ?>,
          title: <?= json_encode(t('bdr_distance_msg_low_match', 'Lokasi geocode meragukan. Sila semak alamat dan koordinat.')) ?>
        };
      }
      if (!distanceLabel) {
        return {
          distanceLabel: addressStatus === 'VALID'
            ? <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>
            : '',
          title: <?= json_encode(t('bdr_distance_msg_unknown', 'Jarak belum dapat ditentukan')) ?>
        };
      }
      return {
        distanceLabel: distanceLabel,
        title: String(payload.distance_source || '') === 'route'
          ? <?= json_encode(t('bdr_distance_msg_route', 'Jarak laluan pemanduan terdekat ke UPNM')) ?>
          : <?= json_encode(t('bdr_distance_msg_straight', 'Anggaran jarak garis lurus ke UPNM')) ?>
      };
    }

    function applyPrefetchPayload(row, payload) {
      const cell = row ? row.querySelector('.js-distance-cell') : null;
      if (!row || !cell) return;
      const addressStatus = String(row.getAttribute('data-address-status') || '');
      const meta = buildDistanceTitle(payload, addressStatus);
      updateDistanceCell(cell, {
        distance_label: meta.distanceLabel,
        direction_url: String(payload.direction_url || cell.getAttribute('data-direction-url') || ''),
        title: meta.title,
        matched_query: String(payload.matched_query || ''),
        home_coords_label: String(payload.home_coords_label || ''),
        match_quality: String(payload.match_quality || ''),
        distance_source: String(payload.distance_source || ''),
        route_provider: String(payload.route_provider || ''),
        cache_origin: String(payload.cache_origin || 'db'),
        debug_reason: String(payload.debug_reason || ''),
        route_points: Array.isArray(payload.route_points) ? payload.route_points : [],
        distance_attempted: true
      });
    }

    function prefetchCachedRows(rows) {
      const candidates = rows.filter(function(row) {
        const address = String(row.getAttribute('data-address') || '').trim();
        const addressStatus = String(row.getAttribute('data-address-status') || '');
        const attempted = String(row.getAttribute('data-distance-attempted') || '') === '1';
        const inFlight = String(row.dataset.distanceInflight || '') === '1';
        return !!address && addressStatus === 'VALID' && !attempted && !inFlight;
      });

      if (!candidates.length || distanceState.prefetching) {
        return Promise.resolve();
      }

      distanceState.prefetching = true;
      const items = candidates.map(function(row) {
        return {
          row_key: buildRowKey(row),
          address: String(row.getAttribute('data-address') || ''),
          address_status: String(row.getAttribute('data-address-status') || ''),
          alamat1: String(row.getAttribute('data-alamat1') || ''),
          alamat2: String(row.getAttribute('data-alamat2') || ''),
          alamat3: String(row.getAttribute('data-alamat3') || ''),
          poskod: String(row.getAttribute('data-poskod') || ''),
          negeri: String(row.getAttribute('data-negeri') || ''),
          negara: String(row.getAttribute('data-negara') || '')
        };
      });

      return fetch(distancePrefetchUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ items: items })
      })
      .then(function(response) {
        return response.json();
      })
      .then(function(payload) {
        const resultItems = Array.isArray(payload.items) ? payload.items : [];
        const rowMap = new Map(candidates.map(function(row) {
          return [buildRowKey(row), row];
        }));
        resultItems.forEach(function(item) {
          const row = rowMap.get(String(item.row_key || ''));
          if (row) {
            applyPrefetchPayload(row, item);
          }
        });
      })
      .catch(function() {
      })
      .finally(function() {
        distanceState.prefetching = false;
        updateStatsSummary();
      });
    }

    function queueFilteredDistanceLookups() {
      const rows = getRowsNeedingLookup(collectFilteredRows()).slice(0, calcQueueBatchSize);
      let sequence = Promise.resolve();

      rows.forEach(function(row) {
        const rowKey = buildRowKey(row);
        if (distanceState.queuedKeys.has(rowKey)) {
          return;
        }

        distanceState.queuedKeys.add(rowKey);
        sequence = sequence.then(function() {
          return lookupDistanceForRow(row, false).then(function() {
            distanceState.queuedKeys.delete(rowKey);
            return new Promise(function(resolve) {
              window.setTimeout(resolve, calcQueueDelayMs);
            });
          });
        });
      });

      sequence.then(function() {
        if (getRowsNeedingLookup(collectFilteredRows()).length > 0) {
          window.setTimeout(function() {
            runDistanceRefreshCycle();
          }, calcQueueDelayMs);
        }
      });
    }

    function runDistanceRefreshCycle() {
      const rows = collectFilteredRows();
      prefetchCachedRows(rows).then(function() {
        queueFilteredDistanceLookups();
      });
    }

    window.addEventListener('resize', function() {
      applyDepartmentFilterWidth(jQuery('#dtDepartmentFilter'), departmentFilterWidth);
    });

    if (departmentFilterValue) {
      dt.draw();
    }
    window.setTimeout(function() {
      runDistanceRefreshCycle();
    }, 250);
    dt.on('draw', function() {
      window.setTimeout(function() {
        runDistanceRefreshCycle();
      }, 120);
      updateStatsSummary();
    });
    updateStatsSummary();
  });
})();
