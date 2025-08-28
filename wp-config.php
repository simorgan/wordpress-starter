<?php
/**
 * WordPress Base Configuration
 */

// ** MySQL settings ** //
define('DB_NAME', 'wordpress-starter');
define('DB_USER', 'root');
define('DB_PASSWORD', '12345678');
define('DB_HOST', 'localhost');

// Set custom content directory (themes, plugins, uploads)
define('WP_CONTENT_DIR', __DIR__ . '/wp-content');
define('WP_CONTENT_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/wp-content');

$table_prefix = 'si_';


// Debug mode
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', true);
define('WP_DEBUG_LOG', true);

// Absolute path to WordPress core
if (!defined('ABSPATH')) {
	define('ABSPATH', __DIR__ . '/wp/');
}

// Load WordPress
require_once ABSPATH . 'wp-settings.php';