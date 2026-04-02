/**
 * Seedfinder AJAX Handler - v7.0.1 (korrigiert)
 * Datum: 10. Februar 2026
 * Autor: Mr. Hanf / Manus AI
 * 
 * Basiert auf: Meilenstein v6.9.3
 * 
 * NEU in v7.0.1:
 * - KEIN Auto-Update beim Checkbox-Change
 * - Update NUR bei Klick auf "Suchen" Button
 * - AJAX statt Page Reload
 * - URL-Parameter für ALLE Filter
 * - Reset Button Funktionalität
 * - Chips für "Deine Kriterien" (horizontal scrollbar)
 * - Sync zwischen Desktop und Mobile
 */

(function() {
    'use strict';

    // Verhindere doppelte Initialisierung
    if (window.SeedfindAjaxInitialized) {
        console.log('⚠️ SeedfindAjax bereits initialisiert - überspringe');
        return;
    }
    window.SeedfindAjaxInitialized = true;

    console.log('✅ SeedfindAjax v7.0.1 initialisiert');

    // Warte bis jQuery verfügbar ist
    function waitForJQuery(callback) {
        if (typeof jQuery !== 'undefined') {
            console.log('✅ jQuery verfügbar');
            callback();
        } else {
            console.log('⏳ Warte auf jQuery...');
            setTimeout(function() {
                waitForJQuery(callback);
            }, 50);
        }
    }

    // Warte bis DOM geladen ist
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            waitForJQuery(init);
        });
    } else {
        waitForJQuery(init);
    }

    function init() {
        // Container-Elemente
        var productsContainer = document.getElementById('products-container');
        var paginationTop = document.getElementById('pagination-top');
        var paginationBottom = document.getElementById('pagination-bottom');

        /**
         * Suchen-Buttons binden (Desktop + Mobile)
         */
        function bindSearchButtons() {
            console.log('🔗 bindSearchButtons() aufgerufen');
            
            // ⭐ NEU: Event Delegation für dynamisch geladene Buttons
            document.addEventListener('click', function(e) {
                // Desktop Suchen Button
                if (e.target && (e.target.id === 'search-filters-desktop' || e.target.closest('#search-filters-desktop'))) {
                    e.preventDefault();
                    console.log('🔍 Desktop: Suchen Button geklickt (Event Delegation)');
                    loadProducts(1); // Immer Seite 1 bei neuer Suche
                }
                
                // Mobile Suchen Button
                if (e.target && (e.target.id === 'search-filters-mobile' || e.target.closest('#search-filters-mobile'))) {
                    e.preventDefault();
                    console.log('🔍 Mobile: Suchen Button geklickt (Event Delegation)');
                    loadProducts(1);
                    // Bottom Sheet schließen
                    var bottomSheet = document.getElementById('filter-bottom-sheet');
                    if (bottomSheet) {
                        bottomSheet.classList.remove('active');
                    }
                }
            });
            
            console.log('✅ Event Delegation für Suchen Buttons aktiviert');
            
            // Prüfe ob Buttons existieren
            var searchDesktop = document.getElementById('search-filters-desktop');
            var searchMobile = document.getElementById('search-filters-mobile');
            console.log('📊 Desktop Suchen Button:', searchDesktop ? '✅ gefunden' : '❌ nicht gefunden');
            console.log('📊 Mobile Suchen Button:', searchMobile ? '✅ gefunden' : '❌ nicht gefunden');
        }

        /**
         * Zurücksetzen-Buttons binden (Desktop + Mobile)
         */
        function bindResetButtons() {
            // Desktop Reset Button
            var resetDesktop = document.getElementById('reset-filters-desktop');
            if (resetDesktop) {
                resetDesktop.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('🔄 Desktop: Reset Button geklickt');
                    resetFilters();
                });
            }

            // Mobile Reset Button
            var resetMobile = document.getElementById('reset-filters-mobile');
            if (resetMobile) {
                resetMobile.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('🔄 Mobile: Reset Button geklickt');
                    resetFilters();
                });
            }
        }

        /**
         * Checkbox Change Event (Counter/Chips/Sortierung/Ausgrauen, KEIN Produkt-Update)
         */
        function bindCheckboxChange() {
            var checkboxes = document.querySelectorAll('.filter-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    console.log('✅ Checkbox geändert (kein Auto-Update)');
                    updateActiveFilterCount();
                    updateActiveChips();
                    // ⭐ NEU: Filter sofort ausgrauen und sortieren
                    updateAvailableFilters();
                    // Sortierung nach kurzer Verzögerung (damit Counts aktualisiert sind)
                    setTimeout(function() {
                        if (typeof window.seedfinderSortCheckboxes === 'function') {
                            window.seedfinderSortCheckboxes();
                        }
                    }, 100);
                });
            });
        }

        /**
         * Lädt Produkte via AJAX
         * @param {number} page - Seitennummer (optional, default: 1)
         */
        function loadProducts(page) {
            console.log('=== loadProducts(' + (page || 1) + ') ===');

            // URL-Parameter sammeln
            var params = new URLSearchParams();

            // Stage und Category aus URL
            var urlParams = new URLSearchParams(window.location.search);
            var stage = urlParams.get('stage') || '2';
            var category = urlParams.get('category');

            // Stage und Category
            params.set('stage', stage);
            if (category) {
                params.set('category', category);
            }

            // Alle checked Checkboxen sammeln
            var checkboxes = document.querySelectorAll('.filter-checkbox:checked');
            var checkedCount = 0;

            checkboxes.forEach(function(checkbox) {
                params.append(checkbox.name, checkbox.value);
                checkedCount++;
                console.log('✅ Filter hinzugefügt:', checkbox.name, '=', checkbox.value);
            });

            console.log('📊 Checked Checkboxen:', checkedCount);

            // Seite hinzufügen
            if (page) {
                params.set('page', page);
            }

            // AJAX URL
            var ajaxUrl = 'seedfinder.php?' + params.toString();
            console.log('🔗 AJAX URL:', ajaxUrl);

            // Loading-Indikator zeigen
            showLoadingIndicator();

            // AJAX Request
            var xhr = new XMLHttpRequest();
            xhr.open('GET', ajaxUrl, true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log('📥 Response erhalten (Status:', xhr.status, ')');

                    if (xhr.status === 200) {
                        console.log('✅ Response OK, aktualisiere Display...');
                        updateProductsDisplay(xhr.responseText);

                        // URL aktualisieren mit Seitennummer
                        updateURL(page);

                        // Filter-Counts aktualisieren
                        updateAvailableFilters();
                    } else {
                        console.error('❌ AJAX Fehler:', xhr.status);
                        hideLoadingIndicator();
                    }
                }
            };

            xhr.send();
        }

        /**
         * Aktualisiert die Produkt-Anzeige mit neuem HTML
         * @param {string} html - Neues HTML vom Server
         */
        function updateProductsDisplay(html) {
            console.log('--- updateProductsDisplay() ---');
            console.log('📝 HTML Länge:', html.length, 'Zeichen');
            console.log('📝 HTML Vorschau (erste 200 Zeichen):', html.substring(0, 200));

            // Parse HTML
            var temp = document.createElement('div');
            temp.innerHTML = html;

            // Produkte aktualisieren
            var newProducts = temp.querySelector('#products-container');
            console.log('📦 newProducts gefunden:', newProducts ? '✅ JA' : '❌ NEIN');
            console.log('📦 productsContainer existiert:', productsContainer ? '✅ JA' : '❌ NEIN');
            
            if (newProducts && productsContainer) {
                productsContainer.innerHTML = newProducts.innerHTML;
                console.log('✅ Produkte aktualisiert');
            } else {
                if (!newProducts) console.error('❌ #products-container nicht in Response gefunden!');
                if (!productsContainer) console.error('❌ productsContainer nicht im DOM gefunden!');
            }

            // Pagination oben aktualisieren
            var newPaginationTop = temp.querySelector('#pagination-top');
            var currentPaginationTop = document.getElementById('pagination-top'); // ⭐ NEU: Immer aktuell aus DOM holen
            console.log('📄 newPaginationTop gefunden:', newPaginationTop ? '✅ JA' : '❌ NEIN');
            console.log('📄 currentPaginationTop existiert:', currentPaginationTop ? '✅ JA' : '❌ NEIN');
            
            if (newPaginationTop && currentPaginationTop) {
                currentPaginationTop.innerHTML = newPaginationTop.innerHTML;
                console.log('✅ Pagination oben aktualisiert');
            } else {
                if (!newPaginationTop) console.warn('⚠️ #pagination-top nicht in Response gefunden');
                if (!currentPaginationTop) console.warn('⚠️ #pagination-top nicht im DOM gefunden');
            }

            // Pagination unten aktualisieren
            var newPaginationBottom = temp.querySelector('#pagination-bottom');
            var currentPaginationBottom = document.getElementById('pagination-bottom'); // ⭐ NEU: Immer aktuell aus DOM holen
            console.log('📄 newPaginationBottom gefunden:', newPaginationBottom ? '✅ JA' : '❌ NEIN');
            console.log('📄 currentPaginationBottom existiert:', currentPaginationBottom ? '✅ JA' : '❌ NEIN');
            
            if (newPaginationBottom && currentPaginationBottom) {
                currentPaginationBottom.innerHTML = newPaginationBottom.innerHTML;
                console.log('✅ Pagination unten aktualisiert');
            } else {
                if (!newPaginationBottom) console.warn('⚠️ #pagination-bottom nicht in Response gefunden');
                if (!currentPaginationBottom) console.warn('⚠️ #pagination-bottom nicht im DOM gefunden');
            }

            // Scroll zu Produkten
            if (productsContainer) {
                var offsetTop = productsContainer.offsetTop - 20;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }

            // Loading-Indikator verstecken
            hideLoadingIndicator();

            // Pagination-Listener neu anhängen
            attachPaginationListeners();

            console.log('--- updateProductsDisplay() FERTIG ---');
        }

        /**
         * Hängt Event-Listener an Pagination-Links
         */
        function attachPaginationListeners() {
            console.log('--- attachPaginationListeners() ---');
            
            var paginationLinks = document.querySelectorAll(
                '.pagination a, #pagination-top a, #pagination-bottom a'
            );
            
            console.log('Pagination Links gefunden:', paginationLinks.length);

            var attachedCount = 0;
            var skippedCount = 0;
            
            paginationLinks.forEach(function(link, index) {
                var href = link.getAttribute('href');
                var text = link.textContent.trim();

                // Überspringe Links mit null oder #
                if (!href || href === 'null' || href === '#' || href === '') {
                    console.log('Link ' + (index + 1) + ': ÜBERSPRUNGEN (href="' + href + '", text="' + text + '")');
                    skippedCount++;
                    return;
                }

                // Event-Listener anhängen
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    console.log('=== PAGINATION-LINK GEKLICKT ===');
                    console.log('Text:', text);
                    console.log('href:', href);

                    // Extrahiere Seitennummer aus URL
                    var urlParams = new URLSearchParams(href.split('?')[1]);
                    var page = urlParams.get('page');

                    console.log('Extrahierte Seite:', page);

                    if (page) {
                        console.log('✅ Lade Seite', page);
                        loadProducts(page);
                    } else {
                        console.log('✅ Lade Seite 1 (Fallback)');
                        loadProducts(1);
                    }
                });
                
                attachedCount++;
            });
            
            console.log('Event-Listener Status:');
            console.log(' - Angehängt:', attachedCount);
            console.log(' - Übersprungen:', skippedCount);
        }

        /**
         * Lädt verfügbare Filter-Werte via AJAX
         */
        function updateAvailableFilters() {
            console.log('\n=== updateAvailableFilters() START ===');

            // URL-Parameter sammeln
            var params = new URLSearchParams();

            var urlParams = new URLSearchParams(window.location.search);
            var stage = urlParams.get('stage') || '2';
            var category = urlParams.get('category');

            params.set('ajax', 'get_available_filters');
            params.set('stage', stage);
            if (category) {
                params.set('category', category);
            }

            // Alle checked Checkboxen sammeln
            var checkboxes = document.querySelectorAll('.filter-checkbox:checked');
            checkboxes.forEach(function(checkbox) {
                params.append(checkbox.name, checkbox.value);
            });

            // AJAX URL
            var ajaxUrl = 'seedfinder.php?' + params.toString();
            console.log('📤 AJAX Request URL:', ajaxUrl);

            // AJAX Request
            var xhr = new XMLHttpRequest();
            xhr.open('GET', ajaxUrl, true);

            xhr.onreadystatechange = function() {
                console.log('📡 XHR State:', xhr.readyState, 'Status:', xhr.status);
                
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        console.log('✅ AJAX Response erhalten');
                        console.log('📝 Response Text (erste 500 Zeichen):', xhr.responseText.substring(0, 500));
                        
                        try {
                            var response = JSON.parse(xhr.responseText);
                            console.log('✅ JSON Parse erfolgreich');
                            console.log('📊 Response Struktur:', response);

                            // Counts in Checkboxen aktualisieren
                            updateFilterCounts(response);
                        } catch (e) {
                            console.error('❌ JSON Parse Fehler:', e);
                            console.error('📝 Response war:', xhr.responseText);
                        }
                    } else {
                        console.error('❌ AJAX Fehler - Status:', xhr.status);
                        console.error('📝 Response Text:', xhr.responseText);
                    }
                }
            };

            xhr.send();
        }

        /**
         * Aktualisiert Filter-Counts in den Labels
         * @param {object} response - AJAX Response mit verfügbaren Filtern
         */
        function updateFilterCounts(response) {
            console.log('\n=== updateFilterCounts() START ===');
            console.log('📊 Response:', response);
            
            // ⭐ FIX: Response heißt 'available_filters' nicht 'filters'
            var filters = response.available_filters || response.filters;
            
            if (!response || !filters) {
                console.warn('⚠️ Keine Filter-Daten in Response!');
                return;
            }
            
            console.log('📊 Filter-Daten vorhanden:', Object.keys(filters).length, 'Filter');

            // Alle Checkboxen durchgehen
            var checkboxes = document.querySelectorAll('.filter-checkbox');
            checkboxes.forEach(function(checkbox) {
                var filterId = checkbox.getAttribute('data-filter-id');
                var valueId = checkbox.getAttribute('data-value-id');

                // Finde Count in Response
                var count = 0;
                var available = false;

                if (filters[filterId] && filters[filterId][valueId]) {
                    count = filters[filterId][valueId].count || 0;
                    available = filters[filterId][valueId].available !== false;
                }

                // Label aktualisieren
                var label = checkbox.nextElementSibling;
                if (label) {
                    var countSpan = label.querySelector('.filter-count, .filter-value-count');
                    if (countSpan) {
                        if (count > 0) {
                            countSpan.textContent = '(' + count + ')';
                            countSpan.style.display = '';
                        } else {
                            countSpan.style.display = 'none';
                        }
                    }
                }

                // Disabled-Status setzen
                var parent = checkbox.closest('.form-check');
                if (parent) {
                    if (available || checkbox.checked) {
                        if (parent.classList.contains('filter-disabled')) {
                            console.log('✅ Enable:', filterId + '/' + valueId, 'Count:', count);
                        }
                        parent.classList.remove('filter-disabled');
                        checkbox.disabled = false;
                    } else {
                        if (!parent.classList.contains('filter-disabled')) {
                            console.log('❌ Disable:', filterId + '/' + valueId, 'Count:', count);
                        }
                        parent.classList.add('filter-disabled');
                        checkbox.disabled = true;
                    }
                }
            });

            console.log('✅ Filter-Counts aktualisiert');
            console.log('=== updateFilterCounts() END ===\n');
        }

        /**
         * Aktualisiert die URL mit aktuellen Filter-Parametern
         */
        function updateURL(page) {
            console.log('--- updateURL(' + (page || 'keine Seite') + ') ---');

            // ⭐ NEU: URL komplett neu aufbauen statt zu modifizieren
            var params = new URLSearchParams();
            
            // Stage und Category aus aktueller URL
            var currentParams = new URLSearchParams(window.location.search);
            var stage = currentParams.get('stage');
            var category = currentParams.get('category');
            
            if (stage) params.set('stage', stage);
            if (category) params.set('category', category);

            // Alle checked Filter hinzufügen
            var checkboxes = document.querySelectorAll('.filter-checkbox:checked');
            checkboxes.forEach(function(checkbox) {
                params.append(checkbox.name, checkbox.value);
            });
            
            // Page hinzufügen (falls vorhanden)
            if (page) params.set('page', page);

            // URL aktualisieren
            var newURL = window.location.pathname + '?' + params.toString();
            window.history.pushState({}, '', newURL);

            console.log('🔗 URL aktualisiert:', newURL);
        }

        /**
         * Alle Filter zurücksetzen
         */
        function resetFilters() {
            console.log('🔄 Setze alle Filter zurück...');

            // Alle Checkboxen deaktivieren
            var checkboxes = document.querySelectorAll('.filter-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });

            // Counter und Chips zurücksetzen
            updateActiveFilterCount();
            updateActiveChips();

            // Produkte neu laden (ohne Filter)
            loadProducts(1);

            console.log('✅ Filter zurückgesetzt');
        }

        /**
         * Active Filter Counter aktualisieren
         */
        function updateActiveFilterCount() {
            var count = document.querySelectorAll('.filter-checkbox:checked').length;

            // Counter in allen Badges aktualisieren
            var badges = document.querySelectorAll('#active-filter-count, #active-filter-count-mobile, #active-filter-count-sheet');
            badges.forEach(function(badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? '' : 'none';
            });

            console.log('📊 Active Filter Count:', count);
        }

        /**
         * ⭐ NEU: Active Chips aktualisieren (horizontal scrollbar)
         */
        function updateActiveChips() {
            var chipsContainer = document.getElementById('active-filters-list');
            var chipsCard = document.getElementById('active-filters-card');

            if (!chipsContainer || !chipsCard) {
                return;
            }

            // Chips Container leeren
            chipsContainer.innerHTML = '';

            var chips = [];

            // Alle checked Checkboxen durchgehen
            var checkboxes = document.querySelectorAll('.filter-checkbox:checked');
            checkboxes.forEach(function(checkbox) {
                var filterId = checkbox.getAttribute('data-filter-id');
                var valueId = checkbox.getAttribute('data-value-id');

                // Label ohne Count extrahieren (nur Text vor der Klammer)
                var label = checkbox.nextElementSibling;
                if (label) {
                    var fullLabel = label.textContent.trim();
                    var cleanLabel = fullLabel.replace(/\s*\(\d+\)\s*$/, '').trim();

                    // Chip HTML erstellen
                    var chip = document.createElement('div');
                    chip.className = 'filter-chip';
                    chip.setAttribute('data-filter-id', filterId);
                    chip.setAttribute('data-value-id', valueId);

                    var span = document.createElement('span');
                    span.textContent = cleanLabel;
                    chip.appendChild(span);

                    var removeBtn = document.createElement('span');
                    removeBtn.className = 'remove-chip';
                    removeBtn.title = 'Entfernen';
                    removeBtn.textContent = '×';
                    removeBtn.addEventListener('click', function() {
                        console.log('❌ Chip entfernen:', filterId, valueId);
                        checkbox.checked = false;
                        updateActiveFilterCount();
                        updateActiveChips();
                    });
                    chip.appendChild(removeBtn);

                    chips.push(chip);
                }
            });

            if (chips.length > 0) {
                // Chips anzeigen
                chips.forEach(function(chip) {
                    chipsContainer.appendChild(chip);
                });
                chipsCard.style.display = '';
            } else {
                // Chips verstecken
                chipsCard.style.display = 'none';
            }

            console.log('🏷️ Chips aktualisiert:', chips.length);
        }

        /**
         * Loading-Indikator zeigen
         */
        function showLoadingIndicator() {
            if (productsContainer) {
                productsContainer.style.opacity = '0.5';
                productsContainer.style.pointerEvents = 'none';
            }
        }

        /**
         * Loading-Indikator verstecken
         */
        function hideLoadingIndicator() {
            if (productsContainer) {
                productsContainer.style.opacity = '1';
                productsContainer.style.pointerEvents = '';
            }
        }

        // Initialisierung
        bindSearchButtons();
        bindResetButtons();
        bindCheckboxChange();
        updateActiveFilterCount();
        updateActiveChips();
        attachPaginationListeners();

        console.log('✅ SeedfindAjax v7.0.1 bereit');
    }

})();
