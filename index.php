<?php

/**
 * Get Heat Index temperature from weather.gov API
 * 
 * @return temperature in Farenheit
 */
function get_temperature($location)
{
    // TODO add contact email address in the User-Agent per https://www.weather.gov/documentation/services-web-api
    $context = stream_context_create(array("http" => array("user_agent" => "natjamboree23.org")));
    // Harcoded URL based on The Summit lat/long
    if ($location == "deathvalley") {
        $office_code = "VEF";
        $gridpoints = "61,124";
        // https://api.weather.gov/gridpoints/VEF/61,124
    } else if ($location == "dallas") {
        $office_code = "FWD";
        $gridpoints = "89,104";
    } else if ($location == "denver") {
        $office_code = "BOU";
        $gridpoints = "63,62";
    } else if ($location == "golden") {
        $office_code = "BOU";
        $gridpoints = "55,63";
    } else {
        $office_code = "RLX";
        $gridpoints = "82,50";
    }

    $json = file_get_contents("https://api.weather.gov/gridpoints/$office_code/$gridpoints", false, $context);
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
    return ($latest_value * 9 / 5) + 32;
}

// TODO check for local file with cached temperature
$cached_temperature = null;
$temperature_timestamp = null;
$location = "sbr";
if (isset($_GET["location"])) {
    $location = $_GET["location"];
}
if (isset($_GET["nocache"]) || !$cached_temperature) {
    // Bypass the local cache
    $temperature = get_temperature($location);
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
    $description = "All participants not actively involved in activities should remain under shade. 20 minutes of rest for every 30 minutes of activity. Medical assistance will be called for any person displaying potential signs of heat-related illness. If the Garden Ground trek is underway, frequent water breaks will be taken. Drink at least 1 to 1 1/4 quarts of water per hour.";
} elseif ($temperature >= 88) {
    $color = "red";
    $description = "15 minutes of rest will be exercised for every 45 minutes of activity. Medical assistance will be called for any person displaying potential signs of heat-related illness. If anyone is hiking, frequent water breaks should be taken. Drink at least 3/4 to 1 full quart of water per hour.";
} elseif ($temperature >= 85) {
    $color = "yellow";
    $description = "Staff will observe participants and other staff members for signs of dehydration or heat-related illnesses. 10 minutes of rest will be exercised for every 60 minutes of activity. Staff shall monitor anyone displaying signs of dehydration or heat-related illness and call for medical assistance if needed. Drink at least 1/2 to 3/4 quart of water per hour.";
} else {
    $color = "green";
    $description = "Physical activities will be conducted with caution and under constant supervision. Staff will observe participants and leaders to ensure they are carrying water bottles. Drink at least 1/4 to 1/2 quart of water per hour.";
}

?>

<html>
<title>Heat Index</title>
<h1 id="heading">Heat Index is <?php echo $temperature ?>&deg F</h1>
<?php
 if($location and $location != "sbr") {
    echo "<p>Location: $location</p>";
 }
?>
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