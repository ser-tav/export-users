<?php
/*
Plugin Name: Export Users to csv
Description: Plugin export users to csv
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

		//Add button hook
		add_action( 'admin_footer', [ $this, 'export_all_users' ] );
		//Add export hook
		add_action( 'admin_init', [ $this, 'export_csv' ] );
		add_filter( 'bulk_actions-users', array( $this, 'export_option' ) );
	}


	static function activation() {

		//update rewrite rules
		flush_rewrite_rules();
	}

	static function deactivation() {

		//update rewrite rules
		flush_rewrite_rules();
	}

	public function export_option( $bulk_actions ) {
		$bulk_actions['export-selected'] = __( 'Export selected', 'export-users' );

		return $bulk_actions;
	}

	public function export_all_users() {
		$screen = get_current_screen();
		if ( $screen->id != "users" ) {
			return;
		}
		?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('.tablenav.top .clear, .tablenav.bottom .clear').before('<form action="#" method="POST"><input type="hidden" id="mytheme_export_csv" name="export-users" value="1" /><input class="button button-primary user_export_button" style="margin-top:3px;" type="submit" value="<?php esc_attr_e( 'Export All as CSV', 'export-users' );?>" /></form>');
            });
        </script>
		<?php
	}

	public function export_csv() {
		if ( ! empty( $_POST['export-users'] ) ) {

			if ( current_user_can( 'manage_options' ) ) {
				$args = array(
					'order'   => 'ASC',
					'orderby' => 'display_name',
					'fields'  => 'all',
				);

				// The User Query
				$exportusers = get_users( $args );

				$delimiter = ",";
				$filename  = "users-" . date( 'd-m-Y' ) . ".csv";

				// Create a file pointer
				$out = fopen( 'php://output', 'w' );

				// Set column headers
				$fields = array( 'Имя', 'Фамилия', 'Email', 'Роль' );
				fputcsv( $out, $fields, $delimiter );

				// Output each row of the data, format line as csv and write to file pointer
				foreach ( $exportusers as $user ) {
					$meta       = get_user_meta( $user->ID );
					$role       = $user->roles;
					$email      = $user->user_email;
					$first_name = ( isset( $meta['first_name'][0] ) && $meta['first_name'][0] != '' ) ? $meta['first_name'][0] : '';
					$last_name  = ( isset( $meta['last_name'][0] ) && $meta['last_name'][0] != '' ) ? $meta['last_name'][0] : '';

					$lineData = array( $first_name, $last_name, $email, $role[0] );
					fputcsv( $out, $lineData, $delimiter );
				}

				header( "Content-type: application/force-download" );
				header( 'Content-Disposition: inline; filename="' . $filename . '";' );

				fpassthru( $out );
			}
			exit;
		}
	}
}

if ( class_exists( 'ExportUsers' ) ) {
	$exportusers = new ExportUsers();
	$exportusers->register();
}

register_activation_hook( __FILE__, array( $exportusers, 'activation' ) );
register_deactivation_hook( __FILE__, array( $exportusers, 'deactivation' ) );