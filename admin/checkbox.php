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
		<input type="checkbox" id="nickname" value="Nickname" name="export_settings_options[checkbox_element][]" <?php checked( in_array( 'Nickname', $checkbox_element ), 1 ); ?> />
		<label for="nickname"><?= esc_html__( 'Nickname', 'export-users' ); ?></label>
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
		<input type="checkbox" id="website" value="Website" name="export_settings_options[checkbox_element][]" <?php checked( in_array( 'Website', $checkbox_element ), 1 ); ?> />
		<label for="website"><?= esc_html__( 'Website', 'export-users' ); ?></label>
	</div>
	<div>
		<input type="checkbox" id="registered_date" value="Registered date" name="export_settings_options[checkbox_element][]" <?php checked( in_array( 'Registered date', $checkbox_element ), 1 ); ?> />
		<label for="registered_date"><?= esc_html__( 'Registered date', 'export-users' ); ?></label>
	</div>
</div>