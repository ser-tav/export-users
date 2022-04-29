<?php
/*
Plugin Name: Export Users to csv
Description: Plugin for export users to csv
Version: 1.0
Author: Sergey T
Author URI: https://github.com/ser-tav/
License: GPLv2 or later
Text Domain: export-users
*/

if (!defined('ABSPATH')) {
    die;
}

class ExportUsers
{

    public function register()
    {

        //Add export hook
        add_action('admin_init', [$this, 'export_all_users_csv']);

        //Add button hook
        add_action('admin_footer-users.php', array(&$this, 'bulk_admin_footer'));
        add_action('load-users.php', array(&$this, 'bulk_action'));
    }

    static function activation()
    {

        //update rewrite rules
        flush_rewrite_rules();
    }

    static function deactivation()
    {

        //update rewrite rules
        flush_rewrite_rules();
    }

    function bulk_admin_footer()
    {
        // Check users page
        $screen = get_current_screen();
        if ($screen->id != "users") {
            return;
        }
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                // Add "Export selected" option
                $('<option>').val('export').text('<?php _e('Export selected')?>').appendTo("select[name='action']");
                // Add "Export all" button
                $('.tablenav.top .clear, .tablenav.bottom .clear').before('<form action="#" method="POST"><input type="hidden" id="export_csv" name="export-users" value="1" /><input class="button button-primary user_export_button" type="submit" value="<?php esc_attr_e('Export all', 'export-users');?>" /></form>');
            });
        </script>
        <?php
    }

    public function bulk_action()
    {
        // get the action
        $wp_list_table = _get_list_table('WP_Users_List_Table');
        $action = $wp_list_table->current_action();

        $allowed_actions = array("export");
        if (!in_array($action, $allowed_actions)) {
            return;
        }

        // security check
        check_admin_referer('bulk-users');

        // make sure ids are submitted.
        if (isset($_REQUEST['users'])) {
            $user_ids = array_map('intval', $_REQUEST['users']);
        }

        if (empty($user_ids)) {
            return;
        }

        switch ($action) {
            case 'export':
                $exported = 0;
                foreach ($user_ids as $user_id) {
                    if (!$this->selected_export($user_ids)) {
                        wp_die(__('Error exporting user.'));
                    }
                    $exported++;
                }
                break;
            default:
                return;
        }
        exit();
    }

    public function export_all_users_csv()
    {
        if (!empty($_POST['export-users'])) {
            if (current_user_can('manage_options')) {
                $args = array(
                    'order' => 'ASC',
                    'orderby' => 'display_name',
                    'fields' => 'all',
                );

                // The user query
                $exportusers = get_users($args);

                $delimiter = ",";
                $filename = "users-" . date('d-m-Y') . ".csv";

                // Create a file pointer
                $out = fopen('php://output', 'w');

                // Set column headers
                $fields = array('Имя', 'Фамилия', 'Email', 'Роль', 'Дата регистрации');
                fputcsv($out, $fields, $delimiter);

                // Output each row of the data, format line as csv and write to file pointer
                foreach ($exportusers as $user) {
                    $meta = get_user_meta($user->ID);
                    $role = $user->roles[0];
                    $email = $user->user_email;
                    $first_name = (isset($meta['first_name'][0]) && $meta['first_name'][0] != '') ? $meta['first_name'][0] : '';
                    $last_name = (isset($meta['last_name'][0]) && $meta['last_name'][0] != '') ? $meta['last_name'][0] : '';
                    $registered_date = $user->user_registered;

                    $lineData = array($first_name, $last_name, $email, $role, $registered_date);
                    fputcsv($out, $lineData, $delimiter);
                }

                header("Content-type: application/force-download");
                header('Content-Disposition: inline; filename="' . $filename . '";');

                fpassthru($out);
            }
            exit;
        }
    }

    function selected_export($user_ids)
    {
        if (current_user_can('manage_options')) {
            $delimiter = ",";
            $filename = "users-" . date('d-m-Y') . ".csv";

            $out = fopen('php://output', 'w');
            $fields = array('Имя', 'Фамилия', 'Email', 'Роль', 'Дата регистрации');
            fputcsv($out, $fields, $delimiter);

            foreach ($user_ids as $user_id) {
                $user_data = get_userdata($user_id);
                $role = $user_data->roles[0];
                $email = $user_data->user_email;
                $registered_date = $user_data->user_registered;

                $first_name = (isset($user_data->first_name) && $user_data->first_name != '') ? $user_data->first_name : '';
                $last_name = (isset($user_data->last_name) && $user_data->last_name != '') ? $user_data->last_name : '';

                $lineData = array($first_name, $last_name, $email, $role, $registered_date);
                fputcsv($out, $lineData, $delimiter);
            }

            header("Content-type: application/force-download");
            header('Content-Disposition: inline; filename="' . $filename . '";');

            fpassthru($out);
        }
        exit();
    }
}

if (class_exists('ExportUsers')) {
    $exportusers = new ExportUsers();
    $exportusers->register();
}

register_activation_hook(__FILE__, array($exportusers, 'activation'));
register_deactivation_hook(__FILE__, array($exportusers, 'deactivation'));