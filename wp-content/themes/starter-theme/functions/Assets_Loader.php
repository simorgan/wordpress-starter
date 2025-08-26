<?php
namespace Simorgan\WPT;

class Assets_Loader
{
	private string $theme_dir;
	private string $theme_uri;
	private string $vite_dev_server;
	private string $manifest_file;
	private bool $is_dev;

	public function __construct()
	{
		$this->theme_dir        = get_template_directory();
		$this->theme_uri        = get_template_directory_uri();
		$this->vite_dev_server  = 'http://localhost:5173';
		$this->manifest_file    = $this->theme_dir . '/dist/.vite/manifest.json';
		$this->is_dev           = $this->check_vite_dev_server();

		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
		add_action('wp_footer', [$this, 'inject_hmr_client']);
	}

	/**
	 * Check if Vite dev server is running
	 */
	private function check_vite_dev_server(): bool
	{
		$fp = @fsockopen('localhost', 5173);
		if ($fp) {
			fclose($fp);
			return true;
		}
		return false;
	}

	/**
	 * Enqueue both CSS and JS assets
	 */
	public function enqueue_assets(): void
	{
		if ($this->is_dev) {

			// Development: load from Vite dev server
			wp_register_script(
				'theme-app',
				$this->vite_dev_server . '/resources/js/app.js',
				[],
				null
			);

			// Add type="module"
			add_filter('script_loader_tag', function ($tag, $handle) {
				if ($handle === 'theme-app') {
					// Replace <script src=...> with <script type="module" src=...>
					$tag = str_replace('<script ', '<script type="module" ', $tag);
				}
				return $tag;
			}, 10, 2);

			wp_enqueue_script('theme-app');
		} else {
			// Production: load from manifest.json
			if (!file_exists($this->manifest_file)) return;

			$manifest = json_decode(file_get_contents($this->manifest_file), true);
			$js_entry = $manifest['resources/js/app.js'];
			// JS
			if (isset($manifest['resources/js/app.js']['file'])) {
				wp_enqueue_script(
					'theme-app',
					$this->theme_uri . '/dist/' . $manifest['resources/js/app.js']['file'],
					[],
					null,
					true
				);
			}

			// Enqueue CSS (if any)
			if (isset($js_entry['css']) && is_array($js_entry['css'])) {
				foreach ($js_entry['css'] as $css_file) {
					wp_enqueue_style(
						'theme-style',
						$this->theme_uri . '/dist/' . $css_file,
						[],
						null
					);
				}
			}
		}
	}

	/**
	 * Inject Vite HMR client in dev mode
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
