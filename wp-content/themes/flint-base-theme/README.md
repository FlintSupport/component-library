# (ƒ) FLINT - Component Library 2.0

This theme is intentionally the **presentation layer**. Edit this package when markup, block templates, SCSS/CSS, or site JavaScript needs to change.

## Responsibilities
- Header/footer and page templates
- ACF block registration and rendering
- Component styles and site JavaScript
- Header/footer layout registry consumed by Flint Base plugin

## Pairing
Install and activate **(ƒ) FLINT - Theme Builder** first, then activate this theme. The plugin owns stable behavior and the **Flint > Starting Setup** screen.

## 2.0.1
- Harden ACF block registration with an `acf/init` registration plus guarded `init` fallback.
- Prevent duplicate Flint ACF block registration.

## 2.1.0
Companion release for Flint Base 2.1.0. Theme presentation architecture is unchanged; version aligned with the expanded unified Theme Settings experience.

## 2.2.0 editor previews
The block editor canvas now loads the same component/theme CSS and site behavior used on the front end. Flint ACF blocks are registered as V3 blocks with automatic inline editing enabled, so supported fields can be edited from the visual preview while complex field structures retain ACF's full editor as a fallback.


## 2.2.1 editor stability

Disables ACF automatic inline-detection for Flint blocks so structured Link/Image values are never replaced by detection marker strings during rendering. Front-end-style previews remain enabled. Hero and shared layout templates include defensive handling for unset/structured ACF values.

## 2.5.1
- Theme asset loading now dynamically detects a launch-renamed src-MM-DD-YY source directory, falling back to base-src during development.


## 2.5.1
- WCAG-oriented theme audit: semantic accordion/tabs/dialog controls, keyboard interactions, skip link/main landmark, persistent focus indicators, reduced-motion support, accessible search labels, and safer navigation controls.
