<?php
require_once __DIR__ . '/../classes/Article.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = Article::loadById($id);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $article ? htmlspecialchars($article->title) : 'Article Not Found' ?></title>
    <meta charset="UTF-8">
</head>
<body>

<nav>
    <a href="index.php">Home</a>
    <a href="submit.php">Submit</a>
</nav>

<?php if (!$article): ?>
    <h1>404 - Article Not Found</h1>
<?php else: ?>
    <h1><?= htmlspecialchars($article->title) ?></h1>
    <p><em>By <?= htmlspecialchars($article->author) ?> on <?= $article->date ?></em></p>
    <p><?= nl2br(htmlspecialchars($article->body)) ?></p>
<?php endif; ?>

<p><a href="index.php">← Back to Home</a></p>

</body>
</html>