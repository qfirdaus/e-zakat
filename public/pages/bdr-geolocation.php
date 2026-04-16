<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_login();

require_once __DIR__ . '/../controllers/BdrGeolocationController.php';

$isMapStaffData = isset($_GET['map_staff']) && $_GET['map_staff'] === '1';
$isMapStaffRoute = isset($_GET['map_route']) && $_GET['map_route'] === '1';
$isMapHeatData = isset($_GET['map_heat']) && $_GET['map_heat'] === '1';
$controllerClass = 'BdrGeolocationController';

if ($isMapStaffData || $isMapStaffRoute || $isMapHeatData) {
    try {
        $controller = new $controllerClass(true);
        if ($isMapHeatData) {
            $controller->handleMapHeatData();
        }
        if ($isMapStaffRoute) {
            $controller->handleMapStaffRoute();
        }
        $controller->handleMapStaffData();
    } catch (Throwable $e) {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        error_log('[bdr-geolocation] map endpoint bootstrap failed: ' . $e->getMessage());
        @file_put_contents(
            __DIR__ . '/../log/bdr-geolocation-map.log',
            json_encode([
                'event' => 'map_endpoint_bootstrap_failed',
                'timestamp' => date('c'),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => 'Data geolokasi staf tidak dapat dimuatkan buat masa ini.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

$controller = new $controllerClass();

$lang = $controller->lang ?? ($_SESSION['lang'] ?? 'ms');
$version = (string)($_ENV['APP_ASSET_VER'] ?? date('ymdHis'));
$siteOptions = function_exists('distance_site_registry') ? distance_site_registry() : [];
$activeSiteCode = function_exists('distance_normalize_site_code')
    ? distance_normalize_site_code((string)($_GET['site'] ?? ($_SESSION['bdr_distance_site'] ?? '')))
    : 'upnm_kampus';

if (!function_exists('h')) {
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('t')) {
    function t(string $key, string $fallback): string
    {
        $value = __($key);
        return ($value === $key || $value === null || $value === '') ? $fallback : (string)$value;
    }
}

$PAGE_TITLE = t('bdr_geolocation_page_title', 'Geolokasi');
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" data-bs-theme="<?= h($_SESSION['theme.layout'] ?? 'light') ?>">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <link href="<?= base_url('assets/vendor/leaflet/leaflet.css') ?>?v=<?= h($version) ?>" rel="stylesheet">
  <link href="<?= base_url('assets/vendor/leaflet.markercluster/MarkerCluster.css') ?>?v=<?= h($version) ?>" rel="stylesheet">
  <link href="<?= base_url('assets/vendor/leaflet.markercluster/MarkerCluster.Default.css') ?>?v=<?= h($version) ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/pages/bdr-geolocation.css') ?>?v=<?= h($version) ?>" rel="stylesheet">
</head>
<body data-topbar-color="<?= h($_SESSION['theme.topbar'] ?? 'light') ?>" data-menu-color="<?= h($_SESSION['theme.menu'] ?? 'light') ?>" data-layout="vertical" data-sidebar-size="default" class="loading">
<div class="wrapper">
  <?php include __DIR__ . '/../includes/topbar.php'; ?>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="content-page">
    <div class="content">
      <div class="container-fluid">
        <div class="row mb-3">
          <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap">
              <h4 class="page-title">
                <i class="ri-map-pin-line me-1"></i>
                <?= h($PAGE_TITLE) ?>
              </h4>
              <div class="page-title-right">
                <ol class="breadcrumb m-0">
                  <li class="breadcrumb-item">
                    <a href="dashboard.php">
                      <i class="ri-home-4-line align-middle me-1"></i>
                      <?= h(__('breadcrumb_home')) ?>
                    </a>
                  </li>
                  <li class="breadcrumb-item active"><?= h($PAGE_TITLE) ?></li>
                </ol>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm profile-card">
          <ul class="nav nav-tabs profile-tabs" role="tablist" aria-label="<?= h(t('bdr_geolocation_module_kicker', 'Tabbed Workspace')) ?>">
            <li class="nav-item">
              <a class="nav-link active" id="map-staff-tab" data-bs-toggle="tab" href="#map-staff-tab-pane" role="tab" aria-controls="map-staff-tab-pane" aria-selected="true">
                <i class="ri-map-pin-user-line me-1"></i> <?= h(t('bdr_geolocation_tab_map_staff', 'Map Staf')) ?>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="heatmap-staff-tab" data-bs-toggle="tab" href="#heatmap-staff-tab-pane" role="tab" aria-controls="heatmap-staff-tab-pane" aria-selected="false">
                <i class="ri-fire-line me-1"></i> <?= h(t('bdr_geolocation_tab_heatmap_staff', 'Heatmap Staf')) ?>
              </a>
            </li>
          </ul>

          <div class="tab-content p-4">
            <div class="tab-pane fade show active" id="map-staff-tab-pane" role="tabpanel" aria-labelledby="map-staff-tab" tabindex="0">
              <div class="geo-map-shell">
                <div class="geo-map-header">
                  <div>
                    <div class="geo-map-kicker"><?= h(t('bdr_geolocation_map_kicker', 'BDR Geolocation')) ?></div>
                    <h5 class="geo-map-title"><?= h(t('bdr_geolocation_map_title', 'Peta Geolokasi Staf')) ?></h5>
                    <p class="geo-map-subtitle mb-0"><?= h(t('bdr_geolocation_map_subtitle', 'Paparan lokasi rumah staf berdasarkan koordinat yang telah disimpan dalam pangkalan data.')) ?></p>
                  </div>
                  <div class="geo-map-header-side">
                    <div class="geo-map-badges">
                      <span class="geo-map-badge" id="bdrGeoMapSiteBadge">
                        <i class="ri-building-4-line"></i><?= h(t('bdr_geolocation_map_site_pending', 'Site aktif')) ?>
                      </span>
                      <span class="geo-map-badge geo-map-badge--muted" id="bdrGeoMapCountBadge">
                        <i class="ri-map-pin-line"></i><?= h(t('bdr_geolocation_map_count_pending', 'Belum dimuat')) ?>
                      </span>
                      <button type="button" class="btn btn-outline-primary btn-sm geo-map-pill-btn" id="bdrGeoMapReloadBtn">
                        <i class="ri-refresh-line me-1"></i><?= h(t('bdr_geolocation_map_reload', 'Muat Semula')) ?>
                      </button>
                      <button type="button" class="btn btn-outline-secondary btn-sm geo-map-pill-btn" id="bdrGeoMapFilterToggleBtn" aria-expanded="false" aria-controls="bdrGeoMapFilterPanel">
                        <i class="ri-filter-3-line me-1"></i><?= h(t('bdr_geolocation_filter_toggle', 'Filter')) ?>
                      </button>
                    </div>
                    <div class="geo-map-toolbar-note geo-map-toolbar-note--header">
                      <i class="ri-database-2-line"></i>
                      <?= h(t('bdr_geolocation_map_source_note', 'Data dipaparkan berdasarkan rekod geolokasi yang tersedia dalam sistem.')) ?>
                    </div>
                  </div>
                </div>
                <div class="geo-map-controls is-collapsed" id="bdrGeoMapFilterPanel">
                  <div class="geo-map-filter-grid">
                    <label class="geo-map-control">
                      <span class="geo-map-control-label"><?= h(t('bdr_geolocation_filter_site', 'Site')) ?></span>
                      <select class="form-select form-select-sm" id="bdrGeoMapSiteFilter">
                        <?php foreach ($siteOptions as $siteCode => $site): ?>
                          <?php $siteCode = (string)$siteCode; ?>
                          <option value="<?= h($siteCode) ?>" <?= $siteCode === $activeSiteCode ? 'selected' : '' ?>>
                            <?= h((string)($site['label'] ?? $siteCode)) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                    <label class="geo-map-control">
                      <span class="geo-map-control-label"><?= h(t('bdr_geolocation_filter_quality', 'Kualiti Padanan')) ?></span>
                      <select class="form-select form-select-sm" id="bdrGeoMapQualityFilter">
                        <option value=""><?= h(t('bdr_geolocation_filter_quality_all', 'Semua')) ?></option>
                        <option value="HIGH">HIGH</option>
                        <option value="MEDIUM">MEDIUM</option>
                        <option value="LOW">LOW</option>
                        <option value="NONE">NONE</option>
                      </select>
                    </label>
                    <label class="geo-map-control geo-map-control--search">
                      <span class="geo-map-control-label"><?= h(t('bdr_geolocation_filter_search', 'Carian')) ?></span>
                      <input type="search" class="form-control form-control-sm" id="bdrGeoMapSearch" placeholder="<?= h(t('bdr_geolocation_filter_search_placeholder', 'Cari no. staf, nama, jabatan atau alamat')) ?>">
                    </label>
                  </div>
                </div>
                <div class="geo-map-selected is-hidden" id="bdrGeoMapSelectedPanel"></div>
                <div class="geo-map-frame">
                  <div id="bdrStaffGeoMap" class="geo-map-canvas" aria-label="<?= h(t('bdr_geolocation_map_canvas_label', 'Peta geolokasi staf')) ?>"></div>
                  <button type="button" class="geo-map-fullscreen-btn" id="bdrGeoMapFullscreenBtn" aria-label="<?= h(t('bdr_geolocation_fullscreen_open', 'Paparan penuh')) ?>">
                    <i class="ri-fullscreen-line"></i>
                  </button>
                  <div class="geo-map-state" id="bdrGeoMapState">
                    <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                    <span><?= h(t('bdr_geolocation_map_waiting', 'Peta sedia untuk dimuatkan.')) ?></span>
                  </div>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="heatmap-staff-tab-pane" role="tabpanel" aria-labelledby="heatmap-staff-tab" tabindex="0">
              <div class="geo-map-shell geo-heatmap-shell">
                <div class="geo-map-header">
                  <div>
                    <div class="geo-map-kicker"><?= h(t('bdr_geolocation_heatmap_kicker', 'BDR Geolocation')) ?></div>
                    <h5 class="geo-map-title"><?= h(t('bdr_geolocation_heatmap_title', 'Heatmap Geolokasi Staf')) ?></h5>
                    <p class="geo-map-subtitle mb-0"><?= h(t('bdr_geolocation_heatmap_subtitle', 'Paparan kepadatan lokasi rumah staf berdasarkan rekod geolokasi yang tersedia dalam sistem.')) ?></p>
                  </div>
                  <div class="geo-map-header-side">
                    <div class="geo-map-badges">
                      <span class="geo-map-badge" id="bdrHeatMapSiteBadge">
                        <i class="ri-building-4-line"></i><?= h(t('bdr_geolocation_map_site_pending', 'Site aktif')) ?>
                      </span>
                      <span class="geo-map-badge geo-map-badge--muted" id="bdrHeatMapCountBadge">
                        <i class="ri-fire-line"></i><?= h(t('bdr_geolocation_map_count_pending', 'Belum dimuat')) ?>
                      </span>
                      <button type="button" class="btn btn-outline-primary btn-sm geo-map-pill-btn" id="bdrHeatMapReloadBtn">
                        <i class="ri-refresh-line me-1"></i><?= h(t('bdr_geolocation_map_reload', 'Muat Semula')) ?>
                      </button>
                      <button type="button" class="btn btn-outline-secondary btn-sm geo-map-pill-btn" id="bdrHeatMapFilterToggleBtn" aria-expanded="false" aria-controls="bdrHeatMapControlPanel">
                        <i class="ri-filter-3-line me-1"></i><?= h(t('bdr_geolocation_filter_toggle', 'Filter')) ?>
                      </button>
                    </div>
                    <div class="geo-map-toolbar-note geo-map-toolbar-note--header">
                      <i class="ri-database-2-line"></i>
                      <?= h(t('bdr_geolocation_map_source_note', 'Data dipaparkan berdasarkan rekod geolokasi yang tersedia dalam sistem.')) ?>
                    </div>
                  </div>
                </div>
                <div class="geo-map-controls is-collapsed" id="bdrHeatMapControlPanel">
                  <div class="geo-map-filter-grid">
                    <label class="geo-map-control">
                      <span class="geo-map-control-label"><?= h(t('bdr_geolocation_filter_site', 'Site')) ?></span>
                      <select class="form-select form-select-sm" id="bdrHeatMapSiteFilter">
                        <?php foreach ($siteOptions as $siteCode => $site): ?>
                          <?php $siteCode = (string)$siteCode; ?>
                          <option value="<?= h($siteCode) ?>" <?= $siteCode === $activeSiteCode ? 'selected' : '' ?>>
                            <?= h((string)($site['label'] ?? $siteCode)) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                  </div>
                </div>
                <div class="geo-map-frame geo-heatmap-frame">
                  <div id="bdrStaffHeatMap" class="geo-map-canvas" aria-label="<?= h(t('bdr_geolocation_heatmap_canvas_label', 'Heatmap geolokasi staf')) ?>"></div>
                  <button type="button" class="geo-map-fullscreen-btn" id="bdrHeatMapFullscreenBtn" aria-label="<?= h(t('bdr_geolocation_fullscreen_open', 'Paparan penuh')) ?>">
                    <i class="ri-fullscreen-line"></i>
                  </button>
                  <div class="geo-map-state is-empty" id="bdrHeatMapState">
                    <i class="ri-information-line"></i>
                    <span><?= h(t('bdr_geolocation_heatmap_waiting', 'Heatmap akan dimuatkan apabila fungsi diaktifkan.')) ?></span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/script.php'; ?>
<script src="<?= base_url('assets/vendor/leaflet/leaflet.js') ?>?v=<?= h($version) ?>"></script>
<script src="<?= base_url('assets/vendor/leaflet.markercluster/leaflet.markercluster.js') ?>?v=<?= h($version) ?>"></script>
<script src="<?= base_url('assets/vendor/leaflet.heat/leaflet-heat.js') ?>?v=<?= h($version) ?>"></script>
<script>
(function () {
  window.BdrGeolocationMapConfig = {
    endpoint: <?= json_encode('bdr-geolocation.php?map_staff=1', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    routeEndpoint: <?= json_encode('bdr-geolocation.php?map_route=1', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    heatEndpoint: <?= json_encode('bdr-geolocation.php?map_heat=1', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    activeSiteCode: <?= json_encode($activeSiteCode, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    sites: <?= json_encode($siteOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    defaultCenter: [3.1390, 101.6869],
    defaultZoom: 10,
    noImageUrl: <?= json_encode(base_url('assets/images/no-image.jpg'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    officeLogoUrl: <?= json_encode(base_url('assets/images/logo-sm.png'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    tileUrl: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
  };
  var geoMap = null;
  var geoMarkerLayer = null;
  var geoRouteLayer = null;
  var geoOfficeMarker = null;
  var geoOfficeRadiusCircle = null;
  var geoRouteAnimationFrame = null;
  var geoRouteAnimationTimeout = null;
  var geoRouteAnimatedMarker = null;
  var geoMapRecords = [];
  var geoMapCurrentSiteMeta = null;
  var geoMapCurrentSite = window.BdrGeolocationMapConfig.activeSiteCode || 'upnm_kampus';
  var geoMapInitialized = false;
  var geoMapDataLoaded = false;
  var geoMapInflight = false;
  var geoMapSearchTimer = null;
  var geoHeatMap = null;
  var geoHeatLayer = null;
  var geoHeatOfficeMarker = null;
  var geoHeatInitialized = false;
  var geoHeatInflight = false;
  var geoHeatRecordsCache = {};
  var geoHeatSiteMetaCache = {};
  var geoHeatStaffTotalCache = {};
  var geoHeatCurrentSite = window.BdrGeolocationMapConfig.activeSiteCode || 'upnm_kampus';

  function getOfficeMarkerIcon() {
    if (!window.L || typeof L.divIcon !== 'function') return undefined;

    var logoUrl = escapeHtml(window.BdrGeolocationMapConfig.officeLogoUrl || '');
    return L.divIcon({
      className: 'geo-office-marker',
      html: '<span><img src="' + logoUrl + '" alt=""></span>',
      iconSize: [34, 34],
      iconAnchor: [17, 17],
      popupAnchor: [0, -24]
    });
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function setGeoMapState(type, message) {
    var stateEl = document.getElementById('bdrGeoMapState');
    if (!stateEl) return;

    var shouldHide = type === 'ready';
    stateEl.classList.toggle('is-hidden', shouldHide);
    stateEl.classList.toggle('is-error', type === 'error');
    stateEl.classList.toggle('is-empty', type === 'empty');

    if (!shouldHide) {
      var spinner = type === 'loading'
        ? '<div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>'
        : '<i class="' + (type === 'error' ? 'ri-error-warning-line' : 'ri-information-line') + '"></i>';
      stateEl.innerHTML = spinner + '<span>' + escapeHtml(message || '') + '</span>';
    }
  }

  function setGeoMapBadges(site, total) {
    var siteBadge = document.getElementById('bdrGeoMapSiteBadge');
    var countBadge = document.getElementById('bdrGeoMapCountBadge');
    if (siteBadge) {
      var siteLabel = site && site.office_label ? site.office_label : <?= json_encode(t('bdr_geolocation_map_site_pending', 'Site aktif')) ?>;
      siteBadge.innerHTML = '<i class="ri-building-4-line"></i>' + escapeHtml(siteLabel);
    }
    if (countBadge) {
      var totalText = Number.isFinite(Number(total)) ? String(Number(total)) : '0';
      countBadge.innerHTML = '<i class="ri-map-pin-line"></i>' + escapeHtml(totalText + ' ' + <?= json_encode(t('bdr_geolocation_map_record_suffix', 'rekod')) ?>);
    }
  }

  function setGeoMapStats(total, visible, review) {
    var totalEl = document.getElementById('bdrGeoMapStatTotal');
    var visibleEl = document.getElementById('bdrGeoMapStatVisible');
    var reviewEl = document.getElementById('bdrGeoMapStatReview');
    if (totalEl) totalEl.textContent = String(Number(total) || 0);
    if (visibleEl) visibleEl.textContent = String(Number(visible) || 0);
    if (reviewEl) reviewEl.textContent = String(Number(review) || 0);
  }

  function setGeoHeatMapState(type, message) {
    var stateEl = document.getElementById('bdrHeatMapState');
    if (!stateEl) return;

    var shouldHide = type === 'ready';
    stateEl.classList.toggle('is-hidden', shouldHide);
    stateEl.classList.toggle('is-error', type === 'error');
    stateEl.classList.toggle('is-empty', type === 'empty');

    if (!shouldHide) {
      var spinner = type === 'loading'
        ? '<div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>'
        : '<i class="' + (type === 'error' ? 'ri-error-warning-line' : 'ri-information-line') + '"></i>';
      stateEl.innerHTML = spinner + '<span>' + escapeHtml(message || '') + '</span>';
    }
  }

  function setGeoHeatMapBadges(site, pointTotal, staffTotal) {
    var siteBadge = document.getElementById('bdrHeatMapSiteBadge');
    var countBadge = document.getElementById('bdrHeatMapCountBadge');
    if (siteBadge) {
      var siteLabel = site && site.office_label ? site.office_label : <?= json_encode(t('bdr_geolocation_map_site_pending', 'Site aktif')) ?>;
      siteBadge.innerHTML = '<i class="ri-building-4-line"></i>' + escapeHtml(siteLabel);
    }
    if (countBadge) {
      var total = Number.isFinite(Number(staffTotal)) ? Number(staffTotal) : Number(pointTotal || 0);
      var points = Number.isFinite(Number(pointTotal)) ? Number(pointTotal) : 0;
      var pointLabel = points === 1
        ? <?= json_encode(t('bdr_geolocation_heatmap_point_singular', 'point')) ?>
        : <?= json_encode(t('bdr_geolocation_heatmap_point_plural', 'points')) ?>;
      countBadge.innerHTML = '<i class="ri-fire-line"></i>' + escapeHtml(total + ' ' + <?= json_encode(t('bdr_geolocation_map_record_suffix', 'rekod')) ?> + ' / ' + points + ' ' + pointLabel);
    }
  }

  function extractLatLngPair(value) {
    if (!value || typeof value !== 'object') return null;

    var lat = Number(value.lat);
    var lng = Number(value.lng != null ? value.lng : value.lon);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return null;

    return [lat, lng];
  }

  function getOfficeLatLng(site, record) {
    var siteCoords = site && site.office_coords ? extractLatLngPair(site.office_coords) : null;
    if (siteCoords) return siteCoords;

    if (record && record.office_lat != null && record.office_lng != null) {
      var lat = Number(record.office_lat);
      var lng = Number(record.office_lng);
      if (Number.isFinite(lat) && Number.isFinite(lng)) {
        return [lat, lng];
      }
    }

    return null;
  }

  function getOfficeLabel(site) {
    return site && site.office_label ? String(site.office_label) : 'Office';
  }

  function focusOffice(site, shouldCenter) {
    if (!geoMap) return;

    var officeLatLng = getOfficeLatLng(site || geoMapCurrentSiteMeta, null);
    if (!officeLatLng) {
      geoMap.setView(window.BdrGeolocationMapConfig.defaultCenter, window.BdrGeolocationMapConfig.defaultZoom);
      return;
    }

    if (geoOfficeMarker) {
      geoOfficeMarker.setLatLng(officeLatLng);
      geoOfficeMarker.setIcon(getOfficeMarkerIcon());
    } else {
      geoOfficeMarker = L.marker(officeLatLng, {
        icon: getOfficeMarkerIcon(),
        interactive: true,
        title: getOfficeLabel(site),
        zIndexOffset: 1000
      }).addTo(geoMap);
    }
    geoOfficeMarker.bindPopup('<div class="geo-map-popup"><div class="geo-map-popup__name">' + escapeHtml(getOfficeLabel(site)) + '</div><div class="geo-map-popup__meta">' + escapeHtml((site && site.office_address) || '') + '</div></div>');
    geoOfficeMarker.off('click');
    geoOfficeMarker.on('click', function () {
      if (geoOfficeRadiusCircle) {
        geoMap.fitBounds(geoOfficeRadiusCircle.getBounds(), { padding: [42, 42], maxZoom: 13 });
      } else {
        geoMap.setView(officeLatLng, 13);
      }
      geoOfficeMarker.openPopup();
    });

    if (geoOfficeRadiusCircle) {
      geoOfficeRadiusCircle.setLatLng(officeLatLng);
      geoOfficeRadiusCircle.bringToFront();
    } else {
      geoOfficeRadiusCircle = L.circle(officeLatLng, {
        radius: 8000,
        color: '#dc2626',
        weight: 4,
        opacity: 0.98,
        fillColor: '#f97316',
        fillOpacity: 0.12,
        dashArray: '10 8',
        interactive: false
      }).addTo(geoMap);
      geoOfficeRadiusCircle.bringToFront();
    }

    if (shouldCenter !== false) {
      geoMap.setView(officeLatLng, 13);
    }
  }

  function clearGeoRoute() {
    if (geoRouteAnimationFrame !== null) {
      window.cancelAnimationFrame(geoRouteAnimationFrame);
      geoRouteAnimationFrame = null;
    }
    if (geoRouteAnimationTimeout !== null) {
      window.clearTimeout(geoRouteAnimationTimeout);
      geoRouteAnimationTimeout = null;
    }
    geoRouteAnimatedMarker = null;
    if (geoRouteLayer) {
      geoRouteLayer.clearLayers();
    }
  }

  function getRouteAvatarIcon(record) {
    if (!window.L || typeof L.divIcon !== 'function') return undefined;

    var avatarUrl = escapeHtml((record && record.avatar_url) || window.BdrGeolocationMapConfig.noImageUrl || '');
    var noImageUrl = escapeHtml(window.BdrGeolocationMapConfig.noImageUrl || '');

    return L.divIcon({
      className: 'geo-route-avatar-marker',
      html: '<span><img src="' + avatarUrl + '" alt="" onerror="this.onerror=null;this.src=\'' + noImageUrl + '\';"></span>',
      iconSize: [28, 28],
      iconAnchor: [14, 14]
    });
  }

  function getRouteWorkClockIcon() {
    if (!window.L || typeof L.divIcon !== 'function') return undefined;

    return L.divIcon({
      className: 'geo-route-clock-marker',
      html: '<span><i></i><b></b><em>8-5</em></span>',
      iconSize: [36, 36],
      iconAnchor: [18, 18]
    });
  }

  function getRouteRestIcon() {
    if (!window.L || typeof L.divIcon !== 'function') return undefined;

    return L.divIcon({
      className: 'geo-route-rest-marker',
      html: '<span><i class="ri-moon-clear-line"></i><b class="ri-sun-line"></b></span>',
      iconSize: [36, 36],
      iconAnchor: [18, 18]
    });
  }

  function distanceBetweenLatLngs(a, b) {
    var dx = Number(b[0]) - Number(a[0]);
    var dy = Number(b[1]) - Number(a[1]);
    return Math.sqrt((dx * dx) + (dy * dy));
  }

  function interpolateRoutePosition(segments, total, progress) {
    progress = Math.max(0, Math.min(1, Number(progress) || 0));
    var targetDistance = progress * total;
    var travelled = 0;
    var active = segments[segments.length - 1];

    for (var j = 0; j < segments.length; j += 1) {
      if (travelled + segments[j].length >= targetDistance) {
        active = segments[j];
        break;
      }
      travelled += segments[j].length;
    }

    var localProgress = active.length > 0 ? (targetDistance - travelled) / active.length : 0;
    localProgress = Math.max(0, Math.min(1, localProgress));

    return [
      Number(active.from[0]) + ((Number(active.to[0]) - Number(active.from[0])) * localProgress),
      Number(active.from[1]) + ((Number(active.to[1]) - Number(active.from[1])) * localProgress)
    ];
  }

  function easeInOutRoute(value) {
    value = Math.max(0, Math.min(1, Number(value) || 0));
    return value * value * (3 - (2 * value));
  }

  function startRouteAvatarAnimation(latLngs, record) {
    if (!geoMap || !geoRouteLayer || !Array.isArray(latLngs) || latLngs.length < 2) return;

    var segments = [];
    var total = 0;
    for (var i = 0; i < latLngs.length - 1; i += 1) {
      var length = distanceBetweenLatLngs(latLngs[i], latLngs[i + 1]);
      if (length <= 0) continue;
      segments.push({ from: latLngs[i], to: latLngs[i + 1], length: length });
      total += length;
    }
    if (total <= 0 || segments.length === 0) return;

    geoRouteAnimatedMarker = L.marker(latLngs[0], {
      icon: getRouteAvatarIcon(record),
      interactive: false,
      zIndexOffset: 1200
    }).addTo(geoRouteLayer);

    var travelDuration = Math.max(12000, Math.min(30000, latLngs.length * 210));
    var workDuration = 5000;
    var restDuration = 5000;

    function animateTravel(fromProgress, toProgress, duration, done) {
      var startedAt = null;

      function step(timestamp) {
        if (!geoRouteAnimatedMarker) return;
        if (startedAt === null) startedAt = timestamp;

        var elapsed = Math.min(duration, timestamp - startedAt);
        var progress = easeInOutRoute(elapsed / duration);
        var routeProgress = fromProgress + ((toProgress - fromProgress) * progress);
        geoRouteAnimatedMarker.setLatLng(interpolateRoutePosition(segments, total, routeProgress));

        if (elapsed >= duration) {
          geoRouteAnimationFrame = null;
          if (typeof done === 'function') done();
          return;
        }

        geoRouteAnimationFrame = window.requestAnimationFrame(step);
      }

      geoRouteAnimationFrame = window.requestAnimationFrame(step);
    }

    function holdAtOffice() {
      if (!geoRouteAnimatedMarker) return;
      geoRouteAnimatedMarker.setLatLng(latLngs[latLngs.length - 1]);
      geoRouteAnimatedMarker.setIcon(getRouteWorkClockIcon());
      geoRouteAnimationTimeout = window.setTimeout(function () {
        if (!geoRouteAnimatedMarker) return;
        geoRouteAnimatedMarker.setIcon(getRouteAvatarIcon(record));
        animateTravel(1, 0, travelDuration, holdAtHome);
      }, workDuration);
    }

    function holdAtHome() {
      if (!geoRouteAnimatedMarker) return;
      geoRouteAnimatedMarker.setLatLng(latLngs[0]);
      geoRouteAnimatedMarker.setIcon(getRouteRestIcon());
      geoRouteAnimationTimeout = window.setTimeout(function () {
        if (!geoRouteAnimatedMarker) return;
        geoRouteAnimatedMarker.setIcon(getRouteAvatarIcon(record));
        animateTravel(0, 1, travelDuration, holdAtOffice);
      }, restDuration);
    }

    animateTravel(0, 1, travelDuration, holdAtOffice);
  }

  function buildGeoMapEndpoint() {
    var url = new URL(window.BdrGeolocationMapConfig.endpoint, window.location.href);
    url.searchParams.set('site', geoMapCurrentSite || 'upnm_kampus');
    return url.toString();
  }

  function buildGeoHeatEndpoint() {
    var url = new URL(window.BdrGeolocationMapConfig.heatEndpoint, window.location.href);
    url.searchParams.set('site', geoHeatCurrentSite || 'upnm_kampus');
    return url.toString();
  }

  function getHeatRadiusByZoom(zoom) {
    zoom = Number(zoom) || window.BdrGeolocationMapConfig.defaultZoom;
    if (zoom <= 8) return 15;
    if (zoom <= 10) return 20;
    if (zoom <= 12) return 25;
    return 32;
  }

  function syncGeoHeatRadius() {
    if (!geoHeatMap || !geoHeatLayer || typeof geoHeatLayer.setOptions !== 'function') return;
    geoHeatLayer.setOptions({ radius: getHeatRadiusByZoom(geoHeatMap.getZoom()) });
  }

  function updateFullscreenButton() {
    var button = document.getElementById('bdrGeoMapFullscreenBtn');
    var frame = document.querySelector('.geo-map-frame');
    if (!button || !frame) return;

    var isFullscreen = document.fullscreenElement === frame;
    button.innerHTML = isFullscreen
      ? '<i class="ri-fullscreen-exit-line"></i>'
      : '<i class="ri-fullscreen-line"></i>';
    button.setAttribute('aria-label', isFullscreen
      ? <?= json_encode(t('bdr_geolocation_fullscreen_close', 'Keluar paparan penuh')) ?>
      : <?= json_encode(t('bdr_geolocation_fullscreen_open', 'Paparan penuh')) ?>);
  }

  function toggleGeoMapFullscreen() {
    var frame = document.querySelector('.geo-map-frame');
    if (!frame) return;

    if (document.fullscreenElement === frame) {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      }
      return;
    }

    if (frame.requestFullscreen) {
      frame.requestFullscreen();
    }
  }

  function updateHeatMapFullscreenButton() {
    var button = document.getElementById('bdrHeatMapFullscreenBtn');
    var frame = document.querySelector('.geo-heatmap-frame');
    if (!button || !frame) return;

    var isFullscreen = document.fullscreenElement === frame;
    button.innerHTML = isFullscreen
      ? '<i class="ri-fullscreen-exit-line"></i>'
      : '<i class="ri-fullscreen-line"></i>';
    button.setAttribute('aria-label', isFullscreen
      ? <?= json_encode(t('bdr_geolocation_fullscreen_close', 'Keluar paparan penuh')) ?>
      : <?= json_encode(t('bdr_geolocation_fullscreen_open', 'Paparan penuh')) ?>);
  }

  function toggleHeatMapFullscreen() {
    var frame = document.querySelector('.geo-heatmap-frame');
    if (!frame) return;

    if (document.fullscreenElement === frame) {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      }
      return;
    }

    if (frame.requestFullscreen) {
      frame.requestFullscreen();
    }
  }

  function buildGeoRouteEndpoint(record) {
    var url = new URL(window.BdrGeolocationMapConfig.routeEndpoint, window.location.href);
    url.searchParams.set('site', geoMapCurrentSite || 'upnm_kampus');
    url.searchParams.set('staff_no', record && record.staff_no ? String(record.staff_no) : '');
    return url.toString();
  }

  function getGeoMapFilters() {
    var qualityEl = document.getElementById('bdrGeoMapQualityFilter');
    var searchEl = document.getElementById('bdrGeoMapSearch');

    return {
      quality: qualityEl ? String(qualityEl.value || '').toUpperCase() : '',
      search: searchEl ? String(searchEl.value || '').trim().toLowerCase() : ''
    };
  }

  function normalizeGeoQuality(record) {
    var quality = String(record && record.match_quality ? record.match_quality : '').trim().toUpperCase();
    return quality || 'NONE';
  }

  function recordMatchesGeoSearch(record, query) {
    if (!query) return true;

    var haystack = [
      record.staff_no,
      record.name,
      record.department,
      record.department_code,
      record.position,
      record.address,
      record.matched_address,
      record.postcode,
      record.state,
      record.country,
      record.distance_km,
      record.match_quality
    ].map(function (value) {
      return String(value == null ? '' : value).toLowerCase();
    }).join(' ');

    return haystack.indexOf(query) !== -1;
  }

  function getStaffDisplayName(record) {
    var name = String(record && record.name ? record.name : '').trim();
    var staffNo = String(record && record.staff_no ? record.staff_no : '').trim();
    if (name && staffNo) return name + ' (' + staffNo + ')';
    return name || staffNo || '-';
  }

  function filterGeoMapRecords(records) {
    var filters = getGeoMapFilters();
    return (Array.isArray(records) ? records : []).filter(function (record) {
      if (filters.quality && normalizeGeoQuality(record) !== filters.quality) {
        return false;
      }

      return recordMatchesGeoSearch(record, filters.search);
    });
  }

  function applyGeoMapFilters(options) {
    options = options || {};
    var filtered = filterGeoMapRecords(geoMapRecords);
    var reviewCount = filtered.filter(function (record) {
      return record && (record.is_review_location || normalizeGeoQuality(record) === 'LOW');
    }).length;

    setGeoMapStats(geoMapRecords.length, filtered.length, reviewCount);
    addGeoMapMarkers(filtered, options);
  }

  function initBdrStaffGeoMap() {
    if (geoMapInitialized) {
      if (geoMap && typeof geoMap.invalidateSize === 'function') {
        window.setTimeout(function () { geoMap.invalidateSize(); }, 80);
      }
      if (!geoMapDataLoaded && !geoMapInflight) {
        loadBdrStaffGeoMapData(false);
      }
      return;
    }

    if (!window.L || typeof L.map !== 'function') {
      setGeoMapState('error', <?= json_encode(t('bdr_geolocation_map_lib_missing', 'Library peta tidak dapat dimuatkan.')) ?>);
      return;
    }

    var mapEl = document.getElementById('bdrStaffGeoMap');
    if (!mapEl) return;

    geoMap = L.map(mapEl, {
      preferCanvas: true,
      scrollWheelZoom: true
    }).setView(window.BdrGeolocationMapConfig.defaultCenter, window.BdrGeolocationMapConfig.defaultZoom);

    L.tileLayer(window.BdrGeolocationMapConfig.tileUrl, {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(geoMap);

    geoMarkerLayer = typeof L.markerClusterGroup === 'function'
      ? L.markerClusterGroup({
          chunkedLoading: true,
          maxClusterRadius: 48
        })
      : L.layerGroup();
    geoRouteLayer = L.layerGroup();

    geoMap.addLayer(geoMarkerLayer);
    geoMap.addLayer(geoRouteLayer);
    geoMapInitialized = true;
    window.setTimeout(function () { geoMap.invalidateSize(); }, 120);
    loadBdrStaffGeoMapData(false);
  }

  function buildGeoStaffDetail(record) {
    var lines = [];
    lines.push('<div class="geo-map-popup">');
    lines.push('<div class="geo-map-popup__avatar-wrap"><img class="geo-map-popup__avatar" src="' + escapeHtml(record.avatar_url || window.BdrGeolocationMapConfig.noImageUrl || '') + '" alt="" onerror="this.onerror=null;this.src=\'' + escapeHtml(window.BdrGeolocationMapConfig.noImageUrl || '') + '\';"></div>');
    lines.push('<div class="geo-map-popup__body">');
    lines.push('<div class="geo-map-popup__name">' + escapeHtml(getStaffDisplayName(record)) + '</div>');
    if (record.department) {
      lines.push('<div><strong><?= h(t('bdr_geolocation_popup_department', 'Jabatan')) ?>:</strong> ' + escapeHtml(record.department) + '</div>');
    }
    if (record.position) {
      lines.push('<div><strong><?= h(t('bdr_geolocation_popup_position', 'Jawatan')) ?>:</strong> ' + escapeHtml(record.position) + '</div>');
    }
    if (record.address) {
      lines.push('<div><strong><?= h(t('bdr_geolocation_popup_address', 'Alamat')) ?>:</strong> ' + escapeHtml(record.address) + '</div>');
    }
    if (
      (record.distance_km !== null && record.distance_km !== undefined && record.distance_km !== '') ||
      (record.lat !== null && record.lat !== undefined && record.lng !== null && record.lng !== undefined)
    ) {
      var distanceText = record.distance_km !== null && record.distance_km !== undefined && record.distance_km !== ''
        ? Number(record.distance_km).toFixed(2) + ' KM'
        : '-';
      var coordinateText = record.lat !== null && record.lat !== undefined && record.lng !== null && record.lng !== undefined
        ? Number(record.lat).toFixed(6) + ', ' + Number(record.lng).toFixed(6)
        : '-';
      lines.push('<div><strong><?= h(t('bdr_geolocation_popup_distance', 'Jarak')) ?>:</strong> ' + escapeHtml(distanceText) + ' <span class="geo-map-popup__separator">|</span> <strong><?= h(t('bdr_geolocation_popup_coordinates', 'Koordinat')) ?>:</strong> ' + escapeHtml(coordinateText) + '</div>');
    }
    lines.push('</div>');
    lines.push('</div>');
    return lines.join('');
  }

  function showSelectedStaffDetail(record) {
    var panel = document.getElementById('bdrGeoMapSelectedPanel');
    if (!panel) return;

    panel.innerHTML = buildGeoStaffDetail(record);
    panel.classList.remove('is-hidden');
  }

  function buildRouteLatLngs(record) {
    var routePoints = Array.isArray(record.route_points) ? record.route_points : [];
    var latLngs = routePoints.map(extractLatLngPair).filter(Boolean);
    if (latLngs.length >= 2) {
      return latLngs;
    }

    var home = extractLatLngPair(record);
    var office = getOfficeLatLng(geoMapCurrentSiteMeta, record);
    return home && office ? [home, office] : [];
  }

  function drawGeoRoute(record) {
    if (!geoMap || !geoRouteLayer || !record) return;

    clearGeoRoute();
    var latLngs = buildRouteLatLngs(record);
    if (latLngs.length < 2) {
      return;
    }

    var usesRoadRoute = Array.isArray(record.route_points) && record.route_points.length >= 2;
    L.polyline(latLngs, {
      color: '#111827',
      weight: 9,
      opacity: 0.35,
      lineJoin: 'round',
      dashArray: usesRoadRoute ? null : '10 10'
    }).addTo(geoRouteLayer);

    var route = L.polyline(latLngs, {
      color: '#2563eb',
      weight: 5,
      opacity: 0.96,
      lineJoin: 'round',
      dashArray: usesRoadRoute ? null : '10 10'
    }).addTo(geoRouteLayer);
    startRouteAvatarAnimation(latLngs, record);

    var office = getOfficeLatLng(geoMapCurrentSiteMeta, record);
    if (office && !geoOfficeMarker) {
      geoOfficeMarker = L.marker(office, {
        icon: getOfficeMarkerIcon(),
        interactive: true,
        title: getOfficeLabel(geoMapCurrentSiteMeta),
        zIndexOffset: 1000
      }).addTo(geoMap);
      geoOfficeMarker.on('click', function () {
        if (geoOfficeRadiusCircle) {
          geoMap.fitBounds(geoOfficeRadiusCircle.getBounds(), { padding: [42, 42], maxZoom: 13 });
        } else {
          geoMap.setView(office, 13);
        }
        geoOfficeMarker.openPopup();
      });
    }

    geoMap.fitBounds(route.getBounds(), { padding: [42, 42], maxZoom: 15 });
  }

  function loadAndDrawGeoRoute(record) {
    if (!record || !record.staff_no) return;

    if (Array.isArray(record.route_points) && record.route_points.length >= 2) {
      drawGeoRoute(record);
      return;
    }

    setGeoMapState('loading', <?= json_encode(t('bdr_geolocation_route_loading', 'Memuat laluan staf...')) ?>);
    fetch(buildGeoRouteEndpoint(record), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })
      .then(function (response) {
        return response.text().then(function (body) {
          var payload = null;
          try {
            payload = JSON.parse(body);
          } catch (parseError) {
            throw new Error(<?= json_encode(t('bdr_geolocation_route_load_error', 'Laluan staf tidak dapat dimuatkan.')) ?>);
          }

          if (!response.ok || !payload || payload.success !== true || !payload.record) {
            throw new Error((payload && payload.message) || <?= json_encode(t('bdr_geolocation_route_load_error', 'Laluan staf tidak dapat dimuatkan.')) ?>);
          }

          return payload.record;
        });
      })
      .then(function (routeRecord) {
        record.route_points = Array.isArray(routeRecord.route_points) ? routeRecord.route_points : [];
        record.office_lat = routeRecord.office_lat;
        record.office_lng = routeRecord.office_lng;
        drawGeoRoute(record);
        setGeoMapState('ready', '');
      })
      .catch(function (error) {
        setGeoMapState('error', error && error.message ? error.message : <?= json_encode(t('bdr_geolocation_route_load_error', 'Laluan staf tidak dapat dimuatkan.')) ?>);
        drawGeoRoute(record);
      });
  }

  function addGeoMapMarkers(records, options) {
    if (!geoMap || !geoMarkerLayer) return;
    options = options || {};

    geoMarkerLayer.clearLayers();
    clearGeoRoute();
    var selectedPanel = document.getElementById('bdrGeoMapSelectedPanel');
    if (selectedPanel) {
      selectedPanel.classList.add('is-hidden');
      selectedPanel.innerHTML = '';
    }
    var bounds = [];

    (Array.isArray(records) ? records : []).forEach(function (record) {
      var lat = Number(record.lat);
      var lng = Number(record.lng);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
      }

      var marker = L.marker([lat, lng], {
        title: getStaffDisplayName(record)
      });
      marker.on('click', function () {
        showSelectedStaffDetail(record);
        loadAndDrawGeoRoute(record);
      });

      geoMarkerLayer.addLayer(marker);
      bounds.push([lat, lng]);
    });

    if (bounds.length > 0) {
      focusOffice(geoMapCurrentSiteMeta, false);
      var mapBounds = L.latLngBounds(bounds);
      var officeLatLng = getOfficeLatLng(geoMapCurrentSiteMeta, null);
      if (officeLatLng) {
        mapBounds.extend(officeLatLng);
      }
      if (geoOfficeRadiusCircle && options.fitFiltered !== true) {
        mapBounds.extend(geoOfficeRadiusCircle.getBounds());
      }
      geoMap.fitBounds(mapBounds, { padding: [32, 32], maxZoom: 15 });
      setGeoMapState('ready', '');
    } else {
      geoMap.setView(window.BdrGeolocationMapConfig.defaultCenter, window.BdrGeolocationMapConfig.defaultZoom);
      var emptyMessage = geoMapRecords.length > 0
        ? <?= json_encode(t('bdr_geolocation_map_empty_filtered', 'Tiada rekod sepadan dengan filter semasa.')) ?>
        : <?= json_encode(t('bdr_geolocation_map_empty', 'Tiada data geolokasi untuk dipaparkan.')) ?>;
      setGeoMapState('empty', emptyMessage);
    }
  }

  function loadBdrStaffGeoMapData(forceReload) {
    if (geoMapInflight) return;
    if (geoMapDataLoaded && !forceReload) return;

    geoMapInflight = true;
    setGeoMapState('loading', <?= json_encode(t('bdr_geolocation_map_loading', 'Memuat data geolokasi staf...')) ?>);

    fetch(buildGeoMapEndpoint(), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })
      .then(function (response) {
        var contentType = response.headers.get('content-type') || '';
        return response.text().then(function (body) {
          var payload = null;
          try {
            payload = JSON.parse(body);
          } catch (parseError) {
            var preview = body.replace(/\s+/g, ' ').trim().slice(0, 160);
            var baseMessage = contentType.indexOf('application/json') === -1
              ? <?= json_encode(t('bdr_geolocation_map_json_error', 'Server tidak memulangkan JSON. Sila semak sesi login atau log server.')) ?>
              : <?= json_encode(t('bdr_geolocation_map_load_error', 'Data geolokasi tidak dapat dimuatkan.')) ?>;
            throw new Error(preview ? baseMessage + ' [' + preview + ']' : baseMessage);
          }

          if (!response.ok || !payload || payload.success !== true) {
            throw new Error((payload && payload.message) || <?= json_encode(t('bdr_geolocation_map_load_error', 'Data geolokasi tidak dapat dimuatkan.')) ?>);
          }
          return payload;
        });
      })
      .then(function (payload) {
        var records = Array.isArray(payload.records) ? payload.records : [];
        geoMapRecords = records;
        geoMapCurrentSiteMeta = payload.site || {};
        geoMapDataLoaded = true;
        setGeoMapBadges(payload.site || {}, records.length);
        applyGeoMapFilters({ fitFiltered: false });
      })
      .catch(function (error) {
        setGeoMapState('error', error && error.message ? error.message : <?= json_encode(t('bdr_geolocation_map_load_error', 'Data geolokasi tidak dapat dimuatkan.')) ?>);
      })
      .finally(function () {
        geoMapInflight = false;
      });
  }

  function focusHeatOffice(site, shouldCenter) {
    if (!geoHeatMap) return;

    var officeLatLng = getOfficeLatLng(site || geoHeatSiteMetaCache[geoHeatCurrentSite] || null, null);
    if (!officeLatLng) {
      if (shouldCenter !== false) {
        geoHeatMap.setView(window.BdrGeolocationMapConfig.defaultCenter, window.BdrGeolocationMapConfig.defaultZoom);
      }
      return;
    }

    if (geoHeatOfficeMarker) {
      geoHeatOfficeMarker.setLatLng(officeLatLng);
      geoHeatOfficeMarker.setIcon(getOfficeMarkerIcon());
    } else {
      geoHeatOfficeMarker = L.marker(officeLatLng, {
        icon: getOfficeMarkerIcon(),
        interactive: true,
        title: getOfficeLabel(site),
        zIndexOffset: 1000
      }).addTo(geoHeatMap);
    }
    geoHeatOfficeMarker.bindPopup('<div class="geo-map-popup"><div class="geo-map-popup__name">' + escapeHtml(getOfficeLabel(site)) + '</div><div class="geo-map-popup__meta">' + escapeHtml((site && site.office_address) || '') + '</div></div>');

    if (shouldCenter !== false) {
      geoHeatMap.setView(officeLatLng, 11);
    }
  }

  function renderGeoHeatMap(records, site) {
    if (!geoHeatMap) return;

    var heatData = (Array.isArray(records) ? records : []).map(function (record) {
      var lat = Number(record.lat);
      var lng = Number(record.lng);
      var weight = Number(record.weight || 1);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
      return [lat, lng, Number.isFinite(weight) && weight > 0 ? weight : 1];
    }).filter(Boolean);

    if (geoHeatLayer) {
      geoHeatMap.removeLayer(geoHeatLayer);
      geoHeatLayer = null;
    }

    focusHeatOffice(site, false);
    if (!heatData.length) {
      setGeoHeatMapState('empty', <?= json_encode(t('bdr_geolocation_heatmap_empty', 'Tiada data heatmap untuk dipaparkan.')) ?>);
      return;
    }

    geoHeatLayer = L.heatLayer(heatData, {
      radius: getHeatRadiusByZoom(geoHeatMap.getZoom()),
      blur: 20,
      maxZoom: 17,
      minOpacity: 0.3,
      gradient: {
        0.2: 'blue',
        0.4: 'lime',
        0.6: 'yellow',
        1.0: 'red'
      }
    }).addTo(geoHeatMap);

    var bounds = heatData.map(function (point) { return [point[0], point[1]]; });
    var officeLatLng = getOfficeLatLng(site, null);
    if (officeLatLng) {
      bounds.push(officeLatLng);
    }
    geoHeatMap.fitBounds(L.latLngBounds(bounds), { padding: [36, 36], maxZoom: 13 });
    setGeoHeatMapState('ready', '');
  }

  function loadBdrStaffHeatMapData(forceReload) {
    if (geoHeatInflight) return;

    var cacheKey = geoHeatCurrentSite || 'upnm_kampus';
    if (!forceReload && Array.isArray(geoHeatRecordsCache[cacheKey])) {
      setGeoHeatMapBadges(geoHeatSiteMetaCache[cacheKey] || {}, geoHeatRecordsCache[cacheKey].length, geoHeatStaffTotalCache[cacheKey]);
      renderGeoHeatMap(geoHeatRecordsCache[cacheKey], geoHeatSiteMetaCache[cacheKey] || {});
      return;
    }

    geoHeatInflight = true;
    setGeoHeatMapState('loading', <?= json_encode(t('bdr_geolocation_heatmap_loading', 'Memuat data heatmap staf...')) ?>);

    fetch(buildGeoHeatEndpoint(), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })
      .then(function (response) {
        var contentType = response.headers.get('content-type') || '';
        return response.text().then(function (body) {
          var payload = null;
          try {
            payload = JSON.parse(body);
          } catch (parseError) {
            var preview = body.replace(/\s+/g, ' ').trim().slice(0, 160);
            var baseMessage = contentType.indexOf('application/json') === -1
              ? <?= json_encode(t('bdr_geolocation_map_json_error', 'Server tidak memulangkan JSON. Sila semak sesi login atau log server.')) ?>
              : <?= json_encode(t('bdr_geolocation_heatmap_load_error', 'Data heatmap tidak dapat dimuatkan.')) ?>;
            throw new Error(preview ? baseMessage + ' [' + preview + ']' : baseMessage);
          }

          if (!response.ok || !payload || payload.success !== true) {
            throw new Error((payload && payload.message) || <?= json_encode(t('bdr_geolocation_heatmap_load_error', 'Data heatmap tidak dapat dimuatkan.')) ?>);
          }
          return payload;
        });
      })
      .then(function (payload) {
        var records = Array.isArray(payload.records) ? payload.records : [];
        geoHeatRecordsCache[cacheKey] = records;
        geoHeatSiteMetaCache[cacheKey] = payload.site || {};
        geoHeatStaffTotalCache[cacheKey] = payload.staff_total;
        setGeoHeatMapBadges(payload.site || {}, records.length, payload.staff_total);
        renderGeoHeatMap(records, payload.site || {});
      })
      .catch(function (error) {
        setGeoHeatMapState('error', error && error.message ? error.message : <?= json_encode(t('bdr_geolocation_heatmap_load_error', 'Data heatmap tidak dapat dimuatkan.')) ?>);
      })
      .finally(function () {
        geoHeatInflight = false;
      });
  }

  function initBdrStaffHeatMap() {
    if (geoHeatInitialized) {
      if (geoHeatMap && typeof geoHeatMap.invalidateSize === 'function') {
        window.setTimeout(function () { geoHeatMap.invalidateSize(); }, 80);
      }
      loadBdrStaffHeatMapData(false);
      return;
    }

    if (!window.L || typeof L.map !== 'function' || typeof L.heatLayer !== 'function') {
      setGeoHeatMapState('error', <?= json_encode(t('bdr_geolocation_heatmap_lib_missing', 'Library heatmap tidak dapat dimuatkan.')) ?>);
      return;
    }

    var mapEl = document.getElementById('bdrStaffHeatMap');
    if (!mapEl) return;

    geoHeatMap = L.map(mapEl, {
      preferCanvas: true,
      scrollWheelZoom: true
    }).setView(window.BdrGeolocationMapConfig.defaultCenter, window.BdrGeolocationMapConfig.defaultZoom);

    L.tileLayer(window.BdrGeolocationMapConfig.tileUrl, {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(geoHeatMap);

    geoHeatMap.on('zoomend', syncGeoHeatRadius);
    geoHeatInitialized = true;
    window.setTimeout(function () { geoHeatMap.invalidateSize(); }, 120);
    loadBdrStaffHeatMapData(false);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var mapTab = document.getElementById('map-staff-tab');
    var heatMapTab = document.getElementById('heatmap-staff-tab');
    var mapReloadButton = document.getElementById('bdrGeoMapReloadBtn');
    var heatMapReloadButton = document.getElementById('bdrHeatMapReloadBtn');
    var mapFilterToggleButton = document.getElementById('bdrGeoMapFilterToggleBtn');
    var heatMapFilterToggleButton = document.getElementById('bdrHeatMapFilterToggleBtn');
    var mapFilterPanel = document.getElementById('bdrGeoMapFilterPanel');
    var heatMapFilterPanel = document.getElementById('bdrHeatMapControlPanel');
    var mapFullscreenButton = document.getElementById('bdrGeoMapFullscreenBtn');
    var heatMapFullscreenButton = document.getElementById('bdrHeatMapFullscreenBtn');
    var mapSiteFilter = document.getElementById('bdrGeoMapSiteFilter');
    var heatMapSiteFilter = document.getElementById('bdrHeatMapSiteFilter');
    var mapQualityFilter = document.getElementById('bdrGeoMapQualityFilter');
    var mapSearch = document.getElementById('bdrGeoMapSearch');
    if (mapTab) {
      mapTab.addEventListener('shown.bs.tab', function () {
        initBdrStaffGeoMap();
      });
    }
    if (heatMapTab) {
      heatMapTab.addEventListener('shown.bs.tab', function () {
        initBdrStaffHeatMap();
      });
    }
    if (mapReloadButton) {
      mapReloadButton.addEventListener('click', function () {
        initBdrStaffGeoMap();
        loadBdrStaffGeoMapData(true);
      });
    }
    if (heatMapReloadButton) {
      heatMapReloadButton.addEventListener('click', function () {
        initBdrStaffHeatMap();
        loadBdrStaffHeatMapData(true);
      });
    }
    if (mapFullscreenButton) {
      mapFullscreenButton.addEventListener('click', function () {
        toggleGeoMapFullscreen();
      });
    }
    if (heatMapFullscreenButton) {
      heatMapFullscreenButton.addEventListener('click', function () {
        toggleHeatMapFullscreen();
      });
    }
    document.addEventListener('fullscreenchange', function () {
      updateFullscreenButton();
      updateHeatMapFullscreenButton();
      if (geoMap && typeof geoMap.invalidateSize === 'function') {
        window.setTimeout(function () { geoMap.invalidateSize(); }, 120);
      }
      if (geoHeatMap && typeof geoHeatMap.invalidateSize === 'function') {
        window.setTimeout(function () { geoHeatMap.invalidateSize(); }, 120);
      }
    });
    if (mapFilterToggleButton && mapFilterPanel) {
      mapFilterToggleButton.addEventListener('click', function () {
        var isCollapsed = mapFilterPanel.classList.toggle('is-collapsed');
        mapFilterToggleButton.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        mapFilterToggleButton.classList.toggle('btn-primary', !isCollapsed);
        mapFilterToggleButton.classList.toggle('btn-outline-secondary', isCollapsed);
      });
    }
    if (heatMapFilterToggleButton && heatMapFilterPanel) {
      heatMapFilterToggleButton.addEventListener('click', function () {
        var isCollapsed = heatMapFilterPanel.classList.toggle('is-collapsed');
        heatMapFilterToggleButton.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        heatMapFilterToggleButton.classList.toggle('btn-primary', !isCollapsed);
        heatMapFilterToggleButton.classList.toggle('btn-outline-secondary', isCollapsed);
        if (geoHeatMap && typeof geoHeatMap.invalidateSize === 'function') {
          window.setTimeout(function () { geoHeatMap.invalidateSize(); }, 120);
        }
      });
    }
    if (mapSiteFilter) {
      geoMapCurrentSite = mapSiteFilter.value || geoMapCurrentSite;
      mapSiteFilter.addEventListener('change', function () {
        geoMapCurrentSite = mapSiteFilter.value || 'upnm_kampus';
        geoMapDataLoaded = false;
        geoMapRecords = [];
        geoMapCurrentSiteMeta = null;
        clearGeoRoute();
        setGeoMapStats(0, 0, 0);
        initBdrStaffGeoMap();
        loadBdrStaffGeoMapData(true);
      });
    }
    if (heatMapSiteFilter) {
      geoHeatCurrentSite = heatMapSiteFilter.value || geoHeatCurrentSite;
      heatMapSiteFilter.addEventListener('change', function () {
        geoHeatCurrentSite = heatMapSiteFilter.value || 'upnm_kampus';
        if (geoHeatLayer && geoHeatMap) {
          geoHeatMap.removeLayer(geoHeatLayer);
          geoHeatLayer = null;
        }
        setGeoHeatMapBadges({}, 0, 0);
        initBdrStaffHeatMap();
        loadBdrStaffHeatMapData(false);
      });
    }
    if (mapQualityFilter) {
      mapQualityFilter.addEventListener('change', function () {
        applyGeoMapFilters({ fitFiltered: true });
      });
    }
    if (mapSearch) {
      mapSearch.addEventListener('input', function () {
        window.clearTimeout(geoMapSearchTimer);
        geoMapSearchTimer = window.setTimeout(function () {
          applyGeoMapFilters({ fitFiltered: true });
        }, 180);
      });
    }
    var mapPane = document.getElementById('map-staff-tab-pane');
    if (mapPane && mapPane.classList.contains('active')) {
      initBdrStaffGeoMap();
    }

  });
})();
</script>
</body>
</html>
