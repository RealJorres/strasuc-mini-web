<?php
require_once 'config.php';

$DB_HOST = "localhost";
$DB_NAME = "sample_db_sports";
$DB_USER = "root";
$DB_PASSWORD = "";

try {
    $pdo = new PDO("mysql:host=" . $DB_HOST . ";dbname=" . $DB_NAME, $DB_USER, $DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sample users data - all with password 'password'
    $users = [
        ['admin', 'password', 'admin'],
        ['official1', 'password', 'official'],
        ['official2', 'password', 'official'],
        ['coach1', 'password', 'coach'],
        ['coach2', 'password', 'coach'],
        ['viewer1', 'password', 'viewer'],
        ['viewer2', 'password', 'viewer']
    ];

    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    
    $inserted = 0;
    foreach ($users as $user) {
        try {
            // Hash the password
            $hashedPassword = password_hash($user[1], PASSWORD_DEFAULT);
            
            $stmt->execute([$user[0], $hashedPassword, $user[2]]);
            $inserted++;
            echo "User '{$user[0]}' inserted successfully with hashed password.<br>";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                echo "User '{$user[0]}' already exists.<br>";
            } else {
                echo "Error inserting user '{$user[0]}': " . $e->getMessage() . "<br>";
            }
        }
    }

    echo "<br>Total users inserted: $inserted<br>";
    echo "<br><strong>All users have the password: 'password'</strong><br>";

} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>