<?php
require_once __DIR__ . '/../classes/Database.php';

$pdo = Database::getConnection();
$errors = [];

$id = $_GET['id'] ?? null;
$title = '';
$author = '';
$content = '';

if (!$id) {
    die('Missing article ID.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '') $errors[] = "Title is required.";
    if ($author === '') $errors[] = "Author is required.";
    if ($content === '') $errors[] = "Content is required.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE articles
            SET title = :title, author = :author, body = :body
            WHERE id = :id
        ");
        $stmt->execute([
            ':title' => $title,
            ':author' => $author,
            ':body' => $content,
            ':id' => $id
        ]);
        header("Location: index.php");
        exit;
    }
} else {
    // Load article to edit
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $article = $stmt->fetch();

    if (!$article) {
        die('Article not found.');
    }

    $title = $article['title'];
    $author = $article['author'];
    $content = $article['body'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Article</title>
    <meta charset="UTF-8">
</head>
<body>
    <h1>Edit Article</h1>

    <nav>
        <a href="index.php">Home</a>
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

        <button type="submit">Update</button>
    </form>

    <p><a href="index.php">← Back to Home</a></p>
</body>
</html>
