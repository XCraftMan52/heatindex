<?php

/**
 * Get Heat Index temperature from weather.gov API
 * 
 * @return temperature in Farenheit
 */
function get_temperature() {
    // TODO add contact email address in the User-Agent per https://www.weather.gov/documentation/services-web-api
    $context = stream_context_create(array("http" => array("user_agent" => "natjamboree23.org")));
    // Harcoded URL based on The Summit lat/long
    $json = file_get_contents("https://api.weather.gov/gridpoints/RLX/82,50", false, $context);
    if (!$json) {
        echo "Failed to access weather.gov API";
        exit;
    }
    $decoded = json_decode($json);

    // Iterate through the heat index forecast to find the current time window
    $now = new DateTime();
    $latest_value = null;
    foreach ($decoded->properties->heatIndex->values as $heatindex_value) {
        if ($now < DateTime::createFromFormat("Y-m-d\TH:i:s+", $heatindex_value->validTime)) {
            break;
        }
        $latest_value = $heatindex_value->value;
    }

    // Convert Celsius to Farenheit
    return ($latest_value * 9/5) + 32;
}

// TODO check for local file with cached temperature
$cached_temperature = null;
$temperature_timestamp = null;
if (isset($_GET["nocache"]) || !$cached_temperature) {
    // Bypass the local cache
    $temperature = get_temperature();
    $temperature_timestamp = date("c");
} else {
    // TODO check for local file modification time, set true if >60 seconds ago
    $cached_temperature_stale = null;
    if ($cached_temperature_stale) {
        // TODO write temperature to local file to cache it and reset mtime & temp_timestamp
    } else {
        $temperature = $cached_temperature;
        $temperature_timestamp = date("c"); // TODO make this the file mtime
    }
}

// Figure out the flag color & description
if ($temperature >= 90) {
    $color = "black";
    $description = "";
} elseif ($temperature >= 88) {
    $color = "red";
    $description = "";
} elseif ($temperature >= 85) {
    $color = "yellow";
    $description = "";
} else {
    $color = "green";
    $description = "";
}

?>

<html>
<title>Heat Index</title>
<h1 id="heading">Heat Index is <?php echo $temperature ?>&deg F</h1>
<p id="flag-text">Flag color is <?php echo $color ?></p>
<div id="flag-rectangle" style="width:300px;height:100px;background-color:<?php echo $color ?>;"></div>
<p id="flag-description"><?php echo $description ?></p>
<p><?php
// TODO: Either format this or remove it
// Currently exists for testing refresh button
echo "Heat Index refreshed at: ".$temperature_timestamp."<br>";
echo "Page refreshed at: ".date("c");
?></p>
<button onclick="window.location.reload()">Refresh</button>

</html>