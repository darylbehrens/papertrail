<?php
    require_once __DIR__ . '/../classes/Article.php';

    $articles = Article::loadAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Papertrail</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/assets/styles.css">
</head>

<body>

    <div class="layout">

        <!-- Sidebar -->
        <aside class="sidebar expanded">
            <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
            <h3>🗂️ Sections</h3>
            <div class="folder">
                <div class="folder-header" onclick="toggleFolder(this)">▶️ Local</div>
                <div class="folder-content" style="display: none">
                    <a href="#">Clark County</a>
                    <a href="#">Police</a>
                </div>
            </div>

            <div class="folder">
                <div class="folder-header" onclick="toggleFolder(this)">▶️ Sports</div>
                <div class="folder-content" style="display: none">
                    <a href="#">Blazers</a>
                    <a href="#">Prep Scores</a>
                </div>
            </div>

            <div class="folder">
                <div class="folder-header" onclick="toggleFolder(this)">▶️ Opinion</div>
                <div class="folder-content" style="display: none">
                    <a href="#">Editorials</a>
                    <a href="#">Letters</a>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <main class="content">
            <h1>Papertrail - Newsletter</h1>

            <nav>
                <a href="index.php">Home</a>
                <a href="submit.php">Submit</a>
            </nav>

            <?php if (empty($articles)): ?>
                <p>No articles yet.</p>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <div class="article">
                        <h2>
                            <a href="article.php?id=<?= $article->id ?>">
                                <?= htmlspecialchars($article->title) ?>
                            </a>
                        </h2>
                        <p><em>By <?= htmlspecialchars($article->author) ?> on <?= $article->date ?></em></p>
                        <p><?= nl2br(htmlspecialchars($article->getSummary())) ?></p>
                    </div>
                <?php endforeach; ?>    
            <?php endif; ?>

            <aside class="about">
                <h3>About Papertrail</h3>
                <p>A modern local news site for Clark County readers — inspired by The Columbian.</p>
            </aside>

            <p><a href="submit.php">+ Submit a New Article</a></p>
        </main>
    </div>

    <script>
        function toggleFolder(el) {
            const content = el.nextElementSibling;
            const isVisible = window.getComputedStyle(content).display !== 'none';
            content.style.display = isVisible ? 'none' : 'block';
            el.innerText = (isVisible ? '▶️' : '🔽') + ' ' + el.innerText.slice(2);
        }

        function toggleSidebar() {
            const layout = document.querySelector('.layout');
            layout.classList.toggle('collapsed');
        }

    </script>

</body>
</html>
