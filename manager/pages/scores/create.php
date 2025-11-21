<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Fetch participants and schedules for dropdowns
$participantsResponse = $apiClient->get('/participants');
$schedulesResponse = $apiClient->get('/schedules');
$teamsResponse = $apiClient->get('/teams');
$participants = $participantsResponse['success'] ? $participantsResponse['data'] : [];
$teams = $teamsResponse['success'] ? $teamsResponse['data'] : [];
$schedules = $schedulesResponse['success'] ? $schedulesResponse['data'] : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'participant_id' => sanitizeInput($_POST['participant_id']),
        'schedule_id' => sanitizeInput($_POST['schedule_id']),
        'score' => sanitizeInput($_POST['score'])
    ];

    $response = $apiClient->post('/scores', $data);
    
    if ($response['success']) {
        $_SESSION['message'] = ['text' => 'Score added successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $response['error']['message'] ?? 'Failed to add score';
        $alert = displayAlert($error, 'error');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-plus me-2"></i>Add Score</h4>
            </div>
            <div class="card-body">
                <?php echo $alert ?? ''; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="schedule_id" class="form-label">Event *</label>
                        <?php 
                            if ($_SESSION['user']['role'] !== 'admin'):
                                $schedules = array_filter($schedules, fn($s) => $s['sport_id'] === $_SESSION['user']['sports_id'] && $s['status'] === 'ongoing');
                        ?>
                        <?php endif; ?>
                        <select class="form-select" id="schedule_id" name="schedule_id" required>
                            <option value="">Select Event</option>
                            <?php foreach ($schedules as $schedule): ?>
                                <option value="<?php echo $schedule['id']; ?>">
                                    <?php echo htmlspecialchars($schedule['event_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="participant_id" class="form-label">Participant *</label>
                        <?php if ($_SESSION['user']['role'] !== 'admin'):
                            $teams = array_filter($teams, fn($t) => $t['sport_id'] === $_SESSION['user']['sports_id']);
                            $team_ids = array_map(fn($t) => $t['id'], $teams);
                            $participants = array_filter($participants, fn($p) => in_array($p['team_id'], $team_ids)); 
                        ?>
                        <?php endif; ?>
                        <select class="form-select" id="participant_id" name="participant_id" required>
                            <option value="">Select Participant</option>
                            <?php foreach ($participants as $participant): ?>
                                <option value="<?php echo $participant['id']; ?>">
                                    <?php echo htmlspecialchars($participant['team_name']); ?> - 
                                    <?php echo htmlspecialchars($participant['university_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="score" class="form-label">Score *</label>
                        <input type="number" step="0.01" class="form-control" id="score" name="score" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Score
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>