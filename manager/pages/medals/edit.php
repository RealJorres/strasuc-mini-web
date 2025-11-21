<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$id = $_GET['id'];
$response = $apiClient->get('/medals/' . $id);
$medal = $response['success'] ? $response['data'] : null;

if (!$medal) {
    $_SESSION['message'] = ['text' => 'Medal record not found!', 'type' => 'error'];
    redirect('index.php');
}

$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'medal_type'  => sanitizeInput($_POST['medal_type']),
        'medal_count' => sanitizeInput($_POST['medal_count']),
        'points'      => sanitizeInput($_POST['points'])
    ];

    $response = $apiClient->put('/medals/' . $id, $data);

    if ($response['success']) {
        $_SESSION['message'] = ['text' => 'Medal updated successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $response['error']['message'] ?? 'Failed to update medal';
        $alert = displayAlert($error, 'error');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-edit me-2"></i>Edit Medal Record</h4>
            </div>
            <div class="card-body">
                <?php echo $alert; ?>

                <div class="mb-3 p-3 bg-light rounded">
                    <small class="text-muted">Team:</small>
                    <p class="mb-1"><?php echo htmlspecialchars($medal['team_name']); ?></p>
                    <small class="text-muted">University:</small>
                    <p class="mb-1"><?php echo htmlspecialchars($medal['university_name']); ?></p>
                    <small class="text-muted">Event:</small>
                    <p class="mb-0"><?php echo htmlspecialchars($medal['event_name']); ?></p>
                </div>

                <form method="POST">

                    <!-- Medal Type -->
                    <div class="mb-3">
                        <label for="medal_type" class="form-label">Medal Type *</label>
                        <select class="form-select" id="medal_type" name="medal_type" required onchange="autoSetPoints()">
                            <option value="">Select Medal Type</option>
                            <option value="gold"   <?= $medal['medal_type'] === 'gold' ? 'selected' : '' ?>>Gold</option>
                            <option value="silver" <?= $medal['medal_type'] === 'silver' ? 'selected' : '' ?>>Silver</option>
                            <option value="bronze" <?= $medal['medal_type'] === 'bronze' ? 'selected' : '' ?>>Bronze</option>
                        </select>
                    </div>

                    <!-- Medal Count -->
                    <div class="mb-3">
                        <label for="medal_count" class="form-label">Medal Count *</label>
                        <input type="number" 
                               class="form-control" 
                               id="medal_count" 
                               name="medal_count" 
                               min="1" 
                               required
                               value="<?= htmlspecialchars($medal['medal_count']); ?>">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Medal
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Optionally auto-set points based on medal type
function autoSetPoints() {
    const type = document.getElementById('medal_type').value;
    const pointsInput = document.getElementById('points');

    const defaults = {
        gold: 5,
        silver: 3,
        bronze: 1
    };

    if (defaults[type] !== undefined) {
        pointsInput.value = defaults[type];
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
