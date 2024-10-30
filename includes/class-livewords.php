<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://lazzo.nl
 * @since      1.0.0
 *
 * @package    Livewords
 * @subpackage Livewords/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Livewords
 * @subpackage Livewords/includes
 * @author     Daco de la Bretonière <daco@mac.com>
 */
class Livewords
{


    const WP_OPTIONS_LIVEWORDS_SETTINGS = 'livewords_settings';
    const WP_OPTIONS_LIVEWORDS_CUSTOM_FIELDS_SETTINGS = 'livewords_custom_fields';

    const WP_OPTIONS_LIVEWORDS_SETTINGS_API_URL = 'livewords_text_field_api_url';
    const WP_OPTIONS_LIVEWORDS_SETTINGS_ACCOUNT_DOMAIN = 'livewords_text_field_account_domain';
    const WP_OPTIONS_LIVEWORDS_SETTINGS_API_KEY = 'livewords_text_field_api_key';

    const WP_OPTIONS_LIVEWORDS_TAXONOMY_CHANNEL = 'livewords-taxonomies';

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Livewords_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {

        $this->plugin_name = 'livewords';
        $this->version = '1.0.0';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Livewords_Loader. Orchestrates the hooks of the plugin.
     * - Livewords_i18n. Defines internationalization functionality.
     * - Livewords_Admin. Defines all hooks for the admin area.
     * - Livewords_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-livewords-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-livewords-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-livewords-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-livewords-public.php';

        $this->loader = new Livewords_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Livewords_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Livewords_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new Livewords_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        // Add Settings link to the plugin
        $plugin_basename = plugin_basename(plugin_dir_path(__DIR__) . $this->plugin_name . '.php');
        $this->loader->add_filter('plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');

        // Add LiveWords settings page
        $this->loader->add_action('admin_init', $plugin_admin, 'options_update');

        // Meta box on edit post
        $this->loader->add_action('admin_menu', $plugin_admin, 'metabox_register');

        // Update status on post edit
        $this->loader->add_action('save_post', $plugin_admin, 'handle_save_post', 10, 3);

        // AJAX API
        $this->loader->add_action('wp_ajax_test_api_connection', $plugin_admin, 'test_api_connection');
        $this->loader->add_action('wp_ajax_send_post_for_translation', $plugin_admin, 'send_post_for_translation');
        $this->loader->add_action('wp_ajax_bulk_translate_posts', $plugin_admin, 'bulk_translate_posts');
        $this->loader->add_action('wp_ajax_translate_taxonomies', $plugin_admin, 'translate_taxonomies');
        // \\ AJAX API


    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Livewords_Public($this->get_plugin_name(), $this->get_version());

//        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
//        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        $this->loader->add_action('admin_post_nopriv_livewords', $plugin_public, 'switch_action');

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Livewords_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

}
