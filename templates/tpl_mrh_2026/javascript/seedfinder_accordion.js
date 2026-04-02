/**
 * Seedfinder Custom Accordion - v7.0.1
 * Datum: 10. Februar 2026
 * Autor: Mr. Hanf / Manus AI
 * 
 * NEU in v7.0.1:
 * - Custom Accordion: Nur EINE Card geöffnet (nicht Bootstrap Default)
 * - Z-Index Overlap: Geöffnete Card überlappt andere
 * - Alle Cards beim Start geschlossen
 * - Sync zwischen Desktop und Mobile
 */

(function() {
    'use strict';

    // Verhindere doppelte Initialisierung
    if (window.SeedfindAccordionInitialized) {
        console.log('⚠️ SeedfindAccordion bereits initialisiert - überspringe');
        return;
    }
    window.SeedfindAccordionInitialized = true;

    // Warte bis jQuery verfügbar ist
    function waitForJQuery(callback) {
        if (typeof jQuery !== 'undefined') {
            console.log('✅ jQuery verfügbar (Accordion)');
            callback(jQuery);
        } else {
            console.log('⏳ Warte auf jQuery (Accordion)...');
            setTimeout(function() {
                waitForJQuery(callback);
            }, 50);
        }
    }

    // Warte bis DOM und jQuery geladen sind
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            waitForJQuery(initAccordion);
        });
    } else {
        waitForJQuery(initAccordion);
    }

    function initAccordion($) {
        const SeedfindAccordion = {
        
        /**
         * Initialisierung
         */
        init: function() {
            console.log('✅ SeedfindAccordion v7.0.1 initialisiert');
            
            // Custom Accordion Behavior
            this.bindCustomAccordion();
            
            // Mobile Bottom Sheet
            this.bindBottomSheet();
            
            // Alle Accordions beim Start schließen
            this.closeAllAccordions();
        },

        /**
         * Custom Accordion Behavior binden
         * Regel: Nur EINE Card geöffnet, mit z-index Overlap
         */
        bindCustomAccordion: function() {
            const self = this;
            
            console.log('🔗 bindCustomAccordion() aufgerufen');
            
            // Desktop Accordion Headers
            const desktopHeaders = $('#filter-accordion-desktop .accordion-filter-header');
            console.log('📊 Desktop Accordion Headers gefunden:', desktopHeaders.length);
            
            desktopHeaders.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $header = $(this);
                const targetId = $header.data('target');
                const $target = $(targetId);
                const isExpanded = $header.attr('aria-expanded') === 'true';
                
                console.log('🎯 Desktop Accordion geklickt:', targetId);
                
                if (isExpanded) {
                    // Schließen wenn bereits geöffnet
                    self.closeAccordionCard($header, $target);
                } else {
                    // Alle anderen schließen
                    self.closeAllDesktopAccordions();
                    
                    // Diese öffnen mit z-index Overlap
                    self.openAccordionCard($header, $target);
                }
            });
            
            // Mobile Accordion Headers
            $('#filter-accordion-mobile .btn-link').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $header = $(this);
                const targetId = $header.data('target');
                const $target = $(targetId);
                const isExpanded = $header.attr('aria-expanded') === 'true';
                
                console.log('🎯 Mobile Accordion geklickt:', targetId);
                
                if (isExpanded) {
                    // Schließen wenn bereits geöffnet
                    self.closeAccordionCard($header, $target);
                } else {
                    // Alle anderen schließen
                    self.closeAllMobileAccordions();
                    
                    // Diese öffnen
                    self.openAccordionCard($header, $target);
                }
            });
        },

        /**
         * Accordion Card öffnen mit z-index Overlap
         */
        openAccordionCard: function($header, $target) {
            console.log('📂 Öffne Accordion Card');
            
            // Header State
            $header.attr('aria-expanded', 'true');
            $header.addClass('active');
            
            // Chevron drehen
            $header.find('.fa-chevron-down')
                .removeClass('fa-chevron-down')
                .addClass('fa-chevron-up');
            
            // Target öffnen
            $target.addClass('show');
            
            // ⭐ Z-Index Overlap für Desktop
            const $item = $header.closest('.filter-accordion-item');
            if ($item.length) {
                $item.css('z-index', 1000);
            }
            
            // Mobile Card Highlight
            const $card = $header.closest('.card');
            if ($card.length) {
                $card.addClass('active');
            }
        },

        /**
         * Accordion Card schließen
         */
        closeAccordionCard: function($header, $target) {
            console.log('📁 Schließe Accordion Card');
            
            // Header State
            $header.attr('aria-expanded', 'false');
            $header.removeClass('active');
            
            // Chevron zurückdrehen
            $header.find('.fa-chevron-up')
                .removeClass('fa-chevron-up')
                .addClass('fa-chevron-down');
            
            // Target schließen
            $target.removeClass('show');
            
            // Z-Index zurücksetzen
            const $item = $header.closest('.filter-accordion-item');
            if ($item.length) {
                $item.css('z-index', '');
            }
            
            // Mobile Card Highlight entfernen
            const $card = $header.closest('.card');
            if ($card.length) {
                $card.removeClass('active');
            }
        },

        /**
         * Alle Desktop Accordions schließen
         */
        closeAllDesktopAccordions: function() {
            const self = this;
            
            $('#filter-accordion-desktop .filter-accordion-header').each(function() {
                const $header = $(this);
                const targetId = $header.data('target');
                const $target = $(targetId);
                
                if ($header.attr('aria-expanded') === 'true') {
                    self.closeAccordionCard($header, $target);
                }
            });
        },

        /**
         * Alle Mobile Accordions schließen
         */
        closeAllMobileAccordions: function() {
            const self = this;
            
            $('#filter-accordion-mobile .btn-link').each(function() {
                const $header = $(this);
                const targetId = $header.data('target');
                const $target = $(targetId);
                
                if ($header.attr('aria-expanded') === 'true') {
                    self.closeAccordionCard($header, $target);
                }
            });
        },

        /**
         * Alle Accordions beim Start schließen
         */
        closeAllAccordions: function() {
            console.log('📁 Schließe alle Accordions beim Start');
            
            // Desktop
            $('#filter-accordion-desktop .collapse').removeClass('show');
            $('#filter-accordion-desktop .filter-accordion-header').attr('aria-expanded', 'false');
            
            // Mobile
            $('#filter-accordion-mobile .collapse').removeClass('show');
            $('#filter-accordion-mobile .btn-link').attr('aria-expanded', 'false');
        },

        /**
         * Mobile Bottom Sheet binden
         */
        bindBottomSheet: function() {
            const $sheet = $('#filter-bottom-sheet');
            const $fab = $('#filter-fab-mobile');
            const $closeBtn = $('#close-bottom-sheet');
            const $overlay = $('.bottom-sheet-overlay');
            
            // FAB öffnet Bottom Sheet
            $fab.on('click', function() {
                console.log('📱 Öffne Bottom Sheet');
                $sheet.addClass('active');
                $('body').addClass('bottom-sheet-open');
            });
            
            // Close Button schließt Bottom Sheet
            $closeBtn.on('click', function() {
                console.log('📱 Schließe Bottom Sheet');
                $sheet.removeClass('active');
                $('body').removeClass('bottom-sheet-open');
            });
            
            // Overlay schließt Bottom Sheet
            $overlay.on('click', function() {
                console.log('📱 Schließe Bottom Sheet (Overlay)');
                $sheet.removeClass('active');
                $('body').removeClass('bottom-sheet-open');
            });
            
            // Swipe Down zum Schließen (Touch Events)
            this.bindSwipeDown($sheet);
        },

        /**
         * Swipe Down zum Schließen des Bottom Sheets
         */
        bindSwipeDown: function($sheet) {
            const $content = $sheet.find('.bottom-sheet-content');
            let startY = 0;
            let currentY = 0;
            let isDragging = false;
            
            $content.on('touchstart', function(e) {
                startY = e.touches[0].clientY;
                isDragging = true;
            });
            
            $content.on('touchmove', function(e) {
                if (!isDragging) return;
                
                currentY = e.touches[0].clientY;
                const deltaY = currentY - startY;
                
                // Nur nach unten ziehen erlauben
                if (deltaY > 0) {
                    $content.css('transform', `translateY(${deltaY}px)`);
                }
            });
            
            $content.on('touchend', function() {
                if (!isDragging) return;
                
                const deltaY = currentY - startY;
                
                // Wenn mehr als 100px nach unten gezogen, schließen
                if (deltaY > 100) {
                    console.log('📱 Schließe Bottom Sheet (Swipe)');
                    $sheet.removeClass('active');
                    $('body').removeClass('bottom-sheet-open');
                }
                
                // Transform zurücksetzen
                $content.css('transform', '');
                isDragging = false;
            });
        }
    };

    // Initialisierung
    SeedfindAccordion.init();
    }

})();


/**
 * ⭐ NEU v7.0.1: Checkbox-Sortierung
 * Sortiert Checkboxen: Checked → Enabled → Disabled
 * Blendet disabled Checkboxen aus (außer checked)
 */
function sortCheckboxes() {
    console.log('='.repeat(60));
    console.log('✅ Checkbox-Sort v7.0.1: Sortiere und blende disabled Items aus');
    console.log('='.repeat(60));
    
    let totalContainers = 0;
    let totalItems = 0;
    
    // Container für Desktop und Mobile
    const desktopContainers = document.querySelectorAll('.filter-values-container');
    const mobileContainers = document.querySelectorAll('#filter-accordion-mobile .card-body');
    
    console.log(`\n📊 Container gefunden:`);
    console.log(`  - Desktop (.filter-values-container): ${desktopContainers.length}`);
    console.log(`  - Mobile (#filter-accordion-mobile .card-body): ${mobileContainers.length}`);
    console.log(`  - GESAMT: ${desktopContainers.length + mobileContainers.length}\n`);
    
    // Kombiniere beide Arrays
    const allContainers = [...desktopContainers, ...mobileContainers];
    
    allContainers.forEach((container, containerIndex) => {
        const checkboxItems = container.querySelectorAll('.form-check');
        
        console.log(`\n${'─'.repeat(60)}`);
        console.log(`Container ${containerIndex}: ${checkboxItems.length} Checkboxen gefunden`);
        
        if (checkboxItems.length === 0) {
            console.warn(`  ⚠️ Container ${containerIndex}: Keine Checkboxen gefunden!`);
            return;
        }
        
        // Konvertiere zu Array für Sortierung
        const itemsArray = Array.from(checkboxItems);
        
        // Debug: VOR Sortierung
        console.log(`\n  📋 VOR Sortierung:`);
        itemsArray.forEach((item, idx) => {
            const checkbox = item.querySelector('input[type="checkbox"]');
            const label = item.querySelector('label');
            const text = label ? label.textContent.trim() : 'NO LABEL';
            const checked = checkbox ? checkbox.checked : false;
            const disabled = checkbox ? checkbox.disabled : false;
            
            const status = checked ? '✓' : (disabled ? 'DIS' : '○');
            console.log(`    [${idx}] ${status} - ${text}`);
        });
        
        // ⭐ SORTIERUNG
        itemsArray.sort((a, b) => {
            const checkboxA = a.querySelector('input[type="checkbox"]');
            const checkboxB = b.querySelector('input[type="checkbox"]');
            
            const checkedA = checkboxA ? checkboxA.checked : false;
            const checkedB = checkboxB ? checkboxB.checked : false;
            const disabledA = checkboxA ? checkboxA.disabled : false;
            const disabledB = checkboxB ? checkboxB.disabled : false;
            
            // Priorität 1: Checked zuerst
            if (checkedA && !checkedB) return -1;
            if (!checkedA && checkedB) return 1;
            
            // Priorität 2: Enabled vor Disabled
            if (!disabledA && disabledB) return -1;
            if (disabledA && !disabledB) return 1;
            
            // Gleiche Priorität: Original-Reihenfolge beibehalten
            return 0;
        });
        
        // Debug: NACH Sortierung
        console.log(`\n  📋 NACH Sortierung:`);
        itemsArray.forEach((item, idx) => {
            const checkbox = item.querySelector('input[type="checkbox"]');
            const label = item.querySelector('label');
            const text = label ? label.textContent.trim() : 'NO LABEL';
            const checked = checkbox ? checkbox.checked : false;
            const disabled = checkbox ? checkbox.disabled : false;
            
            const status = checked ? '✓' : (disabled ? 'DIS' : '○');
            console.log(`    [${idx}] ${status} - ${text}`);
        });
        
        // ⭐ DOM NEU AUFBAUEN
        // Leere Container
        container.innerHTML = '';
        
        // Füge sortierte Items hinzu
        itemsArray.forEach(item => {
            const checkbox = item.querySelector('input[type="checkbox"]');
            const disabled = checkbox ? checkbox.disabled : false;
            const checked = checkbox ? checkbox.checked : false;
            
            // Disabled Items ausblenden (außer checked)
            if (disabled && !checked) {
                item.style.display = 'none';
            } else {
                item.style.display = '';
            }
            
            container.appendChild(item);
        });
        
        totalContainers++;
        totalItems += checkboxItems.length;
    });
    
    console.log(`\n${'='.repeat(60)}`);
    console.log(`✅ Checkbox-Sort abgeschlossen:`);
    console.log(`   - ${totalContainers} Container verarbeitet`);
    console.log(`   - ${totalItems} Items sortiert`);
    console.log(`${'='.repeat(60)}\n`);
}

// Global exportieren
window.seedfinderSortCheckboxes = sortCheckboxes;
