<?php
// models/Article.php
// CMS Articles & Community Announcements

require_once __DIR__ . '/../core/Database.php';

class Article {
    public static function all() {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM `articles` ORDER BY `created_at` DESC")->fetchAll();
    }

    public static function published(int $limit = 6) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `articles` WHERE `is_published` = 1 ORDER BY `created_at` DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function findBySlug(string $slug) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `articles` WHERE `slug` = ? AND `is_published` = 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row) {
            $pdo->prepare("UPDATE `articles` SET `views_count` = `views_count` + 1 WHERE `id` = ?")->execute([$row['id']]);
        }
        return $row ?: null;
    }

    public static function create(array $data) {
        $pdo = Database::getConnection();
        $slug = self::generateSlug($data['title']);
        $stmt = $pdo->prepare("
            INSERT INTO `articles` (`title`, `slug`, `category`, `excerpt`, `content`, `cover_image`, `is_published`)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $slug,
            isset($data['category']) ? $data['category'] : 'Pengumuman',
            $data['excerpt'],
            $data['content'],
            isset($data['cover_image']) ? $data['cover_image'] : null,
            isset($data['is_published']) ? $data['is_published'] : 1
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function delete(int $id) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `articles` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    private static function generateSlug(string $text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) $text = 'artikel-' . time();
        return $text . '-' . mt_rand(100, 999);
    }
}
