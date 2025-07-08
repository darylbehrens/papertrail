<?php
require_once __DIR__ . '/../classes/Database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Missing article ID.");
}

$pdo = Database::getConnection();

$stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id");
$stmt->execute([':id' => $id]);

header("Location: index.php");
exit;
