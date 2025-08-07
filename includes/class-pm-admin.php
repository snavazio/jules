<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'PM_Admin' ) ) {
    class PM_Admin {
        public function __construct() {
            add_action('admin_menu', array($this,'menus'));
            add_action('admin_init', array($this, 'handle_form_actions'));
            add_action('admin_notices', array($this, 'show_admin_notices'));
        }
        public function menus() {
            add_menu_page('Prompt Manager','Prompt Manager','manage_options','prompt-manager',array($this,'page_dashboard'));
            add_submenu_page('prompt-manager','All Prompts','All Prompts','manage_options','prompt-manager',array($this,'page_dashboard'));
            add_submenu_page('prompt-manager','Add New','Add New','manage_options','prompt-manager-new',array($this,'page_new'));
            add_submenu_page('prompt-manager','Import','Import','manage_options','prompt-manager-import',array($this,'page_import'));
            add_submenu_page('prompt-manager','Export','Export','manage_options','prompt-manager-export',array($this,'page_export'));
            add_submenu_page('prompt-manager','Help','Help','manage_options','prompt-manager-help',array($this,'page_help'));
        }
        public function page_dashboard(){
            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline">Prompts</h1>
                <a href="?page=prompt-manager-new" class="page-title-action">Add New</a>
                <hr class="wp-header-end">
                <form method="post">
                    <?php
                    $list_table = new PM_Prompts_List_Table();
                    $list_table->prepare_items();
                    $list_table->display();
                    ?>
                </form>
            </div>
            <?php
        }
        public function page_new(){
            global $wpdb;
            $table_name = $wpdb->prefix . 'pm_prompts';
            $prompt = null;
            $is_editing = false;

            if(isset($_GET['id'])){
                $is_editing = true;
                $prompt_id = absint($_GET['id']);
                $prompt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $prompt_id));
            }
            ?>
            <div class="wrap">
                <h1><?php echo $is_editing ? 'Edit Prompt' : 'Add New Prompt'; ?></h1>
                <form method="post" action="?page=prompt-manager">
                    <input type="hidden" name="pm_action" value="<?php echo $is_editing ? 'update_prompt' : 'add_prompt'; ?>" />
                    <?php if($is_editing): ?>
                        <input type="hidden" name="prompt_id" value="<?php echo esc_attr($prompt->id); ?>" />
                    <?php endif; ?>
                    <?php wp_nonce_field($is_editing ? 'pm_update_prompt' : 'pm_add_prompt'); ?>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="title">Title</label></th>
                                <td><input name="title" type="text" id="title" value="<?php echo $is_editing ? esc_attr($prompt->title) : ''; ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="json_content">JSON Content</label></th>
                                <td>
                            <textarea name="json_content" id="json_content" rows="10" class="large-text"><?php
                                if ($is_editing) {
                                    echo esc_textarea($prompt->json_content);
                                } else {
                                    echo esc_textarea(json_encode([
                                        "context" => "You are a helpful assistant.",
                                        "instructions" => "Translate the following English text to French.",
                                        "input" => "Hello, world!",
                                        "output" => ""
                                    ], JSON_PRETTY_PRINT));
                                }
                            ?></textarea>
                                    <p class="description">Enter the JSON for your prompt.</p>
                                </td>
                            </tr>
                    <tr>
                        <th scope="row"><label for="rating">Rating</label></th>
                        <td><input name="rating" type="number" id="rating" value="<?php echo $is_editing ? esc_attr($prompt->rating) : '0'; ?>" min="0" max="5" class="small-text"></td>
                    </tr>
                        </tbody>
                    </table>
                    <?php submit_button($is_editing ? 'Update Prompt' : 'Add Prompt'); ?>
                </form>
            </div>
            <?php
            wp_enqueue_code_editor(array('type' => 'application/json', 'codemirror' => array('autoRefresh' => true)));
        }
        public function page_import(){
            ?>
            <div class="wrap">
                <h1>Import Prompts</h1>
                <form method="post" enctype="multipart/form-data" action="">
                    <input type="hidden" name="pm_action" value="import_prompts" />
                    <?php wp_nonce_field('pm_import_prompts'); ?>
                    <p>Select a JSON file to upload. The file should contain an array of objects, with each object having a "title" and a "json_content" key.</p>
                    <p>
                        <input type="file" name="import_file" />
                    </p>
                    <?php submit_button('Upload and Import'); ?>
                </form>
            </div>
            <?php
        }
        public function page_export(){
            global $wpdb;
            $table_name = $wpdb->prefix . 'pm_prompts';
            $prompts = $wpdb->get_results("SELECT id, title FROM $table_name ORDER BY title ASC");
            ?>
            <div class="wrap">
                <h1>Export Prompts</h1>
                <form method="post" action="">
                    <input type="hidden" name="pm_action" value="export_prompts" />
                    <?php wp_nonce_field('pm_export_prompts'); ?>
                    <p>Select the prompts you would like to export.</p>
                    <ul class="ul-disc">
                        <?php foreach($prompts as $prompt): ?>
                            <li><label><input type="checkbox" name="prompt_ids[]" value="<?php echo esc_attr($prompt->id); ?>"> <?php echo esc_html($prompt->title); ?></label></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php submit_button('Export Selected'); ?>
                </form>
            </div>
            <?php
        }
        public function page_help(){ echo '<h1>Help</h1>'; }

        public function handle_form_actions(){
            global $wpdb;
            $table_name = $wpdb->prefix . 'pm_prompts';

            // Add/Update
            if(isset($_POST['pm_action'])){
                if($_POST['pm_action'] == 'add_prompt'){
                    check_admin_referer('pm_add_prompt');
                    $wpdb->insert($table_name, array(
                        'title' => sanitize_text_field($_POST['title']),
                        'json_content' => wp_kses_post($_POST['json_content']),
                        'rating' => absint($_POST['rating'])
                    ));
                    wp_redirect('?page=prompt-manager&message=1');
                    exit;
                }
                if($_POST['pm_action'] == 'update_prompt'){
                    check_admin_referer('pm_update_prompt');
                    $wpdb->update($table_name, array(
                        'title' => sanitize_text_field($_POST['title']),
                        'json_content' => wp_kses_post($_POST['json_content']),
                        'rating' => absint($_POST['rating'])
                    ), array('id' => absint($_POST['prompt_id'])));
                    wp_redirect('?page=prompt-manager&message=2');
                    exit;
                }
                if($_POST['pm_action'] == 'export_prompts'){
                    check_admin_referer('pm_export_prompts');
                    if(empty($_POST['prompt_ids'])){
                        wp_redirect('?page=prompt-manager-export&message=1');
                        exit;
                    }
                    $prompt_ids = implode(',', array_map('absint', $_POST['prompt_ids']));
                    $prompts = $wpdb->get_results("SELECT title, json_content FROM $table_name WHERE id IN ($prompt_ids)");

                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename=prompts-export.json');
                    echo json_encode($prompts, JSON_PRETTY_PRINT);
                    exit;
                }
                if($_POST['pm_action'] == 'import_prompts'){
                    check_admin_referer('pm_import_prompts');
                    if(empty($_FILES['import_file']['tmp_name'])){
                        wp_redirect('?page=prompt-manager-import&message=1');
                        exit;
                    }
                    $json = file_get_contents($_FILES['import_file']['tmp_name']);
                    $data = json_decode($json, true);
                    if(is_array($data)){
                        foreach($data as $prompt){
                            if(isset($prompt['title']) && isset($prompt['json_content'])){
                                $wpdb->insert($table_name, array(
                                    'title' => sanitize_text_field($prompt['title']),
                                    'json_content' => wp_kses_post(json_encode($prompt['json_content']))
                                ));
                            }
                        }
                    }
                    wp_redirect('?page=prompt-manager&message=4');
                    exit;
                }
            }

            // Delete
            if(isset($_GET['action']) && $_GET['action'] == 'delete'){
                check_admin_referer('pm_delete_prompt');
                $wpdb->delete($table_name, array('id' => absint($_GET['id'])));
                wp_redirect('?page=prompt-manager&message=3');
                exit;
            }
        }

        public function show_admin_notices(){
            if(isset($_GET['message'])){
                $message = absint($_GET['message']);
                $class = 'notice-success is-dismissible';
                $text = '';
                switch($message){
                    case 1: $text = 'Prompt added successfully.'; break;
                    case 2: $text = 'Prompt updated successfully.'; break;
                    case 3: $text = 'Prompt deleted successfully.'; break;
                    case 4: $text = 'Prompts imported successfully.'; break;
                }
                if(isset($_GET['page']) && $_GET['page'] == 'prompt-manager-import' && $message == 1){
                    $class = 'notice-error is-dismissible';
                    $text = 'Please select a file to import.';
                }
                if($text){
                    printf('<div class="notice %s"><p>%s</p></div>', esc_attr($class), esc_html($text));
                }
            }
        }
    }
}
