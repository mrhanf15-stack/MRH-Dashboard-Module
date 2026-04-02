<?php
/* -----------------------------------------------------------------------------------------
   FreeShippingBar v1.4.0-mrh2026 - JavaScript fuer tpl_mrh_2026 Template
   
   Anpassungen gegenueber bootstrap4-Version:
   - Mini-Warenkorb: BS5 Offcanvas (#offcanvasCart .offcanvas-body) statt BS4 Dropdown
   - Versandland: #countries select[name="country"] (wie reinsalz)
   - Warenkorb-Seite: main#main-content als Fallback-Container
   - z-index: 1050 (BS5 Offcanvas = 1045)
   - Bottom-Bar Offset: #mrhBottomBar Erkennung
   - Header-Bar: Uebernimmt bestehenden #mrh-shipping-bar Container
   - 100% Vanilla JS (kein jQuery)
   
   v1.4.0: Header-Bar Integration (#mrh-shipping-bar), Offcanvas-Warenkorb
   v1.3.2: Polling von 5s auf 30s, Tab-Visibility-Check
   
   Copyright (c) 2026 FreeShippingBar
   -----------------------------------------------------------------------------------------
   Released under the GNU General Public License
   ---------------------------------------------------------------------------------------*/

if (defined('MODULE_FREE_SHIPPING_BAR_STATUS') && MODULE_FREE_SHIPPING_BAR_STATUS == 'true'
    && defined('MODULE_SHIPPING_FREEAMOUNT_STATUS') && MODULE_SHIPPING_FREEAMOUNT_STATUS == 'True') {

    $fsb_position = (defined('MODULE_FREE_SHIPPING_BAR_POSITION')) ? MODULE_FREE_SHIPPING_BAR_POSITION : 'bottom';
    $fsb_color = (defined('MODULE_FREE_SHIPPING_BAR_COLOR')) ? MODULE_FREE_SHIPPING_BAR_COLOR : '#28a745';
    $fsb_bg_color = (defined('MODULE_FREE_SHIPPING_BAR_BG_COLOR')) ? MODULE_FREE_SHIPPING_BAR_BG_COLOR : '#f8f9fa';
    $fsb_success_color = (defined('MODULE_FREE_SHIPPING_BAR_SUCCESS_COLOR')) ? MODULE_FREE_SHIPPING_BAR_SUCCESS_COLOR : '#28a745';
    $fsb_hide_empty = (defined('MODULE_FREE_SHIPPING_BAR_HIDE_EMPTY_CART') && MODULE_FREE_SHIPPING_BAR_HIDE_EMPTY_CART == 'true');
    $fsb_show_on_all = (defined('MODULE_FREE_SHIPPING_BAR_SHOW_ON_ALL') && MODULE_FREE_SHIPPING_BAR_SHOW_ON_ALL == 'true');
    $fsb_show_on_cart = (defined('MODULE_FREE_SHIPPING_BAR_SHOW_ON_CART') && MODULE_FREE_SHIPPING_BAR_SHOW_ON_CART == 'true');
    
    $fsb_current_page = basename($PHP_SELF);
    $fsb_is_cart = ($fsb_current_page == FILENAME_SHOPPING_CART);
    $fsb_is_checkout = in_array($fsb_current_page, array(
        FILENAME_CHECKOUT_SHIPPING, FILENAME_CHECKOUT_PAYMENT, 
        FILENAME_CHECKOUT_CONFIRMATION, FILENAME_CHECKOUT_SUCCESS
    ));
    
    $fsb_hide_checkout_js = (defined('MODULE_FREE_SHIPPING_BAR_HIDE_CHECKOUT') && MODULE_FREE_SHIPPING_BAR_HIDE_CHECKOUT == 'true');
    
    $fsb_show_fixed = false;
    if ($fsb_is_checkout && $fsb_hide_checkout_js) {
        $fsb_show_fixed = false;
    } elseif ($fsb_is_cart && $fsb_show_on_cart) {
        $fsb_show_fixed = true;
    } elseif ($fsb_show_on_all && !$fsb_is_checkout) {
        $fsb_show_fixed = true;
    }
    
    $fsb_show_minicart = !($fsb_is_checkout && $fsb_hide_checkout_js);
    
    // Aktive Sprache fuer initiale Anzeige
    $fsb_lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'german';
?>
<style>
/* ===== FreeShippingBar v1.4.0-mrh2026 ===== */

/* ===== Fixierter Balken (unten/oben) ===== */
#fsb-container {
  position: fixed;
  <?php echo ($fsb_position == 'top') ? 'top: 0;' : 'bottom: 0;'; ?>
  left: 0;
  right: 0;
  z-index: 1050; /* BS5 Offcanvas = 1045 */
  transition: transform 0.4s ease, opacity 0.4s ease;
  pointer-events: none;
}
#fsb-container.fsb-hidden {
  <?php echo ($fsb_position == 'top') ? 'transform: translateY(-100%);' : 'transform: translateY(100%);'; ?>
  opacity: 0;
}
#fsb-container.fsb-visible {
  transform: translateY(0);
  opacity: 1;
}
#fsb-bar {
  background: <?php echo $fsb_bg_color; ?>;
  border-<?php echo ($fsb_position == 'top') ? 'bottom' : 'top'; ?>: 1px solid rgba(0,0,0,0.1);
  padding: 8px 15px;
  pointer-events: auto;
  box-shadow: 0 <?php echo ($fsb_position == 'top') ? '2px' : '-2px'; ?> 8px rgba(0,0,0,0.1);
}
#fsb-inner {
  max-width: 600px;
  margin: 0 auto;
  text-align: center;
}
.fsb-text {
  font-size: 13px;
  color: #555;
  margin-bottom: 4px;
  line-height: 1.3;
}
.fsb-text .fsb-amount {
  font-weight: 700;
  color: <?php echo $fsb_color; ?>;
}
.fsb-text.fsb-success {
  color: <?php echo $fsb_success_color; ?>;
  font-weight: 600;
}
.fsb-text .fsb-icon {
  margin-right: 4px;
}
.fsb-progress-wrap {
  background: #e9ecef;
  border-radius: 10px;
  height: 6px;
  overflow: hidden;
  position: relative;
}
.fsb-progress-fill {
  height: 100%;
  border-radius: 10px;
  background: <?php echo $fsb_color; ?>;
  transition: width 0.6s ease;
  min-width: 0;
}
.fsb-progress-fill.fsb-complete {
  background: <?php echo $fsb_success_color; ?>;
}
#fsb-close {
  position: absolute;
  top: 4px;
  right: 10px;
  background: none;
  border: none;
  color: #aaa;
  font-size: 16px;
  cursor: pointer;
  padding: 2px 6px;
  line-height: 1;
  pointer-events: auto;
}
#fsb-close:hover {
  color: #666;
}

/* ===== Mini-Warenkorb Offcanvas Integration (BS5) ===== */
.fsb-minicart {
  padding: 8px 12px;
  background: <?php echo $fsb_bg_color; ?>;
  border-top: 1px solid rgba(0,0,0,0.08);
  margin-top: 8px;
}
.fsb-minicart .fsb-text {
  font-size: 11px;
  margin-bottom: 3px;
}
.fsb-minicart .fsb-progress-wrap {
  height: 4px;
}

/* ===== Warenkorb-Seite Integration ===== */
.fsb-cart-page {
  padding: 12px 15px;
  background: <?php echo $fsb_bg_color; ?>;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 4px;
  margin-bottom: 15px;
}
.fsb-cart-page .fsb-text {
  font-size: 14px;
  margin-bottom: 6px;
}
.fsb-cart-page .fsb-progress-wrap {
  height: 8px;
}

/* ===== Eingebetteter Header-Balken (#mrh-shipping-bar) ===== */
#mrh-shipping-bar[data-fsb-active] .mrh-shipping-text .fsb-icon {
  margin-right: 4px;
}
#mrh-shipping-bar[data-fsb-active] .mrh-shipping-text .fsb-amount {
  font-weight: 700;
}
#mrh-shipping-bar[data-fsb-active] .mrh-shipping-text.fsb-success {
  color: var(--mrh-green-accent, #5db233);
}
#mrh-shipping-bar[data-fsb-active] .mrh-progress-fill.fsb-complete {
  background-color: var(--mrh-green-accent, #5db233);
}

/* ===== Mobil: Kompakter ===== */
@media (max-width: 576px) {
  #fsb-bar {
    padding: 6px 12px;
  }
  .fsb-text {
    font-size: 12px;
  }
  .fsb-progress-wrap {
    height: 5px;
  }
  .fsb-minicart .fsb-text {
    font-size: 10px;
  }
}

/* ===== Bottom-Bar Offset (tpl_mrh_2026) ===== */
@media (max-width: 991px) {
  #fsb-container[data-fsb-pos="bottom"] {
    bottom: 60px; /* Platz fuer #mrhBottomBar */
  }
}
</style>

<script>
(function() {
  'use strict';
  
  var fsbBaseUrl = '<?php echo DIR_WS_BASE; ?>';
  var fsbHideEmpty = <?php echo $fsb_hide_empty ? 'true' : 'false'; ?>;
  var fsbShowFixed = <?php echo $fsb_show_fixed ? 'true' : 'false'; ?>;
  var fsbShowMinicart = <?php echo $fsb_show_minicart ? 'true' : 'false'; ?>;
  var fsbIsCartPage = <?php echo $fsb_is_cart ? 'true' : 'false'; ?>;
  var fsbShowOnCart = <?php echo $fsb_show_on_cart ? 'true' : 'false'; ?>;
  var fsbDismissed = false;
  var fsbUpdateTimer = null;
  var fsbPollTimer = null;
  var fsbLastData = null;
  var fsbLastCartCount = -1;
  var fsbCurrentCountryId = 0;
  
  // ===== Sprachtexte =====
  var fsbFallbackTexts = {
    'german': {
      remaining: 'Noch <span class="fsb-amount">{#FSB_AMOUNT#}</span> bis kostenloser Versand!',
      reached: 'Gratulation! Kostenloser Versand!',
      close: 'Schliessen'
    },
    'english': {
      remaining: 'Only <span class="fsb-amount">{#FSB_AMOUNT#}</span> more for free shipping!',
      reached: 'Congratulations! Free shipping!',
      close: 'Close'
    },
    'french': {
      remaining: 'Plus que <span class="fsb-amount">{#FSB_AMOUNT#}</span> pour la livraison gratuite !',
      reached: 'F\u00e9licitations ! Livraison gratuite !',
      close: 'Fermer'
    },
    'spanish': {
      remaining: '\u00a1Solo <span class="fsb-amount">{#FSB_AMOUNT#}</span> m\u00e1s para env\u00edo gratuito!',
      reached: '\u00a1Felicidades! \u00a1Env\u00edo gratuito!',
      close: 'Cerrar'
    }
  };
  
  var fsbCurrentLang = '<?php echo $fsb_lang; ?>';
  
  // ===== Sprachtext holen =====
  function fsbGetText(key, amount) {
    var text = '';
    if (typeof fsbLangTexts !== 'undefined' && fsbLangTexts[key]) {
      text = fsbLangTexts[key];
    }
    if (!text) {
      var lang = fsbFallbackTexts[fsbCurrentLang] || fsbFallbackTexts['german'];
      text = lang[key] || '';
    }
    if (amount) {
      text = text.replace('{#FSB_AMOUNT#}', '<span class="fsb-amount">' + amount + '</span>');
    }
    return text;
  }
  
  // ===== Aktuelles Versandland lesen =====
  // tpl_mrh_2026: form#countries select[name="country"] (wie reinsalz)
  // Fallback: bootstrap4 Selektoren
  function fsbGetSelectedCountryId() {
    // 1. tpl_mrh_2026 / reinsalz: form#countries select[name="country"]
    var sel = document.querySelector('#countries select[name="country"]');
    if (sel && sel.value && parseInt(sel.value) > 0) {
      return parseInt(sel.value);
    }
    // 2. Fallback: bootstrap4 Select
    sel = document.getElementById('box-shipping-country-select') 
       || document.getElementById('box-shipping-country-select-2');
    if (sel && sel.value && parseInt(sel.value) > 0) {
      return parseInt(sel.value);
    }
    return 0;
  }
  
  // ===== Header-Bar uebernehmen (#mrh-shipping-bar) =====
  // Uebernimmt den bestehenden Container zwischen Topbar und Header
  // und ersetzt die statische MRH.ShippingBar Logik mit dynamischen AJAX-Daten
  function fsbInitHeaderBar() {
    var headerBar = document.getElementById('mrh-shipping-bar');
    if (!headerBar) return;
    
    // MRH.ShippingBar deaktivieren (mrh-core.js.php)
    if (typeof MRH !== 'undefined' && MRH.ShippingBar) {
      MRH.ShippingBar.update = function() {}; // Noop
    }
    
    // data-fsb Attribute setzen fuer CSS-Targeting
    headerBar.setAttribute('data-fsb-active', 'true');
    
    // Bestehende Elemente finden
    var textEl = headerBar.querySelector('.mrh-shipping-text');
    var fillEl = headerBar.querySelector('.mrh-progress-fill');
    
    // data-fsb Attribute setzen fuer fsbUpdateAll()
    if (textEl) textEl.setAttribute('data-fsb', 'header-text');
    if (fillEl) fillEl.setAttribute('data-fsb', 'header-fill');
  }
  
  // ===== Fixierter Balken erstellen =====
  function fsbCreateFixedBar() {
    if (!fsbShowFixed) return;
    if (document.getElementById('fsb-container')) return;
    
    var container = document.createElement('div');
    container.id = 'fsb-container';
    container.className = 'fsb-hidden';
    container.setAttribute('data-fsb-pos', '<?php echo $fsb_position; ?>');
    container.innerHTML = 
      '<div id="fsb-bar" role="complementary">' +
        '<button id="fsb-close" aria-label="' + fsbGetText('close') + '" title="' + fsbGetText('close') + '">&times;</button>' +
        '<div id="fsb-inner">' +
          '<div class="fsb-text" data-fsb="fixed-text"></div>' +
          '<div class="fsb-progress-wrap">' +
            '<div class="fsb-progress-fill" data-fsb="fixed-fill" style="width:0%"></div>' +
          '</div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(container);
    
    document.getElementById('fsb-close').addEventListener('click', function() {
      fsbDismissed = true;
      container.className = 'fsb-hidden';
      try { sessionStorage.setItem('fsb_dismissed', '1'); } catch(e) {}
    });
    
    try {
      if (sessionStorage.getItem('fsb_dismissed') === '1') {
        fsbDismissed = true;
      }
    } catch(e) {}
  }
  
  // ===== Mini-Warenkorb Balken erstellen (BS5 Offcanvas) =====
  function fsbCreateMinicartBar() {
    if (!fsbShowMinicart) return;
    
    // tpl_mrh_2026: BS5 Offcanvas Warenkorb
    var offcanvas = document.getElementById('offcanvasCart');
    if (!offcanvas) return;
    
    var offcanvasBody = offcanvas.querySelector('.offcanvas-body');
    if (!offcanvasBody) return;
    
    var existing = offcanvasBody.querySelector('.fsb-minicart');
    if (existing) return;
    
    var minicartBar = document.createElement('div');
    minicartBar.className = 'fsb-minicart';
    minicartBar.innerHTML = 
      '<div class="fsb-text" data-fsb="mini-text"></div>' +
      '<div class="fsb-progress-wrap">' +
        '<div class="fsb-progress-fill" data-fsb="mini-fill" style="width:0%"></div>' +
      '</div>';
    
    // Einfuegen: Am Ende der offcanvas-body (vor den Buttons)
    var toggleAction = offcanvasBody.querySelector('.toggle_action_1');
    if (toggleAction) {
      offcanvasBody.insertBefore(minicartBar, toggleAction);
    } else {
      offcanvasBody.appendChild(minicartBar);
    }
  }
  
  // ===== Warenkorb-Seite Balken erstellen =====
  function fsbCreateCartPageBar() {
    if (!fsbIsCartPage || !fsbShowOnCart) return;
    if (document.getElementById('fsb-cart-page')) return;
    
    // tpl_mrh_2026: Selektoren fuer Warenkorb-Seite
    var cartContainer = document.querySelector('.shopping-cart')
                     || document.querySelector('.table-responsive')
                     || document.querySelector('#cartContent')
                     || document.querySelector('form[action*="shopping_cart"]')
                     || document.querySelector('main#main-content .contentrow');
    if (!cartContainer) return;
    
    var cartPageBar = document.createElement('div');
    cartPageBar.id = 'fsb-cart-page';
    cartPageBar.className = 'fsb-cart-page';
    cartPageBar.innerHTML = 
      '<div class="fsb-text" data-fsb="cart-text"></div>' +
      '<div class="fsb-progress-wrap">' +
        '<div class="fsb-progress-fill" data-fsb="cart-fill" style="width:0%"></div>' +
      '</div>';
    
    cartContainer.parentNode.insertBefore(cartPageBar, cartContainer);
  }
  
  // ===== Alle Balken aktualisieren =====
  function fsbUpdateAll(data) {
    fsbLastData = data;
    
    if (!data.active) {
      var container = document.getElementById('fsb-container');
      if (container) container.className = 'fsb-hidden';
      var minicartBars = document.querySelectorAll('.fsb-minicart');
      for (var i = 0; i < minicartBars.length; i++) {
        minicartBars[i].style.display = 'none';
      }
      return;
    }
    
    fsbLastCartCount = data.cart_count;
    
    var hideForEmpty = (fsbHideEmpty && data.cart_count < 1);
    
    var textRemaining = fsbGetText('remaining', data.remaining_formatted);
    var textReached = fsbGetText('reached');
    
    var textEls = document.querySelectorAll('[data-fsb$="-text"]');
    var fillEls = document.querySelectorAll('[data-fsb$="-fill"]');
    
    for (var t = 0; t < textEls.length; t++) {
      if (data.reached) {
        textEls[t].className = 'fsb-text fsb-success';
        textEls[t].innerHTML = '<span class="fsb-icon">&#10003;</span>' + textReached;
      } else {
        textEls[t].className = 'fsb-text';
        textEls[t].innerHTML = '<span class="fsb-icon">&#128666;</span>' + textRemaining;
      }
    }
    
    for (var f = 0; f < fillEls.length; f++) {
      if (data.reached) {
        fillEls[f].style.width = '100%';
        fillEls[f].className = 'fsb-progress-fill fsb-complete';
      } else {
        fillEls[f].style.width = data.percentage + '%';
        fillEls[f].className = 'fsb-progress-fill';
      }
    }
    
    // ===== Fixierter Balken =====
    var container = document.getElementById('fsb-container');
    if (container) {
      if (hideForEmpty || fsbDismissed) {
        container.className = 'fsb-hidden';
      } else {
        container.className = 'fsb-visible';
        try { sessionStorage.removeItem('fsb_dismissed'); } catch(e) {}
        
        if (data.reached) {
          setTimeout(function() {
            if (!fsbDismissed && container) {
              container.className = 'fsb-hidden';
            }
          }, 5000);
        }
      }
    }
    
    // ===== Mini-Warenkorb Balken =====
    var minicartBars = document.querySelectorAll('.fsb-minicart');
    for (var m = 0; m < minicartBars.length; m++) {
      minicartBars[m].style.display = hideForEmpty ? 'none' : 'block';
    }
    
    // ===== Warenkorb-Seite Balken =====
    var cartPageBar = document.getElementById('fsb-cart-page');
    if (cartPageBar) {
      cartPageBar.style.display = hideForEmpty ? 'none' : 'block';
    }
    
    // ===== Header-Bar (#mrh-shipping-bar) =====
    var headerBar = document.getElementById('mrh-shipping-bar');
    if (headerBar && headerBar.hasAttribute('data-fsb-active')) {
      headerBar.style.display = (hideForEmpty && data.cart_count < 1) ? '' : '';
    }
  }
  
  // ===== AJAX Fetch =====
  function fsbFetch(countryId) {
    var url = fsbBaseUrl + 'ajax.php?ext=get_free_shipping_bar';
    
    var cid = countryId || fsbCurrentCountryId || fsbGetSelectedCountryId();
    if (cid > 0) {
      url += '&country_id=' + cid;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          fsbUpdateAll(data);
        } catch(e) {}
      }
    };
    xhr.send();
  }
  
  // ===== Versandland lesen =====
  function fsbReadCurrentCountry() {
    var cid = fsbGetSelectedCountryId();
    if (cid > 0) {
      fsbCurrentCountryId = cid;
    }
  }
  
  // ===== Offcanvas Warenkorb beobachten (BS5) =====
  function fsbObserveMinicart() {
    if (!fsbShowMinicart) return;
    
    var offcanvas = document.getElementById('offcanvasCart');
    if (!offcanvas) return;
    
    var offcanvasBody = offcanvas.querySelector('.offcanvas-body');
    if (!offcanvasBody) return;
    
    var observer = new MutationObserver(function(mutations) {
      var existing = offcanvasBody.querySelector('.fsb-minicart');
      if (!existing) {
        fsbCreateMinicartBar();
        if (fsbLastData) {
          fsbUpdateAll(fsbLastData);
        }
      }
    });
    
    observer.observe(offcanvasBody, { childList: true, subtree: true });
    
    // BS5 Offcanvas Event: Beim Oeffnen aktualisieren
    offcanvas.addEventListener('shown.bs.offcanvas', function() {
      fsbCreateMinicartBar();
      fsbFetch();
    });
  }
  
  // ===== Smart-Polling (v1.3.2: 30s + Tab-Visibility) =====
  function fsbStartPolling() {
    fsbPollTimer = setInterval(function() {
      if (document.hidden) return;
      fsbFetch();
      fsbCreateMinicartBar();
    }, 30000);
    
    document.addEventListener('visibilitychange', function() {
      if (!document.hidden && fsbLastData) {
        fsbFetch();
      }
    });
  }
  
  // ===== Add-to-Cart Erkennung =====
  function fsbWatchAddToCart() {
    document.addEventListener('click', function(e) {
      var target = e.target;
      var isCartAction = false;
      
      var el = target;
      for (var i = 0; i < 5 && el; i++) {
        if (el.tagName) {
          var cls = (el.className || '').toString().toLowerCase();
          var href = (el.getAttribute('href') || '').toLowerCase();
          var action = (el.getAttribute('action') || '').toLowerCase();
          
          if (cls.indexOf('cart') !== -1 || cls.indexOf('add-to') !== -1 ||
              cls.indexOf('btn-cart') !== -1 || cls.indexOf('add_product') !== -1 ||
              href.indexOf('cart_actions') !== -1 || href.indexOf('add_product') !== -1 ||
              action.indexOf('cart_actions') !== -1) {
            isCartAction = true;
            break;
          }
        }
        el = el.parentElement;
      }
      
      if (isCartAction) {
        if (fsbUpdateTimer) clearTimeout(fsbUpdateTimer);
        fsbUpdateTimer = setTimeout(function() {
          fsbFetch();
          fsbCreateMinicartBar();
        }, 2000);
      }
    });
  }
  
  // ===== Versandland-Wechsel beobachten =====
  function fsbWatchCountryChange() {
    // tpl_mrh_2026: form#countries select[name="country"]
    var countrySelect = document.querySelector('#countries select[name="country"]');
    if (countrySelect) {
      countrySelect.addEventListener('change', function() {
        var cid = parseInt(this.value);
        if (cid > 0) {
          fsbCurrentCountryId = cid;
          fsbFetch(cid);
        }
      });
    }
  }
  
  // ===== Initialisierung =====
  function fsbInit() {
    fsbReadCurrentCountry();
    
    fsbInitHeaderBar();
    fsbCreateFixedBar();
    fsbCreateMinicartBar();
    fsbCreateCartPageBar();
    fsbObserveMinicart();
    fsbWatchCountryChange();
    
    fsbFetch();
    
    fsbStartPolling();
    fsbWatchAddToCart();
  }
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fsbInit);
  } else {
    fsbInit();
  }
  
})();
</script>
<?php
} // Ende if MODULE_FREE_SHIPPING_BAR_STATUS
?>
