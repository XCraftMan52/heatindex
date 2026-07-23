<?php

// function get_temperature($location) {
//     // Moved inside: Configures ONLY this API fetch
//     $default_location = getenv("WEATHER_LOCATION") ?: "SBR";
//     $temperature_cache_filename = ($location === "SBR") ? __DIR__ . "/heatindex.txt" : __DIR__ . "/heatindex_" . preg_replace('/[^a-z0-9_-]/', '', $location) . ".txt";

//     if ($location == "deathvalley") {
//         $office_code = "VEF"; $gridpoints = "61,124";
//     } else if ($location == "dallas") {
//         $office_code = "FWD"; $gridpoints = "89,104";
//     } else if ($location == "denver") {
//         $office_code = "BOU"; $gridpoints = "63,62";
//     } else if ($location == "golden") {
//         $office_code = "BOU"; $gridpoints = "55,63";
//     } else {
//         $office_code = "RLX"; $gridpoints = "82,49";
//     }

//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, "https://api.weather.gov/gridpoints/$office_code/$gridpoints");
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_USERAGENT, "(natjamboree23.org, app@natjamboree23.org)");
//     curl_setopt($ch, CURLOPT_TIMEOUT, 10);
//     curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
//     $json = curl_exec($ch);
//     curl_close($ch);

//     if ($json === false || empty($json)) {
//         return "null";
//     }
    
//     $decoded = json_decode($json);
//     if (!isset($decoded->properties->heatIndex->values)) {
//         return "null";
//     }

//     $dt = new DateTime("now", new DateTimeZone("America/New_York"));
//     $now = time();

//     foreach ($decoded->properties->heatIndex->values as $heatindex_value) {
//         if ($dt->setTimestamp($now) < DateTime::createFromFormat("Y-m-d\TH:i:s+", $heatindex_value->validTime)) {
//             break;
//         }
//         if ($heatindex_value->value != null) {
//             $latest_value = $heatindex_value->value;
//         }
//     }

//     $farenheit = ($latest_value != null) ? (($latest_value * 9/5) + 32) : "null";

//     file_put_contents($temperature_cache_filename, $farenheit);
//     return $farenheit;
// }

function get_temperature($location) {
// Moved inside: Configures ONLY this API fetch
$default_location = getenv("WEATHER_LOCATION") ?: "SBR";
$temperature_cache_filename = ($location === "SBR")
    ? __DIR__ . "/heatindex.txt"
    : __DIR__ . "/heatindex_" . preg_replace('/[^a-z0-9_-]/', '', $location) . ".txt";
$url = "https://wvdhsem.onerain.com/sensor/?site_id=1403&site=8b2bc91e-7a23-4829-a6af-b03f8247980a&device_id=10&device=329bb8d4-1972-4c12-9587-7f7f90b046aa";

$cookieFile = __DIR__ . "/cookies.txt";
function get_new_onerain_cookie($url, $cookieFile) {
    // Generate a new session cookie
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_USERAGENT => "Mozilla/5.0",
    ]);

    curl_exec($ch);
    

    // Read generated cookie file
    if (file_exists($cookieFile)) {
        $cookies = file($cookieFile);

        foreach ($cookies as $line) {
            if (strpos($line, "WEBAPP_SESSION") !== false) {
                $parts = explode("\t", trim($line));
                return end($parts);
            }
        }
    }

    return null;
}


function fetch_onerain($url, $cookie) {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [
            "Cookie: WEBAPP_SESSION=" . $cookie
        ],

        CURLOPT_USERAGENT => "Mozilla/5.0",

        CURLOPT_FOLLOWLOCATION => false,
    ]);

    $response = curl_exec($ch);

    $info = [
        "response" => $response,
        "code" => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        "redirect" => curl_getinfo($ch, CURLINFO_REDIRECT_URL)
    ];

  

    return $info;
}


// First attempt (your existing cookie)
$cookie = "e4ho467t3214on5hops2kg4hc7";

$result = fetch_onerain($url, $cookie);


// If expired, generate a new one and retry
if (
    $result["code"] == 302 &&
    str_contains($result["redirect"], "/login")
) {

    $cookie = get_new_onerain_cookie($url, $cookieFile);

    if ($cookie !== null) {
        $result = fetch_onerain($url, $cookie);
    }
}


$response = $result["response"];

// Extract the CURRENT heat index (the top card)
if (preg_match(
    '/icon-time.*?<h4 class="mb-0">\s*([\d.]+)\s*F\s*<\/h4>/s',
    $response,
    $matches
)) {
    $fahrenheit = (float)$matches[1];

    file_put_contents($temperature_cache_filename, $fahrenheit);

    return $fahrenheit;
}

return "null";
}
function get_cached_temp_and_timestamp($location) {
    // Moved inside: Configures ONLY the cache thresholds
    $max_temperature_age_seconds = 600;
    $max_null_age_seconds = 60;
    $now = time();
    $datetime_format = "D, M j, g:i:s A T";
    $dt = new DateTime("now", new DateTimeZone("America/New_York"));

    $temperature_cache_filename = ($location === "sbr") ? __DIR__ . "/heatindex.txt" : __DIR__ . "/heatindex_" . preg_replace('/[^a-z0-9_-]/', '', $location) . ".txt";

    if (isset($_GET["nocache"])) {
        header("Cache-Control: no-cache");
    } else {
        header("Cache-Control: max-age=60");
    }

    $cached_temperature = file_exists($temperature_cache_filename) ? file_get_contents($temperature_cache_filename) : null;
    $file_mtime = file_exists($temperature_cache_filename) ? filemtime($temperature_cache_filename) : null;

    if (
        isset($_GET["nocache"]) || 
        !$cached_temperature || 
        ($now - $file_mtime > $max_temperature_age_seconds) || 
        ($cached_temperature == "null" && ($now - $file_mtime > $max_null_age_seconds))
    ) {
        $temperature = get_temperature($location);
        $temperature_timestamp = $dt->setTimestamp($now)->format($datetime_format);
    } else {
        $temperature = $cached_temperature;
        $temperature_timestamp = $dt->setTimestamp($file_mtime)->format($datetime_format);
    }

    if ($temperature == "null") {
        $temperature = null;
    }

    return array($temperature, $temperature_timestamp);
}

function get_color_and_description($temperature) {
   $override_color = null;

    // 1. URL QUERY PARAMETERS TAKE ABSOLUTE PRIORITY
    if (isset($_GET['override']) && trim($_GET['override']) !== '') {
        $override_color = trim($_GET['override']);
    } elseif (isset($_GET['flag']) && trim($_GET['flag']) !== '') {
        $override_color = trim($_GET['flag']);
    }

    // 2. Fallback to Environment Variables if URL is empty
    if (empty($override_color)) {
        $env_override = getenv('OVERRIDE_COLOR') ?: getenv('HEAT_FLAG');
        if ($env_override !== false) {
            $override_color = trim($env_override);
        }
    }

    // 3. Fallback to local text file ONLY if URL and ENV are completely empty
    if (empty($override_color)) {
        $override_file = __DIR__ . "/override_color.txt";
        if (file_exists($override_file)) {
            $file_contents = trim(file_get_contents($override_file));
            if ($file_contents !== '') {
                $override_color = $file_contents;
            }
        }
    }

    // Standardize input string
    $override_color = !empty($override_color) ? strtolower(trim($override_color)) : null;

    // --- RESOLVE COLOR ---
    if (!empty($override_color)) {
        $color = $override_color;
    } else if ($temperature === null || $temperature === "null") {
        $color = "unknown";
    } else {
        if ($temperature >= 90) {
            $color = "black";
        } else if ($temperature >= 88) {
            $color = "red";
        } else if ($temperature >= 85) {
            $color = "yellow";
        } else {
            $color = "green";
        }
    }
    switch ($color) {
        case 'black':
            $description = "All participants not actively involved in activities should remain under shade. 20 minutes of rest for every 30 minutes of activity. Drink at least 1 to 1 1/4 quarts of water per hour.";
            break;
        case 'red':
            $description = "15 minutes of rest will be exercised for every 45 minutes of activity. Drink at least 3/4 to 1 full quart of water per hour.";
            break;
        case 'yellow':
            $description = "Staff will observe participants for signs of dehydration. 10 minutes of rest for every 60 minutes of activity. Drink at least 1/2 to 3/4 quart of water per hour.";
            break;
        case 'green':
            $description = "Physical activities will be conducted with caution and under constant supervision. Drink at least 1/4 to 1/2 quart of water per hour.";
            break;
        default:
            $color = "unknown";
            $description = "Missing or malformed data received from the weather service engine.";
            break;
    }

    return array($color, $description);
}