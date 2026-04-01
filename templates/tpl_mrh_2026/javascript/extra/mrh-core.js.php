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
   v1.3.0: Promo-Daten (HTML/Banner/Special/New) pro Kategorie
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
            // Promo-Daten aufbereiten
            $_mrh_promo_out = null;
            if (isset($_mrh_entry['promo']) && is_array($_mrh_entry['promo'])) {
                $_mrh_p = $_mrh_entry['promo'];
                $_mrh_promo_out = array('type' => $_mrh_p['type']);
                if ($_mrh_p['type'] === 'html') {
                    $_mrh_promo_out['html'] = isset($_mrh_p['html_content']) ? $_mrh_p['html_content'] : '';
                } elseif ($_mrh_p['type'] === 'banner' && isset($_mrh_p['banner'])) {
                    $_mrh_promo_out['banner'] = array(
                        'title' => isset($_mrh_p['banner']['title']) ? $_mrh_p['banner']['title'] : '',
                        'image' => isset($_mrh_p['banner']['image']) ? $_mrh_p['banner']['image'] : '',
                        'url'   => isset($_mrh_p['banner']['url']) ? $_mrh_p['banner']['url'] : '',
                        'html_text' => isset($_mrh_p['banner']['html_text']) ? $_mrh_p['banner']['html_text'] : '',
                    );
                } elseif ($_mrh_p['type'] === 'special') {
                    // Dynamisch: Sonderangebote laden
                    $_mrh_max = isset($_mrh_p['max_items']) ? (int)$_mrh_p['max_items'] : 3;
                    $_mrh_parent_id = (int)$_mrh_entry['parent_id'];
                    $_mrh_specials = array();
                    if (class_exists('MrhMegaMenuManager')) {
                        $_mrh_mgr_tmp = new MrhMegaMenuManager();
                        $_mrh_specials = $_mrh_mgr_tmp->getSpecialProducts($_mrh_parent_id, $_mrh_max);
                    }
                    $_mrh_promo_out['products'] = $_mrh_specials;
                } elseif ($_mrh_p['type'] === 'new') {
                    // Dynamisch: Neue Produkte laden
                    $_mrh_max = isset($_mrh_p['max_items']) ? (int)$_mrh_p['max_items'] : 3;
                    $_mrh_parent_id = (int)$_mrh_entry['parent_id'];
                    $_mrh_new_prods = array();
                    if (class_exists('MrhMegaMenuManager')) {
                        $_mrh_mgr_tmp = new MrhMegaMenuManager();
                        $_mrh_new_prods = $_mrh_mgr_tmp->getNewProducts($_mrh_parent_id, $_mrh_max);
                    }
                    $_mrh_promo_out['products'] = $_mrh_new_prods;
                }
            }

            $_mrh_cat_out = array(
                'parent_id'   => (int)$_mrh_entry['parent_id'],
                'parent_name' => $_mrh_pname,
                'columns'     => $_mrh_cols_out,
            );
            if ($_mrh_promo_out) {
                $_mrh_cat_out['promo'] = $_mrh_promo_out;
            }
            $_mrh_output[] = $_mrh_cat_out;
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
/* ----------------------------------------------------------
     MRH 2026 Core – v1.5.0
     Vanilla JS – kein jQuery!
     v1.1.0: Bugfix getDashboardConfig + buildDropdown + _buildFromDashboardConfig
     v1.4.0: FA6 Pro Icon-Normalisierung (Brands vs Solid Auto-Detect)
     v1.5.0: Upgrade auf Font Awesome 7 Pro (7.2.0) – 587 Brands-Icons
     v1.6.0: Vanilla JS Offcanvas Mobile-Menü (ersetzt jQuery mmenu)
     ============================================================ */
(function() {
  'use strict';

  /* ----------------------------------------------------------
     NAMESPACE: Alle MRH-Funktionen unter window.MRH
     ---------------------------------------------------------- */
  window.MRH = window.MRH || {};

  /* ----------------------------------------------------------
     FA7 BRANDS ICON SET: Automatische Erkennung
     Icons die NUR als fa-brands funktionieren (nicht fa-solid)
     Generiert aus Font Awesome 7 Pro (7.2.0) all.css Brands-Block – 587 Icons
     ---------------------------------------------------------- */
  var _FA6_BRANDS = new Set(['11ty','42-group','500px','accessible-icon','accusoft','adn','adversal','affiliatetheme','airbnb','algolia','alipay','amazon','amazon-pay','amilia','android','angellist','angrycreative','angular','app-store','app-store-ios','apper','apple','apple-pay','arch-linux','artstation','asymmetrik','atlassian','audible','autoprefixer','avianex','aviato','aws','bandcamp','battle-net','behance','behance-square','bilibili','bimobject','bitbucket','bitcoin','bity','black-tie','blackberry','blogger','blogger-b','bluesky','bluetooth','bluetooth-b','bootstrap','bots','brave','brave-reverse','btc','buffer','buromobelexperte','buy-n-large','buysellads','canadian-maple-leaf','cc-amazon-pay','cc-amex','cc-apple-pay','cc-diners-club','cc-discover','cc-jcb','cc-mastercard','cc-paypal','cc-stripe','cc-visa','centercode','centos','chrome','chromecast','cloudflare','cloudscale','cloudsmith','cloudversify','cmplid','codepen','codiepie','confluence','connectdevelop','contao','cotton-bureau','cpanel','creative-commons','creative-commons-by','creative-commons-nc','creative-commons-nc-eu','creative-commons-nc-jp','creative-commons-nd','creative-commons-pd','creative-commons-pd-alt','creative-commons-remix','creative-commons-sa','creative-commons-sampling','creative-commons-sampling-plus','creative-commons-share','creative-commons-zero','critical-role','css3','css3-alt','cuttlefish','d-and-d','d-and-d-beyond','dailymotion','dart-lang','dashcube','debian','deezer','delicious','deploydog','deskpro','dev','deviantart','dhl','diaspora','digg','digital-ocean','discord','discourse','dochub','docker','draft2digital','dribbble','dribbble-square','dropbox','drupal','dyalog','earlybirds','ebay','edge','edge-legacy','elementor','ello','ember','empire','envira','erlang','ethereum','etsy','evernote','expeditedssl','facebook','facebook-f','facebook-messenger','facebook-square','fantasy-flight-games','fedex','fedora','figma','firefox','firefox-browser','first-order','first-order-alt','firstdraft','flickr','flipboard','flutter','fly','font-awesome','font-awesome-alt','font-awesome-flag','font-awesome-logo-full','fonticons','fonticons-fi','forgejo','fort-awesome','fort-awesome-alt','forumbee','foursquare','free-code-camp','freebsd','fulcrum','galactic-republic','galactic-senate','get-pocket','gg','gg-circle','git','git-alt','git-square','gitee','github','github-alt','github-square','gitkraken','gitlab','gitlab-square','gitter','glide','glide-g','globaleaks','gofore','golang','goodreads','goodreads-g','google','google-drive','google-pay','google-play','google-plus','google-plus-g','google-plus-square','google-scholar','google-wallet','gratipay','grav','gripfire','grunt','guilded','gulp','hacker-news','hacker-news-square','hackerrank','hashnode','hips','hire-a-helper','hive','hooli','hornbill','hotjar','houzz','html5','hubspot','hugging-face','ideal','imdb','innosoft','instagram','instagram-square','instalod','intercom','internet-explorer','invision','ioxhost','itch-io','itunes','itunes-note','java','jedi-order','jenkins','jira','joget','joomla','js','js-square','jsfiddle','julia','jxl','kaggle','kakao-talk','keybase','keycdn','kickstarter','kickstarter-k','ko-fi','korvue','kubernetes','laravel','lastfm','lastfm-square','leanpub','leetcode','less','letterboxd','line','linkedin','linkedin-in','linktree','linode','linux','lumon','lumon-drop','lyft','magento','mailchimp','mandalorian','markdown','mastodon','maxcdn','mdb','medapps','medium','medium-m','medrt','meetup','megaport','mendeley','meta','microblog','microsoft','mintbit','mix','mixcloud','mixer','mizuni','modx','monero','napster','neos','nfc-directional','nfc-symbol','nimblr','node','node-js','notion','npm','ns8','nutritionix','obsidian','octopus-deploy','odnoklassniki','odnoklassniki-square','odysee','old-republic','openai','opencart','openid','openstreetmap','opensuse','opera','optin-monster','orcid','osi','padlet','page4','pagelines','palfed','pandora','patreon','paypal','perbyte','periscope','phabricator','phoenix-framework','phoenix-squadron','php','pied-piper','pied-piper-alt','pied-piper-hat','pied-piper-pp','pied-piper-square','pinterest','pinterest-p','pinterest-square','pix','pixelfed','pixiv','playstation','postgresql','product-hunt','pushed','python','qq','quinscape','quora','r-project','raspberry-pi','ravelry','react','reacteurope','readme','rebel','red-river','reddit','reddit-alien','reddit-square','redhat','rendact','renren','replyd','researchgate','resolving','rev','rocketchat','rockrms','rust','safari','salesforce','sass','scaleway','schlix','screenpal','scribd','searchengin','sellcast','sellsy','servicestack','shirtsinbulk','shoelace','shopify','shopware','signal-messenger','simplybuilt','sistrix','sith','sitrox','sketch','skyatlas','skype','slack','slack-hash','slideshare','snapchat','snapchat-ghost','snapchat-square','solana','soundcloud','sourcetree','space-awesome','speakap','speaker-deck','spotify','square-behance','square-bluesky','square-deskpro','square-dribbble','square-facebook','square-figma','square-font-awesome','square-font-awesome-stroke','square-git','square-github','square-gitlab','square-google-plus','square-hacker-news','square-instagram','square-js','square-kickstarter','square-lastfm','square-letterboxd','square-linkedin','square-odnoklassniki','square-pied-piper','square-pinterest','square-reddit','square-snapchat','square-steam','square-threads','square-tumblr','square-twitter','square-upwork','square-viadeo','square-vimeo','square-web-awesome','square-web-awesome-stroke','square-whatsapp','square-x-twitter','square-xing','square-youtube','squarespace','stack-exchange','stack-overflow','stackpath','staylinked','steam','steam-square','steam-symbol','sticker-mule','strava','stripe','stripe-s','stubber','studiovinari','stumbleupon','stumbleupon-circle','superpowers','supple','supportnow','suse','svelte','swift','symfony','symfonycasts','tailwind-css','teamspeak','telegram','telegram-plane','tencent-weibo','tex','the-red-yeti','themeco','themeisle','think-peaks','threads','threema','tidal','tiktok','tor-browser','trade-federation','trello','tumblr','tumblr-square','twitch','twitter','twitter-square','typescript','typo3','uber','ubuntu','uikit','ultralytics','ultralytics-hub','ultralytics-yolo','umbraco','uncharted','uniregistry','unison','unity','unreal-engine','unsplash','untappd','ups','upwork','usb','usps','ussunnah','vaadin','venmo','venmo-v','viacoin','viadeo','viadeo-square','viber','vim','vimeo','vimeo-square','vimeo-v','vine','vk','vnv','vsco','vuejs','w3c','watchman-monitoring','waze','web-awesome','webflow','weebly','weibo','weixin','whatsapp','whatsapp-square','whmcs','wikipedia-w','windows','wirsindhandwerk','wix','wizards-of-the-coast','wodu','wolf-pack-battalion','wordpress','wordpress-simple','wpbeginner','wpexplorer','wpforms','wpressr','wsh','x-twitter','xbox','xing','xing-square','xmpp','y-combinator','yahoo','yammer','yandex','yandex-international','yarn','yelp','yoast','youtube','youtube-square','zhihu','zoom','zulip']);

  /**
   * FA6/FA7 Icon-Klasse normalisieren
   * Erkennt automatisch ob ein Icon ein Brands-Icon ist
   * und setzt den korrekten Prefix (fa-brands statt fa-solid)
   *
   * Eingabe-Formate:
   *   'fa-pagelines'              → 'fa-brands fa-pagelines'
   *   'fa-solid fa-pagelines'     → 'fa-brands fa-pagelines'  (korrigiert!)
   *   'fa-solid fa-seedling'      → 'fa-solid fa-seedling'    (bleibt)
   *   'fas fa-check-circle'       → 'fa-solid fa-circle-check' (FA4→FA6)
   *   'fa-seedling'               → 'fa-solid fa-seedling'
   */
  function _normalizeFA6(iconClass) {
    if (!iconClass || typeof iconClass !== 'string') return 'fa-solid fa-folder';
    iconClass = iconClass.trim();

    // FA4 Shorthand → FA6 (fas/far/fal/fat/fab)
    var fa4map = {'fas':'fa-solid','far':'fa-regular','fal':'fa-light','fat':'fa-thin','fab':'fa-brands'};
    var parts = iconClass.split(/\s+/);
    if (parts.length >= 2 && fa4map[parts[0]]) {
      parts[0] = fa4map[parts[0]];
      iconClass = parts.join(' ');
    }

    // FA4 icon name aliases → FA6 names
    var fa4aliases = {
      'fa-check-circle': 'fa-circle-check',
      'fa-times-circle': 'fa-circle-xmark',
      'fa-times': 'fa-xmark',
      'fa-window-close': 'fa-rectangle-xmark',
      'fa-arrow-circle-right': 'fa-circle-arrow-right',
      'fa-arrow-circle-left': 'fa-circle-arrow-left'
    };
    for (var old in fa4aliases) {
      if (iconClass.indexOf(old) > -1) {
        iconClass = iconClass.replace(old, fa4aliases[old]);
      }
    }

    // Bereits vollständig qualifiziert (fa-solid/fa-regular/fa-light/fa-thin/fa-brands)?
    var hasPrefix = /^fa-(solid|regular|light|thin|brands)\s/.test(iconClass);

    if (hasPrefix) {
      // Extrahiere den Icon-Namen (zweiter Teil)
      var p = iconClass.split(/\s+/);
      var prefix = p[0];
      var name = p[1] || '';
      var bare = name.replace(/^fa-/, '');

      // Korrektur: Wenn als fa-solid markiert aber eigentlich ein Brands-Icon
      if (prefix !== 'fa-brands' && _FA6_BRANDS.has(bare)) {
        return 'fa-brands ' + name;
      }
      return iconClass;
    }

    // Nur Icon-Name ohne Prefix (z.B. 'fa-pagelines' oder 'fa-seedling')
    var bareName = iconClass.replace(/^fa-/, '');
    if (_FA6_BRANDS.has(bareName)) {
      return 'fa-brands fa-' + bareName;
    }
    // Default: fa-solid
    return 'fa-solid ' + (iconClass.indexOf('fa-') === 0 ? iconClass : 'fa-' + iconClass);
  }

  // Global verfügbar machen
  window.MRH_normalizeFA6 = _normalizeFA6;

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
        this.text.innerHTML = '<i class="' + _normalizeFA6('fas fa-check-circle') + '"></i> Gratis Versand!';
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
      btn.innerHTML = '<i class="' + _normalizeFA6('fa-solid fa-chevron-up') + '"></i>';
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
        'samen': 'fa-solid fa-seedling',
        'seed': 'fa-solid fa-seedling',
        'cannabis': 'fa-solid fa-cannabis',
        'cannabispflanz': 'fa-solid fa-cannabis',
        'grow': 'fa-solid fa-sun',
        'head': 'fa-solid fa-bong',
        'default': 'fa-solid fa-seedling'
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
        navItem.innerHTML = '<span class="' + _normalizeFA6(iconClass) + '"></span> ' +
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

      // Original CatNavi nur auf Desktop verstecken
      // Auf Mobile bleibt sie sichtbar fuer das RevPlus Slide-In-Menue
      if (window.innerWidth > 991) {
        catWrap.style.display = 'none';
      } else {
        // Auf Mobile: CatNavi sichtbar lassen, aber Mega-Nav verstecken
        catWrap.style.display = '';
      }
      // Bei Resize reagieren (Desktop <-> Mobile Wechsel)
      var _catWrapRef = catWrap;
      window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
          _catWrapRef.style.display = 'none';
        } else {
          _catWrapRef.style.display = '';
        }
      });
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
          icons:  ['fa-solid fa-seedling', 'fa-solid fa-fire', 'fa-solid fa-seedling'],
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
          icons:  ['fa-solid fa-box-open', 'fa-solid fa-hand-holding-droplet', 'fa-solid fa-screwdriver-wrench'],
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
          icons:  ['fa-solid fa-cloud', 'fa-solid fa-wrench'],
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
          icons:  ['fa-solid fa-cannabis'],
          maxPerCol: 5,
          useStaticOnly: false,
          columns: [[]]
        };
      }
      // Fallback
      return {
        titles: ['Sortiment', 'Highlights', 'Mehr entdecken'],
        icons:  ['fa-solid fa-layer-group', 'fa-solid fa-star', 'fa-solid fa-compass'],
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

      // Promo-Spalte hinzufügen (Dashboard-Config hat Vorrang)
      this._appendPromoColumn(content, dashConfig);

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
        titleEl.innerHTML = '<span class="' + _normalizeFA6(col.icon || 'fa-folder') + '"></span> ' + (col.title || 'Kategorie');
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
        var _icCls = _normalizeFA6(colIcons[idx] || 'fa-folder');
        title.innerHTML = '<span class="' + _icCls + '"></span> ' + (colTitles[idx] || 'Kategorie ' + (idx + 1));
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
        var _icCls = _normalizeFA6(colIcons[idx] || 'fa-folder');
        title.innerHTML = '<span class="' + _icCls + '"></span> ' + (colTitles[idx] || 'Kategorie ' + (idx + 1));
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
     * Promo-Spalte: Dashboard-Config (v1.3.0) oder Fallback aus #mrhMegaPromoData
     * Modi: html, banner, special, new, oder Fallback (data-Attribute)
     */
    _appendPromoColumn: function(content, dashConfig) {
      var promo = document.createElement('div');
      promo.className = 'mrh-mega-promo';

      // v1.3.0: Dashboard-Promo-Config prüfen
      if (dashConfig && dashConfig.promo && dashConfig.promo.type && dashConfig.promo.type !== 'none') {
        var p = dashConfig.promo;

        if (p.type === 'html' && p.html) {
          // HTML-Content direkt rendern
          promo.innerHTML = '<div class="mrh-mega-promo-inner">' + p.html + '</div>';

        } else if (p.type === 'banner' && p.banner) {
          // Banner-Bild oder HTML
          var inner = '<div class="mrh-mega-promo-inner">';
          if (p.banner.html_text) {
            inner += p.banner.html_text;
          } else if (p.banner.image) {
            var imgUrl = '/' + p.banner.image;
            inner += '<a href="' + (p.banner.url || '#') + '">';
            inner += '<img src="' + imgUrl + '" alt="' + (p.banner.title || '') + '" style="max-width:100%;border-radius:8px;">';
            inner += '</a>';
          }
          inner += '</div>';
          promo.innerHTML = inner;

        } else if (p.type === 'special' && p.products && p.products.length) {
          // Sonderangebote mit Rabatt
          var inner = '<div class="mrh-mega-promo-inner">';
          inner += '<div class="mrh-mega-promo-title"><i class="fa-solid fa-percent"></i> Angebote</div>';
          for (var i = 0; i < p.products.length; i++) {
            var prod = p.products[i];
            inner += '<div class="mrh-promo-product">';
            if (prod.image) {
              inner += '<img src="/images/product_images/thumbnail_images/' + prod.image + '" alt="" class="mrh-promo-product-img">';
            }
            inner += '<div class="mrh-promo-product-info">';
            inner += '<span class="mrh-promo-product-name">' + prod.name + '</span>';
            if (prod.discount) {
              inner += '<span class="mrh-promo-discount">-' + prod.discount + '%</span>';
            }
            inner += '</div></div>';
          }
          inner += '<a href="/angebote/" class="mrh-mega-promo-btn">Alle Angebote</a>';
          inner += '</div>';
          promo.innerHTML = inner;

        } else if (p.type === 'new' && p.products && p.products.length) {
          // Neue Artikel
          var inner = '<div class="mrh-mega-promo-inner">';
          inner += '<div class="mrh-mega-promo-title"><i class="fa-solid fa-star"></i> Neu eingetroffen</div>';
          for (var i = 0; i < p.products.length; i++) {
            var prod = p.products[i];
            inner += '<div class="mrh-promo-product">';
            if (prod.image) {
              inner += '<img src="/images/product_images/thumbnail_images/' + prod.image + '" alt="" class="mrh-promo-product-img">';
            }
            inner += '<div class="mrh-promo-product-info">';
            inner += '<span class="mrh-promo-product-name">' + prod.name + '</span>';
            inner += '<span class="mrh-promo-new-badge">NEU</span>';
            inner += '</div></div>';
          }
          inner += '<a href="/neue-artikel/" class="mrh-mega-promo-btn">Alle neuen Artikel</a>';
          inner += '</div>';
          promo.innerHTML = inner;

        } else {
          // Promo-Typ gesetzt aber keine Daten → kein Promo anzeigen
          return;
        }

        content.appendChild(promo);
        return;
      }

      // Fallback: data-Attribute aus #mrhMegaPromoData (alte Methode)
      var promoData = document.querySelector('#mrhMegaPromoData');
      if (!promoData) return;

      var icon = _normalizeFA6(promoData.dataset.icon || 'fa-percent');
      var titleText = promoData.dataset.title || 'Aktion';
      var brand = promoData.dataset.brand || '';
      var text = promoData.dataset.text || '';
      var link = promoData.dataset.link || '/angebote/';
      var button = promoData.dataset.button || 'Jetzt sparen';

      promo.innerHTML =
        '<div class="mrh-mega-promo-inner">' +
          '<div class="mrh-mega-promo-title">' +
            '<span class="' + icon + '"></span> ' + titleText +
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
     08 MOBILE MENU: Vanilla JS Offcanvas (ersetzt jQuery mmenu)
     Slide-In von links, aufklappbare Unterkategorien, Touch-freundlich
     ---------------------------------------------------------- */
  MRH.MobileMenu = {
    isOpen: false,
    panel: null,
    overlay: null,
    toggleBtn: null,

    init: function() {
      // Nur auf Mobile initialisieren (oder wenn #mobiles_menu existiert)
      var mobileMenu = document.getElementById('mobiles_menu');
      var toggleBtn = document.getElementById('toggle_mobilemenu');
      if (!mobileMenu || !toggleBtn) return;

      this.toggleBtn = toggleBtn;

      // Offcanvas Panel erstellen
      this._buildPanel(mobileMenu);
      this._bindEvents();
    },

    _buildPanel: function(sourceNav) {
      var self = this;

      // Overlay (Backdrop)
      this.overlay = document.createElement('div');
      this.overlay.className = 'mrh-mobile-overlay';
      document.body.appendChild(this.overlay);

      // Offcanvas Panel
      this.panel = document.createElement('div');
      this.panel.className = 'mrh-mobile-panel';
      this.panel.setAttribute('aria-label', 'Mobile Navigation');
      this.panel.setAttribute('role', 'dialog');

      // Panel Header
      var header = document.createElement('div');
      header.className = 'mrh-mobile-header';
      header.innerHTML = '<span class="mrh-mobile-title">Menu</span>' +
                          '<button class="mrh-mobile-close" aria-label="Men\u00fc schlie\u00dfen">' +
                          '<i class="fa-solid fa-xmark"></i></button>';
      this.panel.appendChild(header);

      // Panel Body (scrollbar)
      var body = document.createElement('div');
      body.className = 'mrh-mobile-body';

      // CatNavi Elemente auslesen und als Offcanvas-Liste aufbauen
      var catItems = sourceNav.querySelectorAll(':scope > ul.CatNavi > li.level1');
      if (catItems.length === 0) {
        // Fallback: Alle li.level1 suchen
        catItems = sourceNav.querySelectorAll('li.level1');
      }

      var navList = document.createElement('ul');
      navList.className = 'mrh-mobile-nav';

      catItems.forEach(function(li) {
        var link = li.querySelector(':scope > a');
        if (!link) return;
        var text = link.textContent.replace(/[\n\r]/g, '').trim();
        var href = link.getAttribute('href') || '#';
        var hasSubmenu = li.classList.contains('hassubmenu');
        var subUl = li.querySelector(':scope > ul');

        var navItem = document.createElement('li');
        navItem.className = 'mrh-mobile-item' + (hasSubmenu ? ' has-children' : '');

        var navLink = document.createElement('a');
        navLink.href = href;
        navLink.className = 'mrh-mobile-link';
        navLink.textContent = text;

        if (hasSubmenu && subUl) {
          // Toggle-Button für Unterkategorien
          var toggleSub = document.createElement('button');
          toggleSub.className = 'mrh-mobile-toggle';
          toggleSub.setAttribute('aria-label', 'Unterkategorien anzeigen');
          toggleSub.innerHTML = '<i class="fa-solid fa-chevron-down"></i>';

          var linkRow = document.createElement('div');
          linkRow.className = 'mrh-mobile-link-row';
          linkRow.appendChild(navLink);
          linkRow.appendChild(toggleSub);
          navItem.appendChild(linkRow);

          // Submenu aufbauen
          var subMenu = document.createElement('ul');
          subMenu.className = 'mrh-mobile-sub';

          var subItems = subUl.querySelectorAll(':scope > li');
          subItems.forEach(function(subLi) {
            var subLink = subLi.querySelector(':scope > a');
            if (!subLink) return;
            var subText = subLink.textContent.replace(/[\n\r]/g, '').trim();
            var subHref = subLink.getAttribute('href') || '#';
            var hasLevel3 = subLi.classList.contains('hassubmenu');
            var level3Ul = subLi.querySelector(':scope > ul');

            var subItem = document.createElement('li');
            subItem.className = 'mrh-mobile-item mrh-mobile-l2' + (hasLevel3 ? ' has-children' : '');

            var subNavLink = document.createElement('a');
            subNavLink.href = subHref;
            subNavLink.className = 'mrh-mobile-link';
            subNavLink.textContent = subText;

            if (hasLevel3 && level3Ul) {
              var toggleL3 = document.createElement('button');
              toggleL3.className = 'mrh-mobile-toggle';
              toggleL3.setAttribute('aria-label', 'Unterkategorien anzeigen');
              toggleL3.innerHTML = '<i class="fa-solid fa-chevron-down"></i>';

              var linkRowL2 = document.createElement('div');
              linkRowL2.className = 'mrh-mobile-link-row';
              linkRowL2.appendChild(subNavLink);
              linkRowL2.appendChild(toggleL3);
              subItem.appendChild(linkRowL2);

              // Level 3 Submenu
              var l3Menu = document.createElement('ul');
              l3Menu.className = 'mrh-mobile-sub mrh-mobile-l3-sub';

              var l3Items = level3Ul.querySelectorAll(':scope > li');
              l3Items.forEach(function(l3Li) {
                var l3Link = l3Li.querySelector(':scope > a');
                if (!l3Link) return;
                var l3Text = l3Link.textContent.replace(/[\n\r]/g, '').trim();
                var l3Href = l3Link.getAttribute('href') || '#';
                var l3Item = document.createElement('li');
                l3Item.className = 'mrh-mobile-item mrh-mobile-l3';
                l3Item.innerHTML = '<a href="' + l3Href + '" class="mrh-mobile-link">' + l3Text + '</a>';
                l3Menu.appendChild(l3Item);
              });

              subItem.appendChild(l3Menu);

              // Level 3 Toggle Event
              toggleL3.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var isOpen = subItem.classList.contains('open');
                // Andere Level3 schließen
                var siblings = subItem.parentElement.querySelectorAll('.mrh-mobile-l2.open');
                siblings.forEach(function(s) { s.classList.remove('open'); });
                if (!isOpen) subItem.classList.add('open');
              });
            } else {
              subItem.appendChild(subNavLink);
            }

            subMenu.appendChild(subItem);
          });

          navItem.appendChild(subMenu);

          // Level 1 Toggle Event
          toggleSub.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var isOpen = navItem.classList.contains('open');
            // Andere Level1 schließen
            var siblings = navItem.parentElement.querySelectorAll('.mrh-mobile-item.open');
            siblings.forEach(function(s) { s.classList.remove('open'); });
            if (!isOpen) navItem.classList.add('open');
          });
        } else {
          navItem.appendChild(navLink);
        }

        navList.appendChild(navItem);
      });

      body.appendChild(navList);
      this.panel.appendChild(body);
      document.body.appendChild(this.panel);

      // Original #mobiles_menu verstecken (wird nicht mehr gebraucht)
      sourceNav.style.display = 'none';
    },

    _bindEvents: function() {
      var self = this;

      // Hamburger-Button
      this.toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (self.isOpen) {
          self.close();
        } else {
          self.open();
        }
      });

      // Close-Button im Panel
      var closeBtn = this.panel.querySelector('.mrh-mobile-close');
      if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
          e.preventDefault();
          self.close();
        });
      }

      // Overlay klick schließt
      this.overlay.addEventListener('click', function() {
        self.close();
      });

      // ESC schließt
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && self.isOpen) {
          self.close();
        }
      });

      // Swipe-Left schließt (Touch)
      var startX = 0;
      this.panel.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
      }, { passive: true });

      this.panel.addEventListener('touchend', function(e) {
        var endX = e.changedTouches[0].clientX;
        if (startX - endX > 60) {
          self.close();
        }
      }, { passive: true });
    },

    open: function() {
      this.panel.classList.add('open');
      this.overlay.classList.add('open');
      document.body.classList.add('mrh-no-scroll');
      this.toggleBtn.classList.add('active');
      this.isOpen = true;

      // Fokus auf Close-Button
      var closeBtn = this.panel.querySelector('.mrh-mobile-close');
      if (closeBtn) setTimeout(function() { closeBtn.focus(); }, 200);
    },

    close: function() {
      this.panel.classList.remove('open');
      this.overlay.classList.remove('open');
      document.body.classList.remove('mrh-no-scroll');
      this.toggleBtn.classList.remove('active');
      this.isOpen = false;
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
    MRH.MobileMenu.init();

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
      console.log('[MRH Core] v1.6.0 initialized (Vanilla Offcanvas Mobile Menu)', {
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
<style>
/* v1.6.0 Offcanvas Mobile Menu */
.mrh-mobile-overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.5); z-index: 99998;
  opacity: 0; visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease;
  -webkit-backdrop-filter: blur(2px); backdrop-filter: blur(2px);
}
.mrh-mobile-overlay.open { opacity: 1; visibility: visible; }

.mrh-mobile-panel {
  position: fixed; top: 0; left: 0; width: 85%; max-width: 340px; height: 100%;
  background: #fff; z-index: 99999;
  transform: translateX(-105%);
  transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 4px 0 24px rgba(0,0,0,0.15);
  display: flex; flex-direction: column;
  overflow: hidden;
}
.mrh-mobile-panel.open { transform: translateX(0); }

.mrh-mobile-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; background: #2d7a3a; color: #fff;
  flex-shrink: 0;
}
.mrh-mobile-title {
  font-size: 1.1rem; font-weight: 700; letter-spacing: 0.02em;
}
.mrh-mobile-close {
  background: none; border: none; color: #fff; font-size: 1.4rem;
  cursor: pointer; padding: 4px 8px; border-radius: 6px;
  transition: background 0.2s;
}
.mrh-mobile-close:hover { background: rgba(255,255,255,0.15); }

.mrh-mobile-body {
  flex: 1; overflow-y: auto; overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
}

.mrh-mobile-nav {
  list-style: none; margin: 0; padding: 0;
}
.mrh-mobile-item { border-bottom: 1px solid #f0f0f0; }

.mrh-mobile-link-row {
  display: flex; align-items: stretch;
}
.mrh-mobile-link-row .mrh-mobile-link {
  flex: 1;
}
.mrh-mobile-link {
  display: block; padding: 14px 20px;
  color: #333; text-decoration: none; font-size: 0.95rem;
  font-weight: 500; transition: background 0.15s, color 0.15s;
}
.mrh-mobile-link:hover, .mrh-mobile-link:focus {
  background: #f5f9f5; color: #2d7a3a;
}

.mrh-mobile-toggle {
  background: none; border: none; border-left: 1px solid #f0f0f0;
  padding: 0 16px; cursor: pointer; color: #888;
  transition: color 0.2s, transform 0.3s;
  display: flex; align-items: center;
}
.mrh-mobile-toggle:hover { color: #2d7a3a; }
.mrh-mobile-item.open > .mrh-mobile-link-row > .mrh-mobile-toggle,
.mrh-mobile-l2.open > .mrh-mobile-link-row > .mrh-mobile-toggle {
  color: #2d7a3a;
}
.mrh-mobile-item.open > .mrh-mobile-link-row > .mrh-mobile-toggle i,
.mrh-mobile-l2.open > .mrh-mobile-link-row > .mrh-mobile-toggle i {
  transform: rotate(180deg);
}

.mrh-mobile-sub {
  list-style: none; margin: 0; padding: 0;
  max-height: 0; overflow: hidden;
  transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  background: #fafafa;
}
.mrh-mobile-item.open > .mrh-mobile-sub,
.mrh-mobile-l2.open > .mrh-mobile-sub { max-height: 2000px; }

.mrh-mobile-l2 .mrh-mobile-link {
  padding-left: 36px; font-size: 0.9rem; font-weight: 400; color: #555;
}
.mrh-mobile-l3 .mrh-mobile-link {
  padding-left: 52px; font-size: 0.85rem; font-weight: 400; color: #777;
}
.mrh-mobile-l3-sub { background: #f5f5f5; }

/* Body scroll lock */
body.mrh-no-scroll { overflow: hidden !important; }

/* v1.3.0 Promo-Produkt Styles */
.mrh-promo-product { display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.06); }
.mrh-promo-product:last-of-type { border-bottom: none; }
.mrh-promo-product-img { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.mrh-promo-product-info { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.mrh-promo-product-name { font-size: 0.8rem; line-height: 1.2; color: #333; font-weight: 500; }
.mrh-promo-discount { display: inline-block; background: #dc3545; color: #fff; padding: 1px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; width: fit-content; }
.mrh-promo-new-badge { display: inline-block; background: #2d7a3a; color: #fff; padding: 1px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; width: fit-content; }
</style>
