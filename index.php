<?php

$temperature_cache_filename = "heatindex.txt";
$max_temperature_age_seconds = 60; // How stale the Heat Index can be

/**
 * Get Heat Index temperature from weather.gov API
 * 
 * @param string $location location keyword for debugging
 * @return temperature in Farenheit
 */
function get_temperature($location) {
    // TODO add contact email address in the User-Agent per https://www.weather.gov/documentation/services-web-api
    $context = stream_context_create(array("http" => array("user_agent" => "natjamboree23.org")));
    if ($location == "deathvalley") {
        $office_code = "VEF";
        $gridpoints = "61,124";
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
        // Harcoded location based on The Summit lat/long
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
    $farenheit = ($latest_value * 9/5) + 32;

    // Write heat index temperature to a file to act as a cache
    global $temperature_cache_filename;
    file_put_contents($temperature_cache_filename, $farenheit);

    return $farenheit;
}

// Read the last fetched temperature from the local file
$cached_temperature = file_get_contents($temperature_cache_filename);
$file_mtime = filemtime($temperature_cache_filename);
$temperature_timestamp = null;
if (
    isset($_GET["nocache"]) || // Check for URL param
    !$cached_temperature || // Check if file is missing
    (time() - $file_mtime > $max_temperature_age_seconds) // Check if file is stale
) {
    // Bypass the local cache due to URL param override, missing file, or stale file
    $location = "sbr";
    if (isset($_GET["location"])) {
        $location = $_GET["location"];
    }
    $temperature = get_temperature($location);
    $temperature_timestamp = date("c");
} else {
    // Cached file is wtihin freshness threshold, use that value to avoid an API call
    $temperature = $cached_temperature;
    $temperature_timestamp = date("c", $file_mtime);
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

<!DOCTYPE html>
<html>
    <head>
        <title>Heat Index</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            }
        </style>
    </head>
    <body>
        <h1 id="heading">Heat Index is <?php echo $temperature ?>&deg F</h1>
        <?php
            if(isset($location) && $location != "sbr") {
                echo "<p>Location: $location</p>";
            }
        ?>
        <p id="flag-text">Flag color is <?php echo $color ?></p>
        <div id="flag-rectangle" style="width:300px;height:100px;background-color:<?php echo $color ?>;"
            aria-label="<?php echo $color ?> flag"></div>
        <p id="flag-description"><?php echo $description ?></p>
        <p><?php
        // TODO: Either format this or remove it
        // Currently exists for testing refresh button
        echo "Heat Index refreshed at: " . $temperature_timestamp . "<br>";
        echo "Page refreshed at: " . date("c");
        ?></p>
        <button onclick="window.location.reload()">Refresh</button>

    </body>
</html>