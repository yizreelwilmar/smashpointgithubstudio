<?php
// app/Controllers/ArticleController.php
// CMS Articles Reader Controller

require_once __DIR__ . '/../Models/Article.php';

class ArticleController {
    public static function index(): void {
        $articles = Article::published(20);
        require __DIR__ . '/../../views/pages/articles.php';
    }

    public static function detail(): void {
        $slug = $_GET['slug'] ?? '';
        $article = Article::findBySlug($slug);
        if (!$article) {
            http_response_code(404);
            require __DIR__ . '/../../views/pages/404.php';
            return;
        }

        $related = Article::published(3);
        require __DIR__ . '/../../views/pages/article_detail.php';
    }
}
