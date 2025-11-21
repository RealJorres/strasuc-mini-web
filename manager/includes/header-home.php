<?php
ob_start();
require_once 'config/config.php';
require_once 'api_client.php';
require_once 'functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: login.php');
    exit();
}

echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
echo '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="icon" href="../manager/assets/img/icon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/img/main_logo_small.png" width="180" height="45" alt="STR&SUC Logo" class="me-2 rounded">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['logged_in']): ?>
                    <!-- Navigation for logged-in users -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-university me-1"></i>Universities
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="pages/universities/index.php">
                                        <i class="fas fa-list me-1"></i>View All
                                    </a></li>
                                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="pages/universities/create.php">
                                            <i class="fas fa-plus me-1"></i>Add New
                                        </a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-baseball-ball me-1"></i>Sports
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="pages/sports/index.php">
                                        <i class="fas fa-list me-1"></i>View All
                                    </a></li>
                                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="pages/sports/create.php">
                                            <i class="fas fa-plus me-1"></i>Add New
                                        </a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-users me-1"></i>Teams
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="pages/teams/index.php">
                                        <i class="fas fa-list me-1"></i>View All
                                    </a></li>
                                <li><a class="dropdown-item" href="pages/teams/create.php">
                                        <i class="fas fa-plus me-1"></i>Add New
                                    </a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-calendar me-1"></i>Schedules
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="pages/schedules/index.php">
                                        <i class="fas fa-list me-1"></i>View All
                                    </a></li>
                                <li><a class="dropdown-item" href="pages/schedules/create.php">
                                        <i class="fas fa-plus me-1"></i>Add New
                                    </a></li>
                            </ul>
                        </li>
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="pages/medal-refs/index.php">
                                <i class="fas fa-cog me-1"></i>Medal References
                            </a>
                        </li> -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-medal me-1"></i>Results
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="pages/participants/index.php">
                                        <i class="fas fa-user-friends me-1"></i>Participants
                                    </a></li>
                                <li><a class="dropdown-item" href="pages/scores/index.php">
                                        <i class="fas fa-chart-line me-1"></i>Scores
                                    </a></li>
                                <li><a class="dropdown-item" href="pages/medals/index.php">
                                        <i class="fas fa-trophy me-1"></i>Medals
                                    </a></li>
                            </ul>
                        </li>
                    </ul>

                    <!-- User dropdown for logged-in users -->
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <span class="dropdown-item-text">
                                        <small>Logged in as</small>
                                        <br>
                                        <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong>
                                    </span>
                                </li>
                                <?php if (isset($_SESSION['user'])): ?>
                                    <?php if ($_SESSION['user']['role'] === "admin"): ?>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="pages/users/index.php">
                                                <i class="fas fa-people-roof me-1"></i>Account Management
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                <?php else: ?>
                    <!-- Navigation for guests (not logged in) -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pages/medal-refs/index.php">
                                <i class="fas fa-cog me-1"></i>Medal References
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pages/medals/index.php">
                                <i class="fas fa-trophy me-1"></i>Medals
                            </a>
                        </li>
                    </ul>

                    <!-- Login link for guests -->
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <?php echo displayAlert($_SESSION['message']['text'], $_SESSION['message']['type']); ?>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>