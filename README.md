# Maison Vintique — Elementor + WooCommerce + ACF Build

A luxury editorial wine importer & **B2B trade store** built on **Elementor Pro + WooCommerce + ACF Pro**, delivered as a Hello Elementor **child theme** with importable ACF field groups and Elementor templates.

## Required plugins / theme
| Item | Why |
|------|-----|
| **Hello Elementor** (parent theme) | Lightweight base; this is its child theme |
| **Elementor** + **Elementor Pro** | Page building + Theme Builder (header/footer/single-product), nav menu & cart widgets |
| **WooCommerce** | Wines are products; cart, checkout, accounts |
| **Advanced Custom Fields Pro** | Wine & producer fields (auto-load from `/acf-json`) |

## 1. Install the theme
1. Install & activate **Hello Elementor**.
2. Copy this folder to `wp-content/themes/maison-vintique-elementor` and activate **Maison Vintique (Elementor)**.
   - On activation it registers: the **Producer** post type, all **wine taxonomies**, two WooCommerce global attributes (Bottle Size, Case Format), and the **Trade Customer** role (`mv_trade`).

## 2. Load the ACF fields (already importable)
The field groups live in `/acf-json` and **auto-sync** — go to **Custom Fields → Field Groups**, and if they show as "Sync available", click **Sync**. That creates:
- **Wine Details** → shows on every WooCommerce **product** (producer, vintage, ABV, allergens, case format, MOQ, tasting notes, terroir, technical/sell sheets, **Visibility Tier**, EHD/VCIS SKU…)
- **Producer Details** → shows on the **Producer** post type (history, philosophy, terroir, gallery, founded/hectares/generations…)

*Alternative:* **Custom Fields → Tools → Import**, and upload the two files in `/acf-json`.

## 3. WooCommerce setup
1. Run the WooCommerce setup wizard (currency £, UK, VAT).
2. Add wines as **Products**. Set the price, the **product categories** (Red / White / Rosé), the **wine taxonomies** (country, region, grape, vintage, style, appellation, collection…) and fill the **Wine Details** ACF fields.
3. Set each wine's **Visibility Tier**:
   - `public` → price + Add to Cart for everyone
   - `trade-only / allocation / private` → guests see **"Trade only — Login to view price"**; approved trade users (role `mv_trade`) see price + buy.
   - This gating is handled in `inc/woocommerce.php` (no extra plugin needed for the on/off logic).

## 4. Import the Elementor templates
**Templates → Saved Templates → Import Templates**, then upload the files in `/elementor-templates`:
- `mv-home.json` — full homepage (hero, philosophy, estate partners, trade CTA)
- `mv-header.json` — header (logo, nav menu, search, cart)
- `mv-footer.json` — footer (logo, menus, newsletter)

Then in **Templates → Theme Builder**:
- Assign **Header** → `mv-header`, **Footer** → `mv-footer` (display: entire site).
- Set `mv-home` as your homepage (Settings → Reading → Home page), or open it and "Apply".
- Build a **Single Product** and **Product Archive/Shop** template in Theme Builder using WooCommerce widgets (the gating + ACF fields render automatically). *These are best built in the Theme Builder UI so they bind to live Woo data.*

> After import, **re-select images** in Elementor (the JSON points at the theme's placeholder photos) and pick the site logo (`assets/img/logo-crest.svg`) in each template's Site Logo widget.

## 5. Menus
Appearance → Menus → create **Primary** (Home, Shop, Producers, Journal, Our Story, Trade) and **Footer**, and assign them to the locations the header/footer widgets reference.

## What maps to what
| Requirement | Where |
|---|---|
| Wine catalogue | WooCommerce products + wine taxonomies (`inc/taxonomies.php`) |
| Wine detail fields | ACF `Wine Details` (`acf-json/group_wine_details.json`) |
| Producers | `producer` CPT + ACF `Producer Details` |
| Price on login / trade gating | `inc/woocommerce.php` + ACF `visibility_tier` |
| Minimum order (by the case) | ACF `min_order_qty` → `mve_min_order_qty()` |
| VCIS stock matching | ACF `sku_ehd` (the code matched against EHD's stock CSV) |
| Brand design tokens | `style.css` + `assets/css/style.css` (CSS vars + `.mv-*` classes) |
| Layouts | `/elementor-templates` + Elementor Theme Builder |

## Shop / Archive page (built as PHP, not Theme Builder)
Instead of an Elementor Theme Builder archive, the Shop page ("Wine Portfolio") is a normal
WordPress/WooCommerce PHP template, so it's easy to edit directly in code:

| File | What it does | Edit this to change… |
|---|---|---|
| `woocommerce/archive-product.php` | The whole Shop page: breadcrumb, heading, sort dropdown, grid, pagination. WooCommerce loads this automatically for the shop page — nothing else needs to call it. | Page layout, sort options, "Showing X of Y" text |
| `template-parts/wine-card.php` | **The wine card itself** — badges, title, appellation/vintage, price (or "Trade pricing on login"), the "View Wine" button and the two inline links. Used by BOTH the Shop grid and the homepage "Curated Portfolio" grid, so the two can never drift apart. | Card design, which fields show on a card |
| `template-parts/content-product-wine.php` | Thin wrapper kept so `archive-product.php` keeps working — it just forwards to `wine-card.php`. | Nothing; edit `wine-card.php` instead |
| `template-parts/shop-filters.php` | Sidebar: Search, Category, Price, Availability. Filters by **Product Category** only (see note in `inc/taxonomies.php` on why Country/Region/Grape aren't separate boxes). | Filter options shown in the sidebar |
| `inc/shop-query.php` | Turns the filter form + sort dropdown into an actual WP_Query (category/price/stock filtering, "Vintage: newest" sort). | Filtering/sorting behaviour |
| `assets/css/style.css` (bottom section) | Shop grid, product cards, filters, pagination — uses the same `--mv-*` variables as the rest of the site. | Shop page look & feel |

Trade price-on-login gating is untouched — it still lives entirely in `inc/woocommerce.php`
(`mve_is_gated`, `mve_product_tier`, the three `woocommerce_*` filters). The shop card and
archive template just call normal WooCommerce functions (`get_price_html()`,
`is_purchasable()`) and let those existing filters do the gating, so there's only one place
that logic lives.

## The wine card (homepage portfolio + Shop archive)

Both grids render the same file — `template-parts/wine-card.php`:

| Part of the card | Comes from |
|---|---|
| Left badge | `wine_colour` taxonomy (falls back to the first Product Category) |
| Right badge | ACF `visibility_tier` = `allocation` → "By allocation"; otherwise stock status → "Available" / "Out of stock" |
| Title | Product title |
| Meta line | ACF `appellation` (falls back to the `wine_region` term) + ACF `vintage_year` |
| Price line | Normal WooCommerce price, or "Trade pricing on login" when `mve_is_gated()` says the wine is gated |
| **View Wine** button | Links to the single product page (this replaced the old Add to Cart button) |
| Technical Details | Single product page, Technical tab (`#tab-tech`) |
| Enquire | `?enquire=<id>` on the Shop page — the same pattern the single product page uses |

Every line is optional, so a product with only a title and an image still renders a valid card.

## Producers Grid page

`producers-grid.php` is a **Page Template** — create a page (e.g. "Producers"),
then pick **Producers Grid** under Page Attributes → Template.

| Part of the card | Comes from |
|---|---|
| Image | Featured image → first ACF `producer_media` gallery image → an initials crest block |
| Name | Producer post title |
| "Bordeaux, France" | `producer_region` + `producer_country` taxonomies |
| "Bordeaux Supérieur" | ACF `appellations` |
| "Est. 1868 · family estate" | ACF `established_year` + ACF `estate_note` |
| "View wines →" | Shop page filtered to that estate (`?producer=<id>`, handled in `inc/shop-query.php`) |

The three card fields were added to `acf-json/group_producer.json` under a
**Grid Card** tab — re-sync the field group (Custom Fields → Field Groups →
Sync) after deploying. Header copy for the page lives in the new
`acf-json/group_producers_page.json` group and is entirely optional: leave the
fields blank and the template uses the page's own title and editor content.

## CSS

**All new styles live in `additional-css/mv-additional.css`, which the theme
does NOT enqueue.** Paste its contents into **Appearance → Customize →
Additional CSS**, appending it below whatever is already there. It covers the
wine card, the producers grid, and the My Account responsive fixes, and every
colour falls back to the theme's existing `--ink` / `--taupe` / `--gold` etc.
variables where those are defined.

One block in that file is marked as a **safety net** — the `.card-grid--4` /
`.pgrid` mobile column counts. Delete it if the existing grid CSS already
collapses those grids on mobile.

## Next steps for the developer
1. Build a **Single Product** template the same way (a `single-product.php` following this same pattern), using the Wine Details ACF tabs (Tasting, Terroir, Allergens, Awards, Downloads).
2. Wire trade **pricing** to the Laravel portal (SSO + API) — this theme handles the *gating*; the *price values* for trade users come from Laravel/your B2B pricing plugin.
3. If you want separate Country/Region/Colour/Grape filter boxes back (instead of just Category), re-add those taxonomies in `inc/taxonomies.php`, then copy the "Category" block in `template-parts/shop-filters.php` once per taxonomy and add matching `tax_query` entries in `inc/shop-query.php`.
4. Replace placeholder photography in `assets/img/` with the client's licensed images.

*Companion docs delivered separately: What We Need From You, WordPress Developer Start Guide, Developer Checklist.*
