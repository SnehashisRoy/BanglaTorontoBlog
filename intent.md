# Intent: Vendor Marketplace + Blog Tab

## Summary

Move the blog off the home page and into a `Blog` nav tab. The home page becomes a
product feed showing products from multiple vendors. No cart/checkout in this phase —
Buy/Inquire simply surfaces the vendor's contact details. There is a vendor dashboard
to manage products, and a public shop page per vendor.

Decisions locked in:
- **Vendors are fully separate from the existing `companies` directory** — no FK,
  no shared table. `companies` stays exactly as-is (read-only business directory).
- **Self-signup, live immediately** — any logged-in user can register as a vendor
  and their shop goes live right away, no admin approval step.
- **Products are single-language** (no `product_translations` table like posts have).
  Product pages still render under the site's normal layout so blog/business nav
  still respects locale, but product content itself is typed once.

---

## Current state (for context)

- Public site (`/`, `/blog`, `/businesses`) is server-rendered **Blade + Tailwind**,
  not Inertia/React. Inertia/React is only used for auth, settings, and `/dashboard`.
  (CLAUDE.md's tech stack section is out of date on this point.)
- `Route::redirect('/', '/bn/blog')` — home currently just bounces to the blog.
- Blog: `Post` / `PostTranslation` / `Category`, `PostRepository`, `Blog\PostController`,
  admin CRUD under `Admin\PostController` + `EnsureUserIsAdmin` middleware.
- `companies` table + `Company` model + `CompanyRepository` + `Blog\CompanyController`
  power `/businesses` — a flat, unowned directory (category slug, company slug, contact
  text field). Not touched by this plan.
- `deals` table exists (migration only, no model/controller/route) — unused, left alone.
- Shared public nav lives in `resources/views/layouts/blog.blade.php`.
- Admin layout: `resources/views/layouts/admin.blade.php`.

---

## Data structure

### `vendors`
One shop per user.

| column | type | notes |
|---|---|---|
| `id` | id (bigint) | |
| `user_id` | FK → `users.id`, **unique**, `cascadeOnDelete` | enforces one shop per account |
| `name` | string(255) | shop name |
| `slug` | string(191), **unique** | URL key, generated from name |
| `description` | text, nullable | "about this shop" |
| `logo_path` | string, nullable | stored on `public` disk under `vendors/logos/` |
| `phone` | string(50) | required — shown on Buy/Inquire |
| `email` | string(255), nullable | |
| `whatsapp` | string(50), nullable | |
| `address` | text, nullable | shown on Buy/Inquire |
| `city` | string(120), nullable | |
| `website` | string(255), nullable | |
| `is_active` | boolean, default `true` | admin kill switch (soft-hide a shop without deleting it) |
| `created_at`, `updated_at` | timestamps | |

Indexes: unique `slug`, unique `user_id`.

### `product_categories`
Deliberately separate from the blog's `categories` table — different taxonomy, different
lifecycle, and blog categories are curated by admins while product categories may end up
vendor-suggested later.

| column | type | notes |
|---|---|---|
| `id` | id | |
| `name` | string(120) | |
| `slug` | string(120), unique | |
| `created_at`, `updated_at` | timestamps | |

### `products`

| column | type | notes |
|---|---|---|
| `id` | id | |
| `vendor_id` | FK → `vendors.id`, `cascadeOnDelete` | |
| `product_category_id` | FK → `product_categories.id`, nullable, `nullOnDelete` | |
| `name` | string(255) | |
| `slug` | string(191) | **unique per vendor**: `unique(['vendor_id', 'slug'])`, not globally unique |
| `description` | text, nullable | |
| `price` | decimal(10,2), nullable | `null` renders as "Contact for price" |
| `status` | enum-like string `draft` \| `published`, default `draft` | mirrors `posts.status` |
| `created_at`, `updated_at` | timestamps | |

Indexes:
- `unique(['vendor_id', 'slug'])`
- `index(['status', 'created_at'])` — powers the home feed (published, newest first)
- `index(['vendor_id', 'status'])` — powers the shop page

### `product_images`

| column | type | notes |
|---|---|---|
| `id` | id | |
| `product_id` | FK → `products.id`, `cascadeOnDelete` | |
| `path` | string | stored on `public` disk under `products/` |
| `sort_order` | unsigned smallint, default `0` | lowest = primary/cover image |
| `created_at`, `updated_at` | timestamps | |

Index: `(product_id, sort_order)`.

### Not building yet (explicitly out of scope)

- **Cart / order tables** — none, per your instruction.
- **`product_inquiries`** — Buy/Inquire has no persistence in this phase; it just
  reveals `vendors.phone` / `address` / `whatsapp`. Adding a leads table later is a
  clean additive change, nothing above depends on its absence.
- **`product_translations`** — single-language products for now; if bilingual is
  needed later, mirror `post_translations` (`product_id`, `language`, `name`,
  `description`, unique on `(product_id, language)`).
- **Vendor approval workflow** — signup is instant; no `status`/`pending` column
  on `vendors` beyond the `is_active` admin kill switch.

---

## Models

- `Vendor` — `belongsTo(User)`, `hasMany(Product)`, `logoUrl()` (mirrors `Post::imageUrl()`),
  `scopeActive()`.
- `Product` — `belongsTo(Vendor)`, `belongsTo(ProductCategory)`, `hasMany(ProductImage)`,
  `scopePublished()` (mirrors `Post::scopeHasTranslation`), `primaryImageUrl()`.
- `ProductImage` — `belongsTo(Product)`.
- `ProductCategory` — `hasMany(Product)`.
- `User` — add `vendor(): HasOne`.

---

## Routes

Products/vendors sit outside the `{locale}` prefix (like `/businesses` already does),
since product content is single-language. Blog keeps its existing locale-prefixed routes
unchanged — it just becomes a nav tab instead of the thing `/` redirects to.

```php
// Public
Route::get('/', [HomeController::class, 'index'])->name('home');               // was: redirect to /bn/blog
Route::get('/shops', [ShopController::class, 'index'])->name('shops.index');
Route::get('/shops/{vendor}', [ShopController::class, 'show'])->name('shops.show');
Route::get('/shops/{vendor}/{product}', [ProductController::class, 'show'])->name('products.show');

// Blog — unchanged
Route::redirect('/blog', '/en/blog');
Route::prefix('{locale}')->where(['locale' => 'en|bn'])->middleware('setlocale')->name('blog.')->group(...);

// Businesses — unchanged
Route::prefix('businesses')->name('companies.')->group(...);

// Vendor onboarding — auth only
Route::middleware('auth')->group(function () {
    Route::get('/vendor/register', [VendorRegistrationController::class, 'create'])->name('vendor.register');
    Route::post('/vendor/register', [VendorRegistrationController::class, 'store']);
});

// Vendor dashboard — auth + must own a vendor
Route::prefix('vendor')->name('vendor.')->middleware(['auth', 'vendor'])->group(function () {
    Route::get('/', [VendorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/shop', [VendorShopController::class, 'edit'])->name('shop.edit');
    Route::put('/shop', [VendorShopController::class, 'update'])->name('shop.update');
    Route::resource('products', Vendor\ProductController::class)->except(['show']);
});

// Admin — unchanged, existing 'admin' middleware
```

`{vendor}` / `{product}` route params bind by slug (route-model binding via
`getRouteKeyName()` on `Vendor`/`Product`, scoped to the vendor for products).

---

## Middleware & authorization

- **`EnsureUserIsVendor`** (new, mirrors `EnsureUserIsAdmin`): 403 unless
  `$request->user()?->vendor` exists. Registered as `vendor` alias.
- **`ProductPolicy`**: `update`/`delete` allowed only when `$product->vendor_id === $user->vendor->id`.
  Applied in `Vendor\ProductController` via `$this->authorize(...)`. This is the one
  place a bug would let a vendor edit someone else's listing, so it gets a dedicated
  feature test.

---

## Form Requests

- `StoreVendorRequest` / `UpdateVendorRequest` — validates shop name, phone (required),
  email/website format, image mimes for logo.
- `StoreProductRequest` / `UpdateProductRequest` — validates name, price (numeric,
  nullable), status (`in:draft,published`), category (`exists:product_categories,id`,
  nullable), images (array of `image`, max count/size). `authorize()` checks the
  request user owns a vendor.

---

## Repositories

- **`VendorRepository`**
  - `activeWithProductCounts()` — for `/shops` directory listing
  - `findActiveBySlug(string $slug): Vendor`
  - `createForUser(User $user, array $data): Vendor`
  - `updateForUser(Vendor $vendor, array $data): Vendor`

- **`ProductRepository`**
  - `publishedFeed(array $filters, int $perPage): LengthAwarePaginator` — home page,
    filters: `category`, `search`, `vendor`
  - `findPublished(string $vendorSlug, string $productSlug): Product`
  - `forVendor(Vendor $vendor): LengthAwarePaginator` — vendor's own product list (all statuses)
  - `createForVendor(Vendor $vendor, array $data): Product`
  - `updateForVendor(Product $product, array $data): Product`
  - `syncImages(Product $product, array $uploadedFiles, array $deleteIds = []): void`

---

## Views (Blade, matching existing site — not Inertia)

```
resources/views/
  home.blade.php                    product grid: image, name, vendor name, price/"Contact"
                                     filters: category chips + search box
  shops/index.blade.php             vendor directory grid
  shops/show.blade.php              shop header (logo, name, address) + that vendor's products
  products/show.blade.php           gallery, price, description, Buy/Inquire button
                                     → reveals vendor contact panel (phone/whatsapp/address)
  vendor/register.blade.php         become-a-vendor form
  vendor/dashboard.blade.php        stats + quick links
  vendor/shop.blade.php             edit shop profile
  vendor/products/index.blade.php
  vendor/products/create.blade.php
  vendor/products/edit.blade.php
  layouts/public.blade.php          renamed from layouts/blog.blade.php — shared public nav
  layouts/vendor.blade.php          new, modelled on layouts/admin.blade.php
```

### Nav changes (`layouts/blog.blade.php` → `layouts/public.blade.php`)

- Logo link: `route('blog.index', ...)` → `route('home')`
- Tabs become: **Shop** (home) / **Shops** / **Blog** / **Businesses**
- `@auth` + `$user->vendor` → show "My Shop" link to `vendor.dashboard`
- `@auth` + no vendor → show "Sell on [site]" link to `vendor.register`
- Locale switcher (`$showLocaleSwitch`) stays conditional — only rendered on blog pages,
  since product/shop pages are single-language
- Admin link logic unchanged

Every other Blade view (`blog/*`, `companies/*`) that currently does
`@extends('layouts.blog')` gets updated to `@extends('layouts.public')`.

---

## Storage

New disk paths under the existing `public` disk (already symlinked via
`php artisan storage:link`):
- `storage/app/public/vendors/logos/`
- `storage/app/public/products/`

Both served the same way `posts` images already are.

---

## Migrations to write (in order)

1. `create_vendors_table`
2. `create_product_categories_table`
3. `create_products_table`
4. `create_product_images_table`

Plus: `ProductCategorySeeder` (seed a handful of categories similar to `Company::CATEGORY_LABELS`
in spirit, but this is products, not services).

---

## Build order

1. **Schema** — 4 migrations, 4 models + `User::vendor()`, factories, `ProductCategorySeeder`.
2. **Repositories, `ProductPolicy`, `EnsureUserIsVendor` middleware, form requests** —
   no UI yet; covered by unit/feature tests before any view exists.
3. **Public side** — `HomeController`, `ShopController`, `ProductController`, their views,
   `layouts/blog.blade.php` → `layouts/public.blade.php` rename + nav rework, update
   `Route::redirect('/', ...)` to `HomeController@index`. **This is the point the blog
   has visibly moved to a tab and the home page shows products.**
4. **Vendor side** — registration flow, dashboard, shop profile edit, product CRUD
   with image upload/reorder/delete, all behind `auth` + `vendor` middleware.
5. **Tests + `composer run ci:check`** — feature tests: public feed renders only
   published products, shop page, product detail page, vendor registration,
   product CRUD ownership (a vendor cannot edit/delete another vendor's product —
   the one test with real security weight), image upload validation.

Each step leaves the app in a working state — stopping after step 3 already delivers
the home-page/blog-tab split described in the original ask.

---

## Explicitly not changing

- `companies` table, model, repository, controller, views, routes — untouched.
- `deals` table — untouched (already unused).
- Blog data model (`Post`, `PostTranslation`, `Category`) and admin blog CRUD — untouched.
- No cart, checkout, or order tables in this phase.

---

# Addendum: CJ Dropshipping Integration

Discussed 2026-10-03. The "admin-run shops, one shared CJ account" direction
below was **proposed and then explicitly dropped** — kept here crossed out only
so the reasoning isn't lost, not because it's live. **Current, final direction
is the individual-vendor-account version in the second half of this addendum.**
Status as of this edit: plan final, build starting now.

## ~~Dropped: admin-run shops, one shared CJ account~~

~~Admin creates shop records and manages products directly; one CJ account/
credential shared across every shop, stored like `ANTHROPIC_API_KEY`. Dropped
because it turns admin into the personal fulfillment desk for every shop, and
because every shop sharing one CJ account is a single point of failure. Reverted
back to independent vendor accounts at the user's request.~~

## Final direction: individual vendor accounts, each with their own CJ login

### Decisions locked in

- **Scope: catalog import only.** No cart, no payment, no automated order placed
  with CJ. Customers still use the existing Buy/Inquire flow (reveals vendor
  contact info). Fulfillment to CJ, if a sale happens, is done manually by the
  vendor, outside the app. Nothing here changes that.
- **Model: each vendor connects their own CJ account.** No shared credential,
  no single point of failure across shops — if one vendor's CJ account gets
  rate-limited or flagged, it doesn't touch any other shop.
- **Enablement: admin flags a vendor as CJ-capable** — a toggle in
  `Admin → Vendors`, next to Approve/Pending. Only then does that vendor see
  anything CJ-related in their own dashboard.
- **Pricing: one shop-wide markup %** per vendor — `vendors.cj_markup_percent`.
  `price = cj_cost_price × (1 + markup/100)`, recalculated immediately whenever
  the vendor changes their markup (using each product's last-known cost, no new
  API call needed), not just on the next daily sync.

### CJ's real API contract (verified via developers.cjdropshipping.com — not guessed)

**Auth** is a self-generated **API key** the vendor gets from their own CJ
account (not their CJ password — meaningfully better for trust than initially
assumed). Exact self-service location on CJ's own site wasn't confirmed by the
docs pages fetched; the connect form will point to CJ's docs rather than assert
a specific click-path we haven't verified.

```
POST https://developers.cjdropshipping.com/api2.0/v1/authentication/getAccessToken
  body: { "apiKey": "..." }
  → accessToken, accessTokenExpiryDate, refreshToken, refreshTokenExpiryDate,
    openId, createDate
  (tokens valid 180 days; rate limit 1 req/sec)

POST https://developers.cjdropshipping.com/api2.0/v1/authentication/refreshAccessToken
  body: { "refreshToken": "..." }
  → same token fields

GET https://developers.cjdropshipping.com/api2.0/v1/product/listV2
  query: keyWord, page, size (max 100), categoryId, startSellPrice, endSellPrice,
         countryCode, sort (asc/desc), orderBy (0=best match,1=listings,
         2=price,3=created,4=inventory)
  → items[]: id, nameEn, sku/spu, bigImage, sellPrice, nowPrice, listedNum,
             categoryId, warehouseInventoryNum, verifiedWarehouse, customization

GET https://developers.cjdropshipping.com/api2.0/v1/product/query
  query: pid (or productSku / variantSku), countryCode, features[]
  → pid, productNameEn, productSku, bigImage/productImageSet, sellPrice,
    categoryName, description, variants[]:
      vid, variantNameEn, variantSku, variantKey (e.g. "Black-XXL"),
      variantSellPrice, inventories[]: { countryCode, totalInventory,
      cjInventory, factoryInventory, verifiedWarehouse, stock[] }
```

`sellPrice` is what CJ charges *us* (the vendor) to buy the item — i.e. our
"cost" for markup purposes, not a suggested retail price. Warehouse/ship-from
info comes from `inventories[].countryCode`, not a single flat field.

### Data model

```
vendors
  + cj_dropshipping_enabled: boolean, default false   -- admin flag
  + cj_markup_percent: decimal(5,2), nullable          -- vendor's own setting

vendor_cj_credentials                 -- one row per vendor who's connected CJ
  vendor_id          FK → vendors, unique, cascadeOnDelete
  access_token         text, encrypted
  refresh_token          text, encrypted
  access_token_expires_at  timestamp
  refresh_token_expires_at  timestamp
  connected_at                timestamp
  timestamps
  -- the raw apiKey the vendor enters is used once to call getAccessToken,
  -- then discarded — never stored.

cj_product_links                      -- ties a local Product back to its CJ source
  product_id           FK → products, unique, cascadeOnDelete
  cj_product_id          string   -- CJ's `pid`
  cj_variant_id             string, nullable  -- CJ's `vid`
  cj_cost_price               decimal(10,2)    -- last-seen `sellPrice`/`variantSellPrice`
  cj_warehouse_country           string, nullable  -- from inventories[].countryCode
  cj_last_synced_at                 timestamp
  timestamps
```

### Backend pieces

- **`CjDropshippingClient`** — thin HTTP wrapper (Laravel `Http` facade) over
  the four endpoints above. Takes a `VendorCjCredential`, auto-refreshes the
  access token when it's near `access_token_expires_at`. Respects the
  documented 1 req/sec limit on the auth endpoint specifically.
- **`ProductImportFromCjService`** — given a CJ product/variant + a vendor:
  creates the `Product` at `cj_cost_price × (1 + markup/100)`, downloads CJ's
  image(s) and stores them locally via the **same** `ProductRepository::syncImages()`
  path normal uploads use (never a hotlinked external URL), status defaults to
  `draft` (same convention as manually-created products), writes `cj_product_links`.
- **`cj:sync-prices` daily scheduled command** — for every vendor with a CJ
  connection, re-queries `product/query` for each linked product; updates price
  if cost moved; auto-flips to `draft` if `totalInventory` hits 0. Required, not
  optional — otherwise a shop can keep selling something CJ no longer has.

### Vendor dashboard UX

**1. "CJ Dropshipping" settings page** (visible only once admin enables it):
  - Connect form: single "CJ API Key" field → exchanged once for tokens via
    `getAccessToken`, raw key discarded, only encrypted tokens kept.
  - Status: "✓ Connected" / "Not connected", with a Disconnect action.
  - Markup %: one number input, saving it immediately repricing every already-
    imported CJ product from its last-known cost.

**2. "Import from CJ"** — new button next to "+ New Product" in `Vendor → Products`:
  - Submit-triggered search (keyword + category + max-cost filters), each result
    showing thumbnail, title, CJ cost, ship-from country, and the vendor's own
    computed sell price under their own markup — before anything is imported.
  - Clicking a result opens a variant checklist (out-of-stock variants greyed
    out); each checked variant imports as its own separate `Product` row
    (e.g. "Saree — Red", "Saree — Blue") — no new "product variants" concept
    needed, reuses the existing one-row-per-product schema.
  - Imported products land in the normal product list as `draft`, flagged with
    a small "CJ" badge, each with a manual "Resync from CJ" button in addition
    to the daily job.
  - **Open, not committed**: whether the imported description comes in as CJ's
    raw (often rough machine-translated) text with a manual "✨ Improve with AI"
    button next to it, or gets auto-rewritten on import. Leaning toward manual
    button, not auto-rewrite, to avoid spending API calls on text nobody reviews.

**3. Product page**: a shipping disclosure line for CJ-linked products — e.g.
  *"Ships from overseas · Estimated delivery 2–4 weeks"* — derived from the
  linked product's `cj_warehouse_country`.

### Drawbacks (still real, now scoped to the per-vendor model)

- **Asking vendors to generate and hand over a CJ API key is still a trust ask**,
  even though it's safer than a password — a leaked/misused key on our end would
  still let someone act on the vendor's CJ account (search, read inventory;
  the catalog-only scope here never calls CJ's order-placement endpoints, so a
  compromised key couldn't be used through *this app* to place orders — but the
  key itself still grants whatever CJ's API allows beyond what we use it for).
- **No fulfillment automation, by design.** A sale still depends on the vendor
  manually placing the matching CJ order themselves after a Buy/Inquire contact.
  Nothing in the app tracks whether that happened.
- **Shipping-time / "local vendor" identity mismatch** — same concern as before,
  just per-shop now instead of platform-wide: a dropshipped item next to
  genuinely local inventory, on a site built around local trust.
- **Price/stock drift inside the daily sync window** — inherent to catalog-only.
- **1 req/sec auth rate limit** means connecting many vendors in quick succession
  (e.g. a bulk-enable day) needs the token-refresh logic to queue/throttle rather
  than fire in parallel.
- **CJ's self-service API-key location wasn't verified** against real docs
  (see note above) — confirm the exact vendor-facing instructions before writing
  the connect-form copy, rather than guessing CJ's own UI labels.

### Build order — all 8 steps complete (2026-10-03)

1. ✅ Migrations (`vendor_cj_credentials`, `cj_product_links`,
   `vendors.cj_dropshipping_enabled` + `cj_markup_percent`) + models.
2. ✅ `CjDropshippingClient` (auth + refresh + search + detail), tested against
   `Http::fake()` matching the documented contract above — no real CJ account
   exists to test against live, so that's the honest limit of verification
   possible right now (never tested against CJ's real servers).
3. ✅ Admin toggle in `Admin → Vendors` (`enableCj`/`disableCj`).
4. ✅ Vendor "CJ Dropshipping" settings page (`/vendor/cj` — connect, disconnect,
   markup; changing markup immediately reprices existing CJ products).
5. ✅ Search/import screen (`/vendor/cj/import`) + variant picker +
   `ProductImportFromCjService` — imports land as `draft`, each variant becomes
   its own `Product`, images downloaded to local storage (not hotlinked),
   out-of-stock variants can't be imported even if checked.
6. ✅ `cj:sync-products` daily command (`routes/console.php`, `Schedule::command(...)->daily()`)
   — batches variants by CJ product ID to minimize API calls, reprices in-stock
   items, auto-drafts out-of-stock ones, one vendor's CJ failure doesn't block others.
7. ✅ Shipping-time disclosure on the product page (`CjProductLink::shippingEstimate()`).
8. ✅ Tests throughout — `Http::fake()` everywhere, 28 new tests across client,
   admin toggle, vendor settings, import flow, sync command, and the disclosure
   badge. Full suite: 123/123 passing, Pint clean, PHPStan clean (only the
   same pre-existing generics-annotation style gap already present throughout
   the rest of the codebase).

## Verified live against a real CJ account (2026-10-04) — two real bugs found and fixed

A vendor (Dhaka Bazaar) connected a real CJ Dropshipping account and hit a real
error on the first search. Investigating against the live account (not just
docs) surfaced two gaps between what CJ's documentation says and what the API
actually returns — both are exactly the kind of thing `Http::fake()` tests
can't catch, since the fakes were built from the (incorrect) documented shape:

1. **Search results are nested one level deeper than documented.** CJ's docs
   describe `data.list[]`; the real response is `data.content[0].productList[]`
   — `content[0]` is a wrapper object (also carrying `relatedCategoryList`,
   `keyWord`, `keyWordOld`), not a product. The old code treated `data.content`
   itself as the product array, so it tried to read `.id` off that wrapper and
   crashed with "Undefined array key 'id'". Fixed in `CjDropshippingClient::search()`
   with the real path as the primary case and the documented shapes kept as
   fallbacks. All three DTOs (`CjProductSummary`, `CjProductDetail`, `CjVariant`)
   were also hardened to try a few plausible alternate key names and log a
   warning instead of throwing when a field is genuinely missing — one
   malformed row should never take down the whole results page again.

2. **`product/query`'s `inventories` can be `null` for a variant whose product
   listing clearly has stock** (confirmed: a real product reporting
   `warehouseInventoryNum: 40` in search had every variant come back
   `"inventories":null` in the detail call — with or without `countryCode`
   passed). Three inventory-specific endpoints CJ's docs suggested as the "real"
   stock source (`inventoryQuery`, `inventoryBySku`, `inventoryByProductId`) all
   returned `"Interface not found"` when tried directly — so those paths were
   never real; no further attempt was made to find a true dedicated stock
   endpoint. Given that, `CjVariant::inStock()` was changed to treat `null`
   (unknown) as *importable*, and only a confirmed explicit zero as blocking.
   This matters in both places stock is checked: the import variant-picker (was
   wrongly marking everything "Out of stock" and disabling every checkbox) and
   the daily `cj:sync-products` command (was at risk of wrongly auto-drafting
   *live, published* products on a resync purely because CJ omitted inventory
   data, not because anything actually sold out).

After both fixes: real search → real product detail/variant picker (now showing
honest "Stock unknown" instead of a false "Out of stock") → real import all
confirmed working end-to-end against the live account, including the image
actually downloading from CJ's CDN to local storage. The one test product
created during that verification was deleted afterward; the vendor's real CJ
connection was left untouched.

**Still not independently verified**: order placement (out of scope — this
build never calls CJ's order endpoints), behavior for other product types/
categories beyond the one tested, and whether the `content[0].productList`
shape holds for every `listV2` filter combination (kept as the primary case
with the documented shapes as fallback specifically because of that
uncertainty).

## Follow-up: result count/pagination was silently capped, and pricing was USD-only (2026-10-04)

Two more things found and fixed after the initial build, both from direct
user testing against the live account:

**Only 20 results ever showed, with no pagination.** `search()` defaulted to
`size=20` and the import screen never exposed `page` at all — confirmed live:
"jewelry" has 1,216 real matches on CJ, all but the first 20 were invisible.
Fixed: default size raised to 50 (CJ's documented max of 100 is enforced as a
clamp, not a default), `CjDropshippingClient::search()` now returns a
`CjSearchResult` (items + `page`/`totalPages`/`totalRecords` from CJ's own
response) instead of a bare array, and the search page shows real Previous/Next
links plus a result count. Verified live: page 1 and page 2 of a real "jewelry"
search both render correctly with working navigation.

**Prices were USD, CJ's `sellPrice` excludes shipping.** Both confirmed
directly from CJ's docs (not assumed): no `currency` parameter exists on
`listV2`/`query` — CJ is USD-only — and shipping is calculated by a wholly
separate `logistic/freightCalculate` endpoint (needs destination country +
variant + quantity), never folded into `sellPrice`. Added `CjCurrencyConverter`:
fetches the live USD→CAD rate from Frankfurter (free, ECB-backed, no API key),
caches it for a day, falls back to a configured rate
(`CJ_USD_TO_CAD_FALLBACK_RATE`) if the lookup ever fails. `cj_cost_price` still
stores the *raw USD* value from CJ (unconverted, for data fidelity) — conversion
happens at calculation time via `CjCurrencyConverter::sellingPrice()`, the one
place the full formula (USD → CAD → + markup) now lives, used identically by
the import flow, the daily resync, and both the search and variant-picker
previews. Every vendor-facing price now reads "CJ cost: $X USD (excl.
shipping)" / "Your price: CA$Y" so the currency and the shipping gap are both
explicit, not implied. Verified live: real rate fetched (1.424 at the time of
testing) and applied correctly end-to-end on a real search.

**Not addressed — explicitly out of scope for now**: shipping cost is still
not shown or factored into price anywhere. Integrating it would mean calling
`logistic/freightCalculate` per variant, which needs a destination country —
nothing in this app collects one today (no checkout, no customer address; the
vendor would need a "default ship-to country" setting of their own). Flagged
back to the user as a decision point, not built speculatively.

## Follow-up: CJ's description is HTML, not plain text (2026-10-04)

User-reported: imported products showed literal HTML tags in the description.
Confirmed — CJ's `description` field is real HTML (`<p>`, `&nbsp;`, even
embedded `<img>` tags, seen directly in the earlier real-account capture), but
every other product description in this app (manually typed, or AI-suggested)
is plain text, rendered with `{{ }}` (escapes HTML rather than formatting it).
Fixed in `ProductImportFromCjService::cleanDescription()`: strips tags,
converts `<br>`/paragraph boundaries to blank lines, decodes entities, and
normalizes non-breaking spaces (`&nbsp;` → U+00A0) to plain ASCII spaces —
leaving `&nbsp;` as a real but invisible non-breaking-space character would
have caused exactly this kind of silent, hard-to-spot mismatch again later
(e.g. a vendor searching/editing the text and the match failing with no
visible reason). Verified against the exact real HTML captured from the live
account earlier in this build — output is clean, readable plain text.

## Follow-up: only one photo was ever imported, images in the description were discarded (2026-10-04)

User asked whether the "lot of images" in a CJ description would survive —
they didn't, `strip_tags()` removed `<img>` tags along with everything else.
Separately, only one image was ever downloaded per import (`bigImage`), even
though CJ provides a real multi-photo gallery (`productImageSet`) and often a
distinct photo per variant (`variantImage`) — confirmed on the real connected
account: one real product had 4 gallery photos plus 1 image embedded in its
description, all previously reduced to just the single `bigImage`.

Fixed in `CjProductDetail`/`CjVariant` (capture `imageUrls` from
`productImageSet`, `descriptionImageUrls` extracted from the raw HTML
*before* it's stripped to plain text, and `CjVariant::imageUrl` from
`variantImage`) and `ProductImportFromCjService::imageUrlsFor()` (gathers
variant photo → gallery → description images, in that priority order so the
most specific photo becomes primary, deduplicates, caps at 8 — matching the
manual-upload form's existing limit). One real quirk worth noting: CJ's
`productImage` field (no "Set") is, in practice, a JSON-encoded *string* of
the same list, not a real array — deliberately not used as a fallback source,
to avoid treating that string as a literal image URL.

Verified against the real connected account: the exact product tested earlier
really does have 4 gallery photos + 1 description-embedded image, all now
captured by `CjDropshippingClient::productDetail()`.

## Feature: admin product categories + featured products on the home page (2026-10-05)

User asked for three tied-together things: an admin UI to manage product
categories (previously `ProductCategory` had no admin UI at all — only ever
seeded via `ProductCategorySeeder`), a "featured" flag on products, and an
admin console to pick which products are featured, surfaced on the home page.

Built, following the repository-pattern convention more rigorously than the
existing `Admin\PostController` (which calls `Post::create()`/`update()`
directly — a pre-existing inconsistency, not replicated here) and instead
matching the Vendor-side convention (`VendorRepository`, `ProductRepository`)
that every admin controller in this feature goes through a repository:

- Migration: `is_featured` boolean on `products`, default `false`, indexed
  alongside `status` (the exact pair `featuredFeed()` filters on).
- `Product::scopeFeatured()`, alongside the existing `scopePublished()`.
- `ProductCategoryRepository` (new) — CRUD + unique-slug generation, mirroring
  `ProductRepository`'s existing per-vendor slug uniqueness pattern but
  globally unique (categories have no vendor scope).
- `ProductRepository::featuredFeed()`, `allForAdmin()`, `setFeatured()` added
  to the existing repository. `featuredFeed()` applies the exact same
  published + vendor-approved visibility rule as `publishedFeed()` — a
  featured product from a pending/un-approved vendor, or a draft, never
  appears, even if flagged featured (covered by a dedicated test).
- `Admin\ProductCategoryController` (resourceful, index/create/store/edit/
  update/destroy) and `Admin\ProductController` (index + feature/unfeature)
  — both admin-only via the existing `admin` middleware.
- Deleting a category sets `product_category_id` to `null` on its products
  rather than deleting them (the FK was already `nullOnDelete()` from the
  original migration — confirmed, not changed).
- Admin nav gained "Products" and "Categories" links, same active-state
  pattern as the existing Posts/Vendors links.
- Home page gained a "Featured Products" section (shown only when at least
  one featured product is visible) above the existing filterable grid.

17 new tests added (category CRUD admin-gating, feature/unfeature
admin-gating, home-page featured visibility including the draft/pending-vendor
exclusion case) — full suite now 155/155 passing, Pint clean. PHPStan shows
only the same pre-existing, already-tolerated error classes found everywhere
else in the codebase (`missingType.generics` on `HasFactory`/`Collection`/
`LengthAwarePaginator`, `missingType.iterableValue` on Form Request/repository
array params) — no new error types introduced.

Verified live against the running dev server: logged in as the seeded admin,
created a real category, featured a real product via the admin UI, confirmed
it appeared in the home page's new "Featured Products" section, then
unfeatured it and deleted the test category — both cleanly reverted.

## Follow-up: home page shows featured products only, search moved above (2026-10-05)

User asked to move the search bar above the featured products section, and
changed the home page's default behavior: it should no longer show the full
"all products" grid at all — only featured products, normally. Search (by
name/description, or by category) still works against the full catalog, same
as before, and now visually replaces the featured section while a search is
active, rather than sitting below it.

`HomeController::index()` now computes `$isSearching = filled($search) ||
filled($category)` and only runs `ProductRepository::publishedFeed()` when
true; `featuredFeed()` is always computed. The view reorders to: hero →
search/filter form → (searching ? search results grid : featured products,
or an empty state for whichever is showing). `publishedFeed()`/`featuredFeed()`
themselves were not changed — same published + vendor-approved visibility
rule as before, confirmed unaffected by this change.

One real test-writing gotcha hit while updating the existing
`VendorApprovalTest`'s "approving a vendor makes products visible" test: it
used to search for a product by its *exact* name and then `assertDontSee`
that same string pre-approval — but the search `<input>` field echoes
`request('search')` back into its `value` attribute regardless of whether
anything matched, so the literal search term always "appears" on the page via
the input box itself, making `assertDontSee($productName)` a false pass/fail
independent of actual search results. Fixed by asserting on the product's
actual detail-page URL (`route('shops.products.show', ...)`) instead of the
product name — that string only appears when the product is genuinely in the
results, not as a side effect of the form re-displaying the query.

21 tests updated/added across `HomeTest` and `VendorApprovalTest` — full suite
158/158 passing, Pint clean, PHPStan clean on the changed files (0 new
errors). Verified live: default home page now shows the empty "No featured
products yet" state (nothing currently featured on the real dev DB), search
bar renders above where the featured section goes, featuring a real product
makes it appear by default, searching for a *different, non-featured*
product still finds it while hiding the featured heading, and all live test
changes were reverted afterward.

## Follow-up: "Sell With Us" moved from nav into a hero modal (2026-10-05)

User asked to remove "Sell With Us" from the top nav (both desktop and
mobile, in `layouts/public.blade.php`) and instead add a button beside
"Browse All Shops" in the home page hero that opens a modal pitching how easy
it is to open a shop (upload a photo → AI writes the title/description →
you're live) — directly referencing the real AI-description feature already
built, not a hypothetical one.

Removed the `Sell With Us` `<a>` from both the authenticated-non-vendor and
guest branches of the desktop nav and the mobile nav panel (the `@auth`
vendor-dashboard link and admin link stay; only the "sell with us" entry
point moves). The footer CTA ("Have products to sell?" / Register / Create
Your Shop) was left untouched — user asked specifically about the nav, and
the footer isn't the nav.

Added a plain vanilla-JS modal in `home.blade.php` (no Alpine/library in this
app — matches the existing mobile-nav hamburger's
`classList.toggle('hidden')` convention) triggered by the new hero button,
with a 3-step pitch and a CTA that branches the same way the footer CTA
already does: vendor dashboard if already a vendor, `vendor.register` if
logged in without a shop, `vendor.intent('register')` (preserves intent
through the login/register detour) for guests.

6 tests added to `HomeTest` (nav no longer contains the string, inside a
specific `</header>`-scoped check so it doesn't false-pass just because the
hero button reuses the same label; CTA routes correctly branch by auth/vendor
state) — full suite 162/162 passing, Pint clean. Verified live: nav has zero
"Sell With Us" occurrences, the hero button and modal markup render exactly
once, both `/shops` and `/en/blog` (which share the same layout) still render
fine.

## Feature: buyer inquiry modal with a prefilled WhatsApp/email message (2026-10-05)

User asked whether letting buyers message vendors via WhatsApp/email from a
modal would cost money. Answer given: no, as long as it's built the same way
vendor contact was already revealed elsewhere in the app (`wa.me` and
`mailto:` deep links that just hand off to the buyer's own WhatsApp/email
client) rather than sending anything server-side — no WhatsApp Business API,
no transactional email service, no per-message cost. User asked to implement
it on that basis.

Replaced the old "Buy / Inquire" contact panel on the product page
(`products/show.blade.php`) — which only revealed plain, blank wa.me/tel/
mailto links — with a modal containing an editable, prefilled message
("Hi {vendor}, I am interested in {product}. Is it still available?" plus
the product's own URL) and one button per channel the vendor actually has:
"Send via WhatsApp" (`wa.me/<digits-only-phone>?text=<encoded message>`,
opens in a new tab) and "Send via Email" (`mailto:<email>?subject=Inquiry
about <product>&body=<encoded message>`). A plain `tel:` Call link is always
shown. Buttons for channels the vendor hasn't filled in (no `whatsapp` or no
`email` on file) are simply omitted rather than shown disabled.

Implementation is 100% client-side vanilla JS (`sendInquiry()`, matching the
existing hamburger-menu/modal `classList` convention already used elsewhere
in this app — still no Alpine or other JS library) — the textarea's live
value is read at click time and URL-encoded into the `wa.me`/`mailto:` link,
so the buyer can edit the default message before it's sent. One real
escaping subtlety handled on purpose: the email button's `onclick` passes
the vendor's email and subject through `json_encode()` inside the Blade
`{{ }}` echo — Blade's HTML-escaping converts the JSON string's `"`
characters to `&quot;`, which the browser correctly decodes back to literal
`"` before the inline JS executes, so values containing apostrophes or
special characters (a product name, say) can't break the attribute or the
script. Verified this renders correctly against the real dev server, not
just reasoned through.

4 tests added to `ProductTest` (WhatsApp link uses digits-only phone and
includes the message; email button appears only when the vendor has an
email; both buttons are hidden — Call link still present — when the vendor
has neither; tel link renders). Full suite 166/166 passing, Pint clean.
Verified live: a real published product's page renders the modal with a
correct prefilled message (vendor name, product name, product URL on its own
line), correct wa.me/mailto targets, and a vendor with neither whatsapp nor
email on file correctly shows only the Call button.
