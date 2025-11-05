<?php
header('Content-Type: application/json');

require __DIR__ . '/../db.php';

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($searchTerm) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT fullname FROM users WHERE fullname LIKE ? LIMIT 10");
    
    $stmt->execute([$searchTerm . '%']);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($users);

} catch (Exception $e) {
    error_log("User suggestion search failed: " . $e->getMessage());
    echo json_encode([]);
}
?>