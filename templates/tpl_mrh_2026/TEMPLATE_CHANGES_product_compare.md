# ProductCompare v2.0.0-mrh2026 - Template-Aenderungen

## Uebersicht

In v2.0.0-mrh2026 werden die Vergleichen-Buttons direkt in den Smarty-Templates platziert.
Alle Klassen sind auf **Bootstrap 5.3.0** und **Font Awesome 7 Pro** angepasst.

Es gibt **3 Stellen** wo Aenderungen noetig sind:

---

## 1. Produktseite: Vergleichen-Button einfuegen

**Datei:** `templates/tpl_mrh_2026/module/product_info/product_info_tabs_v1.html`

**Suche** den kleinen Merkzettel-Button in der unteren Button-Leiste und **ersetze** oder **fuege danach** ein:

```smarty
{if $smarty.const.MODULE_PRODUCT_COMPARE_STATUS == 'true'}<div class="col-sm-6 mb-2"><button type="button" class="btn btn-compare btn-info btn-xs btn-block" data-product-id="{$PRODUCTS_ID}" onclick="event.preventDefault(); window.ProductCompare && window.ProductCompare.toggle('{$PRODUCTS_ID}', this);"><span class="fa-solid fa-scale-balanced me-1"></span><span>Vergleichen</span></button></div><div class="clearfix"></div>{/if}
```

---

## 2. Seedfinder-Karten: Vergleichen-Button hinzufuegen

**Datei:** `templates/tpl_mrh_2026/module/seedfinder_product_cards.html`

**Fuege NACH** dem Details-Button ein:

```smarty
{if $smarty.const.MODULE_PRODUCT_COMPARE_STATUS == 'true'}
<div class="mt-2"><button type="button" class="btn btn-compare btn-outline-secondary btn-sm btn-block" data-sku="{$product.PRODUCTS_MODEL}" onclick="event.preventDefault(); window.ProductCompare && window.ProductCompare.toggle('{$product.PRODUCTS_MODEL}', this);"><span class="fa-solid fa-scale-balanced me-1"></span><span>Vergleichen</span></button></div>
{/if}
```

---

## 3. Standard-Produktlisten: Vergleichen-Button hinzufuegen (optional)

**Datei:** `templates/tpl_mrh_2026/module/includes/product_info_include.html`

**Fuege NACH** dem Merkzettel-Button ein:

```smarty
{if $smarty.const.MODULE_PRODUCT_COMPARE_STATUS == 'true'}<button type="button" class="btn btn-compare btn-outline-secondary btn-xs" data-product-id="{$module_data.PRODUCTS_ID}" onclick="event.preventDefault(); window.ProductCompare && window.ProductCompare.toggle('{$module_data.PRODUCTS_ID}', this);"><span class="fa-solid fa-scale-balanced"></span></button>&nbsp;&nbsp;{/if}
```

---

## Zusammenfassung: BS4 → BS5.3 Aenderungen

| Alt (BS4) | Neu (BS5.3) | Beschreibung |
|-----------|-------------|--------------|
| `mr-1`, `mr-2` | `me-1`, `me-2` | margin-right → margin-end |
| `ml-2` | `ms-2` | margin-left → margin-start |
| `text-right` | `text-end` | Text-Ausrichtung |
| `bg-white` | `bg-body` | Hintergrundfarbe |
| `fa fa-balance-scale` | `fa-solid fa-scale-balanced` | Waage-Icon |
| `fa fa-check` | `fa-solid fa-check` | Haekchen-Icon |
| `fa fa-times` | `fa-solid fa-xmark` | Schliessen-Icon |
| `fa fa-arrow-left` | `fa-solid fa-arrow-left` | Zurueck-Pfeil |
| `fa fa-trash` | `fa-solid fa-trash` | Papierkorb-Icon |

## Vanilla JS Status

Das JavaScript war bereits 100% Vanilla JS (kein jQuery). Keine JS-Migration noetig.
