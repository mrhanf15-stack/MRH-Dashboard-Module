<?php
/* -----------------------------------------------------------------------------------------
   $Id: mrh_dashboard.php 1.2.0 2026-03-31 Mr. Hanf $

   MRH Dashboard - Admin-Seite
   https://mr-hanf.at

   Copyright (c) 2026 Mr. Hanf
   -----------------------------------------------------------------------------------------
   Released under the GNU General Public License
   ---------------------------------------------------------------------------------------*/

require('includes/application_top.php');

// Pruefen ob Modul installiert
if (!defined('MODULE_MRH_DASHBOARD_STATUS') || MODULE_MRH_DASHBOARD_STATUS !== 'true') {
  xtc_redirect(xtc_href_link(FILENAME_MODULE_EXPORT, 'set=system'));
}

// Mega-Menü Manager laden
$manager_file = DIR_FS_CATALOG . 'includes/external/mrh_dashboard/MrhMegaMenuManager.php';
if (file_exists($manager_file)) {
  require_once($manager_file);
  $megaMenuManager = new MrhMegaMenuManager();
} else {
  $megaMenuManager = null;
}

// --- AJAX-Endpunkte ---
if (isset($_GET['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');

  // Kategorien laden
  if ($_GET['ajax'] === 'get_categories') {
    $parent_id = (int)$_GET['parent_id'];
    $categories = $megaMenuManager ? $megaMenuManager->getAllSubcategories($parent_id) : array();
    echo json_encode(array('success' => true, 'categories' => $categories));
    exit;
  }

  // Konfiguration laden
  if ($_GET['ajax'] === 'get_config') {
    $parent_id = (int)$_GET['parent_id'];
    $config = $megaMenuManager ? $megaMenuManager->getConfig($parent_id) : null;
    echo json_encode(array('success' => true, 'config' => $config));
    exit;
  }

  // Konfiguration speichern
  if ($_GET['ajax'] === 'save_config') {
    $input = json_decode(file_get_contents('php://input'), true);
    $parent_id = (int)($input['parent_id'] ?? 0);
    $columns   = $input['columns'] ?? array();

    $result = $megaMenuManager ? $megaMenuManager->saveConfig($parent_id, $columns) : false;

    // Cache regenerieren
    if ($result && $megaMenuManager) {
      $megaMenuManager->regenerateCache();
    }

    echo json_encode(array('success' => $result));
    exit;
  }

  // Nav-Links laden
  if ($_GET['ajax'] === 'get_navlinks') {
    $links = $megaMenuManager ? $megaMenuManager->getNavLinks() : array();
    echo json_encode(array('success' => true, 'links' => $links));
    exit;
  }

  // Nav-Links speichern
  if ($_GET['ajax'] === 'save_navlinks') {
    $input = json_decode(file_get_contents('php://input'), true);
    $links = $input['links'] ?? array();

    $result = $megaMenuManager ? $megaMenuManager->saveNavLinks($links) : false;

    // Cache regenerieren
    if ($result && $megaMenuManager) {
      $megaMenuManager->regenerateCache();
    }

    echo json_encode(array('success' => $result));
    exit;
  }

  // Sprachkonstanten laden
  if ($_GET['ajax'] === 'get_lang_constants') {
    $lang_code = preg_replace('/[^a-z]/', '', $_GET['lang'] ?? 'de');
    $constants = $megaMenuManager ? $megaMenuManager->readLangConstants($lang_code) : array();
    echo json_encode(array('success' => true, 'constants' => $constants));
    exit;
  }

  // Sprachkonstanten speichern
  if ($_GET['ajax'] === 'save_lang_constants') {
    $input = json_decode(file_get_contents('php://input'), true);
    $lang_code  = preg_replace('/[^a-z]/', '', $input['lang'] ?? '');
    $constants  = $input['constants'] ?? array();

    $result = ($megaMenuManager && $lang_code) ? $megaMenuManager->writeLangConstants($lang_code, $constants) : false;

    echo json_encode(array('success' => $result));
    exit;
  }

  // Cache regenerieren
  if ($_GET['ajax'] === 'regenerate_cache') {
    $result = $megaMenuManager ? $megaMenuManager->regenerateCache() : false;
    echo json_encode(array('success' => $result));
    exit;
  }

  echo json_encode(array('success' => false, 'error' => 'Unknown action'));
  exit;
}

// --- Daten fuer die Seite ---
$mainCategories = $megaMenuManager ? $megaMenuManager->getMainCategories() : array();
$availableLanguages = $megaMenuManager ? $megaMenuManager->getAvailableLanguages() : array();
$navLinks = $megaMenuManager ? $megaMenuManager->getNavLinks() : array();

// Sprachkonstanten
$heading_title = defined('MRH_DASHBOARD_HEADING') ? MRH_DASHBOARD_HEADING : 'MRH Dashboard';
$version = defined('MODULE_MRH_DASHBOARD_VERSION') ? MODULE_MRH_DASHBOARD_VERSION : '1.2.0';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $heading_title; ?></title>
  <?php echo '<link rel="stylesheet" href="includes/stylesheet.css">'; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; font-family: 'Segoe UI', Tahoma, sans-serif; }
    .mrh-header { background: linear-gradient(135deg, #2d7a3a 0%, #1a5c28 100%); color: #fff; padding: 20px 30px; border-radius: 12px; margin-bottom: 25px; }
    .mrh-header h1 { font-size: 1.5rem; margin: 0; font-weight: 600; }
    .mrh-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
    .mrh-card .card-header { background: #f8f9fa; border-bottom: 1px solid #e9ecef; padding: 12px 20px; font-weight: 600; border-radius: 10px 10px 0 0; }
    .mrh-card .card-body { padding: 20px; }
    .nav-tabs .nav-link { color: #555; font-weight: 500; }
    .nav-tabs .nav-link.active { color: #2d7a3a; border-color: #2d7a3a #2d7a3a #fff; font-weight: 600; }
    .column-card { border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #fafbfc; }
    .column-card .column-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .icon-picker-modal .icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(48px, 1fr)); gap: 4px; max-height: 300px; overflow-y: auto; }
    .icon-picker-modal .icon-item { display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border: 1px solid #dee2e6; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
    .icon-picker-modal .icon-item:hover { background: #e8f5e9; border-color: #2d7a3a; }
    .icon-picker-modal .icon-item.selected { background: #2d7a3a; color: #fff; border-color: #2d7a3a; }
    .navlink-row { display: flex; gap: 10px; align-items: center; margin-bottom: 8px; padding: 8px; background: #f8f9fa; border-radius: 6px; }
    .navlink-row input { flex: 1; }
    .lang-editor-row { display: flex; gap: 10px; align-items: center; margin-bottom: 6px; }
    .lang-editor-row .const-key { font-family: monospace; font-size: 0.85rem; min-width: 220px; color: #6c757d; }
    .lang-editor-row input { flex: 1; }
    .sortable-ghost { opacity: 0.4; }
    .drag-handle { cursor: grab; color: #aaa; }
    .drag-handle:active { cursor: grabbing; }
    .item-row { display: flex; align-items: center; gap: 8px; padding: 6px 8px; background: #fff; border: 1px solid #e9ecef; border-radius: 4px; margin-bottom: 4px; }
    .item-row .item-label { flex: 1; font-size: 0.9rem; }
    .item-row .btn-remove { color: #dc3545; cursor: pointer; border: none; background: none; }
  </style>
</head>
<body>
  <div class="container-fluid" style="max-width: 1400px; padding: 20px;">

    <!-- Header -->
    <div class="mrh-header d-flex justify-content-between align-items-center">
      <div>
        <h1><i class="fa fa-dashboard"></i> <?php echo $heading_title; ?></h1>
        <small class="opacity-75">Modulares Dashboard fuer Template-Funktionen</small>
      </div>
      <div><span class="badge bg-light text-dark">v<?php echo htmlspecialchars($version); ?></span></div>
    </div>

    <!-- Haupt-Tabs -->
    <ul class="nav nav-tabs mb-3" id="mainTabs" role="tablist">
      <li class="nav-item"><a class="nav-link active" id="megamenu-tab" data-bs-toggle="tab" href="#megamenu" role="tab"><i class="fa fa-bars"></i> Mega-Menü</a></li>
      <li class="nav-item"><a class="nav-link" id="navlinks-tab" data-bs-toggle="tab" href="#navlinks" role="tab"><i class="fa fa-link"></i> Nav-Links</a></li>
      <li class="nav-item"><a class="nav-link" id="langeditor-tab" data-bs-toggle="tab" href="#langeditor" role="tab"><i class="fa fa-language"></i> Sprachdatei-Editor</a></li>
    </ul>

    <div class="tab-content">

      <!-- TAB 1: Mega-Menü Manager -->
      <div class="tab-pane fade show active" id="megamenu" role="tabpanel">
        <div class="row">
          <div class="col-md-3">
            <div class="mrh-card">
              <div class="card-header"><i class="fa fa-sitemap"></i> Hauptkategorien</div>
              <div class="card-body p-0">
                <div class="list-group list-group-flush" id="categoryList">
                  <?php foreach ($mainCategories as $cat): ?>
                    <?php $hasConfig = $megaMenuManager ? $megaMenuManager->hasConfig($cat['categories_id']) : false; ?>
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center category-item"
                       data-id="<?php echo (int)$cat['categories_id']; ?>"
                       data-name="<?php echo htmlspecialchars($cat['categories_name']); ?>">
                      <?php echo htmlspecialchars($cat['categories_name']); ?>
                      <?php if ($hasConfig): ?>
                        <span class="badge bg-success rounded-pill"><i class="fa fa-check"></i></span>
                      <?php else: ?>
                        <span class="badge bg-secondary rounded-pill"><i class="fa fa-minus"></i></span>
                      <?php endif; ?>
                    </a>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <div class="mrh-card">
              <div class="card-body text-center">
                <button class="btn btn-success btn-sm" id="btnRegenerateCache"><i class="fa fa-refresh"></i> Cache regenerieren</button>
              </div>
            </div>
          </div>

          <div class="col-md-9">
            <div class="mrh-card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <span id="editorTitle"><i class="fa fa-hand-pointer-o"></i> Kategorie auswählen...</span>
                <div>
                  <button class="btn btn-primary btn-sm" id="btnAddColumn" style="display:none;"><i class="fa fa-plus"></i> Spalte</button>
                  <button class="btn btn-success btn-sm" id="btnSaveConfig" style="display:none;"><i class="fa fa-save"></i> Speichern</button>
                </div>
              </div>
              <div class="card-body" id="editorArea">
                <div class="text-center text-muted py-5">
                  <i class="fa fa-arrow-left fa-2x mb-3 d-block"></i>
                  <p>Wähle eine Hauptkategorie aus der linken Liste.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: Nav-Links -->
      <div class="tab-pane fade" id="navlinks" role="tabpanel">
        <div class="mrh-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fa fa-link"></i> Zusätzliche Navigationslinks</span>
            <div>
              <button class="btn btn-primary btn-sm" id="btnAddNavLink"><i class="fa fa-plus"></i> Link</button>
              <button class="btn btn-success btn-sm" id="btnSaveNavLinks"><i class="fa fa-save"></i> Speichern</button>
            </div>
          </div>
          <div class="card-body">
            <div class="alert alert-info small mb-3">
              <i class="fa fa-info-circle"></i>
              <strong>Syntax:</strong> URL + Linkname. Für <strong>Mehrsprachigkeit</strong> verwende als Name eine Konstante mit Prefix <code>MRH_</code> (z.B. <code>MRH_NAV_ANGEBOTE</code>).
              Die Konstante wird im <strong>Sprachdatei-Editor</strong> pro Sprache definiert.
              <br><strong>Beispiel:</strong> <code>specials.php</code> | <code>MRH_NAV_ANGEBOTE</code>
            </div>
            <div id="navLinksList"></div>
          </div>
        </div>
      </div>

      <!-- TAB 3: Sprachdatei-Editor -->
      <div class="tab-pane fade" id="langeditor" role="tabpanel">
        <div class="mrh-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fa fa-language"></i> MRH Sprachkonstanten</span>
            <div>
              <button class="btn btn-primary btn-sm" id="btnAddConstant"><i class="fa fa-plus"></i> Konstante</button>
              <button class="btn btn-success btn-sm" id="btnSaveLangConstants"><i class="fa fa-save"></i> Speichern</button>
            </div>
          </div>
          <div class="card-body">
            <div class="alert alert-info small mb-3">
              <i class="fa fa-info-circle"></i>
              <code>MRH_</code>-Sprachkonstanten bearbeiten. Gespeichert in <code>lang/{sprache}/extra/admin/mrh_dashboard.php</code>.
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Sprache:</label>
              <div class="btn-group" role="group">
                <?php foreach ($availableLanguages as $code => $name): ?>
                  <button type="button" class="btn btn-outline-success btn-sm lang-select-btn <?php echo ($code === 'de') ? 'active' : ''; ?>" data-lang="<?php echo $code; ?>"><?php echo htmlspecialchars($name); ?></button>
                <?php endforeach; ?>
              </div>
            </div>
            <div id="langConstantsList"></div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Icon-Picker Modal -->
  <div class="modal fade icon-picker-modal" id="iconPickerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa fa-th"></i> Icon auswählen</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="text" class="form-control mb-3" id="iconSearchInput" placeholder="Icon suchen... (z.B. leaf, star, home)">
          <div class="icon-grid" id="iconGrid"></div>
        </div>
        <div class="modal-footer">
          <span class="me-auto" id="iconPreview"><i class="fa fa-question fa-2x"></i> <span class="ms-2 text-muted">Kein Icon</span></span>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>
          <button type="button" class="btn btn-success btn-sm" id="btnConfirmIcon">Übernehmen</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

  <script>
  (function() {
    'use strict';

    var currentParentId = null;
    var currentColumns = [];
    var currentNavLinks = [];
    var currentLangCode = 'de';
    var currentLangConstants = {};
    var iconPickerCallback = null;
    var selectedIcon = '';
    var iconModal = null;
    var AJAX_URL = 'mrh_dashboard.php';

    var FA_ICONS = [
      'fa-home','fa-bars','fa-search','fa-star','fa-star-o','fa-heart','fa-heart-o',
      'fa-user','fa-users','fa-cog','fa-cogs','fa-wrench','fa-shopping-cart','fa-shopping-bag',
      'fa-tag','fa-tags','fa-bookmark','fa-flag','fa-leaf','fa-tree','fa-pagelines',
      'fa-envira','fa-diamond','fa-cloud','fa-sun-o','fa-moon-o','fa-bolt','fa-fire',
      'fa-tint','fa-gift','fa-trophy','fa-certificate','fa-shield','fa-rocket',
      'fa-truck','fa-music','fa-film','fa-camera','fa-image','fa-phone','fa-envelope',
      'fa-comment','fa-comments','fa-bell','fa-info-circle','fa-question-circle',
      'fa-exclamation-circle','fa-check-circle','fa-plus','fa-minus','fa-times','fa-check',
      'fa-pencil','fa-edit','fa-trash','fa-download','fa-upload','fa-share','fa-link',
      'fa-external-link','fa-globe','fa-map-marker','fa-map','fa-compass',
      'fa-book','fa-newspaper-o','fa-file','fa-folder','fa-calendar','fa-clock-o',
      'fa-lock','fa-unlock','fa-key','fa-eye','fa-thumbs-up','fa-thumbs-down',
      'fa-arrow-right','fa-arrow-left','fa-chevron-right','fa-chevron-left',
      'fa-sort','fa-filter','fa-list','fa-th','fa-table','fa-database','fa-code',
      'fa-paint-brush','fa-magic','fa-flask','fa-lightbulb-o','fa-plug',
      'fa-wifi','fa-facebook','fa-twitter','fa-instagram','fa-youtube','fa-pinterest',
      'fa-whatsapp','fa-telegram','fa-linkedin','fa-cc-visa','fa-cc-mastercard','fa-cc-paypal',
      'fa-eur','fa-usd','fa-percent','fa-bar-chart','fa-line-chart','fa-pie-chart',
      'fa-smile-o','fa-coffee','fa-beer','fa-paw','fa-bug','fa-cube','fa-cubes',
      'fa-gamepad','fa-headphones','fa-medkit','fa-graduation-cap','fa-industry',
      'fa-building','fa-anchor','fa-umbrella','fa-recycle','fa-language','fa-sitemap',
      'fa-dashboard','fa-tachometer','fa-spinner','fa-refresh','fa-repeat','fa-undo',
      'fa-expand','fa-crop','fa-scissors','fa-copy','fa-save','fa-print','fa-rss',
      'fa-bullhorn','fa-cannabis'
    ];

    // Hilfsfunktionen
    function ajax(action, params, callback) {
      var url = AJAX_URL + '?ajax=' + action;
      var opts = { method: 'GET' };

      if (params && params._body) {
        opts.method = 'POST';
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body = JSON.stringify(params._body);
      } else if (params) {
        for (var key in params) {
          if (key !== '_body') url += '&' + key + '=' + encodeURIComponent(params[key]);
        }
      }

      fetch(url, opts)
        .then(function(r) { return r.json(); })
        .then(function(data) { callback(data); })
        .catch(function(err) { console.error('AJAX Error:', err); showToast('Serverfehler', 'error'); });
    }

    function showToast(msg, type) {
      var t = document.createElement('div');
      t.className = 'position-fixed top-0 end-0 m-3 p-3 rounded shadow text-white';
      t.style.cssText = 'z-index:9999;background:' + (type === 'success' ? '#2d7a3a' : '#dc3545') + ';transition:opacity 0.3s;';
      t.innerHTML = '<i class="fa ' + (type === 'success' ? 'fa-check' : 'fa-times') + '"></i> ' + msg;
      document.body.appendChild(t);
      setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 2500);
    }

    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML.replace(/"/g, '&quot;'); }

    // ============================================================
    // MEGA-MENÜ
    // ============================================================
    document.querySelectorAll('.category-item').forEach(function(el) {
      el.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.category-item').forEach(function(x) { x.classList.remove('active'); });
        this.classList.add('active');
        currentParentId = parseInt(this.dataset.id);
        document.getElementById('editorTitle').innerHTML = '<i class="fa fa-edit"></i> ' + this.dataset.name + ' <small class="text-muted">(ID: ' + currentParentId + ')</small>';
        document.getElementById('btnAddColumn').style.display = '';
        document.getElementById('btnSaveConfig').style.display = '';
        loadConfig(currentParentId);
      });
    });

    function loadConfig(pid) {
      document.getElementById('editorArea').innerHTML = '<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';
      ajax('get_config', { parent_id: pid }, function(data) {
        currentColumns = data.config || [];
        renderColumns();
      });
    }

    function renderColumns() {
      var area = document.getElementById('editorArea');
      if (currentColumns.length === 0) {
        area.innerHTML = '<div class="text-center text-muted py-4"><p>Keine Spalten. Klicke "Spalte" um zu beginnen.</p></div>';
        return;
      }

      var html = '<div id="columnsContainer">';
      currentColumns.forEach(function(col, idx) {
        html += '<div class="column-card" data-index="' + idx + '">';
        html += '<div class="column-header"><div class="d-flex align-items-center gap-2"><span class="drag-handle"><i class="fa fa-bars"></i></span><h6 class="mb-0">Spalte ' + (idx + 1) + '</h6></div>';
        html += '<button class="btn btn-outline-danger btn-sm" onclick="removeColumn(' + idx + ')"><i class="fa fa-trash"></i></button></div>';

        // Icon + 4 Sprach-Titel in einer Zeile
        html += '<div class="row g-2 mb-2">';
        html += '<div class="col-md-2"><label class="form-label small fw-bold">Icon</label><div class="input-group input-group-sm">';
        html += '<span class="input-group-text"><i class="fa ' + (col.icon || 'fa-question') + '"></i></span>';
        html += '<input type="text" class="form-control" value="' + (col.icon || '') + '" id="colIcon_' + idx + '" readonly>';
        html += '<button class="btn btn-outline-secondary" type="button" onclick="openIconPicker(' + idx + ')"><i class="fa fa-th"></i></button>';
        html += '</div></div>';

        var langs = [{c:'de',l:'DE'},{c:'en',l:'EN'},{c:'fr',l:'FR'},{c:'es',l:'ES'}];
        langs.forEach(function(lang) {
          html += '<div class="col"><label class="form-label small fw-bold">' + lang.l + '</label>';
          html += '<input type="text" class="form-control form-control-sm" value="' + escapeHtml(col['title_' + lang.c] || '') + '" id="colTitle_' + idx + '_' + lang.c + '" placeholder="Titel (' + lang.l + ')"></div>';
        });
        html += '</div>';

        // Items
        html += '<label class="form-label small fw-bold mt-2">Kategorien:</label>';
        html += '<div class="items-list" id="itemsList_' + idx + '">';
        if (col.items && col.items.length > 0) {
          col.items.forEach(function(item, iIdx) {
            html += '<div class="item-row" data-cat-id="' + item.category_id + '"><span class="drag-handle"><i class="fa fa-bars"></i></span>';
            html += '<span class="item-label">' + escapeHtml(item.label || 'ID: ' + item.category_id) + ' <small class="text-muted">(ID: ' + item.category_id + ')</small></span>';
            html += '<button class="btn-remove" onclick="removeItem(' + idx + ',' + iIdx + ')"><i class="fa fa-times"></i></button></div>';
          });
        }
        html += '</div>';

        html += '<div class="mt-2 d-flex gap-1"><select class="form-select form-select-sm" id="addCatSelect_' + idx + '" style="max-width:300px;"><option value="">Kategorie hinzufügen...</option></select>';
        html += '<button class="btn btn-outline-primary btn-sm" onclick="addItemFromSelect(' + idx + ')"><i class="fa fa-plus"></i></button></div>';
        html += '</div>';
      });
      html += '</div>';
      area.innerHTML = html;

      if (currentParentId) loadCategoriesForDropdowns();

      currentColumns.forEach(function(col, idx) {
        var list = document.getElementById('itemsList_' + idx);
        if (list) new Sortable(list, { handle: '.drag-handle', animation: 150, ghostClass: 'sortable-ghost', onEnd: function() { syncItemsFromDOM(idx); } });
      });
    }

    function loadCategoriesForDropdowns() {
      ajax('get_categories', { parent_id: currentParentId }, function(data) {
        var cats = data.categories || [];
        currentColumns.forEach(function(col, idx) {
          var sel = document.getElementById('addCatSelect_' + idx);
          if (sel) {
            var h = '<option value="">Kategorie hinzufügen...</option>';
            cats.forEach(function(c) {
              var p = ''; for (var i = 0; i < c.level; i++) p += '— ';
              h += '<option value="' + c.categories_id + '">' + p + c.categories_name + ' (ID: ' + c.categories_id + ')</option>';
            });
            sel.innerHTML = h;
          }
        });
      });
    }

    document.getElementById('btnAddColumn').addEventListener('click', function() {
      syncAllFromDOM();
      currentColumns.push({ title_de:'', title_en:'', title_fr:'', title_es:'', icon:'fa-folder-o', items:[] });
      renderColumns();
    });

    window.removeColumn = function(idx) {
      if (confirm('Spalte ' + (idx+1) + ' entfernen?')) { syncAllFromDOM(); currentColumns.splice(idx, 1); renderColumns(); }
    };

    window.addItemFromSelect = function(colIdx) {
      var sel = document.getElementById('addCatSelect_' + colIdx);
      if (!sel || !sel.value) return;
      syncAllFromDOM();
      var catId = parseInt(sel.value);
      var catName = sel.options[sel.selectedIndex].text.replace(/^[—\s]+/, '').replace(/\s*\(ID:.*\)$/, '');
      currentColumns[colIdx].items.push({ category_id: catId, label: catName });
      renderColumns();
    };

    window.removeItem = function(colIdx, itemIdx) {
      syncAllFromDOM(); currentColumns[colIdx].items.splice(itemIdx, 1); renderColumns();
    };

    function syncItemsFromDOM(colIdx) {
      var list = document.getElementById('itemsList_' + colIdx);
      if (!list) return;
      var items = [];
      list.querySelectorAll('.item-row').forEach(function(row) {
        items.push({ category_id: parseInt(row.dataset.catId), label: row.querySelector('.item-label').textContent.replace(/\s*\(ID:.*\)$/, '').trim() });
      });
      currentColumns[colIdx].items = items;
    }

    function syncAllFromDOM() {
      currentColumns.forEach(function(col, idx) {
        ['de','en','fr','es'].forEach(function(l) { var inp = document.getElementById('colTitle_' + idx + '_' + l); if (inp) col['title_' + l] = inp.value; });
        var ic = document.getElementById('colIcon_' + idx); if (ic) col.icon = ic.value;
        syncItemsFromDOM(idx);
      });
    }

    document.getElementById('btnSaveConfig').addEventListener('click', function() {
      if (!currentParentId) return;
      syncAllFromDOM();
      ajax('save_config', { _body: { parent_id: currentParentId, columns: currentColumns } }, function(data) {
        if (data.success) {
          showToast('Konfiguration gespeichert!', 'success');
          var badge = document.querySelector('.category-item[data-id="' + currentParentId + '"] .badge');
          if (badge) { badge.className = 'badge bg-success rounded-pill'; badge.innerHTML = '<i class="fa fa-check"></i>'; }
        } else { showToast('Fehler beim Speichern!', 'error'); }
      });
    });

    document.getElementById('btnRegenerateCache').addEventListener('click', function() {
      ajax('regenerate_cache', {}, function(data) {
        showToast(data.success ? 'Cache regeneriert!' : 'Cache-Fehler!', data.success ? 'success' : 'error');
      });
    });

    // ============================================================
    // ICON-PICKER
    // ============================================================
    window.openIconPicker = function(colIdx) {
      iconPickerCallback = function(icon) {
        syncAllFromDOM();
        currentColumns[colIdx].icon = icon;
        var inp = document.getElementById('colIcon_' + colIdx);
        if (inp) { inp.value = icon; inp.previousElementSibling.innerHTML = '<i class="fa ' + icon + '"></i>'; }
      };
      selectedIcon = currentColumns[colIdx] ? currentColumns[colIdx].icon || '' : '';
      renderIconGrid(); if (!iconModal) iconModal = new bootstrap.Modal(document.getElementById('iconPickerModal')); iconModal.show();
    };

    window.openIconPickerForNavLink = function(idx) {
      iconPickerCallback = function(icon) {
        var inp = document.getElementById('navLinkIcon_' + idx);
        if (inp) { inp.value = icon; var p = inp.parentElement.querySelector('.input-group-text'); if (p) p.innerHTML = '<i class="fa ' + icon + '"></i>'; }
      };
      selectedIcon = (document.getElementById('navLinkIcon_' + idx) || {}).value || '';
      renderIconGrid(); if (!iconModal) iconModal = new bootstrap.Modal(document.getElementById('iconPickerModal')); iconModal.show();
    };

    function renderIconGrid(filter) {
      var grid = document.getElementById('iconGrid');
      var html = '', search = (filter || '').toLowerCase();
      FA_ICONS.forEach(function(icon) {
        if (search && icon.toLowerCase().indexOf(search) === -1) return;
        html += '<div class="icon-item' + (icon === selectedIcon ? ' selected' : '') + '" data-icon="' + icon + '" title="' + icon + '"><i class="fa ' + icon + '"></i></div>';
      });
      grid.innerHTML = html || '<div class="text-muted p-3">Keine Icons gefunden.</div>';
      grid.querySelectorAll('.icon-item').forEach(function(el) {
        el.addEventListener('click', function() {
          grid.querySelectorAll('.icon-item').forEach(function(x) { x.classList.remove('selected'); });
          this.classList.add('selected'); selectedIcon = this.dataset.icon;
          document.getElementById('iconPreview').innerHTML = '<i class="fa ' + selectedIcon + ' fa-2x"></i> <span class="ms-2">' + selectedIcon + '</span>';
        });
      });
    }

    document.getElementById('iconSearchInput').addEventListener('input', function() { renderIconGrid(this.value); });
    document.getElementById('btnConfirmIcon').addEventListener('click', function() {
      if (iconPickerCallback && selectedIcon) iconPickerCallback(selectedIcon);
      iconModal.hide();
    });

    // ============================================================
    // NAV-LINKS
    // ============================================================
    function loadNavLinks() {
      ajax('get_navlinks', {}, function(data) { currentNavLinks = data.links || []; renderNavLinks(); });
    }

    function renderNavLinks() {
      var c = document.getElementById('navLinksList'), html = '';
      currentNavLinks.forEach(function(link, idx) {
        html += '<div class="navlink-row" data-index="' + idx + '">';
        html += '<span class="drag-handle"><i class="fa fa-bars"></i></span>';
        html += '<div class="input-group input-group-sm" style="width:140px;flex-shrink:0;"><span class="input-group-text"><i class="fa ' + (link.icon || 'fa-link') + '"></i></span>';
        html += '<input type="text" class="form-control" value="' + escapeHtml(link.icon || '') + '" id="navLinkIcon_' + idx + '" readonly>';
        html += '<button class="btn btn-outline-secondary" type="button" onclick="openIconPickerForNavLink(' + idx + ')"><i class="fa fa-th"></i></button></div>';
        html += '<input type="text" class="form-control form-control-sm" value="' + escapeHtml(link.url || '') + '" id="navLinkUrl_' + idx + '" placeholder="URL">';
        html += '<input type="text" class="form-control form-control-sm" value="' + escapeHtml(link.name || '') + '" id="navLinkName_' + idx + '" placeholder="Name / MRH_KONSTANTE">';
        html += '<div class="form-check form-switch" style="flex-shrink:0;"><input class="form-check-input" type="checkbox" id="navLinkActive_' + idx + '" ' + (link.is_active ? 'checked' : '') + '></div>';
        html += '<button class="btn btn-outline-danger btn-sm" onclick="removeNavLink(' + idx + ')"><i class="fa fa-trash"></i></button>';
        html += '</div>';
      });
      c.innerHTML = html || '<div class="text-center text-muted py-3">Keine Nav-Links.</div>';
      new Sortable(c, { handle: '.drag-handle', animation: 150, ghostClass: 'sortable-ghost' });
    }

    document.getElementById('btnAddNavLink').addEventListener('click', function() {
      syncNavLinksFromDOM(); currentNavLinks.push({ url:'', name:'', icon:'fa-link', is_active:1 }); renderNavLinks();
    });

    window.removeNavLink = function(idx) { syncNavLinksFromDOM(); currentNavLinks.splice(idx, 1); renderNavLinks(); };

    function syncNavLinksFromDOM() {
      var links = [];
      document.querySelectorAll('#navLinksList .navlink-row').forEach(function(row, idx) {
        links.push({
          url: (document.getElementById('navLinkUrl_' + idx) || {}).value || '',
          name: (document.getElementById('navLinkName_' + idx) || {}).value || '',
          icon: (document.getElementById('navLinkIcon_' + idx) || {}).value || '',
          is_active: (document.getElementById('navLinkActive_' + idx) || {}).checked ? 1 : 0
        });
      });
      currentNavLinks = links;
    }

    document.getElementById('btnSaveNavLinks').addEventListener('click', function() {
      syncNavLinksFromDOM();
      ajax('save_navlinks', { _body: { links: currentNavLinks } }, function(data) {
        showToast(data.success ? 'Nav-Links gespeichert!' : 'Fehler!', data.success ? 'success' : 'error');
      });
    });

    // ============================================================
    // SPRACHDATEI-EDITOR
    // ============================================================
    function loadLangConstants(lc) {
      currentLangCode = lc;
      document.querySelectorAll('.lang-select-btn').forEach(function(b) { b.classList.toggle('active', b.dataset.lang === lc); });
      document.getElementById('langConstantsList').innerHTML = '<div class="text-center py-3"><i class="fa fa-spinner fa-spin"></i></div>';
      ajax('get_lang_constants', { lang: lc }, function(data) { currentLangConstants = data.constants || {}; renderLangConstants(); });
    }

    function renderLangConstants() {
      var c = document.getElementById('langConstantsList'), keys = Object.keys(currentLangConstants).sort(), html = '';
      if (keys.length === 0) { c.innerHTML = '<div class="text-center text-muted py-3">Keine MRH_-Konstanten gefunden.</div>'; return; }
      keys.forEach(function(key) {
        html += '<div class="lang-editor-row"><span class="const-key">' + key + '</span>';
        html += '<input type="text" class="form-control form-control-sm" value="' + escapeHtml(currentLangConstants[key]) + '" data-key="' + key + '">';
        html += '<button class="btn btn-outline-danger btn-sm" onclick="removeLangConstant(\'' + key + '\')"><i class="fa fa-trash"></i></button></div>';
      });
      c.innerHTML = html;
    }

    document.querySelectorAll('.lang-select-btn').forEach(function(b) { b.addEventListener('click', function() { loadLangConstants(this.dataset.lang); }); });

    document.getElementById('btnAddConstant').addEventListener('click', function() {
      var key = prompt('Neue Konstante (muss mit MRH_ beginnen):', 'MRH_');
      if (!key || key.indexOf('MRH_') !== 0) { if (key) alert('Muss mit MRH_ beginnen!'); return; }
      key = key.toUpperCase().replace(/[^A-Z0-9_]/g, '');
      if (currentLangConstants[key] !== undefined) { alert('Existiert bereits!'); return; }
      syncLangConstantsFromDOM(); currentLangConstants[key] = ''; renderLangConstants();
    });

    window.removeLangConstant = function(key) {
      if (confirm('"' + key + '" entfernen?')) { syncLangConstantsFromDOM(); delete currentLangConstants[key]; renderLangConstants(); }
    };

    function syncLangConstantsFromDOM() {
      document.querySelectorAll('#langConstantsList input[data-key]').forEach(function(inp) { currentLangConstants[inp.dataset.key] = inp.value; });
    }

    document.getElementById('btnSaveLangConstants').addEventListener('click', function() {
      syncLangConstantsFromDOM();
      ajax('save_lang_constants', { _body: { lang: currentLangCode, constants: currentLangConstants } }, function(data) {
        showToast(data.success ? 'Konstanten (' + currentLangCode.toUpperCase() + ') gespeichert!' : 'Fehler! Dateiberechtigungen prüfen.', data.success ? 'success' : 'error');
      });
    });

    // Tab-Events
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function(tab) {
      tab.addEventListener('shown.bs.tab', function(e) {
        if (e.target.id === 'navlinks-tab') loadNavLinks();
        if (e.target.id === 'langeditor-tab') loadLangConstants('de');
      });
    });

  })();
  </script>
</body>
</html>
