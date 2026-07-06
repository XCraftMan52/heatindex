<?php

$default_location = getenv("WEATHER_LOCATION") ?: "sbr";
$current_location = isset($_GET["location"]) ? strtolower($_GET["location"]) : $default_location;

// Keep compatibility with heatindex.txt for the default sbr location
$temperature_cache_filename = ($current_location === "sbr") ? "./heatindex.txt" : "./heatindex_" . preg_replace('/[^a-z0-9_-]/', '', $current_location) . ".txt";

$max_temperature_age_seconds = 600; // How stale the Heat Index can be
$max_null_age_seconds = 60; // How stale the null-cached Heat Index can be
$dt = new DateTime();
$dt->setTimezone(new DateTimeZone("America/New_York"));
$datetime_format = "D, M j, g:i:s A T";
$now = time();
$api_error_message = null; // Our own error message to be displayed on the frontend

/**
 * Get Heat Index temperature from weather.gov API
 * 
 * @param string|null $location location keyword for debugging
 * @return temperature in Farenheit
 */
function get_temperature($location = null) {
    global $dt, $now, $temperature_cache_filename, $api_error_message, $default_location;

    if ($location === null) {
        $location = $default_location;
    }
    $location = strtolower($location);
    $latest_value = null; // Initial value, default if the API misbehaves

    // User-Agent constructed per https://www.weather.gov/documentation/services-web-api
    $context = stream_context_create(array("http" => array("user_agent" => "(natjamboree23.org, app@natjamboree23.org)")));

    // Check if custom office and gridpoints are provided in the environment (applies to the default location)
    $custom_office = getenv("WEATHER_OFFICE");
    $custom_gridpoints = getenv("WEATHER_GRIDPOINTS");

    if ($custom_office && $custom_gridpoints && $location === strtolower($default_location)) {
        $office_code = $custom_office;
        $gridpoints = $custom_gridpoints;
    } else if ($location == "deathvalley") {
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
        // Hardcoded location based on The Summit lat/long (sbr)
        $office_code = "RLX";
        $gridpoints = "82,49";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.weather.gov/gridpoints/$office_code/$gridpoints");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "(natjamboree23.org, app@natjamboree23.org)");
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $json = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($json === false || empty($json)) {
        $api_error_message = "Failed to access weather.gov API (curl error: " . ($curl_error ?: 'empty response') . ") - please refresh in a few minutes.";
        http_response_code(500);
        return "null";
    }
    
    $decoded = json_decode($json);
    if (!isset($decoded->properties->heatIndex->values)) {
        $api_error_message = "Malformed weather.gov API response - please refresh in a few minutes.";
        http_response_code(500);
        return "null";
    }

    // Iterate through the heat index forecast to find the current time window
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
    }

    if ($latest_value != null) {
        // Convert Celsius to Farenheit
        $farenheit = ($latest_value * 9/5) + 32;
    } else {
        // Cache a special string to indicate recent fetches were null
        $farenheit = "null";
        $api_error_message = "Null cache from weather.gov API - please refresh in a few minutes.";
        http_response_code(500);
    }

    // Write heat index temperature to a file to act as a cache
    file_put_contents($temperature_cache_filename, $farenheit);

    return $farenheit;
}

function get_cached_temp_and_timestamp() {
    global $dt, $now, $temperature_cache_filename, $max_temperature_age_seconds, $max_null_age_seconds, $datetime_format, $api_error_message, $current_location;
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
        ($now - $file_mtime > $max_temperature_age_seconds) || // Check if file is stale
        ($cached_temperature == "null" && ($now - $file_mtime > $max_null_age_seconds)) // Check if null-cached file is stale
    ) {
        // Bypass the local cache due to URL param override, missing file, or stale file
        $temperature = get_temperature($current_location);
        $temperature_timestamp = $dt->setTimestamp($now)->format($datetime_format);
    } else {
        // Cached file is within freshness threshold, use that value to avoid an API call
        $temperature = $cached_temperature;
        $temperature_timestamp = $dt->setTimestamp($file_mtime)->format($datetime_format);
    }

    // Special value when the cached file contains "null" for caching an API error
    if ($temperature == "null") {
        $temperature = null;
        $api_error_message = "Null cache from weather.gov API - please refresh in a few minutes.";
        http_response_code(500);
    }

    return array($temperature, $temperature_timestamp);
}

function get_color_and_description($temperature) {
    global $api_error_message;

    // 1. Check URL query parameters
    $override_color = null;
    if (isset($_GET['override'])) {
        $override_color = trim($_GET['override']);
    } elseif (isset($_GET['flag'])) {
        $override_color = trim($_GET['flag']);
    }

    // 2. Check environment variables
    if (empty($override_color)) {
        $env_override = getenv('OVERRIDE_COLOR') ?: getenv('HEAT_FLAG');
        if ($env_override !== false) {
            $override_color = trim($env_override);
        }
    }

    // 3. Check local override file
    if (empty($override_color)) {
        $override_file = "./override_color.txt";
        if (file_exists($override_file)) {
            $override_color = trim(file_get_contents($override_file));
        }
    }

    $override_color = !empty($override_color) ? strtolower($override_color) : null;

    // Figure out the flag color & description
    if ($temperature == null && empty($override_color)) {
        $color = "";
        $description = $api_error_message ?: "Unknown error retrieving heat index details.";
    } elseif (($override_color == null && $temperature >= 90) || $override_color == "black") {
        $color = "black";
        $description = "All participants not actively involved in activities should remain under shade. 20 minutes of rest for every 30 minutes of activity. Medical assistance will be called for any person displaying potential signs of heat-related illness. If the Garden Ground trek is underway, frequent water breaks will be taken. Drink at least 1 to 1 1/4 quarts of water per hour.";
    } elseif (($override_color == null && $temperature >= 88) || $override_color == "red") {
        $color = "red";
        $description = "15 minutes of rest will be exercised for every 45 minutes of activity. Medical assistance will be called for any person displaying potential signs of heat-related illness. If anyone is hiking, frequent water breaks should be taken. Drink at least 3/4 to 1 full quart of water per hour.";
    } elseif (($override_color == null && $temperature >= 85) || $override_color == "yellow") {
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