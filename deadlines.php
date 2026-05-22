<?php
require 'db.php';
header('Content-Type: application/json');

// Get token from URL (GET) or form body (POST)
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$stmt = $pdo->prepare("SELECT user_id FROM sessions WHERE token = ?");
$stmt->execute([$token]);
$session = $stmt->fetch();

if (!$session) {
    echo json_encode(["status" => "unauthorized"]);
    exit;
}
$user_id = $session['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM deadlines WHERE user_id = ? ORDER BY due_date ASC");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure boolean values are sent correctly to JS
    foreach ($tasks as &$task) {
        $task['is_completed'] = (bool)$task['is_completed'];
    }
    echo json_encode($tasks);
} 
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO deadlines (user_id, title, course, task_type, due_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $_POST['title'], $_POST['course'], $_POST['type'], $_POST['due_date']]);
        echo json_encode(["status" => "success"]);
    } elseif ($action === 'complete') {
        $stmt = $pdo->prepare("UPDATE deadlines SET is_completed = TRUE WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['id'], $user_id]);
        echo json_encode(["status" => "success"]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM deadlines WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['id'], $user_id]);
        echo json_encode(["status" => "success"]);
    }
}
?>