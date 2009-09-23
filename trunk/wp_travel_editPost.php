<div id="weatherlocationdiv" class="postbox if-js-open">
    <h3>Weather</h3>
    <div class="inside">
	<input type="hidden" name="wp_travel_noncename" id="wp_travel_noncename" value="<?php echo wp_create_nonce(plugin_basename(str_replace('wp_travel_editPost.php', 'wp_travel.php', __FILE__)));?>" />
	<table cellpadding="3" cellspacing="5" class="form-table">
	    <tr>
		<th scope="row"><?php _e('Location','wp-traveller'); ?><br /><span style="font-weight:normal;"><a href="#" onclick="updateWeather(); return false;"><?php _e('Fetch weather','wp-traveller'); ?></a></span></th>
		<td><input name="wp_travel_location" type="text" size="25" id="wp_travel_location" value="<?php echo $this->wp_travel_location; ?>" /></td>
	    </tr>
	    <tr class="wtcollapse">
		<th scope="row"><?php _e('Temperature','wp-traveller'); ?> <span style="font-weight:normal;"><?php echo $temp; ?></span></th>
		<td><input name="wp_travel_temperature" type="text" size="25" id="wp_travel_temperature" value="<?php echo $this->wp_travel_temperature; ?>" />
	    </tr>
	    <tr class="wtcollapse">
		<th scope="row"><?php _e('Humidity','wp-traveller'); ?> <span style="font-weight:normal;">(&#37;)</span></th>
		<td><input name="wp_travel_humidity" type="text" size="25" id="wp_travel_humidity" value="<?php echo $this->wp_travel_humidity; ?>" /></td>
	    </tr>
	    <tr class="wtcollapse">
		<th scope="row"><?php _e('Dew point','wp-traveller'); ?> <span style="font-weight:normal;"><?php echo $temp; ?></span></th>
		<td><input name="wp_travel_dew" type="text" size="25" id="wp_travel_dew" value="<?php echo $this->wp_travel_dew; ?>" /></td>
	    </tr>
	    <tr class="wtcollapse">
		<th scope="row"><?php _e('Wind speed','wp-traveller'); ?> <span style="font-weight:normal;">(km/h)</span></th>
		<td><input name="wp_travel_speed" type="text" size="25" id="wp_travel_speed" value="<?php echo $this->wp_travel_speed; ?>" /></td>
	    </tr>
	    <tr class="wtcollapse">
		<th scope="row"><?php _e('Wind direction','wp-traveller'); ?> <span style="font-weight:normal;">(&deg;)</span></th>
		<td><input name="wp_travel_direction" type="text" size="25" id="wp_travel_direction" value="<?php echo $this->wp_travel_direction; ?>" /></td>
	    </tr>
	    <tr class="wtcollapse">
		<th scope="row"><?php _e('Cloud cover','wp-traveller'); ?></th>
		<td><input name="wp_travel_clouds" type="text" size="25" id="wp_travel_clouds" value="<?php echo $this->wp_travel_clouds; ?>" /></td>
	    </tr>
	</table>
    </div>
</div>