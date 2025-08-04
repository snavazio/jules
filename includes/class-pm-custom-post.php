<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'PM_Custom_Post' ) ) {
    class PM_Custom_Post {
        const POST_TYPE = 'json_prompt';
        public static function activate() {
            self::register();
            flush_rewrite_rules();
            self::add_caps();
        }
        public static function deactivate() {
            self::remove_caps();
            flush_rewrite_rules();
        }
        public static function register() {
            $labels = array(
                'name'               => 'Prompts',
                'singular_name'      => 'Prompt',
                'menu_name'          => 'Prompt Manager',
            );
            $caps = array(
                'edit_post'          => 'edit_prompt',
                'edit_posts'         => 'edit_prompts',
                'publish_posts'      => 'publish_prompts',
            );
            $args = array(
                'labels'            => $labels,
                'public'            => false,
                'show_ui'           => true,
                'capability_type'   => array('prompt','prompts'),
                'map_meta_cap'      => true,
                'supports'          => array('title','revisions'),
            );
            register_post_type(self::POST_TYPE, $args);
            register_taxonomy('prompt_category', self::POST_TYPE, array('hierarchical'=>true));
            register_taxonomy('prompt_tag', self::POST_TYPE, array('hierarchical'=>false));
        }
        protected static function add_caps() {
            $roles = array('administrator','editor');
            $caps  = array('edit_prompts','publish_prompts');
            foreach($roles as $r){ $role=get_role($r); foreach($caps as $c){ $role->add_cap($c); }}
        }
        protected static function remove_caps() {
            $roles = array('administrator','editor');
            $caps  = array('edit_prompts','publish_prompts');
            foreach($roles as $r){ $role=get_role($r); foreach($caps as $c){ $role->remove_cap($c); }}
        }
    }
}
