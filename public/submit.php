<?php
require_once __DIR__ . '/../classes/Article.php';

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
        // Load existing
        $file = __DIR__ . '/../storage/articles.json';
        $articles = Article::loadAll();

        // Determine next ID
        $last = end($articles);
        $nextId = $last ? $last->id + 1 : 1;

        // Add new article
        $new = [
            'id' => $nextId,
            'title' => $title,
            'author' => $author,
            'date' => date('Y-m-d'),
            'content' => $content
        ];
        $articles[] = new Article($new);

        // Save to JSON
        file_put_contents($file, json_encode($articles, JSON_PRETTY_PRINT));

        header("Location: index.php");
        exit;
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