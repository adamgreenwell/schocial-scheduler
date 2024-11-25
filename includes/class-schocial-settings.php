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

/**
 * Class for establishing Schocial Scheduler settings page and functionality
 *
 * @category WordPress
 * @package  SchocialScheduler
 * @author   Adam Greenwell <adamgreenwell@gmail.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/adamgreenwell/schocial-scheduler
 * @since    1.0.0
 */
class SchocialSettings
{

    /**
     * The key used to store plugin options in WordPress.
     *
     * @var string 
     */
    private $_options_key = 'schocial_settings';

    /**
     * Initialize the class and set up hooks.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action(
            'admin_enqueue_scripts',
            array($this, 'enqueue_settings_scripts')
        );
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Enqueue scripts and styles for the settings page.
     *
     * @param string $hook The current admin page hook.
     *
     * @return void
     */

    // phpcs:ignore
    public function enqueue_settings_scripts($hook)
    {
        if ('toplevel_page_schocial-settings' !== $hook) {
            return;
        }

        $plugin_dir = plugin_dir_path(dirname(__FILE__));
        $plugin_url = plugins_url('build/settings.js', dirname(__FILE__));
        $asset_file_path = $plugin_dir .
            'build/settings.asset.php';

        // Check if asset file exists and load dependencies
        $dependencies = [
            'wp-components',
            'wp-element',
            'wp-api-fetch',
            'wp-i18n',
            'wp-data'
        ];
        $version = '1.0.0';

        if (file_exists($asset_file_path)) {
            $asset_file = include $asset_file_path;
            if (is_array($asset_file) && isset($asset_file['dependencies'])) {
                $dependencies = array_merge(
                    $asset_file['dependencies'], $dependencies
                );
            }
            if (isset($asset_file['version'])) {
                $version = $asset_file['version'];
            }
        }

        // Enqueue the main settings script
        wp_enqueue_script(
            'schocial-settings',
            $plugin_url,
            array_unique($dependencies),
            $version,
            true
        );

        // Localize the script with required data
        wp_localize_script(
            'schocial-settings',
            'schocialSettings',
            array('nonce' => wp_create_nonce('wp_rest'),
                'restUrl' => rest_url('schocial/v1'),
                'restNonce' => wp_create_nonce('wp_rest'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'isAdmin' => current_user_can('manage_options'),
                'debug' => WP_DEBUG,
                'pluginUrl' => plugin_dir_url(dirname(__FILE__))
            )
        );

        // Enqueue WordPress components styles
        wp_enqueue_style('wp-components');
    }

    /**
     * Add the settings page to the WordPress admin menu.
     *
     * @return void
     */

    // phpcs:ignore
    public function add_settings_page()
    {
        add_menu_page(
            __('Schocial Settings', 'schocial-scheduler'),
            __('Schocial', 'schocial-scheduler'),
            'manage_options',
            'schocial-settings',
            array($this, 'render_settings_page'),
            'dashicons-share', 30
        );
    }

    /**
     * Register plugin settings with WordPress.
     *
     * @return void
     */

    // phpcs:ignore
    public function register_settings()
    {
        register_setting(
            $this->_options_key,
            $this->_options_key,
            array(
                'type' => 'object',
                'show_in_rest' => array(
                    'schema' => array(
                        'type' => 'object',
                        'properties' => array(
                            'facebook_api_key' => array(
                                'type' => 'string',
                            ),
                            'facebook_page_id' => array(
                                'type' => 'string',
                            ),
                            'twitter_api_key' => array(
                                'type' => 'string'
                            ),
                            'twitter_api_secret' => array(
                                'type' => 'string'
                            ),
                            'twitter_bearer_token' => array(
                                'type' => 'string'
                            ),
                            'twitter_access_token' => array(
                                'type' => 'string'
                            ),
                            'twitter_access_token_secret' => array(
                                'type' => 'string'
                            ),
                            'twitter_callback_url' => array(
                                'type' => 'string'
                            ),
                            'twitter_settings' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'auto_thread' => array(
                                        'type' => 'boolean'
                                    ),
                                    'append_link' => array(
                                        'type' => 'boolean'
                                    ),
                                    'include_title' => array(
                                        'type' => 'boolean'
                                    ),
                                    'include_featured_image' => array(
                                        'type' => 'boolean'
                                    ),
                                    'thread_length' => array(
                                        'type' => 'integer'
                                    ),
                                ),
                            ),
                            'linkedin_client_id' => array(
                                'type' => 'string',
                            ),
                            'linkedin_client_secret' => array(
                                    'type' => 'string',
                                ),
                            'instagram_api_key' => array(
                                'type' => 'string',
                            ),
                            'enabled_platforms' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'facebook' => array(
                                        'type' => 'boolean',
                                    ),
                                    'twitter' => array(
                                        'type' => 'boolean',
                                    ),
                                    'linkedin' => array(
                                        'type' => 'boolean',
                                    ),
                                    'instagram' => array(
                                        'type' => 'boolean',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Render the settings page content.
     *
     * @return void
     */

    // phpcs:ignore
    public function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Schocial Settings', 'schocial-scheduler'); ?></h1>
            <div id="schocial-settings-root"></div>
        </div>
        <?php
    }

    /**
     * Register REST API routes for the settings.
     *
     * @return void
     */

    // phpcs:ignore
    public function register_rest_routes()
    {
        register_rest_route(
            'schocial/v1',
            '/settings',
            array(
                array(
                    'methods' => 'GET',
                    'callback' => array(
                        $this, 'get_settings'
                    ),
                    'permission_callback' => function () {
                        return current_user_can('manage_options');
                    },
                ),
                array(
                    'methods' => 'POST',
                    'callback' => array(
                        $this, 'update_settings'
                    ),
                    'permission_callback' => function () {
                        return current_user_can('manage_options');
                    },
                ),
            )
        );

        // Add new Twitter validation route
        register_rest_route(
            'schocial/v1',
            '/validate-twitter-credentials',
            [
            'methods' => 'POST',
            'callback' => [
                $this, 'validate_twitter_credentials'
            ],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'args' => [
                'api_key' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'api_secret' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'bearer_token' => [
                    'required' => true,
                    'type' => 'string',
                    ]
                ]
            ]
        );

        register_rest_route(
            'schocial/v1',
            '/linkedin/callback',
            [
                'methods' => 'GET',
                'callback' => [
                    $this, 'handle_linkedin_callback'
                ],
                'permission_callback' => '__return_true'
                // Allow unauthenticated access for OAuth callback
            ]
        );

        register_rest_route(
            'schocial/v1',
            '/validate-linkedin-credentials',
            [
                'methods' => 'POST',
                'callback' => [
                    $this, 'validate_linkedin_credentials'
                ],
                'permission_callback' => [
                    $this, 'check_admin_permissions'
                ]
            ]
        );
    }

    /**
     * Check if user has admin permissions
     *
     * @return bool Whether user has permission
     */

	// phpcs:ignore
    public function check_admin_permissions()
    {
        // Check if user is logged in and has manage_options capability
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __(
                    'You need to be an administrator to perform this action.',
                    'schocial-scheduler'
                ),
                array(
                        'status' => rest_authorization_required_code()
                )
            );
        }
        return true;
    }

    /**
     * Validate Twitter credentials
     *
     * @param WP_REST_Request $request The request object.
     * 
     * @return WP_REST_Response|WP_Error The response or error.
     */

	// phpcs:ignore
    public function validate_twitter_credentials($request)
    {
        try {
            $bearer_token = $request->get_param('bearer_token');
            $test_url
                = 'https://api.twitter.com/2/tweets/search/recent?query=wordpress';

            $response = wp_remote_get(
                $test_url,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $bearer_token,
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 15,
                ]
            );

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $body_json = json_decode($body, true);

            if ($response_code === 429) {
                throw new Exception(
                    __(
                        'Twitter API rate limit exceeded.
                            Please wait a few minutes and try again.',
                        'schocial-scheduler'
                    )
                );
            }

            if ($response_code !== 200) {
                $error_message = isset(
                    $body_json['errors'][0]['message']
                ) ?
                $body_json['errors'][0]['message'] :
                    sprintf(
                        __(
                            'Twitter API request failed with status %d',
                            'schocial-scheduler'
                        ),
                        $response_code
                    );
                throw new Exception($error_message);
            }

            return rest_ensure_response(
                [
                    'success' => true,
                    'message' => __(
                        'Twitter credentials validated successfully!',
                        'schocial-scheduler'
                    )
                ]
            );

        } catch (Exception $e) {
            return new WP_Error(
                'twitter_validation_failed', $e->getMessage(),
                ['status' => 400]
            );
        }
    }

    // Add method to get LinkedIn auth URL
	// phpcs:ignore
    public function get_linkedin_auth_url()
    {
        $settings = get_option('schocial_settings', []);
        $clientId = $settings['linkedin_client_id'] ?? '';
        $redirectUri = rest_url('schocial/v1/linkedin/callback');

        return 'https://www.linkedin.com/oauth/rest/authorization?' .
            http_build_query(
                [
                'response_type' => 'code',
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'scope' => 'r_liteprofile w_member_social',
                'state' => wp_create_nonce('linkedin_oauth')
                ]
            );
    }

    // Handle LinkedIn OAuth callback
	// phpcs:ignore
    public function handle_linkedin_callback($request)
    {
        try {
            $code = $request->get_param('code');
            $state = $request->get_param('state');

            if (!wp_verify_nonce($state, 'linkedin_oauth')) {
                throw new Exception('Invalid state parameter');
            }

            $settings = get_option('schocial_settings', []);
            $clientId = $settings['linkedin_client_id'] ?? '';
            $clientSecret = $settings['linkedin_client_secret'] ?? '';
            $redirectUri = rest_url('schocial/v1/linkedin/callback');

            $tokenResponse = wp_remote_post(
                'https://www.linkedin.com/oauth/v2/accessToken',
                [
                    'body' => [
                        'grant_type' => 'authorization_code',
                        'code' => $code,
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'redirect_uri' => $redirectUri
                    ]
                ]
            );

            if (is_wp_error($tokenResponse)) {
                throw new Exception($tokenResponse->get_error_message());
            }

            $tokenBody = json_decode(wp_remote_retrieve_body($tokenResponse), true);

            if (empty($tokenBody['access_token'])) {
                throw new Exception('Failed to obtain access token');
            }

            // Store the access token
            $settings['linkedin_access_token'] = $tokenBody['access_token'];
            update_option('schocial_settings', $settings);

            // Redirect back to settings page with success parameter
            wp_redirect(
                admin_url(
                    'admin.php?page=schocial-settings&linkedin_success=1'
                )
            );
            exit;

        } catch (Exception $e) {
            error_log('LinkedIn Callback Error: ' . $e->getMessage());
            wp_redirect(
                admin_url(
                    'admin.php?page=schocial-settings&linkedin_error=' .
                    urlencode($e->getMessage())
                )
            );
            exit;
        }
    }

	// phpcs:ignore
    public function validate_linkedin_credentials($request)
    {
        try {
            $settings = get_option('schocial_settings', []);
            $clientId = $settings['linkedin_client_id'] ?? '';
            $clientSecret = $settings['linkedin_client_secret'] ?? '';
            $redirectUri = rest_url('schocial/v1/linkedin/callback');

            if (empty($clientId) || empty($clientSecret)) {
                return new WP_Error(
                    'missing_credentials',
                    __(
                        'LinkedIn client ID and secret are required',
                        'schocial-scheduler'
                    ),
                    ['status' => 400]
                );
            }

            $authUrl
                = 'https://www.linkedin.com/oauth/v2/authorization?' .
                http_build_query(
                    [
                    'response_type' => 'code',
                    'client_id' => $clientId,
                    'redirect_uri' => $redirectUri,
                    'state' => wp_create_nonce('linkedin_oauth'),
                    'scope' => 'r_organization_social w_organization_social'
                    ]
                );

            return new WP_REST_Response(
                [
                    'success' => true,
                    'authUrl' => $authUrl,
                    'message' => __(
                        'Please authorize the application',
                        'schocial-scheduler'
                    )
                ]
            );

        } catch (Exception $e) {
            error_log('LinkedIn Validation Exception: ' . $e->getMessage());
            return new WP_Error(
                'validation_failed', $e->getMessage(),
                ['status' => 400]
            );
        }
    }

    /**
     * Get the plugin settings.
     *
     * @return WP_REST_Response The settings response.
     */

    // phpcs:ignore
    public function get_settings()
    {
        $settings = get_option($this->_options_key, array());
        return rest_ensure_response(
            array(// Facebook settings
                'facebook_api_key' =>
                    isset($settings['facebook_api_key']) ?
                        $settings['facebook_api_key'] : '',
                'facebook_page_id' =>
                    isset($settings['facebook_page_id']) ?
                        $settings['facebook_page_id'] : '',

                // Twitter settings
                'twitter_api_key' =>
                    isset($settings['twitter_api_key']) ?
                        $settings['twitter_api_key'] : '',
                'twitter_api_secret' =>
                    isset($settings['twitter_api_secret']) ?
                        $settings['twitter_api_secret'] : '',
                'twitter_bearer_token' =>
                    isset($settings['twitter_bearer_token']) ?
                        $settings['twitter_bearer_token'] : '',
                'twitter_settings' =>
                    isset($settings['twitter_settings']) ?
                        $settings['twitter_settings'] : array(
                            'auto_thread' => false,
                            'append_link' => true,
                            'include_title' => true,
                            'include_featured_image' => false,
                            'thread_length' => 280
                    ),

                // LinkedIn settings
                'linkedin_client_id' =>
                    $settings['linkedin_client_id'] ?? '',
                'linkedin_client_secret' =>
                    $settings['linkedin_client_secret'] ?? '',

                // Instagram settings
                'instagram_api_key' =>
                    isset($settings['instagram_api_key']) ?
                        $settings['instagram_api_key'] : '',

                // Platform enable/disable settings
                'enabled_platforms' =>
                    isset($settings['enabled_platforms']) ?
                    $settings['enabled_platforms'] : array(
                        'facebook' => true,
                        'twitter' => true,
                        'linkedin' => true,
                        'instagram' => true,
                    ),
            )
        );
    }

    /**
     * Update the plugin settings.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response The updated settings.
     */

    // phpcs:ignore
    public function update_settings($request)
    {
        $settings = array_filter(
            $request->get_params(),
            function ($key) {
                return in_array(
                    $key,
                    array(// Facebook fields
                        'facebook_api_key',
                        'facebook_page_id',

                        // Twitter fields
                        'twitter_api_key',
                        'twitter_api_secret',
                        'twitter_bearer_token',
                        'twitter_settings',

                        // LinkedIn fields
                        'linkedin_client_id',
                        'linkedin_client_secret',

                        // Instagram fields
                        'instagram_api_key',

                        // Platform settings
                        'enabled_platforms',
                    ),
                    true
                );
            },
            ARRAY_FILTER_USE_KEY
        );

        // Validate twitter_settings structure
        if (isset($settings['twitter_settings'])) {
            $default_twitter_settings = array(
                'auto_thread' => false,
                'append_link' => true,
                'include_title' => true,
                'include_featured_image' => false,
                'thread_length' => 280
            );

            $settings['twitter_settings']
                = wp_parse_args(
                    $settings['twitter_settings'],
                    $default_twitter_settings
                );

            // Ensure thread_length is within valid range
            if (isset($settings['twitter_settings']['thread_length'])) {
                $settings['twitter_settings']['thread_length']
                    = max(
                        100,
                        min(
                            280,
                            intval($settings['twitter_settings']['thread_length'])
                        )
                    );
            }

            // Ensure boolean values are actually booleans
            $boolean_fields = array(
                'auto_thread',
                'append_link',
                'include_title',
                'include_featured_image'
            );
            foreach ($boolean_fields as $field) {
                if (isset($settings['twitter_settings'][$field])) {
                    $settings['twitter_settings'][$field]
                        = filter_var(
                            $settings['twitter_settings'][$field],
                            FILTER_VALIDATE_BOOLEAN
                        );
                }
            }
        }

        // Validate enabled_platforms structure
        if (isset($settings['enabled_platforms'])) {
            $default_platforms = array(
                'facebook' => true,
                'twitter' => true,
                'linkedin' => true,
                'instagram' => true
            );

            $settings['enabled_platforms']
                = wp_parse_args(
                    $settings['enabled_platforms'],
                    $default_platforms
                );

            // Ensure all platform values are boolean
            foreach ($settings['enabled_platforms'] as $platform => $enabled) {
                $settings['enabled_platforms'][$platform]
                    = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
            }
        }

        // Sanitize all text fields
        $text_fields = array(
            'facebook_api_key',
            'facebook_page_id',
            'twitter_api_key',
            'twitter_api_secret',
            'twitter_bearer_token',
            'linkedin_client_id',
            'linkedin_client_secret',
            'instagram_api_key'
        );

        foreach ($text_fields as $field) {
            if (isset($settings[$field])) {
                $settings[$field] = sanitize_text_field($settings[$field]);
            }
        }

        // Save the settings
        $updated = update_option($this->_options_key, $settings);

        if (!$updated) {
            return new WP_Error(
                'settings_update_failed',
                __(
                    'Failed to update settings.',
                    'schocial-scheduler'
                ),
                array(
                    'status' => 500
                )
            );
        }

        return rest_ensure_response(get_option($this->_options_key, array()));
    }
}