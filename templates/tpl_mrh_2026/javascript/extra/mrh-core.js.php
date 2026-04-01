<?php
/* ============================================================
   MRH 2026 – Core JavaScript (Vanilla JS)
   ============================================================
   Alle neuen MRH-Funktionen in reinem Vanilla JS.
   Phase 1: Koexistenz mit jQuery (kein jQuery verwenden!)
   Phase 2: jQuery komplett entfernen

   Wird automatisch über auto_include() in general_bottom.js.php
   geladen und bei COMPRESS_JAVASCRIPT komprimiert.

   v1.2.0: Config wird direkt per PHP eingelesen (kein Timing-Problem)
   ============================================================ */

// ---- Mega-Menu Config direkt einlesen (statt separate JS-Datei) ----
// Damit ist die Variable GARANTIERT verfügbar bevor das Script läuft.
$_mrh_megamenu_js = '';
$_mrh_cache_file = DIR_FS_CATALOG . 'templates/' . CURRENT_TEMPLATE . '/config/megamenu_config.json';
if (file_exists($_mrh_cache_file)) {
    $_mrh_json_raw = file_get_contents($_mrh_cache_file);
    $_mrh_cache = json_decode($_mrh_json_raw, true);
    if (is_array($_mrh_cache) && !empty($_mrh_cache)) {
        // Sprach-Mapping
        $_mrh_lang_map = array(2 => 'de', 1 => 'en', 5 => 'fr', 7 => 'es');
        $_mrh_active_lang = isset($_mrh_lang_map[(int)($_SESSION['languages_id'] ?? 2)])
            ? $_mrh_lang_map[(int)($_SESSION['languages_id'] ?? 2)]
            : 'de';

        // Kategorien aufbereiten
        $_mrh_entries = isset($_mrh_cache['categories']) ? $_mrh_cache['categories'] : array();
        $_mrh_output = array();
        foreach ($_mrh_entries as $_mrh_entry) {
            if (!isset($_mrh_entry['columns']) || !is_array($_mrh_entry['columns'])) continue;
            $_mrh_cols_out = array();
            foreach ($_mrh_entry['columns'] as $_mrh_col) {
                $_mrh_title = '';
                if (isset($_mrh_col['titles'][$_mrh_active_lang]) && $_mrh_col['titles'][$_mrh_active_lang] !== '') {
                    $_mrh_title = $_mrh_col['titles'][$_mrh_active_lang];
                } elseif (isset($_mrh_col['titles']['de'])) {
                    $_mrh_title = $_mrh_col['titles']['de'];
                }
                $_mrh_items_out = array();
                if (isset($_mrh_col['items']) && is_array($_mrh_col['items'])) {
                    foreach ($_mrh_col['items'] as $_mrh_item) {
                        $_mrh_label = '';
                        if (isset($_mrh_item['labels'][$_mrh_active_lang]) && $_mrh_item['labels'][$_mrh_active_lang] !== '') {
                            $_mrh_label = $_mrh_item['labels'][$_mrh_active_lang];
                        } elseif (isset($_mrh_item['labels']['de'])) {
                            $_mrh_label = $_mrh_item['labels']['de'];
                        }
                        if ($_mrh_label === '') continue;
                        $_mrh_items_out[] = array(
                            'category_id' => (int)$_mrh_item['category_id'],
                            'label'       => $_mrh_label,
                            'url'         => isset($_mrh_item['url']) ? $_mrh_item['url'] : '',
                        );
                    }
                }
                if (empty($_mrh_items_out) && $_mrh_title === '') continue;
                $_mrh_cols_out[] = array(
                    'title' => $_mrh_title,
                    'icon'  => isset($_mrh_col['icon']) ? $_mrh_col['icon'] : '',
                    'items' => $_mrh_items_out,
                );
            }
            if (empty($_mrh_cols_out)) continue;
            $_mrh_pname = '';
            if (isset($_mrh_entry['parent_names'][$_mrh_active_lang]) && $_mrh_entry['parent_names'][$_mrh_active_lang] !== '') {
                $_mrh_pname = $_mrh_entry['parent_names'][$_mrh_active_lang];
            } elseif (isset($_mrh_entry['parent_names']['de'])) {
                $_mrh_pname = $_mrh_entry['parent_names']['de'];
            }
            $_mrh_output[] = array(
                'parent_id'   => (int)$_mrh_entry['parent_id'],
                'parent_name' => $_mrh_pname,
                'columns'     => $_mrh_cols_out,
            );
        }
        $_mrh_megamenu_js = json_encode($_mrh_output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
?>
<script>
<?php if ($_mrh_megamenu_js): ?>
/* MRH Mega-Menu Config (inline, v1.2.0) */
window.MRH_MEGAMENU_CONFIG = <?php echo $_mrh_megamenu_js; ?>;
<?php endif; ?>
/* ============================================================
   MRH 2026 Core – v1.1.0
   Vanilla JS – kein jQuery!
   v1.1.0: Bugfix getDashboardConfig + buildDropdown + _buildFromDashboardConfig
   ============================================================ */
(function() {
  'use strict';

  /* ----------------------------------------------------------
     NAMESPACE: Alle MRH-Funktionen unter window.MRH
     ---------------------------------------------------------- */
  window.MRH = window.MRH || {};

  /* ----------------------------------------------------------
     UTILITY: Hilfs-Funktionen
     ---------------------------------------------------------- */
  MRH.Utils = {
    /**
     * Sicheres querySelector mit Fallback
     */
    qs: function(selector, parent) {
      return (parent || document).querySelector(selector);
    },

    /**
     * Sicheres querySelectorAll als Array
     */
    qsa: function(selector, parent) {
      return Array.from((parent || document).querySelectorAll(selector));
    },

    /**
     * Event-Delegation (wie jQuery .on())
     */
    on: function(parent, event, selector, handler) {
      var el = typeof parent === 'string' ? document.querySelector(parent) : parent;
      if (!el) return;
      el.addEventListener(event, function(e) {
        var target = e.target.closest(selector);
        if (target && el.contains(target)) {
          handler.call(target, e);
        }
      });
    },

    /**
     * Cookie setzen
     */
    setCookie: function(name, value, days) {
      var d = new Date();
      d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
      document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
    },

    /**
     * Cookie lesen
     */
    getCookie: function(name) {
      var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
      return match ? match[2] : '';
    },

    /**
     * Debounce (für Scroll/Resize Events)
     */
    debounce: function(fn, delay) {
      var timer;
      return function() {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function() { fn.apply(context, args); }, delay);
      };
    },

    /**
     * Throttle (für häufige Events)
     */
    throttle: function(fn, limit) {
      var waiting = false;
      return function() {
        if (!waiting) {
          fn.apply(this, arguments);
          waiting = true;
          setTimeout(function() { waiting = false; }, limit);
        }
      };
    }
  };

  /* ----------------------------------------------------------
     01 TOPBAR: Marquee-Effekt für Mobile (optional)
     ---------------------------------------------------------- */
  MRH.Topbar = {
    init: function() {
      var topbar = MRH.Utils.qs('#mrh-topbar');
      if (!topbar) return;
      // Topbar ist rein CSS – hier nur für zukünftige
      // Erweiterungen (z.B. rotierende Nachrichten)
    }
  };

  /* ----------------------------------------------------------
     02 FREE SHIPPING BAR: Warenkorb-Fortschritt
     ---------------------------------------------------------- */
  MRH.ShippingBar = {
    threshold: <?php echo defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER')
                      ? (float)MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER
                      : 50; ?>,

    init: function() {
      var bar = MRH.Utils.qs('#mrh-shipping-bar');
      if (!bar) return;
      this.bar = bar;
      this.fill = MRH.Utils.qs('.mrh-progress-fill', bar);
      this.text = MRH.Utils.qs('.mrh-shipping-text', bar);
      this.update();
    },

    /**
     * Fortschritt aktualisieren basierend auf Warenkorb-Wert
     * Wird aufgerufen nach AJAX-Cart-Updates
     */
    update: function(cartTotal) {
      if (!this.fill) return;
      cartTotal = cartTotal || 0;
      var pct = Math.min(100, Math.round((cartTotal / this.threshold) * 100));
      this.fill.style.width = pct + '%';

      if (pct >= 100 && this.text) {
        this.text.innerHTML = '<i class="fas fa-check-circle"></i> Gratis Versand!';
        this.fill.style.backgroundColor = 'var(--mrh-green-accent)';
      }
    }
  };

  /* ----------------------------------------------------------
     03 STICKY HEADER
     ---------------------------------------------------------- */
  MRH.StickyHeader = {
    lastScroll: 0,
    headerHeight: 0,

    init: function() {
      var header = MRH.Utils.qs('#main-header');
      if (!header) return;
      this.header = header;
      this.headerHeight = header.offsetHeight;

      // Sticky-Klasse nur bei Scroll nach unten > Headerhöhe
      window.addEventListener('scroll', MRH.Utils.throttle(this.onScroll.bind(this), 100), { passive: true });
    },

    onScroll: function() {
      var st = window.pageYOffset || document.documentElement.scrollTop;

      if (st > this.headerHeight + 100) {
        // Scrolled past header
        if (!this.header.classList.contains('mrh-sticky')) {
          this.header.classList.add('mrh-sticky');
        }
        // Show/Hide basierend auf Scroll-Richtung
        if (st > this.lastScroll && st > this.headerHeight + 200) {
          // Scroll Down → Header verstecken
          this.header.classList.add('mrh-sticky-hidden');
        } else {
          // Scroll Up → Header zeigen
          this.header.classList.remove('mrh-sticky-hidden');
        }
      } else {
        this.header.classList.remove('mrh-sticky', 'mrh-sticky-hidden');
      }

      this.lastScroll = st;
    }
  };

  /* ----------------------------------------------------------
     04 BACK TO TOP BUTTON
     ---------------------------------------------------------- */
  MRH.BackToTop = {
    init: function() {
      // Button erstellen
      var btn = document.createElement('button');
      btn.id = 'mrh-back-to-top';
      btn.className = 'mrh-back-to-top';
      btn.setAttribute('aria-label', 'Nach oben scrollen');
      btn.innerHTML = '<i class="fas fa-chevron-up"></i>';
      document.body.appendChild(btn);
      this.btn = btn;

      // Klick-Handler
      btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });

      // Sichtbarkeit bei Scroll
      window.addEventListener('scroll', MRH.Utils.throttle(this.toggle.bind(this), 200), { passive: true });
    },

    toggle: function() {
      var st = window.pageYOffset || document.documentElement.scrollTop;
      if (st > 400) {
        this.btn.classList.add('visible');
      } else {
        this.btn.classList.remove('visible');
      }
    }
  };

  /* ----------------------------------------------------------
     05 LAZY LOADING: Native + Fallback
     ---------------------------------------------------------- */
  MRH.LazyLoad = {
    init: function() {
      // Native lazy loading für Browser die es unterstützen
      var images = MRH.Utils.qsa('img[loading="lazy"]');
      if ('loading' in HTMLImageElement.prototype) {
        // Browser unterstützt native lazy loading – nichts zu tun
        return;
      }
      // Fallback: IntersectionObserver
      if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
          entries.forEach(function(entry) {
            if (entry.isIntersecting) {
              var img = entry.target;
              if (img.dataset.src) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
              }
              observer.unobserve(img);
            }
          });
        }, { rootMargin: '200px' });

        images.forEach(function(img) { observer.observe(img); });
      }
    }
  };

  /* ----------------------------------------------------------
     06 ACCESSIBILITY: Keyboard-Navigation + Focus-Trap
     ---------------------------------------------------------- */
  MRH.A11y = {
    init: function() {
      // Skip-to-Content Link
      this.addSkipLink();
      // Focus-Visible Polyfill (nur wenn nötig)
      this.focusVisible();
    },

    addSkipLink: function() {
      var main = MRH.Utils.qs('#main-content');
      if (!main) return;

      var existing = MRH.Utils.qs('.mrh-skip-link');
      if (existing) return;

      var link = document.createElement('a');
      link.href = '#main-content';
      link.className = 'mrh-skip-link';
      link.textContent = 'Zum Inhalt springen';
      document.body.insertBefore(link, document.body.firstChild);
    },

    focusVisible: function() {
      // Füge .using-keyboard Klasse hinzu wenn Tab gedrückt wird
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
          document.body.classList.add('using-keyboard');
        }
      });
      document.addEventListener('mousedown', function() {
        document.body.classList.remove('using-keyboard');
      });
    }
  };

  /* ----------------------------------------------------------
     07 PERFORMANCE: Prefetch + Preconnect
     ---------------------------------------------------------- */
  MRH.Performance = {
    init: function() {
      // Prefetch bei Hover über Links (nur Desktop)
      if (window.matchMedia('(hover: hover)').matches) {
        this.prefetchOnHover();
      }
    },

    prefetchOnHover: function() {
      var prefetched = new Set();

      MRH.Utils.on(document.body, 'mouseenter', 'a[href]', function() {
        var href = this.getAttribute('href');
        // Nur interne Links, keine Anker, keine bereits prefetchten
        if (!href || href.startsWith('#') || href.startsWith('javascript') ||
            href.startsWith('mailto') || href.startsWith('tel') ||
            prefetched.has(href)) return;

        // Nur gleiche Domain
        try {
          var url = new URL(href, window.location.origin);
          if (url.origin !== window.location.origin) return;
        } catch(e) { return; }

        prefetched.add(href);
        var link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = href;
        document.head.appendChild(link);
      });
    }
  };

  /* ----------------------------------------------------------
     08 MEGA-MENÜ: Vanilla JS Navigation
     ---------------------------------------------------------- */
  MRH.MegaMenu = {
    hoverDelay: 150,
    closeDelay: 250,
    openTimer: null,
    closeTimer: null,
    activeItem: null,
    activeDropdown: null,
    isTouch: false,

    init: function() {
      var nav = MRH.Utils.qs('#mrhMegaNav');
      if (!nav) return;
      this.nav = nav;
      this.bar = MRH.Utils.qs('.mrh-mega-nav-bar', nav);

      // Touch-Erkennung
      this.isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

      // 1. CatNavi in Nav-Items umwandeln
      this.transformCategories();

      // 2. Event-Listener
      this.bindEvents();

      // 3. Aktiven Nav-Item markieren
      this.markActive();
    },

    /**
     * Wandelt die Smarty-generierte CatNavi in mrh-nav-items um
     * und baut Mega-Dropdown-Panels für Kategorien mit Submenüs
     */
    transformCategories: function() {
      var catWrap = MRH.Utils.qs('#mrhNavCategories');
      if (!catWrap) return;

      var catNav = MRH.Utils.qs('.CatNavi', catWrap);
      if (!catNav) return;

      var level1Items = MRH.Utils.qsa(':scope > li', catNav);
      var fragment = document.createDocumentFragment();

      // Icon-Map für Hauptkategorien (aus Sprachdatei oder Fallback)
      var iconMap = {
        'samen': 'fa-seedling',
        'seed': 'fa-seedling',
        'cannabis': 'fa-cannabis',
        'cannabispflanz': 'fa-cannabis',
        'grow': 'fa-sun',
        'head': 'fa-bong',
        'default': 'fa-leaf'
      };

      var self = this;

      // Statische Nav-Items sammeln um Duplikate zu vermeiden (SEO: saubere URLs bevorzugen)
      var staticNavTexts = [];
      MRH.Utils.qsa('.mrh-nav-item[data-nav]', this.bar).forEach(function(item) {
        var span = item.querySelector('span');
        if (span) staticNavTexts.push(span.textContent.trim().toLowerCase());
      });

      level1Items.forEach(function(li) {
        var link = MRH.Utils.qs(':scope > a', li);
        if (!link) return;

        var text = link.textContent.trim();
        var href = link.getAttribute('href') || '#';

        // Duplikat-Check: Wenn ein statischer Nav-Item mit gleichem Text existiert, überspringen
        if (staticNavTexts.indexOf(text.toLowerCase()) > -1) return;

        var subUl = MRH.Utils.qs(':scope > ul', li);
        var hasSubmenu = !!subUl;

        // Icon bestimmen
        var iconClass = iconMap['default'];
        var textLower = text.toLowerCase();
        Object.keys(iconMap).forEach(function(key) {
          if (textLower.indexOf(key) > -1) iconClass = iconMap[key];
        });

        // Nav-Item erstellen
        var navItem = document.createElement('a');
        navItem.href = href;
        navItem.className = 'mrh-nav-item';
        navItem.setAttribute('data-nav', text.toLowerCase().replace(/\s+/g, '-'));
        navItem.innerHTML = '<i class="fa-solid ' + iconClass + '"></i> ' +
                            '<span>' + text + '</span>';

        if (hasSubmenu) {
          navItem.innerHTML += ' <i class="fa-solid fa-chevron-down mrh-nav-arrow"></i>';
          navItem.setAttribute('data-mega', 'true');

          // Mega-Dropdown Panel bauen
          var dropdown = self.buildDropdown(subUl, href, text);
          navItem._megaDropdown = dropdown;
          self.nav.appendChild(dropdown);
        }

        fragment.appendChild(navItem);
      });

      // Statische Items (Angebote, Neue Artikel, etc.) kommen NACH den Kategorien
      var staticItems = MRH.Utils.qsa('.mrh-nav-item[data-nav]', this.bar);
      var homeItem = MRH.Utils.qs('.mrh-nav-home', this.bar);

      // Kategorien nach Home einfügen
      if (homeItem && homeItem.nextSibling) {
        this.bar.insertBefore(fragment, homeItem.nextSibling);
      } else {
        this.bar.appendChild(fragment);
      }

      // Original CatNavi verstecken
      catWrap.style.display = 'none';
    },

    /**
     * Kategorie-spezifische Spalten-Konfiguration (SEO 2026)
     * Jede Hauptkategorie bekommt passende Überschriften, Icons
     * und eine feste Zuordnung welche Unterkategorien in welche Spalte gehören.
     * maxPerCol: Maximale Anzahl Links pro Spalte (5 = SEO-optimiert)
     */
    getCategoryConfig: function(parentText) {
      var textLower = (parentText || '').toLowerCase();

      // Samen Shop – SEO 2026: Haupttypen | Kaufentscheidung | Anbau-Szenarien
      // staticLinks: Feste Links die IMMER angezeigt werden (Level-2 Kategorien)
      // columns/keywords: Nur für Zuordnung von CatNavi Level-1 Items (Fallback)
      if (textLower.indexOf('samen') > -1 || textLower.indexOf('seed') > -1 || textLower.indexOf('hanfsamen') > -1) {
        return {
          titles: ['Cannabis Samen kaufen', 'Beliebte Auswahl', 'Anbau & Spezial'],
          icons:  ['fa-seedling', 'fa-fire', 'fa-leaf'],
          maxPerCol: 5,
          useStaticOnly: true,
          staticLinks: [
            [
              {text: 'Feminisierte Samen', href: '/samen-shop/feminisierte-samen/'},
              {text: 'Autoflowering Samen', href: '/samen-shop/autoflowering-samen/'},
              {text: 'Reguläre Samen', href: '/samen-shop/regulaere-samen/'},
              {text: 'F1 Cannabis Sorten', href: '/samen-shop/sortenvielfalt/f1-cannabis-sorten/'},
              {text: 'CBD-Reiche Sorten', href: '/samen-shop/sortenvielfalt/cbd-reiche-cannabis-sorten/'}
            ],
            [
              {text: 'Top-Seller', href: '/samen-shop/favoriten/top-seller/'},
              {text: 'Anfänger Samen', href: '/samen-shop/favoriten/anfaenger-samen/'},
              {text: 'THC-Reiche Sorten', href: '/samen-shop/sortenvielfalt/thc-reiche-sorten/'},
              {text: 'USA Genetik', href: '/samen-shop/weitere-kategorien/usa-genetik/'},
              {text: 'Klassiker', href: '/samen-shop/favoriten/klassiker/'}
            ],
            [
              {text: 'Reine Indoor Samen', href: '/samen-shop/weitere-kategorien/reine-indoor-samen/'},
              {text: 'Reine Outdoor Samen', href: '/samen-shop/weitere-kategorien/reine-outdoor-samen/'},
              {text: 'Fast Flowering Samen', href: '/samen-shop/sortenvielfalt/fast-flowering-samen/'},
              {text: 'Medizinische Samen', href: '/samen-shop/weitere-kategorien/medizinische-samen/'},
              {text: 'Bulk Samen', href: '/samen-shop/weitere-kategorien/bulk-samen/'}
            ]
          ]
        };
      }
      // Growshop – SEO 2026: Grundausstattung | Nährstoffe & Pflege | Zubehör & Ernte
      if (textLower.indexOf('grow') > -1) {
        return {
          titles: ['Grow Grundausstattung', 'Nährstoffe & Pflege', 'Zubehör & Ernte'],
          icons:  ['fa-box-open', 'fa-hand-holding-droplet', 'fa-screwdriver-wrench'],
          maxPerCol: 5,
          useStaticOnly: false,
          columns: [
            ['komplett', 'set', 'growbox', 'growzelt', 'beleuchtung', 'licht', 'led', 'töpfe', 'behälter'],
            ['dünger', 'erde', 'substrat', 'bewässer', 'schädling', 'anzucht', 'propagat'],
            ['zubehör', 'ernte', 'verarbeit', 'lüftung', 'klima']
          ]
        };
      }
      // Headshop – SEO 2026: Rauchen & Dampfen | Zubehör & Tools (2 Spalten)
      if (textLower.indexOf('head') > -1) {
        return {
          titles: ['Rauchen & Dampfen', 'Zubehör & Tools'],
          icons:  ['fa-cloud', 'fa-wrench'],
          maxPerCol: 5,
          useStaticOnly: false,
          columns: [
            ['bong', 'pfeif', 'verdampf', 'vaporiz', 'terpen'],
            ['grinder', 'mischtablett', 'waage', 'zubehör', 'verarbeit', 'extrakt', 'bücher', 'multimedia']
          ]
        };
      }
      // Cannabispflanzen – kein Mega-Dropdown nötig (nur 1 SubCat)
      if (textLower.indexOf('cannabispflanz') > -1 || textLower.indexOf('pflanz') > -1) {
        return {
          titles: ['Pflanzen kaufen'],
          icons:  ['fa-cannabis'],
          maxPerCol: 5,
          useStaticOnly: false,
          columns: [[]]
        };
      }
      // Fallback
      return {
        titles: ['Sortiment', 'Highlights', 'Mehr entdecken'],
        icons:  ['fa-layer-group', 'fa-star', 'fa-compass'],
        maxPerCol: 5,
        useStaticOnly: false,
        columns: [[], [], []]
      };
    },

    /**
     * Prüft ob eine Dashboard-Konfiguration (window.MRH_MEGAMENU_CONFIG)
     * für eine Kategorie existiert. Matcht über cPath-Parameter in der URL
     * oder über parent_name Textvergleich.
     *
     * v1.1.0 BUGFIX: MRH_MEGAMENU_CONFIG ist ein ARRAY, kein Object.
     * Muss mit Array.find() nach parent_id suchen.
     *
     * @param {string} href – Link der Hauptkategorie
     * @param {string} parentText – Name der Hauptkategorie (Fallback-Match)
     * @returns {Object|null} Dashboard-Config oder null
     */
    getDashboardConfig: function(href, parentText) {
      var dashConfig = window.MRH_MEGAMENU_CONFIG;

      // v1.1.0: Prüfe ob es ein Array ist (korrektes Format)
      if (!dashConfig || !Array.isArray(dashConfig) || dashConfig.length === 0) return null;

      // Versuch 1: cPath aus href extrahieren und per parent_id matchen
      var cPathMatch = href ? href.match(/cPath=([\d_]+)/) : null;
      if (cPathMatch) {
        var cPath = cPathMatch[1];
        var rootCatId = parseInt(cPath.split('_')[0], 10);

        // v1.1.0 FIX: Array.find() statt Object-Key-Zugriff
        for (var i = 0; i < dashConfig.length; i++) {
          if (dashConfig[i].parent_id === rootCatId) {
            return dashConfig[i];
          }
        }
      }

      // Versuch 2: Über parent_name matchen (Fallback für SEO-URLs ohne cPath)
      if (parentText) {
        var textLower = parentText.toLowerCase();
        for (var j = 0; j < dashConfig.length; j++) {
          var pName = (dashConfig[j].parent_name || '').toLowerCase();
          if (pName && textLower.indexOf(pName) > -1) {
            return dashConfig[j];
          }
          // Auch umgekehrt prüfen (parent_name enthält parentText)
          if (pName && pName.indexOf(textLower) > -1) {
            return dashConfig[j];
          }
        }
      }

      return null;
    },

    /**
     * Baut ein Mega-Dropdown-Panel für eine Hauptkategorie.
     *
     * Priorisierung:
     * 1. Dashboard-Config (window.MRH_MEGAMENU_CONFIG) – aus Admin-Panel
     * 2. JS-Fallback (getCategoryConfig) – hardcoded Keywords als Backup
     *
     * v1.1.0 BUGFIX: Dashboard-Config hat kein useStaticOnly Feld.
     * Prüfe stattdessen nur ob columns mit Items vorhanden sind.
     *
     * @param {HTMLElement} subUl – UL mit Level-1 Unterkategorien aus CatNavi
     * @param {string} parentHref – URL der Hauptkategorie
     * @param {string} parentText – Name der Hauptkategorie
     * @returns {HTMLElement} Fertiges Dropdown-Panel
     */
    buildDropdown: function(subUl, parentHref, parentText) {
      var dropdown = document.createElement('div');
      dropdown.className = 'mrh-mega-dropdown';

      var content = document.createElement('div');
      content.className = 'mrh-mega-content';

      // === PRIORISIERUNG: Dashboard-Config vor JS-Fallback ===
      // v1.1.0 FIX: parentText als zweiten Parameter übergeben für Fallback-Match
      var dashConfig = this.getDashboardConfig(parentHref, parentText);

      // v1.1.0 FIX: Prüfe nur ob columns mit Items vorhanden sind (kein useStaticOnly nötig)
      if (dashConfig && dashConfig.columns && dashConfig.columns.length > 0) {
        // Prüfe ob mindestens eine Spalte Items hat
        var hasItems = false;
        for (var c = 0; c < dashConfig.columns.length; c++) {
          if (dashConfig.columns[c].items && dashConfig.columns[c].items.length > 0) {
            hasItems = true;
            break;
          }
        }
        if (hasItems) {
          // MODUS A: Dashboard-Config (Admin-Panel, dynamisch konfiguriert)
          this._buildFromDashboardConfig(dashConfig, parentHref, content);
        } else {
          // Spalten ohne Items → JS-Fallback
          this._buildFallback(subUl, parentHref, parentText, content);
        }
      } else {
        // Keine Dashboard-Config → JS-Fallback
        this._buildFallback(subUl, parentHref, parentText, content);
      }

      // Promo-Spalte hinzufügen
      this._appendPromoColumn(content);

      dropdown.appendChild(content);
      return dropdown;
    },

    /**
     * Fallback-Builder: Verwendet getCategoryConfig (hardcoded) oder CatNavi
     */
    _buildFallback: function(subUl, parentHref, parentText, content) {
      var config = this.getCategoryConfig(parentText);
      var maxPerCol = config.maxPerCol || 5;

      if (config.useStaticOnly && config.staticLinks) {
        this._buildFromStaticLinks(config, parentHref, content, maxPerCol);
      } else {
        var subItems = Array.from(subUl.querySelectorAll(':scope > li'));
        this._buildFromCatNavi(config, subItems, parentHref, content, maxPerCol);
      }
    },

    /**
     * MODUS A: Dashboard-Config – Spalten und Items aus Admin-Panel
     * URLs sind system-nah (index.php?cPath=...), SEO-Modul schreibt um
     *
     * v1.1.0 BUGFIX: item.label statt item.name verwenden
     * (mrh-megamenu-config.js.php gibt 'label' aus, nicht 'name')
     */
    _buildFromDashboardConfig: function(dashConfig, parentHref, content) {
      for (var i = 0; i < dashConfig.columns.length; i++) {
        var col = dashConfig.columns[i];
        if (!col.items || !col.items.length) continue;

        var colEl = document.createElement('div');
        colEl.className = 'mrh-mega-col';

        // Spalten-Titel
        var titleEl = document.createElement('div');
        titleEl.className = 'mrh-mega-col-title';
        titleEl.innerHTML = '<i class="fa-solid ' + (col.icon || 'fa-folder') + '"></i> ' + (col.title || 'Kategorie');
        colEl.appendChild(titleEl);

        // Links
        var ul = document.createElement('ul');
        for (var j = 0; j < col.items.length; j++) {
          var item = col.items[j];
          var li = document.createElement('li');
          var a = document.createElement('a');
          a.href = item.url || '#';
          // v1.1.0 FIX: 'label' statt 'name' verwenden
          a.textContent = item.label || item.name || '';
          li.appendChild(a);
          ul.appendChild(li);
        }
        colEl.appendChild(ul);

        // "Alle anzeigen" Link
        var allLink = document.createElement('a');
        allLink.href = parentHref;
        allLink.className = 'mrh-mega-all';
        allLink.innerHTML = 'Alle anzeigen <i class="fa-solid fa-arrow-right"></i>';
        colEl.appendChild(allLink);

        content.appendChild(colEl);
      }
    },

    /**
     * MODUS B1: Statische Links aus JS-Fallback (getCategoryConfig.staticLinks)
     */
    _buildFromStaticLinks: function(config, parentHref, content, maxPerCol) {
      var colIcons = config.icons || [];
      var colTitles = config.titles || [];

      for (var idx = 0; idx < config.staticLinks.length; idx++) {
        var colLinks = config.staticLinks[idx];
        if (!colLinks || !colLinks.length) continue;

        var col = document.createElement('div');
        col.className = 'mrh-mega-col';

        var title = document.createElement('div');
        title.className = 'mrh-mega-col-title';
        title.innerHTML = '<i class="fa-solid ' + (colIcons[idx] || 'fa-folder') + '"></i> ' + (colTitles[idx] || 'Kategorie ' + (idx + 1));
        col.appendChild(title);

        var ul = document.createElement('ul');
        var max = Math.min(colLinks.length, maxPerCol);
        for (var k = 0; k < max; k++) {
          var linkData = colLinks[k];
          var li = document.createElement('li');
          var a = document.createElement('a');
          a.href = linkData.href;
          a.textContent = linkData.text;
          li.appendChild(a);
          ul.appendChild(li);
        }
        col.appendChild(ul);

        var allLink = document.createElement('a');
        allLink.href = parentHref;
        allLink.className = 'mrh-mega-all';
        allLink.innerHTML = 'Alle anzeigen <i class="fa-solid fa-arrow-right"></i>';
        col.appendChild(allLink);

        content.appendChild(col);
      }
    },

    /**
     * MODUS B2: Dynamische Zuordnung aus CatNavi (Keyword-Matching)
     */
    _buildFromCatNavi: function(config, subItems, parentHref, content, maxPerCol) {
      var colIcons = config.icons || [];
      var colTitles = config.titles || [];
      var colKeywords = config.columns || [];
      var columns = this.assignToColumns(subItems, colKeywords, maxPerCol);

      for (var idx = 0; idx < columns.length; idx++) {
        var colItems = columns[idx];
        if (!colItems.length) continue;

        var col = document.createElement('div');
        col.className = 'mrh-mega-col';

        var title = document.createElement('div');
        title.className = 'mrh-mega-col-title';
        title.innerHTML = '<i class="fa-solid ' + (colIcons[idx] || 'fa-folder') + '"></i> ' + (colTitles[idx] || 'Kategorie ' + (idx + 1));
        col.appendChild(title);

        var ul = document.createElement('ul');
        var max = Math.min(colItems.length, maxPerCol);
        for (var k = 0; k < max; k++) {
          var a = colItems[k].querySelector('a');
          if (!a) continue;
          var li = document.createElement('li');
          var link = document.createElement('a');
          link.href = a.getAttribute('href') || '#';
          link.textContent = a.textContent.trim();
          li.appendChild(link);
          ul.appendChild(li);
        }
        col.appendChild(ul);

        var allLink = document.createElement('a');
        allLink.href = parentHref;
        allLink.className = 'mrh-mega-all';
        allLink.innerHTML = 'Alle anzeigen <i class="fa-solid fa-arrow-right"></i>';
        col.appendChild(allLink);

        content.appendChild(col);
      }
    },

    /**
     * Promo-Spalte aus data-Attributen des #mrhMegaPromoData Elements
     */
    _appendPromoColumn: function(content) {
      var promoData = document.querySelector('#mrhMegaPromoData');
      if (!promoData) return;

      var icon = promoData.dataset.icon || 'fa-percent';
      var titleText = promoData.dataset.title || 'Aktion';
      var brand = promoData.dataset.brand || '';
      var text = promoData.dataset.text || '';
      var link = promoData.dataset.link || '/angebote/';
      var button = promoData.dataset.button || 'Jetzt sparen';

      var promo = document.createElement('div');
      promo.className = 'mrh-mega-promo';
      promo.innerHTML =
        '<div class="mrh-mega-promo-inner">' +
          '<div class="mrh-mega-promo-title">' +
            '<i class="fa-solid ' + icon + '"></i> ' + titleText +
          '</div>' +
          '<div class="mrh-mega-promo-brand">' + brand + '</div>' +
          '<div class="mrh-mega-promo-text">' + text + '</div>' +
          '<a href="' + link + '" class="mrh-mega-promo-btn">' +
            button +
          '</a>' +
        '</div>';
      content.appendChild(promo);
    },

    /**
     * Verteilt Sub-Items intelligent basierend auf Keyword-Matching in Spalten.
     * Jedes Item wird der Spalte zugeordnet, deren Keywords am besten zum
     * Kategorienamen passen. Nicht zugeordnete Items kommen in die letzte Spalte.
     */
    assignToColumns: function(items, colKeywords, maxPerCol) {
      var numCols = colKeywords.length || 3;
      var cols = [];
      for (var i = 0; i < numCols; i++) cols.push([]);

      // Wenn keine Keywords definiert: gleichmäßig verteilen (Fallback)
      if (!colKeywords || colKeywords.length === 0 || (colKeywords.length === 1 && colKeywords[0].length === 0)) {
        items.forEach(function(item, idx) {
          cols[idx % numCols].push(item);
        });
        return cols.filter(function(c) { return c.length > 0; });
      }

      var assigned = new Set();

      // Schritt 1: Items den Spalten zuordnen basierend auf Keywords
      items.forEach(function(item) {
        var a = item.querySelector('a');
        if (!a) return;
        var text = a.textContent.trim().toLowerCase();

        for (var colIdx = 0; colIdx < colKeywords.length; colIdx++) {
          var keywords = colKeywords[colIdx];
          for (var k = 0; k < keywords.length; k++) {
            if (text.indexOf(keywords[k].toLowerCase()) > -1) {
              if (cols[colIdx].length < maxPerCol) {
                cols[colIdx].push(item);
                assigned.add(item);
              }
              return; // Item ist zugeordnet, nächstes Item
            }
          }
        }
      });

      // Schritt 2: Nicht zugeordnete Items in die Spalte mit wenigsten Einträgen
      items.forEach(function(item) {
        if (assigned.has(item)) return;
        // Finde die Spalte mit den wenigsten Einträgen (die noch Platz hat)
        var minIdx = -1;
        var minLen = maxPerCol + 1;
        for (var i = 0; i < cols.length; i++) {
          if (cols[i].length < maxPerCol && cols[i].length < minLen) {
            minLen = cols[i].length;
            minIdx = i;
          }
        }
        if (minIdx > -1) {
          cols[minIdx].push(item);
        }
      });

      return cols.filter(function(c) { return c.length > 0; });
    },

    /**
     * Event-Listener für Hover (Desktop) und Click (Touch)
     */
    bindEvents: function() {
      var self = this;
      var megaItems = MRH.Utils.qsa('.mrh-nav-item[data-mega]', this.bar);

      megaItems.forEach(function(item) {
        if (self.isTouch) {
          // Touch: Click öffnet/schließt Dropdown
          item.addEventListener('click', function(e) {
            if (self.activeItem === item) {
              // Zweiter Klick: Navigiere zum Link
              self.closeAll();
              return;
            }
            e.preventDefault();
            self.closeAll();
            self.open(item);
          });
        } else {
          // Desktop: Hover mit Delay
          item.addEventListener('mouseenter', function() {
            clearTimeout(self.closeTimer);
            self.openTimer = setTimeout(function() {
              self.closeAll();
              self.open(item);
            }, self.hoverDelay);
          });

          item.addEventListener('mouseleave', function() {
            clearTimeout(self.openTimer);
            self.closeTimer = setTimeout(function() {
              self.closeAll();
            }, self.closeDelay);
          });
        }
      });

      // Dropdown selbst: Hover hält es offen
      var dropdowns = MRH.Utils.qsa('.mrh-mega-dropdown', this.nav);
      dropdowns.forEach(function(dd) {
        dd.addEventListener('mouseenter', function() {
          clearTimeout(self.closeTimer);
        });
        dd.addEventListener('mouseleave', function() {
          self.closeTimer = setTimeout(function() {
            self.closeAll();
          }, self.closeDelay);
        });
      });

      // Klick außerhalb schließt alles
      document.addEventListener('click', function(e) {
        if (!self.nav.contains(e.target)) {
          self.closeAll();
        }
      });

      // ESC schließt Dropdown
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') self.closeAll();
      });

      // Keyboard: Arrow-Navigation
      this.bindKeyboard(megaItems);
    },

    /**
     * Keyboard-Navigation (Tab, Enter, Arrow Keys)
     */
    bindKeyboard: function(megaItems) {
      var self = this;
      megaItems.forEach(function(item) {
        item.addEventListener('keydown', function(e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            if (self.activeItem === item) {
              self.closeAll();
            } else {
              self.closeAll();
              self.open(item);
              // Fokus auf ersten Link im Dropdown
              var firstLink = item._megaDropdown ? item._megaDropdown.querySelector('a') : null;
              if (firstLink) firstLink.focus();
            }
          }
          if (e.key === 'ArrowDown' && self.activeItem === item) {
            e.preventDefault();
            var firstLink = item._megaDropdown ? item._megaDropdown.querySelector('a') : null;
            if (firstLink) firstLink.focus();
          }
        });
      });
    },

    /**
     * Dropdown öffnen
     */
    open: function(item) {
      if (!item._megaDropdown) return;
      item.classList.add('mrh-mega-open');
      item._megaDropdown.classList.add('open');
      item.setAttribute('aria-expanded', 'true');
      this.activeItem = item;
      this.activeDropdown = item._megaDropdown;
    },

    /**
     * Alle Dropdowns schließen
     */
    closeAll: function() {
      clearTimeout(this.openTimer);
      clearTimeout(this.closeTimer);
      var openItems = MRH.Utils.qsa('.mrh-mega-open', this.bar);
      openItems.forEach(function(item) {
        item.classList.remove('mrh-mega-open');
        item.setAttribute('aria-expanded', 'false');
      });
      var openDropdowns = MRH.Utils.qsa('.mrh-mega-dropdown.open', this.nav);
      openDropdowns.forEach(function(dd) {
        dd.classList.remove('open');
      });
      this.activeItem = null;
      this.activeDropdown = null;
    },

    /**
     * Aktiven Nav-Item basierend auf aktuellem Pfad markieren
     */
    markActive: function() {
      var path = window.location.pathname;
      var items = MRH.Utils.qsa('.mrh-nav-item', this.bar);
      items.forEach(function(item) {
        var href = item.getAttribute('href');
        if (!href || href === '#') return;
        if (href === '/' && path === '/') {
          item.classList.add('active');
        } else if (href !== '/' && path.indexOf(href) === 0) {
          item.classList.add('active');
        }
      });
    }
  };

  /* ----------------------------------------------------------
     INIT: Alles starten wenn DOM bereit
     ---------------------------------------------------------- */
  function mrhInit() {
    MRH.Topbar.init();
    MRH.ShippingBar.init();
    MRH.StickyHeader.init();
    MRH.BackToTop.init();
    MRH.LazyLoad.init();
    MRH.A11y.init();
    MRH.Performance.init();
    MRH.MegaMenu.init();

    // Suchleisten-Placeholder anpassen (Core liefert nur "Suchen")
    var searchInput = document.querySelector('#search input[type="text"], #search input[name="keywords"]');
    if (searchInput) {
      var placeholders = {
        'german': 'Cannabis Samen suchen...',
        'english': 'Search cannabis seeds...',
        'french': 'Rechercher des graines...',
        'dutch': 'Cannabis zaden zoeken...'
      };
      var lang = document.documentElement.lang || 'de';
      // Sprache aus HTML-lang oder aus Body-Klasse ermitteln
      if (lang === 'de' || lang === 'de-AT') searchInput.placeholder = placeholders['german'];
      else if (lang === 'en') searchInput.placeholder = placeholders['english'];
      else if (lang === 'fr') searchInput.placeholder = placeholders['french'];
      else if (lang === 'nl') searchInput.placeholder = placeholders['dutch'];
      else searchInput.placeholder = placeholders['german'];
    }

    // ============================================================
    // Bottom Bar – Mobile Navigation
    // ============================================================
    var bottomBar = document.getElementById('mrhBottomBar');
    if (bottomBar) {

      // -- Suche: Overlay oeffnen/schliessen --
      var bbSearch = document.getElementById('mrhBottomSearch');
      var searchOverlay = document.getElementById('mrhSearchOverlay');
      var searchOverlayClose = document.getElementById('mrhSearchOverlayClose');
      var searchOverlayBg = document.getElementById('mrhSearchOverlayBg');

      if (bbSearch && searchOverlay) {
        bbSearch.addEventListener('click', function(e) {
          e.preventDefault();
          searchOverlay.classList.add('open');
          document.body.classList.add('mrh-no-scroll');
          var overlayInput = searchOverlay.querySelector('input[type="text"]');
          if (overlayInput) {
            setTimeout(function() { overlayInput.focus(); }, 200);
          }
        });

        var closeOverlay = function() {
          searchOverlay.classList.remove('open');
          document.body.classList.remove('mrh-no-scroll');
        };

        if (searchOverlayClose) searchOverlayClose.addEventListener('click', closeOverlay);
        if (searchOverlayBg) searchOverlayBg.addEventListener('click', closeOverlay);

        // ESC-Taste schliesst Overlay
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && searchOverlay.classList.contains('open')) {
            closeOverlay();
          }
        });
      }

      // -- Warenkorb-Badge: Live-Sync mit Header --
      var bbCartBadge = bottomBar.querySelector('.mrh-bb-cart-count');
      if (bbCartBadge) {
        var syncCartBadge = function() {
          var headerBadge = document.querySelector('#iconMenu .cart .cart_content');
          if (headerBadge && headerBadge.textContent.trim() !== '' && headerBadge.textContent.trim() !== '0') {
            bbCartBadge.textContent = headerBadge.textContent.trim();
            bbCartBadge.style.display = 'block';
          } else {
            bbCartBadge.style.display = 'none';
          }
        };
        syncCartBadge();
        var headerCartEl = document.querySelector('#iconMenu .cart .cart_content');
        if (headerCartEl) {
          new MutationObserver(syncCartBadge).observe(headerCartEl, { childList: true, characterData: true, subtree: true });
        }
        document.addEventListener('cartUpdated', syncCartBadge);
      }

      // -- Merkzettel-Badge: Live-Sync mit Header --
      var bbWishBadge = bottomBar.querySelector('.mrh-bb-wish-count');
      if (bbWishBadge) {
        var syncWishBadge = function() {
          var headerWish = document.querySelector('#iconMenu .wishlist .cart_content');
          if (headerWish && headerWish.textContent.trim() !== '' && headerWish.textContent.trim() !== '0') {
            bbWishBadge.textContent = headerWish.textContent.trim();
            bbWishBadge.style.display = 'block';
          } else {
            bbWishBadge.style.display = 'none';
          }
        };
        syncWishBadge();
        var headerWishEl = document.querySelector('#iconMenu .wishlist .cart_content');
        if (headerWishEl) {
          new MutationObserver(syncWishBadge).observe(headerWishEl, { childList: true, characterData: true, subtree: true });
        }
        document.addEventListener('wishlistUpdated', syncWishBadge);
      }

      // -- Active State: Aktuellen Pfad markieren --
      var currentPath = window.location.pathname;
      var bbLinks = bottomBar.querySelectorAll('a');
      bbLinks.forEach(function(link) {
        var href = link.getAttribute('href');
        if (href === '/' && currentPath === '/') {
          link.classList.add('active');
        } else if (href && href !== '/' && href !== '#' && currentPath.indexOf(href) === 0) {
          link.classList.add('active');
        }
      });
    }

    // Debug-Info in Konsole (nur Entwicklung)
    if (window.location.hostname === 'localhost' || window.location.search.indexOf('debug=1') > -1) {
      console.log('[MRH Core] v1.1.0 initialized', {
        modules: Object.keys(MRH).filter(function(k) { return typeof MRH[k] === 'object' && MRH[k].init; }),
        shippingThreshold: MRH.ShippingBar.threshold,
        dashboardConfig: window.MRH_MEGAMENU_CONFIG ? 'loaded (' + window.MRH_MEGAMENU_CONFIG.length + ' entries)' : 'not available'
      });
    }
  }

  // DOM Ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mrhInit);
  } else {
    mrhInit();
  }

})();
</script>
