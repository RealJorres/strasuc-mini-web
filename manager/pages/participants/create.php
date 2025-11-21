<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Fetch schedules and teams for dropdowns
$schedulesResponse = $apiClient->get('/schedules');
$teamsResponse = $apiClient->get('/teams');
$schedules = $schedulesResponse['success'] ? $schedulesResponse['data'] : [];
$teams = $teamsResponse['success'] ? $teamsResponse['data'] : [];

// Organize teams by sport_id for filtering
$teamsBySport = [];
foreach ($teams as $team) {
    // We need to get the sport_id for each team
    // Since our team data doesn't include sport_id directly in the API response,
    // we'll need to restructure our approach
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'schedule_id' => sanitizeInput($_POST['schedule_id']),
        'team_id' => sanitizeInput($_POST['team_id'])
    ];

    $response = $apiClient->post('/participants', $data);
    
    if ($response['success']) {
        $_SESSION['message'] = ['text' => 'Participant added successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $response['error']['message'] ?? 'Failed to add participant';
        $alert = displayAlert($error, 'error');
    }
}

// Create a mapping of schedule_id to sport_id
$scheduleSportMap = [];
foreach ($schedules as $schedule) {
    $scheduleSportMap[$schedule['id']] = $schedule['sport_id'];
}

// Create a mapping of team_id to sport_id
$teamSportMap = [];
foreach ($teams as $team) {
    $teamSportMap[$team['id']] = $team['sport_id'];
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-plus me-2"></i>Add Participant</h4>
            </div>
            <div class="card-body">
                <?php echo $alert ?? ''; ?>
                <form method="POST" id="participantForm">
                    <div class="mb-3">
                        <?php 
                            if ($_SESSION['user']['role'] !== 'admin'):
                                $schedules = array_filter($schedules, fn($s) => $s['sport_id'] === $_SESSION['user']['sports_id']);
                        ?>
                        <?php endif; ?>
                        <label for="schedule_id" class="form-label">Schedule *</label>
                        <select class="form-select" id="schedule_id" name="schedule_id" required onchange="filterTeams()">
                            <option value="">Select Event</option>
                            <?php foreach ($schedules as $schedule): ?>
                                <option value="<?php echo $schedule['id']; ?>" data-sport-id="<?php echo $schedule['sport_id']; ?>">
                                    <?php echo htmlspecialchars($schedule['event_name']); ?> - 
                                    <?php echo formatDate($schedule['date']); ?> (<?php echo htmlspecialchars($schedule['sport_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <?php 
                            if ($_SESSION['user']['role'] !== 'admin'):
                                $teams = array_filter($teams, fn($t) => $t['sport_id'] === $_SESSION['user']['sports_id']);
                        ?>
                        <?php endif; ?>
                        <label for="team_id" class="form-label">Team *</label>
                        <select class="form-select" id="team_id" name="team_id" required>
                            <option value="">Select Team</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>" data-sport-id="<?php echo $team['sport_id']; ?>">
                                    <?php echo htmlspecialchars($team['name']); ?> - 
                                    <?php echo htmlspecialchars($team['university_name']); ?> (<?php echo htmlspecialchars($team['sport_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text" id="teamHelpText">Please select an event first to see available teams</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Participant
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Store all teams for filtering
const allTeams = [
    <?php foreach ($teams as $team): ?>
    {
        id: <?php echo $team['id']; ?>,
        sportId: <?php echo $team['sport_id']; ?>,
        name: "<?php echo htmlspecialchars($team['name']); ?>",
        university: "<?php echo htmlspecialchars($team['university_name']); ?>",
        sport: "<?php echo htmlspecialchars($team['sport_name']); ?>"
    },
    <?php endforeach; ?>
];

function filterTeams() {
    const scheduleSelect = document.getElementById('schedule_id');
    const teamSelect = document.getElementById('team_id');
    const helpText = document.getElementById('teamHelpText');
    
    // Clear current options except the first one
    while (teamSelect.options.length > 1) {
        teamSelect.remove(1);
    }
    
    // Reset team selection
    teamSelect.value = '';
    
    if (!scheduleSelect.value) {
        helpText.textContent = 'Please select an event first to see available teams';
        helpText.className = 'form-text text-muted';
        return;
    }
    
    // Get selected sport ID from the schedule
    const selectedOption = scheduleSelect.options[scheduleSelect.selectedIndex];
    const selectedSportId = selectedOption.getAttribute('data-sport-id');
    
    // Filter teams by sport_id
    const filteredTeams = allTeams.filter(team => team.sportId == selectedSportId);
    
    if (filteredTeams.length === 0) {
        helpText.textContent = 'No teams available for this sport. Please create teams for this sport first.';
        helpText.className = 'form-text text-warning';
        return;
    }
    
    // Add filtered teams to the dropdown
    filteredTeams.forEach(team => {
        const option = document.createElement('option');
        option.value = team.id;
        option.textContent = `${team.name} - ${team.university}`;
        option.setAttribute('data-sport-id', team.sportId);
        teamSelect.appendChild(option);
    });
    
    helpText.textContent = `${filteredTeams.length} team(s) available for this sport`;
    helpText.className = 'form-text text-success';
}

// Initialize team filtering on page load
document.addEventListener('DOMContentLoaded', function() {
    // If a schedule is already selected (e.g., form submission failed), filter teams
    const scheduleSelect = document.getElementById('schedule_id');
    if (scheduleSelect.value) {
        filterTeams();
        
        // If a team was previously selected, try to restore it
        const teamSelect = document.getElementById('team_id');
        const previouslySelectedTeam = "<?php echo $_POST['team_id'] ?? ''; ?>";
        if (previouslySelectedTeam) {
            setTimeout(() => {
                teamSelect.value = previouslySelectedTeam;
            }, 100);
        }
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>