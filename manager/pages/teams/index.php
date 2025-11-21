<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Fetch all data for filters
$teamsResponse = $apiClient->get('/teams');
$universitiesResponse = $apiClient->get('/universities');
$sportsResponse = $apiClient->get('/sports');

$teams = $teamsResponse['success'] ? $teamsResponse['data'] : [];
$universities = $universitiesResponse['success'] ? $universitiesResponse['data'] : [];
$sports = $sportsResponse['success'] ? $sportsResponse['data'] : [];

// Get filter parameters
$universityFilter = $_GET['university'] ?? '';
$sportFilter = $_GET['sport'] ?? '';

// Filter teams based on selections
if (!empty($universityFilter) || !empty($sportFilter)) {
    $teams = array_filter($teams, function($team) use ($universityFilter, $sportFilter) {
        $universityMatch = empty($universityFilter) || $team['university_id'] == $universityFilter;
        $sportMatch = empty($sportFilter) || $team['sport_id'] == $sportFilter;
        return $universityMatch && $sportMatch;
    });
}

if (isset($_GET['delete'])) {
    $deleteResponse = $apiClient->delete('/teams/' . $_GET['delete']);
    if ($deleteResponse['success']) {
        $_SESSION['message'] = ['text' => 'Team deleted successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $deleteResponse['error']['message'] ?? 'Failed to delete team';
        $_SESSION['message'] = ['text' => $error, 'type' => 'error'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users me-2"></i>Teams</h2>
    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Team</a>
    <?php endif; ?>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Teams</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="university" class="form-label">University</label>
                <select class="form-select" id="university" name="university">
                    <option value="">All Universities</option>
                    <?php foreach ($universities as $university): ?>
                        <option value="<?php echo $university['id']; ?>" 
                            <?php echo $universityFilter == $university['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($university['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="sport" class="form-label">Sport</label>
                
                <?php if ($_SESSION['user']['role'] !== 'admin'): ?>
                    <select class="form-select" id="sport" name="sport" disabled>
                        <option value="">All Sports</option>
                        <?php foreach ($sports as $sport): ?>
                            <option value="<?php echo $sport['id']; ?>" 
                                <?php echo $sportFilter == $sport['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sport['name']); ?> 
                                (<?php echo ucfirst($sport['category']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else:?>
                    <select class="form-select" id="sport" name="sport">
                        <option value="">All Sports</option>
                        <?php foreach ($sports as $sport): ?>
                            <option value="<?php echo $sport['id']; ?>" 
                                <?php echo $sportFilter == $sport['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sport['name']); ?> 
                                (<?php echo ucfirst($sport['category']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="d-grid gap-2 d-md-flex">
                    <!-- <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Apply Filters
                    </button> -->
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Active Filters Display -->
        <?php if (!empty($universityFilter) || !empty($sportFilter)): ?>
            <div class="mt-3">
                <small class="text-muted">Active filters:</small>
                <div class="d-flex flex-wrap gap-2 mt-1">
                    <?php if (!empty($universityFilter)): 
                        $selectedUniversity = array_filter($universities, fn($u) => $u['id'] == $universityFilter);
                        $selectedUniversity = reset($selectedUniversity);
                    ?>
                        <span class="badge bg-primary">
                            University: <?php echo htmlspecialchars($selectedUniversity['name']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($sportFilter)): 
                        $selectedSport = array_filter($sports, fn($s) => $s['id'] == $sportFilter);
                        $selectedSport = reset($selectedSport);
                    ?>
                        <span class="badge bg-info">
                            Sport: <?php echo htmlspecialchars($selectedSport['name']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <span class="badge bg-secondary">
                        Showing <?php echo count($teams); ?> of <?php echo count($teamsResponse['success'] ? $teamsResponse['data'] : []); ?> teams
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($teams)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Team Name</th>
                            <th>University</th>
                            <th>Sport</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            if ($_SESSION['user']['role'] !== 'admin'):
                                $teams = array_filter($teams, fn($t) => $t['sport_id'] == $_SESSION['user']['sports_id'] )
                        ?>
                        <?php endif;?>
                        <?php foreach ($teams as $team): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($team['name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($team['university_name']); ?>
                                    <?php if (!empty($team['university_abbreviation'])): ?>
                                        <small class="text-muted">(<?php echo htmlspecialchars($team['university_abbreviation']); ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($team['sport_name']); ?></td>
                                <td>
                                    <?php 
                                    $sportCategory = '';
                                    foreach ($sports as $sport) {
                                        if ($sport['id'] == $team['sport_id']) {
                                            $sportCategory = $sport['category'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $sportCategory === 'team' ? 'primary' : 'info'; ?>">
                                        <?php echo ucfirst($sportCategory); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $team['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="confirmDelete(<?php echo $team['id']; ?>)">
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
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Teams Found</h5>
                <p class="text-muted">
                    <?php if (!empty($universityFilter) || !empty($sportFilter)): ?>
                        No teams match your current filters. 
                        <a href="index.php" class="text-decoration-none">Clear filters</a> to see all teams.
                    <?php else: ?>
                        Get started by adding your first team.
                    <?php endif; ?>
                </p>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Add First Team
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this team?')) {
        // Preserve filters when deleting
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('delete', id);
        window.location.href = 'index.php?' + urlParams.toString();
    }
}

// Auto-submit form when filters change (optional)
document.addEventListener('DOMContentLoaded', function() {
    const universitySelect = document.getElementById('university');
    const sportSelect = document.getElementById('sport');
    
    // Uncomment below for auto-filtering (removes need for Apply button)
    
    universitySelect.addEventListener('change', function() {
        if (this.value || sportSelect.value) {
            this.form.submit();
        }
    });
    
    sportSelect.addEventListener('change', function() {
        if (this.value || universitySelect.value) {
            this.form.submit();
        }
    });
    
});
</script>

<?php require_once '../../includes/footer.php'; ?>