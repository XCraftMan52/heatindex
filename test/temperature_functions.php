<?php

# Note: This file is copied and pasted as we're dumping to a file

$temperature_cache_filename = "./heatindex.txt";
$test_data = "./testdata.csv";
$max_temperature_age_seconds = 60; // How stale the Heat Index can be
$dt = new DateTime();
$dt->setTimezone(new DateTimeZone("America/New_York"));
$datetime_format = "D, M j, g:i A T";
$now = time();

/**
 * Get Heat Index temperature from weather.gov API
 * 
 * @param string $location location keyword for debugging
 * @return temperature in Farenheit
 */
function get_temperature($location = "SBR")
{
    global $dt, $now, $test_data;

    // User-Agent constructed per https://www.weather.gov/documentation/services-web-api
    $context = stream_context_create(array("http" => array("user_agent" => "(natjamboree23.org, app@natjamboree23.org)")));

    if($location == "SBR4") {
        $office_code = "RLX";
        $gridpoints = "85,49";
    } else if($location == "SBR3") {
        $office_code = "RLX";
        $gridpoints = "83,49";
    } else if($location == "SBR2") {
        $office_code = "RLX";
        $gridpoints = "82,49";
    } else {
        // Harcoded location based on The Summit lat/long
        $office_code = "RLX";
        $gridpoints = "82,50";
    }

    $json = file_get_contents("https://api.weather.gov/gridpoints/$office_code/$gridpoints", false, $context);
    if (!$json) {
        $now_stamp = date("c");
        $row = "$now_stamp, FAILED, $location\n";
        file_put_contents($test_data, $row, FILE_APPEND);
        return;
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
        $now_stamp = date("c");
        $row = "$now_stamp, NULL, $location\n";
        file_put_contents($test_data, $row, FILE_APPEND);
        return;
    }

    // Convert Celsius to Farenheit
    $farenheit = ($latest_value * 9 / 5) + 32;


    $now_stamp = date("c");
    $row = "$now_stamp, $farenheit, $location\n";
    file_put_contents($test_data, $row, FILE_APPEND);
}

get_temperature();
get_temperature("SBR2");
get_temperature("SBR3");
get_temperature("SBR4");