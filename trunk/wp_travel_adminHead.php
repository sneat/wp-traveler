<style type="text/css">
    div#weatherlocationdiv span.weather_response {margin-left:3em;display:block;font-weight:bold;}
</style>
<script type="text/javascript">
//<![CDATA[
function makeNotice(type,message)
{
    if (type=='notice')
    {
	wclass='updated';
    }
    else
    {
	wclass='error';
    }
    if (type=='error')
    {
	if(jQuery("#weathernotice").length>0){jQuery("#weathernotice").remove();}
    }
    if (type=='notice')
    {
	if(jQuery("#weathererror").length>0){jQuery("#weathererror").remove();}
    }
    if(jQuery("#weather" + type).length>0)
    {
	jQuery("#weather" + type).replaceWith('<div id="weather' + type + '" class="' + wclass + '">' + message + '</div>');
    }
    else
    {
	jQuery("#weatherlocationdiv > div.inside").prepend('<div id="weather' + type + '" class="' + wclass + '">' + message + '</div>');
    }
}

function updateWeather()
{
    if (jQuery("#wpgeo_location").length<1){
	makeNotice('error','<p>Weather Traveler requires <a href="http://www.wpgeo.com/" target="_blank">WP Geo Location</a> to be installed. Please install this before trying to use Traveller weather.</p>');
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
}

function responseReceived(loc,temp,hum,dew,dir,speed,clouds){
    document.getElementById("wp_travel_location").value=loc;
    document.getElementById("wp_travel_temperature").value=temp;
    document.getElementById("wp_travel_humidity").value=hum;
    document.getElementById("wp_travel_dew").value=dew;
    document.getElementById("wp_travel_direction").value=dir;
    document.getElementById("wp_travel_speed").value=speed;
    document.getElementById("wp_travel_clouds").value=clouds;
    makeNotice('notice','<p><span style="float:right;"><a href="#" onclick="jQuery(\'#weathernotice\').remove(); return false;">Dismiss</a></span>Weather details fetched.</p><p>You can now edit the <a href="#" onclick="jQuery(\'#wp_travel_location\').focus(); return false;">Location name</a> if you wish (e.g. remove the word \'Airport\') as the station name is often not what you want.</p><p>Weather details will be saved with your post so that any future changes that you make to the template will display all of the relevant data.</p>');
}
//]]>
</script>