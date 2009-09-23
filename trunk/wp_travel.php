<?php
/*
Plugin Name: Weather Traveller
Plugin URI: http://www.mcmillan.id.au
Description: Gets weather information from www.geonames.org based on lat and long taken from wp-geo plugin (http://www.benhuson.co.uk/wordpress-plugins/wp-geo/)
Author: Blair McMillan
Version: 2.0
Author URI: http://www.mcmillan.id.au/

Changelog
1.1		Added Options.
1.2		Added Fahrenheit support.
1.2.1		Updated to support WP-Geo changing div names.
2.0		Complete code re-write.
		    Added ability to use more of the data returned from geonames.org
		    Added internationalisation support.
		    Added template support.
*/

if (!class_exists("WeatherTraveller")) {
    class WeatherTraveller {
	/**
	 * The temperature unit.
	 * @var string
	 */
	var $wt_unit;

	/**
	 * The template to append to the post.
	 * @var string
	 */
	var $wt_template;

	/**
	 * The default template to append to the post.
	 * @var string
	 */
	var $wt_default_template;

	/**
	 * The location from where the weather was fetched.
	 * @var string
	 */
	var $wp_travel_location;

	/**
	 * The temperature.
	 * @var int
	 */
	var $wp_travel_temperature;

	/**
	 * The humidity.
	 * @var int
	 */
	var $wp_travel_humidity;

	/**
	 * The dew point.
	 * @var int
	 */
	var $wp_travel_dew;

	/**
	 * The wind speed in km/hr.
	 * @var int
	 */
	var $wp_travel_speed;

	/**
	 * The wind direction in degrees.
	 * @var int
	 */
	var $wp_travel_direction;

	/**
	 * The cloud cover.
	 * @var string
	 */
	var $wp_travel_clouds;

	/**
	 * Start the Weather Traveller Plugin.
	 */
	function WeatherTraveller()
	{
	    global $wt;

	    // Load language file
	    if (function_exists('load_plugin_textdomain'))
	    {
		$plugin_dir = basename(dirname(__FILE__));
		load_plugin_textdomain('wp-traveller', 'wp-content/plugins/' . $plugin_dir, $plugin_dir);
	    }

	    // Add wordpress actions
	    add_action('save_post', array(&$wt, 'wtSavePost'));
	    add_action('edit_form_advanced', array(&$wt, 'wtEditForm'));
	    add_action('admin_menu', array(&$wt, 'wtAdminMenu'));
	    add_action('admin_head', array(&$wt, 'wtAdminHead'));
	    add_action('wp_ajax_updateWeather', array(&$wt, 'wtUpdateWeather'));

	    // Tell wordpress what to do when plugin is activated and deactivated
	    register_activation_hook(__FILE__, array(&$wt, 'activate'));
	    register_deactivation_hook(__FILE__, array(&$wt, 'deactivate'));

	    // Load options
	    $this->loadOptions();
	}

	/**
	 * Add Weather Traveller Options when plugin is activated
	 */
	function activate()
	{
	    $options = array(
		'wt_unit'	    => 'C',
		'wt_template'   => '<div style="color:#999999;margin:1em 0;font-size:0.8em;">
	<p style="margin:0;">-- Weather in %LOCATION% when posted --</p>
	<p style="margin: 0 0 0 1em;">Temperature: %TEMP%, Humidity: %HUMIDITY%</p>
    </div>',
		'wt_default_template'   => '<div style="color:#999999;margin:1em 0;font-size:0.8em;">
	<p style="margin:0;">-- Weather in %LOCATION% when posted --</p>
	<p style="margin: 0 0 0 1em;">Temperature: %TEMP%, Humidity: %HUMIDITY%</p>
    </div>',
	    );

	    add_option('wp_travel_options', $options);
	}

	/**
	 * Delete Weather Traveller Options when plugin is deactivated
	 */
	function deactivate()
	{
	    // delete_option('wp_travel_options');
	}

	/**
	 * Load Weather Traveller Options
	 */
	function loadOptions()
	{
	    $options = get_option('wp_travel_options');

	    $this->wt_unit		= $options['wt_unit'];
	    $this->wt_template		= $options['wt_template'];
	    $this->wt_default_template  = $options['wt_default_template'];
	}

	/**
	 * Update Weather Traveller Options
	 *
	 * @return boolean
	 */
	function updateOptions()
	{
	    $options = array(
		'wt_unit'		=> $this->wt_unit,
		'wt_template'		=> $this->wt_template,
		'wt_default_template'   => $this->wt_default_template,
	    );

	    return update_option('wp_travel_options', $options);
	}

	/**
	 * Add Weather Traveller Options link.
	 */
	function wtAdminMenu()
	{
	    global $wt;

	    add_options_page('Weather Traveller Options', 'Weather Traveller', 8, __FILE__, array(&$wt, 'wtConf'));
	}

	/**
	 * Add required JavaScript and CSS to admin pages.
	 */
	function wtAdminHead()
	{
	    wp_print_scripts(array('sack'));

	    include('wp_travel_adminHead.php');
	}

	/**
	 * Configuration settings for Weather Traveller.
	 */
	function wtConf()
	{
	    if (!current_user_can('manage_options'))
	    {
		wp_die(__('Sorry, but you do not have permissions to change settings.', 'wp-traveller'));
	    }

	    if (isset($_POST['submit']))
	    {
		    $this->wt_unit	= (in_array($_POST['wt_unit'], array('C','F'))) ? $_POST['wt_unit'] : 'C';
		    $this->wt_template	= (string) stripslashes($_POST['wptraveller_template']);

		    if ($this->updateOptions())
		    {
			echo '<div id="message" class="updated fade"><p><strong>'. __('Weather Traveller settings saved.', 'wp-traveller') .'</strong></p></div>';
		    }
	    }

	    include('wp_travel_confOptions.php');
	}

	/**
	 * Shows the weather information when a post is being edited.
	 *
	 * @global int $post_ID
	 */
	function wtEditForm()
	{
	    global $post_ID, $wt;
	    $wt->loadOptions();
	    $wt->wtGetPostMeta($post_ID);
	    $temp='(&deg;C)';
	    if ($this->wt_unit=='F'){
		$temp='(&deg;C)<br />(Will be converted to &deg;F)';
	    }

	    include('wp_travel_editPost.php');
	    
	    $wt->wtResetValues();
	}

	/**
	 * Get the weather information for a given latitude and longitude. Returns
	 * the data in a format suitable for Wordpress' AJAX script.
	 */
	function wtUpdateWeather()
	{
	    if ($_POST['latitude'] && $_POST['longitude'])
	    {
		$lat = urlencode($_POST['latitude']);
		$long = urlencode($_POST['longitude']);
		$url = "http://ws.geonames.org/findNearByWeatherXML?lat=$lat&lng=$long";
	    }
	    else
	    {
		die('makeNotice(\'error\',\'<p>Error looking up weather details. Please confirm that a latitude and longitude have been set in WP Geo Traveller.</p>\');');
	    }

	    if(function_exists('curl_init'))
	    {
		$c   = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($c, CURLOPT_TIMEOUT, 5);
		$data = curl_exec($c);
		curl_close($c);
	    }
	    elseif(function_exists('fopen'))
	    {
		$fp = fopen($url,"r");
		while (!feof ($fp))
		{
		    $data .= fgets($fp, 4096);
		}
		fclose ($fp);
	    }
	    else
	    {
		die('makeNotice(\'error\',\'<p>Error looking up weather details. Your server does not support CURL or fopen. Please contact your host for assistance.</p>\');');
	    }

	    if(!$data)
	    {
		die('makeNotice(\'error\',\'<p>Error looking up weather details. Please try again later.</p>\');');
	    }

	    // Fire up the built-in XML parser
	    if(function_exists('xml_parser_create'))
	    {
		$parser = xml_parser_create(  );
	    }
	    else
	    {
		die('makeNotice(\'error\',\'<p>Error parsing weather details. Please ensure that your PHP install includes the XML Parser.</p>\');');
	    }
	    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);

	    // Set tag names and values
	    xml_parse_into_struct($parser,$data,$values,$tags);

	    // Close down XML parser
	    xml_parser_free($parser);

	    if(function_exists('simplexml_load_file'))
	    {
		$xml = new SimpleXmlElement($data);

		$status = $xml->status['message'];
		$location = $xml->observation[0]->stationName;
		$temp = $xml->observation[0]->temperature;
		$humidity = $xml->observation[0]->humidity;
		$dew = $xml->observation[0]->dewPoint;
		$direction = $xml->observation[0]->windDirection;
		$speed = $xml->observation[0]->windSpeed;
		$clouds = $xml->observation[0]->clouds;
	    }
	    else
	    {
		require_once(dirname(__FILE__).'/xml/parser_php4.php');
		$parser = new XMLParser($data);
		$parser->Parse();

		$status = $parser->document->status[0]->tagAttrs['message'];
		$location = $parser->document->observation[0]->stationName[0]->tagData;
		$temp = $parser->document->observation[0]->temperature[0]->tagData;
		$humidity = $parser->document->observation[0]->humidity[0]->tagData;
		$dew = $parser->document->observation[0]->dewPoint[0]->tagData;
		$direction = $parser->document->observation[0]->windDirection[0]->tagData;
		$speed = $parser->document->observation[0]->windSpeed[0]->tagData;
		$clouds = $parser->document->observation[0]->clouds[0]->tagData;
	    }

	    if($status)
	    {
		die("makeNotice('error','<p>No weather details found for the location specified.</p><p>Received the following response:<span class=\"weather_response\">".ucfirst($status).".</span></p>');");
	    }
	    elseif($location && $temp && $humidity && $dew && $direction && $speed && $clouds)
	    {
		die("responseReceived('$location',$temp,$humidity,$dew,$direction,$speed,'$clouds');");
	    }
	    else
	    {
		die('makeNotice(\'error\',\'<p>Something went horribly wrong. Please try again later.</p><p>If the problem keeps happening, please <a href="http://code.google.com/p/wp-traveler/issues/list" target="_blank">report the issue</a>.</p>\');');
	    }

	}

	/**
	 * Displays the weather information at the bottom of the post.
	 *
	 * @global obj $post The wordpress post object
	 * @param string $content The content of the post
	 * @return string
	 */
	function wtAppendWeatherInfo($content = '')
	{
	    global $post, $wt;

	    $wt->loadOptions();
	    $wt->wtGetPostMeta($post->ID);

	    if ($wt->wp_travel_location)
	    {
		$template = $wt->wtReplaceTemplate($wt->wt_template);
	    }
	    $content .= $template;

	    $wt->wtResetValues();
	    return $content;
	}

	/**
	 * Clears any old post meta data and calls wtProcessWeather to save the
	 * new data. Called when a post is saved.
	 *
	 * @param int $post_id
	 */
	function wtSavePost($post_id)
	{
	    // Verify this came from the our screen and with proper authorization,
	    // because save_post can be triggered at other times
	    if (!wp_verify_nonce($_POST['wp_travel_noncename'], plugin_basename(__FILE__)))
	    {
		    return $post_id;
	    }

	    // Authenticate user
	    if ('page' == $_POST['post_type'])
	    {
		    if (!current_user_can('edit_page', $post_id))
			    return $post_id;
	    }
	    else
	    {
		    if (!current_user_can('edit_post', $post_id))
			    return $post_id;
	    }

	    // Find and save the data
	    if (isset($_POST['wp_travel_location']))
	    {
		// Only delete post meta if isset (to avoid deletion in bulk/quick edit mode)
		delete_post_meta($post_id, '_wp_travel_location');
		delete_post_meta($post_id, '_wp_travel_temperature');
		delete_post_meta($post_id, '_wp_travel_humidity');
		delete_post_meta($post_id, '_wp_travel_dew');
		delete_post_meta($post_id, '_wp_travel_speed');
		delete_post_meta($post_id, '_wp_travel_direction');
		delete_post_meta($post_id, '_wp_travel_clouds');

		$temperature = is_numeric($_POST['wp_travel_temperature']) ? (int) $_POST['wp_travel_temperature'] : '';
		$humidity = is_numeric($_POST['wp_travel_humidity']) ? (int) $_POST['wp_travel_humidity'] : '';
		$dew = is_numeric($_POST['wp_travel_dew']) ? (int) $_POST['wp_travel_dew'] : '';
		$speed = is_numeric($_POST['wp_travel_speed']) ? (int) $_POST['wp_travel_speed'] : '';
		$direction = is_numeric($_POST['wp_travel_direction']) ? (int) $_POST['wp_travel_direction'] : '';

		add_post_meta($post_id, '_wp_travel_location', htmlentities($_POST['wp_travel_location']));
		add_post_meta($post_id, '_wp_travel_temperature', $temperature);
		add_post_meta($post_id, '_wp_travel_humidity', $humidity);
		add_post_meta($post_id, '_wp_travel_dew', $dew);
		add_post_meta($post_id, '_wp_travel_speed', $speed);
		add_post_meta($post_id, '_wp_travel_direction', $direction);
		add_post_meta($post_id, '_wp_travel_clouds', htmlentities($_POST['wp_travel_clouds']));
		
		return true;
	    }
	    return false;
	}

	/**
	 * Replaces the template variables in the given text.
	 *
	 * @param string $template
	 * @return string
	 */
	function wtReplaceTemplate($template) {
	    global $wt;

	    // Convert degrees C to degrees F if required
	    if ($this->wt_unit=='F' && is_int($this->wp_travel_temperature))
	    {
		$this->wp_travel_temperature = round(($this->wp_travel_temperature * 1.8) + 32, 2);
	    }

	    // Make sure there are no blank template variables
	    $wt->wtCheckValues();

	    $template = preg_replace('/%LOCATION%/', $this->wp_travel_location, $template);
	    $template = preg_replace('/%TEMP%/', $this->wp_travel_temperature, $template);
	    $template = preg_replace('/%HUMIDITY%/', $this->wp_travel_humidity, $template);
	    $template = preg_replace('/%DEW%/', $this->wp_travel_dew, $template);
	    $template = preg_replace('/%SPEED%/', $this->wp_travel_speed, $template);
	    $template = preg_replace('/%DIRECTION%/', $this->wp_travel_direction, $template);
	    $template = preg_replace('/%CLOUDS%/', $this->wp_travel_clouds, $template);

	    return $template;
	}

	/**
	 * Checks if the template variables are empty and gives them a value.
	 */
	function wtCheckValues()
	{
	    if (!$this->wp_travel_temperature)
	    {
		$this->wp_travel_temperature = 'n/a';
	    }
	    else
	    {
		$this->wp_travel_temperature .= ($this->wt_unit == 'C') ? ' &deg;C' : ' &deg;F';
	    }
	    if (!$this->wp_travel_humidity)
	    {
		$this->wp_travel_humidity = 'n/a';
	    }
	    else
	    {
		$this->wp_travel_humidity .= '%';
	    }
	    if (!$this->wp_travel_dew)
	    {
		$this->wp_travel_dew = 'n/a';
	    }
	    else
	    {
		$this->wp_travel_dew .= ($this->wt_unit == 'C') ? ' &deg;C' : ' &deg;F';
	    }
	    if (!$this->wp_travel_speed)
	    {
		$this->wp_travel_speed = 'n/a';
	    }
	    else
	    {
		// Remove any leading 0s
		$this->wp_travel_speed = round($this->wp_travel_speed, 0) . 'km/hr';
	    }
	    if (!$this->wp_travel_direction)
	    {
		$this->wp_travel_direction = 'n/a';
	    }
	    else
	    {
		$this->wp_travel_direction .= '&deg;';
	    }
	    if (!$this->wp_travel_clouds)
	    {
		$this->wp_travel_clouds = 'n/a';
	    }
	}

	/**
	 * Gets the post meta data for each template variable listed in the
	 * wt_data array.
	 *
	 * @param int $post_id
	 */
	function wtGetPostMeta($post_id)
	{
	    global $wt;

	    $wt->wp_travel_location	    = get_post_meta($post_id, '_wp_travel_location', true);
	    $wt->wp_travel_temperature	    = get_post_meta($post_id, '_wp_travel_temperature', true);
	    $wt->wp_travel_humidity	    = get_post_meta($post_id, '_wp_travel_humidity', true);
	    $wt->wp_travel_dew		    = get_post_meta($post_id, '_wp_travel_dew', true);
	    $wt->wp_travel_speed	    = get_post_meta($post_id, '_wp_travel_speed', true);
	    $wt->wp_travel_direction	    = get_post_meta($post_id, '_wp_travel_direction', true);
	    $wt->wp_travel_clouds	    = get_post_meta($post_id, '_wp_travel_clouds', true);
	}

	/**
	 * Resets the template variables to prevent cross-post contamination.
	 */
	function wtResetValues()
	{
	    $this->wp_travel_location	    = '';
	    $this->wp_travel_temperature    = '';
	    $this->wp_travel_humidity	    = '';
	    $this->wp_travel_dew	    = '';
	    $this->wp_travel_speed	    = '';
	    $this->wp_travel_direction	    = '';
	    $this->wp_travel_clouds	    = '';
	}

	/**
	 * Generates a radio button.
	 *
	 * @param string $name The input field name
	 * @param string $id The input field id
	 * @param string $val The input field value
	 * @param string $label The label text
	 * @param boolean $disabled Is the radio button disabled
	 * @return string HTML code for the generated radio button
	 */
	function wtCreateRadio($name, $id, $val, $label, $disabled=false)
	{
	    if ($this->$name == $val)
	    {
		$checked = 'checked="checked" ';
	    }
	    if ($disabled===true){$is_disabled = 'disabled="disabled" ';}
	    $radio = '<input name="'.$name.'" type="radio" id="'.$id.'" value="'.$val.'" '.$checked.$disabled.'/><label for="'.$id.'">'.$label.'</label>';
	    return $radio;
	}

    }
}

// Initiate the class
if (class_exists("WeatherTraveller")) {
    $wt = new WeatherTraveller();
}

if ( function_exists('is_admin') )
{
	if ( !is_admin() )
	{
	    if ( function_exists('add_filter') )
		{
			add_filter('the_content', array(&$wt, 'wtAppendWeatherInfo'));
		}
	}
}
?>