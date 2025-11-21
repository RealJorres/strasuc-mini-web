<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

$response = $apiClient->get('/scores');
$scores = $response['success'] ? $response['data'] : [];

$response = $apiClient->get('/schedules');
$schedules = $response['success'] ? $response['data'] : [];


if (isset($_GET['delete'])) {
    $deleteResponse = $apiClient->delete('/scores/' . $_GET['delete']);
    if ($deleteResponse['success']) {
        $_SESSION['message'] = ['text' => 'Score deleted successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $deleteResponse['error']['message'] ?? 'Failed to delete score';
        $_SESSION['message'] = ['text' => $error, 'type' => 'error'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-chart-line me-2"></i>Scores</h2>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Score</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($scores)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Team</th>
                            <th>University</th>
                            <th>Event</th>
                            <th>Score</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($_SESSION['user']['role'] !== 'admin'):

                            $schedules = array_filter($schedules, fn($sc) => $sc['sport_id'] === $_SESSION['user']['sports_id']);
                            $schedule_ids = array_map(fn($sc) => $sc['id'], $schedules);
                            $scores = array_filter($scores, fn($c) => in_array($c['schedule_id'], $schedule_ids));
                        ?>
                        <?php endif; ?>
                        <?php foreach ($scores as $score): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($score['team_name']); ?></td>
                                <td><?php echo htmlspecialchars($score['university_name']); ?></td>
                                <td><?php echo htmlspecialchars($score['event_name']); ?></td>
                                <td><strong><?php echo $score['score']; ?></strong></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $score['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?php echo $score['id']; ?>)">
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
                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                <p class="text-muted">No scores found.</p>
                <a href="create.php" class="btn btn-primary">Add First Score</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this score?')) {
        window.location.href = 'index.php?delete=' + id;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>