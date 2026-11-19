# (ƒ) FLINT - Theme Builder 2.0

The plugin is the stable **behavior/configuration layer** shared across Flint WordPress builds.

## Starting Setup
Open **Flint > Starting Setup** to configure:
- Active header/footer layout (menu locations are registered from the chosen templates)
- Gutenberg blocks from core, plugins, and Flint ACF blocks
- Excerpt length
- Base Team Members post type, Team Categories, and Locations
- Additional custom post types and taxonomies
- Gutenberg + TinyMCE color swatches
- Common features: emoji cleanup, SVG uploads, Gravity Forms button markup, Slick, jQuery handling, image alt defaults, WYSIWYG elements/tables

## ACF
The ACF export supplied with the starter has been split into Local JSON files under `acf-json/`. ACF Pro is expected to be installed separately.

## Architecture rule
If it changes the site's presentation, put it in the theme. If it is a stable behavior or setup switch, put it in this plugin.

## 2.0.1
- Resolve enabled Gutenberg blocks against the live WordPress block registry at editor runtime.
- Run the allow-list filter late so ACF/plugin registrations are available first.
- Add Enabled + Registered / Enabled + Not Registered / Disabled diagnostics to Starting Setup.

## 2.1.0
- Per-group Select all / Deselect all controls for Gutenberg blocks.
- Starter post types and taxonomies are now ordinary editable/removable rows.
- Configurable TinyMCE/ACF WYSIWYG toolbar elements.
- ACF Site Content settings are nested beneath the main Theme Settings menu.
- Role-based dashboard menu visibility controls.
- All plugin configuration pages require administrator (`manage_options`) access.

## 2.2.0 editor experience
- Flint ACF blocks use ACF Blocks V3.
- Supported ACF fields can be edited directly from the rendered preview on ACF PRO 6.7+ via automatic inline editing.
- The normal ACF field sidebar remains available for structured editing.
- Repeaters and Flexible Content still use ACF's larger field editor because ACF does not currently support their subfields for inline editing.
- The editor canvas loads Slick when enabled so interactive previews can initialize like the front end.


## 2.2.1 editor stability

Disables ACF automatic inline-detection for Flint blocks so structured Link/Image values are never replaced by detection marker strings during rendering. Front-end-style previews remain enabled. Hero and shared layout templates include defensive handling for unset/structured ACF values.

## 2.5.1
- Block availability settings are limited to Posts, Pages, and Flint-managed custom post types with Editor enabled.
- Replaced dashboard hiding with Editor-role capability controls for Gravity Forms, plugin updates, and user management.
- Added built-in WCAG-oriented accessibility scanner and Launch Checklist integration.
- Added Scripts & API Integrations settings for GA4, GTM, Google Maps API keys, and external script URLs.
- Added /login alias and automatic Apache directory-index protection where .htaccess is writable.
- Updated Launch Checklist for Rank Math, Gravity Forms reCAPTCHA, WHOIS, Scripts settings, Digital46 launch sheet, and Analytics notification.
- Added launch action to rename base-src to src-MM-DD-YY; the paired theme dynamically detects the renamed source directory.


## 2.5.1
- Launch checklist backup-plugin check now specifically targets All-in-One WP Migration and Backup / Unlimited Extension.

## 2.0
- Hide all `/wp/v2/users` REST API routes from unauthenticated/public requests to prevent public user enumeration.
- Authenticated requests continue to use WordPress's normal capability checks.
