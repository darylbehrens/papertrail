<?php
require_once __DIR__ . '/Database.php';

class Article
{
    public int $id;
    public string $title;
    public string $author;
    public string $body;
    public string $date;

    public function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->title = $data['title'];
        $this->author = $data['author'];
        $this->body = $data['body'];
        $this->date = $data['created_at'];
    }

    public static function loadAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC");
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => new Article($row), $rows);
    }

    public static function loadById(int $id): ?Article
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? new Article($row) : null;
    }

    public static function save(string $title, string $author, string $body): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO articles (title, author, body) VALUES (?, ?, ?)");
        return $stmt->execute([$title, $author, $body]);
    }

    public function getSummary(): string
    {
        return mb_substr($this->body, 0, 200) . '...';
    }
}
