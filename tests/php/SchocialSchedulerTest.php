<?php
use PHPUnit\Framework\TestCase;

class SchocialSchedulerTest extends TestCase {
	use \WP_Mock\Tools\TestCase;

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	public function test_register_meta() {
		\WP_Mock::userFunction('register_post_meta', [
			'times' => 1,
			'args' => [
				'post',
				'_schocial_schedule',
				[
					'show_in_rest' => true,
					'single' => true,
					'type' => 'object',
					'auth_callback' => \WP_Mock\Functions::type('callable')
				]
			]
		]);

		$scheduler = new SchocialScheduler();
		$scheduler->register_meta();
	}

	public function test_publish_to_social() {
		\WP_Mock::userFunction('get_post', [
			'args' => [1],
			'return' => (object) [
				'post_title' => 'Test Post',
				'post_content' => 'Test Content'
			]
		]);

		\WP_Mock::userFunction('get_permalink', [
			'args' => [1],
			'return' => 'https://example.com/test-post'
		]);

		$scheduler = new SchocialScheduler();
		$scheduler->publish_to_social(1, 'facebook');

		// Add assertions based on your implementation
		$this->assertTrue(true); // Replace with actual assertions
	}
}