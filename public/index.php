<?php
require_once __DIR__ . '/../classes/Article.php';

$articles = Article::loadAll(__DIR__ . '/../storage/articles.json');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Papertrail</title>
    <meta charset="UTF-8">
</head>
<body>
    <h1>Papertrail - Newsletter</h1>

    <?php if (empty($articles)): ?>
        <p>No articles yet.</p>
    <?php else: ?>
        <?php foreach ($articles as $article): ?>
            <article>
                <h2><a href="article.php?id=<?= $article->id ?>"><?= htmlspecialchars($article->title) ?></a></h2>
                <p><em>By <?= htmlspecialchars($article->author) ?> on <?= $article->date ?></em></p>
                <p><?= nl2br(htmlspecialchars(substr($article->content, 0, 200))) ?>...</p>
            </article>
            <hr>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>