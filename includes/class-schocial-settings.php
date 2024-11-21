<?php
class SchocialSettings {
	private $options_key = 'schocial_settings';

	public function __construct() {
		add_action('admin_menu', [$this, 'add_settings_page']);
		add_action('admin_init', [$this, 'register_settings']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_settings_scripts']);
		add_action('rest_api_init', [$this, 'register_rest_routes']);
	}

	public function enqueue_settings_scripts($hook) {
		error_log('Current hook: ' . $hook);

		if ($hook !== 'toplevel_page_schocial-settings') {
			return;
		}

		$plugin_dir = plugin_dir_path(dirname(__FILE__));
		$plugin_url = plugins_url('build/settings.js', dirname(__FILE__));

		error_log('Plugin dir: ' . $plugin_dir);
		error_log('Plugin URL: ' . $plugin_url);
		error_log('Asset file path: ' . $plugin_dir . 'build/settings.asset.php');

		$asset_file = include $plugin_dir . 'build/settings.asset.php';

		wp_enqueue_script(
			'schocial-settings',
			$plugin_url,
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

	}

	public function add_settings_page() {
		add_menu_page(
			'Schocial Settings',      // Page title
			'Schocial',               // Menu title
			'manage_options',         // Capability required
			'schocial-settings',      // Menu slug
			[$this, 'render_settings_page'], // Callback to render page
			'dashicons-share',        // Icon
			30                        // Position in menu
		);
	}

	public function register_settings() {
		register_setting($this->options_key, $this->options_key, [
			'type' => 'object',
			'show_in_rest' => [
				'schema' => [
					'type' => 'object',
					'properties' => [
						'facebook_api_key' => ['type' => 'string'],
						'twitter_api_key' => ['type' => 'string'],
						'linkedin_api_key' => ['type' => 'string'],
						'instagram_api_key' => ['type' => 'string'],
						'enabled_platforms' => [
							'type' => 'object',
							'properties' => [
								'facebook' => ['type' => 'boolean'],
								'twitter' => ['type' => 'boolean'],
								'linkedin' => ['type' => 'boolean'],
								'instagram' => ['type' => 'boolean']
							]
						]
					]
				]
			]
		]);
	}

	public function render_settings_page() {
		error_log('render_settings_page called. Stack trace: ' . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));
		?>
		<div class="wrap">
			<h1>Schocial Settings</h1>
			<div id="schocial-settings-root"></div>
		</div>
		<?php
	}

	public function register_rest_routes() {
		register_rest_route('schocial/v1', '/settings', [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_settings'],
				'permission_callback' => function() {
					return current_user_can('manage_options');
				}
			],
			[
				'methods' => 'POST',
				'callback' => [$this, 'update_settings'],
				'permission_callback' => function() {
					return current_user_can('manage_options');
				}
			]
		]);
	}

	public function get_settings() {
		$settings = get_option($this->options_key, []);
		return rest_ensure_response([
			'facebook_api_key' => $settings['facebook_api_key'] ?? '',
			'twitter_api_key' => $settings['twitter_api_key'] ?? '',
			'linkedin_api_key' => $settings['linkedin_api_key'] ?? '',
			'instagram_api_key' => $settings['instagram_api_key'] ?? '',
			'enabled_platforms' => $settings['enabled_platforms'] ?? [
					'facebook' => true,
					'twitter' => true,
					'linkedin' => true,
					'instagram' => true
				]
		]);
	}

	public function update_settings($request) {
		$settings = array_filter($request->get_params(), function($key) {
			return in_array($key, [
				'facebook_api_key',
				'twitter_api_key',
				'linkedin_api_key',
				'instagram_api_key',
				'enabled_platforms'
			]);
		}, ARRAY_FILTER_USE_KEY);

		update_option($this->options_key, $settings);
		return rest_ensure_response(get_option($this->options_key, []));
	}
}