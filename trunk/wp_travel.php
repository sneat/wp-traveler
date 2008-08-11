<?php
/*
Plugin Name: Weather Traveler
Plugin URI: http://www.mcmillan.id.au
Description: Gets weather information from www.geonames.org based on lat and long taken from wp-geo plugin (http://www.benhuson.co.uk/wordpress-plugins/wp-geo/)
Author: Blair McMillan
Version: 1.0
Author URI: http://www.mcmillan.id.au/
*/

add_action('save_post', 'save_post');
add_action('edit_form_advanced', 'travel_dbx_post_advanced');
add_filter('the_content', 'append_travel_weather_info');
add_action('wp_head', 'weather_wp_head');
add_action('admin_head', 'weather_admin_head');
add_action('wp_ajax_updateWeather', 'updateWeather' );

/**
 * Hook: wp_head
 */
function weather_wp_head(){
		
		?>
		<!--	wp-travel css -->
			<style type="text/css">
				.weather {color:#999999;margin:1em 0;font-size:10px;}
			</style>
		<?php
	}

/**
 * Weather feed
 */
function getWeather($lat,$long) {
	$url = "http://ws.geonames.org/findNearByWeather?lat=$lat&lng=$long";
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
						<th scope="row">Temperature <span style="font-weight:normal;">(&deg;C)</span>,<br>Humidity <span style="font-weight:normal;">(&#37;)</span></th>
						<td><input name="wp_travel_temperature" type="text" size="25" id="wp_travel_temperature" value="' . $temperature . '" /> <input name="wp_travel_humidity" type="text" size="25" id="wp_travel_humidity" value="' . $humidity . '" /></td>
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
	global $post;
	$location = get_post_meta($post->ID, '_wp_travel_location', true);
	$temperature = get_post_meta($post->ID, '_wp_travel_temperature', true);
	$humidity = get_post_meta($post->ID, '_wp_travel_humidity', true);
	if ($temperature) $temperature = 'Temperature: ' . $temperature . '&deg;C';
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
		<!--	wp-travel css -->
			<style type="text/css">
				span.weather_response {margin-left:3em;display:block;font-weight:bold;}
			</style>
		<!--	wp-travel -->
		<script type="text/javascript">
		//<![CDATA[
		function makeNotice(type,message){
			if (type=='notice'){wclass='updated';}else{wclass='error';}
			if (type=='error'){
				if(jQuery("#weathernotice").length>0){jQuery("#weathernotice").remove();}
			}
			if(jQuery("#weather" + type).length>0){
				jQuery("#weather" + type).replaceWith('<div id="weather' + type + '" class="' + wclass + '">' + message + '</div>');
			}else{
				jQuery("#weatherlocationdiv > div.inside").prepend('<div id="weather' + type + '" class="' + wclass + '">' + message + '</div>');
			}
		}
		
		function updateWeather(){
			if (jQuery("#wpgeolocationdiv").length<1){
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
	$url = "http://ws.geonames.org/findNearByWeather?lat=$lat&lng=$long";
	
	if(function_exists('curl_init')) {
		$c   = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($c, CURLOPT_TIMEOUT, 5);
		$xml = curl_exec($c);
		curl_close($c);
	}else{
		$fp = fopen($url,"r");
		while (!feof ($fp))
			$xml .= fgets($fp, 4096);
		fclose ($fp);
	}

	// Send request to elevation server 
	if(!$xml) {
	die('makeNotice(\'error\',\'<p>Error looking up weather details. Please try again later.</p>\');');
	} 
	
	// Fire up the built-in XML parser
	$parser = xml_parser_create(  ); 
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);

	// Set tag names and values
	xml_parse_into_struct($parser,$xml,$values,$tags); 

	// Close down XML parser
	xml_parser_free($parser);

	$xml = new SimpleXmlElement($xml);

	$status = $xml->status['message'];
	$location = $xml->observation[0]->stationName;
	$temp = $xml->observation[0]->temperature;
	$humidity = $xml->observation[0]->humidity;
	
	if($status) die("makeNotice('error','<p>No weather details found for the location specified.</p><p>Received the following response:<span class=\"weather_response\">".ucfirst($status).".</span></p>');");
	else die("responseReceived('$location',$temp,$humidity);");

}

?>