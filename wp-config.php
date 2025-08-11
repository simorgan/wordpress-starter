<?php
/**
 * WordPress Base Configuration
 */

// ** MySQL settings ** //
define( 'DB_NAME',     'database_name_here' );
define( 'DB_USER',     'username_here' );
define( 'DB_PASSWORD', 'password_here' );
define( 'DB_HOST',     'localhost' );

// Set custom content directory (themes, plugins, uploads)
define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' );
define( 'WP_CONTENT_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/wp-content' );

// Debug mode
define( 'WP_DEBUG', true );

// Absolute path to WordPress core
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/wp/' );
}

// Load WordPress
require_once ABSPATH . 'wp-settings.php';