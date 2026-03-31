<?php
/**
 * --------------------------------------------------------------
 * File: mrh_dashboard.php
 * Version: 1.0.0
 * Date: 2026-03-31
 *
 * Author: Mr. Hanf Development Team
 * Copyright: (c) 2026 Mr. Hanf
 * Web: https://mr-hanf.at
 * --------------------------------------------------------------
 * MRH Dashboard - Admin-Seite
 * Modulare Verwaltung für Template-Funktionen
 * --------------------------------------------------------------
 * Released under the GNU General Public License
 * --------------------------------------------------------------
 */

require('includes/application_top.php');

// Sicherheitscheck: Modul muss aktiv sein
if (!defined('MODULE_MRH_DASHBOARD_STATUS') || 'true' !== strtolower(MODULE_MRH_DASHBOARD_STATUS)) {
    xtc_redirect(xtc_href_link(FILENAME_DEFAULT));
}

// Externe Klassen laden
require_once(DIR_FS_CATALOG . 'includes/external/mrh_dashboard/MrhMegaMenuManager.php');

$megamenu = new MrhMegaMenuManager();

// ============================================================
// POST-Verarbeitung: Mega-Menü Konfiguration speichern
// ============================================================
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // CSRF-Token prüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Ungültiger Sicherheits-Token. Bitte versuchen Sie es erneut.';
        $message_type = 'error';
    } else {

        switch ($_POST['action']) {

            case 'save_megamenu':
                $parent_id = (int)$_POST['parent_category_id'];
                $columns   = $_POST['columns'] ?? [];

                $success = $megamenu->saveConfig($parent_id, $columns);

                if ($success) {
                    // Frontend-Cache neu generieren
                    $megamenu->regenerateCache();
                    $message = defined('MRH_MEGAMENU_SAVE_SUCCESS') ? MRH_MEGAMENU_SAVE_SUCCESS : 'Konfiguration gespeichert.';
                    $message_type = 'success';
                } else {
                    $message = defined('MRH_MEGAMENU_SAVE_ERROR') ? MRH_MEGAMENU_SAVE_ERROR : 'Fehler beim Speichern.';
                    $message_type = 'error';
                }
                break;

            case 'delete_megamenu':
                $parent_id = (int)$_POST['parent_category_id'];
                $megamenu->deleteConfig($parent_id);
                $megamenu->regenerateCache();
                $message = 'Konfiguration für diese Kategorie wurde gelöscht.';
                $message_type = 'success';
                break;
        }
    }
}

// CSRF-Token generieren
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================================
// Daten für die Anzeige laden
// ============================================================

// Hauptkategorien mit Mega-Dropdown (haben Unterkategorien)
$main_categories = $megamenu->getMainCategories();

// Aktive Kategorie (Tab)
$active_cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
if ($active_cat_id === 0 && !empty($main_categories)) {
    $active_cat_id = $main_categories[0]['categories_id'];
}

// Unterkategorien der aktiven Hauptkategorie (alle Level)
$subcategories = $megamenu->getAllSubcategories($active_cat_id);

// Gespeicherte Konfiguration für aktive Kategorie
$saved_config = $megamenu->getConfig($active_cat_id);

// ============================================================
// HTML-Ausgabe
// ============================================================
require(DIR_WS_INCLUDES . 'head.php');
?>
<style>
    /* MRH Dashboard Styles - Vanilla CSS, kein Framework-Overhead */
    .mrh-dashboard { max-width: 1400px; margin: 0 auto; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .mrh-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #e2e8f0; }
    .mrh-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
    .mrh-header .mrh-version { font-size: 12px; color: #94a3b8; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; }
    .mrh-header .mrh-subtitle { font-size: 14px; color: #64748b; margin-top: 4px; }

    /* Nachrichten */
    .mrh-message { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
    .mrh-message.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .mrh-message.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    /* Tabs für Hauptkategorien */
    .mrh-tabs { display: flex; gap: 4px; margin-bottom: 24px; flex-wrap: wrap; }
    .mrh-tab { padding: 10px 20px; background: #f1f5f9; border: 1px solid #e2e8f0; border-bottom: none; border-radius: 8px 8px 0 0; cursor: pointer; font-size: 14px; font-weight: 500; color: #475569; text-decoration: none; transition: all 0.2s; }
    .mrh-tab:hover { background: #e2e8f0; color: #1e293b; }
    .mrh-tab.active { background: #fff; color: #4a8c2a; border-color: #4a8c2a; border-bottom: 2px solid #fff; font-weight: 600; position: relative; top: 1px; }

    /* Spalten-Container */
    .mrh-columns-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .mrh-column { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; }
    .mrh-column-header { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
    .mrh-column-header .col-number { background: #4a8c2a; color: #fff; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
    .mrh-column-header input { flex: 1; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 14px; }
    .mrh-column-header .remove-col { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 18px; padding: 4px; }

    /* Icon-Feld */
    .mrh-icon-field { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
    .mrh-icon-field label { font-size: 12px; color: #64748b; white-space: nowrap; }
    .mrh-icon-field input { width: 60px; padding: 4px 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 14px; text-align: center; }

    /* Sortierbare Items */
    .mrh-items-list { min-height: 40px; }
    .mrh-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; margin-bottom: 4px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; cursor: grab; transition: all 0.15s; }
    .mrh-item:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .mrh-item.dragging { opacity: 0.5; background: #dbeafe; }
    .mrh-item .drag-handle { color: #94a3b8; cursor: grab; font-size: 16px; user-select: none; }
    .mrh-item .item-label { flex: 1; font-size: 13px; color: #334155; }
    .mrh-item .item-id { font-size: 11px; color: #94a3b8; }
    .mrh-item .remove-item { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 14px; padding: 2px; opacity: 0.6; }
    .mrh-item .remove-item:hover { opacity: 1; }

    /* Kategorie hinzufügen */
    .mrh-add-item { margin-top: 8px; }
    .mrh-add-item select { width: 100%; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 13px; background: #fff; }

    /* Buttons */
    .mrh-actions { display: flex; gap: 12px; align-items: center; padding-top: 16px; border-top: 1px solid #e2e8f0; }
    .mrh-btn { padding: 10px 24px; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; border: 1px solid transparent; transition: all 0.2s; }
    .mrh-btn-primary { background: #4a8c2a; color: #fff; }
    .mrh-btn-primary:hover { background: #3a7020; }
    .mrh-btn-secondary { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
    .mrh-btn-secondary:hover { background: #e2e8f0; }
    .mrh-btn-danger { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    .mrh-btn-danger:hover { background: #fee2e2; }
    .mrh-btn-sm { padding: 6px 12px; font-size: 12px; }

    /* Info-Box */
    .mrh-info { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 16px; font-size: 13px; color: #1e40af; margin-bottom: 16px; }

    /* Verfügbare Kategorien Sidebar */
    .mrh-layout { display: grid; grid-template-columns: 1fr 280px; gap: 24px; }
    .mrh-sidebar { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; height: fit-content; position: sticky; top: 20px; }
    .mrh-sidebar h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0 0 12px; }
    .mrh-sidebar .cat-item { padding: 6px 10px; font-size: 13px; color: #475569; border-radius: 4px; cursor: pointer; margin-bottom: 2px; transition: background 0.15s; display: flex; justify-content: space-between; align-items: center; }
    .mrh-sidebar .cat-item:hover { background: #e2e8f0; }
    .mrh-sidebar .cat-item.used { opacity: 0.4; text-decoration: line-through; cursor: not-allowed; }
    .mrh-sidebar .cat-item .cat-id { font-size: 11px; color: #94a3b8; }
    .mrh-sidebar .search-box { width: 100%; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 13px; margin-bottom: 12px; }
    .mrh-sidebar .cat-list { max-height: 400px; overflow-y: auto; }

    /* Responsive */
    @media (max-width: 1024px) {
        .mrh-layout { grid-template-columns: 1fr; }
        .mrh-sidebar { position: static; }
    }
</style>
</head>
<body>
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<div class="mrh-dashboard">

    <!-- Header -->
    <div class="mrh-header">
        <div>
            <h1><?php echo defined('MRH_HEADING_TITLE') ? MRH_HEADING_TITLE : 'MRH Dashboard'; ?>
                <span class="mrh-version">v<?php echo mrh_dashboard::VERSION; ?></span>
            </h1>
            <div class="mrh-subtitle"><?php echo defined('MRH_HEADING_SUBTITLE') ? MRH_HEADING_SUBTITLE : 'Modulare Verwaltung'; ?></div>
        </div>
    </div>

    <!-- Nachrichten -->
    <?php if ($message): ?>
        <div class="mrh-message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Mega-Menü Manager -->
    <h2 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 16px;">
        <?php echo defined('MRH_MEGAMENU_TITLE') ? MRH_MEGAMENU_TITLE : 'Mega-Menü Manager'; ?>
    </h2>

    <div class="mrh-info">
        <?php echo defined('MRH_MEGAMENU_DESC') ? MRH_MEGAMENU_DESC : 'Konfigurieren Sie die Mega-Dropdown-Menüs.'; ?>
        <br><strong><?php echo defined('MRH_MEGAMENU_MAX_COLUMNS') ? MRH_MEGAMENU_MAX_COLUMNS : 'Max. 3 Spalten'; ?></strong>
        &middot; <strong><?php echo defined('MRH_MEGAMENU_MAX_ITEMS') ? MRH_MEGAMENU_MAX_ITEMS : 'Max. 5 Einträge pro Spalte'; ?></strong>
    </div>

    <!-- Tabs: Hauptkategorien -->
    <div class="mrh-tabs">
        <?php foreach ($main_categories as $cat): ?>
            <a href="<?php echo xtc_href_link('mrh_dashboard.php', 'cat_id=' . $cat['categories_id']); ?>"
               class="mrh-tab <?php echo ($cat['categories_id'] == $active_cat_id) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat['categories_name']); ?>
                <?php if ($megamenu->hasConfig($cat['categories_id'])): ?>
                    <span style="color: #4a8c2a; margin-left: 4px;">&#10003;</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($active_cat_id > 0): ?>

    <form method="post" action="<?php echo xtc_href_link('mrh_dashboard.php', 'cat_id=' . $active_cat_id); ?>" id="megamenu-form">
        <input type="hidden" name="action" value="save_megamenu">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="parent_category_id" value="<?php echo $active_cat_id; ?>">

        <div class="mrh-layout">
            <!-- Hauptbereich: Spalten -->
            <div class="mrh-main">
                <div class="mrh-columns-container" id="columns-container">
                    <?php
                    // Gespeicherte Spalten anzeigen oder leere Vorlage
                    $columns = $saved_config ?: [
                        ['title' => '', 'icon' => '', 'items' => []],
                    ];
                    foreach ($columns as $col_idx => $column):
                    ?>
                    <div class="mrh-column" data-col-index="<?php echo $col_idx; ?>">
                        <div class="mrh-column-header">
                            <span class="col-number"><?php echo $col_idx + 1; ?></span>
                            <input type="text"
                                   name="columns[<?php echo $col_idx; ?>][title]"
                                   value="<?php echo htmlspecialchars($column['title']); ?>"
                                   placeholder="<?php echo defined('MRH_MEGAMENU_COLUMN_TITLE') ? MRH_MEGAMENU_COLUMN_TITLE : 'Spaltenüberschrift'; ?>">
                            <button type="button" class="remove-col" onclick="removeColumn(this)" title="<?php echo defined('MRH_BUTTON_REMOVE_COLUMN') ? MRH_BUTTON_REMOVE_COLUMN : 'Entfernen'; ?>">&times;</button>
                        </div>
                        <div class="mrh-icon-field">
                            <label><?php echo defined('MRH_MEGAMENU_COLUMN_ICON') ? MRH_MEGAMENU_COLUMN_ICON : 'Icon'; ?>:</label>
                            <input type="text"
                                   name="columns[<?php echo $col_idx; ?>][icon]"
                                   value="<?php echo htmlspecialchars($column['icon']); ?>"
                                   placeholder="&#x1F331;">
                        </div>
                        <div class="mrh-items-list" id="items-list-<?php echo $col_idx; ?>" data-col="<?php echo $col_idx; ?>">
                            <?php foreach ($column['items'] as $item_idx => $item): ?>
                            <div class="mrh-item" draggable="true" data-cat-id="<?php echo $item['category_id']; ?>">
                                <span class="drag-handle">&#x2630;</span>
                                <span class="item-label"><?php echo htmlspecialchars($item['label']); ?></span>
                                <span class="item-id">ID: <?php echo $item['category_id']; ?></span>
                                <input type="hidden" name="columns[<?php echo $col_idx; ?>][items][<?php echo $item_idx; ?>][category_id]" value="<?php echo $item['category_id']; ?>">
                                <input type="hidden" name="columns[<?php echo $col_idx; ?>][items][<?php echo $item_idx; ?>][label]" value="<?php echo htmlspecialchars($item['label']); ?>">
                                <button type="button" class="remove-item" onclick="removeItem(this)">&times;</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mrh-add-item">
                            <select onchange="addItemFromSelect(this, <?php echo $col_idx; ?>)">
                                <option value=""><?php echo defined('MRH_MEGAMENU_SELECT_CATEGORY') ? MRH_MEGAMENU_SELECT_CATEGORY : '-- Kategorie wählen --'; ?></option>
                                <?php foreach ($subcategories as $sub): ?>
                                <option value="<?php echo $sub['categories_id']; ?>" data-label="<?php echo htmlspecialchars($sub['categories_name']); ?>">
                                    <?php echo str_repeat('&nbsp;&nbsp;', $sub['level']); ?><?php echo htmlspecialchars($sub['categories_name']); ?> (ID: <?php echo $sub['categories_id']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Spalte hinzufügen -->
                <div style="margin-bottom: 16px;">
                    <button type="button" class="mrh-btn mrh-btn-secondary mrh-btn-sm" onclick="addColumn()" id="add-column-btn">
                        + <?php echo defined('MRH_BUTTON_ADD_COLUMN') ? MRH_BUTTON_ADD_COLUMN : 'Spalte hinzufügen'; ?>
                    </button>
                </div>

                <!-- Actions -->
                <div class="mrh-actions">
                    <button type="submit" class="mrh-btn mrh-btn-primary">
                        <?php echo defined('MRH_BUTTON_SAVE') ? MRH_BUTTON_SAVE : 'Speichern'; ?>
                    </button>
                    <button type="button" class="mrh-btn mrh-btn-danger" onclick="deleteMegamenuConfig()">
                        <?php echo defined('MRH_BUTTON_RESET') ? MRH_BUTTON_RESET : 'Zurücksetzen'; ?>
                    </button>
                </div>
            </div>

            <!-- Sidebar: Verfügbare Kategorien -->
            <div class="mrh-sidebar">
                <h3>Verfügbare Kategorien</h3>
                <input type="text" class="search-box" placeholder="Suchen..." oninput="filterCategories(this.value)">
                <div class="cat-list" id="available-categories">
                    <?php foreach ($subcategories as $sub): ?>
                    <div class="cat-item" data-cat-id="<?php echo $sub['categories_id']; ?>" data-label="<?php echo htmlspecialchars($sub['categories_name']); ?>" onclick="addItemFromSidebar(this)">
                        <span><?php echo str_repeat('&middot; ', $sub['level']); ?><?php echo htmlspecialchars($sub['categories_name']); ?></span>
                        <span class="cat-id"><?php echo $sub['categories_id']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </form>

    <!-- Lösch-Formular (separates Form) -->
    <form method="post" action="<?php echo xtc_href_link('mrh_dashboard.php', 'cat_id=' . $active_cat_id); ?>" id="delete-form" style="display:none;">
        <input type="hidden" name="action" value="delete_megamenu">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="parent_category_id" value="<?php echo $active_cat_id; ?>">
    </form>

    <?php endif; ?>

</div>

<!-- Vanilla JS: Drag & Drop, Spalten-Verwaltung -->
<script>
(function() {
    'use strict';

    const MAX_COLUMNS = 3;
    const MAX_ITEMS_PER_COLUMN = 5;
    let colCount = document.querySelectorAll('.mrh-column').length;

    // Subcategories Daten für JS
    const subcategories = <?php echo json_encode(array_map(function($s) {
        return ['id' => $s['categories_id'], 'name' => $s['categories_name'], 'level' => $s['level']];
    }, $subcategories)); ?>;

    // ============================================================
    // Spalten-Verwaltung
    // ============================================================
    window.addColumn = function() {
        if (colCount >= MAX_COLUMNS) {
            alert('Maximal ' + MAX_COLUMNS + ' Spalten erlaubt.');
            return;
        }

        const container = document.getElementById('columns-container');
        const colIdx = colCount;

        const colHtml = `
            <div class="mrh-column" data-col-index="${colIdx}">
                <div class="mrh-column-header">
                    <span class="col-number">${colIdx + 1}</span>
                    <input type="text" name="columns[${colIdx}][title]" value="" placeholder="Spaltenüberschrift">
                    <button type="button" class="remove-col" onclick="removeColumn(this)" title="Entfernen">&times;</button>
                </div>
                <div class="mrh-icon-field">
                    <label>Icon:</label>
                    <input type="text" name="columns[${colIdx}][icon]" value="" placeholder="&#x1F331;">
                </div>
                <div class="mrh-items-list" id="items-list-${colIdx}" data-col="${colIdx}"></div>
                <div class="mrh-add-item">
                    <select onchange="addItemFromSelect(this, ${colIdx})">
                        <option value="">-- Kategorie wählen --</option>
                        ${subcategories.map(s =>
                            `<option value="${s.id}" data-label="${s.name}">${'&nbsp;&nbsp;'.repeat(s.level)}${s.name} (ID: ${s.id})</option>`
                        ).join('')}
                    </select>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', colHtml);
        colCount++;
        updateColumnNumbers();
        initDragDrop();
        toggleAddColumnBtn();
    };

    window.removeColumn = function(btn) {
        if (!confirm('Spalte wirklich entfernen?')) return;
        btn.closest('.mrh-column').remove();
        colCount--;
        updateColumnNumbers();
        updateUsedCategories();
        toggleAddColumnBtn();
    };

    function updateColumnNumbers() {
        document.querySelectorAll('.mrh-column').forEach((col, idx) => {
            col.dataset.colIndex = idx;
            col.querySelector('.col-number').textContent = idx + 1;

            // Input-Names aktualisieren
            col.querySelectorAll('[name]').forEach(input => {
                input.name = input.name.replace(/columns\[\d+\]/, `columns[${idx}]`);
            });

            const itemsList = col.querySelector('.mrh-items-list');
            if (itemsList) {
                itemsList.id = `items-list-${idx}`;
                itemsList.dataset.col = idx;
            }

            const select = col.querySelector('.mrh-add-item select');
            if (select) {
                select.setAttribute('onchange', `addItemFromSelect(this, ${idx})`);
            }
        });
    }

    function toggleAddColumnBtn() {
        const btn = document.getElementById('add-column-btn');
        if (btn) {
            btn.disabled = colCount >= MAX_COLUMNS;
            btn.style.opacity = colCount >= MAX_COLUMNS ? '0.4' : '1';
        }
    }

    // ============================================================
    // Items hinzufügen/entfernen
    // ============================================================
    window.addItemFromSelect = function(select, colIdx) {
        const catId = select.value;
        if (!catId) return;

        const label = select.options[select.selectedIndex].dataset.label;
        addItem(colIdx, catId, label);
        select.value = '';
    };

    window.addItemFromSidebar = function(el) {
        if (el.classList.contains('used')) return;

        const catId = el.dataset.catId;
        const label = el.dataset.label;

        // In die erste Spalte mit Platz einfügen
        const columns = document.querySelectorAll('.mrh-items-list');
        for (const list of columns) {
            if (list.children.length < MAX_ITEMS_PER_COLUMN) {
                const colIdx = parseInt(list.dataset.col);
                addItem(colIdx, catId, label);
                return;
            }
        }
        alert('Alle Spalten sind voll (max. ' + MAX_ITEMS_PER_COLUMN + ' Einträge).');
    };

    function addItem(colIdx, catId, label) {
        const list = document.getElementById(`items-list-${colIdx}`);
        if (!list) return;

        if (list.children.length >= MAX_ITEMS_PER_COLUMN) {
            alert('Maximal ' + MAX_ITEMS_PER_COLUMN + ' Einträge pro Spalte.');
            return;
        }

        // Prüfen ob schon in einer Spalte vorhanden
        if (document.querySelector(`.mrh-item[data-cat-id="${catId}"]`)) {
            alert('Diese Kategorie ist bereits zugeordnet.');
            return;
        }

        const itemIdx = list.children.length;
        const itemHtml = `
            <div class="mrh-item" draggable="true" data-cat-id="${catId}">
                <span class="drag-handle">&#x2630;</span>
                <span class="item-label">${label}</span>
                <span class="item-id">ID: ${catId}</span>
                <input type="hidden" name="columns[${colIdx}][items][${itemIdx}][category_id]" value="${catId}">
                <input type="hidden" name="columns[${colIdx}][items][${itemIdx}][label]" value="${label}">
                <button type="button" class="remove-item" onclick="removeItem(this)">&times;</button>
            </div>
        `;

        list.insertAdjacentHTML('beforeend', itemHtml);
        updateUsedCategories();
        initDragDrop();
        renumberItems(list);
    }

    window.removeItem = function(btn) {
        const list = btn.closest('.mrh-items-list');
        btn.closest('.mrh-item').remove();
        updateUsedCategories();
        renumberItems(list);
    };

    function renumberItems(list) {
        const colIdx = list.dataset.col;
        list.querySelectorAll('.mrh-item').forEach((item, idx) => {
            item.querySelectorAll('input[type="hidden"]').forEach(input => {
                input.name = input.name.replace(
                    /columns\[\d+\]\[items\]\[\d+\]/,
                    `columns[${colIdx}][items][${idx}]`
                );
            });
        });
    }

    // ============================================================
    // Verwendete Kategorien markieren
    // ============================================================
    function updateUsedCategories() {
        const usedIds = new Set();
        document.querySelectorAll('.mrh-item[data-cat-id]').forEach(item => {
            usedIds.add(item.dataset.catId);
        });

        document.querySelectorAll('#available-categories .cat-item').forEach(el => {
            el.classList.toggle('used', usedIds.has(el.dataset.catId));
        });
    }

    // ============================================================
    // Sidebar-Suche
    // ============================================================
    window.filterCategories = function(query) {
        const lower = query.toLowerCase();
        document.querySelectorAll('#available-categories .cat-item').forEach(el => {
            const text = el.textContent.toLowerCase();
            el.style.display = text.includes(lower) ? '' : 'none';
        });
    };

    // ============================================================
    // Drag & Drop (Vanilla JS)
    // ============================================================
    let draggedItem = null;

    function initDragDrop() {
        document.querySelectorAll('.mrh-item[draggable="true"]').forEach(item => {
            item.removeEventListener('dragstart', onDragStart);
            item.removeEventListener('dragend', onDragEnd);
            item.addEventListener('dragstart', onDragStart);
            item.addEventListener('dragend', onDragEnd);
        });

        document.querySelectorAll('.mrh-items-list').forEach(list => {
            list.removeEventListener('dragover', onDragOver);
            list.removeEventListener('drop', onDrop);
            list.addEventListener('dragover', onDragOver);
            list.addEventListener('drop', onDrop);
        });
    }

    function onDragStart(e) {
        draggedItem = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    function onDragEnd() {
        this.classList.remove('dragging');
        draggedItem = null;
        // Alle Listen neu nummerieren
        document.querySelectorAll('.mrh-items-list').forEach(list => renumberItems(list));
    }

    function onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const list = this;
        if (list.children.length >= MAX_ITEMS_PER_COLUMN && !list.contains(draggedItem)) {
            return; // Spalte ist voll
        }

        const afterElement = getDragAfterElement(list, e.clientY);
        if (afterElement) {
            list.insertBefore(draggedItem, afterElement);
        } else {
            list.appendChild(draggedItem);
        }
    }

    function onDrop(e) {
        e.preventDefault();
        updateUsedCategories();
    }

    function getDragAfterElement(container, y) {
        const elements = [...container.querySelectorAll('.mrh-item:not(.dragging)')];
        return elements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset, element: child };
            }
            return closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    // ============================================================
    // Löschen
    // ============================================================
    window.deleteMegamenuConfig = function() {
        if (confirm('Konfiguration für diese Kategorie wirklich löschen?')) {
            document.getElementById('delete-form').submit();
        }
    };

    // ============================================================
    // Init
    // ============================================================
    updateUsedCategories();
    initDragDrop();
    toggleAddColumnBtn();

})();
</script>

<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
