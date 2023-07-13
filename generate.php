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

echo "Data output, redirecting";