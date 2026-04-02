/**
 * Seedfinder Modal JS v7.1.1 - Performance-Optimierung + Sub-Accordions
 * 
 * v7.1.1 - 27. Feb 2026:
 * - FIX: Badge-Sichtbarkeit - transform:scale(1) hinzugefügt (badgeFadeIn Animation Fix)
 * - FIX: Badge-Reset mit transform:scale(0) und opacity:0 bei count=0
 *
 * v7.1.0 - 27. Feb 2026:
 * - NEU: transformToSubAccordions() - Filter-Gruppen als Sub-Accordions auf Mobile
 * - NEU: updateSubAccordionHighlights() - Markiert Sub-Accordions mit aktiven Filtern
 * - NEU: Sub-Accordion Click-Handler (Toggle open/close)
 * - NEU: Mobile-Badge-Selector (data-category-badge="XXX-mobile")
 * - Alle bestehenden v7.0.0 Features beibehalten
 *
 * v7.0.0 - 27. Feb 2026:
 * - ALLE console.log/warn/error entfernt (Production-Ready)
 * - Debouncing für updateFilterCounts() (300ms) - verhindert Mehrfach-Requests
 * - Request-Cancellation: Vorheriger AJAX-Request wird abgebrochen
 * - Code-Cleanup: Leere Blöcke und unnötige Kommentare entfernt
 *
 * Basiert auf v6.10.1
 */
(function($) {
    'use strict';
    
    // Globale Variablen
    let currentCategoryId = null;
    let isUpdating = false;
    let isMobileView = false;
    
    // v7.0: Request-Cancellation und Debounce
    let currentCountXHR = null;
    let countUpdateTimer = null;
    
    // Initialisierung
    $(document).ready(function() {
        initSeedfinder();
        
        setTimeout(function() {
            sortFilterOptions();
            updateCategoryFilterBadges();
            updateActiveFilterBadges();
        }, 100);
        
        checkAndOpenModal();
    });
    
    function initSeedfinder() {
        const urlParams = new URLSearchParams(window.location.search);
        currentCategoryId = urlParams.get('category');
        
        if (!currentCategoryId) {
            return;
        }
        
        setupEventListeners();
        initMobileAccordion();
        updateActiveFilterBadges();
        toggleResetButtons();
        updateCategoryFilterBadges();
    }
    
    /**
     * Mobile Accordion initialisieren
     */
    function initMobileAccordion() {
        if ($('#filter-accordion-mobile').length === 0) {
            return;
        }
        
        handleResponsiveLayout();
        
        $(window).on('resize', debounce(function() {
            handleResponsiveLayout();
        }, 250));
    }
    
    /**
     * Handhabt den responsiven Layout-Wechsel
     * Verschiebt Filter-Inhalte zwischen Desktop-Tabs und Mobile-Accordion
     */
    function handleResponsiveLayout() {
        const wasMobile = isMobileView;
        isMobileView = $(window).width() < 768;
        
        if (wasMobile === isMobileView && wasMobile !== false) return;
        
        const categories = ['main', 'genetics', 'cultivation', 'taste', 'advanced'];
        
        if (isMobileView) {
            categories.forEach(function(cat) {
                const $source = $('#category-' + cat);
                const $target = $('#accordion-body-' + cat);
                
                if ($source.length && $target.length && $target.children().length === 0) {
                    // v7.1.0: Klone den Inhalt und transformiere zu Sub-Accordions
                    var $clonedContent = $source.clone();
                    var $transformed = transformToSubAccordions($clonedContent, cat);
                    $target.html($transformed.html());
                    
                    // Synchronisiere Checkbox-States vom Original
                    $source.find('.modal-filter-checkbox:checked').each(function() {
                        const filterId = $(this).data('filter-id');
                        const valueId = $(this).data('value-id');
                        $target.find('.modal-filter-checkbox[data-filter-id="' + filterId + '"][data-value-id="' + valueId + '"]')
                               .prop('checked', true);
                    });
                }
            });
            
            $('#filter-categories-desktop').addClass('d-none d-md-block');
            
            // v7.1.0: Markiere Sub-Accordions mit aktiven Filtern
            updateSubAccordionHighlights();
        } else {
            $('#filter-categories-desktop').removeClass('d-none d-md-block');
            
            categories.forEach(function(cat) {
                const $accordion = $('#accordion-body-' + cat);
                const $desktop = $('#category-' + cat);
                
                if ($accordion.length && $desktop.length) {
                    $accordion.find('.modal-filter-checkbox:checked').each(function() {
                        const filterId = $(this).data('filter-id');
                        const valueId = $(this).data('value-id');
                        $desktop.find('.modal-filter-checkbox[data-filter-id="' + filterId + '"][data-value-id="' + valueId + '"]')
                                .prop('checked', true);
                    });
                }
            });
        }
    }
    
    /**
     * Debounce-Hilfsfunktion
     */
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }
    
    /**
     * v7.1.0: Transformiert Filter-Gruppen zu Sub-Accordions
     * Jede <h5> + .filter-options wird zu einem Sub-Accordion-Item
     */
    function transformToSubAccordions($content, categoryId) {
        var $row = $content.find('.row').first();
        if (!$row.length) return $content;
        
        var $subAccordionContainer = $('<div class="sf-sub-accordion-container"></div>');
        var subIndex = 0;
        
        // Iteriere über alle col-Elemente (Filter-Gruppen)
        $row.find('.col-12').each(function() {
            var $col = $(this);
            var $heading = $col.find('h5').first();
            var $filterOptions = $col.find('.filter-options').first();
            
            if (!$heading.length || !$filterOptions.length) return;
            
            var headingText = $heading.text().trim();
            var subAccordionId = categoryId + '-sub-' + subIndex;
            
            // Erstelle Sub-Accordion-Item
            var $subItem = $('<div class="sf-sub-accordion-item"></div>');
            
            // Sub-Accordion-Header
            var $subHeader = $('<div class="sf-sub-accordion-header" data-bs-toggle-sub-accordion="' + subAccordionId + '">' +
                '<span class="sf-sub-accordion-title">' + headingText + '</span>' +
                '<span class="sf-sub-accordion-chevron"><i class="fa fa-chevron-down"></i></span>' +
                '</div>');
            
            // Sub-Accordion-Body
            var $subBody = $('<div class="sf-sub-accordion-body" id="sub-accordion-' + subAccordionId + '"></div>');
            $subBody.html($filterOptions.html());
            
            $subItem.append($subHeader).append($subBody);
            $subAccordionContainer.append($subItem);
            
            subIndex++;
        });
        
        // Ersetze die .row mit dem Sub-Accordion-Container
        if (subIndex > 0) {
            $row.replaceWith($subAccordionContainer);
        }
        
        return $content;
    }
    
    /**
     * Prüfe ob Modal automatisch geöffnet werden soll
     */
    function checkAndOpenModal() {
        const urlParams = new URLSearchParams(window.location.search);
        const openModal = urlParams.get('open_modal');
        
        if (openModal === '1') {
            setTimeout(function() {
                $('#seedfinder-filter-modal').modal('show');
                
                const newUrl = window.location.pathname + window.location.search.replace('&open_modal=1', '').replace('open_modal=1&', '').replace('?open_modal=1', '?').replace('?&', '?');
                window.history.replaceState({}, '', newUrl);
            }, 300);
        }
    }
    
    function setupEventListeners() {
        // Modal öffnen
        $('.open-filters-btn').on('click', function() {
            syncMainFiltersToModal();
            $('#seedfinder-filter-modal').modal('show');
        });
        
        // Badges nach Modal-Öffnung aktualisieren
        $('#seedfinder-filter-modal').on('shown.bs.modal', function() {
            if (isMobileView) {
                handleResponsiveLayout();
            }
            updateCategoryFilterBadges();
        });
        
        // Kategorie-Dropdown im Modal-Header
        $('#modal-category-selector').on('change', function() {
            const newCategoryId = $(this).val();
            
            if (newCategoryId && newCategoryId !== currentCategoryId) {
                showCategoryLoadingOverlay();
                
                const activeFilters = {};
                $('.filter-checkbox:checked').each(function() {
                    const filterId = $(this).data('filter-id');
                    const valueId = $(this).data('value-id');
                    
                    if (!activeFilters[filterId]) {
                        activeFilters[filterId] = [];
                    }
                    activeFilters[filterId].push(valueId);
                });
                
                const urlParams = new URLSearchParams(window.location.search);
                const stage = urlParams.get('stage') || '3';
                
                let newUrl = window.location.pathname + '?stage=' + stage + '&category=' + newCategoryId;
                
                for (const filterId in activeFilters) {
                    for (const valueId of activeFilters[filterId]) {
                        newUrl += '&filter[' + filterId + '][]=' + valueId;
                    }
                }
                
                newUrl += '&open_modal=1';
                
                window.location.href = newUrl;
            }
        });
        
        // Kategorie-Navigation (Desktop)
        $('.filter-category-btn').on('click', function() {
            const category = $(this).data('category');
            switchFilterCategory(category);
            
            $('.filter-category-btn').removeClass('active');
            $(this).addClass('active');
        });
        
        // Accordion-Header Click (Mobile)
        $(document).on('click', '.sf-accordion-header', function() {
            const category = $(this).data('toggle-accordion');
            if (!category) return;
            
            const $item = $(this).closest('.sf-accordion-item');
            const $body = $item.find('.sf-accordion-body');
            const $chevron = $(this).find('.sf-accordion-chevron i');
            const isOpen = $body.hasClass('show');
            
            if (isOpen) {
                $body.slideUp(300, function() {
                    $body.removeClass('show');
                });
                $chevron.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $(this).removeClass('active');
            } else {
                // Alle anderen schließen
                $('.sf-accordion-item').not($item).each(function() {
                    var $otherBody = $(this).find('.sf-accordion-body');
                    var $otherHeader = $(this).find('.sf-accordion-header');
                    var $otherChevron = $otherHeader.find('.sf-accordion-chevron i');
                    if ($otherBody.hasClass('show')) {
                        $otherBody.slideUp(300, function() {
                            $otherBody.removeClass('show');
                        });
                        $otherChevron.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                        $otherHeader.removeClass('active');
                    }
                });
                
                $body.slideDown(300, function() {
                    $body.addClass('show');
                });
                $chevron.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $(this).addClass('active');
            }
            
            updateCategoryFilterBadges();
        });
        
        // v7.1.0: Sub-Accordion-Header Click (Mobile)
        $(document).on('click', '.sf-sub-accordion-header', function() {
            var subAccordionId = $(this).data('toggle-sub-accordion');
            if (!subAccordionId) return;
            
            var $subItem = $(this).closest('.sf-sub-accordion-item');
            var $subBody = $subItem.find('.sf-sub-accordion-body');
            var $chevron = $(this).find('.sf-sub-accordion-chevron i');
            var isOpen = $subBody.hasClass('show');
            
            if (isOpen) {
                $subBody.slideUp(200, function() {
                    $subBody.removeClass('show');
                });
                $chevron.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $(this).removeClass('active');
            } else {
                $subBody.slideDown(200, function() {
                    $subBody.addClass('show');
                });
                $chevron.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $(this).addClass('active');
            }
        });
        
        // Haupt-Filter: Checkbox-Änderung
        $(document).on('change', '.main-filter-checkbox', function() {
            if (!isUpdating) {
                syncFilters($(this));
                updateActiveFilterBadges();
                // v7.0: Debounced statt sofortigem AJAX-Call
                debouncedCountUpdate();
                toggleResetButtons();
                updateCategoryFilterBadges();
                updateSubAccordionHighlights();
            }
        });
        
        // Modal-Filter: Checkbox-Änderung
        $(document).on('change', '.modal-filter-checkbox', function() {
            if (!isUpdating) {
                syncFilters($(this));
                updateActiveFilterBadges();
                // v7.0: Debounced statt sofortigem AJAX-Call
                debouncedCountUpdate();
                toggleResetButtons();
                updateCategoryFilterBadges();
                updateSubAccordionHighlights();
            }
        });
        
        // Haupt-Filter anwenden
        $('#apply-main-filters').on('click', function() {
            applyFilters();
        });
        
        // Modal-Filter anwenden
        $('#apply-modal-filters').on('click', function() {
            applyFilters();
        });
        
        // Haupt-Filter zurücksetzen
        $('#reset-main-filters').on('click', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const category = urlParams.get('category');
            const stage = urlParams.get('stage');
            
            let newUrl = window.location.pathname;
            if (stage && category) {
                newUrl += '?stage=' + stage + '&category=' + category + '&filters_reset=1';
            }
            
            window.location.href = newUrl;
        });
        
        // Modal-Filter zurücksetzen
        $('#reset-modal-filters').on('click', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const category = urlParams.get('category');
            const stage = urlParams.get('stage');
            
            let newUrl = window.location.pathname;
            if (stage && category) {
                newUrl += '?stage=' + stage + '&category=' + category + '&filters_reset=1';
            }
            
            window.location.href = newUrl;
        });
    }
    
    /**
     * v7.0: Debounced Count-Update
     * Wartet 300ms nach dem letzten Checkbox-Change
     */
    function debouncedCountUpdate() {
        if (countUpdateTimer) {
            clearTimeout(countUpdateTimer);
        }
        countUpdateTimer = setTimeout(function() {
            updateFilterCounts();
        }, 300);
    }
    
    /**
     * Loading-Overlay für Kategorie-Wechsel
     */
    function showCategoryLoadingOverlay() {
        let loadingText = $('#trans-loading-category-filters').text();
        
        if (!loadingText || loadingText.trim() === '') {
            loadingText = 'Lade Filter f&uuml;r neue Kategorie...';
        }
        
        const $overlay = $('<div class="seedfinder-loading-overlay">' +
            '<div class="seedfinder-loading-content">' +
                '<i class="fa fa-spinner fa-spin fa-4x text-primary mb-3"></i>' +
                '<h4 class="text-dark">' + loadingText + '</h4>' +
            '</div>' +
        '</div>');
        
        $('#seedfinder-filter-modal .modal-content').append($overlay);
        
        setTimeout(function() {
            $overlay.addClass('active');
        }, 10);
    }
    
    /**
     * Reset-Buttons ein-/ausblenden
     */
    function toggleResetButtons() {
        const hasActiveFilters = $('.filter-checkbox:checked').length > 0;
        if (hasActiveFilters) {
            $('#reset-filters-btn').show();
        } else {
            $('#reset-filters-btn').hide();
        }
    }
    
    /**
     * Category Filter Badges aktualisieren (DOM-basiert)
     * v7.1.0: Aktualisiert auch Mobile-Badges (data-category-badge="XXX-mobile")
     */
    function updateCategoryFilterBadges() {
        const categories = ['main', 'genetics', 'cultivation', 'taste', 'advanced'];
        
        categories.forEach(function(cat) {
            const $container = $('#category-' + cat);
            const $accordionBody = $('#accordion-body-' + cat);
            // v7.1.0: Auch Mobile-Badges selektieren
            const $badge = $('[data-category-badge="' + cat + '"], [data-category-badge="' + cat + '-mobile"]');
            
            // Deduplizierung
            const uniqueFilters = {};
            const $allCheckboxes = $container.find('.filter-checkbox:checked').add($accordionBody.find('.filter-checkbox:checked'));
            $allCheckboxes.each(function() {
                const key = $(this).data('filter-id') + '_' + $(this).data('value-id');
                uniqueFilters[key] = true;
            });
            const count = Object.keys(uniqueFilters).length;
            
            if ($badge.length) {
                if (count > 0) {
                    // v7.1.1 FIX: animation:none + transform:scale(1) + opacity:1
                    // Ohne transform:scale(1) bleibt das Badge bei 0x0px
                    // weil die CSS-Animation badgeFadeIn von scale(0.5) startet
                    $badge.css('animation', 'none');
                    $badge[0] && ($badge[0].offsetHeight); // Force reflow
                    $badge.text(count).css({
                        'display': 'inline-block',
                        'opacity': '1',
                        'transform': 'scale(1)'
                    });
                } else {
                    $badge.css('animation', 'none');
                    $badge.text('0').css({
                        'display': 'none',
                        'opacity': '0',
                        'transform': 'scale(0)'
                    });
                }
            }
        });
    }
    
    /**
     * v7.1.0: Sub-Accordion-Highlights aktualisieren
     * Markiert Sub-Accordions die aktive Filter enthalten
     */
    function updateSubAccordionHighlights() {
        $('.sf-sub-accordion-item').each(function() {
            var $subItem = $(this);
            var $subBody = $subItem.find('.sf-sub-accordion-body');
            var $subHeader = $subItem.find('.sf-sub-accordion-header');
            
            var hasActiveFilters = $subBody.find('.filter-checkbox:checked, .modal-filter-checkbox:checked').length > 0;
            
            if (hasActiveFilters) {
                $subHeader.addClass('has-active-filters');
            } else {
                $subHeader.removeClass('has-active-filters');
            }
        });
    }
    
    /**
     * Wechselt zwischen Filter-Kategorien im Modal (Desktop)
     */
    function switchFilterCategory(category) {
        $('.filter-category-content').hide();
        $('#category-' + category).fadeIn(300);
    }
    
    /**
     * Synchronisiert Filter zwischen Haupt-Seite und Modal
     */
    function syncFilters($checkbox) {
        const filterId = $checkbox.data('filter-id');
        const valueId = $checkbox.data('value-id');
        const isChecked = $checkbox.is(':checked');
        
        const selector = `input[data-filter-id="${filterId}"][data-value-id="${valueId}"]`;
        const $targets = $(selector).not($checkbox);
        
        $targets.prop('checked', isChecked);
    }
    
    /**
     * Synchronisiert Haupt-Filter mit Modal-Filtern beim Öffnen
     */
    function syncMainFiltersToModal() {
        $('.main-filter-checkbox').each(function() {
            const $mainCheckbox = $(this);
            const filterId = $mainCheckbox.data('filter-id');
            const valueId = $mainCheckbox.data('value-id');
            const isChecked = $mainCheckbox.is(':checked');
            
            const $modalCheckbox = $(`.modal-filter-checkbox[data-filter-id="${filterId}"][data-value-id="${valueId}"]`);
            $modalCheckbox.prop('checked', isChecked);
        });
    }
    
    /**
     * Aktualisiert Filter-Counts via AJAX
     * v7.0: Mit Request-Cancellation und Debouncing
     */
    function updateFilterCounts() {
        if (isUpdating) return;
        
        isUpdating = true;
        
        // v7.0: Vorherigen Request abbrechen
        if (currentCountXHR && currentCountXHR.readyState !== 4) {
            currentCountXHR.abort();
        }
        
        const activeFilters = {};
        $('.filter-checkbox:checked').each(function() {
            const filterId = $(this).data('filter-id');
            const valueId = $(this).data('value-id');
            
            if (!activeFilters[filterId]) {
                activeFilters[filterId] = [];
            }
            activeFilters[filterId].push(valueId);
        });
        
        currentCountXHR = $.ajax({
            url: 'seedfinder.php',
            method: 'GET',
            data: {
                ajax: 'update_counts',
                category: currentCategoryId,
                filter: activeFilters
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    applyCountUpdates(response.data);
                }
            },
            error: function(xhr, status, error) {
                // Abgebrochene Requests ignorieren
                if (status === 'abort') return;
            },
            complete: function() {
                isUpdating = false;
            }
        });
    }
    
    /**
     * Wendet aktualisierte Counts auf alle Filter an
     */
    function applyCountUpdates(data) {
        if (!data) {
            return;
        }
        
        $('.filter-checkbox').each(function() {
            const $checkbox = $(this);
            const filterId = $checkbox.data('filter-id');
            const valueId = $checkbox.data('value-id');
            const $label = $checkbox.next('label');
            const $count = $label.find('.filter-count');
            
            let newCount = 0;
            let isEnabled = true;
            
            if (data[filterId] && data[filterId][valueId]) {
                newCount = data[filterId][valueId].count || 0;
                isEnabled = data[filterId][valueId].enabled !== false;
            }
            
            $count.text('(' + newCount + ')');
            
            if (!$checkbox.is(':checked')) {
                if (!isEnabled || newCount === 0) {
                    $checkbox.prop('disabled', true);
                    $checkbox.closest('.custom-control').addClass('filter-disabled');
                    $checkbox.closest('.custom-control').css({
                        'display': 'block',
                        'opacity': '0.5',
                        'pointer-events': 'none'
                    });
                } else {
                    $checkbox.prop('disabled', false);
                    $checkbox.closest('.custom-control').removeClass('filter-disabled');
                    $checkbox.closest('.custom-control').css({
                        'display': 'block',
                        'opacity': '1',
                        'pointer-events': 'auto'
                    });
                }
            }
        });
        
        sortFilterOptions();
        updateCategoryFilterBadges();
        updateActiveFilterBadges();
        // v7.1.0: Sub-Accordion-Highlights nach Count-Update aktualisieren
        updateSubAccordionHighlights();
    }
    
    /**
     * Sortiert Filter-Optionen: Checked > Enabled > Disabled
     */
    function sortFilterOptions() {
        $('.filter-options, .filter-options-main').each(function() {
            const $container = $(this);
            const $items = $container.find('.custom-control').detach();
            
            $items.sort(function(a, b) {
                const $aCheckbox = $(a).find('input');
                const $bCheckbox = $(b).find('input');
                
                const aChecked = $aCheckbox.is(':checked');
                const bChecked = $bCheckbox.is(':checked');
                const aDisabled = $aCheckbox.is(':disabled');
                const bDisabled = $bCheckbox.is(':disabled');
                
                if (aChecked && !bChecked) return -1;
                if (!aChecked && bChecked) return 1;
                
                if (!aDisabled && bDisabled) return -1;
                if (aDisabled && !bDisabled) return 1;
                
                let aOrder = parseInt($aCheckbox.data('sort-order'));
                let bOrder = parseInt($bCheckbox.data('sort-order'));
                
                if (isNaN(aOrder) && isNaN(bOrder)) {
                    const aText = $aCheckbox.data('value-name');
                    const bText = $bCheckbox.data('value-name');
                    return aText.localeCompare(bText);
                }
                
                if (isNaN(aOrder)) return 1;
                if (isNaN(bOrder)) return -1;
                
                if (aOrder !== bOrder) {
                    return aOrder - bOrder;
                }
                
                const aText = $aCheckbox.data('value-name');
                const bText = $bCheckbox.data('value-name');
                return aText.localeCompare(bText);
            });
            
            $container.append($items);
            reapplyFilterStyles();
        });
    }
    
    /**
     * Wendet Styles auf alle Filter an (nach Sortierung)
     */
    function reapplyFilterStyles() {
        $('.filter-checkbox').each(function() {
            const $checkbox = $(this);
            const $control = $checkbox.closest('.custom-control');
            
            if ($checkbox.is(':checked')) {
                $control.css({
                    'display': 'block',
                    'opacity': '1',
                    'pointer-events': 'auto'
                });
            }
            else if ($checkbox.is(':disabled')) {
                $control.css({
                    'display': 'block',
                    'opacity': '0.5',
                    'pointer-events': 'none'
                });
            }
            else {
                $control.css({
                    'display': 'block',
                    'opacity': '1',
                    'pointer-events': 'auto'
                });
            }
        });
    }
    
    /**
     * Aktualisiert die "Deine ausgewählten Kriterien"-Card
     */
    function updateActiveFilterBadges() {
        const $container = $('#active-filters-list');
        const $card = $('#active-filters-card');
        
        const activeFiltersMap = {};
        $('.filter-checkbox:checked').each(function() {
            const filterName = $(this).data('filter-name');
            const valueName = $(this).data('value-name');
            const filterId = String($(this).data('filter-id'));
            const valueId = String($(this).data('value-id'));
            
            const key = filterId + '_' + valueId;
            
            if (!activeFiltersMap[key]) {
                activeFiltersMap[key] = {
                    filterName: filterName,
                    valueName: valueName,
                    filterId: filterId,
                    valueId: valueId
                };
            }
        });
        
        const activeFilters = Object.values(activeFiltersMap);
        
        if (activeFilters.length > 0) {
            $container.empty();
            
            activeFilters.forEach(function(filter) {
                const badge = $('<span class="badge badge-primary mr-2 mb-2"></span>')
                    .text(filter.filterName + ': ' + filter.valueName)
                    .append(' <i class="fa fa-times ml-1 remove-filter-badge" data-filter-id="' + filter.filterId + '" data-value-id="' + filter.valueId + '"></i>');
                
                $container.append(badge);
            });
            
            $card.show();
        } else {
            $card.hide();
        }
    }
    
    /**
     * Wendet alle ausgewählten Filter an (Seite neu laden)
     */
    function applyFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        const stage = urlParams.get('stage');
        const category = currentCategoryId;
        
        const activeFilters = {};
        $('.filter-checkbox:checked').each(function() {
            const filterId = $(this).data('filter-id');
            const valueId = $(this).data('value-id');
            
            if (!activeFilters[filterId]) {
                activeFilters[filterId] = [];
            }
            activeFilters[filterId].push(valueId);
        });
        
        const manufacturers = [];
        $('.manufacturer-checkbox:checked').each(function() {
            manufacturers.push($(this).val());
        });
        
        let newUrl = window.location.pathname + '?stage=2&category=' + category;
        
        for (const filterId in activeFilters) {
            for (const valueId of activeFilters[filterId]) {
                newUrl += '&filter[' + filterId + '][]=' + valueId;
            }
        }
        
        for (const manufacturerId of manufacturers) {
            newUrl += '&manufacturer[]=' + manufacturerId;
        }
        
        window.location.href = newUrl + '#products-container';
    }
    
    // Event Delegation für Badge-Entfernung
    $(document).on('click', '.remove-filter-badge', function() {
        const filterId = $(this).data('filter-id');
        const valueId = $(this).data('value-id');
        
        $(`.filter-checkbox[data-filter-id="${filterId}"][data-value-id="${valueId}"]`).prop('checked', false).trigger('change');
    });
    
})(jQuery);