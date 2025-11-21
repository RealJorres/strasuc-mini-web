<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitizeInput($_POST['name']),
        'abbreviation' => sanitizeInput($_POST['abbreviation'])
    ];

    $response = $apiClient->post('/universities', $data);
    
    if ($response['success']) {
        $_SESSION['message'] = [
            'text' => 'University created successfully!',
            'type' => 'success'
        ];
        redirect('../universities/index.php');
    } else {
        $error = $response['error']['message'] ?? 'Failed to create university';
        $alert = displayAlert($error, 'error');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-plus me-2"></i>Add New University</h4>
            </div>
            <div class="card-body">
                <?php echo $alert ?? ''; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">University Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="abbreviation" class="form-label">Abbreviation</label>
                        <input type="text" class="form-control" id="abbreviation" name="abbreviation" 
                               placeholder="e.g., UST, UP, ADMU">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create University
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>