<?php
/**
 * Automated test suite for Webarat External HTTP Blocker
 */

declare(strict_types=1);

define('ABSPATH', true);

// Mock WordPress core environment
function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {}
function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
function plugin_basename($file) { return basename($file); }
function esc_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function esc_attr($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function esc_textarea($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function esc_js($s) { return addslashes((string)$s); }
function esc_url($s) { return $s; }
function checked($checked, $current = true, $echo = true) {}
function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null) {}
function settings_fields($group) {}
function is_rtl() { return false; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function sanitize_text_field($str) { return trim(strip_tags((string)$str)); }
function sanitize_key($key) { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', (string)$key)); }
function wp_create_nonce($action = -1) { return 'mock_nonce'; }
function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true, $echo = true) {}
function current_time($type, $gmt = 0) { return date('Y-m-d H:i:s'); }
function apply_filters($tag, $value, ...$args) { return $value; }
function get_option($option, $default = false) { return $default; }
function update_option($option, $value, $autoload = null) { return true; }
function delete_option($option) { return true; }
function admin_url($path = '', $scheme = 'admin') { return 'http://example.com/wp-admin/' . $path; }
function check_ajax_referer($action = -1, $query_arg = false, $die = true) { return true; }
function check_admin_referer($action = -1, $query_arg = '_wpnonce') { return true; }
function current_user_can($capability, ...$args) { return true; }
function wp_send_json_success($data = null, $status_code = null, $flags = 0) {}
function wp_send_json_error($data = null, $status_code = null, $flags = 0) {}
function wp_json_encode($data, $options = 0, $depth = 512) { return json_encode($data, $options, $depth); }

require_once __DIR__ . '/../webarat-external-http-blocker.php';

use Webarat\ExternalHttpBlocker\Plugin;

echo "--> Verifying Plugin Version...\n";
if (Plugin::VERSION !== '1.2.0') {
    throw new RuntimeException("Version mismatch: expected 1.2.0, got " . Plugin::VERSION);
}

echo "--> Verifying i18n Translations...\n";
if (empty(Plugin::t('plugin_title')) || empty(Plugin::t('whitelist_domains'))) {
    throw new RuntimeException("Translation string lookup failed");
}

$reflection = new ReflectionClass(Plugin::class);
$plugin = $reflection->newInstanceWithoutConstructor();

echo "--> Verifying Sanitize & Normalization...\n";
$input = [
    'enabled' => '1',
    'block_subdomains' => '1',
    'enable_logging' => '1',
    'domains_raw' => "https://elementor.com/foo\n*.test.com\nhttp://elementor.com\nAPI.EXAMPLE.ORG\n",
    'whitelist_domains_raw' => "safe.elementor.com\nhttp://whitelist.org/path\n",
];
$sanitized = $plugin->sanitize($input);

assert($sanitized['enabled'] === 1);
assert(in_array('elementor.com', $sanitized['domains'], true));
assert(in_array('api.example.org', $sanitized['domains'], true));
assert(in_array('safe.elementor.com', $sanitized['whitelist_domains'], true));
assert(in_array('whitelist.org', $sanitized['whitelist_domains'], true));

echo "--> Verifying Host Matching Logic...\n";
$matchesMethod = $reflection->getMethod('hostMatchesList');
$matchesMethod->setAccessible(true);

assert($matchesMethod->invoke($plugin, 'safe.elementor.com', ['safe.elementor.com'], true) === true);
assert($matchesMethod->invoke($plugin, 'sub.safe.elementor.com', ['safe.elementor.com'], true) === true);
assert($matchesMethod->invoke($plugin, 'other.elementor.com', ['safe.elementor.com'], true) === false);

echo "--> Verifying Caller Detection...\n";
$callerMethod = $reflection->getMethod('detectCaller');
$callerMethod->setAccessible(true);
$caller = $callerMethod->invoke($plugin);
assert(is_string($caller));

echo "\n[PASS] All unit tests completed successfully!\n";
