<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'PM_Admin' ) ) {
    class PM_Admin {
        public function __construct() {
            add_action('admin_menu', array($this,'menus'));
            add_action('add_meta_boxes', array($this,'metaboxes'));
            add_action('save_post_json_prompt', array($this,'save_json'),10,2);
            add_action('admin_enqueue_scripts', array($this,'assets'));
        }
        public function menus() {
            add_menu_page('Prompt Manager','Prompt Manager','edit_prompts','prompt-manager',array($this,'page_dashboard'));          
            add_submenu_page('prompt-manager','All Prompts','All Prompts','edit_prompts','edit.php?post_type=json_prompt');
            add_submenu_page('prompt-manager','Add New','Add New','edit_prompts','prompt-new.php?post_type=json_prompt');
            add_submenu_page('prompt-manager','Import','Import','edit_prompts','prompt-manager-import',array($this,'page_import'));
            add_submenu_page('prompt-manager','Export','Export','edit_prompts','prompt-manager-export',array($this,'page_export'));
            add_submenu_page('prompt-manager','Help','Help','edit_prompts','prompt-manager-help',array($this,'page_help'));
        }
        public function metaboxes() {
            add_meta_box('pm_json','JSON Prompt Content',array($this,'box_json'),'json_prompt','normal','high');
            add_meta_box('pm_eval','Prompt Evaluation',array($this,'box_eval'),'json_prompt','side');
        }
        public function assets($hook){ 
            $s=get_current_screen(); 
            if($s->post_type==='json_prompt'){
                if(function_exists('wp_enqueue_code_editor')) wp_enqueue_code_editor(['type'=>'application/json']);
                wp_enqueue_script('pm-admin',PM_PLUGIN_URL.'assets/admin.js',array('jquery'),PM_PLUGIN_VERSION,true);
                wp_enqueue_style('pm-admin',PM_PLUGIN_URL.'assets/admin.css',array(),PM_PLUGIN_VERSION);
            } 
        }
        public function box_json($prompt){
            wp_nonce_field('pm_save','pm_nonce');
            $json=get_post_meta($prompt->ID,'_pm_json',true);
            if(empty($json)&&in_array($prompt->post_status,['auto-draft','draft'],true)) {
                $json=json_encode(['context'=>'...','instructions'=>'...','input'=>'...','output'=>'...'],JSON_PRETTY_PRINT);
            }
            echo '<textarea name="pm_json" style="width:100%;height:200px;">'.esc_textarea($json).'</textarea>';
        }
        public function box_eval($prompt){
            $json=get_post_meta($prompt->ID,'_pm_json',true);
            $res=PM_Evaluator::evaluate($json);
            echo '<ul>';
            foreach($res['checks'] as $k=>$v){
                echo '<li>' . esc_html($k) . ': ' . ($v?'Yes':'No') . '</li>';
            }
            echo '</ul><p>Score: '.intval($res['score']).'/4</p>';
        }
        public function save_json($id,$prompt){
            if(!isset($_POST['pm_nonce'])||!wp_verify_nonce($_POST['pm_nonce'],'pm_save')) return;
            if(!current_user_can('edit_prompt',$id)) return;
            if(isset($_POST['pm_json'])) update_post_meta($id,'_pm_json',wp_kses_post(stripslashes($_POST['pm_json'])));
        }
        public function page_dashboard(){ echo '<h1>Prompt Manager</h1><p>Select a submenu.</p>'; }
        public function page_import(){ echo '<h1>Import</h1>'; }
        public function page_export(){ echo '<h1>Export</h1>'; }
        public function page_help(){ echo '<h1>Help</h1>'; }
    }
}
