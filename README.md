# Maison Vintique — Elementor + WooCommerce + ACF Build

> ## ⚠ SECURITY: injected code was found and removed from `functions.php`
>
> The uploaded theme contained a **144 KB obfuscated block** between markers
> `/* SC_TH_BEGIN:4.0.3:c46bfced */` and `/* SC_TH_END:... */` — 92% of the
> file. The theme's real code is about 11 KB.
>
> It used `eval` and `gzinflate` on a 141,000-character blob, and rebuilt the
> names `rename`, `copy`, `unlink`, `chmod` and `opcache_invalidate` character
> by character from a scrambled alphabet so that searching the file for those
> words finds nothing. Writing, renaming and deleting files then clearing the
> opcache is what a self-installing backdoor does.
>
> **It has been removed from this copy of the theme.** That is not a cleanup.
> Injections like this are rarely confined to one file — assume `wp-config.php`,
> `wp-content/uploads`, other themes, plugins, the database `wp_options` table
> and any admin accounts are also affected, and that whatever got in can get
> back in. This is a live shop handling trade accounts, so treat it as a
> compromise: take a backup, get a malware scan across the whole install
> (Wordfence / Sucuri / the host's own scanner), rotate all admin and database
> passwords and any API keys, and check the user list for accounts nobody
> created.
>
> **IT CAME BACK.** The block was removed on 10 August. The theme uploaded on
> 13 August contained it again — same marker, 225 KB this time instead of 144 KB,
> 94% of the file. That is not a leftover; something on the server put it back.
> The site is actively compromised and cleaning the theme alone will not stop
> it. Until the whole install is cleaned, every theme file will keep getting
> re-infected, and a backdoor that rewrites theme files is also a plausible
> reason for fixes appearing not to take effect after deployment.
>
> **Third time: 17 August, 225 KB again.** Removed for a third time. Nothing
> else in that upload had been touched — only `functions.php` — so the theme
> files are landing correctly and this is coming from the server, not from the
> deploys. Please get the install cleaned; this will keep happening.


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
   - On activation it registers: the **Producer** post type, all **wine taxonomies**, and two WooCommerce global attributes (Bottle Size, Case Format).

## 2. Load the ACF fields (already importable)
The field groups live in `/acf-json` and **auto-sync** — go to **Custom Fields → Field Groups**, and if they show as "Sync available", click **Sync**. That creates:
- **Wine Details** → shows on every WooCommerce **product** (producer, vintage, ABV, allergens, case format, MOQ, tasting notes, terroir, technical/sell sheets…)
- **Producer Details** → shows on the **Producer** post type (history, philosophy, terroir, gallery, founded/hectares/generations…)

*Alternative:* **Custom Fields → Tools → Import**, and upload the two files in `/acf-json`.

## 3. WooCommerce setup
1. Run the WooCommerce setup wizard (currency £, UK, VAT).
2. Add wines as **Products**. Set the price, the **product categories** (Red / White / Rosé), the **wine taxonomies** (country, region, grape, vintage, style, appellation, collection…) and fill the **Wine Details** ACF fields.
3. Pricing visibility needs no per-product setting. There is **one rule**, in `inc/woocommerce.php`:
   - **Logged out** → no prices anywhere, nothing can be added to the basket; cards and the product page show *"Sign in to view trade pricing"*.
   - **Logged in** → prices visible, add to cart and buy work normally, for any account.
   - To carve out an exception, filter `mve_is_gated` — don't add branches.

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
| Price on login / trade gating | `inc/woocommerce.php` (`mve_is_gated` — logged in or not) |
| Minimum order (by the case) | ACF `min_order_qty` → `mve_min_order_qty()` |
| VCIS stock matching | ACF `sku_ehd` (the code matched against EHD's stock CSV) |
| Brand design tokens | `style.css` + the Customizer CSS (see **Where the CSS lives**) |
| Layouts | `/elementor-templates` + Elementor Theme Builder |

## Shop / Archive page (built as PHP, not Theme Builder)
Instead of an Elementor Theme Builder archive, the Shop page ("Wine Portfolio") is a normal
WordPress/WooCommerce PHP template, so it's easy to edit directly in code:

| File | What it does | Edit this to change… |
|---|---|---|
| `woocommerce/archive-product.php` | The whole Shop page: breadcrumb, heading, sort dropdown, grid, pagination. WooCommerce loads this automatically for the shop page — nothing else needs to call it. | Page layout, sort options, "Showing X of Y" text |
| `template-parts/wine-card.php` | **The wine card itself** — badges, award medallion, meta row, title, sub line, price (or "Sign in to view trade pricing"), the "View Wine" button and the two inline links. Used by BOTH the Shop grid and the homepage "Curated Portfolio" grid, so the two can never drift apart. | Card design, which fields show on a card |
| `template-parts/content-product-wine.php` | Thin wrapper kept so `archive-product.php` keeps working — it just forwards to `wine-card.php`. | Nothing; edit `wine-card.php` instead |
| `template-parts/shop-filters.php` | Sidebar: Search, Category, Price, Availability. Filters by **Product Category** only (see note in `inc/taxonomies.php` on why Country/Region/Grape aren't separate boxes). | Filter options shown in the sidebar |
| `inc/shop-query.php` | Turns the filter form + sort dropdown into an actual WP_Query (category/price/stock filtering, "Vintage: newest" sort), and keeps a filtered listing on the grid — see below. | Filtering/sorting behaviour |

Price-on-login gating lives entirely in `inc/woocommerce.php` — one helper
(`mve_is_gated()`, which is just "is this visitor logged out?") plus three
`woocommerce_*` filters. The shop card and archive template only call normal
WooCommerce functions (`get_price_html()`, `is_purchasable()`) and let those
filters do the gating, so there is a single place that logic lives.

### Filtering down to one wine

The sidebar carries a search box, so **every** filter click submits `s` — empty
or not — and WordPress treats the result as a search. Core then redirects a
search matching exactly one post straight to that post, which threw the customer
onto the product page the moment their filters narrowed to a single wine,
instead of showing them the one card they had filtered to.

`mve_keep_filtered_shop_on_grid()` switches that redirect off, and only for
filtered product listings — every other canonical redirect on the site
(trailing slashes, old permalinks, pagination) is left alone.
`mve_force_shop_listing()` is the belt-and-braces half, pinning the query to
"this is a listing" in case the one result is promoted to a single post before
the redirect stage. It bails out on anything carrying a real single-post query
var, so opening a product with a stray `?orderby=` on the URL still works.

## The wine card (homepage portfolio + Shop archive)

Both grids render the same file — `template-parts/wine-card.php`:

It is rendered in three places: the homepage portfolio, the Shop archive, and
the "You may also like" grid on the single product page.

| Part of the card | Comes from |
|---|---|
| Bottle image | ACF `bottle_image`, else the featured image. **Never cropped** — see below |
| Left badge | `wine_colour` taxonomy (falls back to the first Product Category) |
| Right badge | Stock status → "Available" / "Out of stock". **Logged-in visitors only** — see below |
| **Award medallion** | ACF `awards` — fades in over the bottle on hover (see below) |
| Meta line, left | **`wine_colour` · `wine_region`** taxonomy terms, joined by a middot |
| Meta line, right | **`wine_country`** taxonomy terms |
| Title | Product title |
| **Sub line** | ACF `producer` · `appellation` `vintage_year` · `case_format` (see below) |
| Price line | Normal WooCommerce price, or "Sign in to view trade pricing" when `mve_is_gated()` says the wine is gated |
| **View Wine** button | Links to the single product page (this replaced the old Add to Cart button) |
| Technical Details | Single product page, Technical tab — links to `?tab=tech#tab-tech`, and the tab script opens that tab on arrival |
| Enquire | `?enquire=<id>` on the Shop page — the same pattern the single product page uses |

Every line is optional, so a product with only a title and an image still renders a valid card.

**Stock is trade information.** Logged-out visitors see no availability badge at
all — the same rule as the price, via `mve_is_gated()`, so a card either shows
trade information or it doesn't. There is no state where the price is hidden but
the stock is on show. The single product page follows the same rule: both the
availability line under the title and the Stock row in the technical table are
hidden until login.

### The award medallion

`awards` is a textarea with **one award per line**:

```
Gold — IWC 2023
92 pts — Decanter
```

Fill in anything at all and the card gets the round **Award Winning** medallion,
faded in over the bottle on hover or keyboard focus. The medallion always reads
"Award Winning" (as designed) so a wine with four awards still gets one clean
mark; the first line becomes its tooltip and is what a screen reader announces.
Wines with no award simply don't get a medallion. On touch devices there is no
hover, so `@media (hover: none)` shows it permanently rather than hiding it
forever.

### Why the bottle is not cropped

The card used to ask WordPress for the `mv-card` image size, which is
registered **hard cropped** to 760×600 (`add_image_size( 'mv-card', 760, 600, true )`
— the `true` is the crop). WordPress cut the top and bottom off every bottle
*when the file was uploaded*, so the whole bottle was already gone from the file
on disk. No amount of CSS could bring it back.

The card now asks for uncropped sizes in this order, using the first one that
exists: **`mv-bottle`** (new, 900×1400 soft) → **`large`** → **`medium_large`** →
the original upload. `mv-card` is still hard cropped and still used by the
producer and journal cards, which *are* landscape photos meant to fill a
landscape box.

`mv-bottle` only exists for images uploaded **after** this change — WordPress
generates image sizes at upload time. That's why `large` and the original are in
the list: everything already on the site keeps working straight away, uncropped.
To get the tighter `mv-bottle` file for older products, run a "Regenerate
Thumbnails" plugin once. It is optional.

### The sub line

The line under the title is built from up to three parts, joined by a middot,
each dropped when its field is empty:

**producer · appellation vintage · case format** — e.g. *Bordeaux Supérieur 2019 · 6 × 75cl*

The producer is **skipped when the title already contains it**, so a wine titled
*Château Toulouse-Lautrec* doesn't read "Château Toulouse-Lautrec · Château
Toulouse-Lautrec · …". When it is shown it links through to that estate's page.

All the fields the card reads — `awards`, `producer`, `case_format`,
`appellation`, `vintage_year` — already exist on the product's **Wine Details**
tab. Nothing new to create; just fill them in per product.

> The design shows the *RED · BORDEAUX / FRANCE* line in a wine red. This site's
> accent token (`--mv-burg`) is the deep green `#17251f`, so that line is driven
> by its own variable — change `--mv2-card-meta` at the top of section 18 to
> recolour both halves of the row in one place.

## Producers

### Estate logos

Each estate has its own logo on the back of its card, from **Producer → Grid
Card → Estate Logo** (ACF `producer_logo`). It shows on the homepage "Estate
Partners" carousel and on the Producers grid — both call `mve_producer_logo()`
in `functions.php`, so an estate's logo is only ever set in one place.

Use a PNG or SVG with a **transparent background**; the card behind it is dark.
Any shape works — the CSS caps the height and lets a wide wordmark use the width
it needs, without cropping or stretching.

Leave it empty and the card falls back to the house crest, which is what every
card used to show, so an estate with no logo yet still looks finished. To change
that fallback, edit `mve_producer_logo()` or filter `mve_default_estate_logo`.

### Two routes to the grid

There are **two** routes to the producers grid, and both render the same card
(`template-parts/producer-card.php`):

1. **`/producers/`** — served by `archive-producer.php`. The producer post type
   is registered with `has_archive => true`, so this URL exists automatically;
   before this file was added it fell through to the *parent* theme's generic
   `archive.php` and came out as a plain blog-style list.
2. **`producers-grid.php`** — a **Page Template**, if you'd rather have a real
   editable Page. Create a page, then pick **Producers Grid** under Page
   Attributes → Template.

The cards **flip on hover**, the same way the homepage "Estate Partners" cards
do — front is the estate photo, back is the crest, the estate's initials in a
ring, and "Est. 1868" (falling back to the region when there's no year).

They use their own `mvprod__*` class names rather than the homepage's
`estate-card__*` ones. Sharing those names meant the homepage's estate-card
rules also landed on these cards and fought with them, which stopped the front
photo from showing. Separate names, no collision.

### Single producer page

`single-producer.php` renders one estate. **This was previously missing** — the
producer post type is public, so every estate has its own URL, and without this
file those URLs fell through to the *parent* theme's `single.php`, which shows
only the title and editor content. Since all of an estate's content lives in
ACF fields, the page came out essentially blank.

| Section | Comes from |
|---|---|
| Header | Title, `producer_region` + `producer_country`, and the editor content as the intro |
| Facts row | ACF `established_year`, `appellations`, `estate_note` |
| Story | ACF `producer_history`, `producer_philosophy`, `producer_terroir`, `producer_sustainability` |
| Gallery | ACF `producer_media` |
| Wines from this estate | Products whose ACF `producer` is this estate, rendered with the shared wine card |

Every section is skipped when its field is empty.

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

## Editorial pages

| Page | Template | Pick it under |
|---|---|---|
| Our Story | `template-our-story.php` | Page Attributes → Template |
| **Trade Partners** | `template-for-the-trade.php` | Page Attributes → Template |
| FAQ | `template-faq.php` | Page Attributes → Template |
| Contact | `template-contact.php` | Page Attributes → Template |
| Journal | `archive-journal.php` | automatic, at `/journal/` |
| One journal entry | `single-journal.php` | automatic |

**"For the Trade" is now "Trade Partners."** Only the label changed — in the
Template dropdown, in the field group name, and in the page's default eyebrow.
The **file name stays `template-for-the-trade.php` on purpose**: WordPress
stores the *filename* against a page, not the display name, so renaming the file
would silently detach any page already using it and drop that page back to the
default template. Nothing to re-pick after deploying.

All the copy on these pages is ACF — see `acf-json/group_our_story.json`,
`group_for_the_trade.json`, `group_faq.json` and `group_contact.json`. Every
field is optional and every block is skipped when empty, so a half-filled page
still renders cleanly; where a field is blank the template falls back to the
page's own title and editor content.

The dark banner at the top of each is one shared partial,
`template-parts/page-hero.php`, so the five pages can't drift apart.

**FAQ** is built from a two-level repeater (groups → questions) and renders as
native `<details>`/`<summary>` — the accordion works with no JavaScript and
stays keyboard- and screen-reader friendly.

**Contact** has a real working form. It posts to `admin-post.php` (so it works
without JavaScript), is nonce-checked, honeypot- and rate-limited, and emails
the address in the page's *Send Enquiries To* field, falling back to the site
admin. Hook `mve_contact_submitted` to push enquiries into a CRM. See
`inc/contact-form.php`.

### The hero background (image or video)

Both pages can put a photo or a video behind the banner at the top. Under a
**Hero Background** tab on the page:

| Field | What it does |
|---|---|
| Hero Background Image | The still behind the title. Use a wide file, 1920px or more |
| Hero Background Video | An MP4 that plays behind the title, muted and looped |
| Background Darkness | 0–100, how dark the tint over it is. Blank = 55 |

Leave all three empty and the hero renders exactly as before — the flat dark
panel. Nothing to change on the pages that don't use it.

The darkness control matters: white text over an untinted photo is unreadable,
and how much tint a photo needs depends entirely on the photo. 55 is a safe
default; a bright or busy image wants more, a dark one less.

Set **both** an image and a video and the image becomes the video's poster —
what shows while the video buffers, on the phones that decline to autoplay at
all, and for anyone browsing with reduced motion turned on (the video is hidden
for them, the still is not).

### The text + image / video section

The "about us" style section — copy on one side, media on the other. Both pages
have one, under a **Text + Image / Video** tab.

| Field | What it does |
|---|---|
| Eyebrow | Small gold line above the heading |
| Heading | |
| Text | The copy beside the media |
| Button Label / Link | Optional; no label means no button |
| Media Side | Which side the image or video sits on — Right (default) or Left |
| Media Type | *Image*, *Video file (uploaded)* or *Video embed (YouTube / Vimeo)* |
| Image | Used when the type is Image |
| Video File | An `.mp4` you upload to the Media Library |
| Video Poster | Still shown before an uploaded video loads |
| Video Embed URL | Paste a YouTube or Vimeo link — WordPress turns it into a player |
| Caption | Optional line under the media |

Field names are prefixed per page: `os_split_title`, `os_image`, … on Our Story
and `ft_split_title`, `ft_image`, … on Trade Partners.

Four things worth knowing:

- **Fill in only one half and it runs the full width** — text with no image
  doesn't leave a blank column, and vice versa.
- **Leave the whole thing blank and the section disappears** — no empty box, no
  gap.
- **On mobile it stacks with the copy first**, whichever side you picked. Media
  Side is a desktop choice; on a phone, text-then-picture always reads better.
- **Uploaded videos autoplay muted and loop**, with controls, because browsers
  refuse to autoplay anything with sound. Embeds are locked to 16:9 whatever the
  provider hands back.

The media half is `template-parts/page-media.php` in "bare" mode — the same file
that renders the full-width block — so the two can't drift apart. If the Media
Type doesn't match what's actually filled in, it falls back to whatever it can
render rather than showing nothing.

## Where the CSS lives

**Appearance → Customize → Additional CSS** holds the site's styling.

The file to paste is `additional-css/maison-vintique-site.css`. Paste the
**whole** file, replacing everything already in Additional CSS.

The theme's own stylesheets are all still here and still enqueued:

| File | What it is |
|---|---|
| `style.css` | Child-theme header plus a few brand primitives |
| `assets/css/style.css` | Enqueued after `style.css`. Currently empty — a place for theme-side CSS if you ever want it |
| `additional-css/_site-base.css` | Your original Customizer CSS, kept as the build input |
| `additional-css/mv-additional.css` | Only what this build added, commented, for review |
| `additional-css/mv-customizer-full.css` | The older generated combined file |
| `additional-css/mv-add-this-round.css` | A round's additions on their own |
| `additional-css/build-full.php` | Regenerates the combined file from the two sources |

Nothing in `additional-css/` is enqueued — those are build sources, not
stylesheets the browser loads. They are kept so a later round can rebuild
cleanly instead of hand-merging.

Two things follow from this:

- **Edit it in the Customizer, and keep a copy.** The Customizer is the live
  source of truth. If you hand-edit there (crest sizes, spacing, the wordmark —
  all of which you have), send the current contents back before asking for
  changes, otherwise a regenerated file overwrites them.
- **Order matters.** Everything is one file, so a rule later in the file beats
  an identical-specificity rule earlier. The fixes are appended at the bottom
  under `FIXES — ADDED THIS ROUND` for exactly that reason.

### Colours and fonts

Every component uses the site's own brand variables — `--mv-ink`, `--mv-burg`,
`--mv-gold`, `--mv-serif`, `--mv-sans` and friends — through the `--mv2-*`
aliases near the top of the file. Nothing hard-codes a brand colour, so editing
the `:root` block restyles everything.

> Note `--mv-burg` is the deep green `#17251f`, not a burgundy — the name is
> historical.

## Scroll reveal

Sections fade up as you scroll. The animation is the site's own — `_site-base.css`
sets `.section { opacity: 0 }` and `.section.is-visible { opacity: 1 }` — and the
script that adds `is-visible` lives at the bottom of `footer.php`.

That script was rewritten because the page below the hero arrived late. Three
things were wrong:

1. **It waited for the section to be 15% on screen** (`threshold: 0.15`, plus a
   `-50px` bottom margin) before *starting* a 0.8s fade. So you scrolled, saw a
   gap, and the content caught up afterwards. Worse: a section taller than about
   six viewports can never show 15% of itself at once, so it would never have
   revealed at all.
2. **It waited for `DOMContentLoaded`**, so sections already on screen at load
   faded in from nothing instead of simply being there.
3. **No failsafe.** One JavaScript error anywhere earlier and everything below
   the hero stayed invisible permanently.

Now: whatever is on screen at load is shown immediately with no animation,
everything else starts fading 200px *before* it scrolls into view, and with
JavaScript off the CSS shows everything (`@media (scripting: none)`).

It sweeps element positions on scroll rather than using an IntersectionObserver.
That is deliberate: jump straight down the page — an anchor link, the End key, a
restored scroll position — and a section can go from below the viewport to above
it between two frames. IntersectionObserver reports nothing for that (it wasn't
intersecting before and isn't now), so the section stays invisible and you find
it blank on the way back up. A position check can't miss it. The listener
detaches itself once every section has been revealed.

The timing is tuned in **section 20** of the CSS — 0.5s instead of 0.8s, 18px of
travel instead of 35px, and `will-change` no longer parked on every section for
the life of the page. Delete that section to go back to the original feel.

## Menus

Every menu location this theme uses is registered in one place —
`register_nav_menus()` inside `mve_setup()` in `functions.php`:

| Location | Where it renders |
|---|---|
| Primary Menu (header) | `header.php` |
| Footer — Explore (column 2) | `footer.php`, second column |
| Footer — Trade (column 3) | `footer.php`, third column |
| Footer — Legal (bottom bar) | `footer.php`, bottom bar |

To use one: **Appearance → Menus**, build the menu, then tick its **Display
location**. Nothing else is needed.

Until a menu is assigned, the two footer columns fall back to a sensible
default link list (see `mve_footer_menu()` in `inc/mv-footer.php`) so the
footer never renders as an empty gap — as soon as you assign a menu, it takes
over.

## Footer social icons

### Where to add the links

**Appearance → Customize → Social Links.**

Paste the full URL into the box for each network and click Publish. The icon
appears in the footer the moment there's a URL in the box; leave a box empty and
that icon simply isn't rendered. Six boxes ship out of the box:

**Instagram**, Facebook, X (Twitter), LinkedIn, Pinterest, YouTube.

Nothing is hard-coded in `footer.php` any more — it asks `mve_social_links()`
for whatever has a URL and draws only those. If none of the six has a URL, the
whole social row is skipped rather than rendering an empty strip.

### Adding another network later

One entry in `mve_social_networks()` in `inc/social.php` — the Customizer box,
the icon and the footer link all follow from it automatically:

```php
'tiktok' => array(
    'label' => __( 'TikTok', 'maison-vintique-elementor' ),
    'path'  => '<path d="…" fill="currentColor"/>',   // 24 × 24 viewBox
),
```

The `path` is the *inside* of a `<svg viewBox="0 0 24 24">` — copy it straight
out of any icon set. Icons are inlined rather than loaded from a font or a CDN,
so they inherit the footer's colour and cost no extra request.

If you'd rather not touch PHP at all, the same list is filterable — add to it
from a plugin or a snippet with `add_filter( 'mve_social_networks', … )`.

## Cart / Checkout / Login

| Template | Page |
|---|---|
| `woocommerce/cart/cart.php` | Basket |
| `woocommerce/cart/cart-empty.php` | Empty basket |
| `woocommerce/checkout/form-checkout.php` | Checkout |
| `woocommerce/myaccount/form-login.php` | Trade Login (logged-out `/my-account/`) |

These follow the approved prototype markup but keep WooCommerce's own fields,
hooks and nonces, so quantity updates, coupons, shipping, gateways, validation
and login all behave normally.

The three trade extras on checkout — **PO reference**, **Requested delivery
date** and **Delivery instructions** — are registered as real WooCommerce
checkout fields in `inc/woocommerce.php`, saved to the order, and shown on the
order screen in wp-admin.

## Emails

### Where do I put the address that receives them?

**WooCommerce → Settings → Emails**, the very first box: *Send shop
notifications to*. One address, or several separated by commas. Everything the
shop is told about goes there:

- a new trade account application
- a new order, and a new order request
- cancelled and failed orders

Leave it empty and it uses the WordPress admin email. Each individual email
still has its own **Recipient** box further down that screen — anything typed
there wins for that one email, so a specific address can go to accounts while
everything else goes to the office.

Emails **to a customer** always go to the customer. There is nothing to set.

### Where do I edit the wording?

**WooCommerce → Settings → Emails**. Every email is listed with its own on/off
switch, subject line and heading. The body copy lives in
`inc/emails/class-mve-emails.php` — one small class each, only the words.

### How do I see one without placing an order?

**WooCommerce → Emails**. Pick any email, look at it rendered, and send a real
copy to any address you type in. Try one Gmail address and one Outlook address —
they filter very differently.

### Nothing is arriving

The same screen answers this. The panel at the top reports what the site is
actually doing, and the two causes below account for nearly every case.

**1. WordPress is asking the web server to send mail directly.** Most hosts
block PHP's `mail()` outright, and mail from a hosting IP without authentication
is filed as spam by Gmail and Outlook whatever it says. **Install WP Mail SMTP
or FluentSMTP and point it at a real mail service** — Brevo, Postmark, SendGrid,
Google Workspace or your own mailbox. Until that is done, no email from the site
is reliable, however good the template looks.

**2. The "from" address is on someone else's domain.** WooCommerce → Settings →
Emails → *Email sender options*. An address on gmail.com or on the host's domain
is treated as forged. Use one on your own domain, and add SPF and DKIM records
for it — your mail provider gives you both.

Everything the site sends is recorded, so the panel can tell you whether a
message left WordPress at all. If it says it was handed over and nothing
arrived, it was accepted and then filtered — that is the mail service's log to
check, not the theme.

### What gets sent, and to whom

The trade-workflow ones are listed in full in
[TRADE-WORKFLOW.md](TRADE-WORKFLOW.md). In summary:

| When | To the customer | To the shop |
|---|---|---|
| Trade application submitted | Application received | New trade application |
| Application approved | Your trade account is open | — |
| More information needed / declined | About your application | — |
| First sign-in after approval | Welcome | — |
| Order placed and paid | WooCommerce's Processing | WooCommerce's New order |
| Order placed, awaiting approval | Order request received | New order request |
| Marked Shipped | Your wine is on its way | — |
| Order completed / cancelled / refunded | WooCommerce's own | WooCommerce's own |
| Proforma / chasing payment | Invoice, Payment required, Payment reminder — sent by hand from the order screen's Actions box | — |
| Password or account details changed | Security notice | — |
| Waitlisted wine back in stock | Back in stock | — |

The "order request" pair exists because WooCommerce sends **nothing at all**
while an order sits at *pending payment* — its emails hang off the move out of
that status, which a payment gateway normally does immediately. A proforma flow
has no gateway, so both sides would otherwise be told nothing. It is decided
after checkout has finished, and only for orders nothing else emailed about, so
a normal paid order never gets a duplicate.

## Trade pricing popup

Appearance → Customize → **Trade Pricing Popup** — wording, button, delay, how
long it stays dismissed, and an optional decorative background image.

**"I can't see the popup."** Almost always this: it is for visitors who are
**not signed in**, and you are signed in to wp-admin in the same browser, so it
is doing exactly what it should. Two ways to see it yourself:

- tick **"Show it to signed-in users too (for testing)"** in that Customizer
  section — remember to untick it when you're done; or
- add `?mv_popup=preview` to any address. That forces it for one page view,
  ignores the "already dismissed" flag, and does not set one.

It is also deliberately never shown on the account, basket or checkout pages.

Once dismissed it stays dismissed for the number of days set in the Customizer.
That flag lives in the browser, not the database — to clear it, run
`localStorage.removeItem('mvTradePopupDismissed')` in the console.

## Trade Account Application

**The whole workflow is written up in [TRADE-WORKFLOW.md](TRADE-WORKFLOW.md)** —
who reviews what, which screen, which email goes to whom, and how to change any
of it. Read that one. This is the short version for developers.

The client's rule is that the full application must never be public:

```
Apply for a Trade Account  ->  short enquiry  ->  YOU REVIEW
   ->  private expiring link  ->  full application  ->  YOU REVIEW
   ->  portal activated
```

**Stage one — the short enquiry.** The Contact page. `inc/contact-form.php`
renders it from `mve_enquiry_fields()` and stores each submission as a
`mv_enquiry` post. Everything that says "Apply for a Trade Account" anywhere on
the site resolves through `mve_apply_url()`, which returns the enquiry page and
never the application.

**The gate.** Approving an enquiry mints a token — unique, expiring (21 days by
default), single-use, replaced on re-issue — and emails the link. The
application page is `noindex`, excluded from site search and the sitemap, and
renders a "by invitation" panel to anybody without a live token. See
`mve_check_invite()` and `mve_application_invited()`.

**Stage two — the full application.** Nine sections, defined once in
`mve_application_schema()`; the form, the validation, the storage and the admin
display all read that one array. Answers from the enquiry are pre-filled by
`mve_application_prefill()`. To add a field:

```php
add_filter( 'mve_application_schema', function ( $schema ) {
    $schema['business']['fields']['sic_code'] = array(
        'label' => 'SIC code',
        'type'  => 'text',
        'width' => 'half',
    );
    return $schema;
} );
```

Field types: `text`, `email`, `tel`, `url`, `textarea`, `date`, `number`,
`select`, `radio`, `checkgroup`, `checkbox`, `file`, `users`. Add
`'controls' => 'some-group'` to a radio and `'group' => 'some-group'` to the
questions that depend on it, and they stay hidden until the answer calls for
them; `'when' => 'no'` flips that round.

**Portal activation.** `mve_block_unapproved_login()` refuses sign-in until the
status is `approved`, with a message that matches the actual status. Scoped to
accounts that have `mve_app_submitted` on file — legacy customers and anyone
who can edit posts are never affected. Turn it off with
`add_filter( 'mve_require_approval_to_sign_in', '__return_false' );`.

**Hooks for a CRM:** `mve_enquiry_status_changed`, `mve_contact_submitted`,
`mve_trade_application_received`, `mve_trade_status_changed`.

## Next steps for the developer
1. Build a **Single Product** template the same way (a `single-product.php` following this same pattern), using the Wine Details ACF tabs (Tasting, Terroir, Allergens, Awards, Downloads).
2. Wire trade **pricing** to the Laravel portal (SSO + API) — this theme handles the *gating*; the *price values* for trade users come from Laravel/your B2B pricing plugin.
3. If you want separate Country/Region/Colour/Grape filter boxes back (instead of just Category), re-add those taxonomies in `inc/taxonomies.php`, then copy the "Category" block in `template-parts/shop-filters.php` once per taxonomy and add matching `tax_query` entries in `inc/shop-query.php`.
4. Replace placeholder photography in `assets/img/` with the client's licensed images.

*Companion docs delivered separately: What We Need From You, WordPress Developer Start Guide, Developer Checklist.*
