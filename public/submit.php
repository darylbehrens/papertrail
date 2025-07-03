<?php
require_once __DIR__ . '/../classes/Database.php';

$errors = [];
$title = '';
$author = '';
$content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '') $errors[] = "Title is required.";
    if ($author === '') $errors[] = "Author is required.";
    if ($content === '') $errors[] = "Content is required.";

    if (empty($errors)) {
        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare("
                INSERT INTO articles (title, author, body, created_at)
                VALUES (:title, :author, :body, NOW())
            ");

            $stmt->execute([
                ':title' => $title,
                ':author' => $author,
                ':body' => $content
            ]);

            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Article</title>
    <meta charset="UTF-8">
</head>
<body>
    <h1>Submit a New Article</h1>

    <nav>
        <a href="index.php">Home</a>
        <a href="submit.php">Submit</a>
    </nav>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="">
        <p><label>Title:<br>
            <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" size="40">
        </label></p>

        <p><label>Author:<br>
            <input type="text" name="author" value="<?= htmlspecialchars($author) ?>" size="40">
        </label></p>

        <p><label>Content:<br>
            <textarea name="content" rows="10" cols="60"><?= htmlspecialchars($content) ?></textarea>
        </label></p>

        <button type="submit">Submit</button>
    </form>

    <p><a href="index.php">← Back to Home</a></p>
</body>
</html>
