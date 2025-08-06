<?php
/**
 * Plugin Name:       Prompt Manager
 * Plugin URI:        https://example.com/
 * Description:       Create, store and manage JSON-formatted prompts for large language models from within WordPress. Includes version control, import/export tools, user access control, and prompt evaluation.
 * Version:           1.0.1
 * Author:            Your Name
 * Author URI:        https://example.com/
 * License:           GPLv2 or later
 * Text Domain:       prompt-manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Prevent class redeclaration
if ( ! class_exists( 'PM_Prompt_Manager' ) ) {
    final class PM_Prompt_Manager {
        const VERSION = '1.0.1';
        private static $instance;

        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            $this->define_constants();
            $this->includes();
            $this->init_hooks();
        }

        private function define_constants() {
            define( 'PM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
            define( 'PM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
            define( 'PM_PLUGIN_VERSION', self::VERSION );
        }

        private function includes() {
            $inc = PM_PLUGIN_DIR . 'includes/';
            require_once $inc . 'class-pm-custom-post-legacy.php';
            require_once $inc . 'class-pm-admin.php';
            require_once $inc . 'class-pm-prompts-list-table.php';
            require_once $inc . 'class-pm-import-export.php';
            require_once $inc . 'class-pm-evaluator.php';
        }

        private function init_hooks() {
            register_activation_hook( __FILE__, array( 'PM_Prompt_Manager', 'activate' ) );
            if ( is_admin() ) {
                new PM_Admin();
            }
        }

        public static function activate() {
            self::create_table();
        }

        public static function create_table(){
            global $wpdb;
            $table_name = $wpdb->prefix . 'pm_prompts';
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                title text NOT NULL,
                json_content longtext NOT NULL,
                rating tinyint(1) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    // Launch the plugin
    PM_Prompt_Manager::instance();
}
