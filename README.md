# Webarat External HTTP Blocker 🛡️

[![Version](https://img.shields.io/badge/version-1.1.3-blue.svg)](https://github.com/webarat/webarat-external-http-blocker/releases)
[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4.svg)](https://php.net)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](LICENSE)

A ultra-lightweight, zero-overhead WordPress plugin designed to intercept, monitor, and block outgoing HTTP/HTTPS requests at the core level (`pre_http_request`).

---

## 🚀 Key Features

- **⚡ Zero-Overhead Architecture:** Uses in-memory caching and stores settings in a single unified database option. No bloated database tables.
- **🌐 Zero-Dependency Micro-i18n:** Built-in dynamic localization engine (`Plugin::t()`) utilizing `determine_locale()`. Delivers seamless **Persian (`fa_IR`)** and **English (`en_US`)** experiences without the I/O cost of `.mo/.po` files.
- **🎯 Dynamic Wildcard & Subdomain Blocking:** Blocks exact domains or automatically intercepts all subdomains (`*.domain.com`).
- **🔤 RFC & IDN Compliant:** Supports Internationalized Domain Names (IDN / Punycode) via UTS #46 standard algorithms.
- **📊 Ring-Buffer Request Logging:** Keeps a memory-efficient circular log of the last 30 blocked requests with AJAX one-click cleanup.
- **🎨 Modern RTL/LTR Adaptive UI:** Card-based dashboard with responsive layout and dynamic text alignment.
- **🔒 Secure & Clean:** Hardened with WordPress Nonces, capability checks (`manage_options`), and a dedicated `uninstall.php` script for complete cleanup.

---

## 📦 Installation

### From GitHub Releases:
1. Download the latest `webarat-external-http-blocker-1.1.3.zip` from the [Releases](https://github.com/webarat/webarat-external-http-blocker/releases) page.
2. In WordPress Admin, navigate to **Plugins -> Add New -> Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Activate the plugin.

### Manual / Git Clone:
```bash
cd wp-content/plugins/
git clone https://github.com/webarat/webarat-external-http-blocker.git
```

---

## ⚙️ Configuration

Navigate to **Settings -> HTTP Blocker** (یا در پیشخوان فارسی: **تنظیمات -> مسدودساز HTTP**):

1. **System Status:** Toggle outgoing request blocking on/off.
2. **Domain Blacklist:** Add one target domain per line (e.g. `api.elementor.com`, `tracking.example.org`).
3. **Subdomain Rules:** Enable to block all child subdomains automatically.
4. **Logging:** Enable circular logging to monitor blocked requests with timestamps, HTTP verbs, and destination URLs.

---

## 🛠️ Developer Hooks & Extensibility

You can programmatically allow specific requests using the built-in filter:

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

افزونه **Webarat External HTTP Blocker** یک ابزار سبک و بهینه‌سازی‌شده برای وردپرس است که با هوک در عمیق‌ترین لایه شبکه وردپرس (`pre_http_request`)، درخواست‌های خروجی ناخواسته به سرورهای خارجی را بدون کوچک‌ترین فشار بر سرور مسدود می‌کند.

### ویژگی‌های کلیدی:
- عدم نیاز به فایل‌های ترجمه سنگین و تشخیص خودکار زبان ادمین
- پشتیبانی کامل از راست‌چین (RTL) و چپ‌چین (LTR)
- ثبت آخرین ۳۰ درخواست مسدود شده در قالب Ring-Buffer
- بازنویسی کاملاً شیءگرا و سازگار با PHP 8.2 به بالا

---

## 📄 License

This project is licensed under the **GPL v2 or later**. See the [LICENSE](LICENSE) file for details.
