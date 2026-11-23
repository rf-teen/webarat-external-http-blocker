# Webarat External HTTP Blocker 🛡️

[![Version](https://img.shields.io/badge/version-1.2.0-blue.svg)](https://github.com/rf-teen/webarat-external-http-blocker/releases)
[![CI](https://github.com/rf-teen/webarat-external-http-blocker/actions/workflows/ci.yml/badge.svg)](https://github.com/rf-teen/webarat-external-http-blocker/actions/workflows/ci.yml)
[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4.svg)](https://php.net)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](LICENSE)

An ultra-lightweight, zero-overhead WordPress plugin designed to intercept, monitor, and block outgoing HTTP/HTTPS requests at the core network level (`pre_http_request`).

---

## 🚀 Key Features

- **⚡ Zero-Overhead Architecture:** Uses in-memory caching and stores settings in a single unified database option with `autoload = false`. No bloated custom database tables.
- **🌐 Zero-Dependency Micro-i18n:** Built-in dynamic localization engine (`Plugin::t()`) utilizing `determine_locale()`. Delivers seamless **Persian (`fa_IR`)** and **English (`en_US`)** experiences without the I/O cost of `.mo/.po` files.
- **🎯 Dynamic Blacklist & Wildcard Subdomain Blocking:** Blocks exact domains or automatically intercepts all child subdomains (`*.domain.com`).
- **🛡️ Native Whitelist / Allowlist:** Define exception domains or URLs that must never be blocked, directly from the admin interface.
- **🔍 Caller & Origin Detection:** Intelligent lightweight backtrace inspection to pinpoint exactly which plugin, theme, or core file triggered the outgoing request.
- **⚡ Quick Telemetry Presets:** One-click presets for common telemetry/phone-home domains (Elementor, Jetpack, WooCommerce, Freemius, Google Analytics).
- **💾 JSON Backup & Migration:** Effortless one-click export and import of all settings and rules in standard JSON format.
- **🔤 RFC & IDN Compliant:** Supports Internationalized Domain Names (IDN / Punycode) via UTS #46 standard algorithms.
- **📊 Ring-Buffer Request Logging:** Keeps a memory-efficient circular log of the last 30 blocked requests with AJAX one-click cleanup.
- **🎨 Modern RTL/LTR Adaptive UI:** Sleek card-based dashboard with responsive layout and automatic text alignment.
- **🔒 Hardened Security:** Strict capability checks (`manage_options`), WordPress nonces, safe JSON parsing, and a dedicated `uninstall.php` script for complete cleanup.

---

## 📦 Installation

### From GitHub Releases:
1. Download the latest `webarat-external-http-blocker-1.2.0.zip` from the [Releases](https://github.com/rf-teen/webarat-external-http-blocker/releases) page.
2. In WordPress Admin, navigate to **Plugins -> Add New -> Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Activate the plugin.

### Manual / Git Clone:
```bash
cd wp-content/plugins/
git clone https://github.com/rf-teen/webarat-external-http-blocker.git
```

---

## ⚙️ Configuration

Navigate to **Settings -> HTTP Blocker** (یا در پیشخوان فارسی: **تنظیمات -> مسدودساز HTTP**):

1. **System Status:** Toggle outgoing request blocking on/off.
2. **Domain Blacklist:** Add target domains to block, one per line (e.g. `telemetry.elementor.com`). Use the Quick Preset buttons to add common telemetry domains with a single click.
3. **Domain Whitelist:** Add trusted domains or webhooks that should bypass blocking.
4. **Subdomain Rules:** Enable to automatically block all subdomains of blacklisted domains.
5. **Logging:** Enable circular logging to monitor blocked requests with timestamps, HTTP verbs, caller origins (Plugin/Theme), and destination URLs.
6. **Backup & Migration:** Export or import rules via JSON to synchronize settings across multiple WordPress sites.

---

## 🛠️ Developer Hooks & Extensibility

You can also programmatically allow specific requests using the built-in filter:

```php
add_filter('webarat_blocker_allow', function (bool $allow, string $url, string $host): bool {
    // Whitelist a specific webhook or healthcheck
    if (str_contains($url, '/critical-webhook/')) {
        return true; // Bypass blocking
    }
    return $allow;
}, 10, 3);
```

---

## 🇮🇷 راهنمای فارسی

افزونه **Webarat External HTTP Blocker** یک ابزار حرفه‌ای، فوق‌العاده سبک و بهینه‌سازی‌شده برای وردپرس است که با هوک در عمیق‌ترین لایه شبکه وردپرس (`pre_http_request`)، درخواست‌های خروجی ناخواسته به سرورهای خارجی را بدون کوچک‌ترین فشار بر سرور مسدود می‌کند.

### ویژگی‌های کلیدی نگارش 1.2.0:
- **لیست مجاز (Whitelist):** امکان تعیین دامنه‌های استثنا که نباید تحت هیچ شرایطی مسدود شوند.
- **تشخیص منشأ فراخوانی (Caller Detection):** شناسایی دقیق افزونه یا قالبی که درخواست خروجی را ارسال کرده است.
- **پری‌ست‌های سریع تله‌متری:** افزودن دامنه‌های ردیابی و آمارگیر معروف (المنتور، ووکامرس، جت‌پک، گوگل و فریموس) با یک کلیک.
- **پشتیبان‌گیری و انتقال تنظیمات:** دریافت خروجی و بازیابی قوانین با فایل استاندارد JSON.
- **بهینگی و سرعت مطلق:** بدون جداول اضافه در پایگاه‌داده و ذخیره لاگ‌ها در بافر چرخشی ۳۰ تایی.
- **سازگاری کامل دوزبانه (فارسی و انگلیسی):** بدون نیاز به فایل‌های حجیم ترجمه، با رابط کاربری واکنش‌گرا و راست‌چین (RTL).
- **امنیت سخت‌گیرانه:** اعتبارسنجی با Nonce، بررسی دسترسی ادمین و پالایش کامل ورودی‌ها مطابق استانداردهای وردپرس و PHP 8.2+.

---

## 📄 License

This project is licensed under the **GPL v2 or later**. See the [LICENSE](LICENSE) file for details.
