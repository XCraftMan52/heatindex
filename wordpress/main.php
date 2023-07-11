<?php

/**
 * @package Jamboree Heat Index
 * @version 0.0.1
 */
/*
Plugin Name: Jamboree Heat Index
Plugin URI: https://github.com/natjamboree23/heatindex/
Description: Adds a heat index shortcode
Author: Jason Kiesling, Adam Gross, Nathan Vick
Version: 0.0.1
Author URI: https://scouting.org
Tested up to: 6.2
Requires at least: 6.1
*/

require("temperature_functions.php");

function add_heat_index()
{
	$data = "";

	$temp_data = get_cached_temp_and_timestamp();

	$temperature = $temp_data[0];
	$temperature_timestamp = $temp_data[1];

	$color_data = get_color_and_description($temperature);

	$color = $color_data[0];
	$description = $color_data[1];

	$data .= '<script>
    setTimeout(() => {
            document.getElementById("mandatory-refresh-content").style.display = "block"
            document.getElementById("heat-index-content").style.display = "none"
        },
        5 * 60 * 1000
    )
    </script>';

	$data .= '<div id="heat-index-content">';
	$data .= '<h1 id="heading">Heat Index is ' . $temperature  . '&deg; F</h1>';

	if (isset($_GET["location"])) {
		$data .= "<p>Location: " . $_GET["location"] . "</p>";
	}
	$data .= '<p id="flag-text">Flag color is ' . $color  . '</p>';
	$data .= '<div id="flag-rectangle" style="width:300px;height:100px;background-color:' . $color  . ';"';
	$data .= ' aria-label="' . $color  . ' flag"></div>';
	$data .= '<p id="flag-description">' . $description  . '</p>';
	$data .= '<p>';
	$data .= "Heat Index refreshed at: " . $temperature_timestamp . "<br>";
	$data .= "Page refreshed at: " . get_now();
	$data .= '</p>';
	$data .= '<button class="btn btn-refresh" onclick="window.location.reload()">Refresh</button>';
	$data .= '</div>';
	$data .= '<div id="mandatory-refresh-content" style="display: none;">';
	$data .= '<p>Please <a href="refresh" onclick="window.location.reload()">refresh</a> the page.</p>';
	$data .= '<p>The data that you were previously viewing is more than 5 minutes old.</p>';
	$data .= '</div>';

	return $data;
}

add_shortcode("heat_index", "add_heat_index");