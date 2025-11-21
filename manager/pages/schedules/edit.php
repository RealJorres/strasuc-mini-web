<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$id = $_GET['id'];
$response = $apiClient->get('/schedules/' . $id);
$schedule = $response['success'] ? $response['data'] : null;

if (!$schedule) {
    $_SESSION['message'] = ['text' => 'Schedule not found!', 'type' => 'error'];
    redirect('index.php');
}

$sportsResponse = $apiClient->get('/sports');
$sports = $sportsResponse['success'] ? $sportsResponse['data'] : [];

// Format date for datetime-local input
$formattedDate = date('Y-m-d\TH:i', strtotime($schedule['date']));

// Minimum date for validation (now)
$minDate = date('Y-m-d\TH:i');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dateInput = sanitizeInput($_POST['date']);

    // Server-side check: date cannot be in the past
    if (strtotime($dateInput) < time()) {
        $alert = displayAlert('Event date and time cannot be in the past.', 'error');
    } else {
        $data = [
            'sport_id'   => sanitizeInput($_POST['sport_id']),
            'event_name' => sanitizeInput($_POST['event_name']),
            'date'       => sanitizeInput($_POST['date']),
            'venue'      => sanitizeInput($_POST['venue']),
            'status'     => sanitizeInput($_POST['status']),
            'round'      => sanitizeInput($_POST['round']),
            'category'   => sanitizeInput($_POST['category'])
        ];

        $response = $apiClient->put('/schedules/' . $id, $data);
        
        if ($response['success']) {
            $_SESSION['message'] = ['text' => 'Schedule updated successfully!', 'type' => 'success'];
            redirect('index.php');
        } else {
            $error = $response['error']['message'] ?? 'Failed to update schedule';
            $alert = displayAlert($error, 'error');
        }
    }
}

// Format date for datetime-local input
$formattedDate = date('Y-m-d\TH:i', strtotime($schedule['date']));
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-edit me-2"></i>Edit Schedule</h4>
            </div>
            <div class="card-body">
                <?php echo $alert ?? ''; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="event_name" class="form-label">Event Name *</label>
                        <input type="text" class="form-control" id="event_name" name="event_name" 
                               value="<?php echo htmlspecialchars($schedule['event_name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="sport_id" class="form-label">Sport *</label>
                        <select class="form-select" id="sport_id" name="sport_id" required>
                            <?php foreach ($sports as $sport): ?>
                                <option value="<?php echo $sport['id']; ?>" 
                                    <?php echo $sport['id'] == $schedule['sport_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sport['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Date & Time *</label>
                        <input type="datetime-local" class="form-control" id="date" name="date" 
                               value="<?php echo $formattedDate; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="venue" class="form-label">Venue *</label>
                        <input type="text" class="form-control" id="venue" name="venue" 
                               value="<?php echo htmlspecialchars($schedule['venue']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Category *</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="men" <?php echo $schedule['category'] === 'men' ? 'selected' : ''; ?>>Men</option>
                            <option value="women" <?php echo $schedule['category'] === 'women' ? 'selected' : ''; ?>>Women</option>
                            <option value="mixed" <?php echo $schedule['category'] === 'mixed' ? 'selected' : ''; ?>>Mixed</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="round" class="form-label">Round *</label>
                        <select class="form-select" id="round" name="round" required>
                            <option value="">Select Round</option>
                            <option value="elimination" <?php echo $schedule['round'] === 'elimination' ? 'selected' : ''; ?>>Elimination</option>
                            <option value="semis" <?php echo $schedule['round'] === 'semis' ? 'selected' : ''; ?>>Semis</option>
                            <option value="championship" <?php echo $schedule['round'] === 'championship' ? 'selected' : ''; ?>>Championship</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="scheduled" <?php echo $schedule['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="ongoing" <?php echo $schedule['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="cancelled" <?php echo $schedule['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="ended" <?php echo $schedule['status'] === 'ended' ? 'selected' : ''; ?>>Ended</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Schedule
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
