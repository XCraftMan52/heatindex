<?php

require("./temperature_functions.php");

$data = get_cached_temp_and_timestamp();

$temperature = $data[0];
$temperature_timestamp = $data[1];

$color_data = get_color_and_description($temperature);

$color = $color_data[0];
$description = $color_data[1];

?>

<!DOCTYPE html>
<html>

<head>
    <title>Heat Index</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    .btn {
        background-color: #1c407d;
        border: 2px solid #1c407d;
        border-radius: 0.375rem;
        box-shadow: none;
        color: #ffffff;
        cursor: pointer;
        font-size: 1rem;
        padding: 0.6rem 1rem;
        transition: all 0.25s;
    }

    .btn:hover,
    .btn:active {
        background-color: #ffffff;
        color: #1c407d;
    }
    </style>
</head>

<body>
    <script>
    setTimeout(() => {
            document.getElementById("mandatory-refresh-content").style.display = "block"
            document.getElementById("heat-index-content").style.display = "none"
        },
        5 * 60 * 1000
    )
    </script>
    <div id="heat-index-content">
        <h1 id="heading">Heat Index is <?php echo $temperature ?>&deg F</h1>
        <?php
            if(isset($_GET["location"])) {
                echo "<p>Location: " . $_GET["location"] . "</p>";
            }
        ?>
        <p id="flag-text">Flag color is <?php echo $color ?></p>
        <div id="flag-rectangle" style="width:300px;height:100px;background-color:<?php echo $color ?>;"
            aria-label="<?php echo $color ?> flag"></div>
        <p id="flag-description"><?php echo $description ?></p>
        <p><?php
            echo "Heat Index refreshed at: " . $temperature_timestamp . "<br>";
            echo "Page refreshed at: " . $dt->setTimestamp($now)->format($datetime_format);
        ?></p>
        <button class="btn btn-refresh" onclick="window.location.reload()">Refresh</button>
    </div>
    <div id="mandatory-refresh-content" style="display: none;">
        <p>Please <a href="refresh" onclick="window.location.reload()">refresh</a> the page.</p>
        <p>The data that you were previously viewing is more than 5 minutes old.</p>
    </div>
</body>

</html>