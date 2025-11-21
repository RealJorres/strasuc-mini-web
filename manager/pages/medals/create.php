<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Fetch participants, schedules, and teams
$participantsResponse = $apiClient->get('/participants');
$schedulesResponse    = $apiClient->get('/schedules');
$teamsResponse        = $apiClient->get('/teams');

$participants = $participantsResponse['success'] ? $participantsResponse['data'] : [];
$schedules    = $schedulesResponse['success'] ? $schedulesResponse['data'] : [];
$teams        = $teamsResponse['success'] ? $teamsResponse['data'] : [];

$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'participant_id' => sanitizeInput($_POST['participant_id']),
        'schedule_id'    => sanitizeInput($_POST['schedule_id']),
        'medal_type'     => sanitizeInput($_POST['medal_type']),
        'medal_count'    => sanitizeInput($_POST['medal_count'])
    ];

    $response = $apiClient->post('/medals', $data);

    if ($response['success']) {
        $_SESSION['message'] = ['text' => 'Medal awarded successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $response['error']['message'] ?? 'Failed to award medal';
        $alert = displayAlert($error, 'error');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-plus me-2"></i>Award Medal</h4>
            </div>

            <div class="card-body">
                <?php echo $alert; ?>

                <form method="POST" id="medalForm">

                    <!-- EVENT DROPDOWN -->
                    <div class="mb-3">
                        <label for="schedule_id" class="form-label">Event *</label>

                        <?php 
                        if ($_SESSION['user']['role'] !== 'admin') {
                            $schedules = array_filter(
                                $schedules,
                                fn($s) => $s['sport_id'] == $_SESSION['user']['sports_id']
                            );
                        }
                        ?>

                        <select class="form-select" id="schedule_id" name="schedule_id" required>
                            <option value="">Select Event</option>
                            <?php foreach ($schedules as $schedule): ?>
                                <option value="<?php echo $schedule['id']; ?>">
                                    <?php echo htmlspecialchars($schedule['event_name']); ?> -
                                    <?php echo formatDate($schedule['date']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- PARTICIPANT DROPDOWN -->
                    <div class="mb-3">
                        <label for="participant_id" class="form-label">Participant *</label>

                        <?php 
                        if ($_SESSION['user']['role'] !== 'admin') {
                            $teams = array_filter(
                                $teams,
                                fn($t) => $t['sport_id'] == $_SESSION['user']['sports_id']
                            );

                            $team_ids = array_column($teams, 'id');

                            $participants = array_filter(
                                $participants,
                                fn($p) => in_array($p['team_id'], $team_ids)
                            );
                        }
                        ?>

                        <select class="form-select" id="participant_id" name="participant_id" required>
                            <option value="">Select Participant</option>
                            <?php foreach ($participants as $participant): ?>
                                <option value="<?php echo $participant['id']; ?>">
                                    <?php echo htmlspecialchars($participant['team_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- MEDAL TYPE -->
                    <div class="mb-3">
                        <label for="medal_type" class="form-label">Medal Type *</label>
                        <select class="form-select" id="medal_type" name="medal_type" required>
                            <option value="">Select Medal Type</option>
                            <option value="gold">Gold</option>
                            <option value="silver">Silver</option>
                            <option value="bronze">Bronze</option>
                        </select>
                    </div>

                    <!-- MEDAL COUNT -->
                    <div class="mb-3">
                        <label for="medal_count" class="form-label">Medal Count *</label>
                        <input type="number" 
                               class="form-control" 
                               id="medal_count" 
                               name="medal_count" 
                               required 
                               min="1"
                               placeholder="How many medals? (e.g., 5)">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-trophy me-1"></i>Award Medal
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
