<?php
/* -----------------------------------------------------------------------------------------
   $Id: mrh_dashboard.php 1.8.4 2026-04-02 Mr. Hanf $

   MRH Dashboard - Admin-Seite
   https://mr-hanf.at

   Copyright (c) 2026 Mr. Hanf
   -----------------------------------------------------------------------------------------
   Released under the GNU General Public License
   ---------------------------------------------------------------------------------------*/

// === Error-Logging (v1.8.4) ===
$_mrh_log_dir = dirname(__FILE__) . '/../includes/external/mrh_dashboard/logs';
if (!is_dir($_mrh_log_dir)) @mkdir($_mrh_log_dir, 0755, true);
$_mrh_log_file = $_mrh_log_dir . '/mrh_dashboard_error.log';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', $_mrh_log_file);
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($_mrh_log_file) {
  $msg = date('[Y-m-d H:i:s]') . " PHP Error [$errno]: $errstr in $errfile on line $errline" . PHP_EOL;
  @file_put_contents($_mrh_log_file, $msg, FILE_APPEND);
  return false;
});
set_exception_handler(function($e) use ($_mrh_log_file) {
  $msg = date('[Y-m-d H:i:s]') . " Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
  @file_put_contents($_mrh_log_file, $msg, FILE_APPEND);
});
register_shutdown_function(function() use ($_mrh_log_file) {
  $error = error_get_last();
  if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    $msg = date('[Y-m-d H:i:s]') . " FATAL: {$error['message']} in {$error['file']} on line {$error['line']}" . PHP_EOL;
    @file_put_contents($_mrh_log_file, $msg, FILE_APPEND);
  }
});

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

  // Promo-Config laden
  if ($_GET['ajax'] === 'get_promo_config') {
    $parent_id = (int)$_GET['parent_id'];
    $config = $megaMenuManager ? $megaMenuManager->getPromoConfig($parent_id) : null;
    echo json_encode(array('success' => true, 'promo' => $config));
    exit;
  }

  // Promo-Config speichern
  if ($_GET['ajax'] === 'save_promo_config') {
    $input = json_decode(file_get_contents('php://input'), true);
    $parent_id = (int)($input['parent_id'] ?? 0);
    $promo_data = $input['promo'] ?? array();

    $result = $megaMenuManager ? $megaMenuManager->savePromoConfig($parent_id, $promo_data) : false;

    // Cache regenerieren
    if ($result && $megaMenuManager) {
      $megaMenuManager->regenerateCache();
    }

    echo json_encode(array('success' => $result));
    exit;
  }

  // Banner-Gruppen laden
  if ($_GET['ajax'] === 'get_banner_groups') {
    $groups = $megaMenuManager ? $megaMenuManager->getBannerGroups() : array();
    echo json_encode(array('success' => true, 'groups' => $groups));
    exit;
  }

  // Banner einer Gruppe laden
  if ($_GET['ajax'] === 'get_banners') {
    $group = $_GET['group'] ?? '';
    $banners = $megaMenuManager ? $megaMenuManager->getBannersByGroup($group) : array();
    echo json_encode(array('success' => true, 'banners' => $banners));
    exit;
  }

  // Special-Produkte laden (Vorschau)
  if ($_GET['ajax'] === 'get_special_products') {
    $parent_id = (int)$_GET['parent_id'];
    $max = (int)($_GET['max'] ?? 3);
    $products = $megaMenuManager ? $megaMenuManager->getSpecialProducts($parent_id, $max) : array();
    echo json_encode(array('success' => true, 'products' => $products));
    exit;
  }

  // Neue Produkte laden (Vorschau)
  if ($_GET['ajax'] === 'get_new_products') {
    $parent_id = (int)$_GET['parent_id'];
    $max = (int)($_GET['max'] ?? 3);
    $products = $megaMenuManager ? $megaMenuManager->getNewProducts($parent_id, $max) : array();
    echo json_encode(array('success' => true, 'products' => $products));
    exit;
  }

  // Cache regenerieren
  if ($_GET['ajax'] === 'regenerate_cache') {
    $result = $megaMenuManager ? $megaMenuManager->regenerateCache() : false;
    echo json_encode(array('success' => $result));
    exit;
  }

  // Mobile-Promos laden
  if ($_GET['ajax'] === 'get_mobile_promos') {
    $promos = $megaMenuManager ? $megaMenuManager->getMobilePromos() : array();
    echo json_encode(array('success' => true, 'promos' => $promos));
    exit;
  }

  // Mobile-Promos speichern
  if ($_GET['ajax'] === 'save_mobile_promos') {
    $input = json_decode(file_get_contents('php://input'), true);
    $promos = $input['promos'] ?? array();
    $result = $megaMenuManager ? $megaMenuManager->saveMobilePromos($promos) : false;
    if ($result && $megaMenuManager) { $megaMenuManager->regenerateCache(); }
    echo json_encode(array('success' => $result));
    exit;
  }

  // Mobile-Icons laden
  if ($_GET['ajax'] === 'get_mobile_icons') {
    $icons = $megaMenuManager ? $megaMenuManager->getMobileIcons() : array();
    echo json_encode(array('success' => true, 'icons' => $icons));
    exit;
  }

  // Mobile-Icons speichern
  if ($_GET['ajax'] === 'save_mobile_icons') {
    $input = json_decode(file_get_contents('php://input'), true);
    $icons = $input['icons'] ?? array();
    $result = $megaMenuManager ? $megaMenuManager->saveMobileIcons($icons) : false;
    if ($result && $megaMenuManager) { $megaMenuManager->regenerateCache(); }
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
$version = defined('MODULE_MRH_DASHBOARD_VERSION') ? MODULE_MRH_DASHBOARD_VERSION : '1.3.0';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $heading_title; ?></title>
  <?php echo '<link rel="stylesheet" href="includes/stylesheet.css">'; ?>
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="../templates/tpl_mrh_2026/css/fontawesome-7.css" rel="stylesheet">
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
    .promo-config { border: 2px dashed #2d7a3a; border-radius: 10px; padding: 15px; margin-top: 15px; background: #f0faf2; }
    .promo-config .promo-header { font-weight: 600; color: #2d7a3a; margin-bottom: 10px; }
    .promo-preview { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 12px; margin-top: 10px; min-height: 60px; }
    .promo-preview img { max-width: 100%; max-height: 150px; border-radius: 4px; }
    .promo-product-item { display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid #eee; }
    .promo-product-item:last-child { border-bottom: none; }
    .promo-product-item .discount-badge { background: #dc3545; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
    .promo-product-item .new-badge { background: #2d7a3a; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
    .mobile-icon-row { display: flex; gap: 10px; align-items: center; margin-bottom: 8px; padding: 8px 12px; background: #f8f9fa; border-radius: 6px; }
    .mobile-promo-card { border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 12px; background: #fafbfc; }
    .mobile-promo-card .promo-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .mobile-promo-card .promo-card-header h6 { margin: 0; font-size: 0.9rem; }
  </style>
</head>
<body>
  <div class="container-fluid" style="max-width: 1400px; padding: 20px;">

    <!-- Header -->
    <div class="mrh-header d-flex justify-content-between align-items-center">
      <div>
        <h1><i class="fa-solid fa-gauge"></i> <?php echo $heading_title; ?></h1>
        <small class="opacity-75">Modulares Dashboard fuer Template-Funktionen</small>
      </div>
      <div><span class="badge bg-light text-dark">v<?php echo htmlspecialchars($version); ?></span></div>
    </div>

    <!-- Haupt-Tabs -->
    <ul class="nav nav-tabs mb-3" id="mainTabs" role="tablist">
      <li class="nav-item"><a class="nav-link active" id="megamenu-tab" data-bs-toggle="tab" href="#megamenu" role="tab"><i class="fa-solid fa-bars"></i> Mega-Menü</a></li>
      <li class="nav-item"><a class="nav-link" id="navlinks-tab" data-bs-toggle="tab" href="#navlinks" role="tab"><i class="fa-solid fa-link"></i> Nav-Links</a></li>
      <li class="nav-item"><a class="nav-link" id="langeditor-tab" data-bs-toggle="tab" href="#langeditor" role="tab"><i class="fa-solid fa-language"></i> Sprachdatei-Editor</a></li>
      <li class="nav-item"><a class="nav-link" id="mobilemenu-tab" data-bs-toggle="tab" href="#mobilemenu" role="tab"><i class="fa-solid fa-mobile-screen"></i> Mobile Menü</a></li>
    </ul>

    <div class="tab-content">

      <!-- TAB 1: Mega-Menü Manager -->
      <div class="tab-pane fade show active" id="megamenu" role="tabpanel">
        <div class="row">
          <div class="col-md-3">
            <div class="mrh-card">
              <div class="card-header"><i class="fa-solid fa-sitemap"></i> Hauptkategorien</div>
              <div class="card-body p-0">
                <div class="list-group list-group-flush" id="categoryList">
                  <?php foreach ($mainCategories as $cat): ?>
                    <?php $hasConfig = $megaMenuManager ? $megaMenuManager->hasConfig($cat['categories_id']) : false; ?>
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center category-item"
                       data-id="<?php echo (int)$cat['categories_id']; ?>"
                       data-name="<?php echo htmlspecialchars($cat['categories_name']); ?>">
                      <?php echo htmlspecialchars($cat['categories_name']); ?>
                      <?php if ($hasConfig): ?>
                        <span class="badge bg-success rounded-pill"><i class="fa-solid fa-check"></i></span>
                      <?php else: ?>
                        <span class="badge bg-secondary rounded-pill"><i class="fa-solid fa-minus"></i></span>
                      <?php endif; ?>
                    </a>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <div class="mrh-card">
              <div class="card-body text-center">
                <button class="btn btn-success btn-sm" id="btnRegenerateCache"><i class="fa-solid fa-arrows-rotate"></i> Cache regenerieren</button>
              </div>
            </div>
          </div>

              <div class="col-md-9">
            <div class="mrh-card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <span id="editorTitle"><i class="fa-solid fa-hand-pointer"></i> Kategorie auswählen...</span>
                <div>
                  <button class="btn btn-outline-success btn-sm" id="btnPromoConfig" style="display:none;"><i class="fa-solid fa-bullhorn"></i> Promo</button>
                  <button class="btn btn-primary btn-sm" id="btnAddColumn" style="display:none;"><i class="fa-solid fa-plus"></i> Spalte</button>
                  <button class="btn btn-success btn-sm" id="btnSaveConfig" style="display:none;"><i class="fa-solid fa-floppy-disk"></i> Speichern</button>
                </div>
              </div>
              <div class="card-body" id="editorArea">
                <div class="text-center text-muted py-5">
                  <i class="fa-solid fa-arrow-left fa-2x mb-3 d-block"></i>
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
            <span><i class="fa-solid fa-link"></i> Zusätzliche Navigationslinks</span>
            <div>
              <button class="btn btn-primary btn-sm" id="btnAddNavLink"><i class="fa-solid fa-plus"></i> Link</button>
              <button class="btn btn-success btn-sm" id="btnSaveNavLinks"><i class="fa-solid fa-floppy-disk"></i> Speichern</button>
            </div>
          </div>
          <div class="card-body">
            <div class="alert alert-info small mb-3">
              <i class="fa-solid fa-circle-info"></i>
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
            <span><i class="fa-solid fa-language"></i> MRH Sprachkonstanten</span>
            <div>
              <button class="btn btn-primary btn-sm" id="btnAddConstant"><i class="fa-solid fa-plus"></i> Konstante</button>
              <button class="btn btn-success btn-sm" id="btnSaveLangConstants"><i class="fa-solid fa-floppy-disk"></i> Speichern</button>
            </div>
          </div>
          <div class="card-body">
            <div class="alert alert-info small mb-3">
              <i class="fa-solid fa-circle-info"></i>
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

      <!-- TAB 4: Mobile Menü -->
      <div class="tab-pane fade" id="mobilemenu" role="tabpanel">
        <div class="row">
          <!-- Linke Spalte: Mobile-Icons -->
          <div class="col-md-6">
            <div class="mrh-card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-icons"></i> Kategorie-Icons (Mobile Menü)</span>
                <button class="btn btn-success btn-sm" id="btnSaveMobileIcons"><i class="fa-solid fa-floppy-disk"></i> Speichern</button>
              </div>
              <div class="card-body">
                <div class="alert alert-info small mb-3">
                  <i class="fa-solid fa-circle-info"></i>
                  Weise jeder Hauptkategorie ein <strong>Font Awesome 6 Icon</strong> zu.
                  Diese Icons werden im <strong>Mobile Offcanvas-Menü</strong> vor dem Kategorienamen angezeigt.
                </div>
                <div id="mobileIconsList">
                  <?php foreach ($mainCategories as $cat): ?>
                    <div class="mobile-icon-row" data-cat-id="<?php echo (int)$cat['categories_id']; ?>">
                      <div class="input-group input-group-sm" style="width:160px;flex-shrink:0;">
                        <span class="input-group-text"><span class="fa-solid fa-question" id="mobileIconPreview_<?php echo (int)$cat['categories_id']; ?>"></span></span>
                        <input type="text" class="form-control" id="mobileIcon_<?php echo (int)$cat['categories_id']; ?>" readonly placeholder="Icon wählen...">
                        <button class="btn btn-outline-secondary" type="button" onclick="openIconPickerForMobile(<?php echo (int)$cat['categories_id']; ?>)"><i class="fa-solid fa-table-cells"></i></button>
                      </div>
                      <span class="flex-grow-1 fw-medium"><?php echo htmlspecialchars($cat['categories_name']); ?></span>
                      <small class="text-muted">ID: <?php echo (int)$cat['categories_id']; ?></small>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Rechte Spalte: Mobile-Promos -->
          <div class="col-md-6">
            <div class="mrh-card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-bullhorn"></i> Mobile Promo/Banner</span>
                <div>
                  <button class="btn btn-primary btn-sm" id="btnAddMobilePromo"><i class="fa-solid fa-plus"></i> Promo</button>
                  <button class="btn btn-success btn-sm" id="btnSaveMobilePromos"><i class="fa-solid fa-floppy-disk"></i> Speichern</button>
                </div>
              </div>
              <div class="card-body">
                <div class="alert alert-info small mb-3">
                  <i class="fa-solid fa-circle-info"></i>
                  Füge <strong>Banner oder HTML-Werbung</strong> zum Mobile Menü hinzu.
                  Wähle die Position: <strong>Oben</strong> (über der Navigation) oder <strong>Unten</strong> (unter der Navigation).
                </div>
                <div id="mobilePromosList"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Icon-Picker Modal -->
  <div class="modal fade icon-picker-modal" id="iconPickerModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><span class="fa-solid fa-icons"></span> Icon auswählen (Font Awesome 6 Pro)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex gap-2 mb-3">
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-sm btn-outline-success active" data-icon-style="solid" onclick="switchIconStyle('solid',this)">Solid</button>
              <button type="button" class="btn btn-sm btn-outline-primary" data-icon-style="regular" onclick="switchIconStyle('regular',this)">Regular</button>
              <button type="button" class="btn btn-sm btn-outline-info" data-icon-style="light" onclick="switchIconStyle('light',this)">Light</button>
              <button type="button" class="btn btn-sm btn-outline-warning" data-icon-style="thin" onclick="switchIconStyle('thin',this)">Thin</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-icon-style="brands" onclick="switchIconStyle('brands',this)">Brands</button>
            </div>
            <input type="text" class="form-control form-control-sm flex-grow-1" id="iconSearchInput" placeholder="Icon suchen... (z.B. leaf, cannabis, star, home)">
          </div>
          <div class="icon-grid" id="iconGrid" style="max-height:400px;overflow-y:auto;"></div>
        </div>
        <div class="modal-footer">
          <span class="me-auto" id="iconPreview"><span class="fa-solid fa-question fa-2x"></span> <span class="ms-2 text-muted">Kein Icon</span></span>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>
          <button type="button" class="btn btn-success btn-sm" id="btnConfirmIcon">Übernehmen</button>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/Sortable.min.js"></script>

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

    // FA6 Pro Icon-Liste (Solid + Regular + Brands) - 2060 Icons
    var currentIconStyle = 'solid';
    var FA6_ICONS = <?php
      // fa6_icons.json liegt im selben Verzeichnis wie mrh_dashboard.php
      $iconFile = dirname(__FILE__) . '/fa6_icons.json';
      if (!file_exists($iconFile)) {
        // Fallback: ein Verzeichnis höher
        $iconFile = dirname(__FILE__) . '/../fa6_icons.json';
      }
      if (!file_exists($iconFile)) {
        // Fallback: relativ zum Shop-Root
        $iconFile = (defined('DIR_FS_CATALOG') ? DIR_FS_CATALOG : '') . 'admin/fa6_icons.json';
      }
      echo file_exists($iconFile) ? file_get_contents($iconFile) : '{"solid":[],"regular":[],"light":[],"thin":[],"brands":[]}';
    ?>;

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
      t.innerHTML = '<span class="' + (type === 'success' ? 'fa-solid fa-check' : 'fa-solid fa-xmark') + '"></span> ' + msg;
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
        document.getElementById('editorTitle').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> ' + this.dataset.name + ' <small class="text-muted">(ID: ' + currentParentId + ')</small>';
        document.getElementById('btnAddColumn').style.display = '';
        document.getElementById('btnSaveConfig').style.display = '';
        document.getElementById('btnPromoConfig').style.display = '';
        loadConfig(currentParentId);
      });
    });

    function loadConfig(pid) {
      document.getElementById('editorArea').innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
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
        html += '<div class="column-header"><div class="d-flex align-items-center gap-2"><span class="drag-handle"><i class="fa-solid fa-bars"></i></span><h6 class="mb-0">Spalte ' + (idx + 1) + '</h6></div>';
        html += '<button class="btn btn-outline-danger btn-sm" onclick="removeColumn(' + idx + ')"><i class="fa-solid fa-trash"></i></button></div>';

        // Icon + 4 Sprach-Titel in einer Zeile
        html += '<div class="row g-2 mb-2">';
        html += '<div class="col-md-2"><label class="form-label small fw-bold">Icon</label><div class="input-group input-group-sm">';
        html += '<span class="input-group-text"><span class="' + iconDisplayClass(col.icon || 'fa-solid fa-question') + '"></span></span>';
        html += '<input type="text" class="form-control" value="' + (col.icon || '') + '" id="colIcon_' + idx + '" readonly>';
        html += '<button class="btn btn-outline-secondary" type="button" onclick="openIconPicker(' + idx + ')"><i class="fa-solid fa-table-cells"></i></button>';
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
            html += '<div class="item-row" data-cat-id="' + item.category_id + '"><span class="drag-handle"><i class="fa-solid fa-bars"></i></span>';
            html += '<span class="item-label">' + escapeHtml(item.label || 'ID: ' + item.category_id) + ' <small class="text-muted">(ID: ' + item.category_id + ')</small></span>';
            html += '<button class="btn-remove" onclick="removeItem(' + idx + ',' + iIdx + ')"><i class="fa-solid fa-xmark"></i></button></div>';
          });
        }
        html += '</div>';

        html += '<div class="mt-2 d-flex gap-1"><select class="form-select form-select-sm" id="addCatSelect_' + idx + '" style="max-width:300px;"><option value="">Kategorie hinzufügen...</option></select>';
        html += '<button class="btn btn-outline-primary btn-sm" onclick="addItemFromSelect(' + idx + ')"><i class="fa-solid fa-plus"></i></button></div>';
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
          if (badge) { badge.className = 'badge bg-success rounded-pill'; badge.innerHTML = '<i class="fa-solid fa-check"></i>'; }
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
    // Hilfsfunktion: Icon-Klasse für Anzeige bauen
    function iconDisplayClass(fullIcon) {
      if (!fullIcon) return 'fa-solid fa-question';
      if (fullIcon.indexOf('fa-solid ') === 0 || fullIcon.indexOf('fa-regular ') === 0 || fullIcon.indexOf('fa-light ') === 0 || fullIcon.indexOf('fa-thin ') === 0 || fullIcon.indexOf('fa-brands ') === 0 || fullIcon.indexOf('fa-duotone ') === 0) return fullIcon;
      return 'fa-solid ' + fullIcon;
    }

    // Stil-Prefix für aktuellen Tab
    function getStylePrefix() {
      if (currentIconStyle === 'regular') return 'fa-regular';
      if (currentIconStyle === 'light') return 'fa-light';
      if (currentIconStyle === 'thin') return 'fa-thin';
      if (currentIconStyle === 'brands') return 'fa-brands';
      return 'fa-solid';
    }

    window.switchIconStyle = function(style, btn) {
      currentIconStyle = style;
      document.querySelectorAll('[data-icon-style]').forEach(function(b) { b.classList.remove('active'); });
      if (btn) btn.classList.add('active');
      renderIconGrid(document.getElementById('iconSearchInput').value);
    };

    window.openIconPicker = function(colIdx) {
      iconPickerCallback = function(icon) {
        syncAllFromDOM();
        currentColumns[colIdx].icon = icon;
        var inp = document.getElementById('colIcon_' + colIdx);
        if (inp) { inp.value = icon; inp.previousElementSibling.innerHTML = '<span class="' + iconDisplayClass(icon) + '"></span>'; }
      };
      selectedIcon = currentColumns[colIdx] ? currentColumns[colIdx].icon || '' : '';
      currentIconStyle = 'solid';
      document.querySelectorAll('[data-icon-style]').forEach(function(b) { b.classList.remove('active'); });
      var solidBtn = document.querySelector('[data-icon-style="solid"]'); if (solidBtn) solidBtn.classList.add('active');
      renderIconGrid(); if (!iconModal) iconModal = new bootstrap.Modal(document.getElementById('iconPickerModal')); iconModal.show();
    };

    window.openIconPickerForNavLink = function(idx) {
      iconPickerCallback = function(icon) {
        var inp = document.getElementById('navLinkIcon_' + idx);
        if (inp) { inp.value = icon; var p = inp.parentElement.querySelector('.input-group-text'); if (p) p.innerHTML = '<span class="' + iconDisplayClass(icon) + '"></span>'; }
      };
      selectedIcon = (document.getElementById('navLinkIcon_' + idx) || {}).value || '';
      currentIconStyle = 'solid';
      document.querySelectorAll('[data-icon-style]').forEach(function(b) { b.classList.remove('active'); });
      var solidBtn = document.querySelector('[data-icon-style="solid"]'); if (solidBtn) solidBtn.classList.add('active');
      renderIconGrid(); if (!iconModal) iconModal = new bootstrap.Modal(document.getElementById('iconPickerModal')); iconModal.show();
    };

    function renderIconGrid(filter) {
      var grid = document.getElementById('iconGrid');
      var html = '', search = (filter || '').toLowerCase();
      var icons = FA6_ICONS[currentIconStyle] || [];
      var prefix = getStylePrefix();
      icons.forEach(function(icon) {
        if (search && icon.toLowerCase().indexOf(search) === -1) return;
        var fullIcon = prefix + ' fa-' + icon;
        html += '<div class="icon-item' + (fullIcon === selectedIcon ? ' selected' : '') + '" data-icon="' + fullIcon + '" title="' + fullIcon + '"><span class="' + fullIcon + '"></span></div>';
      });
      grid.innerHTML = html || '<div class="text-muted p-3">Keine Icons gefunden.</div>';
      grid.querySelectorAll('.icon-item').forEach(function(el) {
        el.addEventListener('click', function() {
          grid.querySelectorAll('.icon-item').forEach(function(x) { x.classList.remove('selected'); });
          this.classList.add('selected'); selectedIcon = this.dataset.icon;
          document.getElementById('iconPreview').innerHTML = '<span class="' + selectedIcon + ' fa-2x"></span> <span class="ms-2">' + selectedIcon + '</span>';
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
        html += '<span class="drag-handle"><i class="fa-solid fa-bars"></i></span>';
        html += '<div class="input-group input-group-sm" style="width:140px;flex-shrink:0;"><span class="input-group-text"><span class="' + iconDisplayClass(link.icon || 'fa-solid fa-link') + '"></span></span>';
        html += '<input type="text" class="form-control" value="' + escapeHtml(link.icon || '') + '" id="navLinkIcon_' + idx + '" readonly>';
        html += '<button class="btn btn-outline-secondary" type="button" onclick="openIconPickerForNavLink(' + idx + ')"><i class="fa-solid fa-table-cells"></i></button></div>';
        html += '<input type="text" class="form-control form-control-sm" value="' + escapeHtml(link.url || '') + '" id="navLinkUrl_' + idx + '" placeholder="URL">';
        html += '<input type="text" class="form-control form-control-sm" value="' + escapeHtml(link.name || '') + '" id="navLinkName_' + idx + '" placeholder="Name / MRH_KONSTANTE">';
        html += '<div class="form-check form-switch" style="flex-shrink:0;"><input class="form-check-input" type="checkbox" id="navLinkActive_' + idx + '" ' + (link.is_active ? 'checked' : '') + '></div>';
        html += '<button class="btn btn-outline-danger btn-sm" onclick="removeNavLink(' + idx + ')"><i class="fa-solid fa-trash"></i></button>';
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
      document.getElementById('langConstantsList').innerHTML = '<div class="text-center py-3"><i class="fa-solid fa-spinner fa-spin"></i></div>';
      ajax('get_lang_constants', { lang: lc }, function(data) { currentLangConstants = data.constants || {}; renderLangConstants(); });
    }

    function renderLangConstants() {
      var c = document.getElementById('langConstantsList'), keys = Object.keys(currentLangConstants).sort(), html = '';
      if (keys.length === 0) { c.innerHTML = '<div class="text-center text-muted py-3">Keine MRH_-Konstanten gefunden.</div>'; return; }
      keys.forEach(function(key) {
        html += '<div class="lang-editor-row"><span class="const-key">' + key + '</span>';
        html += '<input type="text" class="form-control form-control-sm" value="' + escapeHtml(currentLangConstants[key]) + '" data-key="' + key + '">';
        html += '<button class="btn btn-outline-danger btn-sm" onclick="removeLangConstant(\'' + key + '\')"><i class="fa-solid fa-trash"></i></button></div>';
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

    // ============================================================
    // PROMO-KONFIGURATION
    // ============================================================
    var currentPromoConfig = { promo_type: 'none', html_content: '', banner_id: 0, banner_group: '', max_items: 3 };
    var promoModalEl = null;
    var promoModal = null;

    document.getElementById('btnPromoConfig').addEventListener('click', function() {
      if (!currentParentId) return;
      if (!promoModalEl) {
        promoModalEl = document.createElement('div');
        promoModalEl.className = 'modal fade';
        promoModalEl.id = 'promoModal';
        promoModalEl.tabIndex = -1;
        promoModalEl.innerHTML =
          '<div class="modal-dialog modal-lg">' +
          '<div class="modal-content">' +
          '<div class="modal-header" style="background:#f0faf2;">' +
          '<h5 class="modal-title"><i class="fa-solid fa-bullhorn" style="color:#2d7a3a;"></i> Promo-Konfiguration</h5>' +
          '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
          '</div>' +
          '<div class="modal-body" id="promoModalBody"></div>' +
          '<div class="modal-footer">' +
          '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>' +
          '<button type="button" class="btn btn-success btn-sm" id="btnSavePromo"><i class="fa-solid fa-floppy-disk"></i> Promo speichern</button>' +
          '</div>' +
          '</div></div>';
        document.body.appendChild(promoModalEl);
        promoModal = new bootstrap.Modal(promoModalEl);

        document.getElementById('btnSavePromo').addEventListener('click', function() {
          syncPromoFromDOM();
          ajax('save_promo_config', { _body: { parent_id: currentParentId, promo: currentPromoConfig } }, function(data) {
            if (data.success) {
              showToast('Promo gespeichert!', 'success');
              promoModal.hide();
            } else {
              showToast('Fehler beim Speichern!', 'error');
            }
          });
        });
      }
      // Promo-Config laden
      ajax('get_promo_config', { parent_id: currentParentId }, function(data) {
        currentPromoConfig = data.promo || { promo_type: 'none', html_content: '', banner_id: 0, banner_group: '', max_items: 3 };
        renderPromoModal();
        promoModal.show();
      });
    });

    function renderPromoModal() {
      var body = document.getElementById('promoModalBody');
      var type = currentPromoConfig.promo_type || 'none';
      var html = '';

      html += '<div class="mb-3">';
      html += '<label class="form-label fw-bold">Promo-Typ:</label>';
      html += '<select class="form-select" id="promoTypeSelect">';
      html += '<option value="none"' + (type === 'none' ? ' selected' : '') + '>Kein Promo</option>';
      html += '<option value="html"' + (type === 'html' ? ' selected' : '') + '>HTML-Content (freier Editor)</option>';
      html += '<option value="banner"' + (type === 'banner' ? ' selected' : '') + '>Banner (aus Bannermanager)</option>';
      html += '<option value="special"' + (type === 'special' ? ' selected' : '') + '>Angebote (dynamisch)</option>';
      html += '<option value="new"' + (type === 'new' ? ' selected' : '') + '>Neue Artikel (dynamisch)</option>';
      html += '</select></div>';

      html += '<div id="promoTypeContent"></div>';
      html += '<div id="promoPreviewArea"></div>';

      body.innerHTML = html;

      document.getElementById('promoTypeSelect').addEventListener('change', function() {
        currentPromoConfig.promo_type = this.value;
        renderPromoTypeContent(this.value);
      });

      renderPromoTypeContent(type);
    }

    function renderPromoTypeContent(type) {
      var container = document.getElementById('promoTypeContent');
      var preview = document.getElementById('promoPreviewArea');
      var html = '';

      if (type === 'html') {
        html += '<div class="mb-3">';
        html += '<label class="form-label fw-bold">Visueller Editor:</label>';
        // WYSIWYG Toolbar
        html += '<div class="wysiwyg-toolbar" style="display:flex;flex-wrap:wrap;gap:2px;padding:6px 8px;background:#f8f9fa;border:1px solid #dee2e6;border-bottom:none;border-radius:8px 8px 0 0;">';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="bold" title="Fett"><i class="fa-solid fa-bold"></i></button>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="italic" title="Kursiv"><i class="fa-solid fa-italic"></i></button>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="underline" title="Unterstrichen"><i class="fa-solid fa-underline"></i></button>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="strikeThrough" title="Durchgestrichen"><i class="fa-solid fa-strikethrough"></i></button>';
        html += '<span style="border-left:1px solid #ccc;margin:0 4px;"></span>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="justifyLeft" title="Links"><i class="fa-solid fa-align-left"></i></button>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="justifyCenter" title="Zentriert"><i class="fa-solid fa-align-center"></i></button>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="justifyRight" title="Rechts"><i class="fa-solid fa-align-right"></i></button>';
        html += '<span style="border-left:1px solid #ccc;margin:0 4px;"></span>';
        html += '<select class="form-select form-select-sm wysi-select" id="wysiHeading" style="width:auto;min-width:100px;" title="Überschrift">';
        html += '<option value="">Normal</option><option value="1">H1</option><option value="2">H2</option><option value="3">H3</option><option value="4">H4</option>';
        html += '</select>';
        html += '<select class="form-select form-select-sm wysi-select" id="wysiFontSize" style="width:auto;min-width:70px;" title="Schriftgröße">';
        html += '<option value="">Größe</option><option value="1">Klein</option><option value="2">Normal</option><option value="3">Mittel</option><option value="4">Groß</option><option value="5">Sehr groß</option><option value="6">Riesig</option><option value="7">Maximum</option>';
        html += '</select>';
        html += '<span style="border-left:1px solid #ccc;margin:0 4px;"></span>';
        html += '<label class="btn btn-sm btn-outline-secondary" title="Textfarbe" style="position:relative;overflow:hidden;"><i class="fa-solid fa-font" style="color:#c00;"></i><input type="color" id="wysiFontColor" value="#000000" style="position:absolute;left:0;top:0;width:100%;height:100%;opacity:0;cursor:pointer;"></label>';
        html += '<label class="btn btn-sm btn-outline-secondary" title="Hintergrundfarbe" style="position:relative;overflow:hidden;"><i class="fa-solid fa-paintbrush"></i><input type="color" id="wysiBackColor" value="#ffff00" style="position:absolute;left:0;top:0;width:100%;height:100%;opacity:0;cursor:pointer;"></label>';
        html += '<span style="border-left:1px solid #ccc;margin:0 4px;"></span>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="insertUnorderedList" title="Aufzählung"><i class="fa-solid fa-list-ul"></i></button>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="insertOrderedList" title="Nummerierung"><i class="fa-solid fa-list-ol"></i></button>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary" id="wysiLinkBtn" title="Link einfügen"><i class="fa-solid fa-link"></i></button>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="removeFormat" title="Formatierung entfernen"><i class="fa-solid fa-eraser"></i></button>';
        html += '<span style="border-left:1px solid #ccc;margin:0 4px;"></span>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary" id="wysiImageBtn" title="Bild einfügen"><i class="fa-solid fa-image"></i></button>';
        html += '<button type="button" class="btn btn-sm btn-outline-warning" id="wysiToggleSource" title="HTML-Quellcode anzeigen/bearbeiten"><i class="fa-solid fa-code"></i></button>';
        html += '</div>';
        // Editierbarer Bereich
        html += '<div id="promoWysiwyg" contenteditable="true" style="border:1px solid #dee2e6;border-radius:0 0 8px 8px;padding:12px 15px;min-height:120px;max-height:300px;overflow-y:auto;background:#fff;font-size:0.95rem;line-height:1.5;outline:none;">' + (currentPromoConfig.html_content || '') + '</div>';
        html += '<textarea class="form-control mt-2" id="promoHtmlContent" rows="6" style="font-family:monospace;font-size:0.8rem;display:none;">' + escapeHtml(currentPromoConfig.html_content || '') + '</textarea>';
        html += '</div>';
        html += '<div class="mb-3"><label class="form-label fw-bold">Vorschau:</label>';
        html += '<div class="promo-preview" id="promoHtmlPreview"></div></div>';

      } else if (type === 'banner') {
        html += '<div class="row g-3">';
        html += '<div class="col-md-6"><label class="form-label fw-bold">Banner-Gruppe:</label>';
        html += '<select class="form-select" id="promoBannerGroup"><option value="">Laden...</option></select></div>';
        html += '<div class="col-md-6"><label class="form-label fw-bold">Banner:</label>';
        html += '<select class="form-select" id="promoBannerSelect"><option value="">Erst Gruppe wählen...</option></select></div>';
        html += '</div>';
        html += '<div class="mt-3"><label class="form-label fw-bold">Vorschau:</label>';
        html += '<div class="promo-preview" id="promoBannerPreview">Kein Banner ausgewählt</div></div>';

      } else if (type === 'special') {
        html += '<div class="mb-3">';
        html += '<label class="form-label fw-bold">Maximale Anzahl Produkte:</label>';
        html += '<input type="number" class="form-control" id="promoMaxItems" value="' + (currentPromoConfig.max_items || 3) + '" min="1" max="10" style="max-width:100px;">';
        html += '</div>';
        html += '<div class="alert alert-info small"><i class="fa-solid fa-circle-info"></i> Zeigt automatisch Sonderangebote mit Produktname und Rabatt-% aus dieser Kategorie.</div>';
        html += '<button class="btn btn-outline-primary btn-sm" id="btnPreviewSpecials"><i class="fa-solid fa-eye"></i> Vorschau laden</button>';
        html += '<div class="promo-preview mt-2" id="promoSpecialPreview"></div>';

      } else if (type === 'new') {
        html += '<div class="mb-3">';
        html += '<label class="form-label fw-bold">Maximale Anzahl Produkte:</label>';
        html += '<input type="number" class="form-control" id="promoMaxItems" value="' + (currentPromoConfig.max_items || 3) + '" min="1" max="10" style="max-width:100px;">';
        html += '</div>';
        html += '<div class="alert alert-info small"><i class="fa-solid fa-circle-info"></i> Zeigt automatisch die neuesten Produkte aus dieser Kategorie.</div>';
        html += '<button class="btn btn-outline-primary btn-sm" id="btnPreviewNew"><i class="fa-solid fa-eye"></i> Vorschau laden</button>';
        html += '<div class="promo-preview mt-2" id="promoNewPreview"></div>';

      } else {
        html += '<div class="text-muted">Kein Promo-Feld wird angezeigt.</div>';
      }

      container.innerHTML = html;

      // Event-Listener für Typ-spezifische Aktionen
      if (type === 'html') {
        var wysi = document.getElementById('promoWysiwyg');
        var ta = document.getElementById('promoHtmlContent');
        var prev = document.getElementById('promoHtmlPreview');
        var sourceMode = false;

        if (wysi && ta && prev) {
          prev.innerHTML = currentPromoConfig.html_content || '<span class="text-muted">Kein Content</span>';

          // WYSIWYG → Vorschau + Textarea sync
          wysi.addEventListener('input', function() {
            if (!sourceMode) {
              ta.value = wysi.innerHTML;
              prev.innerHTML = wysi.innerHTML || '<span class="text-muted">Kein Content</span>';
            }
          });

          // Toolbar-Buttons (execCommand)
          document.querySelectorAll('.wysi-btn[data-cmd]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
              e.preventDefault();
              wysi.focus();
              document.execCommand(this.dataset.cmd, false, null);
              ta.value = wysi.innerHTML;
              prev.innerHTML = wysi.innerHTML;
            });
          });

          // Überschrift-Auswahl
          var headingSel = document.getElementById('wysiHeading');
          if (headingSel) headingSel.addEventListener('change', function() {
            wysi.focus();
            if (this.value) {
              document.execCommand('formatBlock', false, 'H' + this.value);
            } else {
              document.execCommand('formatBlock', false, 'P');
            }
            ta.value = wysi.innerHTML;
            prev.innerHTML = wysi.innerHTML;
            this.value = '';
          });

          // Schriftgröße
          var sizeSel = document.getElementById('wysiFontSize');
          if (sizeSel) sizeSel.addEventListener('change', function() {
            if (this.value) {
              wysi.focus();
              document.execCommand('fontSize', false, this.value);
              ta.value = wysi.innerHTML;
              prev.innerHTML = wysi.innerHTML;
            }
            this.value = '';
          });

          // Textfarbe
          var fontColor = document.getElementById('wysiFontColor');
          if (fontColor) fontColor.addEventListener('input', function() {
            wysi.focus();
            document.execCommand('foreColor', false, this.value);
            ta.value = wysi.innerHTML;
            prev.innerHTML = wysi.innerHTML;
          });

          // Hintergrundfarbe
          var backColor = document.getElementById('wysiBackColor');
          if (backColor) backColor.addEventListener('input', function() {
            wysi.focus();
            document.execCommand('hiliteColor', false, this.value);
            ta.value = wysi.innerHTML;
            prev.innerHTML = wysi.innerHTML;
          });

          // Link einfügen
          var linkBtn = document.getElementById('wysiLinkBtn');
          if (linkBtn) linkBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var url = prompt('Link-URL eingeben:', 'https://');
            if (url) {
              wysi.focus();
              document.execCommand('createLink', false, url);
              ta.value = wysi.innerHTML;
              prev.innerHTML = wysi.innerHTML;
            }
          });

          // Bild einfügen
          var imgBtn = document.getElementById('wysiImageBtn');
          if (imgBtn) imgBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var url = prompt('Bild-URL eingeben:', '/images/banner/');
            if (url) {
              wysi.focus();
              document.execCommand('insertImage', false, url);
              ta.value = wysi.innerHTML;
              prev.innerHTML = wysi.innerHTML;
            }
          });

          // Toggle HTML-Quellcode
          var toggleBtn = document.getElementById('wysiToggleSource');
          if (toggleBtn) toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sourceMode = !sourceMode;
            if (sourceMode) {
              // Wechsel zu Quellcode-Ansicht
              ta.value = wysi.innerHTML;
              ta.style.display = 'block';
              wysi.style.display = 'none';
              this.classList.remove('btn-outline-warning');
              this.classList.add('btn-warning');
            } else {
              // Wechsel zurück zu WYSIWYG
              wysi.innerHTML = ta.value;
              wysi.style.display = 'block';
              ta.style.display = 'none';
              prev.innerHTML = ta.value || '<span class="text-muted">Kein Content</span>';
              this.classList.remove('btn-warning');
              this.classList.add('btn-outline-warning');
            }
          });

          // Textarea → Vorschau sync (im Source-Modus)
          ta.addEventListener('input', function() {
            if (sourceMode) {
              prev.innerHTML = this.value || '<span class="text-muted">Kein Content</span>';
            }
          });
        }
      }

      if (type === 'banner') {
        loadBannerGroups();
      }

      if (type === 'special') {
        var btn = document.getElementById('btnPreviewSpecials');
        if (btn) btn.addEventListener('click', function() {
          var max = parseInt((document.getElementById('promoMaxItems') || {}).value) || 3;
          ajax('get_special_products', { parent_id: currentParentId, max: max }, function(data) {
            renderProductPreview(data.products || [], 'promoSpecialPreview', 'special');
          });
        });
      }

      if (type === 'new') {
        var btn = document.getElementById('btnPreviewNew');
        if (btn) btn.addEventListener('click', function() {
          var max = parseInt((document.getElementById('promoMaxItems') || {}).value) || 3;
          ajax('get_new_products', { parent_id: currentParentId, max: max }, function(data) {
            renderProductPreview(data.products || [], 'promoNewPreview', 'new');
          });
        });
      }
    }

    function loadBannerGroups() {
      ajax('get_banner_groups', {}, function(data) {
        var sel = document.getElementById('promoBannerGroup');
        if (!sel) return;
        var html = '<option value="">Gruppe wählen...</option>';
        (data.groups || []).forEach(function(g) {
          html += '<option value="' + escapeHtml(g) + '"' + (g === currentPromoConfig.banner_group ? ' selected' : '') + '>' + escapeHtml(g) + '</option>';
        });
        sel.innerHTML = html;

        sel.addEventListener('change', function() {
          currentPromoConfig.banner_group = this.value;
          if (this.value) loadBannersForGroup(this.value);
        });

        // Wenn bereits eine Gruppe gesetzt ist, Banner laden
        if (currentPromoConfig.banner_group) {
          loadBannersForGroup(currentPromoConfig.banner_group);
        }
      });
    }

    function loadBannersForGroup(group) {
      ajax('get_banners', { group: group }, function(data) {
        var sel = document.getElementById('promoBannerSelect');
        if (!sel) return;
        var html = '<option value="0">Banner wählen...</option>';
        (data.banners || []).forEach(function(b) {
          html += '<option value="' + b.id + '"' + (b.id === currentPromoConfig.banner_id ? ' selected' : '') + '>' + escapeHtml(b.title) + ' (ID: ' + b.id + ')</option>';
        });
        sel.innerHTML = html;

        sel.addEventListener('change', function() {
          currentPromoConfig.banner_id = parseInt(this.value);
          // Vorschau aktualisieren
          var banner = (data.banners || []).find(function(b) { return b.id === parseInt(sel.value); });
          var prev = document.getElementById('promoBannerPreview');
          if (prev && banner) {
            if (banner.image) {
              prev.innerHTML = '<img src="/' + banner.image + '" alt="' + escapeHtml(banner.title) + '" style="max-width:100%;max-height:200px;"><br><small class="text-muted">' + escapeHtml(banner.title) + '</small>';
            } else if (banner.html_text) {
              prev.innerHTML = banner.html_text;
            } else {
              prev.innerHTML = '<span class="text-muted">' + escapeHtml(banner.title) + ' (kein Bild)</span>';
            }
          } else if (prev) {
            prev.innerHTML = '<span class="text-muted">Kein Banner ausgewählt</span>';
          }
        });

        // Wenn bereits ein Banner gesetzt ist, Vorschau zeigen
        if (currentPromoConfig.banner_id) {
          sel.dispatchEvent(new Event('change'));
        }
      });
    }

    function renderProductPreview(products, containerId, type) {
      var container = document.getElementById(containerId);
      if (!container) return;
      if (!products.length) {
        container.innerHTML = '<span class="text-muted">Keine Produkte gefunden.</span>';
        return;
      }
      var html = '';
      products.forEach(function(p) {
        html += '<div class="promo-product-item">';
        if (p.image) html += '<img src="/images/product_images/thumbnail_images/' + p.image + '" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">';
        html += '<span style="flex:1;">' + escapeHtml(p.name) + '</span>';
        if (type === 'special' && p.discount) {
          html += '<span class="discount-badge">-' + p.discount + '%</span>';
        }
        if (type === 'new') {
          html += '<span class="new-badge">NEU</span>';
        }
        html += '</div>';
      });
      container.innerHTML = html;
    }

    function syncPromoFromDOM() {
      var type = (document.getElementById('promoTypeSelect') || {}).value || 'none';
      currentPromoConfig.promo_type = type;

      if (type === 'html') {
        // WYSIWYG: Wenn der visuelle Editor sichtbar ist, dessen innerHTML verwenden
        var wysi = document.getElementById('promoWysiwyg');
        var ta = document.getElementById('promoHtmlContent');
        if (wysi && wysi.style.display !== 'none') {
          currentPromoConfig.html_content = wysi.innerHTML || '';
        } else if (ta) {
          currentPromoConfig.html_content = ta.value || '';
        }
      }
      if (type === 'banner') {
        currentPromoConfig.banner_id = parseInt((document.getElementById('promoBannerSelect') || {}).value) || 0;
        currentPromoConfig.banner_group = (document.getElementById('promoBannerGroup') || {}).value || '';
      }
      if (type === 'special' || type === 'new') {
        currentPromoConfig.max_items = parseInt((document.getElementById('promoMaxItems') || {}).value) || 3;
      }
    }

    // ============================================================
    // MOBILE MENÜ
    // ============================================================
    var currentMobilePromos = [];

    window.openIconPickerForMobile = function(catId) {
      iconPickerCallback = function(icon) {
        var inp = document.getElementById('mobileIcon_' + catId);
        var prev = document.getElementById('mobileIconPreview_' + catId);
        if (inp) inp.value = icon;
        if (prev) prev.className = iconDisplayClass(icon);
      };
      selectedIcon = (document.getElementById('mobileIcon_' + catId) || {}).value || '';
      currentIconStyle = 'solid';
      document.querySelectorAll('[data-icon-style]').forEach(function(b) { b.classList.remove('active'); });
      var solidBtn = document.querySelector('[data-icon-style="solid"]'); if (solidBtn) solidBtn.classList.add('active');
      renderIconGrid(); if (!iconModal) iconModal = new bootstrap.Modal(document.getElementById('iconPickerModal')); iconModal.show();
    };

    function loadMobileIcons() {
      ajax('get_mobile_icons', {}, function(data) {
        var icons = data.icons || {};
        for (var catId in icons) {
          var inp = document.getElementById('mobileIcon_' + catId);
          var prev = document.getElementById('mobileIconPreview_' + catId);
          if (inp) inp.value = icons[catId];
          if (prev) prev.className = iconDisplayClass(icons[catId]);
        }
      });
    }

    document.getElementById('btnSaveMobileIcons').addEventListener('click', function() {
      var icons = {};
      document.querySelectorAll('.mobile-icon-row').forEach(function(row) {
        var catId = row.dataset.catId;
        var inp = document.getElementById('mobileIcon_' + catId);
        if (inp && inp.value) icons[catId] = inp.value;
      });
      ajax('save_mobile_icons', { _body: { icons: icons } }, function(data) {
        showToast(data.success ? 'Mobile-Icons gespeichert!' : 'Fehler!', data.success ? 'success' : 'error');
      });
    });

    function loadMobilePromos() {
      ajax('get_mobile_promos', {}, function(data) {
        currentMobilePromos = data.promos || [];
        renderMobilePromos();
      });
    }

    function renderMobilePromos() {
      var c = document.getElementById('mobilePromosList'), html = '';
      if (currentMobilePromos.length === 0) {
        c.innerHTML = '<div class="text-center text-muted py-3">Keine Mobile-Promos. Klicke "Promo" um zu beginnen.</div>';
        return;
      }
      currentMobilePromos.forEach(function(promo, idx) {
        html += '<div class="mobile-promo-card" data-index="' + idx + '">';
        html += '<div class="promo-card-header"><h6><i class="fa-solid fa-bullhorn" style="color:#2d7a3a;"></i> Promo ' + (idx + 1) + '</h6>';
        html += '<button class="btn btn-outline-danger btn-sm" onclick="removeMobilePromo(' + idx + ')"><i class="fa-solid fa-trash"></i></button></div>';

        // Typ
        html += '<div class="row g-2 mb-2">';
        html += '<div class="col-md-4"><label class="form-label small fw-bold">Typ:</label>';
        html += '<select class="form-select form-select-sm" id="mobilePromoType_' + idx + '">';
        html += '<option value="none"' + (promo.promo_type === 'none' ? ' selected' : '') + '>Kein Promo</option>';
        html += '<option value="html"' + (promo.promo_type === 'html' ? ' selected' : '') + '>HTML-Content</option>';
        html += '<option value="banner"' + (promo.promo_type === 'banner' ? ' selected' : '') + '>Banner</option>';
        html += '</select></div>';

        // Position
        html += '<div class="col-md-4"><label class="form-label small fw-bold">Position:</label>';
        html += '<select class="form-select form-select-sm" id="mobilePromoPos_' + idx + '">';
        html += '<option value="top"' + (promo.promo_position === 'top' ? ' selected' : '') + '>Über Navigation</option>';
        html += '<option value="bottom"' + (promo.promo_position === 'bottom' ? ' selected' : '') + '>Unter Navigation</option>';
        html += '</select></div>';

        // Aktiv
        html += '<div class="col-md-4"><label class="form-label small fw-bold">Aktiv:</label>';
        html += '<div class="form-check form-switch mt-1"><input class="form-check-input" type="checkbox" id="mobilePromoActive_' + idx + '"' + (promo.is_active ? ' checked' : '') + '></div></div>';
        html += '</div>';

        // HTML-Content (mit WYSIWYG-Editor wie Desktop)
        if (promo.promo_type === 'html') {
          html += '<div class="mb-2"><label class="form-label small fw-bold">Visueller Editor:</label>';
          // WYSIWYG Toolbar
          html += '<div class="wysiwyg-toolbar" style="display:flex;flex-wrap:wrap;gap:2px;padding:5px 6px;background:#f8f9fa;border:1px solid #dee2e6;border-bottom:none;border-radius:8px 8px 0 0;">';
          html += '<button type="button" class="btn btn-sm btn-outline-secondary mwysi-btn" data-idx="' + idx + '" data-cmd="bold" title="Fett"><i class="fa-solid fa-bold"></i></button>';
          html += '<button type="button" class="btn btn-sm btn-outline-secondary mwysi-btn" data-idx="' + idx + '" data-cmd="italic" title="Kursiv"><i class="fa-solid fa-italic"></i></button>';
          html += '<button type="button" class="btn btn-sm btn-outline-secondary mwysi-btn" data-idx="' + idx + '" data-cmd="underline" title="Unterstrichen"><i class="fa-solid fa-underline"></i></button>';
          html += '<span style="border-left:1px solid #ccc;margin:0 3px;"></span>';
          html += '<button type="button" class="btn btn-sm btn-outline-secondary mwysi-btn" data-idx="' + idx + '" data-cmd="justifyLeft" title="Links"><i class="fa-solid fa-align-left"></i></button>';
          html += '<button type="button" class="btn btn-sm btn-outline-secondary mwysi-btn" data-idx="' + idx + '" data-cmd="justifyCenter" title="Zentriert"><i class="fa-solid fa-align-center"></i></button>';
          html += '<span style="border-left:1px solid #ccc;margin:0 3px;"></span>';
          html += '<label class="btn btn-sm btn-outline-secondary" title="Textfarbe" style="position:relative;overflow:hidden;"><i class="fa-solid fa-font" style="color:#c00;"></i><input type="color" class="mwysi-fontcolor" data-idx="' + idx + '" value="#000000" style="position:absolute;left:0;top:0;width:100%;height:100%;opacity:0;cursor:pointer;"></label>';
          html += '<label class="btn btn-sm btn-outline-secondary" title="Hintergrundfarbe" style="position:relative;overflow:hidden;"><i class="fa-solid fa-paintbrush"></i><input type="color" class="mwysi-backcolor" data-idx="' + idx + '" value="#ffff00" style="position:absolute;left:0;top:0;width:100%;height:100%;opacity:0;cursor:pointer;"></label>';
          html += '<span style="border-left:1px solid #ccc;margin:0 3px;"></span>';
          html += '<button type="button" class="btn btn-sm btn-outline-secondary mwysi-link" data-idx="' + idx + '" title="Link einf\u00fcgen"><i class="fa-solid fa-link"></i></button>';
          html += '<button type="button" class="btn btn-sm btn-outline-secondary mwysi-image" data-idx="' + idx + '" title="Bild einf\u00fcgen"><i class="fa-solid fa-image"></i></button>';
          html += '<button type="button" class="btn btn-sm btn-outline-secondary mwysi-btn" data-idx="' + idx + '" data-cmd="removeFormat" title="Formatierung entfernen"><i class="fa-solid fa-eraser"></i></button>';
          html += '<button type="button" class="btn btn-sm btn-outline-warning mwysi-toggle" data-idx="' + idx + '" title="HTML-Quellcode"><i class="fa-solid fa-code"></i></button>';
          html += '</div>';
          // Editierbarer Bereich
          html += '<div id="mobilePromoWysi_' + idx + '" contenteditable="true" style="border:1px solid #dee2e6;border-radius:0 0 8px 8px;padding:10px 12px;min-height:80px;max-height:200px;overflow-y:auto;background:#fff;font-size:0.9rem;line-height:1.4;outline:none;">' + (promo.html_content || '') + '</div>';
          html += '<textarea class="form-control form-control-sm mt-1" rows="4" id="mobilePromoHtml_' + idx + '" style="font-family:monospace;font-size:0.8rem;display:none;">' + escapeHtml(promo.html_content || '') + '</textarea>';
          html += '</div>';
        }

        // Banner
        if (promo.promo_type === 'banner') {
          html += '<div class="mb-2"><label class="form-label small fw-bold">Banner-Gruppe:</label>';
          html += '<select class="form-select form-select-sm" id="mobilePromoBannerGroup_' + idx + '"><option value="">Gruppe wählen...</option></select></div>';
          html += '<div class="mb-2"><label class="form-label small fw-bold">Banner:</label>';
          html += '<select class="form-select form-select-sm" id="mobilePromoBannerSelect_' + idx + '"><option value="0">Banner wählen...</option></select></div>';
          html += '<div id="mobilePromoBannerPreview_' + idx + '" class="promo-preview"><span class="text-muted">Kein Banner ausgewählt</span></div>';
        }

        html += '</div>';
      });
      c.innerHTML = html;

      // Banner-Gruppen laden + WYSIWYG-Editor initialisieren
      currentMobilePromos.forEach(function(promo, idx) {
        if (promo.promo_type === 'banner') {
          loadMobileBannerGroups(idx, promo.banner_group, promo.banner_id);
        }

        // WYSIWYG-Editor Events für HTML-Promos
        if (promo.promo_type === 'html') {
          var wysi = document.getElementById('mobilePromoWysi_' + idx);
          var ta = document.getElementById('mobilePromoHtml_' + idx);
          if (wysi && ta) {
            wysi.addEventListener('input', function() {
              if (wysi.style.display !== 'none') ta.value = wysi.innerHTML;
            });
          }
        }

        // Typ-Änderung
        var typeSel = document.getElementById('mobilePromoType_' + idx);
        if (typeSel) typeSel.addEventListener('change', function() {
          syncMobilePromosFromDOM();
          currentMobilePromos[idx].promo_type = this.value;
          renderMobilePromos();
        });
      });

      // WYSIWYG Toolbar-Buttons (execCommand)
      c.querySelectorAll('.mwysi-btn[data-cmd]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          var i = this.dataset.idx;
          var wysi = document.getElementById('mobilePromoWysi_' + i);
          var ta = document.getElementById('mobilePromoHtml_' + i);
          if (wysi) { wysi.focus(); document.execCommand(this.dataset.cmd, false, null); if (ta) ta.value = wysi.innerHTML; }
        });
      });

      // Textfarbe
      c.querySelectorAll('.mwysi-fontcolor').forEach(function(inp) {
        inp.addEventListener('input', function() {
          var wysi = document.getElementById('mobilePromoWysi_' + this.dataset.idx);
          var ta = document.getElementById('mobilePromoHtml_' + this.dataset.idx);
          if (wysi) { wysi.focus(); document.execCommand('foreColor', false, this.value); if (ta) ta.value = wysi.innerHTML; }
        });
      });

      // Hintergrundfarbe
      c.querySelectorAll('.mwysi-backcolor').forEach(function(inp) {
        inp.addEventListener('input', function() {
          var wysi = document.getElementById('mobilePromoWysi_' + this.dataset.idx);
          var ta = document.getElementById('mobilePromoHtml_' + this.dataset.idx);
          if (wysi) { wysi.focus(); document.execCommand('hiliteColor', false, this.value); if (ta) ta.value = wysi.innerHTML; }
        });
      });

      // Link einfügen
      c.querySelectorAll('.mwysi-link').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          var wysi = document.getElementById('mobilePromoWysi_' + this.dataset.idx);
          var ta = document.getElementById('mobilePromoHtml_' + this.dataset.idx);
          var url = prompt('Link-URL eingeben:', 'https://');
          if (url && wysi) { wysi.focus(); document.execCommand('createLink', false, url); if (ta) ta.value = wysi.innerHTML; }
        });
      });

      // Bild einfügen
      c.querySelectorAll('.mwysi-image').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          var wysi = document.getElementById('mobilePromoWysi_' + this.dataset.idx);
          var ta = document.getElementById('mobilePromoHtml_' + this.dataset.idx);
          var url = prompt('Bild-URL eingeben:', '/images/banner/');
          if (url && wysi) { wysi.focus(); document.execCommand('insertImage', false, url); if (ta) ta.value = wysi.innerHTML; }
        });
      });

      // Toggle HTML-Quellcode
      c.querySelectorAll('.mwysi-toggle').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          var i = this.dataset.idx;
          var wysi = document.getElementById('mobilePromoWysi_' + i);
          var ta = document.getElementById('mobilePromoHtml_' + i);
          if (!wysi || !ta) return;
          if (ta.style.display !== 'none') {
            wysi.innerHTML = ta.value;
            wysi.style.display = 'block'; ta.style.display = 'none';
            this.classList.remove('btn-warning'); this.classList.add('btn-outline-warning');
          } else {
            ta.value = wysi.innerHTML;
            ta.style.display = 'block'; wysi.style.display = 'none';
            this.classList.remove('btn-outline-warning'); this.classList.add('btn-warning');
          }
        });
      });
    }

    function loadMobileBannerGroups(idx, selectedGroup, selectedBannerId) {
      ajax('get_banner_groups', {}, function(data) {
        var sel = document.getElementById('mobilePromoBannerGroup_' + idx);
        if (!sel) return;
        var html = '<option value="">Gruppe wählen...</option>';
        (data.groups || []).forEach(function(g) {
          html += '<option value="' + escapeHtml(g) + '"' + (g === selectedGroup ? ' selected' : '') + '>' + escapeHtml(g) + '</option>';
        });
        sel.innerHTML = html;
        sel.addEventListener('change', function() {
          if (this.value) loadMobileBanners(idx, this.value, selectedBannerId);
        });
        if (selectedGroup) loadMobileBanners(idx, selectedGroup, selectedBannerId);
      });
    }

    function loadMobileBanners(idx, group, selectedBannerId) {
      ajax('get_banners', { group: group }, function(data) {
        var sel = document.getElementById('mobilePromoBannerSelect_' + idx);
        if (!sel) return;
        var html = '<option value="0">Banner wählen...</option>';
        (data.banners || []).forEach(function(b) {
          html += '<option value="' + b.id + '"' + (b.id === selectedBannerId ? ' selected' : '') + '>' + escapeHtml(b.title) + ' (ID: ' + b.id + ')</option>';
        });
        sel.innerHTML = html;
        sel.addEventListener('change', function() {
          var banner = (data.banners || []).find(function(b) { return b.id === parseInt(sel.value); });
          var prev = document.getElementById('mobilePromoBannerPreview_' + idx);
          if (prev && banner) {
            if (banner.image) {
              prev.innerHTML = '<img src="/' + banner.image + '" alt="' + escapeHtml(banner.title) + '" style="max-width:100%;max-height:120px;"><br><small class="text-muted">' + escapeHtml(banner.title) + '</small>';
            } else if (banner.html_text) {
              prev.innerHTML = banner.html_text;
            } else {
              prev.innerHTML = '<span class="text-muted">' + escapeHtml(banner.title) + ' (kein Bild)</span>';
            }
          } else if (prev) {
            prev.innerHTML = '<span class="text-muted">Kein Banner ausgewählt</span>';
          }
        });
        if (selectedBannerId) sel.dispatchEvent(new Event('change'));
      });
    }

    document.getElementById('btnAddMobilePromo').addEventListener('click', function() {
      syncMobilePromosFromDOM();
      currentMobilePromos.push({ promo_type: 'html', promo_position: 'bottom', html_content: '', banner_id: 0, banner_group: '', is_active: 1 });
      renderMobilePromos();
    });

    window.removeMobilePromo = function(idx) {
      if (confirm('Promo ' + (idx+1) + ' entfernen?')) {
        syncMobilePromosFromDOM();
        currentMobilePromos.splice(idx, 1);
        renderMobilePromos();
      }
    };

    function syncMobilePromosFromDOM() {
      currentMobilePromos.forEach(function(promo, idx) {
        var typeSel = document.getElementById('mobilePromoType_' + idx);
        var posSel = document.getElementById('mobilePromoPos_' + idx);
        var activeCb = document.getElementById('mobilePromoActive_' + idx);
        var htmlTa = document.getElementById('mobilePromoHtml_' + idx);
        var wysi = document.getElementById('mobilePromoWysi_' + idx);
        var bannerGroup = document.getElementById('mobilePromoBannerGroup_' + idx);
        var bannerSel = document.getElementById('mobilePromoBannerSelect_' + idx);

        if (typeSel) promo.promo_type = typeSel.value;
        if (posSel) promo.promo_position = posSel.value;
        if (activeCb) promo.is_active = activeCb.checked ? 1 : 0;
        // WYSIWYG: Wenn der visuelle Editor sichtbar ist, dessen Content nehmen
        if (wysi && wysi.style.display !== 'none') {
          promo.html_content = wysi.innerHTML;
          if (htmlTa) htmlTa.value = wysi.innerHTML;
        } else if (htmlTa) {
          promo.html_content = htmlTa.value;
        }
        if (bannerGroup) promo.banner_group = bannerGroup.value;
        if (bannerSel) promo.banner_id = parseInt(bannerSel.value) || 0;
      });
    }

    document.getElementById('btnSaveMobilePromos').addEventListener('click', function() {
      syncMobilePromosFromDOM();
      ajax('save_mobile_promos', { _body: { promos: currentMobilePromos } }, function(data) {
        showToast(data.success ? 'Mobile-Promos gespeichert!' : 'Fehler!', data.success ? 'success' : 'error');
      });
    });

    // Tab-Events
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function(tab) {
      tab.addEventListener('shown.bs.tab', function(e) {
        if (e.target.id === 'navlinks-tab') loadNavLinks();
        if (e.target.id === 'langeditor-tab') loadLangConstants('de');
        if (e.target.id === 'mobilemenu-tab') { loadMobileIcons(); loadMobilePromos(); }
      });
    });

  })();
  </script>
</body>
</html>
