<?php
require 'db.php';
// Ensure $pdo is defined. If db.php didn't provide it, fall back to a local SQLite DB.
if (!isset($pdo)) {
    try {
        $pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Create basic tables if they don't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT);");
        $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (token TEXT PRIMARY KEY, user_id INTEGER);");
    } catch (Exception $e) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(["status" => "error", "message" => "Database connection not available."]);
        exit;
    }
}
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($action === 'register') {
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    try {
        $stmt->execute([$username, $password]);
        echo json_encode(["status" => "success"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Username might already exist."]);
    }
} elseif ($action === 'login') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(16)); // Generate random token
        $stmt = $pdo->prepare("INSERT INTO sessions (token, user_id) VALUES (?, ?)");
        $stmt->execute([$token, $user['id']]);
        echo json_encode(["status" => "success", "token" => $token]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    }
}
?>