<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// This is a temporary compatibility file to prevent a fatal error on activation
// if the old activation hook is still stored in the database.
// This file can be removed after the user has successfully activated the plugin once with this code.
if ( ! class_exists( 'PM_Custom_Post' ) ) {
    class PM_Custom_Post {
        public static function activate() {
            // Do nothing. This is just to prevent a fatal error.
        }
    }
}
