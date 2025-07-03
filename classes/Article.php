<?php

class Article {
    public int $id;
    public string $title;
    public string $author;
    public string $date;
    public string $content;

    public function __construct(array $data) {
        $this->id = $data['id'];
        $this->title = $data['title'];
        $this->author = $data['author'];
        $this->date = $data['date'];
        $this->content = $data['content'];
    }

    public static function loadAll(string $filePath): array {
        if (!file_exists($filePath)) return [];

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        return array_map(fn($item) => new Article($item), $data);
    }

    public static function findById(string $filePath, int $id): ?Article {
        $articles = self::loadAll($filePath);
        foreach ($articles as $article) {
            if ($article->id === $id) {
                return $article;
            }
        }
        return null;
    }
}