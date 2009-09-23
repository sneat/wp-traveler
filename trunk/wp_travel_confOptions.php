<div class="wrap">
    <div id="icon-options-general" class="icon32"></div>
    <h2><?php echo __('Weather Traveller Options', 'wp-traveller'); ?></h2>

    <form action="options-general.php?page=wp-traveller/wp_travel.php" method="post">
	<div id="poststuff" class="ui-sortable">
	    <div class="postbox opened">
		<h3><?php echo __('General', 'wp-traveller'); ?></h3>
		<div class="inside">
		    <table class="form-table">
			<tr>
			    <th><?php echo __('Temperature Units', 'wp-traveller'); ?></th>
			</tr>
			<tr>
			    <td>
				<?php echo $this->wtCreateRadio('wt_unit', 'metric', 'C', __('Metric (&deg;C)', 'wp-traveller')); ?>
				<?php echo $this->wtCreateRadio('wt_unit', 'imperial', 'F', __('Imperial (&deg;F)', 'wp-traveller')); ?>
			    </td>
			</tr>
		    </table>
		</div>
	    </div>
	</div>

	<div id="poststuff" class="ui-sortable">
	    <div class="postbox opened">
		<h3><?php _e('Template', 'wp-traveller'); ?></h3>
		<div class="inside">
		    <table class="form-table">
			<?php
			    if ($this->wt_template == '')
				{ ?>
				    <tr>
					<td colspan="2">
					    <p class="notice">
						<?php _e('The default template is currently being used. You may customise it to display however you wish using the variables listed below.' ,'wp-traveller'); ?>
					    </p>
					</td>
				    </tr>
				<?php } ?>
			<tr>
			    <td colspan="2"><?php _e('This template gets appended to the bottom of posts that have associated Weather Traveller information. You can use it to style the added content however you wish.', 'wp-traveller'); ?></td>
			</tr>
			<tr>
			    <td colspan="2">
				<textarea name="wptraveller_template" cols="80" rows="10"><?php echo ($this->wt_template) ? $this->wt_template : $this->wt_default_template ?></textarea>
			    </td>
			</tr>
			<tr>
			    <th colspan="2"><?php _e('Variables that will be replaced in the template:', 'wp-traveller'); ?></th>
			</tr>
			<tr>
			    <th>%LOCATION%</th>
			    <td><?php _e('The location specified in WP-Geo (eg. London)', 'wp-traveller'); ?></td>
			</tr>
			<tr>
			    <th>%TEMP%</th>
			    <td><?php _e('The current temperature (eg. 16 &deg;C)', 'wp-traveller'); ?></td>
			</tr>
			<tr>
			    <th>%HUMIDITY%</th>
			    <td><?php _e('The current humidity (eg. 60%)', 'wp-traveller'); ?></td>
			</tr>
			<tr>
			    <th>%DEW%</th>
			    <td><?php _e('The current dew point (eg. 11 &deg;C)', 'wp-traveller'); ?></td>
			</tr>
			<tr>
			    <th>%SPEED%</th>
			    <td><?php _e('The current wind speed (eg. 4km/hr)', 'wp-traveller'); ?></td>
			</tr>
			<tr>
			    <th>%DIRECTION%</th>
			    <td><?php _e('The current wind direction (eg. 105&deg;)', 'wp-traveller'); ?></td>
			</tr>
			<tr>
			    <th>%CLOUDS%</th>
			    <td><?php _e('The current cloud cover (eg. partly cloudy)', 'wp-traveller'); ?></td>
			</tr>
			<tr>
			    <td colspan="2"><?php _e('Please note that any empty variables will show n/a - see the example below where Humidity is left empty.', 'wp-traveller'); ?></td>
			</tr>
		    </table>
		</div>
	    </div>
	</div>

	<div id="poststuff" class="ui-sortable">
	    <div class="postbox opened">
		<h3><?php echo __('Static Example', 'wp-traveller'); ?></h3>
		<div class="inside">
		    <div style="color:#999999;margin:1em 0;font-size:0.8em;">
			<p style="margin:0;">-- Weather in Example when posted --</p>
			<p style="margin: 0 0 0 1em;">Temperature: 11 &deg;C, Humidity: n/a</p>
		    </div>
		</div>
	    </div>
	</div>
	<p class="submit"><input type="submit" class='button-primary' name="submit" value="<?php _e('Save settings', 'wp-traveller'); ?>" /></p>
    </form>
</div>