**WP Animated Counter 1.9.1** adds native WordPress updates and counter duplication, and fixes the Save warning.

- Check for updates from the Plugins page and install future GitHub releases using the standard Update now action.
- View release version details in WordPress. Update checks are cached and handle unavailable GitHub responses safely.
- Duplicate a counter from All counters. The copy opens as a new draft, preserving settings and receiving its own shortcode ID.
- Save new and existing counters without the incorrect leave-page warning.
- Existing counters, settings and shortcodes are preserved.

Validation: PHP 7.4/8.3/8.4 rendering and updater tests, admin/frontend tests, real Chrome animation checks, live WordPress save/duplicate/permissions checks, duplicate and dead-code scans.

Install **wp-animated-counter-1.9.1.zip** using Plugins > Add New > Upload Plugin. Existing versions without the updater require this one-time manual installation; subsequent releases can be installed through WordPress. Keep the wp-cunter folder name. **SHA256SUMS.txt** contains the release ZIP checksum.
