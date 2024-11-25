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
     * @return void
     * @since  1.0.0
     *
     * @access public
     */
    public function __construct()
    {
        $this->_pluginPath = plugin_dir_path(__FILE__);
        $this->_pluginUrl  = plugin_dir_url(__FILE__);

        add_action('init', [ $this, 'registerMeta' ]);
        add_action('enqueue_block_editor_assets', [ $this, 'enqueueEditorAssets' ]);
        add_action('save_post', [ $this, 'saveScheduleData' ]);
        add_action(
            'wp_scheduled_schocial_post',
            [ $this, 'publishToSocial' ],
            10,
            2
        );
        add_action('rest_api_init', [ $this, 'registerRestRoutes' ]);

        include_once $this->_pluginPath . 'includes/class-schocial-settings.php';
        $this->settings = new SchocialSettings();
    }

    /**
     * Enqueue editor assets for the block editor
     *
     * @return void
     * @since  1.0.0
     *
     * @access public
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
     * Register custom meta fields for post scheduling
     *
     * @return void
     * @since  1.0.0
     *
     * @access public
     */
    public function registerMeta()
    {
        register_post_meta(
            'post',
            '_schocial_schedule',
            [
                'show_in_rest'  => [
                    'schema' => [
                        'type'                 => 'object',
                        'properties'           => [
                            'facebook'  => [ 'type' => [ 'string', 'null' ] ],
                            'twitter'   => [ 'type' => [ 'string', 'null' ] ],
                            'linkedin'  => [ 'type' => [ 'string', 'null' ] ],
                            'instagram' => [ 'type' => [ 'string', 'null' ] ]
                        ],
                        'additionalProperties' => false
                    ]
                ],
                'single'        => true,
                'type'          => 'object',
                'default'       => (object) [
                    'facebook'  => null,
                    'twitter'   => null,
                    'linkedin'  => null,
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
                'show_in_rest'  => [
                    'schema' => [
                        'type'                 => 'object',
                        'properties'           => [
                            'facebook'  => [ 'type' => 'boolean' ],
                            'twitter'   => [ 'type' => 'boolean' ],
                            'linkedin'  => [ 'type' => 'boolean' ],
                            'instagram' => [ 'type' => 'boolean' ]
                        ],
                        'additionalProperties' => false
                    ]
                ],
                'single'        => true,
                'type'          => 'object',
                'default'       => (object) [
                    'facebook'  => false,
                    'twitter'   => false,
                    'linkedin'  => false,
                    'instagram' => false
                ],
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                }
            ]
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

        $scheduleData  = get_post_meta($postId, '_schocial_schedule', true);
        $platformsData = get_post_meta($postId, '_schocial_platforms', true);

        if (empty($scheduleData) || empty($platformsData)) {
            return;
        }

        foreach ($scheduleData as $platform => $datetime) {
            if (! empty($datetime) && ! empty($platformsData[ $platform ])) {
                wp_schedule_single_event(
                    strtotime($datetime),
                    'wp_scheduled_schocial_post',
                    [ $postId, $platform ]
                );
            }
        }
    }

    /**
     * Register REST API routes for the plugin
     *
     * @return void
     * @since  1.0.0
     *
     * @access public
     */
    public function registerRestRoutes()
    {
        register_rest_route(
            'schocial/v1',
            '/post-now/(?P<platform>[a-zA-Z0-9-]+)/(?P<post_id>\d+)',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handlePostNow' ],
                'permission_callback' => function () {
                    return current_user_can('edit_posts');
                },
                'args'                => [
                    'platform' => [
                        'required' => true,
                        'type'     => 'string',
                        'enum'     => [
                            'facebook',
                            'twitter',
                            'linkedin',
                            'instagram'
                        ]
                    ],
                    'post_id'  => [
                        'required' => true,
                        'type'     => 'integer'
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
        $postId   = $request->get_param('post_id');

        $post = get_post($postId);
        if (! $post) {
            return new WP_Error(
                'post_not_found',
                'Post not found',
                [ 'status' => 404 ]
            );
        }

        $settings = get_option('schocial_settings', []);
        $apiKey   = isset(
            $settings["{$platform}_api_key"]
        ) ?
            $settings["{$platform}_api_key"] : '';

        if (empty($apiKey)) {
            return new WP_Error(
                'missing_api_key',
                sprintf('No API key configured for %s', $platform),
                [ 'status' => 400 ]
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
        $message  = $post->post_title . "\n\n" .
                    wp_trim_words($post->post_content, 30);
        $link     = get_permalink($post->ID);
        $settings = get_option('schocial_settings', []);
        $apiKey   = isset($settings["{$platform}_api_key"]) ?
            $settings["{$platform}_api_key"] : '';

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => sprintf('No API key configured for %s', $platform),
                'debug'   => [
                    'platform' => $platform,
                    'post_id'  => $post->ID,
                    'title'    => $post->post_title
                ]
            ];
        }

        $payload = $this->_preparePlatformPayload(
            $platform,
            $message,
            $link,
            $apiKey
        );
        $this->_logSocialPostAttempt($platform, $post->ID, $payload);

        return [
            'success' => true,
            'message' => sprintf('Successfully simulated post to %s', $platform),
            'debug'   => [
                'platform' => $platform,
                'post_id'  => $post->ID,
                'title'    => $post->post_title,
                'payload'  => $payload
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
        global $post;
        switch ($platform) {
        case 'facebook':
            return [
                'message'      => $message,
                'link'         => $link,
                'access_token' => $apiKey
            ];
        case 'twitter':
            return [
                'text'        => wp_trim_words($message, 20) . " " . $link,
                'oauth_token' => $apiKey
            ];
        case 'linkedin':
            return [
                'content'      => [
                    'title'       => $post->post_title,
                    'description' => wp_trim_words($post->post_content, 30),
                    'url'         => $link
                ],
                'access_token' => $apiKey
            ];
        case 'instagram':
            return [
                'caption'      => $message,
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
            'platform'  => $platform,
            'post_id'   => $postId,
            'payload'   => $payload
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
     * @return array Array of recent post logs.
     * @since  1.0.0
     *
     * @access public
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
        if (! $post) {
            return new WP_Error(
                'post_not_found',
                __('Post not found', 'schocial-scheduler')
            );
        }

        $settings = get_option('schocial_settings', []);
        $apiKey   = isset($settings["{$platform}_api_key"]) ?
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
            $post->post_content,
            30
        );
        $link    = get_permalink($postId);

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
        $pageId          = get_option('schocial_facebook_page_id', '');

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
                    'link'    => $link
                ]
            )
        );

        $response = wp_remote_post(
            $url,
            [
                'body'    => [
                    'message'      => $message,
                    'link'         => $link,
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

        if (! empty($body['error'])) {
            error_log(
                'Facebook API Error: ' . wp_json_encode($body['error'])
            );

            return new WP_Error(
                'facebook_api_error',
                $body['error']['message']
            );
        }

        return [
            'success'  => true,
            'platform' => 'facebook',
            'post_id'  => $body['id'] ?? null,
            'message'  =>
                __('Post successfully shared to Facebook', 'schocial-scheduler'),
            'response' => $body
        ];
    }

    /**
     * Post content to Twitter
     *
     * @param string $message The message to post.
     * @param string $link    The link to include in the post.
     * @param string $apiKey  The client secret key to authenticate with the API.
     *
     * @return void
     *
     * @since  1.0.0
     * @access private
     */
    private function _postToTwitter($message, $link, $apiKey)
    {
        // Twitter API v2 endpoint for creating tweets
        $url = 'https://api.twitter.com/2/tweets';

        // Truncate message to fit Twitter's character limit (280 chars)
        // Allow space for the link (23 characters as per Twitter's t.co service)
        $maxLength = 280 - 23 - 1; // -1 for space between message and link
        if (mb_strlen($message) > $maxLength) {
            $message = mb_substr($message, 0, $maxLength - 3) . '...';
        }

        // Prepare the tweet text
        $tweetText = $message . ' ' . $link;

        error_log('Twitter API Request URL: ' . $url);
        error_log(
            'Twitter API Request Body: ' .
            wp_json_encode([ 'text' => $tweetText ])
        );

        // Make the API request
        $response = wp_remote_post(
            $url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode(
                    [
                        'text' => $tweetText
                    ]
                ),
                'timeout' => 30
            ]
        );

        if (is_wp_error($response)) {
            error_log('Twitter API Error: ' . $response->get_error_message());

            return $response;
        }

        $body        = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);

        error_log('Twitter API Response Code: ' . $status_code);
        error_log('Twitter API Response: ' . wp_json_encode($body));

        if ($status_code !== 201) {
            $error_message = isset($body['detail']) ?
                $body['detail'] :
                __(
                    'Unknown error occurred while posting to Twitter',
                    'schocial-scheduler'
                );

            error_log('Twitter API Error: ' . $error_message);

            return new WP_Error(
                'twitter_api_error',
                $error_message,
                [ 'status' => $status_code ]
            );
        }

        return [
            'success'  => true,
            'platform' => 'twitter',
            'tweet_id' => $body['data']['id'] ?? null,
            'message'  => __('Tweet successfully posted', 'schocial-scheduler'),
            'response' => $body
        ];
    }

    /**
     * Post content to LinkedIn
     *
     * @param string $message The message to post.
     * @param string $link    The link to include in the post.
     * @param string $apiKey  The client secret platform key.
     *
     * @return void
     * @since  1.0.0
     *
     * @access private
     */
    private function _postToLinkedin($message, $link, $apiKey)
    {
        try {
            // Get settings
            $settings     = get_option('schocial_settings', []);
            $clientId     = $settings['linkedin_client_id'] ?? '';
            $clientSecret = $settings['linkedin_client_secret'] ?? '';

            if (empty($clientId) || empty($clientSecret)) {
                throw new Exception(
                    __(
                        'LinkedIn client credentials not configured',
                        'schocial-scheduler'
                    )
                );
            }

            // Exchange client credentials for access token
            $tokenResponse = wp_remote_post(
                'https://www.linkedin.com/oauth/rest/accessToken',
                [
                    'body'    => [
                        'grant_type'    => 'client_credentials',
                        'client_id'     => $clientId,
                        'client_secret' => $clientSecret,
                        'scope'         => 'w_member_social'
                    ],
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ]
                ]
            );

            if (is_wp_error($tokenResponse)) {
                throw new Exception($tokenResponse->get_error_message());
            }

            $tokenBody = json_decode(wp_remote_retrieve_body($tokenResponse), true);
            if (empty($tokenBody['access_token'])) {
                throw new Exception(
                    __(
                        'Failed to obtain LinkedIn access token',
                        'schocial-scheduler'
                    )
                );
            }

            // Prepare the post content
            $postData = [
                'author'          => 'urn:li:person:me',
                'lifecycleState'  => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary'    => [
                            'text' => $message
                        ],
                        'shareMediaCategory' => 'ARTICLE',
                        'media'              => [
                            [
                                'status'      => 'READY',
                                'originalUrl' => $link
                            ]
                        ]
                    ]
                ],
                'visibility'      => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
                ]
            ];

            // Create the post
            $postResponse = wp_remote_post(
                'https://api.linkedin.com/v2/ugcPosts',
                [
                    'headers' => [
                        'Authorization'             =>
                            'Bearer ' . $tokenBody['access_token'],
                        'Content-Type'              => 'application/json',
                        'X-Restli-Protocol-Version' => '2.0.0'
                    ],
                    'body'    => wp_json_encode($postData)
                ]
            );

            if (is_wp_error($postResponse)) {
                throw new Exception($postResponse->get_error_message());
            }

            $responseBody = json_decode(
                wp_remote_retrieve_body($postResponse),
                true
            );

            if (! empty($responseBody['id'])) {
                return [
                    'success' => true,
                    'message' => __(
                        'Successfully posted to LinkedIn',
                        'schocial-scheduler'
                    ),
                    'post_id' => $responseBody['id']
                ];
            }

            throw new Exception(
                isset($responseBody['message'])
                    ? $responseBody['message']
                    : __(
                        'Unknown error occurred while posting to LinkedIn',
                        'schocial-scheduler'
                    )
            );
        } catch (Exception $e) {
            error_log('LinkedIn Post Error: ' . $e->getMessage());

            return new WP_Error(
                'linkedin_post_failed',
                $e->getMessage()
            );
        }
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

    /**
     * Handle immediate post to social media
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return WP_REST_Response|WP_Error The REST response or error.
     */
    public function handlePostNow($request)
    {
        global $url;
        try {
            $platform = $request->get_param('platform');
            $postId   = $request->get_param('post_id');

            error_log("Starting post to {$platform} for post {$postId}");

            $post = get_post($postId);
            if (! $post) {
                error_log("Post {$postId} not found");

                return new WP_Error(
                    'post_not_found',
                    __('Post not found', 'schocial-scheduler'),
                    [ 'status' => 404 ]
                );
            }

            $settings = get_option('schocial_settings', []);
            error_log(
                "Retrieved settings: " . wp_json_encode($settings)
            );

            $apiKey  = $settings["{$platform}_api_key"] ?? '';
            $message = $post->post_title . "\n\n" .
                       wp_trim_words($post->post_content, 30);
            $link    = get_permalink($postId);

            switch ($platform) {
            case 'facebook':
                $pageId = $settings['facebook_page_id'] ?? '';
                if (empty($apiKey) || empty($pageId)) {
                    error_log(
                        "Missing Facebook credentials - API Key exists: " .
                        (!empty($apiKey)) . ", Page ID exists: " . (!empty($pageId))
                    );

                    return new WP_Error(
                        'missing_credentials',
                        __('Missing Facebook credentials', 'schocial-scheduler'),
                        [ 'status' => 400 ]
                    );
                }

                error_log("Attempting to post to Facebook with Page ID: {$pageId}");
                $url      = "https://graph.facebook.com/v18.0/{$pageId}/feed";
                $response = wp_remote_post(
                    $url,
                    [
                    'body'    => [
                        'message'      => $message,
                        'link'         => $link,
                        'access_token' => $apiKey
                    ],
                    'timeout' => 30
                    ]
                );
                break;

            case 'linkedin':
                $clientId     = $settings['linkedin_client_id'] ?? '';
                $clientSecret = $settings['linkedin_client_secret'] ?? '';

                if (empty($clientId) || empty($clientSecret)) {
                    error_log("Missing LinkedIn credentials");

                    return new WP_Error(
                        'missing_credentials',
                        __(
                            'Missing LinkedIn client credentials',
                            'schocial-scheduler'
                        ),
                        [ 'status' => 400 ]
                    );
                }

                $tokenUrl      = 'https://www.linkedin.com/oauth/rest/accessToken';
                $tokenResponse = wp_remote_post(
                    $tokenUrl,
                    [
                    'headers' => [
                        'Content-Type'  => 'application/x-www-form-urlencoded',
                        'Authorization' =>
                            'Basic ' . base64_encode($clientId . ':' . $clientSecret)
                    ],
                    'body'    => http_build_query(
                        [
                            'grant_type' => 'client_credentials',
                            'scope'      => 'r_liteprofile w_member_social'
                        ]
                    )
                    ]
                );

                error_log(
                    "Token Request URL: " . $tokenUrl
                );
                error_log(
                    "Token Request Headers: " .
                    wp_json_encode(wp_remote_retrieve_headers($tokenResponse))
                );
                error_log(
                    "Token Response Status: " .
                      wp_remote_retrieve_response_code($tokenResponse)
                );
                error_log(
                    "Token Response Body: " .
                    wp_remote_retrieve_body($tokenResponse)
                );

                if (is_wp_error($tokenResponse)) {
                    error_log(
                        "LinkedIn Token Error: " .
                        $tokenResponse->get_error_message()
                    );

                    return $tokenResponse;
                }

                if (is_wp_error($tokenResponse)) {
                    error_log(
                        "LinkedIn Token Error: " .
                        $tokenResponse->get_error_message()
                    );

                    return $tokenResponse;
                }

                $tokenBody = json_decode(
                    wp_remote_retrieve_body($tokenResponse),
                    true
                );
                if (empty($tokenBody['access_token'])) {
                    error_log(
                        "LinkedIn Token Error: " .
                        wp_json_encode($tokenBody)
                    );

                    return new WP_Error(
                        'token_error',
                        __(
                            'Failed to obtain LinkedIn access token',
                            'schocial-scheduler'
                        ),
                        [ 'status' => 400 ]
                    );
                }

                $payload = [
                'author'          => 'urn:li:person:me',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary'    => [
                            'text' => $message
                        ],
                        'shareMediaCategory' => 'ARTICLE',
                        'media'              => [
                            [
                                'status'      => 'READY',
                                'originalUrl' => $link
                            ]
                        ]
                    ]
                ],
                'visibility'      => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
                ]
                ];

                $response = wp_remote_post(
                    $url,
                    [
                    'headers' => [
                        'Authorization'             =>
                            'Bearer ' . $tokenBody['access_token'],
                        'Content-Type'              => 'application/json',
                        'X-Restli-Protocol-Version' => '2.0.0'
                    ],
                    'body'    => wp_json_encode($payload),
                    'timeout' => 30
                    ]
                );
                break;

            default:
                return new WP_Error(
                    'invalid_platform',
                    __(
                        'Invalid platform specified',
                        'schocial-scheduler'
                    ),
                    [ 'status' => 400 ]
                );
            }

            error_log(
                "{$platform} API Response: " . wp_json_encode($response)
            );

            if (is_wp_error($response)) {
                error_log("WP Error: " . $response->get_error_message());

                return $response;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            error_log("Response body: " . wp_json_encode($body));

            return rest_ensure_response(
                [
                    'success'  => true,
                    'message'  => sprintf(
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
                [ 'status' => 500 ]
            );
        }
    }
}

// Initialize the plugin
new SchocialScheduler();
