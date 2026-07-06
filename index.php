<?php

require("./temperature_functions.php");

$data = get_cached_temp_and_timestamp();

$temperature = $data[0];
$temperature_timestamp = $data[1];

$color_data = get_color_and_description($temperature);

$color = $color_data[0];
$description = $color_data[1];

// Dynamic CSS Variables based on flag color
switch ($color) {
    case 'green':
        $theme_vars = "
            --bg-gradient: linear-gradient(135deg, #022c16, #020f08);
            --card-bg: rgba(12, 48, 28, 0.45);
            --card-border: rgba(34, 197, 94, 0.25);
            --text-primary: #f0fdf4;
            --text-secondary: #86efac;
            --accent-color: #22c55e;
            --accent-glow: rgba(34, 197, 94, 0.4);
            --orb-1: rgba(34, 197, 94, 0.25);
            --orb-2: rgba(74, 222, 128, 0.1);
        ";
        break;
    case 'yellow':
        $theme_vars = "
            --bg-gradient: linear-gradient(135deg, #2e1f02, #0f0a01);
            --card-bg: rgba(67, 50, 10, 0.45);
            --card-border: rgba(234, 179, 8, 0.25);
            --text-primary: #fefdf0;
            --text-secondary: #fde047;
            --accent-color: #eab308;
            --accent-glow: rgba(234, 179, 8, 0.4);
            --orb-1: rgba(234, 179, 8, 0.25);
            --orb-2: rgba(253, 224, 71, 0.1);
        ";
        break;
    case 'red':
        $theme_vars = "
            --bg-gradient: linear-gradient(135deg, #3f070c, #140204);
            --card-bg: rgba(84, 16, 22, 0.45);
            --card-border: rgba(239, 68, 68, 0.25);
            --text-primary: #fef2f2;
            --text-secondary: #fca5a5;
            --accent-color: #ef4444;
            --accent-glow: rgba(239, 68, 68, 0.4);
            --orb-1: rgba(239, 68, 68, 0.25);
            --orb-2: rgba(248, 113, 113, 0.1);
        ";
        break;
    case 'black':
        $theme_vars = "
            --bg-gradient: linear-gradient(135deg, #18181b, #09090b);
            --card-bg: rgba(39, 39, 42, 0.55);
            --card-border: rgba(161, 161, 170, 0.25);
            --text-primary: #fafafa;
            --text-secondary: #d4d4d8;
            --accent-color: #a1a1aa;
            --accent-glow: rgba(161, 161, 170, 0.3);
            --orb-1: rgba(82, 82, 91, 0.35);
            --orb-2: rgba(39, 39, 42, 0.2);
        ";
        break;
    default:
        $theme_vars = "
            --bg-gradient: linear-gradient(135deg, #0f172a, #020617);
            --card-bg: rgba(30, 41, 59, 0.45);
            --card-border: rgba(148, 163, 184, 0.2);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-color: #3b82f6;
            --accent-glow: rgba(59, 130, 246, 0.4);
            --orb-1: rgba(59, 130, 246, 0.25);
            --orb-2: rgba(147, 197, 253, 0.1);
        ";
        break;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Heat Index Safety Dashboard</title>
    
    <!-- Premium Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            <?= $theme_vars ?>
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-gradient);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            padding: 2rem 1rem;
        }

        /* Ambient Glowing Background Orbs */
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            z-index: 1;
            pointer-events: none;
            animation: float 25s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 450px;
            height: 450px;
            background: var(--orb-1);
            top: -10%;
            left: -10%;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: var(--orb-2);
            bottom: -10%;
            right: -10%;
            animation-delay: -7s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, 60px) scale(1.1); }
            100% { transform: translate(-20px, -30px) scale(0.95); }
        }

        /* Glassmorphic Container */
        .dashboard-container {
            width: 100%;
            max-width: 540px;
            z-index: 10;
            position: relative;
        }

        .dashboard-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: var(--transition-smooth);
        }

        /* App Title */
        .app-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .system-tag {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        /* Temperature Display */
        .temp-display-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.75rem;
        }

        .temp-display {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 5rem;
            line-height: 1;
            background: linear-gradient(180deg, #ffffff 30%, var(--text-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-flex;
            filter: drop-shadow(0 0 20px var(--accent-glow));
        }

        .temp-unit {
            font-size: 2rem;
            align-self: flex-start;
            margin-top: 0.75rem;
            -webkit-text-fill-color: var(--text-primary);
        }

        /* Status Badges */
        .status-row {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-primary);
        }

        .status-badge.flag-badge {
            border-color: var(--card-border);
            background: rgba(255, 255, 255, 0.05);
        }

        .flag-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--accent-color);
            box-shadow: 0 0 10px var(--accent-glow);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.6; }
            50% { transform: scale(1.15); opacity: 1; box-shadow: 0 0 14px var(--accent-color); }
            100% { transform: scale(0.95); opacity: 0.6; }
        }

        /* Safety Guide Box */
        .safety-guide {
            background: rgba(255, 255, 255, 0.02);
            border-left: 4px solid var(--accent-color);
            border-radius: 4px 16px 16px 4px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
        }

        .guide-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .guide-text {
            font-size: 0.95rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.85);
        }

        /* Timestamps section */
        .timestamp-section {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .timestamp-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
        }

        .timestamp-label {
            color: var(--text-secondary);
        }

        .timestamp-value {
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Interactive Refresh Button */
        .refresh-btn {
            width: 100%;
            background: var(--accent-color);
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            border: none;
            border-radius: 14px;
            padding: 1rem 1.5rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            box-shadow: 0 8px 20px var(--accent-glow);
            transition: var(--transition-smooth);
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px var(--accent-glow);
            filter: brightness(1.1);
        }

        .refresh-btn:active {
            transform: translateY(1px);
        }

        .refresh-icon {
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .refresh-btn:hover .refresh-icon {
            transform: rotate(180deg);
        }

        .refreshing .refresh-icon {
            animation: spin 1s linear infinite !important;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Alert and Error Pages */
        .warning-card {
            text-align: center;
        }

        .warning-icon-wrapper {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.25);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.15);
        }

        .warning-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #ef4444;
        }

        .warning-desc {
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        /* JavaScript disabled alert style */
        .no-script {
            background: rgba(234, 88, 12, 0.1);
            border: 1px solid rgba(234, 88, 12, 0.25);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: #f97316;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.05);
            backdrop-filter: blur(8px);
            z-index: 20;
            position: relative;
        }

        /* Location Info under title if any */
        .location-info {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <!-- Background Blur Orbs -->
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>

    <div class="dashboard-container">
        
        <!-- JavaScript Alert -->
        <noscript>
            <div class="no-script">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm-1.02 5.074a.07.07 0 0 0-.066.076l.47 3.32a.077.077 0 0 0 .074.067h.842a.077.077 0 0 0 .074-.067l.47-3.32a.07.07 0 0 0-.066-.076h-.848zm.87 5.624a.55.55 0 1 0 0-1.1.55.55 0 0 0 0 1.1z"/>
                </svg>
                JavaScript is disabled. Some dynamic components may not refresh automatically.
            </div>
        </noscript>

        <!-- Main Dashboard Card -->
        <section id="heat-index-content" class="dashboard-card">
            <header class="app-header">
                <span class="system-tag">Heat Index Safety System</span>
                <?php if(isset($_GET["location"])): ?>
                    <p class="location-info">Query location: <?= htmlspecialchars(ucfirst($_GET["location"])) ?></p>
                <?php endif; ?>
            </header>

            <div class="temp-display-container">
                <div class="temp-display">
                    <?php if($temperature !== null): ?>
                        <?= htmlspecialchars(round($temperature)) ?><span class="temp-unit">&deg;F</span>
                    <?php else: ?>
                        --<span class="temp-unit">&deg;F</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="status-row">
                <div class="status-badge">
                    <!-- Location Marker SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                    </svg>
                    <?= htmlspecialchars(ucfirst($current_location)) ?>
                </div>
                <div class="status-badge flag-badge">
                    <span class="flag-dot"></span>
                    <?= htmlspecialchars(ucfirst($color ?: 'No Active')) ?> Flag
                </div>
            </div>

            <article class="safety-guide">
                <h3 class="guide-title">Safety Protocol</h3>
                <p class="guide-text"><?= htmlspecialchars($description) ?></p>
            </article>

            <div class="timestamp-section">
                <div class="timestamp-row">
                    <span class="timestamp-label">Weather.gov Refreshed:</span>
                    <span class="timestamp-value"><?= htmlspecialchars($temperature_timestamp ?: 'N/A') ?></span>
                </div>
                <div class="timestamp-row">
                    <span class="timestamp-label">Dashboard Refreshed:</span>
                    <span class="timestamp-value" id="refreshed-page-at">Loading...</span>
                </div>
            </div>

            <button class="refresh-btn" onclick="triggerRefresh(this)">
                <!-- Arrow Repeat SVG -->
                <svg class="refresh-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                    <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                </svg>
                Refresh Dashboard
            </button>
        </section>

        <!-- Stale Data Warning Card -->
        <section id="mandatory-refresh-content" class="dashboard-card warning-card" style="display: none;">
            <div class="warning-icon-wrapper">
                <!-- Exclamation Triangle SVG -->
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm-1.02 5.074a.07.07 0 0 0-.066.076l.47 3.32a.077.077 0 0 0 .074.067h.842a.077.077 0 0 0 .074-.067l.47-3.32a.07.07 0 0 0-.066-.076h-.848zm.87 5.624a.55.55 0 1 0 0-1.1.55.55 0 0 0 0 1.1z"/>
                </svg>
            </div>
            <h2 class="warning-title">Stale Data Alert</h2>
            <p class="warning-desc">This dashboard has been inactive, and the displayed data is more than 5 minutes old. Please refresh to ensure compliance with safety protocols.</p>
            <button class="refresh-btn" onclick="window.location.reload()">
                Refresh Dashboard
            </button>
        </section>

    </div>

    <script>
    // Automatically trigger refresh warning after 5 minutes
    setTimeout(() => {
            document.getElementById("mandatory-refresh-content").style.display = "block"
            document.getElementById("heat-index-content").style.display = "none"
        },
        5 * 60 * 1000
    );

    // Track execution of button refresh
    function triggerRefresh(btn) {
        btn.classList.add('refreshing');
        setTimeout(() => {
            window.location.reload();
        }, 400);
    }

    // Initialize Page Load Timestamps
    function onLoad() {
        var refreshedSpan = document.getElementById("refreshed-page-at")
        if (refreshedSpan) {
            var now = new Date()
            refreshedSpan.innerText = `${now.toLocaleDateString("en-US", {
                weekday: "short",
                month: "short",
                day: "numeric",
                hour: "numeric",
                minute: "2-digit",
                second: "2-digit",
                hour12: true,
                timeZone: "America/New_York"
            })} EDT`
        }
    }
    
    window.addEventListener('DOMContentLoaded', onLoad);
    </script>
</body>
</html>