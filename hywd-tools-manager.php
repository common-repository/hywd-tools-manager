<?php

/**
 * Plugin Name:       HYWD Tools Manager
 * Plugin URI:        https://haywood.tools/
 * Description:       Manage all your HAYWOOD WordPress plugins in one place. Manage your license keys, activate and deactivate the plugins and see what else we have in store for you.
 * Version:           1.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2.0
 * Author:            HAYWOOD Digital Tools
 * Author URI:        https://haywood.tools
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hywd-tools-manager
*/

if(!defined('ABSPATH')){
    exit;
}

define('HYWD_PLUGIN_MANAGER_PATH', plugin_dir_path(__FILE__));
define('HYWD_PLUGIN_MANAGER_URL', plugin_dir_url(__FILE__));

define('HYWD_PLUGIN_MANAGER_SLT_APP_API_URL', 'https://haywood.tools/');
define('HYWD_PLUGIN_MANAGER_SLT_INSTANCE', str_replace(array("https://", "http://"), "", network_site_url()));

if (!class_exists('HYWD_Plugin_Manager')) {
    final class HYWD_Plugin_Manager
    {

        /**
         * Instance
         *
         * @since 1.0.0
         *
         * @access private
         * @static
         *
         * @var HYWD_Plugin_Manager The single instance of the class.
         */
        private static $_instance = null;

        /**
         * Instance
         *
         * Ensures only one instance of the class is loaded or can be loaded.
         *
         * @return HYWD_Plugin_Manager An instance of the class.
         * @since 1.0.0
         *
         * @access public
         * @static
         *
         */
        public static function instance()
        {

            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;

        }

        /**
         * Constructor
         *
         * @since 1.0.0
         *
         * @access public
         */
        public function __construct()
        {
        }

        /**
         * Initialize the plugin
         *
         * Load the plugin only after Elementor (and other plugins) are loaded.
         * Load the files required to run the plugin.
         *
         * Fired by `plugins_loaded` action hook.
         *
         * @since 1.0.0
         *
         * @access public
         */
        public function init()
        {

            //enqueue style & script
            add_action('admin_enqueue_scripts', [$this, 'hywd_license_admin_styles']);

            //add admin menu
            add_action('admin_menu', [$this, 'hywd_plugin_manager']);
            include(plugin_dir_path(__FILE__) . 'admin/plugin-functions.php');
            include(plugin_dir_path(__FILE__) . 'admin/hywd_tools_menu.php');
        }

        public function hywd_plugin_manager()
        {
            add_menu_page(
                'HYWD Tools Manager',                   // Page Title
                'HYWD Tools Manager',                   // Menu Title
                'manage_options',                // Capability
                'hywd-tools',              // Menu Slug
                '\HywdPluginManager\Admin\hywd_plugin_manager_options',         // Callback function to display the page
                'data:image/svg+xml;base64,' . base64_encode('<svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M3.74658 0.103516C2.08973 0.103516 0.746582 1.44666 0.746582 3.10352V13.1035C0.746582 14.7604 2.08973 16.1035 3.74658 16.1035H13.7466C15.4034 16.1035 16.7466 14.7604 16.7466 13.1035V3.10352C16.7466 1.44666 15.4034 0.103516 13.7466 0.103516H3.74658ZM12.6884 2.55347L10.2881 13.7535H8.49685L9.50802 9.03754H7.71677L6.7056 13.7535H4.90576L7.30612 2.55347H9.10596L8.09478 7.26087H9.88603L10.8972 2.55347H12.6884Z" fill="#C4C6C7"/>
</svg>'),
                20                               // Menu Position
            );
        }

        public function hywd_license_admin_styles()
        {
            wp_register_style('hywd-plugin-manager', plugin_dir_url(__FILE__) . 'assets/hywd-admin-css.css', '1.0.0', true);
            wp_enqueue_style('hywd-plugin-manager');

            $hywd_slt_variable = array(
                'HYWD_PLUGIN_MANAGER_SLT_APP_API_URL' => HYWD_PLUGIN_MANAGER_SLT_APP_API_URL,
                'HYWD_PLUGIN_MANAGER_SLT_INSTANCE' => HYWD_PLUGIN_MANAGER_SLT_INSTANCE,
                'admin_ajax' => admin_url("admin-ajax.php"),
                'ajax_nonce' => wp_create_nonce('ajax-nonce')
            );

            wp_register_script('hywd-plugin-manager', plugin_dir_url(__FILE__) . 'assets/js/hywd-script.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('hywd-plugin-manager');
            wp_add_inline_script('hywd-plugin-manager', 'var php_js_var = ' . wp_json_encode($hywd_slt_variable), 'before');
        }

    }
}

HYWD_Plugin_Manager::instance()->init();
