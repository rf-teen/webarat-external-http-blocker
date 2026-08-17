<?php
/**
 * Plugin Name: Webarat External HTTP Blocker
 * Plugin URI:  https://webarat.com
 * Description: مسدودساز هوشمند و بدون سربار درخواست‌های خروجی HTTP وردپرس با مدیریت پیشرفته دامنه‌ها.
 * Version:     1.1.3
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
 * مدیریت مرکزی مسدودسازی درخواست‌های خروجی و رابط کاربری ادمین
 */
final class Plugin
{
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
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'addSettingsLink']);
        add_action('wp_ajax_webarat_clear_blocked_logs', [$this, 'handleClearLogs']);
    }

    /**
     * متد چندزبانه داینامیک بدون نیاز به فایل ترجمه
     */
    public static function t(string $key, ...$args): string
    {
        $locale = function_exists('determine_locale') ? determine_locale() : (function_exists('get_user_locale') ? get_user_locale() : get_locale());
        $isFa = str_starts_with(strtolower((string)$locale), 'fa');

        $dict = [
            'plugin_title'   => ['fa' => 'مسدودساز خروجی وب‌آرات', 'en' => 'Webarat HTTP Blocker'],
            'menu_title'     => ['fa' => 'مسدودساز HTTP', 'en' => 'HTTP Blocker'],
            'page_desc'      => ['fa' => 'مدیریت و مسدودسازی درخواست‌های خروجی وردپرس برای افزایش امنیت و سرعت.', 'en' => 'Manage and block outgoing WordPress requests for better security and speed.'],
            'master_switch'  => ['fa' => 'وضعیت سیستم', 'en' => 'System Status'],
            'enable_blocking'=> ['fa' => 'فعال‌سازی سیستم مسدودسازی', 'en' => 'Enable Blocking System'],
            'blocked_domains'=> ['fa' => 'لیست سیاه دامنه‌ها', 'en' => 'Domain Blacklist'],
            'domains_desc'   => ['fa' => 'دامنه‌ها را در هر خط وارد کنید. (مثال: elementor.com)', 'en' => 'Enter domains per line. (e.g., elementor.com)'],
            'subdomain_rules'=> ['fa' => 'قوانین زیردامنه', 'en' => 'Subdomain Rules'],
            'block_subs'     => ['fa' => 'مسدودسازی خودکار تمام زیردامنه‌ها (Wildcard)', 'en' => 'Automated Wildcard Subdomain Blocking'],
            'logging_label'  => ['fa' => 'ثبت گزارشات', 'en' => 'Logging'],
            'logging_desc'   => ['fa' => 'ذخیره ۳۰ مورد از آخرین تلاش‌های مسدود شده', 'en' => 'Log last 30 blocked attempts'],
            'save_btn'       => ['fa' => 'ذخیره تغییرات', 'en' => 'Save Changes'],
            'recent_logs'    => ['fa' => 'گزارشات اخیر', 'en' => 'Recent Logs'],
            'clear_logs'     => ['fa' => 'پاک‌سازی گزارشات', 'en' => 'Clear History'],
            'no_logs'        => ['fa' => 'گزارشی یافت نشد.', 'en' => 'No logs found.'],
            'time'           => ['fa' => 'زمان', 'en' => 'Time'],
            'method'         => ['fa' => 'متد', 'en' => 'Method'],
            'target'         => ['fa' => 'مقصد', 'en' => 'Target'],
            'url'            => ['fa' => 'آدرس', 'en' => 'URL'],
            'confirm_clear'  => ['fa' => 'آیا تاریخچه پاک شود؟', 'en' => 'Clear history?'],
            'settings'       => ['fa' => 'تنظیمات', 'en' => 'Settings'],
            'ajax_success'   => ['fa' => 'گزارشات با موفقیت پاک شدند.', 'en' => 'Logs cleared successfully.'],
            'ajax_error'     => ['fa' => 'خطا در دسترسی.', 'en' => 'Unauthorized access.'],
            'error_blocked'  => ['fa' => 'درخواست به دامنه %s توسط وب‌آرات مسدود شد.', 'en' => 'Request to %s blocked by Webarat.'],
        ];

        $text = $dict[$key][$isFa ? 'fa' : 'en'] ?? $key;
        return empty($args) ? $text : sprintf($text, ...$args);
    }

    public function maybeBlockRequest(false|array|WP_Error $preempt, array $parsedArgs, string $url): false|array|WP_Error
    {
        if ($preempt !== false) return $preempt;

        $options = $this->getOptions();
        if (empty($options['enabled']) || empty($options['domains'])) return $preempt;

        $host = wp_parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') return $preempt;

        $host = $this->normalizeHost($host);
        $domainList = $options['domains'];
        $blockSubdomains = ! empty($options['block_subdomains']);
        $matched = null;

        if (in_array($host, $domainList, true)) {
            $matched = $host;
        } elseif ($blockSubdomains) {
            foreach ($domainList as $d) {
                if (str_ends_with($host, '.' . $d)) {
                    $matched = $d;
                    break;
                }
            }
        }

        if ($matched) {
            if (apply_filters('webarat_blocker_allow', false, $url, $host)) return $preempt;

            if (! empty($options['enable_logging'])) {
                $this->logRequest($host, $url, $parsedArgs['method'] ?? 'GET');
            }

            return new WP_Error(self::ERROR_CODE, self::t('error_blocked', $host));
        }

        return $preempt;
    }

    private function logRequest(string $host, string $url, string $method): void
    {
        $logs = get_option(self::LOGS_OPTION, []);
        array_unshift($logs, [
            'time'   => current_time('mysql'),
            'host'   => $host,
            'url'    => substr($url, 0, 200),
            'method' => strtoupper($method)
        ]);
        update_option(self::LOGS_OPTION, array_slice($logs, 0, self::MAX_LOGS), false);
    }

    public function handleClearLogs(): void
    {
        check_ajax_referer('webarat_clear_nonce', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(self::t('ajax_error'));
        }
        delete_option(self::LOGS_OPTION);
        wp_send_json_success(['msg' => self::t('ajax_success')]);
    }

    public function registerSettingsPage(): void
    {
        add_options_page(self::t('plugin_title'), self::t('menu_title'), 'manage_options', self::PAGE_SLUG, [$this, 'renderUI']);
    }

    public function registerSettings(): void
    {
        register_setting(self::SETTINGS_GROUP, self::OPTION_NAME, [
            'sanitize_callback' => [$this, 'sanitize'],
            'default' => ['enabled' => 1, 'domains' => [], 'block_subdomains' => 1, 'enable_logging' => 0]
        ]);
    }

    public function sanitize(mixed $input): array
    {
        $raw = $input['domains_raw'] ?? '';
        $lines = preg_split('/\R/u', (string)$raw) ?: [];
        $domains = [];
        foreach ($lines as $l) {
            $h = $this->extractHost($l);
            if ($h) $domains[] = $h;
        }
        $domains = array_values(array_unique($domains));
        sort($domains);

        return [
            'enabled' => !empty($input['enabled']) ? 1 : 0,
            'block_subdomains' => !empty($input['block_subdomains']) ? 1 : 0,
            'enable_logging' => !empty($input['enable_logging']) ? 1 : 0,
            'domains' => $domains
        ];
    }

    public function renderUI(): void
    {
        $opt = $this->getOptions();
        $isRtl = is_rtl();
        $align = $isRtl ? 'right' : 'left';
        ?>
        <style>
            .webarat-card { background:#fff; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.05); padding:30px; margin-top:20px; border:1px solid #e5e7eb; }
            .webarat-header { display:flex; align-items:center; gap:12px; margin-bottom:10px; }
            .webarat-icon { color:#2563eb; font-size:32px!important; width:32px!important; height:32px!important; }
            .form-table th { font-weight:600; color:#374151; width:220px; }
            .webarat-badge { background:#dcfce7; color:#166534; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:bold; }
            .log-table { width:100%; border-collapse:collapse; margin-top:15px; border-radius:8px; overflow:hidden; }
            .log-table th { background:#f9fafb; text-align:<?php echo $align; ?>; padding:12px; font-size:12px; border-bottom:1px solid #edf2f7; }
            .log-table td { padding:12px; border-bottom:1px solid #f3f4f6; font-size:12px; }
        </style>
        <div class="wrap" style="max-width:1000px;">
            <div class="webarat-header">
                <span class="dashicons dashicons-shield-alt webarat-icon"></span>
                <h1 style="margin:0;"><?php echo esc_html(self::t('plugin_title')); ?></h1>
                <span class="webarat-badge">v1.1.3 Stable</span>
            </div>
            <p style="color:#6b7280; font-size:14px;"><?php echo esc_html(self::t('page_desc')); ?></p>

            <form action="options.php" method="post" class="webarat-card">
                <?php settings_fields(self::SETTINGS_GROUP); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html(self::t('master_switch')); ?></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[enabled]" value="1" <?php checked($opt['enabled'], 1); ?>> <?php echo esc_html(self::t('enable_blocking')); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html(self::t('blocked_domains')); ?></th>
                        <td>
                            <textarea name="<?php echo self::OPTION_NAME; ?>[domains_raw]" rows="8" class="large-text code" dir="ltr" style="border-radius:6px;"><?php echo esc_textarea(implode("
", $opt['domains'])); ?></textarea>
                            <p class="description"><?php echo esc_html(self::t('domains_desc')); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html(self::t('subdomain_rules')); ?></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[block_subdomains]" value="1" <?php checked($opt['block_subdomains'], 1); ?>> <?php echo esc_html(self::t('block_subs')); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html(self::t('logging_label')); ?></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[enable_logging]" value="1" <?php checked($opt['enable_logging'], 1); ?>> <?php echo esc_html(self::t('logging_desc')); ?></label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(self::t('save_btn'), 'primary large'); ?>
            </form>

            <?php if ($opt['enable_logging']): 
                $logs = get_option(self::LOGS_OPTION, []);
            ?>
            <div class="webarat-card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h2 style="margin:0; font-size:18px;"><?php echo esc_html(self::t('recent_logs')); ?></h2>
                    <?php if ($logs): ?>
                    <button id="wb-clear" class="button button-link-delete"><?php echo esc_html(self::t('clear_logs')); ?></button>
                    <?php endif; ?>
                </div>
                <?php if (!$logs): ?>
                    <p style="color:#9ca3af; padding-top:10px;"><?php echo esc_html(self::t('no_logs')); ?></p>
                <?php else: ?>
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html(self::t('time')); ?></th>
                                <th><?php echo esc_html(self::t('method')); ?></th>
                                <th><?php echo esc_html(self::t('target')); ?></th>
                                <th><?php echo esc_html(self::t('url')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logs as $l): ?>
                            <tr>
                                <td style="color:#6b7280;"><?php echo esc_html($l['time']); ?></td>
                                <td><strong><?php echo esc_html($l['method']); ?></strong></td>
                                <td style="color:#ef4444; font-weight:bold;"><?php echo esc_html($l['host']); ?></td>
                                <td dir="ltr" style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><code><?php echo esc_html($l['url']); ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <script>
            document.getElementById('wb-clear')?.addEventListener('click', function(){
                if(!confirm("<?php echo esc_js(self::t('confirm_clear')); ?>")) return;
                var btn = this; btn.disabled = true;
                fetch(ajaxurl, {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({action:'webarat_clear_blocked_logs', nonce:'<?php echo wp_create_nonce('webarat_clear_nonce'); ?>'})
                }).then(() => window.location.reload());
            });
            </script>
            <?php endif; ?>
        </div>
        <?php
    }

    private function getOptions(): array
    {
        if ($this->cachedOptions) return $this->cachedOptions;
        $stored = get_option(self::OPTION_NAME, []);
        return $this->cachedOptions = array_merge(['enabled'=>1, 'domains'=>[], 'block_subdomains'=>1, 'enable_logging'=>0], (array)$stored);
    }

    private function extractHost(string $in): ?string
    {
        $h = trim($in);
        if (!str_contains($h, '://')) $h = 'http://'.$h;
        $parsed = wp_parse_url($h, PHP_URL_HOST);
        return $parsed ? $this->normalizeHost($parsed) : null;
    }

    private function normalizeHost(string $h): string
    {
        $h = strtolower(trim($h, " ."));
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($h, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii) $h = $ascii;
        }
        return $h;
    }

    public function addSettingsLink(array $links): array
    {
        array_unshift($links, sprintf('<a href="%s">%s</a>', admin_url('options-general.php?page='.self::PAGE_SLUG), self::t('settings')));
        return $links;
    }
}

new Plugin();
