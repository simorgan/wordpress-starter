<?php

namespace SIWP\WPT;

use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Timber\Timber;

class Theme_Debugger
{
	private static $instance = null;
	private $vardumper_initialized = false;

	public function __construct()
	{
		add_action('init', [$this, 'init_symfony_vardumper']);

		// Register global functions after VarDumper is initialized
		add_action('init', [$this, 'register_global_functions'], 20);

		// REMOVED: self::get_instance(); ← This was causing infinite loop!
		// Set the instance when constructor is called
		if (self::$instance === null) {
			self::$instance = $this;
		}
	}

	/**
	 * Get singleton instance
	 */
	public static function get_instance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize Symfony VarDumper with custom configuration
	 */
	public function init_symfony_vardumper()
	{
		if (!class_exists('Symfony\Component\VarDumper\VarDumper')) {
			return false;
		}

		// Configure VarDumper for WordPress
		VarDumper::setHandler(function ($var) {
			$cloner = new VarCloner();

			// Use HTML dumper for web requests, CLI dumper for WP-CLI
			if (php_sapi_name() === 'cli') {
				$dumper = new CliDumper();
			} else {
				$dumper = new HtmlDumper();

				// Custom theme configuration
				$dumper->setTheme('dark'); // or 'light'
				$dumper->setDisplayOptions([
					'maxDepth' => 3,
					'maxStringLength' => 160,
				]);
			}

			$dumper->dump($cloner->cloneVar($var));
		});

		$this->vardumper_initialized = true;
		return true;
	}

	/**
	 * Register global dd() and dump() functions
	 */
	public function register_global_functions()
	{
		// Only register if functions don't already exist and we're in debug mode
		if (!WP_DEBUG) {
			return;
		}

		// Register dd() function
		if (!function_exists('dd')) {
			function dd(...$vars)
			{
				$debugger = \SIWP\WPT\Theme_Debugger::get_instance();
				$debugger->dd_function(...$vars);
			}
		}

		// Register dump() function
		if (!function_exists('dump')) {
			function dump(...$vars)
			{
				$debugger = \SIWP\WPT\Theme_Debugger::get_instance();
				$debugger->dump_function(...$vars);
			}
		}

		// Register WordPress/Timber specific functions
		if (!function_exists('dd_context')) {
			function dd_context($context = null)
			{
				$debugger = \SIWP\WPT\Theme_Debugger::get_instance();
				$debugger->dd_timber_context($context);
			}
		}

		if (!function_exists('dd_timber_post')) {
			function dd_timber_post($post = null)
			{
				$debugger = \SIWP\WPT\Theme_Debugger::get_instance();
				$debugger->dd_timber_post_function($post);
			}
		}

		if (!function_exists('dd_wp_query')) {
			function dd_wp_query($query = null)
			{
				$debugger = \SIWP\WPT\Theme_Debugger::get_instance();
				$debugger->dd_wp_query_function($query);
			}
		}
	}

	/**
	 * Main dd() function implementation
	 */
	public function dd_function(...$vars)
	{
		if (!WP_DEBUG) {
			return;
		}

		// Check if VarDumper is available
		if ($this->vardumper_initialized && class_exists('Symfony\Component\VarDumper\VarDumper')) {
			$this->render_dd_with_vardumper($vars);
		} else {
			$this->render_dd_fallback($vars);
		}

		die();
	}

	/**
	 * Dump function (without dying)
	 */
	public function dump_function(...$vars)
	{
		if (!WP_DEBUG) {
			return;
		}

		if ($this->vardumper_initialized && class_exists('Symfony\Component\VarDumper\VarDumper')) {
			echo '<div style="margin: 10px 0; z-index: 99999; position: relative; font-size: 14px;">';
			foreach ($vars as $var) {
				VarDumper::dump($var);
			}
			echo '</div>';
		} else {
			foreach ($vars as $var) {
				echo '<pre style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 3px solid #007cba; font-size: 12px;">';
				print_r($var);
				echo '</pre>';
			}
		}
	}

	/**
	 * Debug Timber context
	 */
	public function dd_timber_context($context = null)
	{
		if (!WP_DEBUG) {
			return;
		}

		$context = $context ?: (class_exists('Timber\Timber') ? Timber::context() : []);

		echo '<div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px; margin: 20px; font-weight: 600; border-radius: 8px 8px 0 0; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">🌲 Timber Context Debug</div>';
		echo '<div style="background: #1e1e1e; margin: 0 20px 20px; padding: 0; border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';

		if ($this->vardumper_initialized && class_exists('Symfony\Component\VarDumper\VarDumper')) {
			VarDumper::dump($context);
		} else {
			echo '<pre style="color: #fff; padding: 20px; margin: 0; overflow-x: auto;">';
			print_r($context);
			echo '</pre>';
		}

		echo '</div>';
		die();
	}

	/**
	 * Debug Timber post
	 */
	public function dd_timber_post_function($post = null)
	{
		if (!WP_DEBUG) {
			return;
		}

		$post = $post ?: (class_exists('Timber\Timber') ? Timber::get_post() : get_post());

		echo '<div style="background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%); color: white; padding: 15px; margin: 20px; font-weight: 600; border-radius: 8px 8px 0 0; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">📝 Timber Post Debug</div>';
		echo '<div style="background: #1e1e1e; margin: 0 20px 20px; padding: 0; border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';

		if ($this->vardumper_initialized && class_exists('Symfony\Component\VarDumper\VarDumper')) {
			VarDumper::dump($post);
		} else {
			echo '<pre style="color: #fff; padding: 20px; margin: 0; overflow-x: auto;">';
			print_r($post);
			echo '</pre>';
		}

		echo '</div>';
		die();
	}

	/**
	 * Debug WordPress Query
	 */
	public function dd_wp_query_function($query = null)
	{
		if (!WP_DEBUG) {
			return;
		}

		global $wp_query;
		$query = $query ?: $wp_query;

		echo '<div style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%); color: #333; padding: 15px; margin: 20px; font-weight: 600; border-radius: 8px 8px 0 0; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">🔍 WordPress Query Debug</div>';
		echo '<div style="background: #1e1e1e; margin: 0 20px 20px; padding: 0; border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';

		$debug_data = [
			'query_vars' => $query->query_vars ?? [],
			'request' => $query->request ?? '',
			'found_posts' => $query->found_posts ?? 0,
			'post_count' => $query->post_count ?? 0,
			'is_main_query' => method_exists($query, 'is_main_query') ? $query->is_main_query() : false,
			'conditional_tags' => [
				'is_home' => is_home(),
				'is_front_page' => is_front_page(),
				'is_single' => is_single(),
				'is_page' => is_page(),
				'is_archive' => is_archive(),
				'is_search' => is_search(),
				'is_404' => is_404(),
			]
		];

		if ($this->vardumper_initialized && class_exists('Symfony\Component\VarDumper\VarDumper')) {
			VarDumper::dump($debug_data);
		} else {
			echo '<pre style="color: #fff; padding: 20px; margin: 0; overflow-x: auto;">';
			print_r($debug_data);
			echo '</pre>';
		}

		echo '</div>';
		die();
	}

	/**
	 * Render dd with VarDumper
	 */
	private function render_dd_with_vardumper($vars)
	{
		echo '<div style="margin: 20px; z-index: 99999; position: relative;">';
		echo '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 20px; font-weight: 600; border-radius: 8px 8px 0 0; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">🐛 Laravel DD Debug Output</div>';
		echo '<div style="background: #1e1e1e; padding: 0; border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';

		foreach ($vars as $var) {
			VarDumper::dump($var);
		}

		// Add call stack
		$this->render_call_stack();

		echo '</div></div>';
	}

	/**
	 * Fallback dd rendering when VarDumper is not available
	 */
	private function render_dd_fallback($vars)
	{
		echo '<div style="background: #ffebee; border-left: 5px solid #f44336; color: #c62828; padding: 15px; margin: 20px; font-family: monospace;">';
		echo '<strong>⚠️ Symfony VarDumper not found!</strong><br>';
		echo 'Install it with: <code>composer require symfony/var-dumper</code><br><br>';
		echo '<strong>Fallback output:</strong>';
		echo '<pre style="background: #fff; padding: 10px; margin: 10px 0; border: 1px solid #ddd;">';
		foreach ($vars as $var) {
			var_dump($var);
		}
		echo '</pre>';

		$this->render_call_stack();
		echo '</div>';
	}

	/**
	 * Render call stack
	 */
	private function render_call_stack()
	{
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

		// Find the actual calling location (skip internal class methods)
		$calling_trace = null;
		foreach ($backtrace as $trace) {
			if (isset($trace['file']) && !str_contains($trace['file'], __FILE__)) {
				$calling_trace = $trace;
				break;
			}
		}

		if ($calling_trace) {
			echo '<div style="background: #2a2a2a; color: #ccc; padding: 15px; font-size: 12px; font-family: monospace; border-top: 1px solid #333;">';
			echo '<strong style="color: #ff6b6b;">Called from:</strong> ';
			echo basename($calling_trace['file']) . ':' . ($calling_trace['line'] ?? 'unknown');
			echo '</div>';
		}
	}

	/**
	 * Static method for easy access
	 */
	public static function dd(...$vars)
	{
		$instance = self::get_instance();
		$instance->dd_function(...$vars);
	}

	/**
	 * Static method for dump
	 */
	public static function dump(...$vars)
	{
		$instance = self::get_instance();
		$instance->dump_function(...$vars);
	}
}