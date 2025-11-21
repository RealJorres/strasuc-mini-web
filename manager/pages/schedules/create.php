<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

$sportsResponse = $apiClient->get('/sports');
$sports = $sportsResponse['success'] ? $sportsResponse['data'] : [];
$schedResponse = $apiClient->get('/schedules');
$schedules = $schedResponse['success'] ? $schedResponse['data'] : [];

// Format date for datetime-local input
//$formattedDate = date('Y-m-d\TH:i', strtotime($schedule['date']));

// Minimum date for validation (now)
$minDate = date('Y-m-d\TH:i');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dateInput = sanitizeInput($_POST['date']);

    if (strtotime($dateInput) < time()) {
        $alert = displayAlert('Event date and time cannot be in the past.', 'error');
    } else {
        $data = [
            'sport_id' => sanitizeInput($_POST['sport_id']),
            'event_name' => sanitizeInput($_POST['event_name']),
            'date' => sanitizeInput($_POST['date']),
            'venue' => sanitizeInput($_POST['venue']),
            'category' => sanitizeInput($_POST['category']),
            'round' => sanitizeInput($_POST['round']),
        ];

        $response = $apiClient->post('/schedules', $data);

        if ($response['success']) {
            $_SESSION['message'] = ['text' => 'Schedule created successfully!', 'type' => 'success'];
            redirect('index.php');
        } else {
            $error = $response['error']['message'] ?? 'Failed to create schedule';
            $alert = displayAlert($error, 'error');
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-plus me-2"></i>Add New Schedule</h4>
            </div>
            <div class="card-body">
                <?php echo $alert ?? ''; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="event_name" class="form-label">Event Name *</label>
                        <input type="text" class="form-control" id="event_name" name="event_name" required>
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
                                    <?php echo htmlspecialchars($sport['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Date & Time *</label>
                        <input type="datetime-local" class="form-control" id="date" name="date"
                            value=""
                            min="<?php echo $minDate; ?>"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="venue" class="form-label">Venue *</label>
                        <input type="text" class="form-control" id="venue" name="venue" required>
                    </div>

                    <label for="category" class="form-label">Category *</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="men">Men</option>
                        <option value="women">Women</option>
                        <option value="mixed">Mixed</option>
                    </select>

                    <label for="round" class="form-label">Round *</label>
                    <select class="form-select" id="round" name="round" required>
                        <option value="">Select Round</option>
                        <option value="elimination">Elimination</option>
                        <option value="semis">Semis</option>
                        <option value="championship">Championship</option>
                    </select>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="scheduled" selected>Scheduled</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="ended">Ended</option>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create Schedule
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>