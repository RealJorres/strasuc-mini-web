<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Fetch schedules
$response = $apiClient->get('/schedules');
$schedules = $response['success'] ? $response['data'] : [];

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';

// Filter schedules based on status
if (!empty($statusFilter)) {
    $schedules = array_filter($schedules, function($schedule) use ($statusFilter) {
        return $schedule['status'] === $statusFilter;
    });
}

if (isset($_GET['delete'])) {
    $deleteResponse = $apiClient->delete('/schedules/' . $_GET['delete']);
    if ($deleteResponse['success']) {
        $_SESSION['message'] = ['text' => 'Schedule deleted successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $deleteResponse['error']['message'] ?? 'Failed to delete schedule';
        $_SESSION['message'] = ['text' => $error, 'type' => 'error'];
    }
}

// Status options for filter
$statusOptions = [
    'scheduled' => ['label' => 'Scheduled', 'class' => 'bg-primary'],
    'ongoing' => ['label' => 'Ongoing', 'class' => 'bg-warning'],
    'ended' => ['label' => 'Ended', 'class' => 'bg-success'],
    'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-danger']
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-calendar me-2"></i>Schedules</h2>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Schedule</a>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Schedules</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach ($statusOptions as $value => $option): ?>
                        <option value="<?php echo $value; ?>" 
                            <?php echo $statusFilter === $value ? 'selected' : ''; ?>>
                            <?php echo $option['label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Apply Filters
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Active Filters Display -->
        <?php if (!empty($statusFilter)): ?>
            <div class="mt-3">
                <small class="text-muted">Active filters:</small>
                <div class="d-flex flex-wrap gap-2 mt-1">
                    <span class="badge <?php echo $statusOptions[$statusFilter]['class']; ?>">
                        Status: <?php echo $statusOptions[$statusFilter]['label']; ?>
                    </span>
                    <span class="badge bg-secondary">
                        Showing <?php echo count($schedules); ?> schedules
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($schedules)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Sport</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            if ($_SESSION['user']['role'] !== 'admin'): 
                                $schedules = array_filter($schedules, fn($s) => $s['sport_id'] == $_SESSION['user']['sports_id']);    
                        ?>
                            
                        <?php endif; ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($schedule['event_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($schedule['sport_name']); ?></td>
                                <td><?php echo formatDate($schedule['date']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['venue']); ?></td>
                                <td>
                                    <span class="badge <?php echo $statusOptions[$schedule['status']]['class']; ?>">
                                        <?php echo $statusOptions[$schedule['status']]['label']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $schedule['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="confirmDelete(<?php echo $schedule['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Schedules Found</h5>
                <p class="text-muted">
                    <?php if (!empty($statusFilter)): ?>
                        No schedules match your current filters. 
                        <a href="index.php" class="text-decoration-none">Clear filters</a> to see all schedules.
                    <?php else: ?>
                        Get started by adding your first schedule.
                    <?php endif; ?>
                </p>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Add First Schedule
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this schedule?')) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('delete', id);
        window.location.href = 'index.php?' + urlParams.toString();
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>