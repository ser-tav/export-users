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

	// activation function
	public static function activation() {

		//update rewrite rules
		flush_rewrite_rules();
	}

	// deactivation function
	public static function deactivation() {

		//update rewrite rules
		flush_rewrite_rules();
	}

	// enqueue admin
	public function enqueue_admin() {
		wp_enqueue_style( 'exportUserStyle', plugins_url( '/assets/admin/styles.css', __FILE__ ) );
		wp_enqueue_script( 'exportUserScript', plugins_url( '/assets/admin/scripts.js', __FILE__ ) );
	}

	// register settings
	public function settings_init() {

		register_setting( 'export-users_settings', 'export_settings_options' );
		add_settings_section( 'export_settings_section', esc_html__( 'Export settings', 'export-users' ), [ $this, 'settings_section_callback' ], 'export_settings' );
		add_settings_field(
			'checkbox_element',
			'Select fields:',
			[ $this, 'checkbox_element_callback' ],
			'export_settings',
			'export_settings_section'
		);
	}

	// section callback function
	public function settings_section_callback() {
		echo esc_html__( 'Please select the required fields to export and save', 'export-users' );
	}

	// checkbox callback function
	public function checkbox_element_callback() {

		$options          = get_option( 'export_settings_options', [] );
		$checkbox_element = isset( $options['checkbox_element'] ) ? (array) $options['checkbox_element'] : [];

        // include checkbox template
		require_once plugin_dir_path( __FILE__ ) . 'admin/checkbox.php';
	}

	// add settings link to plugin page
	public function add_plugin_setting_link( $link ) {
		$custom_link = '<a href="users.php?page=export_settings">' . esc_html__( 'Settings', 'export-users' ) . '</a>';
		array_push( $link, $custom_link );

		return $link;
	}

	// add menu page
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

	// include settings page template
	public function export_page() {
		require_once plugin_dir_path( __FILE__ ) . 'admin/admin.php';
	}

	// add admin footer script
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

	// export selected users action
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

	// export selected users
	public function export_selected( $user_ids ) {
		if ( current_user_can( 'manage_options' ) ) {

			$export_users = $user_ids;
			$this->export_template( $export_users );

			return true;
		}

		// current user has no access to export the template
		return false;
	}

	// export all users
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

	// csv template
	public function export_template( $export_users ) {

		$options = get_option( 'export_settings_options' )['checkbox_element'];
		if ( ! empty( $options ) ) {
			$delimiter = ",";
			$filename  = "users-" . date( 'd-m-Y' ) . ".csv";
			$out       = fopen( 'php://output', 'w' );

			fputcsv( $out, $options, $delimiter );

			foreach ( $export_users as $user_id ) {
				$user_data       = get_userdata( $user_id );
				$nickname        = $user_data->nickname;
				$first_name      = ( isset( $user_data->first_name ) && $user_data->first_name != '' ) ? $user_data->first_name : '';
				$last_name       = ( isset( $user_data->last_name ) && $user_data->last_name != '' ) ? $user_data->last_name : '';
				$email           = $user_data->user_email;
				$role            = $user_data->roles[0];
				$website         = $user_data->user_url;
				$registered_date = $user_data->user_registered;
				$lineData        = [];

				if ( in_array( 'User ID', $options ) ) {
					array_push( $lineData, $user_id );
				}
				if ( in_array( 'Nickname', $options ) ) {
					array_push( $lineData, $nickname );
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
				if ( in_array( 'Website', $options ) ) {
					array_push( $lineData, $website );
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
			wp_safe_redirect( 'users.php?page=export_settings' );
		}
	}
}

if ( class_exists( 'ExportUsers' ) ) {
	$export_users = new ExportUsers();
	$export_users->register();
}

register_activation_hook( __FILE__, [ $export_users, 'activation' ] );
register_deactivation_hook( __FILE__, [ $export_users, 'deactivation' ] );