<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

$schedResponse = $apiClient->get('/schedules');
$schedules = $schedResponse['success'] ? $schedResponse['data'] : [];

$response = $apiClient->get('/medals');
$medals = $response['success'] ? $response['data'] : [];

if (isset($_GET['delete'])) {
    $deleteResponse = $apiClient->delete('/medals/' . $_GET['delete']);
    if ($deleteResponse['success']) {
        $_SESSION['message'] = ['text' => 'Medal record deleted successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $deleteResponse['error']['message'] ?? 'Failed to delete medal record';
        $_SESSION['message'] = ['text' => $error, 'type' => 'error'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-medal me-2"></i>Medals</h2>
    <div>
        <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Medal</a>
        <a href="../../index.php#medal-tally" class="btn btn-success"><i class="fas fa-trophy me-1"></i>View Tally</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($medals)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Team</th>
                            <th>University</th>
                            <th>Event</th>
                            <th>Medal Type</th>
                            <th>Medal Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($_SESSION['user']['role'] !== 'admin'):

                            $schedules = array_filter($schedules, fn($sc) => $sc['sport_id'] === $_SESSION['user']['sports_id']);
                            $schedule_ids = array_map(fn($sc) => $sc['id'], $schedules);
                            $medals = array_filter($medals, fn($m) => in_array($m['schedule_id'], $schedule_ids));
                        ?>
                        <?php endif; ?>

                        <?php foreach ($medals as $medal): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medal['team_name']); ?></td>
                                <td><?php echo htmlspecialchars($medal['university_name']); ?></td>
                                <td><?php echo htmlspecialchars($medal['event_name']); ?></td>

                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($medal['medal_type']) {
                                            'gold' => 'warning',
                                            'silver' => 'secondary',
                                            'bronze' => 'bronze'
                                        };
                                    ?>">
                                        <i class="fas fa-medal me-1"></i>
                                        <?php echo ucfirst($medal['medal_type']); ?>
                                    </span>
                                </td>

                                <td>
                                    <strong><?php echo $medal['medal_count'] ?? 1; ?></strong>
                                </td>

                                <td>
                                    <a href="edit.php?id=<?php echo $medal['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?php echo $medal['id']; ?>)">
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
                <i class="fas fa-medal fa-3x text-muted mb-3"></i>
                <p class="text-muted">No medals awarded yet.</p>
                <a href="create.php" class="btn btn-primary">Award First Medal</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this medal record?')) {
        window.location.href = 'index.php?delete=' + id;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
