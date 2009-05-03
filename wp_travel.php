<?php
/*
Plugin Name: Weather Traveller
Plugin URI: http://www.mcmillan.id.au
Description: Gets weather information from www.geonames.org based on lat and long taken from wp-geo plugin (http://www.wpgeo.com/)
Author: Blair McMillan
Version: 1.2.1
Author URI: http://www.mcmillan.id.au/

Changelog
1.1			Added Options.
1.2			Added Fahrenheit support.
1.2.1		Updated to support WP-Geo changing div names
*/

register_activation_hook(__FILE__, 'register_activation');
add_action('save_post', 'save_post');
add_action('edit_form_advanced', 'travel_dbx_post_advanced');
add_filter('the_content', 'append_travel_weather_info');
add_action('wp_head', 'weather_wp_head');
add_action('admin_menu', 'weather_admin_menu');
add_action('admin_head', 'weather_admin_head');
add_action('wp_ajax_updateWeather', 'updateWeather' );

/**
 * Register Activation
 */
function register_activation()
{
	$options = array(
		'colour' 		=> '#999999', 
		'margin' 		=> '1em 0',
		'font-size' 	=> '10px',
		'unit' 			=> 'C',
		'temperature' 	=> true,
		'humidity' 		=> true,
		'dew' 			=> false,
		'windSpeed'		=> false,
		'windDirection'	=> false,
		'clouds'		=> false,
	);
	add_option('wp_travel_options', $options);
	$wp_travel_options = get_option('wp_travel_options');
	foreach ($options as $key => $val)
	{
		if (!isset($wp_travel_options[$key]))
		{
			$wp_travel_options[$key] = $options[$key];
		}
	}
	update_option('wp_travel_options', $wp_travel_options);
}

/**
 * Hook: wp_head
 */
function weather_wp_head(){
				$wp_travel_options = get_option('wp_travel_options');
                echo '
                <!--    wp-travel css -->
					<style type="text/css">
							.weather {color:' . $wp_travel_options['colour'] . ';margin:' . $wp_travel_options['margin'] . ';font-size:' . $wp_travel_options['font-size'] . ';}
					</style>';
        }

/**
 * Weather feed
 */
function getWeather($lat,$long) {
        $url = "http://ws.geonames.org/findNearByWeatherXML?lat=$lat&lng=$long";
        $c   = curl_init($url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $xml = curl_exec($c);
        curl_close($c);
        return $xml;
}

/**
 * Hook: dbx_post_advanced
 */
function travel_dbx_post_advanced()
{
        global $post_ID;
       
        // Get post weather details
        $location = get_post_meta($post_ID, '_wp_travel_location', true);
        $temperature = get_post_meta($post_ID, '_wp_travel_temperature', true);
        $humidity = get_post_meta($post_ID, '_wp_travel_humidity', true);
       
        // Output
        echo travelForm($location, $temperature, $humidity);
       
}

/**
 * Travel Form
 */
function travelForm($location = null, $temperature = null, $humidity = null)
{
	$wp_travel_options = get_option('wp_travel_options');
	$temp='(&deg;C)';
	if ($wp_travel_options['unit']=='F'){
		$temp='(&deg;C)<br />(Will be converted to &deg;F)';
	}
	// Output
	$edit_html = '
			<div id="weatherlocationdiv" class="postbox if-js-open">
					<h3>Weather</h3>
					<div class="inside">
							<table cellpadding="3" cellspacing="5" class="form-table">
									<tr>
											<th scope="row">Location<br /><span style="font-weight:normal;"><a href="#" onclick="updateWeather(); return false;">Fetch weather</a></span></th>
											<td><input name="wp_travel_location" type="text" size="25" id="wp_travel_location" value="' . $location . '" /></td>
									</tr>
									<tr>
											<th scope="row">Temperature <span style="font-weight:normal;">' . $temp . '</span></th>
											<td><input name="wp_travel_temperature" type="text" size="25" id="wp_travel_temperature" value="' . $temperature . '" />
									</tr>
									<tr>
											<th scope="row">Humidity <span style="font-weight:normal;">(&#37;)</span></th>
											<td><input name="wp_travel_humidity" type="text" size="25" id="wp_travel_humidity" value="' . $humidity . '" /></td>
									</tr>
							</table>
					</div>
			</div>';
   
	return $edit_html;
}

/**
 * Hook: save_post
 */
function save_post($post_id)
{
        delete_post_meta($post_id, '_wp_travel_location');
        delete_post_meta($post_id, '_wp_travel_temperature');
        delete_post_meta($post_id, '_wp_travel_humidity');
        if (isset($_POST['wp_travel_location']) && isset($_POST['wp_travel_temperature']) && isset($_POST['wp_travel_humidity']))
        {
                if (is_numeric($_POST['wp_travel_temperature']) && is_numeric($_POST['wp_travel_humidity']))
                {
                        add_post_meta($post_id, '_wp_travel_location', $_POST['wp_travel_location']);
                        add_post_meta($post_id, '_wp_travel_temperature', $_POST['wp_travel_temperature']);
                        add_post_meta($post_id, '_wp_travel_humidity', $_POST['wp_travel_humidity']);
                }
        }
}

/**
 * Hook: append_travel_weather_info
 */
function append_travel_weather_info($content = '')
{
		$wp_travel_options = get_option('wp_travel_options');
        global $post;
        $location = get_post_meta($post->ID, '_wp_travel_location', true);
        $temperature = (int) get_post_meta($post->ID, '_wp_travel_temperature', true);
        $humidity = (int) get_post_meta($post->ID, '_wp_travel_humidity', true);
        if ($temperature) {
			if ($wp_travel_options['unit']=='F'){
				$temperature=round(($temperature*1.8)+32,2);
			}
			$temperature = 'Temperature: ' . $temperature . '&deg;' . $wp_travel_options['unit'];
		}
        if ($humidity) $humidity = 'Humidity: ' . $humidity . '&#37;';
        if ($location || $temperature || $humidity){
                $output = '<div class="weather">';
                $output .= '<p style="margin:0">-- Weather When Posted --</p>';
                if ($location) $output .= '<p style="margin:0 0 0 1em;">Location: '. $location . '</p>';
                if ($temperature && $humidity) $sep = ', ';
                if ($temperature || $humidity) $output .= '<p style="margin:0 0 0 1em;">' . $temperature . $sep . $humidity . '</p>';
                $output .= '</div>';
               
                $content .= $output;
        }
        return $content;
}

/**
 * Hook: admin_head
 */
function weather_admin_head(){
                wp_print_scripts( array( 'sack' ));
                ?>
                <!--    wp-travel css -->
                        <style type="text/css">
                                span.weather_response {margin-left:3em;display:block;font-weight:bold;}
                        </style>
                <!--    wp-travel -->
                <script type="text/javascript">
                //<![CDATA[
                function makeNotice(type,message){
                        if (type=='notice'){wclass='updated';}else{wclass='error';}
                        if (type=='error'){
                                if(jQuery("#weathernotice").length>0){jQuery("#weathernotice").remove();}
                        }
                        if (type=='notice'){
                                if(jQuery("#weathererror").length>0){jQuery("#weathererror").remove();}
                        }
                        if(jQuery("#weather" + type).length>0){
                                jQuery("#weather" + type).replaceWith('<div id="weather' + type + '" class="' + wclass + '">' + message + '</div>');
                        }else{
                                jQuery("#weatherlocationdiv > div.inside").prepend('<div id="weather' + type + '" class="' + wclass + '">' + message + '</div>');
                        }
                }
               
                function updateWeather(){
                        if (jQuery("#wpgeo_location").length<1){
                                makeNotice('error','<p>Weather Traveler requires <a href="http://www.benhuson.co.uk/wordpress-plugins/wp-geo/" target="_blank">WP Geo Location</a> to be installed. Please install this before trying to use Traveller weather.</p>');
                        }else {
                                var lat = jQuery("#wp_geo_latitude").val();
                                var lon = jQuery("#wp_geo_longitude").val();
                                if (lat=='' || lon=='') {
                                        makeNotice('notice','<p>Please set a <a href="#" onclick="jQuery(\'#wp_geo_search\').focus(); return false;">location</a> in WP Geo Location first.</p>');
                                }else {
                                        makeNotice('notice','<p>Please wait...</p><p>Fetching weather details...</p>');
                                        var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php" );    

                                        mysack.execute = 1;
                                        mysack.method = 'POST';
                                        mysack.setVar( "action", "updateWeather" );
                                        mysack.setVar( "latitude", lat );
                                        mysack.setVar( "longitude", lon );
                                        mysack.encVar( "cookie", document.cookie, false );
                                        mysack.onError = function() {makeNotice('error','<p>Error looking up weather details. Please try again later.</p>');};
                                        mysack.runAJAX();
                                       
                                }
                        }
                };
               
                function responseReceived(loc,temp,hum){
                        document.getElementById("wp_travel_location").value=loc;
                        document.getElementById("wp_travel_temperature").value=temp;
                        document.getElementById("wp_travel_humidity").value=hum;
                        makeNotice('notice','<p><span style="float:right;"><a href="#" onclick="jQuery(\'#weathernotice\').remove(); return false;">Dismiss</a></span>Weather details fetched. You can now update the <a href="#" onclick="jQuery(\'#wp_travel_location\').focus(); return false;">Location name</a>.</p><p>Weather details will be saved with your post.</p>');
                }
                //]]>
                </script>
                <?php
        }

/**
 * Update Weather Post Form
 */
function updateWeather(){
        $lat = $_POST['latitude'];
        $long = $_POST['longitude'];
        $url = "http://ws.geonames.org/findNearByWeatherXML?lat=$lat&lng=$long";
       
        if(function_exists('curl_init')) {
                $c   = curl_init($url);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($c, CURLOPT_TIMEOUT, 5);
                $data = curl_exec($c);
                curl_close($c);
        }elseif(function_exists('fopen')) {
                $fp = fopen($url,"r");
                while (!feof ($fp))
                        $data .= fgets($fp, 4096);
                fclose ($fp);
        }else {
			die('makeNotice(\'error\',\'<p>Error looking up weather details. Your server does not support CURL or fopen.</p>\');');
		}


        // Send request to elevation server
        if(!$data) {
			die('makeNotice(\'error\',\'<p>Error looking up weather details. Please try again later.</p>\');');
        }
       
        // Fire up the built-in XML parser
        if(function_exists('xml_parser_create')) {
			$parser = xml_parser_create(  );
		}else{
			die('makeNotice(\'error\',\'<p>Error parsing weather details. Please ensure that your PHP install includes the XML Parser.</p>\');');
		}
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);

        // Set tag names and values
        xml_parse_into_struct($parser,$data,$values,$tags);

        // Close down XML parser
        xml_parser_free($parser);

        if(function_exists('simplexml_load_file')) {
			$xml = new SimpleXmlElement($data);
			
	        $status = $xml->status['message'];
	        $location = $xml->observation[0]->stationName;
	        $temp = $xml->observation[0]->temperature;
	        $humidity = $xml->observation[0]->humidity;
		}else{
			require_once(dirname(__FILE__).'/xml/parser_php4.php');
			$parser = new XMLParser($data);
			$parser->Parse();
			
	        $status = $parser->document->status[0]->tagAttrs['message'];
	        $location = $parser->document->observation[0]->stationName[0]->tagData;
	        $temp = $parser->document->observation[0]->temperature[0]->tagData;
	        $humidity = $parser->document->observation[0]->humidity[0]->tagData;
        }

        if($status) die("makeNotice('error','<p>No weather details found for the location specified.</p><p>Received the following response:<span class=\"weather_response\">".ucfirst($status).".</span></p>');");
        elseif ($location && $temp && $humidity){
			die("responseReceived('$location',$temp,$humidity);");
		}
		else die('makeNotice(\'error\',\'<p>Something went horribly wrong. Please try again later.</p><p>If the problem keeps happening, please <a href="http://code.google.com/p/wp-traveler/issues/list" target="_blank">report the issue</a>.</p>\');');

}
/**
 * Options Radio
 */
function options_radio($name, $id, $val, $checked, $disabled=false)
{
	$is_checked = '';
	$is_disabled = '';
	if ($val == $checked)
	{
		$is_checked = 'checked="checked" ';
	}
	if ($disabled===true){$is_disabled = 'disabled="disabled" ';}
	return '<input name="' . $name . '" type="radio" id="' . $id . '" value="' . $val . '" ' . $is_checked . '/><label for="' . $id . '">' . $id . ' (&deg;' . $val . ')</label>';
}

/**
 * Options Page
 */
function options_page()
{
	
	$wp_travel_options = get_option('wp_travel_options');
	$temp=22;
	if ($wp_travel_options['unit']=='F'){
		$temp=round(($temp*1.8)+32,2);
	}
	
	// Process option updates
	if (isset($_POST['action']) && $_POST['action'] == 'update')
	{
		$wp_travel_options['colour'] = $_POST['colour'];
		$wp_travel_options['margin'] = $_POST['margin'];
		$wp_travel_options['font-size'] = $_POST['font-size'];
		$wp_travel_options['unit'] = $_POST['unit'];
		
		update_option('wp_travel_options', $wp_travel_options);
		echo '<div class="updated"><p>Options updated</p></div>';
		$temp=22;
		if ($wp_travel_options['unit']=='F'){
			$temp=round(($temp*1.8)+32,2);
		}
	}

	// Create form elements
	
	// Write the form
	echo '
	<div class="wrap">
		<h2>Weather Traveller Options</h2>
		<form method="post">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Text Colour<br /><span style="font-weight:normal;font-size:10px;">(Hex or common name)</span></th>
					<td><input name="colour" type="text" id="colour" value="' . $wp_travel_options['colour'] . '" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Margin</th>
					<td><input name="margin" type="text" id="margin" value="' . $wp_travel_options['margin'] . '" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Font-size</th>
					<td><input name="font-size" type="text" id="font-size" value="' . $wp_travel_options['font-size'] . '" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Temperature Units</th>
					<td>' . options_radio('unit','Metric','C',$wp_travel_options['unit']) . ' ' . options_radio('unit','Imperial','F',$wp_travel_options['unit'],true) . '</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="Submit" value="Save Changes" />
				<input type="hidden" name="action" value="update" />
			</p>
		</form>
		<h2>Demonstration</h2>
		<p>Below is an example of the output generated with the options specified above. This shows what is appended to the bottom of your posts.</p>
		<div style="color:' . $wp_travel_options['colour'] . ';margin:' . $wp_travel_options['margin'] . ';font-size:' . $wp_travel_options['font-size'] . ';"><p style="margin: 0pt;">-- Weather When Posted --</p><p style="margin: 0pt 0pt 0pt 1em;">Location: Brisbane, Australia</p><p style="margin: 0pt 0pt 0pt 1em;">Temperature: ' . $temp . '&deg;' . $wp_travel_options['unit'] . ', Humidity: 33&#37;</p></div>
	</div>';
}

/**
 * Hook: admin_menu
 */
function weather_admin_menu()
{
	if (function_exists('add_options_page'))
	{
		add_options_page('Weather Traveller Options', 'Weather Traveller', 8, __FILE__, 'options_page');
	}
}
?>