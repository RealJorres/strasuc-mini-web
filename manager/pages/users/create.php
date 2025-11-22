<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/header.php';

if ($_SESSION['user']['role'] !== 'admin') {
    echo "<div class='alert alert-danger mt-4'>Access denied. Admins only.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$sportsResponse = $apiClient->get('/sports');
$sports = $sportsResponse['success'] ? $sportsResponse['data'] : [];

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $payload = [
        'username' => $_POST['username'],
        'password' => $hashedPassword,
        'role' => $_POST['role'],
        'sports_id' => $_POST['sports_id']
    ];

    $response = $apiClient->post('/users', $payload);

    if ($response['success']) {
        $_SESSION['message'] = ['text' => 'User created successfully!', 'type' => 'success'];
        redirect('index.php');
    } else {
        $error = $response['error']['message'] ?? "Failed to create user";
    }
}
?>

<div class="mb-4">
    <h2><i class="fas fa-user-plus me-2"></i>Add User</h2>
</div>

<div class="card">
    <div class="card-body">

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input required type="text" name="username" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input required type="password" name="password" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" id="roleSelect" required>
                    <option value="admin">Admin</option>
                    <option value="official">Official</option>
                </select>
            </div>

            <div class="mb-3" id="sportsGroup">
                <label class="form-label">Sports</label>
                <select class="form-select" name="sports_id">
                    <option value="">-- Select Sports --</option>
                    <?php foreach ($sports as $sport): ?>
                        <option value="<?php echo $sport['id'] ?>"><?php echo $sport['name'] ?></option>
                    <?php endforeach; ?>

                </select>
            </div>


            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save User</button>
            <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
    document.getElementById('roleSelect').addEventListener('change', function() {
        document.getElementById('sportsGroup').style.display = this.value === 'official' ? 'block' : 'none';
    });
</script>

<?php require_once '../../includes/footer.php'; ?>