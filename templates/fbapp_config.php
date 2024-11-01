<div class="wrap">
	<h1>Wordpress 2 Step Facebook Authentication: App Setting</h1>
	<form method="post" action="options.php">
		<?php if ( isset( $_GET['settings-updated'] ) ) {			 
			echo "<div class='updated'><p>Facebook configuration details updated successfully.</p></div>";
		} ?>
		<?php settings_fields( 'wp2sfba-fb-authentication-settings-group' ); 
			  $myplug_options = get_option('wp2sfba_skip_fb_auth');	
			  
			  $arr_wp2sfba_role = get_option('wp2sfba_role'); //
			  
		?>
		<?php do_settings_sections( 'wp2sfba-fb-authentication-settings-group' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row" style="width:20%">Facebook App ID</th>
				<td><input type="text" name="wp2sfba_appid" maxlength="45" size="45" value="<?php echo esc_attr( get_option('wp2sfba_appid') ); ?>" /></td>
			</tr>  
			<tr valign="top">
				<th scope="row">Facebook App Secret Key</th>
				<td><input type="password" name="wp2sfba_appsecretkey" maxlength="45"  size="45" value="<?php echo esc_attr( get_option('wp2sfba_appsecretkey') ); ?>" /></td>
			</tr>	
			<tr valign="top">
				<th scope="row">Apply to</th>
				<td>
					<?php global $wp_roles; ?> 
					<select name="wp2sfba_role[]" multiple="multiple">
					<?php foreach ( $wp_roles->roles as $key=>$value ): ?>
					<option value="<?php echo $key; ?>" <?php if(in_array($key,$arr_wp2sfba_role)) { echo ' selected'; }?>><?php echo $value['name']; ?></option>
					<?php endforeach; ?>
					</select>
					<div style="text-align:bottom;">(Ctrl + Click to select multiple options)</div>
				</td>
			</tr>					
			<tr valign="top">
				<th scope="row">Skip Facebook Authentication Link</th>
				<td> 
				<input type="radio" name="wp2sfba_skip_fb_auth[radio1]" value="y" <?php checked('y', $myplug_options['radio1']); ?> /> Yes &nbsp;&nbsp;&nbsp;
				<input type="radio" name="wp2sfba_skip_fb_auth[radio1]" value="n" <?php checked('n', $myplug_options['radio1']); ?> /> No
				</td>
			</tr>		
		</table>		
		<?php submit_button(); ?>
	</form>
	</div>