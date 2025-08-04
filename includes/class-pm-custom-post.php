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
                'name'                  => 'Prompts',
                'singular_name'         => 'Prompt',
                'menu_name'             => 'Prompt Manager',
                'name_admin_bar'        => 'Prompt',
                'archives'              => 'Prompt Archives',
                'attributes'            => 'Prompt Attributes',
                'parent_item_colon'     => 'Parent Prompt:',
                'all_items'             => 'All Prompts',
                'add_new_item'          => 'Add New Prompt',
                'add_new'               => 'Add New',
                'new_item'              => 'New Prompt',
                'edit_item'             => 'Edit Prompt',
                'update_item'           => 'Update Prompt',
                'view_item'             => 'View Prompt',
                'view_items'            => 'View Prompts',
                'search_items'          => 'Search Prompt',
                'not_found'             => 'Not found',
                'not_found_in_trash'    => 'Not found in Trash',
                'featured_image'        => 'Featured Image',
                'set_featured_image'    => 'Set featured image',
                'remove_featured_image' => 'Remove featured image',
                'use_featured_image'    => 'Use as featured image',
                'insert_into_item'      => 'Insert into prompt',
                'uploaded_to_this_item' => 'Uploaded to this prompt',
                'items_list'            => 'Prompts list',
                'items_list_navigation' => 'Prompts list navigation',
                'filter_items_list'     => 'Filter prompts list',
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
