<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

$response = $apiClient->get('/medals/refs');
$medalRefs = $response['success'] ? $response['data'] : [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-medal me-2"></i>Medal Types</h2>
</div>

<div class="card">
    <div class="card-body">
        <!-- Non-dismissible alert -->
        <div class="bg-opacity-10 border rounded mb-4 bg-info p-4">
            <i class="fas fa-info-circle me-2"></i>
            These medal types define the points system for awards. Points are automatically assigned when awarding medals.
        </div>
        
        <?php if (!empty($medalRefs)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Medal Type</th>
                            <th>Points</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medalRefs as $medalRef): ?>
                            <tr>
                                <td>
                                    <span class="badge 
                                        <?php 
                                        switch($medalRef['name']) {
                                            case 'gold': echo 'bg-warning'; break;
                                            case 'silver': echo 'bg-secondary'; break;
                                            case 'bronze': echo 'bg-bronze'; break;
                                            default: echo 'bg-primary';
                                        }
                                        ?>">
                                        <i class="fas fa-medal me-1"></i>
                                        <?php echo ucfirst($medalRef['name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="fs-5"><?php echo $medalRef['points']; ?></strong>
                                    <small class="text-muted">point(s)</small>
                                </td>
                                <td><?php echo htmlspecialchars($medalRef['description'] ?? 'No description'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-medal fa-3x text-muted mb-3"></i>
                <p class="text-muted">No medal types configured.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>