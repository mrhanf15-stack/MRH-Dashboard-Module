<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v2.0.0-mrh2026 - JavaScript (als .js.php für Smarty-Variablen)

   Hookpoint: templates/tpl_mrh_2026/javascript/extra/
   Wird automatisch auf jeder Seite geladen.

   Basiert auf v1.9.4 (bootstrap4) mit folgenden Anpassungen fuer tpl_mrh_2026:
   - Bootstrap 5.3.0: mr-* → me-*, ml-* → ms-*, text-right → text-end
   - Font Awesome 7 Pro: fa fa-* → fa-solid fa-*
   - 100% Vanilla JS (kein jQuery) - war bereits so
   - DOM-Selektoren fuer tpl_mrh_2026 Layout angepasst

   v2.0.0: Initiale tpl_mrh_2026 Version (BS5.3 + FA7 Pro)
   
   Changelog (aus bootstrap4 Version):
   v1.9.4: BUGFIX - Doppelter Confirm-Dialog behoben
   v1.9.3: BUGFIX - Clear-Button Selector fix + clearAll() global
   v1.9.2: BUGFIX - FPC-sichere Initialisierung
   v1.9.1: BUGFIX - "Liste leeren" per AJAX statt Page-Link
   v1.9.0: Cookie-basierte Persistenz
   v1.2.0: Neuer Ansatz - Buttons direkt in Smarty-Templates

   @author    Mr. Hanf / Manus AI
   @version   2.0.0-mrh2026
   @date      2026-04-02
   -----------------------------------------------------------------------------------------*/

if (defined('MODULE_PRODUCT_COMPARE_STATUS') && MODULE_PRODUCT_COMPARE_STATUS == 'true'):
?>
<link rel="stylesheet" href="<?php echo (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/'); ?>templates/<?php echo CURRENT_TEMPLATE; ?>/css/product_compare.css">
<script>
(function() {
    'use strict';

    // === Cookie-Helper Funktionen ===
    function pcSaveCookie(ids) {
        var value = (ids || []).map(Number).filter(function(id){ return id > 0; }).join(',');
        var expires = new Date();
        expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000)); // 30 Tage
        document.cookie = 'pc_compare_ids=' + value + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
    }

    function pcLoadCookie() {
        var match = document.cookie.match(/(?:^|;\s*)pc_compare_ids=([^;]*)/);
        if (match && match[1] && match[1] !== '') {
            return match[1].split(',').map(Number).filter(function(id){ return id > 0; });
        }
        return [];
    }

    function pcClearCookie() {
        document.cookie = 'pc_compare_ids=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;SameSite=Lax';
    }

    // === Konfiguration ===
    var PC = {
        ajaxUrl: '<?php echo (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/'); ?>ajax.php?ext=product_compare',
        compareUrl: '<?php echo xtc_href_link("product_compare.php"); ?>',
        maxProducts: <?php echo (defined('MODULE_PRODUCT_COMPARE_MAX_PRODUCTS') ? (int)MODULE_PRODUCT_COMPARE_MAX_PRODUCTS : 6); ?>,
        currentProducts: [], // v1.9.2: IMMER leer initialisieren (FPC-sicher)

        // Texte
        text: {
            add: '<?php echo addslashes(defined("PC_BUTTON_ADD") ? PC_BUTTON_ADD : "Vergleichen"); ?>',
            added: '<?php echo addslashes(defined("PC_BUTTON_ADDED") ? PC_BUTTON_ADDED : "Im Vergleich"); ?>',
            compareNow: '<?php echo addslashes(defined("PC_BUTTON_COMPARE_NOW") ? PC_BUTTON_COMPARE_NOW : "Jetzt vergleichen"); ?>',
            msgAdded: '<?php echo addslashes(defined("PC_MSG_ADDED") ? PC_MSG_ADDED : "Produkt zum Vergleich hinzugef\u00fcgt"); ?>',
            msgRemoved: '<?php echo addslashes(defined("PC_MSG_REMOVED") ? PC_MSG_REMOVED : "Produkt aus dem Vergleich entfernt"); ?>',
            msgAlready: '<?php echo addslashes(defined("PC_MSG_ALREADY") ? PC_MSG_ALREADY : "Produkt ist bereits im Vergleich"); ?>',
            msgMaxReached: '<?php echo addslashes(defined("PC_MSG_MAX_REACHED") ? str_replace("%s", (defined("MODULE_PRODUCT_COMPARE_MAX_PRODUCTS") ? MODULE_PRODUCT_COMPARE_MAX_PRODUCTS : "6"), PC_MSG_MAX_REACHED) : "Maximale Anzahl erreicht"); ?>',
            msgCleared: '<?php echo addslashes(defined("PC_MSG_CLEARED") ? PC_MSG_CLEARED : "Vergleichsliste geleert"); ?>'
        },

        // Cache: SKU → products_id Mapping
        skuMap: {}
    };

    // === v1.9.2: FPC-sichere Initialisierung per AJAX ===
    var initDone = false;
    function initFromServer() {
        ajaxCompare('list', null, function(data) {
            if (data.success) {
                var serverProducts = (data.products || []).map(Number);
                var serverCount = data.count || 0;

                if (serverCount > 0) {
                    PC.currentProducts = serverProducts;
                    pcSaveCookie(PC.currentProducts);
                    updateBadge(serverCount);
                    updateAllButtons();
                    initDone = true;
                } else {
                    var cookieIds = pcLoadCookie();
                    if (cookieIds.length > 0) {
                        var restoreCount = 0;
                        var totalToRestore = cookieIds.length;

                        PC.currentProducts = cookieIds;
                        updateBadge(cookieIds.length);
                        updateAllButtons();

                        cookieIds.forEach(function(pid) {
                            var url = PC.ajaxUrl + '&sub_action=add&products_id=' + pid;
                            var xhr = new XMLHttpRequest();
                            xhr.open('GET', url, true);
                            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                            xhr.onreadystatechange = function() {
                                if (xhr.readyState === 4) {
                                    restoreCount++;
                                    if (restoreCount >= totalToRestore) {
                                        ajaxCompare('list', null, function(data2) {
                                            if (data2.success) {
                                                PC.currentProducts = (data2.products || []).map(Number);
                                                pcSaveCookie(PC.currentProducts);
                                                updateBadge(PC.currentProducts.length);
                                                updateAllButtons();
                                            }
                                        });
                                    }
                                }
                            };
                            xhr.send();
                        });
                    } else {
                        PC.currentProducts = [];
                        updateBadge(0);
                        updateAllButtons();
                    }
                    initDone = true;
                }
            }
        });
    }

    // === Toast-Benachrichtigung ===
    var toastEl = null;
    var toastTimeout = null;

    function createToast() {
        if (toastEl) return;
        toastEl = document.createElement('div');
        toastEl.className = 'compare-toast';
        document.body.appendChild(toastEl);
    }

    function showToast(message, type) {
        createToast();
        toastEl.textContent = message;
        toastEl.className = 'compare-toast ' + (type || 'info');
        void toastEl.offsetWidth;
        toastEl.classList.add('show');

        if (toastTimeout) clearTimeout(toastTimeout);
        toastTimeout = setTimeout(function() {
            toastEl.classList.remove('show');
        }, 3000);
    }

    // === Badge aktualisieren ===
    function updateBadge(count) {
        var badge = document.getElementById('product-compare-badge');
        if (!badge) return;

        var countEl = badge.querySelector('.compare-count');
        if (countEl) countEl.textContent = count;

        if (count > 0) {
            badge.classList.add('active');
        } else {
            badge.classList.remove('active');
        }
    }

    // === AJAX-Aufruf ===
    function ajaxCompare(subAction, productId, callback) {
        var url = PC.ajaxUrl + '&sub_action=' + subAction;
        if (productId) url += '&products_id=' + productId;

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (callback) callback(data);
                } catch(e) {
                    console.error('ProductCompare: JSON parse error', e);
                }
            }
        };
        xhr.send();
    }

    // === SKU → products_id per AJAX auflösen ===
    function resolveProductId(sku, callback) {
        if (PC.skuMap[sku]) {
            callback(PC.skuMap[sku]);
            return;
        }

        var url = PC.ajaxUrl + '&sub_action=resolve_sku&sku=' + encodeURIComponent(sku);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success && data.products_id) {
                        PC.skuMap[sku] = data.products_id;
                        callback(data.products_id);
                    }
                } catch(e) {
                    console.error('ProductCompare: SKU resolve error', e);
                }
            }
        };
        xhr.send();
    }

    // === Produkt hinzufügen/entfernen (Toggle) ===
    function toggleCompare(productId, button) {
        if (isNaN(productId)) {
            resolveProductId(productId, function(resolvedId) {
                doToggle(resolvedId, button);
            });
        } else {
            doToggle(productId, button);
        }
    }

    function doToggle(productId, button) {
        productId = parseInt(productId);
        var isInList = PC.currentProducts.indexOf(productId) !== -1;

        if (isInList) {
            ajaxCompare('remove', productId, function(data) {
                if (data.success) {
                    PC.currentProducts = data.products.map(Number);
                    pcSaveCookie(PC.currentProducts);
                    updateBadge(data.count);
                    updateAllButtons();
                    showToast(PC.text.msgRemoved, 'info');
                }
            });
        } else {
            if (PC.currentProducts.length >= PC.maxProducts) {
                showToast(PC.text.msgMaxReached, 'error');
                return;
            }

            ajaxCompare('add', productId, function(data) {
                if (data.success) {
                    PC.currentProducts = data.products.map(Number);
                    pcSaveCookie(PC.currentProducts);
                    updateBadge(data.count);
                    updateAllButtons();
                    showToast(PC.text.msgAdded, 'success');
                } else if (data.message === 'already_in_list') {
                    showToast(PC.text.msgAlready, 'info');
                } else if (data.message === 'max_reached') {
                    showToast(PC.text.msgMaxReached, 'error');
                }
            });
        }
    }

    // === v1.9.1: Liste leeren per AJAX ===
    function clearCompare(reloadPage) {
        pcClearCookie();
        PC.currentProducts = [];
        updateBadge(0);
        updateAllButtons();

        ajaxCompare('clear', null, function(data) {
            if (data.success) {
                showToast(PC.text.msgCleared, 'info');
                if (reloadPage) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                }
            }
        });
    }

    // === Alle Buttons aktualisieren ===
    // BS5.3: me-1 statt mr-1, FA7 Pro: fa-solid fa-* statt fa fa-*
    function updateAllButtons() {
        var buttons = document.querySelectorAll('.btn-compare[data-product-id]');
        buttons.forEach(function(btn) {
            var pid = parseInt(btn.getAttribute('data-product-id'));
            var isInList = PC.currentProducts.indexOf(pid) !== -1;

            if (isInList) {
                btn.classList.add('active');
                btn.innerHTML = '<span class="fa-solid fa-check me-1"></span>' + PC.text.added;
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '<span class="fa-solid fa-scale-balanced me-1"></span>' + PC.text.add;
            }
        });
    }

    // === Seedfinder-Karten: SKU-basierte Buttons initialisieren ===
    function initSeedfinderButtons() {
        var skuButtons = document.querySelectorAll('.btn-compare[data-sku]:not([data-product-id])');

        skuButtons.forEach(function(btn) {
            var sku = btn.getAttribute('data-sku');
            if (!sku) return;

            resolveProductId(sku, function(productId) {
                btn.setAttribute('data-product-id', productId);

                var isInList = PC.currentProducts.indexOf(parseInt(productId)) !== -1;
                if (isInList) {
                    btn.classList.add('active');
                    btn.innerHTML = '<span class="fa-solid fa-check me-1"></span>' + PC.text.added;
                }
            });
        });
    }

    // === Button-Click-Handler (Event Delegation) ===
    function handleCompareClick(e) {
        var btn = e.target.closest('.btn-compare');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        var productId = btn.getAttribute('data-product-id');
        var sku = btn.getAttribute('data-sku');

        if (productId) {
            toggleCompare(productId, btn);
        } else if (sku) {
            toggleCompare(sku, btn);
        }
    }

    // === v1.9.4: Clear-Button Click-Handler (Event Delegation) ===
    function handleClearClick(e) {
        var link = e.target.closest('a[href*="action=clear"]');
        if (!link) link = e.target.closest('a.btn-outline-danger[href*="product_compare"]');
        if (!link) link = e.target.closest('a.btn-outline-danger[href*="vergleich"]');
        if (!link) return;

        if (link.hasAttribute('onclick')) return;

        e.preventDefault();
        e.stopPropagation();

        if (confirm('Vergleichsliste wirklich leeren?')) {
            clearCompare(true);
        }
    }

    // === Initialisierung ===
    function init() {
        updateBadge(0);
        initFromServer();
        initSeedfinderButtons();

        document.addEventListener('click', handleCompareClick);
        document.addEventListener('click', handleClearClick);

        // MutationObserver fuer dynamisch geladene Seedfinder-Karten
        var observer = new MutationObserver(function(mutations) {
            var shouldUpdate = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && (
                            (node.querySelector && node.querySelector('.btn-compare'))
                        )) {
                            shouldUpdate = true;
                        }
                    });
                }
            });
            if (shouldUpdate) {
                setTimeout(function() {
                    initSeedfinderButtons();
                    updateAllButtons();
                }, 200);
            }
        });

        // tpl_mrh_2026: Content-Container Selektoren
        var mainContent = document.getElementById('products-container') ||
                          document.querySelector('#content') ||
                          document.querySelector('main#main-content') ||
                          document.body;

        observer.observe(mainContent, {
            childList: true,
            subtree: true
        });
    }

    // DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Globale Funktion fuer externe Aufrufe
    window.ProductCompare = {
        toggle: toggleCompare,
        update: updateAllButtons,
        clear: clearCompare,
        clearAll: function() { clearCompare(true); },
        getProducts: function() { return PC.currentProducts; },
        getCount: function() { return PC.currentProducts.length; },
        saveCookie: function() { pcSaveCookie(PC.currentProducts); },
        clearCookie: pcClearCookie
    };

})();
</script>
<?php endif; ?>
