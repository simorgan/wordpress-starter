<?php

namespace SIWP\WPT;

use DirectoryIterator;
use Timber\Timber;

class Theme_Blocks
{
	/**
	 * Block directories to scan for blocks
	 */
	private static $block_directories = ['views/blocks'];

	/**
	 * Block category slug
	 */
	private static $block_category = 'siwp';

	public function __construct()
	{

		// WordPress Hooks
		add_action('init', [$this, 'register_blocks']);
		add_action('init', [$this, 'register_block_category']);
		add_filter('timber/loader/loader', [$this, 'register_timber_blocks_path']);
		add_action('acf/init', [$this, 'register_block_fields']);
		// Remove core blocks and only allow custom blocks
		add_filter('allowed_block_types_all', [$this, 'allowed_blocks'], 10, 2);
	}

	public function register_block_fields(): void
	{
		$block_directories = $this->discover_block_directories();

		foreach ($block_directories as $block_dir) {
			$fields_file_path = $block_dir . '/fields.php';
			if (file_exists($fields_file_path)) {
				require_once $fields_file_path;
			}
		}
	}

	/**
	 * Add block path to Timber loader
	 */
	public function register_timber_blocks_path($loader)
	{
		$loader->addPath(get_template_directory() . '/views/blocks', 'blocks');

		return $loader;
	}

	/**
	 * Register custom block category
	 */
	public function register_block_category(): void
	{
		add_filter('block_categories_all', function ($categories) {
			// Check if category already exists
			foreach ($categories as $category) {
				if ($category['slug'] === self::$block_category) {
					return $categories;
				}
			}

			// Add our custom category
			$categories[] = [
				'slug' => self::$block_category,
				'title' => __('SIWP Blocks', 'starter-theme'),
				'icon' => 'layout',
			];

			return $categories;
		}, 10, 2);
	}

	/**
	 * Register all blocks found in block directories
	 */
	public function register_blocks(): void
	{
		$block_directories = $this->discover_block_directories();

		foreach ($block_directories as $block_dir) {
			$block_json_path = $block_dir . '/block.json';

			if (file_exists($block_json_path)) {
				// Register the block first
				register_block_type($block_dir, [
					'render_callback' => [$this, 'render_block_callback']
				]);

			}
		}

	}

	/**
	 * Discover block directories that contain block.json files
	 */
	private function discover_block_directories(): array
	{
		$block_directories = [];
		$directories = $this->get_block_directories();

		foreach ($directories as $dir) {
			$full_path = get_template_directory() . '/' . $dir;

			if (!is_dir($full_path)) {
				continue;
			}

			$block_directories = array_merge($block_directories, $this->scan_directory_for_blocks($full_path));
		}

		return $block_directories;
	}

	/**
	 * Scan a directory for block directories containing block.json
	 */
	private function scan_directory_for_blocks($full_path): array
	{
		$block_directories = [];
		$iterator = new DirectoryIterator($full_path);

		foreach ($iterator as $item) {
			if ($item->isDot() || !$item->isDir()) {
				continue;
			}

			$block_json_path = $item->getPathname() . '/block.json';
			if (file_exists($block_json_path)) {
				$block_directories[] = $item->getPathname();
			}
		}

		return $block_directories;
	}

	/**
	 * Render block callback
	 */
	public function render_block_callback($attributes, $content = '', $block = null): void
	{
		// For ACF blocks, the block name is in attributes, not the $block parameter
		$block_name = $attributes['name'] ?? '';

		// Extract slug from block name
		$slug = str_replace('acf/', '', $block_name);

		error_log('Block name: ' . $block_name);
		error_log('Slug: ' . $slug);

		// Prepare context
		$context = Timber::context();
		$context['block'] = $attributes;
		$context['slug'] = $slug;

		// For ACF blocks, the field data is in $attributes['data']
		$context['fields'] = $attributes['data'] ?? [];

		// Also make fields available at root level for easier access
		if (!empty($context['fields'])) {
			foreach ($context['fields'] as $key => $value) {
				// Skip the underscore fields (ACF metadata)
				if (strpos($key, '_') !== 0) {
					$context[$key] = $value;
				}
			}
		}

		// Apply filters
		$context = apply_filters('timber/acf-gutenberg-blocks-data', $context);

		// Get template paths
		$templates = $this->get_block_template_paths($slug);

		error_log('Template paths: ' . print_r($templates, true));

		// Render the block
		Timber::render($templates, $context);
	}

	/**
	 * Get template paths for a block
	 */
	private function get_block_template_paths($slug): array
	{
		$templates = [];

		// Try preview template first if in admin
		if (is_admin()) {
			$templates[] = "@blocks/{$slug}/{$slug}-preview.twig";
		}

		// Main template
		$templates[] = "@blocks/{$slug}/{$slug}.twig";

		return $templates;
	}

	/**
	 * Get all block directories
	 */
	private function get_block_directories()
	{
		return apply_filters('timber/acf-gutenberg-blocks-templates', self::$block_directories);
	}

	/**
	 * Filter allowed blocks - only allow custom blocks
	 */
	public function allowed_blocks($allowed_blocks, $editor_context)
	{
		$current_post_type = 'post'; // default fallback
		if (isset($editor_context->post) && $editor_context->post) {
			$current_post_type = get_post_type($editor_context->post);
		} elseif (isset($_GET['post']) && $_GET['post']) {
			$current_post_type = get_post_type($_GET['post']);
		} elseif (isset($_GET['post_type']) && $_GET['post_type']) {
			$current_post_type = $_GET['post_type'];
		}

		$blocks_by_post_type = $this->get_blocks_by_post_type();
		$custom_blocks = [];

		// Get blocks for current post type
		if (isset($blocks_by_post_type[$current_post_type])) {
			$custom_blocks = $blocks_by_post_type[$current_post_type];
		}

		// Also include blocks with no post type restrictions
		if (isset($blocks_by_post_type['all'])) {
			$custom_blocks = array_merge($custom_blocks, $blocks_by_post_type['all']);
		}

		return array_unique($custom_blocks);
	}

	/**
	 * Filter blocks based on post type restrictions in block.json
	 */
	public function get_blocks_by_post_type()
	{
		// Find the block.json file for this block
		$block_directories = $this->discover_block_directories();
		$sorted_by_post_type = [];

		foreach ($block_directories as $block_dir) {
			$block_json_path = $block_dir . '/block.json';

			if (file_exists($block_json_path)) {
				$json_data = json_decode(file_get_contents($block_json_path), true);

				if (isset($json_data['post_type']) && is_array($json_data['post_type'])) {
					$block_name = $json_data['name'] ?? 'unknown';

					foreach ($json_data['post_type'] as $post_type) {
						if (!isset($sorted_by_post_type[$post_type])) {
							$sorted_by_post_type[$post_type] = [];
						}
						$sorted_by_post_type[$post_type][] = 'acf/' . $block_name;
					}
				}
			}
		}

		return $sorted_by_post_type;
	}


}