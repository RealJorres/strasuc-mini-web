<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

$response = $apiClient->get('/sports');
$sports = $response['success'] ? $response['data'] : [];

if (isset($_GET['delete'])) {
    $deleteResponse = $apiClient->delete('/sports/' . $_GET['delete']);
    if ($deleteResponse['success']) {
        $_SESSION['message'] = ['text' => 'Sport deleted successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $deleteResponse['error']['message'] ?? 'Failed to delete sport';
        $_SESSION['message'] = ['text' => $error, 'type' => 'error'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-baseball-ball me-2"></i>Sports</h2>
    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
        <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Sport</a>
    <?php endif;?>  
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($sports)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <?php if ($_SESSION['user']['role'] !== 'admin'): ?>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        <?php endif;?>
                    </thead>
                    <tbody>
                        <?php foreach ($sports as $sport): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sport['name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $sport['category'] === 'team' ? 'primary' : 'info'; ?>">
                                        <?php echo ucfirst($sport['category']); ?>
                                    </span>
                                </td>
                                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                    <td>
                                        <a href="edit.php?id=<?php echo $sport['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete(<?php echo $sport['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                <?php endif;?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-baseball-ball fa-3x text-muted mb-3"></i>
                <p class="text-muted">No sports found.</p>
                <a href="create.php" class="btn btn-primary">Add First Sport</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this sport?')) {
        window.location.href = 'index.php?delete=' + id;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>