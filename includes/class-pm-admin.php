<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'PM_Admin' ) ) {
    class PM_Admin {
        public function __construct() {
            add_action('admin_menu', array($this,'menus'));
            add_action('add_meta_boxes', array($this,'metaboxes'));
            add_action('save_post_json_prompt', array($this,'save_json'),10,2);
            add_action('admin_enqueue_scripts', array($this,'assets'));
            add_action('post_submitbox_misc_actions', array($this, 'add_save_as_new_button'));
            add_action('admin_action_save_as_new', array($this, 'handle_save_as_new'));
            add_action('init', array($this, 'handle_export'));
            add_action('admin_head', array($this, 'rating_styles'));
            add_action('save_post_json_prompt', array($this, 'save_rating'), 10, 2);
            add_filter('manage_json_prompt_posts_columns', array($this, 'add_rating_column'));
            add_action('manage_json_prompt_posts_custom_column', array($this, 'display_rating_column'), 10, 2);
            add_filter('manage_edit-json_prompt_sortable_columns', array($this, 'make_rating_column_sortable'));
        }
        public function menus() {
            add_menu_page('Prompt Manager','Prompt Manager','edit_prompts','prompt-manager',array($this,'page_dashboard'));          
            add_submenu_page('prompt-manager','All Prompts','All Prompts','edit_prompts','edit.php?post_type=json_prompt');
            add_submenu_page('prompt-manager','Add New','Add New','edit_prompts','post-new.php?post_type=json_prompt');
            add_submenu_page('prompt-manager','Import','Import','edit_prompts','prompt-manager-import',array($this,'page_import'));
            add_submenu_page('prompt-manager','Export','Export','edit_prompts','prompt-manager-export',array($this,'page_export'));
            add_submenu_page('prompt-manager','Help','Help','edit_prompts','prompt-manager-help',array($this,'page_help'));
        }
        public function metaboxes() {
            add_meta_box('pm_json','JSON Prompt Content',array($this,'box_json'),'json_prompt','normal','high');
            add_meta_box('pm_eval','Prompt Evaluation',array($this,'box_eval'),'json_prompt','side');
            add_meta_box('pm_rating', 'Rating', array($this, 'box_rating'), 'json_prompt', 'side');
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
        public function box_rating($post){
            wp_nonce_field('pm_save_rating', 'pm_rating_nonce');
            $rating = get_post_meta($post->ID, '_pm_rating', true);
            ?>
            <div class="star-rating">
                <input type="radio" id="5-stars" name="pm_rating" value="5" <?php checked($rating, 5); ?> /><label for="5-stars" class="star">&#9733;</label>
                <input type="radio" id="4-stars" name="pm_rating" value="4" <?php checked($rating, 4); ?> /><label for="4-stars" class="star">&#9733;</label>
                <input type="radio" id="3-stars" name="pm_rating" value="3" <?php checked($rating, 3); ?> /><label for="3-stars" class="star">&#9733;</label>
                <input type="radio" id="2-stars" name="pm_rating" value="2" <?php checked($rating, 2); ?> /><label for="2-stars" class="star">&#9733;</label>
                <input type="radio" id="1-star"  name="pm_rating" value="1" <?php checked($rating, 1); ?> /><label for="1-star" class="star">&#9733;</label>
            </div>
            <?php
        }
        public function save_json($id,$prompt){
            if(!isset($_POST['pm_nonce'])||!wp_verify_nonce($_POST['pm_nonce'],'pm_save')) return;
            if(!current_user_can('edit_prompt',$id)) return;
            if(isset($_POST['pm_json'])) update_post_meta($id,'_pm_json',wp_kses_post(stripslashes($_POST['pm_json'])));
        }
        public function page_dashboard(){ echo '<h1>Prompt Manager</h1><p>Select a submenu.</p>'; }
        public function page_import(){ echo '<h1>Import</h1>'; }
        public function page_export(){
            ?>
            <div class="wrap">
                <h1>Export Prompts</h1>
                <form method="post" action="">
                    <input type="hidden" name="pm_export_action" value="export" />
                    <?php wp_nonce_field('pm_export_nonce', 'pm_export_nonce_field'); ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td>
                                <th scope="col" id="title" class="manage-column column-title column-primary">Title</th>
                            </tr>
                        </thead>
                        <tbody id="the-list">
                            <?php
                            $prompts = get_posts(array('post_type' => 'json_prompt', 'posts_per_page' => -1));
                            if($prompts){
                                foreach($prompts as $prompt){
                                    ?>
                                    <tr>
                                        <th scope="row" class="check-column">
                                            <input type="checkbox" name="prompt_ids[]" value="<?php echo esc_attr($prompt->ID); ?>">
                                        </th>
                                        <td class="title column-title has-row-actions column-primary" data-colname="Title">
                                            <strong><a class="row-title" href="<?php echo get_edit_post_link($prompt->ID); ?>"><?php echo esc_html($prompt->post_title); ?></a></strong>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="2">No prompts found.</td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php submit_button('Export Selected'); ?>
                </form>
            </div>
            <?php
        }
        public function page_help(){ echo '<h1>Help</h1>'; }
        public function add_save_as_new_button($post){
            if($post->post_type == 'json_prompt'){
                echo '<div class="misc-pub-section misc-pub-section-last" style="border-top: 1px solid #eee;"><a href="'.admin_url('post.php?post='.$post->ID.'&action=save_as_new').'" class="button button-secondary">Save as New</a></div>';
            }
        }
        public function handle_save_as_new(){
            if(!(isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] == 'save_as_new')){
                wp_die('No post to duplicate has been supplied!');
            }

            $post_id = absint($_GET['post']);
            $post = get_post($post_id);

            if(isset($post) && $post != null){
                $new_post_id = wp_insert_post(array(
                    'post_title' => $post->post_title . ' (Copy)',
                    'post_content' => $post->post_content,
                    'post_status' => 'draft',
                    'post_type' => $post->post_type,
                ));

                $post_meta_keys = get_post_custom_keys($post_id);
                if(!empty($post_meta_keys)){
                    foreach($post_meta_keys as $meta_key){
                        $meta_values = get_post_custom_values($meta_key, $post_id);
                        foreach($meta_values as $meta_value){
                            add_post_meta($new_post_id, $meta_key, $meta_value);
                        }
                    }
                }
                wp_redirect(admin_url('post.php?post='.$new_post_id.'&action=edit'));
                exit;
            } else {
                wp_die('Post creation failed, could not find original post: ' . $post_id);
            }
        }
        public function handle_export(){
            if(isset($_POST['pm_export_action']) && $_POST['pm_export_action'] == 'export'){
                if(!isset($_POST['pm_export_nonce_field']) || !wp_verify_nonce($_POST['pm_export_nonce_field'], 'pm_export_nonce')){
                    wp_die('Invalid nonce.');
                }
                if(!current_user_can('edit_prompts')){
                    wp_die('You do not have permission to export prompts.');
                }
                if(empty($_POST['prompt_ids'])){
                    wp_die('No prompts selected for export.');
                }

                $prompt_ids = array_map('absint', $_POST['prompt_ids']);
                $export_data = array();

                foreach($prompt_ids as $prompt_id){
                    $prompt = get_post($prompt_id);
                    if($prompt && $prompt->post_type == 'json_prompt'){
                        $json_content = get_post_meta($prompt_id, '_pm_json', true);
                        $export_data[] = array(
                            'title' => $prompt->post_title,
                            'json' => json_decode($json_content)
                        );
                    }
                }

                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename=prompts-export.json');
                echo json_encode($export_data, JSON_PRETTY_PRINT);
                exit;
            }
        }
        public function rating_styles(){
            ?>
            <style>
                .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; }
                .star-rating input[type="radio"] { display: none; }
                .star-rating label { font-size: 2em; color: #ddd; cursor: pointer; }
                .star-rating input[type="radio"]:checked ~ label,
                .star-rating label:hover,
                .star-rating label:hover ~ label { color: #f2b600; }
            </style>
            <?php
        }
        public function save_rating($post_id, $post){
            if(!isset($_POST['pm_rating_nonce']) || !wp_verify_nonce($_POST['pm_rating_nonce'], 'pm_save_rating')) return;
            if(!current_user_can('edit_prompt', $post_id)) return;
            if(isset($_POST['pm_rating'])){
                update_post_meta($post_id, '_pm_rating', absint($_POST['pm_rating']));
            }
        }
        public function add_rating_column($columns){
            $columns['rating'] = 'Rating';
            return $columns;
        }
        public function display_rating_column($column, $post_id){
            if($column == 'rating'){
                $rating = get_post_meta($post_id, '_pm_rating', true);
                echo str_repeat('&#9733;', absint($rating));
                echo str_repeat('&#9734;', 5 - absint($rating));
            }
        }
        public function make_rating_column_sortable($columns){
            $columns['rating'] = 'rating';
            return $columns;
        }
    }
}
