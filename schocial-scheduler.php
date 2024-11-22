<?php
/**
 * Schedule WordPress posts to social media platforms
 *
 * This plugin allows users to schedule their WordPress posts to be automatically
 * shared on various social media platforms at specified times.
 *
 * PHP version 8.2.4
 *
 * @category WordPress
 * @package  SchocialScheduler
 * @author   Adam Greenwell <adamgreenwell@gmail.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/adamgreenwell/schocial-scheduler
 *
 * @wordpress-plugin
 * Plugin Name: Schocial Scheduler
 * Description: Schedule WordPress posts to social media platforms
 * Version:     1.0.0
 * Author:      Adam Greenwell
 * License:     GPL-2.0-or-later
 * Text Domain: schocial-scheduler
 * Domain Path: /languages
 * Requires PHP: 7.4
 */

// Prevent direct access.
defined('ABSPATH') || exit;

/**
 * Main plugin class responsible for initializing Schocial Scheduler
 *
 * @category WordPress
 * @package  SchocialScheduler
 * @author   Adam Greenwell <adamgreenwell@gmail.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/adamgreenwell/schocial-scheduler
 * @since    1.0.0
 */
class SchocialScheduler
{
    /**
     * Absolute path to the plugin directory.
     *
     * @since 1.0.0
     *
     * @access private
     *
     * @var string
     */
    private $_pluginPath;

    /**
     * URL to the plugin directory.
     *
     * @since 1.0.0
     *
     * @access private
     *
     * @var string
     */
    private $_pluginUrl;

    /**
     * Initialize the plugin
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return void
     */
    public function __construct()
    {
        $this->_pluginPath = plugin_dir_path(__FILE__);
        $this->_pluginUrl = plugin_dir_url(__FILE__);

        add_action('init', [$this, 'registerMeta']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorAssets']);
        add_action('save_post', [$this, 'saveScheduleData']);
        add_action('wp_scheduled_schocial_post', [$this, 'publishToSocial'], 10, 2);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);

        include_once $this->_pluginPath . 'includes/class-schocial-settings.php';
        $this->settings = new SchocialSettings();
    }

    /**
     * Register custom meta fields for post scheduling
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return void
     */
    public function registerMeta()
    {
        register_post_meta(
            'post',
            '_schocial_schedule',
            [
                'show_in_rest' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'facebook' => ['type' => ['string', 'null']],
                            'twitter' => ['type' => ['string', 'null']],
                            'linkedin' => ['type' => ['string', 'null']],
                            'instagram' => ['type' => ['string', 'null']]
                        ],
                        'additionalProperties' => false
                    ]
                ],
                'single' => true,
                'type' => 'object',
                'default' => (object)[
                    'facebook' => null,
                    'twitter' => null,
                    'linkedin' => null,
                    'instagram' => null
                ],
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                }
            ]
        );

        register_post_meta(
            'post',
            '_schocial_platforms',
            [
                'show_in_rest' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'facebook' => ['type' => 'boolean'],
                            'twitter' => ['type' => 'boolean'],
                            'linkedin' => ['type' => 'boolean'],
                            'instagram' => ['type' => 'boolean']
                        ],
                        'additionalProperties' => false
                    ]
                ],
                'single' => true,
                'type' => 'object',
                'default' => (object)[
                    'facebook' => false,
                    'twitter' => false,
                    'linkedin' => false,
                    'instagram' => false
                ],
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                }
            ]
        );
    }

    /**
     * Enqueue editor assets for the block editor
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return void
     */
    public function enqueueEditorAssets()
    {
        $assetFile = include $this->_pluginPath . 'build/index.asset.php';

        $dependencies = array_merge(
            $assetFile['dependencies'],
            [
                'wp-plugins',
                'wp-edit-post',
                'wp-components',
                'wp-data',
                'wp-element',
                'wp-blocks',
                'wp-editor'
            ]
        );

        wp_enqueue_script(
            'schocial-scheduler',
            $this->_pluginUrl . 'build/index.js',
            array_unique($dependencies),
            $assetFile['version'],
            true
        );

        $this->registerMeta();

        wp_add_inline_script(
            'schocial-scheduler',
            sprintf(
                'console.log("Schocial Scheduler script loaded");
                window.schocialDebug = {
                    pluginUrl: "%s",
                    version: "%s"
                };',
                esc_js($this->_pluginUrl),
                esc_js($assetFile['version'])
            ),
            'before'
        );
    }

    /**
     * Save scheduling data when a post is saved
     *
     * @param int $postId The ID of the post being saved.
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function saveScheduleData($postId)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $scheduleData = get_post_meta($postId, '_schocial_schedule', true);
        $platformsData = get_post_meta($postId, '_schocial_platforms', true);

        if (empty($scheduleData) || empty($platformsData)) {
            return;
        }

        foreach ($scheduleData as $platform => $datetime) {
            if (!empty($datetime) && !empty($platformsData[$platform])) {
                wp_schedule_single_event(
                    strtotime($datetime),
                    'wp_scheduled_schocial_post',
                    [$postId, $platform]
                );
            }
        }
    }

    /**
     * Register REST API routes for the plugin
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return void
     */
    public function registerRestRoutes()
    {
        register_rest_route(
            'schocial/v1',
            '/test-post/(?P<platform>[a-zA-Z0-9-]+)/(?P<post_id>\d+)',
            [
                'methods' => 'POST',
                'callback' => [$this, 'testSocialPost'],
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
                'args' => [
                    'platform' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => ['facebook', 'twitter', 'linkedin', 'instagram']
                    ],
                    'post_id' => [
                        'required' => true,
                        'type' => 'integer'
                    ]
                ]
            ]
        );
    }

    /**
     * Test post to social media platform
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return WP_REST_Response|WP_Error The REST response or error.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function testSocialPost($request)
    {
        $platform = $request->get_param('platform');
        $postId = $request->get_param('post_id');

        $post = get_post($postId);
        if (!$post) {
            return new WP_Error(
                'post_not_found',
                'Post not found',
                ['status' => 404]
            );
        }

        $settings = get_option('schocial_settings', []);
        $apiKey = isset(
            $settings["{$platform}_api_key"]
        ) ?
            $settings["{$platform}_api_key"] : '';

        if (empty($apiKey)) {
            return new WP_Error(
                'missing_api_key',
                sprintf('No API key configured for %s', $platform),
                ['status' => 400]
            );
        }

        return rest_ensure_response(
            $this->_simulateSocialPost($post, $platform)
        );
    }

    /**
     * Simulate posting to social media platform
     *
     * @param WP_Post $post     The post object to simulate posting.
     * @param string  $platform The social media platform to simulate.
     *
     * @return array The simulation results.
     *
     * @since 1.0.0
     *
     * @access private
     */
    private function _simulateSocialPost($post, $platform)
    {
        $message = $post->post_title . "\n\n" .
            wp_trim_words($post->post_content, 30);
        $link = get_permalink($post->ID);
        $settings = get_option('schocial_settings', []);
        $apiKey = isset($settings["{$platform}_api_key"]) ?
            $settings["{$platform}_api_key"] : '';

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => sprintf('No API key configured for %s', $platform),
                'debug' => [
                    'platform' => $platform,
                    'post_id' => $post->ID,
                    'title' => $post->post_title
                ]
            ];
        }

        $payload = $this->_preparePlatformPayload(
            $platform, $message, $link, $apiKey
        );
        $this->_logSocialPostAttempt($platform, $post->ID, $payload);

        return [
            'success' => true,
            'message' => sprintf('Successfully simulated post to %s', $platform),
            'debug' => [
                'platform' => $platform,
                'post_id' => $post->ID,
                'title' => $post->post_title,
                'payload' => $payload
            ]
        ];
    }

    /**
     * Prepare payload for specific platform
     *
     * @param string $platform The social media platform.
     * @param string $message  The message to post.
     * @param string $link     The link to include.
     * @param string $apiKey   The API key for the platform.
     *
     * @return array The prepared payload.
     * 
     * @since 1.0.0
     *
     * @access private
     */
    private function _preparePlatformPayload($platform, $message, $link, $apiKey)
    {
        switch ($platform) {
        case 'facebook':
            return [
                    'message' => $message,
                    'link' => $link,
                    'access_token' => $apiKey
                ];
        case 'twitter':
            return [
                    'text' => wp_trim_words($message, 20) . " " . $link,
                    'oauth_token' => $apiKey
                ];
        case 'linkedin':
            return [
                    'content' => [
                        'title' => $post->post_title,
                        'description' => wp_trim_words($post->post_content, 30),
                        'url' => $link
                    ],
                    'access_token' => $apiKey
                ];
        case 'instagram':
            return [
                    'caption' => $message,
                    'access_token' => $apiKey
                ];
        default:
            return [];
        }
    }

    /**
     * Log social post attempt
     *
     * @param string $platform The social media platform.
     * @param int    $postId   The post ID.
     * @param array  $payload  The payload that would be sent.
     *
     * @return void
     * @since  1.0.0
     *
     * @access private
     */
    private function _logSocialPostAttempt($platform, $postId, $payload)
    {
        $logEntry = [
            'timestamp' => current_time('mysql'),
            'platform' => $platform,
            'post_id' => $postId,
            'payload' => $payload
        ];

        $logs = get_option('schocial_post_logs', []);
        array_unshift($logs, $logEntry);
        $logs = array_slice($logs, 0, 50);
        update_option('schocial_post_logs', $logs);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(
                sprintf(
                    '[Schocial] Test post to %s for post ID %d: %s',
                    $platform,
                    $postId,
                    wp_json_encode($payload, JSON_PRETTY_PRINT)
                )
            );
        }
    }

    /**
     * Get recent post logs
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return array Array of recent post logs.
     */
    public function getRecentPostLogs()
    {
        return get_option('schocial_post_logs', []);
    }

    /**
     * Publish post to specified social media platform
     *
     * @param int    $postId   The ID of the post to publish.
     * @param string $platform The social media platform to publish to.
     *
     * @return void
     * @since  1.0.0
     *
     * @access public
     */
    public function publishToSocial($postId, $platform)
    {
        $post = get_post($postId);
        $message = $post->post_title . "\n\n" .
            wp_trim_words($post->post_content, 30);
        $link = get_permalink($postId);

        switch ($platform) {
        case 'facebook':
            $this->_postToFacebook($message, $link);
            break;
        case 'twitter':
            $this->_postToTwitter($message, $link);
            break;
        case 'linkedin':
            $this->_postToLinkedin($message, $link);
            break;
        case 'instagram':
            $this->_postToInstagram($message);
            break;
        }
    }

    /**
     * Post content to Facebook
     *
     * @param string $message The message to post.
     * @param string $link    The link to include in the post.
     *
     * @return void
     * @since  1.0.0
     *
     * @access private
     */
    private function _postToFacebook($message, $link)
    {
        // Implement Facebook API integration
    }

    /**
     * Post content to Twitter
     *
     * @param string $message The message to post.
     * @param string $link    The link to include in the post.
     *
     * @return void
     * 
     * @since  1.0.0
     * @access private
     */
    private function _postToTwitter($message, $link)
    {
        // Implement Twitter API integration
    }

    /**
     * Post content to LinkedIn
     *
     * @param string $message The message to post.
     * @param string $link    The link to include in the post.
     *
     * @return void
     * @since  1.0.0
     *
     * @access private
     */
    private function _postToLinkedin($message, $link)
    {
        // Implement LinkedIn API integration
    }

    /**
     * Post content to Instagram
     *
     * @param string $message The message to post.
     *
     * @return void
     * @since  1.0.0
     *
     * @access private
     */
    private function _postToInstagram($message)
    {
        // Implement Instagram API integration
    }
}

// Initialize the plugin
new SchocialScheduler();