<?php
session_start();

echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
echo '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">';


// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once '../manager/config/config.php';
require_once '../manager/includes/api_client.php';
require_once '../manager/includes/functions.php';

class SportsViewerData
{
    private $apiClient;

    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function fetchData($endpoint)
    {
        try {
            $response = $this->apiClient->get($endpoint);
            return $response['success'] ? ($response['data'] ?? []) : [];
        } catch (Exception $e) {
            error_log("Error fetching {$endpoint}: " . $e->getMessage());
            return [];
        }
    }

    public function getAllData()
    {
        $endpoints = [
            'universities' => '/universities',
            'medalTally' => '/medals/tally',
            'schedules' => '/schedules',
            'sports' => '/sports',
            'participants' => '/participants',
            'teams' => '/teams',
            'scores' => '/scores'
        ];

        $data = [];
        foreach ($endpoints as $key => $endpoint) {
            $data[$key] = $this->fetchData($endpoint);
        }

        return $data;
    }
}

// Initialize and fetch data
$dataFetcher = new SportsViewerData($apiClient);
$appData = $dataFetcher->getAllData();

// Helper function to escape output
function escape($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// Get selected university from query parameter
$selectedUniversity = $_GET['university'] ?? 'all';

// Process medal tally data for initial render
$processedMedalTally = [];
if (!empty($appData['medalTally'])) {
    $processedMedalTally = array_map(function ($tally) use ($appData) {
        $university = array_filter($appData['universities'], function ($u) use ($tally) {
            return ($u['id'] == $tally['university_id']) ||
                ($u['name'] == $tally['university_name']) ||
                ($u['abbreviation'] == $tally['university_name']);
        });
        $university = !empty($university) ? reset($university) : null;

        return [
            'university_id' => $tally['university_id'] ?? $tally['universityId'] ?? $tally['id'] ?? null,
            'university_name' => $tally['university_name'] ?? $tally['universityName'] ?? $tally['name'] ?? '',
            'university' => $university,
            'golds' => intval($tally['golds'] ?? $tally['gold'] ?? $tally['gold_count'] ?? 0),
            'silvers' => intval($tally['silvers'] ?? $tally['silver'] ?? $tally['silver_count'] ?? 0),
            'bronzes' => intval($tally['bronzes'] ?? $tally['bronze'] ?? $tally['bronze_count'] ?? 0)
        ];
    }, $appData['medalTally']);

    // Sort by gold -> silver -> bronze
    usort($processedMedalTally, function ($a, $b) {
        if ($b['golds'] !== $a['golds']) return $b['golds'] - $a['golds'];
        if ($b['silvers'] !== $a['silvers']) return $b['silvers'] - $a['silvers'];
        return $b['bronzes'] - $a['bronzes'];
    });
}

// Function to get participants for a schedule
function getScheduleParticipants($schedule, $participants, $teams, $universities)
{
    $scheduleParticipants = array_filter($participants, function ($p) use ($schedule) {
        return $p['schedule_id'] == $schedule['id'];
    });

    $participantUniversities = [];
    foreach ($scheduleParticipants as $participant) {
        if ($participant['team_id']) {
            $team = array_filter($teams, function ($t) use ($participant) {
                return $t['id'] == $participant['team_id'];
            });
            $team = !empty($team) ? reset($team) : null;

            if ($team && $team['university_id']) {
                $university = array_filter($universities, function ($u) use ($team) {
                    return $u['id'] == $team['university_id'];
                });
                $university = !empty($university) ? reset($university) : null;

                if ($university) {
                    $participantUniversities[$university['id']] = $university;
                }
            }
        }
    }

    return $participantUniversities;
}

// Process schedules for initial render with university filtering
$processedSchedules = [
    'ongoing' => [],
    'upcoming' => [],
    'all' => []
];

if (!empty($appData['schedules'])) {
    foreach ($appData['schedules'] as $schedule) {
        // Get universities participating in this schedule
        $participantUniversities = getScheduleParticipants(
            $schedule,
            $appData['participants'] ?? [],
            $appData['teams'] ?? [],
            $appData['universities'] ?? []
        );

        $schedule['participant_universities'] = $participantUniversities;

        // Apply university filter
        $showSchedule = true;
        if ($selectedUniversity !== 'all') {
            $showSchedule = array_key_exists($selectedUniversity, $participantUniversities);
        }

        if ($showSchedule) {
            $processedSchedules['all'][] = $schedule;

            if (($schedule['status'] ?? '') === 'ongoing') {
                $processedSchedules['ongoing'][] = $schedule;
            } elseif (($schedule['status'] ?? '') === 'scheduled') {
                $processedSchedules['upcoming'][] = $schedule;
            }
        }
    }
}

// Prepare data for JavaScript
$jsonData = json_encode([
    'universities' => $appData['universities'] ?? [],
    'medalTally' => $appData['medalTally'] ?? [],
    'schedules' => $appData['schedules'] ?? [],
    'sports' => $appData['sports'] ?? [],
    'participants' => $appData['participants'] ?? [],
    'teams' => $appData['teams'] ?? [],
    'scores' => $appData['scores'] ?? []
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STRASUC - Sports Festival Viewer</title>
    <link rel="shortcut icon" href="../manager/assets/img/icon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .sport-image {
            width: 50px;
            height: 50px;
            padding: 10px;
            object-fit: contain;
        }

        .university-logo img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .medal-count {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 8px;
        }

        .medal-badge {
            display: inline-block;
            min-width: 36px;
            text-align: center;
            padding: 6px 8px;
            border-radius: 6px;
            color: #fff;
            font-weight: 700;
        }

        .medal-gold {
            background: #FFD700;
        }

        .medal-silver {
            background: #C0C0C0;
        }

        .medal-bronze {
            background: #CD7F32;
        }

        .status-indicator {
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 700;
            color: #fff;
        }

        .status-ongoing {
            background: #dc3545;
        }

        .status-scheduled {
            background: #0d6efd;
        }

        .status-cancelled {
            background: #6c757d;
        }

        .status-ended {
            background: #198754;
        }

        .loading-spinner {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-top-color: rgba(0, 0, 0, 0.25);
            display: inline-block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .university-badge {
            font-size: 0.75rem;
            margin: 1px;
        }

        .filter-active {
            background-color: #0d6efd !important;
            color: white !important;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-white" href="">
                <div class="header-logo me-3">
                    <img src="../manager/assets/img/main_logo_small.png" alt="main_logo" class="header-image">
                </div>
            </a>
            <div class="navbar-nav flex-row gap-2">
                <a class="nav-link" href="#medal-tally">Medal Tally</a>
                <a class="nav-link" href="#live-scores">Live Scores</a>
                <a class="nav-link" href="#schedules">Schedules</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">

        <!-- Medal Tally Section -->
        <section id="medal-tally" class="mb-5">
            <h2 class="section-title">
                <i class="fas fa-trophy me-2"></i>University Medal Tally
            </h2>
            <div class="row g-3" id="medalTallyContainer">
                <?php if (empty($processedMedalTally)): ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-trophy text-muted mb-3" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-0">No medal data available</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($processedMedalTally as $index => $tally): ?>
                        <div class="col-md-6 col-lg-4 fade-in">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="university-logo me-3">
                                            <?php if ($tally['university']): ?>
                                                <img src="../manager/assets/img/sucs_logo/<?= escape($tally['university']['abbreviation'] ?? $tally['university']['id'] ?? 'unknown') ?>.png"
                                                    alt="<?= escape($tally['university']['name']) ?>"
                                                    class="sport-image"
                                                    onerror="this.src='../manager/assets/img/sports_icon/default.png'">
                                            <?php else: ?>
                                                🏀
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold">
                                                <?= escape($tally['university']['name'] ?? $tally['university_name']) ?>
                                            </h6>
                                            <small class="text-muted"><?= escape($tally['university']['team'] ?? '') ?></small>
                                        </div>
                                        <div class="badge bg-primary">#<?= $index + 1 ?></div>
                                    </div>

                                    <div class="medal-count">
                                        <span class="medal-badge medal-gold"><?= $tally['golds'] ?></span>
                                        <span class="medal-badge medal-silver"><?= $tally['silvers'] ?></span>
                                        <span class="medal-badge medal-bronze"><?= $tally['bronzes'] ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Live Scores Section -->
        <section id="live-scores" class="mb-5">
            <h2 class="section-title">
                <i class="fas fa-running me-2"></i>Live Scores
            </h2>
            <div class="row g-3" id="liveScoresContainer">
                <div class="col-12 text-center">
                    <div class="loading-spinner me-2"></div>
                    Loading live scores...
                </div>
            </div>
        </section>

        <!-- Schedules Section -->
        <section id="schedules" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title mb-0">
                    <i class="fas fa-calendar me-2"></i>Match Schedules
                </h2>

                <!-- University Filter -->
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="universityFilter" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-filter me-2"></i>
                        <?php if ($selectedUniversity === 'all'): ?>
                            All Universities
                        <?php else: ?>
                            <?php
                            $selectedUni = array_filter($appData['universities'] ?? [], function ($u) use ($selectedUniversity) {
                                return $u['id'] == $selectedUniversity;
                            });
                            $selectedUni = !empty($selectedUni) ? reset($selectedUni) : null;
                            echo $selectedUni ? escape($selectedUni['name']) : 'Selected University';
                            ?>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="universityFilter">
                        <li>
                            <a class="dropdown-item <?= $selectedUniversity === 'all' ? 'active' : '' ?>"
                                href="?university=all">
                                <i class="fas fa-university me-2"></i>All Universities
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <?php foreach ($appData['universities'] ?? [] as $university): ?>
                            <li>
                                <a class="dropdown-item <?= $selectedUniversity == $university['id'] ? 'active' : '' ?>"
                                    href="?university=<?= $university['id'] ?>">
                                    <img src="../manager/assets/img/sucs_logo/<?= escape($university['abbreviation'] ?? $university['id'] ?? 'unknown') ?>.png"
                                        alt="<?= escape($university['name']) ?>"
                                        class="sport-image me-2"
                                        style="width: 20px; height: 20px;"
                                        onerror="this.src='../manager/assets/img/sports_icon/default.png'">
                                    <?= escape($university['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Current Matches -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-play-circle me-2"></i>Ongoing Now</span>
                            <span class="badge bg-danger"><?= count($processedSchedules['ongoing']) ?></span>
                        </div>
                        <div class="card-body" id="ongoingMatches">
                            <?php if (empty($processedSchedules['ongoing'])): ?>
                                <p class="text-muted text-center mb-0">No matches in progress</p>
                            <?php else: ?>
                                <?php foreach ($processedSchedules['ongoing'] as $schedule): ?>
                                    <?php
                                    $sport = array_filter($appData['sports'] ?? [], function ($s) use ($schedule) {
                                        return $s['id'] == $schedule['sport_id'];
                                    });
                                    $sport = !empty($sport) ? reset($sport) : null;
                                    ?>
                                    <div class="d-flex align-items-center mb-2 p-2 border rounded fade-in">
                                        <div class="sport-icon me-2">
                                            <?php if ($sport): ?>
                                                <img src="../manager/assets/img/sports_icon/<?= strtolower(str_replace(' ', '_', $sport['name'])) ?>.png"
                                                    class="sport-image"
                                                    alt="<?= escape($sport['name']) ?>"
                                                    onerror="this.src='../manager/assets/img/sports_icon/default.png'">
                                            <?php else: ?>
                                                🏀
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?= escape($schedule['event_name']) ?></div>
                                            <small class="text-muted">
                                                <?= date('H:i', strtotime($schedule['date'])) ?> • <?= escape($schedule['venue']) ?>
                                            </small>
                                            <div class="mt-1">
                                                <?php foreach ($schedule['participant_universities'] as $uni): ?>
                                                    <span class="badge bg-secondary university-badge">
                                                        <?= escape($uni['abbreviation'] ?? $uni['name']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-clock me-2"></i>Starting Soon</span>
                            <span class="badge bg-primary"><?= count($processedSchedules['upcoming']) ?></span>
                        </div>
                        <div class="card-body" id="upcomingMatches">
                            <?php if (empty($processedSchedules['upcoming'])): ?>
                                <p class="text-muted text-center mb-0">No upcoming matches</p>
                            <?php else: ?>
                                <?php foreach ($processedSchedules['upcoming'] as $schedule): ?>
                                    <?php
                                    $sport = array_filter($appData['sports'] ?? [], function ($s) use ($schedule) {
                                        return $s['id'] == $schedule['sport_id'];
                                    });
                                    $sport = !empty($sport) ? reset($sport) : null;
                                    ?>
                                    <div class="d-flex align-items-center mb-2 p-2 border rounded fade-in">
                                        <div class="sport-icon me-2">
                                            <?php if ($sport): ?>
                                                <img src="../manager/assets/img/sports_icon/<?= strtolower(str_replace(' ', '_', $sport['name'])) ?>.png"
                                                    class="sport-image"
                                                    alt="<?= escape($sport['name']) ?>"
                                                    onerror="this.src='../manager/assets/img/sports_icon/default.png'">
                                            <?php else: ?>
                                                🏀
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?= escape($schedule['event_name']) ?></div>
                                            <small class="text-muted">
                                                <?= date('H:i', strtotime($schedule['date'])) ?> • <?= escape($schedule['venue']) ?>
                                            </small>
                                            <div class="mt-1">
                                                <?php foreach ($schedule['participant_universities'] as $uni): ?>
                                                    <span class="badge bg-secondary university-badge">
                                                        <?= escape($uni['abbreviation'] ?? $uni['name']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Schedules Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-2"></i>Complete Schedule</span>
                    <span class="badge bg-dark"><?= count($processedSchedules['all']) ?> matches</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Sport</th>
                                    <th>Event</th>
                                    <th>Universities</th>
                                    <th>Date & Time</th>
                                    <th>Venue</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="allSchedulesTable">
                                <?php if (empty($processedSchedules['all'])): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No schedule data available
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($processedSchedules['all'] as $schedule): ?>
                                        <?php
                                        $sport = array_filter($appData['sports'] ?? [], function ($s) use ($schedule) {
                                            return $s['id'] == $schedule['sport_id'];
                                        });
                                        $sport = !empty($sport) ? reset($sport) : null;
                                        $statusClass = 'status-' . ($schedule['status'] ?? 'scheduled');
                                        ?>
                                        <tr class="fade-in">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="sport-icon me-2">
                                                        <?php if ($sport): ?>
                                                            <img src="../manager/assets/img/sports_icon/<?= strtolower(str_replace(' ', '_', $sport['name'])) ?>.png"
                                                                class="sport-image"
                                                                alt="<?= escape($sport['name']) ?>"
                                                                onerror="this.src='../manager/assets/img/sports_icon/default.png'">
                                                        <?php else: ?>
                                                            🏀
                                                        <?php endif; ?>
                                                    </div>
                                                    <?= escape($sport['name'] ?? 'Unknown Sport') ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?= escape($schedule['event_name']) ?></div>
                                                <small class="text-muted">
                                                    <?= escape($schedule['category'] ?? '') ?> • <?= escape($schedule['round'] ?? '') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php foreach ($schedule['participant_universities'] as $uni): ?>
                                                    <span class="badge bg-light text-dark university-badge border"
                                                        title="<?= escape($uni['name']) ?>">
                                                        <?= escape($uni['abbreviation'] ?? substr($uni['name'], 0, 3)) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </td>
                                            <td>
                                                <?= date('M j, Y H:i', strtotime($schedule['date'])) ?>
                                            </td>
                                            <td><a href="#!"><?= escape($schedule['venue']) ?></a></td>
                                            <td>
                                                <span class="status-indicator <?= $statusClass ?>">
                                                    <?= strtoupper($schedule['status'] ?? 'SCHEDULED') ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-4">
        <div class="container text-center">
            <p class="mb-1">&copy; <?= date('Y') ?> STRASUC Sports Festival</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global data storage with server-side data
        let appData = <?= $jsonData ?>;

        // API Base URL
        const API_BASE_URL = 'http://localhost:5000/api/v1';

        // Helper to normalize API responses
        async function safeFetchJson(url) {
            const resp = await fetch(url);
            if (!resp.ok) throw new Error(`HTTP ${resp.status} for ${url}`);
            const json = await resp.json();
            if (json && (json.success === true || json.success === false) && json.hasOwnProperty('data')) return json.data;
            return json;
        }

        // API Functions
        async function fetchMedalTally() {
            try {
                return await safeFetchJson(`${API_BASE_URL}/medals/tally`);
            } catch (error) {
                console.error('Error fetching medal tally:', error);
                return [];
            }
        }

        async function fetchSchedules() {
            try {
                return await safeFetchJson(`${API_BASE_URL}/schedules`);
            } catch (error) {
                console.error('Error fetching schedules:', error);
                return [];
            }
        }

        async function fetchLiveScores() {
            try {
                return await safeFetchJson(`${API_BASE_URL}/scores`);
            } catch (error) {
                console.error('Error fetching live scores:', error);
                return [];
            }
        }

        // Initialize app
        document.addEventListener('DOMContentLoaded', async function() {
            await loadAllData();
            startAutoRefresh();
        });

        // Load all data
        async function loadAllData() {
            try {
                const [medalTally, schedules, scores] = await Promise.all([
                    fetchMedalTally(),
                    fetchSchedules(),
                    fetchLiveScores()
                ]);

                // Update app data
                appData.medalTally = Array.isArray(medalTally) ? medalTally : [];
                appData.schedules = Array.isArray(schedules) ? schedules : [];
                appData.liveScores = Array.isArray(scores) ? scores : [];

                // Render components that need client-side updates
                loadLiveScores();

            } catch (error) {
                console.error('Error loading data:', error);
            }
        }

        // Load Live Scores (client-side only)
        function loadLiveScores() {
            const container = document.getElementById('liveScoresContainer');
            const ongoingSchedules = appData.schedules.filter(s => s.status === 'ongoing');

            if (ongoingSchedules.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-clock text-muted mb-3" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-0">No live matches at the moment</p>
                            </div>
                        </div>
                    </div>`;
                return;
            }

            container.innerHTML = ongoingSchedules.map(schedule => {
                const sport = appData.sports.find(s => Number(s.id) === Number(schedule.sport_id));
                const scoresForSchedule = appData.liveScores.filter(sc => Number(sc.schedule_id) === Number(schedule.id));
                scoresForSchedule.sort((a, b) => Number(b.score) - Number(a.score));

                const rowsHtml = scoresForSchedule.length > 0 ? scoresForSchedule.map(sc => {
                    const participant = appData.participants.find(p => Number(p.id) === Number(sc.participant_id)) || {};

                    // Get university team name
                    const team = appData.teams?.find(t => Number(t.id) === Number(participant.team_id));
                    const university = team ? appData.universities?.find(u => Number(u.id) === Number(team.university_id)) : null;
                    const teamName = university?.team || participant.name || 'Team';
                    const uniName = university?.name || '';

                    return `
                        <div class="row align-items-center mb-2">
                            <div class="col-8 text-start">
                                <div class="fw-medium">${escapeHtml(teamName)}</div>
                                <small class="text-muted">${escapeHtml(uniName)}</small>
                            </div>
                            <div class="col-4 text-end">
                                <div class="fs-5 fw-bold">${Number(sc.score)}</div>
                            </div>
                        </div>
                    `;
                }).join('') : `<div class="text-center text-muted">Scores not available</div>`;

                return `
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="sport-icon me-2">
                                        ${sport ? `<img src="../manager/assets/img/sports_icon/${(sport.name||'').toLowerCase().replace(/\s+/g,'_')}.png" class="sport-image" alt="${escapeHtml(sport.name)}">` : ''}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-bold">${escapeHtml(schedule.event_name)}</h6>
                                        <small class="text-muted">${escapeHtml(schedule.venue)}</small>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">${escapeHtml(schedule.category)}</small>
                                            <small class="text-muted">${escapeHtml(schedule.round)}</small>
                                        </div>
                                    </div>
                                    <span class="status-indicator status-ongoing">LIVE</span>
                                </div>

                                ${rowsHtml}

                                <div class="text-center mt-2">
                                    <small class="text-muted">Match time: ${formatTime(schedule.date)}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Helper: escape basic HTML for safety
        function escapeHtml(s) {
            if (!s) return '';
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Auto-refresh
        function startAutoRefresh() {
            setInterval(async () => {
                console.log('Refreshing data...');
                await loadAllData();
            }, 15000);
        }

        // Utility functions
        function formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatTime(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>

</html>