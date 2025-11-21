<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: login.php');
    exit();
}

require_once 'config/config.php';
require_once 'includes/header-home.php';

// Fetch data for dashboard
$universities = $apiClient->get('/universities');
$sports = $apiClient->get('/sports');
$teams = $apiClient->get('/teams');
$schedules = $apiClient->get('/schedules');
$medals = $apiClient->get('/medals'); // Fetch all medals

// Compute medal tally
// Compute medal tally
$medalTally = [];
if ($medals['success'] && !empty($medals['data'])) {
    foreach ($medals['data'] as $m) {
        $u = $m['university_name'];
        $u_a = $universities['data'][$m['university_id']]['abbreviation'];
        $type = strtolower($m['medal_type']);
        $count = (int) $m['medal_count'];

        if (!isset($medalTally[$u])) {
            $medalTally[$u] = [
                'university_name' => $u,
                'university_acronym' => $u_a,
                'golds' => 0,
                'silvers' => 0,
                'bronzes' => 0,
            ];
        }

        switch($type) {
            case 'gold':
                $medalTally[$u]['golds'] += $count;
                break;
            case 'silver':
                $medalTally[$u]['silvers'] += $count;
                break;
            case 'bronze':
                $medalTally[$u]['bronzes'] += $count;
                break;
        }
    }


    // Sort by gold → silver → bronze descending
    usort($medalTally, function($a, $b) {
        return $b['golds'] <=> $a['golds']
            ?: $b['silvers'] <=> $a['silvers']
            ?: $b['bronzes'] <=> $a['bronzes'];
    });
}

?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo count($universities['data'] ?? []); ?></h4>
                        <p class="mb-0">Universities</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-university fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo count($sports['data'] ?? []); ?></h4>
                        <p class="mb-0">Sports</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-baseball-ball fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo count($teams['data'] ?? []); ?></h4>
                        <p class="mb-0">Teams</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo count($schedules['data'] ?? []); ?></h4>
                        <p class="mb-0">Events</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-trophy me-2"></i>Medal Tally</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($medalTally)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>University</th>
                                    <th><i class="fas fa-medal text-warning"></i> Gold</th>
                                    <th><i class="fas fa-medal text-secondary"></i> Silver</th>
                                    <th><i class="fas fa-medal text-bronze"></i> Bronze</th>
                                    <th>Total Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medalTally as $tally): ?>
                                    <tr>
                                       <td><img src="./assets/img/sucs_logo/<?= $tally['university_acronym'] ?>.png" alt="University Logo" width=30px></td>
                                        <td><?php echo htmlspecialchars($tally['university_name']); ?></td>
                                        <td><?php echo $tally['golds']; ?></td>
                                        <td><?php echo $tally['silvers']; ?></td>
                                        <td><?php echo $tally['bronzes']; ?></td>
                                        <td><strong><?php echo $tally['golds'] + $tally['silvers'] + $tally['bronzes']; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No medal data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-calendar me-2"></i>Upcoming Events</h5>
            </div>
            <div class="card-body">
                <?php if ($schedules['success'] && !empty($schedules['data'])): ?>
                    <?php 
                    $upcoming = array_slice($schedules['data'], 0, 5);
                    foreach ($upcoming as $schedule): ?>
                        <?php if ($schedule['status'] === 'scheduled'): ?>
                            <div class="mb-3 pb-2 border-bottom">
                                <h5 class="mb-1"><?php echo htmlspecialchars($schedule['event_name']); ?></h5>
                                <h6 class="mb-1">Category: <?php echo htmlspecialchars($schedule['category']); ?></h6>
                                <h6 class="mb-1">Round: <?php echo htmlspecialchars($schedule['round']); ?></h6>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo formatDate($schedule['date']); ?>
                                </small><br>
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($schedule['venue']); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No upcoming events.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer-home.php'; ?>
