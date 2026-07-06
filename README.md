# Heat Index Safety Dashboard (Standalone Docker Application)

A premium, containerized PHP dashboard that displays real-time heat index information fetched directly from the `weather.gov` API. The user interface dynamically shifts color themes and glow animations to match the active heat flag condition (green, yellow, red, black) according to safety and activity protocol limits.

It supports both live dynamic page rendering and static HTML generation for serving minified static files.

---

## Features

- **Dynamic Theme Adapting**: The dashboard's visual style, background gradients, card borders, and indicator dots automatically shift and animate to reflect the current Heat Index flag severity.
- **Robust Caching**: Automatically caches weather data to local files to stay within the weather.gov API rate limits.
- **Static Exporter**: Includes a minified static HTML exporter (`generate.php`) that compiles the dynamic PHP page into a static `heatindex.html` file for deployment to static CDNs or web servers.
- **Docker-First Design**: Fully configured to build and run out-of-the-box with Docker and Docker Compose.
- **Configurable Locations**: Customize weather station coordinates using environment variables without modifying source code.

---

## Getting Started

### Prerequisites

- [Docker](https://www.docker.com/get-started)
- [Docker Compose](https://docs.docker.com/compose/install/)

### Running the Application

1. **Clone the repository** (if not already local).
2. **Start the containers**:
   ```bash
   docker compose up -d --build
   ```
3. **Access the Dashboard**:
   Open [http://localhost:8080/](http://localhost:8080/) in your web browser.

---

## Configuration Options

You can configure the application using environment variables in the `docker-compose.yml` file:

| Variable | Description | Default | Example |
| :--- | :--- | :--- | :--- |
| `GENERATION_KEY` | Secret token required to run the static HTML generator. | `test-secret-key-12345` | `my-secure-uuid-token` |
| `WEATHER_LOCATION` | Code name for pre-defined locations (`sbr`, `deathvalley`, `dallas`, `denver`, `golden`). | `sbr` | `deathvalley` |
| `WEATHER_OFFICE` | Custom NWS Office Code (e.g. `RLX`). Requires `WEATHER_GRIDPOINTS` to be set. | None | `RLX` |
| `WEATHER_GRIDPOINTS` | Custom weather station gridpoints (e.g. `82,49`). Requires `WEATHER_OFFICE` to be set. | None | `82,49` |
| `FETCH_URL` | Loopback address the static generator uses to fetch and render the page. | `http://127.0.0.1/index.php` | `http://localhost/index.php` |

---

## Static HTML Generation

To trigger static file compilation and generate a minified `heatindex.html` file:

1. Send an HTTP request to the generator URL with your defined `generationSecret`:
   ```
   http://localhost:8080/generate.php?generationSecret=test-secret-key-12345
   ```
2. The server will update the minified file `heatindex.html` on the server disk and redirect your browser to the generated static page.
3. You can also generate location-specific static files using query parameters:
   ```
   http://localhost:8080/generate.php?generationSecret=test-secret-key-12345&location=deathvalley
   ```

---

## Local Development (Without Docker)

You can run this project using PHP's built-in web server:

```bash
# Set environment variables (Mac/Linux)
export GENERATION_KEY="local-secret"
export WEATHER_LOCATION="sbr"

# Start PHP built-in server
php -S localhost:9001
```

- Live Dashboard: [http://localhost:9001/](http://localhost:9001/)
- Trigger Generation: [http://localhost:9001/generate.php?generationSecret=local-secret&local=1](http://localhost:9001/generate.php?generationSecret=local-secret&local=1)
