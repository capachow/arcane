## Arcane Microframework

> *Arcane is unconventional but beautifully intuitive. It is intentionally different, breaking away from modern frameworks to encourage critical thinking without the dependence and overhead of complex systems. It brings out the fun in building for the web by automating the features you want, while making it easier to apply the ones you need.*

At its core, Arcane is a tiny `14kb` single-file PHP microframework designed to keep things easy and minimal. It uses a filesystem-first workflow where files map directly to routes, and context-aware helpers and assets load automatically. Perfect for anyone who wants a fast, flexible tool with zero setup.

  - Clean configuration free URLs
  - Unique filesystem defined routing
  - Helpers autoloaded by context
  - Layouts wrap pages automatically
  - Optional MV engine compiled views
  - Robust localization kept simple
  - Architecture driven by directories
  - Simple ENV file configuration
  - HTML minification for performance
  - Native PHP minimal learning curve
  - Zero external dependencies needed

`Less config. More craft.`

## Installation

**[https://arcane.dev/download](https://arcane.dev/download)** or copy via terminal:

``` shell
curl -fsLO copy.arcane.dev/index.php
```

> <code><del>composer create-project capachow/arcane</del></code> It's a single file with zero dependencies. You should just curl it.

Simply drop `index.php` into your project and open it in your browser. Arcane conjures the rest like magic.

## Documentation

1. [Philosophy & Architecture](#1-philosophy--architecture)
2. [The Four Functions](#2-the-four-functions)
3. [Routing & Pages](#3-routing--pages)
4. [Layouts, Rendering, and Includes](#4-layouts-rendering-and-includes)
5. [Optional Model-View Engine](#5-optional-model-view-engine)
6. [Helpers & Autoload Cascade](#6-helpers--autoload-cascade)
7. [Automatic CSS/JS Assets](#7-automatic-cssjs-assets)
8. [Localization and Translation](#8-localization-and-translation)
9. [Environment & Settings](#9-environment--settings)
10. [Page Directives](#10-page-directives)
11. [Runtime Constants](#11-runtime-constants)
12. [Troubleshooting & Notes](#12-troubleshooting--notes)
13. [Requirements and Ecosystem](#13-requirements-and-ecosystem)
<!-- 14. [The Meaning of Arcane](#14-the-meaning-of-arcane) -->

---

### 1. Philosophy & Architecture

Arcane operates on a single governing principle: **location is logic**.

Modern web development often drifts into configuration chaos. Many frameworks are built to satisfy enterprise edge cases, and everyone else ends up carrying that weight. Arcane takes the opposite stance. It focuses on the work that ships real projects, like routing, templating, and helpers, and treats "less" as a **deliberate discipline**, not a missing feature. By focusing on what most projects actually need, Arcane makes a promise rooted in honesty, freedom, and focus. You should not have to carry the baggage for features you will never use.

That discipline comes from *unified primitives* and a filesystem-first approach. There is no route registry or hidden sigils to maintain. If you want a page, you create a file. By keeping assets, logic, and views close together, Arcane encourages *cognitive locality*. You build where it makes sense instead of hunting through scattered configuration.

Dependencies also break. They age, conflict, and require maintenance. Arcane stays **dependency-free** at its core, keeping your project stable and grounded in standard PHP, so you are not betting your site on whether a framework stays maintained.

**The Request Lifecycle**:

Instead of a complex event loop, Arcane follows a linear path of discovery:

1. **Match:** The URL is mapped directly to the closest physical file in `/pages`, normalizing paths for SEO.
2. **Collect:** Arcane walks the directory tree down to that file, channeling only relevant helpers, data, and assets.
3. **Build:** The page executes within this prepared environment, generating the `CONTENT` constant.
4. **Return:** Output is wrapped in the layout, assets are injected, and the final response is sent to the browser.

---

### 2. The Four Functions

Arcane keeps things minimal. You can build simple to complex applications using just these four globals. These global functions are available everywhere.

2.1 **`env(string, string)`**: Retrieves environment variables or feature flags.

- *Note*: Automatically converts strings `true`, `false`, or `null` into actual bool or nulls.

``` php
<?php $mode = env('IS_ALLOWED', 'true'); ?>

<?php if(env('IS_ALLOWED')) { ... } ?>
```

2.2 **`path(mixed, bool)`**: The unified tool for inspecting where you are and generating where you want to go.

  -  A. **Current Request** [empty]:

``` php
<?= path(); # /blog/2024 ?>
```

  - B. **Get Segment** [integer]:

``` php
<?= path(1); # first-segment ?>
<?= path(3); # third-segment (null if missing) ?>
```

  - C. **Generate URL** [string|array]:
- *Note*: If a locale is active (example: `en+us`), Arcane automatically prefixes the generated URL (`/us/about`). You do not have to manually manage language prefixes.
- *Normalization*: If you provide a path without an extension or parameters (no `.` or `?`), Arcane assumes it is a page route and removes any trailing slash to normalize SEO.

``` php
<?= path('/about/'); # normalized to /about ?>
<?= path('/shop/cart'); # /us/shop/cart (if active locale) ?>
```
<?= path(['IMAGES', 'logo.svg']); # /images/logo.svg (no matter the URL) ?>
```

  - D. **System Path** [string|array][bool]:

    - Pass `true` as the second argument to get the absolute server path for `include()` or `require()`.

``` php
<?= path('/folder/custom.php', true); ?>
<?= path(['HELPERS', 'custom.php'], true); ?>
```

2.3 **`relay(string, mixed, bool)`**: Pass variables or content blocks from your page to your layout.

  - **Usage**: Pages pass data with `relay('key', 'value')`. Layouts then use `relay('key')`.
  - **Constants**: Pass `true` as the third parameter to define a constant.
  - **Blocks**: Pass a function to capture a block of HTML to inject into the layout.

*Note*: Keys are case-insensitive and automatically normalized. Variables are always stored as lowercase (`title`) and constants as uppercase (`SITE`).

``` php
<?php relay('SITE', 'Name', true); ?>
<?php relay('title', 'Home'); ?>
<?php relay('sidebar', function() { ?>
  <h2>Sidebar</h2>
  <p>Injected into the layout.</p>
<?php }); ?>
```

2.4 **`scribe(string|array, array)`**:

Returns a translated string based on the active locale. If no translation exists, it returns the fallback text provided. Localization is optional.

``` php
<?= scribe('Welcome'); ?>
<?= scribe('Hello :name', [':name' => 'John']); # Hello John ?>
<?= scribe(['missing.key', 'Fallback']); ?>
```

---

### 3. Routing & Pages

In Arcane, a file *is* a URL. This eliminates the need for a `routes.php` file.

  - `pages/about.php` → `/about`
  - `pages/blog/index.php` → `/blog`
  - `pages/shop/checkout.php` → `/shop/checkout`

What if you need dynamic segments, like `/blog/my-post`? Arcane resolves the *closest* physical file first.
If you visit `/blog/my-post`, Arcane loads `pages/blog.php` (if it exists) and passes `my-post` as a parameter.

You control valid dynamic segments using `define('ROUTES', array)` inside the page file.

*Example*: `pages/blog.php`

```php
<?php define('ROUTES', [
  ['history'], # /blog/history
  [['2023', '2024']], # /blog/2023 or /blog/2024
  ['*'] # /blog/anything
]);

$slug = path(1); ?>
```

  - String values are an exact match.
  - Array values are an allow-list of options.
  - Wildcard matching with `*`. If the URL fails this validation, Arcane redirects to the closest valid path.

---

### 4. Layouts, Rendering, and Includes

Arcane separates logic from presentation using a simple wrapper system.

  1. **The Page**: Executes first. It defines data, handles logic, and outputs HTML. This output is captured in the constant `CONTENT`.
  2. **The Layout**: Executes second. It outputs the HTML structure (`<html>`, `<body>`) and echoes `CONTENT` where the page should appear.

The layout automatically receives `CONTENT`, `STYLES`, and `SCRIPTS` as global constants. Any custom data passed from the page is retrieved using the `relay()` function.

*Example*: `layouts/default.php`

``` php
<html>
  <head>
    <title><?= relay('title') ?? SITE; ?></title>
    <?= STYLES; ?>
  </head>
  <body>
      <?= CONTENT; ?>
      <aside>
        <?= relay('sidebar') ?>
      </aside>
      <?= SCRIPTS; ?>
  </body>
</html>
```

---

### 5. Optional Model-View Engine

Arcane ships with a built-in, regex-powered MV engine. Create an `.html` (view) file alongside your `.php` (model) file. Your model handles the data logic, while the engine compiles your view into native PHP for safe rendering.

**The Golden Rule of Syntax:** If you use an equals sign (`=`), you must wrap your PHP expression in quotes. If you don't use an equals sign, no quotes are needed.

5.1 **Auto-Escaped Variables**

Values output using the engine are automatically wrapped in `htmlspecialchars()` to protect your application from XSS attacks by default.

  - **Shorthand (`:`):** Best for simple variables and object methods. Does not support array brackets.
  - **Implicit (`:=`):** Best for arrays, complex expressions, or string interpolation. When interpolating, use single quotes on the outside and double quotes on the inside.
  - **Explicit (`<echo>`):** The explicit HTML-style tag for outputting expressions.

```html
<!-- Shorthand (no equals, no quotes) -->
<h1>:$title</h1>
<span>:$user->getName()</span>

<!-- Implicit (equals used, quotes required) -->
<h2>:="$user['name']"</h2>
<h2>:='"Welcome back, {$user->name}"'</h2>
<p>:="number_format($price, 2)"</p>

<!-- Explicit (equals used, quotes required) -->
<p><echo :="$description" /></p>
```

5.2 **Whitelisted Functions**

If you need to execute a core Arcane function (`php`, `scribe`, `relay`, `path`, `env`) or output raw, unescaped HTML (like parsed Markdown or rich text), you can call them directly. They support both the `:<function>()` shorthand and the explicit `<function :="">` tag.

```html
<!-- Shorthand (no equals, no quotes) -->
<h2>:scribe('welcome_message')</h2>
<link rel="stylesheet" href=":path('/css/style.css')" />

<!-- Explicit (equals used, quotes required) -->
<p><php :="'<strong>Raw Bold Text</strong>'" /></p>
```

5.3 **Control Structures**

Arcane supports standard PHP control structures using clean HTML-style tags. The supported tags are `<if>`, `<elseif>`, `<else>`, `</if>`, `<foreach>`, `</foreach>`, `<break>`, and `<continue>`.

```html
<if :="$count > 0">
  <p>You have items!</p>
<elseif :="$count === 0">
  <p>Your cart is empty.</p>
<else>
  <p>Please log in.</p>
</if>

<ul>
  <foreach :="$users as $user">
    <li>:$user->name</li>
  </foreach>
</ul>

<input type="checkbox" <if :="$checked">checked</if> />
```

5.4 **Includes & Comments**

You can easily pull in other HTML partials, and developer comments are completely stripped from the final compiled code. Included files inherit the exact same variable scope as the parent template.

```html
<-- This developer note will not appear in the DOM -->
<include :="'partials/header.php'" />
```

*Performance:* Currently, Arcane evaluates these compiled HTML strings in memory (`eval`). This is fast and completely secure (since it only evaluates local `.html` files you write). It serves as a highly capable engine until future native integration with a file-based caching system is introduced to unlock maximum speeds.

---

### 6. Helpers & Autoload Cascade

This is one of Arcane's most "refreshing" features. Instead of autoloading every class in the universe, Arcane loads helpers based on context. Helpers are PHP files that return a value (`mixed`). They become variables in your page matching their filename.

*Example*: `helpers/cart.php`

``` php
<?php return [
  'items' => 3,
  'total' => 99.00
]; ?>
```

*Example*: `pages/checkout.php`

``` php
<p>Items: <?= $cart['items']; ?></p>
<p>Total: $<?= number_format($cart['total'], 2); ?></p>
```

**Context-aware Cascade**:

If you are visiting `/shop/checkout`, Arcane looks for helpers in this specific order, merging them as it goes:

  1. `helpers/*.php` (global scoped helpers)
  2. `helpers/shop/*.php` (section scoped helpers)
  3. `helpers/shop/checkout/*.php` (page scoped helpers)

This is done because you can have a helper named `cart.php`:

  - In `helpers/cart.php`, it might define a generic cart.
  - In `helpers/shop/cart.php`, you can override it with a detailed shopping cart specific to the shop section.
  - The most specific helper always wins.

*Note*: Helpers are imbued into Pages. If a helper needs another helper, you must `include` it explicitly.

``` php
<?php $truncate = include(path(['HELPERS', 'truncate.php'], true));

return [
  'excerpt' => $truncate($post['body'], 160)
]; ?>
```

---

### 7. Automatic CSS/JS Assets

Forget complicated configurations for simple tasks. Arcane uses *convention over wiring*.

When a layout is active, Arcane scans your `/styles` and `/scripts` directories. If a file matches the current layout or the current page path, it is automatically bound in the `STYLES` or `SCRIPTS` constants.

**Context-aware Cascade**:

For a user visiting `/blog/post` using the `default` layout, Arcane looks for and auto-injects:

  1. **Layout Assets**: `styles/default.css` & `styles/default.js`
  2. **Section Assets**: `styles/pages/blog.css` & `styles/pages/blog.js`
  3. **Page Assets**: `styles/pages/blog/post.css` & `styles/pages/blog/post.js`

*The Benefit*: You can create `styles/pages/blog.css` and it will automatically apply to every blog post, but nowhere else on the site. No manual `<link />` or `<script>` tags required. All assets are cache-busted automatically via `?m=` timestamp.

---

### 8. Localization and Translation

Arcane handles localization via folder naming conventions, supporting both language switching and country specific content.

<table>
  <tr>
    <th>Language Locale</th>
    <th>Country Locale</th>
  </tr>
  <tr>
    <td>
      <pre lang="txt">locales/
├─ en/
│  ├─ en-ca.json
│  ├─ en+us.json
│  └─ en.json
├─ es/
│  ├─ es+mx.json
│  ├─ es-us.json
│  └─ es.json
├─ fr/
│  └─ fr+ca.json
├─ ca.json
└─ us.json</pre>
    </td>
    <td>
      <pre lang="txt">locales/
├─ ca/
│  ├─ en-ca.json
│  └─ fr-ca.json
├─ mx/
│  └─ es-mx.json
├─ us/
│  ├─ en-us.json
│  ├─ es-us.json
│  ├─ fr-us.json
│  └─ us.json
├─ es.json
└─ en.json</pre>
    </td>
  </tr>
</table>

**Folder Naming (`-` vs `+`)**:

  - `locales/en/en-us.json` creates a **two-segment URL** (`/en/us`).
  - `locales/en/en+us.json` creates a **one-segment URL** (`/en`). Country is active, but hidden from the URL.

Arcane weaves translations intelligently. For `en-us`, it loads:

  1. `locales/us.json` (country defaults)
  2. `locales/en/en.json` (language defaults)
  3. `locales/en/en-us.json` (specific overrides)

Later files override earlier ones, allowing you to define a base language and only tweak specific keys for countries.

*Note*: If you set `SET_LOCALE`, Arcane will detect the browser's `Accept-Language` header and redirect the user to the best matching locale path automatically.

---

### 9. Environment & Settings

Configuration is handled via `.env`. If `.env` is missing, Arcane defaults to `.env.example`.

9.1 **Key Settings (`SET_`)**:

| Setting | Default | Purpose |
| :--- | :--- | :--- |
| `SET_ERRORS` | `false` | Set to `true` to debug PHP errors. |
| `SET_INDEX` | `'index'` | Change the default directory file (example: `home.php`). |
| `SET_LAYOUT` | `null` | Define a global layout wrapper for all pages. |
| `SET_LOCALE` | `null` | Set a default `BCP 47` tag to enable auto-localization. |
| `SET_MINIFY` | `true` | Toggles HTML minification to keep output small. |

9.2 **Directories (`DIR_`)**:

You can remap any default folder (like `pages` to `views`) by setting `DIR_PAGES` in your environment.

``` env
DIR_PAGES=/pages/
DIR_LAYOUTS=/layouts/
DIR_HELPERS=/helpers/
DIR_SCRIPTS=/scripts/
DIR_STYLES=/styles/
DIR_LOCALES=/locales/
DIR_IMAGES=/images/
```

---

### 10. Page Directives

Directives are constants defined at the top of a *page* file. They act as the "controller" logic for that specific page.

  - `define('LAYOUT', 'filename')`: Overrides the global layout for this specific page.
  - `define('REDIRECT', '/path')`: Immediately redirects the user. Relative paths are auto-resolved; absolute URLs are respected.
  - `define('ROUTES', array)`: Defines acceptable "extra" URL segments for this page (see [Routing & Pages](#3-routing--pages)).

---

### 11. Runtime Constants

Arcane provides global constants to give you instant access to the application state.

11.1 **Content & Output**:

  - `CONTENT`: The rendered HTML of the page.
  - `HELPERS`: The merged and injected helpers for pages and layouts.
  - `STYLES`: The injected `<link>` tags (layout must be active).
  - `SCRIPTS`: The injected `<script>` tags (layout must be active).

11.2 **Routing & Location**:

  - `URI`: The URL segments array (locale segments removed when applicable).
  - `PATH`: The resolved “base path” for the matched page.
  - `PATHS`: Hierarchical path list used for helper/asset discovery.
  - `PAGEFILE`: Absolute path to the resolved PHP page file.
  - `VIEWFILE`: Absolute path to the resolved HTML view file (when used).
  - `LAYOUTFILE`: Absolute path to the resolved PHP layout file (when used).

11.3 **Localization**:

  - `LOCALES`: Discovered locales grouped by folder.
  - `LOCALE`: Array containing `CODE`, `COUNTRY`, `FILES`, `LANGUAGE`, and `URI` (when active).
  - `TRANSCRIPT`: The merged translations for the active locale (when active).

11.4 **App Configuration**:

  - `APP`: Derived runtime info containing `DIR`, `ROOT`, `START`, `QUERY`, and `URI`.
  - `DIR`: Directory map (from defaults + any DIR_* overrides).
  - `SET`: Settings map (from defaults + any SET_* overrides).

---

### 12. Troubleshooting & Notes

  - **Trailing Slashes**: Arcane normalizes "page-like" routes to not have trailing slashes. This prevents duplicate content issues for SEO.
  - **Minification**: By default, `SET_MINIFY` is `true`. It strips whitespace and comments. If your HTML looks broken, try setting this to `false` in `.env` to debug.
  - **Helper Collisions**: If you have helpers with the same name in different folders, remember: *Scope wins*. The helper closest to the current page path overrides the global one.
  - **Errors**: To see full PHP error details during development, ensure `SET_ERRORS` is set to `true` in your environment.

---

### 13. Requirements and Ecosystem

Arcane is minimal by design. It doesn't include heavy tomes, but it offers a system to plug them in easily.

  - Arcane **requires** PHP >= 8.4.
  - **Apache users:** Works automatically provided the `AllowOverride All` directive is enabled (Arcane will generate the required `.htaccess` file for you).
  - **NGINX users:** Requires routing traffic to the front controller. Add this to your `server` block alongside your existing PHP execution directive:
```nginx
location / {
  rewrite ^/(.*)/$ /$1 permanent;
  try_files $uri $uri/ /index.php?$query_string;
}
```
  - [Arcane Helpers](https://github.com/capachow/arcane-helpers) is a collection of drop-in files for common tasks (Markdown parsing, OAuth, database access).
  - [Creating an issue](https://github.com/capachow/arcane/issues) on GitHub for reporting bugs is always appreciated.

*License*: Copyright 2017-2026 [Joshua Britt](https://github.com/capachow) under the [MIT](LICENSE.md).

---

### 14. The Meaning of Arcane

Since you made it this far down, here is a little easter egg message.

The word *arcane* refers to mysterious knowledge, secrets understood by only a few.

In an era of endless configuration and complexity, simplicity has become that secret. We have largely forgotten that the web was meant to be crafted, not configured. Arcane is a return to that lost art. It is a reminder that real mastery does not require complexity. It requires deep understanding.

> Now go have fun and develop something you are proud of, but keep it **Arcane**.
