# WP Animted Counter

WP Animated Counter is a standalone WordPress plugin for reusable animated counters. It has an English admin editor, shortcodes and optional WPBakery integration. Woodmart, WP Swiper and jQuery are not required.

## Install and use

Download the plugin ZIP from [Releases](https://github.com/Andrusi4ka/WP-Animted-Counter/releases), upload it under **Plugins > Add New > Upload Plugin** and activate **WP Animated Counter**. Keep the `wp-cunter` folder name when updating an existing installation.

1. Open **Animated Counter > Add counter** and enter a title.
2. Configure **Numbers**, **Text**, **Appearance** and **Animations** on the single editor page.
3. Click **Save** to publish a new counter or update an existing one. **Delete** moves a saved counter to Trash using WordPress permissions and nonce protection.
4. Copy `[wpcunter_counter id="123"]` from **Embed counter**, or select the saved counter in the **Animated Counter** WPBakery element. With Woodmart it appears under **Theme elements**, otherwise under **Content**.

`[wpcunter_saved id="123"]` is an alias. **Animated Counter — Custom** provides inline WPBakery settings. Direct attributes also work:

```text
[wpcunter_counter value="100" suffix="%" label="Completed" counter_animation="ring_band" duration="2000" slow_down="1" ring_color="#0099ee" ring_thickness="8" ring_size="240" ring_size_mobile="160"]
```

## Effects and settings

- **None — number only:** count without an additional visual effect.
- **Ring fill:** a complete circle fills in sync with the number.
- **Arc with marker:** a 300-degree arc with a circular marker at its tip and a symmetric gap at the bottom.
- **Gradient arc with marker:** an angular gradient from 0% opacity at the start to the selected color at 100% opacity by the marker. No separate outline. **Gradient thickness, px** directly controls the band width.

All effects keep the number, suffix and label centered. Each effect defines its layout; obsolete alignment controls, placeholders and Design options tabs have been removed. The containing column positions the entire compact group through standard text or flex alignment. A compatibility class prevents Woodmart from forcing the group to full width, without calling theme APIs.

The **Animation duration, ms** default is 2000 (two seconds), with a range of 100–60000ms. **Slow down at the end** applies quartic easing to both the number and effect; disabling it gives linear motion. Counting starts when visible and runs once. Without JavaScript or with reduced motion, final values remain visible. Screen readers receive the final value rather than each animation frame. Number width is reserved before counting and refreshed for resized screens and loaded fonts.

**Appearance** controls the shared number/suffix/label color; effect and marker colors are independent. Fonts inherit from the theme. The theme must call standard `wp_head()` and `wp_footer()` hooks. The small stylesheet loads in the head; the animation script loads when a counter renders.

## Responsive values and compatibility

Number/text sizes and weights, ring diameter/thickness and marker diameter have Desktop, Tablet and Mobile controls. Desktop is >=1025px, Tablet is 768–1024px and Mobile is <=767px. Empty tablet values inherit Desktop; empty mobile values inherit Tablet, then Desktop. Numeric zero is preserved where allowed. Pixel sizes for numbers and labels are independent.

Existing counter IDs and shortcodes remain valid. Legacy size presets are translated into their previous pixel values. Previously saved padding, margin, radius and background settings are retained for compatibility, while their controls are hidden. Removed placeholder effects fall back to number-only mode; old position attributes are ignored in favor of each effect's centered layout.

## Development and verification

Requires WordPress 6.0+ and PHP 7.4+. Node.js 24 is used only for development checks.

```sh
npm ci
php tests/render.php
npm test
npm run test:browser
npm run audit:duplicates
npm run audit:dead-code
```

PHP must be on PATH or set `PHP_BIN`. Browser tests use installed Google Chrome; override its executable with `BROWSER_BIN`. No browser downloads are needed.

- PHP tests cover schema validation, safe saves, permissions, decimal values, legacy conversion, shortcode privacy, geometry and defaults.
- DOM tests cover the current single-page admin, 50/25/25 animation row, responsive switching, effect labels and field visibility.
- Chrome tests sample actual gradient pixels, verify SVG masks/markers, responsive dimensions, reduced motion, stable digit width and synchronized progress.
- Static checks cover duplicate CSS rules/properties, unreferenced plugin CSS classes, image references/hashes, PHP function references and version consistency.
- jscpd scans production PHP/JS/CSS for clones (10 lines / 100 tokens minimum) and checks JavaScript dead code with both enqueued scripts as entry points. PHP reference checks are heuristic, not a complete reachability proof.

An optional live WordPress test checks database persistence, native Save/publish handling, gradient settings, a protected Trash link and unpublished-counter privacy. It creates and deletes only its own fixture posts, restores the CLI current user and does not change existing counters:

```sh
WP_ROOT=/path/to/wordpress php tests/wordpress.php
```

GitHub Actions runs PHP 7.4/8.3/8.4 checks and a Chrome run on PHP 8.3. Automated tests do not guarantee visual compatibility with every theme/browser.

## Release

After committing production changes, `npm run build` creates `dist/wp-animated-counter-1.9.0.zip` and `SHA256SUMS.txt` from that exact commit. Tests, dependencies, workflows and development tools are excluded. A matching `v1.9.0` tag runs CI before publishing the release and attaching the ZIP/checksum.

GPL-3.0-or-later. See [LICENSE](LICENSE) and [third-party notices](THIRD-PARTY-NOTICES.md).