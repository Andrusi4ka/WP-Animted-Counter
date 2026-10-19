# WP Animted Counter

WP Animated Counter is a theme-independent WordPress plugin with reusable counters, an English admin editor, blue accents, shortcodes and optional WPBakery integration. No Woodmart, WP Swiper, jQuery or external animation library is required.

## Installation

Download `wp-animated-counter-1.8.0.zip` from [Releases](https://github.com/Andrusi4ka/WP-Animted-Counter/releases), upload it under **Plugins > Add New > Upload Plugin**, then activate **WP Animated Counter**. Keep the existing `wp-cunter` folder name when updating this project.

## Usage

1. Open **Animated Counter > Add counter**.
2. Configure **Numbers**, **Text**, **Layout** and **Appearance**, then publish.
3. Paste `[wpcunter_counter id="123"]` into a Shortcode block, widget or page.
4. With WPBakery, select a published counter in **Animated Counter**. It appears under **Theme elements** with Woodmart, otherwise under **Content**.

`[wpcunter_saved id="123"]` is an alias. **Animated Counter — Custom** provides inline WPBakery settings. Direct shortcodes also work:

```text
[wpcunter_counter value="150" suffix="+" label="Completed projects" font_size="44" font_size_mobile="28" label_font_size="18"]
```

Existing counter IDs and shortcodes remain valid. Legacy size presets are converted to their previous pixel values on read, then stored as numeric sizes on the next save. Theme-specific backdrop/entrance animation settings from early versions are not supported by the standalone renderer.

## Features

- Start/final values, arbitrary suffixes and label text.
- Independent numeric sizes and font weights for numbers and labels.
- Visual text alignment, animation position and vertical alignment controls.
- Shared inherited/light/dark/custom color scheme, background, padding, margin and radius.
- Desktop (>=1025px), tablet (768–1024px) and mobile (<=767px) controls for typography and spacing. Empty tablet values inherit desktop; empty mobile values inherit tablet, then desktop. Zero spacing is an explicit value.
- Three-second counting on entering view, decreasing values and reduced-motion support. Without JavaScript the final value remains visible. Screen readers receive the final value rather than every animation frame.

Additional animation effects are **placeholders** in this release. Selecting a placeholder displays a numbered static block to demonstrate the chosen layout. Fonts inherit from the active theme. Themes must call normal `wp_head()` and `wp_footer()` hooks. The small stylesheet loads in the page head; the counting script loads only when a counter renders.

## Development and verification

Requires PHP 7.4+; Node.js 24 is used for development checks only.

```sh
npm ci
php tests/render.php
npm test
npm run audit:duplicates
npm run audit:dead-code
npm run build
```

For DOM tests, PHP must be on PATH or set `PHP_BIN` to its executable. `npm test` covers animation, actual editor DOM interactions through jsdom, CSS structure, PHP function references, asset duplicates and version consistency. The PHP suite tests rendering, validation, persistence guards, legacy conversion and device inheritance with WordPress API stubs. jscpd scans production PHP/JS/CSS for clones (10 lines / 100 tokens minimum); its JS dead-code check treats both WordPress-enqueued scripts as entry points. PHP reference checks are a heuristic, not proof that every branch is reachable.

An optional live WordPress test creates and deletes only its own fixture posts:

```sh
WP_ROOT=/path/to/wordpress php tests/wordpress.php
```

Validated locally with PHP 7.4, 8.3 and 8.4, DOM tests and the local WordPress database. GitHub Actions repeats the standalone checks on all three PHP versions. Tests do not replace visual checks across arbitrary themes and browsers.

## Release

`npm run build` packages only committed production files into `dist/wp-animated-counter-1.8.0.zip` and writes `SHA256SUMS.txt`. Tests, node_modules, workflows and development tools are excluded. Pushing a matching `v1.8.0` tag runs checks before the release job attaches the ZIP and checksum.

## License

GPL-3.0-or-later. See [LICENSE](LICENSE) and [third-party notices](THIRD-PARTY-NOTICES.md) for bundled layout illustrations.