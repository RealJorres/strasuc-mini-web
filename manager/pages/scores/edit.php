<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$id = $_GET['id'];
$response = $apiClient->get('/scores/' . $id);
$score = $response['success'] ? $response['data'] : null;

if (!$score) {
    $_SESSION['message'] = ['text' => 'Score not found!', 'type' => 'error'];
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'score' => sanitizeInput($_POST['score']),
        'rank' => sanitizeInput($_POST['rank'])
    ];

    $response = $apiClient->put('/scores/' . $id, $data);
    
    if ($response['success']) {
        $_SESSION['message'] = ['text' => 'Score updated successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $response['error']['message'] ?? 'Failed to update score';
        $alert = displayAlert($error, 'error');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-edit me-2"></i>Edit Score</h4>
            </div>
            <div class="card-body">
                <?php echo $alert ?? ''; ?>
                <div class="mb-3 p-3 bg-light rounded">
                    <small class="text-muted">Team:</small>
                    <p class="mb-1"><?php echo htmlspecialchars($score['team_name']); ?></p>
                    <small class="text-muted">University:</small>
                    <p class="mb-1"><?php echo htmlspecialchars($score['university_name']); ?></p>
                    <small class="text-muted">Event:</small>
                    <p class="mb-0"><?php echo htmlspecialchars($score['event_name']); ?></p>
                </div>
                <form method="POST">
                    <div class="mb-3">
                        <label for="score" class="form-label">Score *</label>
                        <input type="number" step="0.01" class="form-control" id="score" name="score" 
                               value="<?php echo $score['score']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="rank" class="form-label">Rank *</label>
                        <input type="number" class="form-control" id="rank" name="rank" 
                               value="<?php echo $score['rank']; ?>" min="1" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Score
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>