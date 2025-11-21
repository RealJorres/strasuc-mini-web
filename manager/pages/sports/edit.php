<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$id = $_GET['id'];
$response = $apiClient->get('/sports/' . $id);
$sport = $response['success'] ? $response['data'] : null;

if (!$sport) {
    $_SESSION['message'] = ['text' => 'Sport not found!', 'type' => 'error'];
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitizeInput($_POST['name']),
        'category' => sanitizeInput($_POST['category'])
    ];

    $response = $apiClient->put('/sports/' . $id, $data);
    
    if ($response['success']) {
        $_SESSION['message'] = ['text' => 'Sport updated successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $response['error']['message'] ?? 'Failed to update sport';
        $alert = displayAlert($error, 'error');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-edit me-2"></i>Edit Sport</h4>
            </div>
            <div class="card-body">
                <?php echo $alert ?? ''; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Sport Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($sport['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category *</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="team" <?php echo $sport['category'] === 'team' ? 'selected' : ''; ?>>Team Sport</option>
                            <option value="individual" <?php echo $sport['category'] === 'individual' ? 'selected' : ''; ?>>Individual Sport</option>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Sport
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>