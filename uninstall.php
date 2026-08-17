<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
delete_option('webarat_external_http_blocker_options');
delete_option('webarat_ext_http_blocked_logs');
