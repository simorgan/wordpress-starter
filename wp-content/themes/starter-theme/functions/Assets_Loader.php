<?php

namespace SIWP\WPT;

class Assets_Loader
{
	private string $theme_dir;
	private string $theme_uri;
	private string $vite_dev_server;
	private string $manifest_file;
	private bool $is_dev;
	private ?array $manifest_cache = null;

	public function __construct()
	{
		$this->theme_dir = get_template_directory();
		$this->theme_uri = get_template_directory_uri();
		// Configurable dev server URL via constant or fallback
		$this->vite_dev_server = defined('VITE_DEV_SERVER') ? VITE_DEV_SERVER : 'http://localhost:5173';
		$this->manifest_file = $this->theme_dir . '/dist/.vite/manifest.json';
		$this->is_dev = defined('WP_ENV') && WP_ENV === 'development' && defined('VITE_DEV_ACTIVE') && VITE_DEV_ACTIVE;

		// Enqueue frontend and editor assets
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
		add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);

		// Inject Vite HMR client
		add_action('wp_footer', [$this, 'inject_hmr_client']);

		// Single filter for type="module" scripts in dev mode
		add_filter('script_loader_tag', [$this, 'add_type_module'], 10, 2);
	}

	/**
	 * Add type="module" to dev scripts
	 */
	public function add_type_module(string $tag, string $handle): string
	{
		if ($this->is_dev && in_array($handle, ['theme-app', 'theme-editor'])) {
			return str_replace('<script ', '<script type="module" ', $tag);
		}
		return $tag;
	}

	/**
	 * Read and cache manifest.json
	 */
	private function get_manifest(): ?array
	{
		if ($this->manifest_cache !== null) {
			return $this->manifest_cache;
		}

		if (!file_exists($this->manifest_file)) {
			return null;
		}

		$manifest = json_decode(file_get_contents($this->manifest_file), true);
		if (!is_array($manifest)) {
			return null;
		}

		$this->manifest_cache = $manifest;

		return $manifest;
	}

	/**
	 * Enqueue frontend JS & CSS
	 */
	public function enqueue_assets(): void
	{
		if ($this->is_dev) {
			wp_register_script('theme-app', $this->vite_dev_server . '/resources/js/app.js', [], null);
			wp_enqueue_script('theme-app');
			return;
		}

		$manifest = $this->get_manifest();
		if (!$manifest) return;

		$entry = $manifest['resources/js/app.js'] ?? null;
		if (!$entry || !isset($entry['file'])) return;

		// JS
		wp_enqueue_script(
			'theme-app',
			$this->theme_uri . '/dist/' . $entry['file'],
			[],
			null,
			true
		);

		// CSS (multiple files)
		foreach ($entry['css'] ?? [] as $css_file) {
			$handle = 'theme-style-' . sanitize_title(pathinfo($css_file, PATHINFO_FILENAME));
			wp_enqueue_style(
				$handle,
				$this->theme_uri . '/dist/' . $css_file,
				[],
				null
			);
		}
	}

	/**
	 * Enqueue Gutenberg/editor assets
	 */
	public function enqueue_editor_assets(): void
	{
		if ($this->is_dev) {
			wp_register_script('theme-editor', $this->vite_dev_server . '/resources/js/editor.js', [], null);
			wp_enqueue_script('theme-editor');
			return;
		}

		$manifest = $this->get_manifest();
		if (!$manifest) return;

		$entry = $manifest['resources/js/editor.js'] ?? null;
		if (!$entry || !isset($entry['file'])) return;

		// JS
		wp_enqueue_script(
			'theme-editor',
			$this->theme_uri . '/dist/' . $entry['file'],
			[],
			null,
			true
		);

		// CSS (multiple files)
		foreach ($entry['css'] ?? [] as $css_file) {
			$handle = 'theme-editor-style-' . sanitize_title(pathinfo($css_file, PATHINFO_FILENAME));
			wp_enqueue_style(
				$handle,
				$this->theme_uri . '/dist/' . $css_file,
				[],
				null
			);
		}
	}

	/**
	 * Inject Vite HMR client for dev mode
	 */
	public function inject_hmr_client(): void
	{
		if (!$this->is_dev) return;

		echo <<<HTML
<script type="module">
  import "{$this->vite_dev_server}/@vite/client";
</script>
HTML;
	}
}
