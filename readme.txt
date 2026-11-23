=== Webarat External HTTP Blocker ===
Contributors: rf-teen, rf_pouralizadeh
Donate link: https://github.com/rf-teen/webarat-external-http-blocker
Tags: http-blocker, firewall, outgoing-traffic, privacy, security, telemetry
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.2
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An ultra-lightweight, zero-overhead WordPress plugin to intercept, monitor, and block outgoing HTTP/HTTPS requests at core network level.

== Description ==

Webarat External HTTP Blocker is an ultra-lightweight, zero-overhead WordPress security plugin designed to intercept, monitor, and block outgoing HTTP and HTTPS requests at the deepest WordPress core level (`pre_http_request`).

Unlike bulky firewall and security suites, this plugin introduces no database bloat, requires no background cron jobs, and adds zero overhead to page generation time. Settings are stored in a single unified option with autoload disabled, and logs are kept in a circular ring-buffer.

= 🚀 Key Features =
* **Zero-Overhead Architecture:** Uses in-memory caching and stores settings in a single unified database option with autoload disabled. No bloated custom database tables.
* **Caller & Origin Detection:** Intelligent lightweight backtrace inspection to pinpoint exactly which plugin, theme, or core file triggered the outbound HTTP request.
* **Domain Whitelist (Allowlist):** Define trusted exception domains or webhook URLs directly from the dashboard to bypass blocking.
* **Dynamic Wildcard & Subdomain Blocking:** Blocks exact domains or automatically intercepts all child subdomains (*.domain.com).
* **Quick Telemetry Presets:** One-click presets for common telemetry/phone-home domains (Elementor, Jetpack, WooCommerce, Google Analytics, Freemius).
* **JSON Backup & Migration:** Effortless one-click export and import of all settings and rules in standard JSON format.
* **Ring-Buffer Logging:** Keeps a memory-efficient circular log of the last 30 blocked attempts with timestamps, HTTP methods, caller origin, and one-click AJAX cleanup.
* **RFC & IDN Compliant:** Supports Internationalized Domain Names (IDN / Punycode) via UTS #46 standard algorithms.
* **Modern RTL & LTR Adaptive UI:** Card-based responsive admin dashboard.
* **Zero-Dependency Micro-i18n:** Built-in dynamic localization engine delivering seamless Persian (fa_IR) and English (en_US) experiences without .mo/.po file I/O overhead.

== Installation ==

1. Download the plugin ZIP archive.
2. In WordPress Admin, navigate to **Plugins -> Add New -> Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Activate the plugin through the 'Plugins' menu in WordPress.
5. Navigate to **Settings -> HTTP Blocker** (or in Persian: **تنظیمات -> مسدودساز HTTP**) to configure your blocking rules.

== Frequently Asked Questions ==

= Does this plugin slow down my WordPress site? =
No! In fact, it often significantly speeds up page loading and admin response times by preventing slow or failing external API requests, telemetries, and timeouts from third-party plugins.

= Can I allow certain webhooks or URLs programmatically? =
Yes, you can use the built-in developer filter hook:
`add_filter('webarat_blocker_allow', function(bool $allow, string $url, string $host): bool { return $allow; }, 10, 3);`

= Does this plugin require third-party services or APIs? =
No. It operates 100% locally on your WordPress server. No external connections, tracking, or remote dependencies.

== Screenshots ==

1. Webarat External HTTP Blocker main settings dashboard with presets and whitelist.
2. Ring-buffer blocked requests log showing caller origin and target host.
3. Backup and migration tools for JSON import/export.

== Changelog ==

= 1.2.0 =
* Added: Native Domain Whitelist / Allowlist in settings.
* Added: Origin Caller detection identifying which plugin, theme, or core file sent the request.
* Added: One-click Quick Presets for common telemetry services (Elementor, WooCommerce, Jetpack, Freemius, Google).
* Added: JSON Settings Backup and Restore tools.
* Added: Enqueued external stylesheet and script assets for WordPress coding standard compliance.
* Added: Automated CI/CD test workflow on PHP 8.2, 8.3, and 8.4.

= 1.1.3 =
* Initial public release with ring-buffer request logging, UTS #46 IDN domain normalization, and dual Persian/English micro-i18n.
