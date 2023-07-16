<?php

$temperature_cache_filename = "./heatindex.txt";
$max_temperature_age_seconds = 60; // How stale the Heat Index can be
$dt = new DateTime();
$dt->setTimezone(new DateTimeZone("America/New_York"));
$datetime_format = "D, M j, g:i A T";
$now = time();
$api_error_message = null;

/**
 * Get Heat Index temperature from weather.gov API
 * 
 * @param string $location location keyword for debugging
 * @return temperature in Farenheit
 */
function get_temperature($location = "sbr") {
    global $dt, $now, $temperature_cache_filename, $api_error_message;

    // User-Agent constructed per https://www.weather.gov/documentation/services-web-api
    $context = stream_context_create(array("http" => array("user_agent" => "(natjamboree23.org, app@natjamboree23.org)")));
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
        $api_error_message = "Failed to access weather.gov API - please refresh in a few minutes.";
        http_response_code(500);
        return null;
    }
    $decoded = json_decode($json);

    // Iterate through the heat index forecast to find the current time window
    $latest_value = null;
    foreach ($decoded->properties->heatIndex->values as $heatindex_value) {
        if ($dt->setTimestamp($now) < DateTime::createFromFormat("Y-m-d\TH:i:s+", $heatindex_value->validTime)) {
            break;
        }
        if ($heatindex_value->value != null) {
            $latest_value = $heatindex_value->value;
        } // If the value is null, fall back to the last value
    }

    if ($latest_value == null) {
        $api_error_message = "All null values received from weather.gov API - please refresh in a few minutes.";
        http_response_code(500);
        return null;
    }

    // Convert Celsius to Farenheit
    $farenheit = ($latest_value * 9/5) + 32;

    // Write heat index temperature to a file to act as a cache
    file_put_contents($temperature_cache_filename, $farenheit);

    return $farenheit;
}



function get_cached_temp_and_timestamp() {
    global $dt, $now, $temperature_cache_filename, $max_temperature_age_seconds, $datetime_format;
    if (isset($_GET["nocache"])) {
        header("Cache-Control: no-cache");
    } else {
        header("Cache-Control: max-age=60");
    }

    // Read the last fetched temperature from the local file
    $cached_temperature = file_exists($temperature_cache_filename) ? file_get_contents($temperature_cache_filename) : null;
    $file_mtime = file_exists($temperature_cache_filename) ? filemtime($temperature_cache_filename) : null;
    $temperature_timestamp = null;
    if (
        isset($_GET["nocache"]) || // Check for URL param
        !$cached_temperature || // Check if file is missing
        ($now - $file_mtime > $max_temperature_age_seconds) // Check if file is stale
    ) {
        // Bypass the local cache due to URL param override, missing file, or stale file
        if (isset($_GET["location"])) {
            // Read location for debugging
            $temperature = get_temperature($_GET["location"]);
        } else {
            $temperature = get_temperature();
        }
        $temperature_timestamp = $dt->setTimestamp($now)->format($datetime_format);
    } else {
        // Cached file is wtihin freshness threshold, use that value to avoid an API call
        $temperature = $cached_temperature;
        $temperature_timestamp = $dt->setTimestamp($file_mtime)->format($datetime_format);
    }

    return array($temperature, $temperature_timestamp);
}

function get_color_and_description($temperature) {
    global $api_error_message;

    // Figure out the flag color & description
    if ($temperature == null) {
        $color = "";
        $description = $api_error_message;
    } elseif ($temperature >= 90) {
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

    return array($color, $description);
}

function get_now() {
    global $dt, $now, $datetime_format;
    return $dt->setTimestamp($now)->format($datetime_format);
}