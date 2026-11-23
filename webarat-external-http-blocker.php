<?php
/**
 * Plugin Name: Webarat External HTTP Blocker
 * Plugin URI:  https://github.com/rf-teen/webarat-external-http-blocker
 * Description: مسدودساز هوشمند، فوق‌سبک و بدون سربار درخواست‌های خروجی HTTP وردپرس با مدیریت پیشرفته دامنه‌ها، لیست مجاز، تشخیص منشأ و پشتیبان‌گیری.
 * Version:     1.2.0
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * Author:      Webarat
 * License:     GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Webarat\ExternalHttpBlocker;

use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Plugin
 * مدیریت مرکزی مسدودسازی درخواست‌های خروجی، لیست سفید، تشخیص منشأ و رابط کاربری
 */
final class Plugin
{
    public const VERSION = '1.2.0';
    public const OPTION_NAME = 'webarat_external_http_blocker_options';
    public const LOGS_OPTION = 'webarat_ext_http_blocked_logs';
    private const SETTINGS_GROUP = 'webarat_external_http_blocker_group';
    private const PAGE_SLUG = 'webarat-external-http-blocker';
    private const ERROR_CODE = 'webarat_external_http_request_blocked';
    private const MAX_LOGS = 30;

    private ?array $cachedOptions = null;

    public function __construct()
    {
        add_filter('pre_http_request', [$this, 'maybeBlockRequest'], 5, 3);
        add_action('admin_menu', [$this, 'registerSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_notices', [$this, 'renderNotices']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'addSettingsLink']);
        add_action('wp_ajax_webarat_clear_blocked_logs', [$this, 'handleClearLogs']);
        add_action('admin_post_webarat_export_settings', [$this, 'handleExportSettings']);
        add_action('admin_post_webarat_import_settings', [$this, 'handleImportSettings']);
    }

    /**
     * متد چندزبانه داینامیک و سبک بدون نیاز به فایل ترجمه سنگین
     */
    public static function t(string $key, ...$args): string
    {
        $locale = 'en_US';
        if (function_exists('determine_locale')) {
            $locale = determine_locale();
        } elseif (function_exists('get_user_locale')) {
            $locale = get_user_locale();
        } elseif (function_exists('get_locale')) {
            $locale = get_locale();
        }
        $isFa = str_starts_with(strtolower((string)$locale), 'fa');

        $dict = [
            'plugin_title'       => ['fa' => 'مسدودساز خروجی وب‌آرات', 'en' => 'Webarat HTTP Blocker'],
            'menu_title'         => ['fa' => 'مسدودساز HTTP', 'en' => 'HTTP Blocker'],
            'page_desc'          => ['fa' => 'مدیریت و مسدودسازی هوشمند درخواست‌های خروجی وردپرس برای افزایش حداکثری امنیت و سرعت بارگذاری.', 'en' => 'Smart management and blocking of outgoing WordPress requests for maximized security and page speed.'],
            'master_switch'      => ['fa' => 'وضعیت سیستم', 'en' => 'System Status'],
            'enable_blocking'    => ['fa' => 'فعال‌سازی سیستم مسدودسازی', 'en' => 'Enable Blocking System'],
            'blocked_domains'    => ['fa' => 'لیست سیاه دامنه‌ها', 'en' => 'Domain Blacklist'],
            'domains_desc'       => ['fa' => 'دامنه‌ها را در هر خط وارد کنید (مثال: telemetry.elementor.com).', 'en' => 'Enter target domains per line (e.g. telemetry.elementor.com).'],
            'whitelist_domains'  => ['fa' => 'لیست مجاز (Whitelist)', 'en' => 'Domain Whitelist'],
            'whitelist_desc'     => ['fa' => 'دامنه‌های استثنا که هرگز نباید مسدود شوند (در هر خط یک دامنه).', 'en' => 'Exceptions that must never be blocked (one domain per line).'],
            'subdomain_rules'    => ['fa' => 'قوانین زیردامنه', 'en' => 'Subdomain Rules'],
            'block_subs'         => ['fa' => 'مسدودسازی خودکار تمام زیردامنه‌ها (*.domain.com)', 'en' => 'Automated Wildcard Subdomain Blocking (*.domain.com)'],
            'presets_title'      => ['fa' => 'افزودن سریع تله‌متری‌های معروف:', 'en' => 'Quick Telemetry Presets:'],
            'logging_label'      => ['fa' => 'ثبت گزارشات', 'en' => 'Logging'],
            'logging_desc'       => ['fa' => 'ذخیره ۳۰ مورد از آخرین تلاش‌های مسدود شده به همراه منشأ فراخوانی', 'en' => 'Log last 30 blocked attempts including caller origin'],
            'save_btn'           => ['fa' => 'ذخیره تنظیمات', 'en' => 'Save Settings'],
            'recent_logs'        => ['fa' => 'گزارشات اخیر (Ring-Buffer)', 'en' => 'Recent Logs (Ring-Buffer)'],
            'clear_logs'         => ['fa' => 'پاک‌سازی تاریخچه', 'en' => 'Clear History'],
            'no_logs'            => ['fa' => 'هیچ درخواستی مسدود نشده است.', 'en' => 'No blocked requests recorded yet.'],
            'time'               => ['fa' => 'زمان', 'en' => 'Time'],
            'method'             => ['fa' => 'متد', 'en' => 'Method'],
            'target'             => ['fa' => 'دامنه مقصد', 'en' => 'Target Host'],
            'caller'             => ['fa' => 'منشأ درخواست', 'en' => 'Origin Caller'],
            'url'                => ['fa' => 'آدرس کامل (URL)', 'en' => 'Target URL'],
            'confirm_clear'      => ['fa' => 'آیا مطمئن هستید که تمام گزارشات اخیر پاک شوند؟', 'en' => 'Are you sure you want to clear all recent logs?'],
            'settings'           => ['fa' => 'تنظیمات', 'en' => 'Settings'],
            'tools_title'        => ['fa' => 'پشتیبان‌گیری و انتقال (Backup & Migrate)', 'en' => 'Backup & Migration'],
            'export_btn'         => ['fa' => 'دریافت خروجی JSON', 'en' => 'Export JSON Backup'],
            'import_btn'         => ['fa' => 'بازیابی تنظیمات', 'en' => 'Import Backup'],
            'import_file_label'  => ['fa' => 'انتخاب فایل پشتیبان:', 'en' => 'Select JSON backup file:'],
            'import_success_msg' => ['fa' => 'تنظیمات با موفقیت بازیابی شدند.', 'en' => 'Settings successfully imported.'],
            'import_error_msg'   => ['fa' => 'خطا: فایل پشتیبان نامعتبر است یا ساختار صحیحی ندارد.', 'en' => 'Error: The selected backup file is invalid.'],
            'ajax_success'       => ['fa' => 'گزارشات با موفقیت پاک شدند.', 'en' => 'Logs cleared successfully.'],
            'ajax_error'         => ['fa' => 'خطا در احراز هویت یا دسترسی غیرمجاز.', 'en' => 'Unauthorized access or invalid nonce.'],
            'error_blocked'      => ['fa' => 'درخواست به دامنه %s توسط وب‌آرات مسدود شد.', 'en' => 'Outgoing HTTP request to %s was blocked by Webarat.'],
        ];

        $text = $dict[$key][$isFa ? 'fa' : 'en'] ?? $key;
        return empty($args) ? $text : sprintf($text, ...$args);
    }

    /**
     * رهگیری و ارزیابی درخواست خروجی در عمیق‌ترین لایه شبکه وردپرس
     */
    public function maybeBlockRequest(false|array|WP_Error $preempt, array $parsedArgs, string $url): false|array|WP_Error
    {
        if ($preempt !== false) {
            return $preempt;
        }

        $options = $this->getOptions();
        if (empty($options['enabled']) || empty($options['domains'])) {
            return $preempt;
        }

        $host = wp_parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return $preempt;
        }

        $host = $this->normalizeHost($host);
        if ($host === '') {
            return $preempt;
        }

        // ۱. بررسی لیست مجاز (Whitelist)
        if (! empty($options['whitelist_domains'])) {
            if ($this->hostMatchesList($host, $options['whitelist_domains'], ! empty($options['block_subdomains']))) {
                return $preempt;
            }
        }

        // ۲. فیلتر برنامه‌نویسی اختصاصی برای استثناها
        if (apply_filters('webarat_blocker_allow', false, $url, $host)) {
            return $preempt;
        }

        // ۳. بررسی لیست سیاه (Blacklist)
        $blockSubdomains = ! empty($options['block_subdomains']);
        $matched = null;

        if (in_array($host, $options['domains'], true)) {
            $matched = $host;
        } elseif ($blockSubdomains) {
            foreach ($options['domains'] as $d) {
                if (str_ends_with($host, '.' . $d)) {
                    $matched = $d;
                    break;
                }
            }
        }

        if ($matched) {
            if (! empty($options['enable_logging'])) {
                $caller = $this->detectCaller();
                $this->logRequest($host, $url, $parsedArgs['method'] ?? 'GET', $caller);
            }

            return new WP_Error(self::ERROR_CODE, self::t('error_blocked', $host));
        }

        return $preempt;
    }

    /**
     * بررسی تطابق دامنه با لیست معین
     */
    private function hostMatchesList(string $host, array $list, bool $allowSubdomains): bool
    {
        if (in_array($host, $list, true)) {
            return true;
        }
        if ($allowSubdomains) {
            foreach ($list as $item) {
                if (str_ends_with($host, '.' . $item)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * تشخیص هوشمند منشأ فراخوانی‌کننده (پلاگین، قالب یا هسته وردپرس)
     */
    private function detectCaller(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';
            if ($file === '' || str_contains($file, 'webarat-external-http-blocker')) {
                continue;
            }
            if (preg_match('#wp-content[\\\\/](plugins|themes)[\\\\/]([^\\\\/]+)#', $file, $matches)) {
                $type = ($matches[1] === 'plugins') ? 'Plugin' : 'Theme';
                return $type . ': ' . sanitize_text_field($matches[2]);
            }
        }
        return 'Core';
    }

    /**
     * ثبت گزارش درخواست مسدود شده در بافر دایره‌ای (Ring-Buffer)
     */
    private function logRequest(string $host, string $url, string $method, string $caller): void
    {
        $logs = get_option(self::LOGS_OPTION, []);
        if (! is_array($logs)) {
            $logs = [];
        }

        array_unshift($logs, [
            'time'   => current_time('mysql'),
            'host'   => $host,
            'url'    => substr($url, 0, 250),
            'method' => strtoupper(substr($method, 0, 10)),
            'caller' => substr($caller, 0, 50),
        ]);

        update_option(self::LOGS_OPTION, array_slice($logs, 0, self::MAX_LOGS), false);
    }

    /**
     * پاک‌سازی گزارشات ذخیره‌شده از طریق AJAX با بررسی Nonce و سطح دسترسی
     */
    public function handleClearLogs(): void
    {
        check_ajax_referer('webarat_clear_nonce', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(self::t('ajax_error'));
        }
        delete_option(self::LOGS_OPTION);
        wp_send_json_success(['msg' => self::t('ajax_success')]);
    }

    /**
     * خروجی امن تنظیمات در قالب فایل JSON
     */
    public function handleExportSettings(): void
    {
        check_admin_referer('webarat_export_action', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_die(esc_html(self::t('ajax_error')), 403);
        }

        $options = $this->getOptions();
        $payload = [
            'generator' => 'Webarat External HTTP Blocker',
            'version'   => self::VERSION,
            'exported'  => current_time('mysql'),
            'settings'  => $options,
        ];

        $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $filename = 'webarat-http-blocker-' . gmdate('Y-m-d-His') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen((string) $json));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $json;
        exit;
    }

    /**
     * بازیابی امن تنظیمات از فایل JSON بارگذاری شده
     */
    public function handleImportSettings(): void
    {
        check_admin_referer('webarat_import_action', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_die(esc_html(self::t('ajax_error')), 403);
        }

        $file = $_FILES['webarat_import_file'] ?? null;
        if (! is_array($file) || empty($file['tmp_name']) || ! is_uploaded_file($file['tmp_name'])) {
            wp_safe_redirect(admin_url('options-general.php?page=' . self::PAGE_SLUG . '&webarat_notice=import_error'));
            exit;
        }

        // محدودیت سقف حجم فایل (حداکثر ۵۱۲ کیلوبایت) جهت جلوگیری از DoS و سربار حافظه
        if (($file['size'] ?? 0) > 512 * 1024) {
            wp_safe_redirect(admin_url('options-general.php?page=' . self::PAGE_SLUG . '&webarat_notice=import_error'));
            exit;
        }

        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            wp_safe_redirect(admin_url('options-general.php?page=' . self::PAGE_SLUG . '&webarat_notice=import_error'));
            exit;
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded) || ! isset($decoded['settings']) || ! is_array($decoded['settings'])) {
            wp_safe_redirect(admin_url('options-general.php?page=' . self::PAGE_SLUG . '&webarat_notice=import_error'));
            exit;
        }

        $imp = $decoded['settings'];
        $clean = $this->sanitize([
            'enabled'               => ! empty($imp['enabled']) ? 1 : 0,
            'block_subdomains'      => ! empty($imp['block_subdomains']) ? 1 : 0,
            'enable_logging'        => ! empty($imp['enable_logging']) ? 1 : 0,
            'domains_raw'           => implode("\n", (array) ($imp['domains'] ?? [])),
            'whitelist_domains_raw' => implode("\n", (array) ($imp['whitelist_domains'] ?? [])),
        ]);

        update_option(self::OPTION_NAME, $clean);
        $this->cachedOptions = $clean;

        wp_safe_redirect(admin_url('options-general.php?page=' . self::PAGE_SLUG . '&webarat_notice=import_success'));
        exit;
    }

    /**
     * نمایش پیام‌های وضعیت سیستم در بالای صفحه مدیریت
     */
    public function renderNotices(): void
    {
        if (! isset($_GET['page']) || $_GET['page'] !== self::PAGE_SLUG) {
            return;
        }

        $notice = sanitize_key($_GET['webarat_notice'] ?? '');
        if ($notice === 'import_success') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html(self::t('import_success_msg')) . '</strong></p></div>';
        } elseif ($notice === 'import_error') {
            echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html(self::t('import_error_msg')) . '</strong></p></div>';
        }
    }

    public function registerSettingsPage(): void
    {
        add_options_page(
            self::t('plugin_title'),
            self::t('menu_title'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderUI']
        );
    }

    public function registerSettings(): void
    {
        register_setting(self::SETTINGS_GROUP, self::OPTION_NAME, [
            'sanitize_callback' => [$this, 'sanitize'],
            'default'           => [
                'enabled'           => 1,
                'domains'           => [],
                'whitelist_domains' => [],
                'block_subdomains'  => 1,
                'enable_logging'    => 0,
            ],
        ]);
    }

    /**
     * پالایش دقیق و بهینه‌سازی دامنه‌ها
     */
    public function sanitize(mixed $input): array
    {
        $input = is_array($input) ? $input : [];

        $blacklist = $this->parseDomainList((string) ($input['domains_raw'] ?? ''));
        $whitelist = $this->parseDomainList((string) ($input['whitelist_domains_raw'] ?? ''));

        return [
            'enabled'           => ! empty($input['enabled']) ? 1 : 0,
            'block_subdomains'  => ! empty($input['block_subdomains']) ? 1 : 0,
            'enable_logging'    => ! empty($input['enable_logging']) ? 1 : 0,
            'domains'           => $blacklist,
            'whitelist_domains' => $whitelist,
        ];
    }

    /**
     * استخراج و نرمال‌سازی رشته چندخطی دامنه‌ها
     */
    private function parseDomainList(string $raw): array
    {
        $lines = preg_split('/\R/u', $raw) ?: [];
        $domains = [];
        foreach ($lines as $l) {
            $h = $this->extractHost($l);
            if ($h !== null && $h !== '') {
                $domains[] = $h;
            }
        }
        $domains = array_values(array_unique($domains));
        sort($domains);
        return $domains;
    }

    public function renderUI(): void
    {
        $opt = $this->getOptions();
        $isRtl = is_rtl();
        $align = $isRtl ? 'right' : 'left';
        ?>
        <style>
            .webarat-wrap { max-width: 1040px; }
            .webarat-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); padding: 26px 30px; margin-top: 20px; border: 1px solid #e5e7eb; }
            .webarat-header { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
            .webarat-icon { color: #2563eb; font-size: 32px !important; width: 32px !important; height: 32px !important; }
            .webarat-badge { background: #dcfce7; color: #166534; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; }
            .form-table th { font-weight: 600; color: #374151; width: 220px; }
            .log-table { width: 100%; border-collapse: collapse; margin-top: 15px; border-radius: 8px; overflow: hidden; border: 1px solid #f3f4f6; }
            .log-table th { background: #f9fafb; text-align: <?php echo $align; ?>; padding: 12px 14px; font-size: 12px; color: #4b5563; border-bottom: 1px solid #e5e7eb; }
            .log-table td { padding: 12px 14px; border-bottom: 1px solid #f3f4f6; font-size: 12px; }
            .preset-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; align-items: center; }
            .preset-btn { background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; padding: 4px 10px; font-size: 11px; cursor: pointer; color: #1f2937; transition: all 0.15s; }
            .preset-btn:hover { background: #e5e7eb; border-color: #9ca3af; }
            .caller-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
            .caller-plugin { background: #e0f2fe; color: #0369a1; }
            .caller-theme { background: #fef3c7; color: #b45309; }
            .caller-core { background: #f3f4f6; color: #4b5563; }
        </style>

        <div class="wrap webarat-wrap">
            <div class="webarat-header">
                <span class="dashicons dashicons-shield-alt webarat-icon"></span>
                <h1 style="margin: 0; font-weight: 700;"><?php echo esc_html(self::t('plugin_title')); ?></h1>
                <span class="webarat-badge">v<?php echo esc_html(self::VERSION); ?> Stable</span>
            </div>
            <p style="color: #6b7280; font-size: 14px; margin-top: 4px;"><?php echo esc_html(self::t('page_desc')); ?></p>

            <form action="options.php" method="post" class="webarat-card">
                <?php settings_fields(self::SETTINGS_GROUP); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html(self::t('master_switch')); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[enabled]" value="1" <?php checked($opt['enabled'], 1); ?>>
                                <strong><?php echo esc_html(self::t('enable_blocking')); ?></strong>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html(self::t('blocked_domains')); ?></th>
                        <td>
                            <textarea id="wb-domains" name="<?php echo self::OPTION_NAME; ?>[domains_raw]" rows="7" class="large-text code" dir="ltr" style="border-radius: 6px;"><?php echo esc_textarea(implode("\n", $opt['domains'])); ?></textarea>
                            <p class="description"><?php echo esc_html(self::t('domains_desc')); ?></p>
                            
                            <div class="preset-chips">
                                <span style="font-size: 11px; font-weight: 600; color: #6b7280;"><?php echo esc_html(self::t('presets_title')); ?></span>
                                <button type="button" class="preset-btn" onclick="webaratAddDomain('telemetry.elementor.com')">+ Elementor Telemetry</button>
                                <button type="button" class="preset-btn" onclick="webaratAddDomain('stats.wp.com')">+ Jetpack/WP Stats</button>
                                <button type="button" class="preset-btn" onclick="webaratAddDomain('tracking.woocommerce.com')">+ WooCommerce Tracker</button>
                                <button type="button" class="preset-btn" onclick="webaratAddDomain('api.freemius.com')">+ Freemius SDK</button>
                                <button type="button" class="preset-btn" onclick="webaratAddDomain('google-analytics.com'); webaratAddDomain('googletagmanager.com');">+ Google Analytics/GTM</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html(self::t('whitelist_domains')); ?></th>
                        <td>
                            <textarea name="<?php echo self::OPTION_NAME; ?>[whitelist_domains_raw]" rows="4" class="large-text code" dir="ltr" style="border-radius: 6px;"><?php echo esc_textarea(implode("\n", $opt['whitelist_domains'] ?? [])); ?></textarea>
                            <p class="description"><?php echo esc_html(self::t('whitelist_desc')); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html(self::t('subdomain_rules')); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[block_subdomains]" value="1" <?php checked($opt['block_subdomains'], 1); ?>>
                                <?php echo esc_html(self::t('block_subs')); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html(self::t('logging_label')); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[enable_logging]" value="1" <?php checked($opt['enable_logging'], 1); ?>>
                                <?php echo esc_html(self::t('logging_desc')); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(self::t('save_btn'), 'primary large'); ?>
            </form>

            <script>
            function webaratAddDomain(domain) {
                var textarea = document.getElementById('wb-domains');
                var lines = textarea.value.split('\n').map(function(l){ return l.trim(); }).filter(Boolean);
                if (lines.indexOf(domain) === -1) {
                    lines.push(domain);
                    textarea.value = lines.join('\n');
                }
            }
            </script>

            <?php if (! empty($opt['enable_logging'])): 
                $logs = get_option(self::LOGS_OPTION, []);
            ?>
            <div class="webarat-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0; font-size: 17px;"><?php echo esc_html(self::t('recent_logs')); ?></h2>
                    <?php if (! empty($logs)): ?>
                    <button id="wb-clear" class="button button-link-delete"><?php echo esc_html(self::t('clear_logs')); ?></button>
                    <?php endif; ?>
                </div>

                <?php if (empty($logs)): ?>
                    <p style="color: #9ca3af; padding-top: 10px; margin: 0;"><?php echo esc_html(self::t('no_logs')); ?></p>
                <?php else: ?>
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th style="width: 140px;"><?php echo esc_html(self::t('time')); ?></th>
                                <th style="width: 70px;"><?php echo esc_html(self::t('method')); ?></th>
                                <th style="width: 160px;"><?php echo esc_html(self::t('caller')); ?></th>
                                <th style="width: 200px;"><?php echo esc_html(self::t('target')); ?></th>
                                <th><?php echo esc_html(self::t('url')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $l): 
                                $caller = (string)($l['caller'] ?? 'Core');
                                $badgeClass = str_starts_with($caller, 'Plugin') ? 'caller-plugin' : (str_starts_with($caller, 'Theme') ? 'caller-theme' : 'caller-core');
                            ?>
                            <tr>
                                <td style="color: #6b7280; font-family: monospace;"><?php echo esc_html($l['time'] ?? '-'); ?></td>
                                <td><strong><?php echo esc_html($l['method'] ?? 'GET'); ?></strong></td>
                                <td><span class="caller-badge <?php echo esc_attr($badgeClass); ?>"><?php echo esc_html($caller); ?></span></td>
                                <td style="color: #dc2626; font-weight: 600; font-family: monospace;"><?php echo esc_html($l['host'] ?? '-'); ?></td>
                                <td dir="ltr" style="max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <code><?php echo esc_html($l['url'] ?? '-'); ?></code>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <script>
            document.getElementById('wb-clear')?.addEventListener('click', function(){
                if (!confirm("<?php echo esc_js(self::t('confirm_clear')); ?>")) return;
                var btn = this;
                btn.disabled = true;
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'webarat_clear_blocked_logs',
                        nonce: '<?php echo wp_create_nonce('webarat_clear_nonce'); ?>'
                    })
                }).then(function(){ window.location.reload(); });
            });
            </script>
            <?php endif; ?>

            <div class="webarat-card">
                <h2 style="margin: 0 0 14px 0; font-size: 17px;"><?php echo esc_html(self::t('tools_title')); ?></h2>
                <div style="display: flex; gap: 30px; flex-wrap: wrap; align-items: flex-start;">
                    <div style="flex: 1; min-width: 250px;">
                        <p style="margin-top: 0; color: #4b5563; font-size: 13px;"><?php echo esc_html(self::t('export_settings')); ?>:</p>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                            <input type="hidden" name="action" value="webarat_export_settings">
                            <?php wp_nonce_field('webarat_export_action', 'nonce'); ?>
                            <?php submit_button(self::t('export_btn'), 'secondary', 'submit', false); ?>
                        </form>
                    </div>

                    <div style="flex: 1.2; min-width: 280px; border-inline-start: 1px solid #e5e7eb; padding-inline-start: 30px;">
                        <p style="margin-top: 0; color: #4b5563; font-size: 13px;"><?php echo esc_html(self::t('import_settings')); ?>:</p>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="webarat_import_settings">
                            <?php wp_nonce_field('webarat_import_action', 'nonce'); ?>
                            <input type="file" name="webarat_import_file" accept=".json" required style="font-size: 12px; margin-bottom: 8px;">
                            <br>
                            <?php submit_button(self::t('import_btn'), 'secondary', 'submit', false); ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function getOptions(): array
    {
        if ($this->cachedOptions !== null) {
            return $this->cachedOptions;
        }
        $stored = get_option(self::OPTION_NAME, []);
        $defaults = [
            'enabled'           => 1,
            'domains'           => [],
            'whitelist_domains' => [],
            'block_subdomains'  => 1,
            'enable_logging'    => 0,
        ];
        return $this->cachedOptions = array_merge($defaults, (array) $stored);
    }

    private function extractHost(string $in): ?string
    {
        $h = trim($in);
        if ($h === '') {
            return null;
        }
        if (! str_contains($h, '://')) {
            $h = 'http://' . $h;
        }
        $parsed = wp_parse_url($h, PHP_URL_HOST);
        return $parsed ? $this->normalizeHost($parsed) : null;
    }

    private function normalizeHost(string $h): string
    {
        $h = strtolower(trim($h, " ."));
        if ($h === '') {
            return '';
        }
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($h, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii !== false && $ascii !== '') {
                $h = $ascii;
            }
        }
        return $h;
    }

    public function addSettingsLink(array $links): array
    {
        array_unshift(
            $links,
            sprintf('<a href="%s">%s</a>', admin_url('options-general.php?page=' . self::PAGE_SLUG), self::t('settings'))
        );
        return $links;
    }
}

new Plugin();
