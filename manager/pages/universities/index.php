<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Fetch universities
$response = $apiClient->get('/universities');
$universities = $response['success'] ? $response['data'] : [];

// Handle delete action
if (isset($_GET['delete'])) {
    $deleteResponse = $apiClient->delete('/universities/' . $_GET['delete']);
    if ($deleteResponse['success']) {
        $_SESSION['message'] = [
            'text' => 'University deleted successfully!',
            'type' => 'success'
        ];
        redirect('index.php');
    } else {
        $error = $deleteResponse['error']['message'] ?? 'Failed to delete university';
        $_SESSION['message'] = [
            'text' => $error,
            'type' => 'error'
        ];
    }
}
?>
<style>
    td{
        justify-content: center;
        align-content: center;
    }
    td.img-logo img{
        width: 50px;
        justify-content: center;
        align-content: center;
        margin: 0 30px;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-university me-2"></i>Universities</h2>
    <?php if ($_SESSION['user']['role'] ==='admin'): ?>
    <a href="create.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Add University
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($universities)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Abbreviation</th>
                            <?php if ($_SESSION['user']['role'] ==='admin'): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($universities as $university): ?>
                            <tr>
                                <td class="img-logo">
                                    <img src="../../assets/img/sucs_logo/<?= $university['abbreviation'] ?>.png" width=30px alt="university_logo">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($university['name']); ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($university['abbreviation'])): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($university['abbreviation']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($_SESSION['user']['role'] ==='admin'): ?>
                                <td>
                                    <a href="edit.php?id=<?php echo $university['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?php echo $university['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-university fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Universities Found</h5>
                <p class="text-muted">Get started by adding your first university.</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Add First University
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this university? This action cannot be undone.')) {
        window.location.href = 'index.php?delete=' + id;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>