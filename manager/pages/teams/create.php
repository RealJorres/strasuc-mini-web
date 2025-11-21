<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Fetch universities and sports for dropdowns
$universitiesResponse = $apiClient->get('/universities');
$sportsResponse = $apiClient->get('/sports');
$universities = $universitiesResponse['success'] ? $universitiesResponse['data'] : [];
$sports = $sportsResponse['success'] ? $sportsResponse['data'] : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitizeInput($_POST['name']),
        'university_id' => sanitizeInput($_POST['university_id']),
        'sport_id' => sanitizeInput($_POST['sport_id'])
    ];

    $response = $apiClient->post('/teams', $data);
    
    if ($response['success']) {
        $_SESSION['message'] = ['text' => 'Team created successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $response['error']['message'] ?? 'Failed to create team';
        $alert = displayAlert($error, 'error');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-plus me-2"></i>Add New Team</h4>
            </div>
            <div class="card-body">
                <?php echo $alert ?? ''; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Team Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="university_id" class="form-label">University *</label>
                        <select class="form-select" id="university_id" name="university_id" required>
                            <option value="">Select University</option>
                            <?php foreach ($universities as $university): ?>
                                <option value="<?php echo $university['id']; ?>">
                                    <?php echo htmlspecialchars($university['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <?php 
                            if ($_SESSION['user']['role'] !== 'admin'):
                                $sports = array_filter($sports, fn($s) => $s['id'] === $_SESSION['user']['sports_id']);
                        ?>
                        <?php endif; ?>
                        <label for="sport_id" class="form-label">Sport *</label>
                        <select class="form-select" id="sport_id" name="sport_id" required>
                            
                            <option value="">Select Sport</option>
                            <?php foreach ($sports as $sport): ?>
                                <option value="<?php echo $sport['id']; ?>">
                                    <?php echo htmlspecialchars($sport['name']); ?> (<?php echo $sport['category']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create Team
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>