<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$id = $_GET['id'];
$response = $apiClient->get('/universities/' . $id);
$university = $response['success'] ? $response['data'] : null;

if (!$university) {
    $_SESSION['message'] = ['text' => 'University not found!', 'type' => 'error'];
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitizeInput($_POST['name']),
        'abbreviation' => sanitizeInput($_POST['abbreviation'])
    ];

    $response = $apiClient->put('/universities/' . $id, $data);
    
    if ($response['success']) {
        $_SESSION['message'] = ['text' => 'University updated successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $response['error']['message'] ?? 'Failed to update university';
        $alert = displayAlert($error, 'error');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-edit me-2"></i>Edit University</h4>
            </div>
            <div class="card-body">
                <?php echo $alert ?? ''; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">University Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($university['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="abbreviation" class="form-label">Abbreviation</label>
                        <input type="text" class="form-control" id="abbreviation" name="abbreviation" 
                               value="<?php echo htmlspecialchars($university['abbreviation'] ?? ''); ?>"
                               placeholder="e.g., UST, UP, ADMU">
                        <div class="form-text">Short code or acronym for the university</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update University
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>