<?php
/**
 * Plugin Name: Schocial Scheduler
 * Description: Schedule WordPress posts to social media platforms
 * Version: 1.0.0
 * Author: Adam Greenwell
 */

// Prevent direct access
defined('ABSPATH') || exit;

class SchocialScheduler {
	public function __construct() {
		add_action('init', [$this, 'register_meta']);
		add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
		add_action('save_post', [$this, 'save_schedule_data']);
		add_action('wp_scheduled_schocial_post', [$this, 'publish_to_social'], 10, 2);
		require_once plugin_dir_path(__FILE__) . 'includes/class-schocial-settings.php';
		$this->settings = new SchocialSettings();
	}

	public function register_meta() {
		register_post_meta('post', '_schocial_schedule', [
			'show_in_rest' => true,
			'single' => true,
			'type' => 'object',
			'auth_callback' => function() {
				return current_user_can('edit_posts');
			}
		]);
	}

	public function enqueue_editor_assets() {
		$dev_url = 'http://localhost:8887/';

		wp_enqueue_script(
			'schocial-scheduler',
			defined('SCRIPT_DEBUG') && SCRIPT_DEBUG
				? $dev_url . 'index.js'
				: plugins_url('build/index.js', __FILE__),
			['wp-blocks', 'wp-components', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins'],
			'1.0.0',
			true
		);
		wp_script_add_data('schocial-scheduler', 'type', 'module');
	}

	public function save_schedule_data($post_id) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

		$schedule_data = get_post_meta($post_id, '_schocial_schedule', true);
		if (empty($schedule_data)) return;

		foreach ($schedule_data as $platform => $datetime) {
			if (!empty($datetime)) {
				wp_schedule_single_event(
					strtotime($datetime),
					'wp_scheduled_schocial_post',
					[$post_id, $platform]
				);
			}
		}
	}

	public function publish_to_social($post_id, $platform) {
		$post = get_post($post_id);
		$message = $post->post_title . "\n\n" . wp_trim_words($post->post_content, 30);
		$link = get_permalink($post_id);

		switch ($platform) {
			case 'facebook':
				$this->post_to_facebook($message, $link);
				break;
			case 'twitter':
				$this->post_to_twitter($message, $link);
				break;
			case 'linkedin':
				$this->post_to_linkedin($message, $link);
				break;
			case 'instagram':
				$this->post_to_instagram($message);
				break;
		}
	}

	private function post_to_facebook($message, $link) {
		// Implement Facebook API integration
	}

	private function post_to_twitter($message, $link) {
		// Implement Twitter API integration
	}

	private function post_to_linkedin($message, $link) {
		// Implement LinkedIn API integration
	}

	private function post_to_instagram($message) {
		// Implement Instagram API integration
	}
}

new SchocialScheduler();