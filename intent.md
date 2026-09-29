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
