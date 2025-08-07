<?php
if(!class_exists('WP_List_Table')){
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class PM_Prompts_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'prompt',
            'plural'   => 'prompts',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'title'        => 'Title',
            'json_content' => 'JSON Content',
            'rating'       => 'Rating',
            'created_at'   => 'Date'
        ];
    }

    public function get_sortable_columns() {
        return [
            'title'      => ['title', false],
            'rating'     => ['rating', false],
            'created_at' => ['created_at', true]
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pm_prompts';
        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'created_at';
        $order = isset($_GET['order']) ? sanitize_key($_GET['order']) : 'desc';

        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'json_content':
                return '<pre>' . esc_html(json_encode(json_decode($item[$column_name]), JSON_PRETTY_PRINT)) . '</pre>';
            case 'rating':
                return str_repeat('&#9733;', absint($item['rating'])) . str_repeat('&#9734;', 5 - absint($item['rating']));
            case 'created_at':
                return mysql2date('Y/m/d g:i:s a', $item[$column_name]);
            default:
                return print_r($item, true);
        }
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="prompt[]" value="%s" />', $item['id']);
    }

    public function column_title($item) {
        $actions = [
            'edit'   => sprintf('<a href="?page=prompt-manager-new&id=%s">Edit</a>', $item['id']),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s&_wpnonce=%s">Delete</a>', $_REQUEST['page'], $item['id'], wp_create_nonce('pm_delete_prompt'))
        ];
        return sprintf('%1$s %2$s', $item['title'], $this->row_actions($actions));
    }
}
