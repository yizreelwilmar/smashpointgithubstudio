<?php
// app/Models/Article.php
// CMS Articles & Announcements Model

require_once __DIR__ . '/../../core/Database.php';

class Article {
    public static function all(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM `articles` ORDER BY `created_at` DESC")->fetchAll();
    }

    public static function published(int $limit = 6): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM `articles` 
            WHERE `is_published` = 1 
            ORDER BY `created_at` DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function findBySlug(string $slug): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `articles` WHERE `slug` = ? AND `is_published` = 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row) {
            $pdo->prepare("UPDATE `articles` SET `views_count` = `views_count` + 1 WHERE `id` = ?")->execute([$row['id']]);
        }
        return $row ?: null;
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `articles` WHERE `id` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $slug = self::generateSlug($data['title']);
        $stmt = $pdo->prepare("
            INSERT INTO `articles` (`title`, `slug`, `category`, `excerpt`, `content`, `cover_image`, `is_published`)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $slug,
            $data['category'] ?? 'Pengumuman',
            $data['excerpt'],
            $data['content'],
            $data['cover_image'] ?? null,
            $data['is_published'] ?? 1
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `articles` 
            SET `title` = ?, `category` = ?, `excerpt` = ?, `content` = ?, `cover_image` = ?, `is_published` = ?
            WHERE `id` = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['category'] ?? 'Pengumuman',
            $data['excerpt'],
            $data['content'],
            $data['cover_image'] ?? null,
            $data['is_published'] ?? 1,
            $id
        ]);
    }

    public static function delete(int $id): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `articles` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    private static function generateSlug(string $text): string {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) {
            $text = 'artikel-' . time();
        }
        return $text . '-' . mt_rand(100, 999);
    }
}
