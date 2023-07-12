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
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            margin: 0;
        }

        #heat-index-content,
        #mandatory-refresh-content {
            margin: 1rem;
        }

        #flag-rectangle {
            height: 160px;
            margin: -1rem;
            width: 100vw;
        }

        #heat-index-content header {
            background-color: #ffffff;
            border-radius: 0.375rem;
            margin-top: -3rem;
            padding: 0.75rem 1rem;
            width: calc(100% - 2rem);
        }

        #heat-index-content header h1 {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }

        #heat-index-content article {
            margin: 0 1rem;
        }

        @media (min-width: 767px) {
            #heat-index-content header {
                max-width: 50vw;
            }
        }

        @media (min-width: 992px) {
            #heat-index-content header {
                max-width: 30vw;
            }
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
    );
    </script>
    <section id="heat-index-content">
        <div id="flag-rectangle" style="background-color:<?= $color ?>;"
                aria-label="<?= $color ?> flag"></div>
        <header>
            <h1 id="heading">Heat Index is <?= $temperature ?>&deg; F</h1>
            <?php
                if(isset($_GET["location"])) {
                    echo "<p>Location: " . $_GET["location"] . "</p>";
                }
            ?>
        </header>
        <article>
            <p id="flag-text">Flag Color: <?= ucfirst($color) ?></p>
            <p id="flag-description"><?= $description ?></p>
            <p><?php
                echo "<strong>Heat Index refreshed:</strong> " . $temperature_timestamp . "<br>";
                echo "<strong>Page refreshed:</strong> " . $dt->setTimestamp($now)->format($datetime_format);
            ?></p>
            <button class="btn btn-lg btn-primary mt-3" onclick="window.location.reload()">Refresh</button>
        </article>
    </section>
    <section id="mandatory-refresh-content" style="display: none;">
        <p>Please <a href="" onclick="window.location.reload()">refresh</a> the page.</p>
        <p>The data that you were previously viewing is more than 5 minutes old.</p>
    </section>
</body>

</html>