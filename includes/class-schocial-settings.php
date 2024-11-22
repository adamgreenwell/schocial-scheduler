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
    private $_optons_key = 'schocial_settings';

    /**
     * Initialize the class and set up hooks.
     */
    public function __construct()
    {
        add_action('admin_menu', array( $this, 'add_settings_page' ));
        add_action('admin_init', array( $this, 'register_settings' ));
        add_action(
            'admin_enqueue_scripts',
            array( $this, 'enqueue_settings_scripts' )
        );
        add_action('rest_api_init', array( $this, 'register_rest_routes' ));
    }

    /**
     * Enqueue scripts and styles for the settings page.
     *
     * @param string $hook The current admin page hook.
     * 
     * @return void
     */

    // phpcs:ignore
    public function enqueue_settings_scripts( $hook )
    {
        if ('toplevel_page_schocial-settings' !== $hook ) {
            return;
        }

        $plugin_dir = plugin_dir_path(dirname(__FILE__));
        $plugin_url = plugins_url('build/settings.js', dirname(__FILE__));
        $asset_file = include $plugin_dir . 'build/settings.asset.php';

        wp_enqueue_script(
            'schocial-settings',
            $plugin_url,
            array_merge(
                $asset_file['dependencies'],
                array(
                    'wp-components',
                    'wp-element',
                    'wp-api-fetch',
                    'wp-i18n',
                    'wp-data',
                )
            ),
            $asset_file['version'],
            true
        );

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
            array( $this, 'render_settings_page' ),
            'dashicons-share',
            30
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
            $this->_optons_key,
            $this->_optons_key,
            array(
                'type'         => 'object',
                'show_in_rest' => array(
                    'schema' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'facebook_api_key'  => array(
                                'type' => 'string',
                            ),
                            'twitter_api_key'   => array(
                                'type' => 'string',
                            ),
                            'linkedin_api_key'  => array(
                                'type' => 'string',
                            ),
                            'instagram_api_key' => array(
                                'type' => 'string',
                            ),
                            'enabled_platforms' => array(
                                'type'       => 'object',
                                'properties' => array(
                                    'facebook'  => array(
                                        'type' => 'boolean',
                                    ),
                                    'twitter'   => array(
                                        'type' => 'boolean',
                                    ),
                                    'linkedin'  => array(
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
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_settings' ),
                    'permission_callback' => function () {
                        return current_user_can('manage_options');
                    },
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'update_settings' ),
                    'permission_callback' => function () {
                        return current_user_can('manage_options');
                    },
                ),
            )
        );
    }

    /**
     * Get the plugin settings.
     *
     * @return WP_REST_Response The settings response.
     */

    // phpcs:ignore
    public function get_settings()
    {
        $settings = get_option($this->_optons_key, array());
        return rest_ensure_response(
            array(
                'facebook_api_key'  =>
                    isset($settings['facebook_api_key']) ?
                        $settings['facebook_api_key'] : '',
                'twitter_api_key'   =>
                    isset($settings['twitter_api_key']) ?
                        $settings['twitter_api_key'] : '',
                'linkedin_api_key'  =>
                    isset($settings['linkedin_api_key']) ?
                        $settings['linkedin_api_key'] : '',
                'instagram_api_key' =>
                    isset($settings['instagram_api_key']) ?
                        $settings['instagram_api_key'] : '',
                'enabled_platforms' =>
                    isset($settings['enabled_platforms']) ?
                        $settings['enabled_platforms'] : array(
                            'facebook'  => true,
                            'twitter'   => true,
                            'linkedin'  => true,
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
    public function update_settings( $request )
    {
        $settings = array_filter(
            $request->get_params(),
            function ( $key ) {
                return in_array(
                    $key,
                    array(
                        'facebook_api_key',
                        'twitter_api_key',
                        'linkedin_api_key',
                        'instagram_api_key',
                        'enabled_platforms',
                    ),
                    true
                );
            },
            ARRAY_FILTER_USE_KEY
        );

        update_option($this->_optons_key, $settings);
        return rest_ensure_response(get_option($this->_optons_key, array()));
    }
}