<?php
// create custom plugin settings menu
add_action('admin_menu', 'wptrack_create_menu');

function wptrack_create_menu() {

	//create new top-level menu
  add_submenu_page(
    '/edit.php?post_type=wptrack_tracking',
    __('WPTrack Settings', 'wptrack_tracking' ),
    __('WPTrack Settings', 'wptrack_tracking'),
    'manage_options',
    'wptrack-options',
    'wptrack_settings_page'
  );

	//call register settings function
	add_action( 'admin_init', 'register_mysettings' );
}


function register_mysettings() {
	//register our settings
	register_setting( 'wptrack-settings-group', 'dont_use_image_extension' );
}

function wptrack_settings_page() {
  $dont_use_image_extensions = get_option('dont_use_image_extension');
?>
<div class="wrap">
<h2>WP Track Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'wptrack-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Dont Use .png image extension?</th>
        <td>
          <input type="checkbox"
            name="dont_use_image_extension"
            value="yes" 
            <?php if(isset($dont_use_image_extensions)) {checked($dont_use_image_extensions, 'yes');} ?>
          />
        <p>Note: This should probably be checked, but depending on how your webhost behaves you may need to uncheck this.</p>
        </td>
        </tr>
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php } ?>
