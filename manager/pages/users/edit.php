<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

if ($_SESSION['user']['role'] !== 'admin') {
    echo "<div class='alert alert-danger mt-4'>Access denied. Admins only.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$id = $_GET['id'] ?? null;

$response = $apiClient->get("/users/$id");
$user = $response['success'] ? $response['data'] : null;

$sportsResponse = $apiClient->get('/sports');
$sports = $sportsResponse['success'] ? $sportsResponse['data'] : [];

if (!$user) {
    echo "<div class='alert alert-danger mt-4'>User not found.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'username' => $_POST['username'],
        'role' => $_POST['role'],
        'sports_id' => $_POST['sports_id']
    ];

    if (!empty($_POST['password'])) {
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $payload['password'] = $hashedPassword;
    }

    $updateResponse = $apiClient->put("/users/$id", $payload);

    if ($updateResponse['success']) {
        $_SESSION['message'] = ['text' => 'User updated successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $updateResponse['error']['message'] ?? 'Failed to update user';
    }
}
?>

<div class="mb-4">
    <h2><i class="fas fa-edit me-2"></i>Edit User</h2>
</div>

<div class="card">
    <div class="card-body">

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input required type="text" name="username" class="form-control"
                    value="<?= htmlspecialchars($user['username']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">New Password (leave blank to keep existing)</label>
                <input type="password" name="password" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" id="roleSelect" required>
                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="official" <?= $user['role'] == 'official' ? 'selected' : '' ?>>Official</option>
                </select>
            </div>

            <div class="mb-3" id="sportsGroup" style="<?= $user['role'] == 'official' ? '' : 'display:none;' ?>">
                <label class="form-label">Sports</label>
                <select class="form-select" name="sports_id">
                    <option value="">-- Select Sports --</option>
                    <?php foreach ($sports as $sport): ?>
                        <option value="<?php echo $sport['id'] ?>"><?php echo $sport['name'] ?></option>
                    <?php endforeach; ?>

                </select>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update User</button>
            <a href="index.php" class="btn btn-outline-secondary">Cancel</a>

        </form>
    </div>
</div>

<script>
    document.getElementById('roleSelect').addEventListener('change', function() {
        document.getElementById('sportsGroup').style.display =
            this.value === 'official' ? 'block' : 'none';
    });
</script>

<?php require_once '../../includes/footer.php'; ?>