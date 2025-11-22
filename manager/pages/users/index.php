<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Restrict access
if ($_SESSION['user']['role'] !== 'admin') {
    echo "<div class='alert alert-danger mt-4'>Access denied. Admins only.</div>";
    require_once '../../includes/footer.php';
    exit;
}

// Fetch users
$response = $apiClient->get('/users');
$users = $response['success'] ? $response['data'] : [];

$sportsResponse = $apiClient->get('/sports');
$sports = $sportsResponse['success'] ? $sportsResponse['data'] : [];

if (isset($_GET['delete'])) {
    $deleteResponse = $apiClient->delete('/users/' . $_GET['delete']);
    if ($deleteResponse['success']) {
        $_SESSION['message'] = ['text' => 'User deleted successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $deleteResponse['error']['message'] ?? 'Failed to delete user';
        $_SESSION['message'] = ['text' => $error, 'type' => 'error'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users-cog me-2"></i>User Management</h2>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add User</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($users)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Sports ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                <td>
                                    <?php $sport_names = array_column($sports, 'name', 'id'); 
                                        echo $sport_names[$user['sports_id']] ?? '<span class="text-muted">None</span>';
                                    ?>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="confirmDelete(<?= $user['id'] ?>)">
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
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted">No users found.</p>
                <a href="create.php" class="btn btn-primary">Add First User</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function confirmDelete(id) {
        if (confirm('Are you sure you want to delete this user?')) {
            window.location.href = 'index.php?delete=' + id;
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>