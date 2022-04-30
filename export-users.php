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

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class ExportUsers {

	public function register() {

		//enqueue
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );

		// add button hook
		add_action( 'admin_footer-users.php', [ &$this, 'bulk_admin_footer' ] );
		add_action( 'load-users.php', [ &$this, 'bulk_action' ] );

		// add export hook
		add_action( 'admin_init', [ $this, 'export_all_users_csv' ] );

		// add menu admin
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'settings_init' ], 999 );

		// add link to plugin page
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'add_plugin_setting_link' ] );
	}

	public static function activation() {

		//update rewrite rules
		flush_rewrite_rules();
	}

	public static function deactivation() {

		//update rewrite rules
		flush_rewrite_rules();
	}

	//Enqueue Admin
	public function enqueue_admin() {
		wp_enqueue_style( 'exportUserStyle', plugins_url( '/assets/admin/styles.css', __FILE__ ) );
		wp_enqueue_script( 'exportUserScript', plugins_url( '/assets/admin/scripts.js', __FILE__ ) );
	}

	//Register settings
	public function settings_init() {

		register_setting( 'export-users_settings', 'export_settings_options' );
		add_settings_section( 'export_settings_section', esc_html__( 'Export settings', 'export-users' ), [ $this, 'settings_section_html' ], 'export_settings' );
		add_settings_field(
			'checkbox_element',
			'Exported user fields',
			[ $this, 'sandbox_checkbox_element_callback' ],
			'export_settings',
			'export_settings_section'
		);
	}

	public function settings_section_html() {
		// section function
	}

	function sandbox_checkbox_element_callback() {

		$options          = get_option( 'export_settings_options', [] );
		$checkbox_element = isset( $options['checkbox_element'] ) ? (array) $options['checkbox_element'] : [];
		?>

        <div class="settings_body">
            <div class="btn_container">
                <div class="select_all_btn">Select all</div>
                <div class="delimiter">&nbsp;/&nbsp;</div>
                <div class="clear_all_btn">Clear</div>
            </div>
            <div>
                <input type="checkbox" id="user_id" value="User ID" name="export_settings_options[checkbox_element][]" <?php checked( in_array( 'User ID', $checkbox_element ), 1 ); ?> />
                <label for="user_id"><?= esc_html__( 'User ID', 'export-users' ); ?></label>
            </div>
            <div>
                <input type="checkbox" id="first_name" value="First name" name="export_settings_options[checkbox_element][]" <?php checked( in_array( 'First name', $checkbox_element ), 1 ); ?> />
                <label for="first_name"><?= esc_html__( 'First name', 'export-users' ); ?></label>
            </div>
            <div>
                <input type="checkbox" id="last_name" value="Last name" name="export_settings_options[checkbox_element][]" <?php checked( in_array( 'Last name', $checkbox_element ), 1 ); ?> />
                <label for="last_name"><?= esc_html__( 'Last name', 'export-users' ); ?></label>
            </div>
            <div>
                <input type="checkbox" id="email" value="Email" name="export_settings_options[checkbox_element][]" <?php checked( in_array( 'Email', $checkbox_element ), 1 ); ?> />
                <label for="email"><?= esc_html__( 'Email', 'export-users' ); ?></label>
            </div>
            <div>
                <input type="checkbox" id="role" value="Role" name="export_settings_options[checkbox_element][]" <?php checked( in_array( 'Role', $checkbox_element ), 1 ); ?> />
                <label for="role"><?= esc_html__( 'Role', 'export-users' ); ?></label>
            </div>
            <div>
                <input type="checkbox" id="registered_date" value="Registered date" name="export_settings_options[checkbox_element][]" <?php checked( in_array( 'Registered date', $checkbox_element ), 1 ); ?> />
                <label for="registered_date"><?= esc_html__( 'Registered date', 'export-users' ); ?></label>
            </div>
        </div>
		<?php
	}

	//Add settings link to plugin page
	public function add_plugin_setting_link( $link ) {
		$custom_link = '<a href="users.php?page=export_settings">' . esc_html__( 'Settings', 'export-users' ) . '</a>';
		array_push( $link, $custom_link );

		return $link;
	}

	//Add menu page
	public function add_admin_menu() {
		add_submenu_page(
			'users.php',
			esc_html__( 'Export Settings Page', 'export-users' ),
			esc_html__( 'Export Settings', 'export-users' ),
			'manage_options',
			'export_settings',
			[ $this, 'export_page' ],
			100
		);
	}

	//AleBooking Admin HTML
	public function export_page() {
		require_once plugin_dir_path( __FILE__ ) . 'admin/admin.php';
	}

	public function bulk_admin_footer() {
		// check if the user page is
		$screen = get_current_screen();
		if ( $screen->id != "users" ) {
			return;
		}
		?>
        <script>
            jQuery(document).ready(function ($) {
                // add "Export" option
                $('<option>').val('export').text('<?php _e( 'Export' )?>').appendTo("select[name='action']");
                // add "Export all" button
                $('.tablenav.top .clear, .tablenav.bottom .clear').before('<form action="#" method="POST"><input type="hidden" id="export_csv" name="export-users" value="1" /><input class="button button-primary user_export_button" type="submit" value="<?php esc_attr_e( 'Export all', 'export-users' );?>" /></form>');
            });
        </script>
		<?php
	}

	public function bulk_action() {
		// get the action
		$wp_list_table   = _get_list_table( 'WP_Users_List_Table' );
		$action          = $wp_list_table->current_action();
		$allowed_actions = [ 'export' ];

		if ( ! in_array( $action, $allowed_actions ) ) {
			return;
		}

		// make sure ids are submitted.
		if ( isset( $_REQUEST['users'] ) ) {
			$user_ids = array_map( 'intval', $_REQUEST['users'] );
		}

		if ( empty( $user_ids ) ) {
			wp_safe_redirect( 'users.php' );
		}

		switch ( $action ) {
			case 'export':
				if ( ! $this->export_selected( $user_ids ) ) {
					wp_die( __( 'Error exporting user.' ) );
				}
				break;
			default:
				return;
		}
		exit();
	}

	public function export_all_users_csv() {
		if ( current_user_can( 'manage_options' ) ) {
			if ( ! empty( $_POST['export-users'] ) ) {

				// get all users
				$export_users = get_users( [ 'order' => 'ASC', 'orderby' => 'display_name', 'fields' => 'ID' ] );
				$this->export_template( $export_users );

				exit();
			}
		}
	}

	public function export_selected( $user_ids ) {
		if ( current_user_can( 'manage_options' ) ) {

			$export_users = $user_ids;
			$this->export_template( $export_users );

			exit();
		}
	}

	public function export_template( $export_users ) {

		$options   = get_option( 'export_settings_options' )['checkbox_element'];
        if (!empty($options)) {
	        $delimiter = ",";
	        $filename  = "users-" . date( 'd-m-Y' ) . ".csv";
	        $out       = fopen( 'php://output', 'w' );

	        fputcsv( $out, $options, $delimiter );

	        foreach ( $export_users as $user_id ) {
		        $user_data       = get_userdata( $user_id );
		        $first_name      = ( isset( $user_data->first_name ) && $user_data->first_name != '' ) ? $user_data->first_name : '';
		        $last_name       = ( isset( $user_data->last_name ) && $user_data->last_name != '' ) ? $user_data->last_name : '';
		        $email           = $user_data->user_email;
		        $role            = $user_data->roles[0];
		        $registered_date = $user_data->user_registered;
		        $lineData        = [];

		        if ( in_array( 'User ID', $options ) ) {
			        array_push( $lineData, $user_id );
		        }
		        if ( in_array( 'First name', $options ) ) {
			        array_push( $lineData, $first_name );
		        }
		        if ( in_array( 'Last name', $options ) ) {
			        array_push( $lineData, $last_name );
		        }
		        if ( in_array( 'Email', $options ) ) {
			        array_push( $lineData, $email );
		        }
		        if ( in_array( 'Role', $options ) ) {
			        array_push( $lineData, $role );
		        }
		        if ( in_array( 'Registered date', $options ) ) {
			        array_push( $lineData, $registered_date );
		        }

		        fputcsv( $out, $lineData, $delimiter );
	        }

	        header( "Content-type: application/force-download" );
	        header( 'Content-Disposition: inline; filename="' . $filename . '";' );

	        fpassthru( $out );
        } else {
            wp_safe_redirect('users.php?page=export_settings');
        }
	}

}

if ( class_exists( 'ExportUsers' ) ) {
	$export_users = new ExportUsers();
	$export_users->register();
}

register_activation_hook( __FILE__, [ $export_users, 'activation' ] );
register_deactivation_hook( __FILE__, [ $export_users, 'deactivation' ] );