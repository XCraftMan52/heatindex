<?php

require("secrets.php");

if(!isset($_GET["generationSecret"]) || $_GET["generationSecret"] != $GENERATION_KEY) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

if(isset($_GET["local"])) {
    $url = "http://localhost:9001";
} else {
    $url = "https://www.natjamboree23.org/wp-content/heatindexapp/index.php";
}

$data = file_get_contents($url);

if($data == false) {
    echo "Failed to fetch data";
    exit;
}


header("Location: heatindex.html");
http_response_code(302);

file_put_contents("./heatindex.html", $data);

echo "Data output, redirecting";