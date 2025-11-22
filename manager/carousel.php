<?php
session_start();

require_once '../manager/config/config.php';
require_once '../manager/includes/api_client.php';
require_once '../manager/includes/functions.php';

class ScheduleCarouselData
{
    private $apiClient;

    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function fetchSchedules()
    {
        try {
            $response = $this->apiClient->get('/schedules');
            return $response['success'] ? ($response['data'] ?? []) : [];
        } catch (Exception $e) {
            error_log("Error fetching schedules: " . $e->getMessage());
            return [];
        }
    }

    public function fetchSports()
    {
        try {
            $response = $this->apiClient->get('/sports');
            return $response['success'] ? ($response['data'] ?? []) : [];
        } catch (Exception $e) {
            error_log("Error fetching sports: " . $e->getMessage());
            return [];
        }
    }
}

$dataFetcher = new ScheduleCarouselData($apiClient);

$schedules = $dataFetcher->fetchSchedules();
$sports = $dataFetcher->fetchSports();

function getSport($sports, $id)
{
    foreach ($sports as $s) {
        if ($s['id'] == $id) return $s;
    }
    return null;
}

function esc($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>STRASUC — Schedule Carousel (TV Mode)</title>

    <!-- Poppins (Ultra minimal look, as requested) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap minimal -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #660033;   /* your primary */
            --secondary: #fcc300; /* your secondary */

            /* soft muted variants for status badges (low saturation + opacity) */
            --muted-ongoing: rgba(252, 195, 0, 0.92);   /* soft yellow (secondary) */
            --muted-scheduled: rgba(123, 174, 255, 0.92);/* soft blue */
            --muted-ended: rgba(118, 221, 154, 0.92);    /* soft green */
            --muted-cancelled: rgba(170,170,170,0.92);   /* soft gray */
        }

        /* Page basics for TV / full-screen */
        html, body {
            height: 100%;
            margin: 0;
            background: #000;
            color: #fff;
            font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow: hidden; /* keep it full-screen */
        }

        /* Carousel occupies full viewport */
        .carousel, .carousel-inner, .carousel-item {
            height: 100vh;
        }

        .carousel-item {
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 900ms ease-in-out;
        }

        /* Subtle fade animation when slide becomes active */
        .carousel-item .content {
            opacity: 0;
            transform: translateY(6px);
            transition: opacity 700ms ease, transform 700ms ease;
        }

        .carousel-item.active .content {
            opacity: 1;
            transform: translateY(0);
        }

        /* Card — very clean, minimal */
        .schedule-card {
            width: min(1200px, 92vw);
            padding: 48px 56px;
            text-align: center;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
            backdrop-filter: blur(2px);
            border: 2px solid rgba(255,255,255,0.04);
            box-shadow: none; /* minimal */
        }

        /* Sport icon — generous size but simple */
        .sport-icon img {
            width: 128px;
            height: 128px;
            object-fit: contain;
            display: inline-block;
            margin-bottom: 18px;
            filter: drop-shadow(0 0 20px rgba(251, 255, 1, 1));
        }

        /* Event title: large but light */
        .event-title {
            font-size: clamp(36px, 6.2vw, 72px);
            font-weight: 300;
            line-height: 1.02;
            color: #fff;
            margin: 8px 0 6px;
            letter-spacing: -0.01em;
        }

        /* Secondary info (sport name, venue) */
        .event-sub {
            font-size: clamp(18px, 2.6vw, 28px);
            font-weight: 300;
            color: rgba(255,255,255,0.92);
            margin-bottom: 6px;
        }

        .event-venue {
            font-size: clamp(16px, 2.2vw, 22px);
            color: rgba(255,255,255,0.78);
            margin-bottom: 8px;
        }

        /* Time text uses your secondary with subtle glow */
        .time-text {
            font-size: clamp(18px, 2.4vw, 26px);
            color: var(--secondary);
            font-weight: 600;
            margin-top: 10px;
            text-shadow: 0 0 10px rgba(252,195,0,0.12);
        }

        /* Status badge — soft muted palette and minimal shape */
        .status-badge {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 999px;
            font-weight: 600;
            font-size: clamp(14px, 1.8vw, 18px);
            color: #000;
            margin-top: 18px;
            letter-spacing: 0.02em;
        }

        .status-ongoing { background: var(--muted-ongoing); color: #000; }
        .status-scheduled { background: var(--muted-scheduled); color: #000; }
        .status-ended { background: var(--muted-ended); color: #000; }
        .status-cancelled { background: var(--muted-cancelled); color: #000; }

        /* Indicators & controls — keep minimal and subtly visible */
        .carousel-indicators {
            bottom: 18px;
        }
        .carousel-indicators [data-bs-target] {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.22);
            border: none;
        }
        .carousel-indicators .active {
            background: rgba(255,255,255,0.92);
        }
        .carousel-control-prev,
        .carousel-control-next {
            opacity: 0.001; /* hide controls for TV; accessible via remote if needed */
            pointer-events: none;
        }

        /* Extra breathing room on very large displays */
        @media (min-width: 1600px) {
            .schedule-card { padding: 64px 96px; width: 1200px; }
        }

        /* Accessibility: reduce motion if user prefers */
        @media (prefers-reduced-motion: reduce) {
            .carousel-item, .carousel-item .content { transition: none !important; transform: none !important; }
        }
    </style>
</head>

<body>

    <div id="scheduleCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="9000" data-bs-pause="false">

        <div class="carousel-inner">

            <?php if (empty($schedules)): ?>
                <div class="carousel-item active">
                    <div class="content">
                        <div class="schedule-card mx-auto">
                            <div class="sport-icon">
                                <!-- optional placeholder -->
                            </div>
                            <div class="event-title">No Schedules Available</div>
                            <div class="event-sub">Please check back shortly</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>

                <?php foreach ($schedules as $i => $s): ?>
                    <?php $sport = getSport($sports, $s['sport_id']); ?>
                    <?php
                        // normalize status class name and fallback
                        $status = strtolower(trim($s['status'] ?? 'scheduled'));
                        $statusClass = 'status-' . (in_array($status, ['ongoing','scheduled','ended','cancelled']) ? $status : 'scheduled');
                    ?>

                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                        <div class="content">
                            <div class="schedule-card mx-auto">

                                <div class="sport-icon">
                                    <?php if (!empty($sport['name'])): ?>
                                        <img src="../manager/assets/img/sports_icon/<?= esc(strtolower(str_replace(' ', '_', $sport['name']))) ?>.png"
                                             alt="<?= esc($sport['name']) ?>"
                                             onerror="this.src='../manager/assets/img/sports_icon/default.png'">
                                    <?php endif; ?>
                                </div>

                                <div class="event-title"><?= esc($s['event_name']) ?></div>

                                <div class="event-sub"><?= esc($sport['name'] ?? 'Unknown Sport') ?></div>

                                <div class="event-venue"><?= esc($s['venue']) ?></div>

                                <div class="time-text"><?= esc(date("M j, Y • h:i A", strtotime($s['date'] ?? '')) ) ?></div>

                                <div>
                                    <span class="status-badge <?= esc($statusClass) ?>">
                                        <?= esc(strtoupper($s['status'] ?? 'SCHEDULED')) ?>
                                    </span>
                                </div>

                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

        <!-- Indicators — minimal -->
        <?php if (count($schedules) > 1): ?>
            <div class="carousel-indicators">
                <?php foreach ($schedules as $idx => $_): ?>
                    <button type="button" data-bs-target="#scheduleCarousel" data-bs-slide-to="<?= $idx ?>" <?= $idx === 0 ? 'class="active" aria-current="true"' : '' ?> aria-label="Slide <?= $idx + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Auto-refresh page to pick up new schedule changes (keeps carousel stable between refreshes) -->
    <script>
        // Keep refresh separate from carousel interval. Refresh less often to avoid flicker.
        setInterval(function() {
            try {
                // Using location.reload(true) is deprecated; simple reload will suffice.
                window.location.reload();
            } catch (e) {
                console.error(e);
            }
        }, 10000); // refresh every 10 seconds
    </script>
</body>

</html>
