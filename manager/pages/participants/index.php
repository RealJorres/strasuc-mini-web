<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

$response = $apiClient->get('/participants');
$participants = $response['success'] ? $response['data'] : [];

$response2 = $apiClient->get('/teams');
$teams = $response2['success'] ? $response2['data'] : [];

if (isset($_GET['delete'])) {
    $deleteResponse = $apiClient->delete('/participants/' . $_GET['delete']);
    if ($deleteResponse['success']) {
        $_SESSION['message'] = ['text' => 'Participant deleted successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $deleteResponse['error']['message'] ?? 'Failed to delete participant';
        $_SESSION['message'] = ['text' => $error, 'type' => 'error'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-user-friends me-2"></i>Participants</h2>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Participant</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($participants)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Team</th>
                            <th>University</th>
                            <th>Event</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($_SESSION['user']['role'] !== 'admin'):
                        
                            $teams = array_filter($teams, fn($t) => $t['sport_id'] === $_SESSION['user']['sports_id']);
                            $team_ids = array_map(fn($t) => $t['id'], $teams);
                            $participants = array_filter($participants, fn($p) => in_array($p['team_id'], $team_ids));
                                
                        ?>
                        <?php endif; ?>
                        <?php foreach ($participants as $participant): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($participant['team_name']); ?></td>
                                <td><?php echo htmlspecialchars($participant['university_name']); ?></td>
                                <td><?php echo htmlspecialchars($participant['event_name']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?php echo $participant['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                <p class="text-muted">No participants found.</p>
                <a href="create.php" class="btn btn-primary">Add First Participant</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this participant?')) {
        window.location.href = 'index.php?delete=' + id;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>