<?php
use PHPUnit\Framework\TestCase;

class SchocialSettingsTest extends TestCase {
	use \WP_Mock\Tools\TestCase;

	protected $settings;

	public function setUp(): void {
		\WP_Mock::setUp();
		$this->settings = new SchocialSettings();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	public function test_get_settings() {
		\WP_Mock::userFunction('get_option', [
			'args' => ['schocial_settings', []],
			'return' => [
				'facebook_api_key' => 'test_key',
				'twitter_api_key' => 'test_key'
			]
		]);

		$response = $this->settings->get_settings();
		$data = $response->get_data();

		$this->assertArrayHasKey('facebook_api_key', $data);
		$this->assertArrayHasKey('twitter_api_key', $data);
		$this->assertEquals('test_key', $data['facebook_api_key']);
	}
}