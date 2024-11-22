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
            '/post-now/(?P<platform>[a-zA-Z0-9-]+)/(?P<post_id>\d+)',
            [
                'methods' => 'POST',
                'callback' => [$this, 'handlePostNow'],
                'permission_callback' => function () {
                    return current_user_can('edit_posts');
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
     * @return array|WP_Error Array on success, WP_Error on failure
     * @since  1.0.0
     *
     * @access public
     */
    public function publishToSocial($postId, $platform)
    {
        $post = get_post($postId);
        if (!$post) {
            return new WP_Error(
                'post_not_found',
                __('Post not found', 'schocial-scheduler')
            );
        }

        $settings = get_option('schocial_settings', []);
        $apiKey = isset($settings["{$platform}_api_key"]) ?
            $settings["{$platform}_api_key"] : '';

        if (empty($apiKey)) {
            return new WP_Error(
                'missing_api_key',
                sprintf(
                    __('No API key configured for %s', 'schocial-scheduler'),
                    $platform
                )
            );
        }

        $message = $post->post_title . "\n\n" . wp_trim_words(
            $post->post_content, 30
        );
        $link = get_permalink($postId);

        try {
            switch ($platform) {
            case 'facebook':
                return $this->_postToFacebook($message, $link, $apiKey);
            case 'twitter':
                return $this->_postToTwitter($message, $link, $apiKey);
            case 'linkedin':
                return $this->_postToLinkedin($message, $link, $apiKey);
            case 'instagram':
                return $this->_postToInstagram($message, $apiKey);
            default:
                return new WP_Error(
                    'invalid_platform',
                    __('Invalid social media platform', 'schocial-scheduler')
                );
            }
        } catch (Exception $e) {
            return new WP_Error(
                'post_failed',
                $e->getMessage()
            );
        }
    }

    /**
     * Handle immediate post to social media
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return WP_REST_Response|WP_Error The REST response or error.
     */
    public function handlePostNow($request)
    {
        try {
            $platform = $request->get_param('platform');
            $postId = $request->get_param('post_id');

            error_log("Starting post to {$platform} for post {$postId}");

            $post = get_post($postId);
            if (!$post) {
                error_log("Post {$postId} not found");
                return new WP_Error(
                    'post_not_found',
                    __('Post not found', 'schocial-scheduler'),
                    ['status' => 404]
                );
            }

            $settings = get_option('schocial_settings', []);
            error_log(
                "Retrieved settings: " . wp_json_encode($settings)
            );

            $apiKey = $settings["{$platform}_api_key"] ?? '';
            $pageId = $settings['facebook_page_id'] ?? '';

            if ($platform === 'facebook' && (empty($apiKey) || empty($pageId))) {
                error_log(
                    "Missing Facebook credentials - API Key exists: " .
                    (!empty($apiKey)) . ", Page ID exists: " . (!empty($pageId))
                );
                return new WP_Error(
                    'missing_credentials',
                    __('Missing Facebook credentials', 'schocial-scheduler'),
                    ['status' => 400]
                );
            }

            $message = $post->post_title . "\n\n" .
                wp_trim_words($post->post_content, 30);
            $link = get_permalink($postId);

            error_log("Attempting to post to Facebook with Page ID: {$pageId}");

            $url = "https://graph.facebook.com/v18.0/{$pageId}/feed";
            $response = wp_remote_post(
                $url, [
                'body' => [
                    'message' => $message,
                    'link' => $link,
                    'access_token' => $apiKey
                ],
                'timeout' => 30
                ]
            );

            error_log("Facebook API Response: " . wp_json_encode($response));

            if (is_wp_error($response)) {
                error_log("WP Error: " . $response->get_error_message());
                return $response;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            error_log("Response body: " . wp_json_encode($body));

            return rest_ensure_response(
                [
                'success' => true,
                'message' => sprintf(
                    __('Posted to %s', 'schocial-scheduler'),
                    $platform
                ),
                'response' => $body
                ]
            );

        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            return new WP_Error(
                'post_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Post content to Facebook
     *
     * @param string $message The message to post.
     * @param string $link    The link to include in the post.
     * @param string $apiKey  The API key for the given platform.
     *
     * @return void
     * @since  1.0.0
     *
     * @access private
     */
    private function _postToFacebook($message, $link, $apiKey)
    {
        $graphApiVersion = 'v21.0';
        $pageId = get_option('schocial_facebook_page_id', '');

        if (empty($pageId)) {
            return new WP_Error(
                'missing_page_id',
                __('Facebook Page ID not configured', 'schocial-scheduler')
            );
        }

        $url = "https://graph.facebook.com/{$graphApiVersion}/{$pageId}/feed";

        error_log('Facebook API Request URL: ' . $url);
        error_log(
            'Facebook API Request Body: ' . wp_json_encode(
                [
                'message' => $message,
                'link' => $link
                ]
            )
        );

        $response = wp_remote_post(
            $url, [
            'body' => [
                'message' => $message,
                'link' => $link,
                'access_token' => $apiKey
            ],
            'timeout' => 30
            ]
        );

        if (is_wp_error($response)) {
            error_log('Facebook API Error: ' . $response->get_error_message());
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        error_log('Facebook API Response: ' . wp_json_encode($body));

        if (!empty($body['error'])) {
            error_log(
                'Facebook API Error: ' . wp_json_encode($body['error'])
            );
            return new WP_Error(
                'facebook_api_error',
                $body['error']['message']
            );
        }

        return [
            'success' => true,
            'platform' => 'facebook',
            'post_id' => $body['id'] ?? null,
            'message' =>
                __('Post successfully shared to Facebook', 'schocial-scheduler'),
            'response' => $body
        ];
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