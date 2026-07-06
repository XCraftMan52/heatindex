<?php

if (file_exists("secrets.php")) {
    require("secrets.php");
}

$generation_key = getenv("GENERATION_KEY") ?: ($GENERATION_KEY ?? null);

if (!$generation_key) {
    http_response_code(500);
    echo "Configuration Error: GENERATION_KEY environment variable or secrets.php is not set.";
    exit;
}

if(!isset($_GET["generationSecret"]) || $_GET["generationSecret"] != $generation_key) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

// Build URL to fetch index.php
if (isset($_GET["local"])) {
    $url = "http://localhost:9001/index.php";
} else {
    // Inside Docker, default to fetching itself via loopback on port 80.
    // Can be overridden via FETCH_URL environment variable.
    $url = getenv("FETCH_URL") ?: "http://127.0.0.1/index.php";
}

// Forward any location or nocache parameters to the generator request
$params = [];
if (isset($_GET["location"])) {
    $params["location"] = $_GET["location"];
}
if (isset($_GET["nocache"])) {
    $params["nocache"] = "1";
}
if (!empty($params)) {
    $url .= "?" . http_build_query($params);
}

// Set a timeout stream context to avoid hanging requests
$ctx = stream_context_create([
    'http' => [
        'timeout' => 15, // 15 seconds
    ]
]);

$data = file_get_contents($url, false, $ctx);

if($data == false) {
    http_response_code(502);
    echo "Failed to fetch data from $url";
    exit;
}

// https://stackoverflow.com/a/48123642
function minify_html($html)
{
   $search = array(
    '/(\n|^)(\x20+|\t)/',
    '/(\n|^)\/\/(.*?)(\n|$)/',
    '/\n/',
    '/\<\!--.*?-->/',
    '/(\x20+|\t)/', # Delete multispace (Without \n)
    '/\>\s+\</', # strip whitespaces between tags
    '/(\"|\')\s+\>/', # strip whitespaces between quotation ("') and end tags
    '/=\s+(\"|\')/'); # strip whitespaces between = "'

   $replace = array(
    "\n",
    "\n",
    " ",
    "",
    " ",
    "><",
    "$1>",
    "=$1");

    $html = preg_replace($search,$replace,$html);
    return $html;
}

// Write a minified version to heatindex.html
file_put_contents("./heatindex.html", minify_html($data));

header("Location: heatindex.html");
http_response_code(302);
echo "Data output, redirecting";