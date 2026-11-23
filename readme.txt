=== Webarat External HTTP Blocker ===
Contributors: rf-teen, rf_pouralizadeh
Donate link: https://github.com/rf-teen/webarat-external-http-blocker
Tags: http-blocker, firewall, outgoing-traffic, privacy, security, telemetry, persian, iran, speed
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An ultra-lightweight, zero-overhead WordPress plugin to intercept, monitor, and block outgoing HTTP/HTTPS requests at core network level.

== Description ==

Webarat External HTTP Blocker is an ultra-lightweight, zero-overhead WordPress security plugin designed to intercept, monitor, and block outgoing HTTP and HTTPS requests at the deepest WordPress core level (`pre_http_request`).

Unlike bulky firewall and security suites, this plugin introduces no database bloat, requires no background cron jobs, and adds zero overhead to page generation time. Settings are stored in a single unified option with autoload disabled, and logs are kept in a circular ring-buffer.

= 🚀 Key Features (English) =
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

= 🇮🇷 معرفی و راهنمای فارسی (Webarat External HTTP Blocker) =

افزونه **Webarat External HTTP Blocker** (مسدودساز خروجی وب‌آرات) یک فایروال فوق‌العاده سبک، بدون سربار و بهینه‌سازی‌شده برای وردپرس است که جهت رهگیری، پایش و مسدودسازی هوشمند درخواست‌های خروجی HTTP و HTTPS در عمیق‌ترین لایه شبکه وردپرس (`pre_http_request`) طراحی شده است.

بسیاری از افزونه‌ها و قالب‌های وردپرس به‌صورت مخفیانه یا ناخواسته، درخواست‌های تله‌متری، آمارگیری، لایسنس و ارتباط با سرورهای خارجی ارسال می‌کنند. این امر در سرورهای داخل کشور یا شبکه‌های با پهنای باند محدود باعث معطلی، تایم‌اوت‌های طولانی، کندی شدید لود صفحات و مصرف بی‌مورد منابع سرور می‌شود. این افزونه جلوی تمامی این درخواست‌های ناخواسته را در کسری از میلی‌ثانیه می‌گیرد.

= 🌟 قابلیت‌های برجسته برای کاربران وردپرس فارسی =
* **سرعت و سبکی مطلق (Zero-Overhead):** بدون ایجاد حتی یک جدول اضافی در پایگاه‌داده و ذخیره اطلاعات در کش حافظه بدون تحمیل فشار به هاست و دیتابیس.
* **تشخیص دقیق منشأ درخواست (Caller Detection):** شناسایی و نمایش مستقیم نام افزونه، قالب یا کدی که در حال تلاش برای برقراری ارتباط خارجی بوده است.
* **لیست سفید و مجاز (Whitelist):** امکان تعیین دامنه‌های استثنا و ضروری تا درخواست‌های مهم و وب‌هوک‌ها هرگز قطع نشوند.
* **پری‌ست‌های سریع تله‌متری:** اضافه کردن دامنه‌های ردیابی معروف وردپرس (مثل ردیاب المنتور، ووکامرس، جت‌پک، فریموس و گوگل) با یک کلیک.
* **مسدودسازی زیردامنه‌ها (Wildcard):** امکان فیلتر خودکار تمام زیردامنه‌ها (*.domain.com).
* **پشتیبانی کامل دوزبانه و راست‌چین (RTL):** پنل مدیریت کاملاً واکنش‌گرا و فارسی بدون نیاز به فایل‌های سنگین ترجمه.
* **پشتیبان‌گیری و جابه‌جایی سریع (JSON Backup):** خروجی گرفتن و انتقال ساده تنظیمات بین سایت‌های مختلف با فایل استاندارد JSON.
* **بافر چرخشی گزارشات (Ring-Buffer):** ثبت ۳۰ مورد از آخرین تلاش‌های مسدود شده با نمایش زمان دقیق، متد، دامنه مقصد، منشأ فراخوانی و امکان پاک‌سازی سریع با ایجکس.

== Installation ==

= English =
1. Download the plugin ZIP archive.
2. In WordPress Admin, navigate to **Plugins -> Add New -> Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Activate the plugin through the 'Plugins' menu in WordPress.
5. Navigate to **Settings -> HTTP Blocker** to configure your blocking rules.

= راهنمای نصب در وردپرس فارسی =
۱. فایل فشرده (ZIP) افزونه را دانلود کنید.
۲. در پیشخوان وردپرس به مسیر **افزونه‌ها -> افزودن افزونه -> بارگذاری افزونه** بروید.
۳. فایل ZIP را انتخاب کرده و روی **نصب** کلیک کنید.
۴. افزونه را فعال نمایید.
۵. از منوی **تنظیمات -> مسدودساز HTTP** وارد پیشخوان افزونه شده و دامنه‌های مورد نظر را مسدود یا مدیریت کنید.

== Frequently Asked Questions ==

= Does this plugin slow down my WordPress site? =
No! In fact, it often significantly speeds up page loading and admin response times by preventing slow or failing external API requests, telemetries, and timeouts from third-party plugins.

= آیا این افزونه باعث کندی سایت وردپرسی می‌شود؟ =
خیر! برعکس، این افزونه با جلوگیری از درخواست‌های خروجی سنگین، کند یا مسدودشده افزونه‌های جانبی، تایم‌اوت‌ها را حذف کرده و سرعت لود پیشخوان و سایت را به‌طور چشم‌گیری افزایش می‌دهد.

= Can I allow certain webhooks or URLs programmatically? =
Yes, you can use the built-in developer filter hook:
`add_filter('webarat_blocker_allow', function(bool $allow, string $url, string $host): bool { return $allow; }, 10, 3);`

= چگونه می‌توان یک دامنه، وب‌هوک یا آدرس خاص را مجاز کرد؟ =
هم می‌توانید دامنه را در کادر «لیست مجاز (Whitelist)» در پنل تنظیمات افزونه وارد کنید و هم از طریق فیلتر برنامه‌نویسی `webarat_blocker_allow` استثنا تعریف کنید.

= آیا این افزونه به سرویس یا API خارجی وابسته است؟ =
خیر، این افزونه ۱۰۰٪ به‌صورت محلی روی سرور شما اجرا می‌شود و هیچ‌گونه وابستگی، اتصال خارجی یا ارسال اطلاعات به سرورهای شخص ثالث ندارد.

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
* Added: Complete bilingual Persian & English documentation for WordPress.org and fa.wordpress.org.
* Added: Automated CI/CD test workflow on PHP 8.2, 8.3, and 8.4.

= 1.1.3 =
* Initial public release with ring-buffer request logging, UTS #46 IDN domain normalization, and dual Persian/English micro-i18n.
